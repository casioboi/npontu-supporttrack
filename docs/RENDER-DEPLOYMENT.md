# Render and Aiven deployment guide

This guide prepares Npontu SupportTrack for a Render Docker Web Service using the existing Dockerfile and an existing Aiven MySQL database. It does not replace the Ubuntu VPS deployment instructions in [DEPLOYMENT.md](DEPLOYMENT.md) and [PRODUCTION-HOSTING-AIVEN-VPS.md](PRODUCTION-HOSTING-AIVEN-VPS.md).

## Before you begin

- Confirm the intended production code and migrations are committed to `main` and available in the GitHub repository `casioboi/npontu-supporttrack`.
- Confirm you can administer the Aiven service and create/use a dedicated database user with only the privileges this application requires.
- Back up the database before applying any schema changes. Do not use production data for destructive tests.
- Generate an `APP_KEY` securely once (for example, locally with `php artisan key:generate --show` in a trusted Laravel checkout) and store it in Render. Do not commit it. Keep the same key across deploys; changing it invalidates encrypted data and existing sessions.
- Rotate any database password that has previously been exposed or reused. Keep secrets in Render's environment settings, never in Git or the image.

## 1. Connect GitHub and create the service

1. In the Render Dashboard choose **New + → Web Service**.
2. Connect GitHub if prompted and authorize the `casioboi/npontu-supporttrack` repository.
3. Select that repository.
4. Name the service, for example `npontu-supporttrack`.
5. Set **Branch** to `main`.
6. Set **Language/Runtime** to **Docker**. Use the repository root as the Docker build context and the existing `Dockerfile`; do not set a Compose file or use `compose.production.yaml` on Render.
7. Select a region supported by your Render account and as close as practical to the Aiven database's region. A nearby region can reduce latency, but does not guarantee network reachability.
8. Select the **Free** instance type initially, if available for this service and region.
9. Leave the Docker command/start command unset so the image's `ENTRYPOINT` and `CMD` start FrankenPHP. Render supplies `PORT`; the Caddyfile listens on that port and binds to `0.0.0.0`.

## 2. Configure production environment variables

In the service's **Environment** tab, add these values. Use the exact host, port, database name, and user shown by Aiven's service connection information. The database name may require a different value if the existing Aiven database is named differently; do not rename or recreate it to match this example without planning a migration.

| Key | Value |
| --- | --- |
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_KEY` | A securely generated, persistent `base64:...` production key |
| `APP_URL` | The HTTPS URL Render assigns to this service (or your configured custom domain) |
| `DB_CONNECTION` | `mysql` |
| `DB_HOST` | Aiven hostname |
| `DB_PORT` | Aiven port |
| `DB_DATABASE` | `npontu_supporttrack` (only if this is the actual existing database name) |
| `DB_USERNAME` | Dedicated application database user |
| `DB_PASSWORD` | Rotated database password |
| `MYSQL_ATTR_SSL_CA` | `/etc/secrets/aiven-ca.pem` |
| `SESSION_DRIVER` | `database` |
| `SESSION_ENCRYPT` | `true` |
| `SESSION_SECURE_COOKIE` | `true` |
| `SESSION_HTTP_ONLY` | `true` |
| `SESSION_SAME_SITE` | `lax` |
| `CACHE_STORE` | `database` |
| `QUEUE_CONNECTION` | `sync` |
| `LOG_CHANNEL` | `stderr` |
| `LOG_LEVEL` | `info` |

Do not set `PORT` yourself; Render injects it. The image's default `PORT=80` is only a local fallback. Avoid setting `DB_URL` alongside the individual `DB_*` variables.

### Configure email delivery

Personnel invitations and password-reset links require a working email provider. Laravel's default `MAIL_MAILER=log` writes messages to application logs and does not deliver email. Select and configure an email provider you control; configure and verify the sender address with that provider before relying on invitations or password resets.

For a typical SMTP provider using STARTTLS, set `MAIL_MAILER=smtp`, `MAIL_SCHEME=smtp`, and `MAIL_PORT=587`. For implicit TLS, typically on port 465, set `MAIL_MAILER=smtp`, `MAIL_SCHEME=smtps`, and `MAIL_PORT=465`. Use the host, username, password, and sender values supplied by your selected provider; no provider or credentials are assumed here.

Add these in the Render service's **Environment** settings. Keep credentials in Render environment variables, never in Git, the Docker image, or this guide.

| Key | Value |
| --- | --- |
| `MAIL_MAILER` | `smtp` for SMTP delivery |
| `MAIL_SCHEME` | `smtp` for STARTTLS, or `smtps` for implicit TLS |
| `MAIL_HOST` | SMTP hostname provided by your email provider |
| `MAIL_PORT` | Usually `587` for STARTTLS or `465` for implicit TLS; follow provider settings |
| `MAIL_USERNAME` | SMTP username from your provider, if required |
| `MAIL_PASSWORD` | SMTP password or provider-issued credential; store only in Render |
| `MAIL_FROM_ADDRESS` | Sender address verified with your email provider |
| `MAIL_FROM_NAME` | `Npontu SupportTrack` or another approved display name |

Laravel's configured default mailer is `log` when `MAIL_MAILER` is unset, so set `MAIL_MAILER=smtp` explicitly for SMTP delivery. After configuring the provider, verify that invitation and password-reset messages arrive and that links use the public HTTPS `APP_URL`.

## 3. Provide Aiven's CA certificate

Keep Aiven's CA certificate out of the repository and image. Obtain the CA certificate from the Aiven Console or Aiven's documented connection instructions, verify its source, and configure it as a **Render Secret File** named `aiven-ca.pem`. Render mounts secret files at `/etc/secrets/<filename>`, so set `MYSQL_ATTR_SSL_CA=/etc/secrets/aiven-ca.pem` as above. Do not paste the CA into a normal environment variable, commit it, or weaken certificate verification. The Laravel MySQL config enables server certificate verification whenever this CA path is supplied.

## 4. Check Aiven network access

Confirm the Aiven service is running, the hostname and port are correct, and its database user has the required grants. Check Aiven's current IP allowlist/firewall settings. Render outbound addresses depend on Render's current product, region, and plan; consult the current Render service networking information and Aiven controls before choosing an allowlist policy. Do not rely on guessed or static IPs and do not assume connectivity is guaranteed. If the selected Render service cannot provide stable outbound addresses suitable for Aiven's allowlist, choose an appropriate private networking or paid networking option, or a deliberate Aiven access policy, before production use. Keep TLS verification enabled regardless.

## 5. Deploy and verify

1. Save the environment settings and select **Manual Deploy → Deploy latest commit** (or enable deploys from `main`).
2. Review the build and runtime logs. Build-time asset/dependency errors belong in the build logs; Laravel startup or database errors appear in runtime logs. The container runs `php artisan optimize` at startup and does not run migrations or seeders.
3. Open `https://<service-name>.onrender.com/up`. Expect HTTP 200. Render may also use this path as its health check; configure `/up` as the health check path in service settings if that field is available.
4. Once connectivity is confirmed and a backup/change plan is in place, apply pending migrations manually as an explicit release step using a secure, one-off command/container configured with the same production environment. Review migrations first; do not run `migrate:fresh`, seeders, or destructive test suites against production. Startup intentionally never migrates.
5. Bootstrap the first administrator through the documented secure operational process. Do not add admin credentials to the Docker image or ordinary source control.
6. Test login and logout, password reset/invitation flow if configured, password changes, role-based access, activity creation and assignment, staff updates, pending/done validations, handover history, reports/CSV, and administrator/staff permissions. Use non-production test accounts/data where possible and verify email configuration separately if invitations/reset links are required.
7. Confirm a redeploy retains the same `APP_KEY` and database configuration. Verify that session-backed authentication continues to work across requests/restarts.

## Storage, sessions, and free-tier behavior

Sessions and Laravel cache default to the Aiven database, so they do not depend on a Render instance's local disk. Database-backed sessions can survive a process restart, but users may still need to sign in again after session expiration, cookie/key changes, or database/session-table changes. `SESSION_ENCRYPT=true` protects stored session payloads; the secure, HTTP-only, same-site cookie settings should be retained behind Render's HTTPS endpoint.

The application's local and public filesystem disks use `storage/app`. Render Free services have an ephemeral filesystem: locally stored uploads or generated files can disappear on restart, redeploy, or instance replacement. The current application has no configured durable object-storage service. Do not rely on local uploads for production records; if durable uploads become necessary, configure an external persistent object store (for example S3-compatible storage) and test access controls and backups first. Logs are sent to stderr and are available through Render logs subject to plan retention.

Free web services can spin down after inactivity and take time to wake on a later request. This can cause a slow first response and interrupt long requests. Free plan limits and availability can change; check Render's current plan details before relying on uptime, persistent storage, networking, or log retention. Aiven connectivity from Render is a separate network policy question and must be verified in the selected configuration.

## Troubleshooting

- **Build fails:** inspect Render build logs; confirm the Dockerfile is at the repository root, `composer.lock` is present, and the selected branch contains the intended files.
- **Service fails to bind / health check fails:** leave the start command unset, confirm Render's injected `PORT` is available, and confirm `/up` returns 200 in runtime logs. Do not add an Nginx sidecar or port mapping from Compose.
- **Startup reports `frankenphp: Operation not permitted`:** ensure the image does not use `setcap` on the FrankenPHP executable; Render supplies a port that does not require the privileged-port capability. Rebuild and deploy the updated image.
- **Laravel reports missing APP_KEY:** set a valid persistent `APP_KEY` in Render and redeploy. Never generate a new key on every startup.
- **Database connection timeout/refused:** verify Aiven status, hostname/port, Render and Aiven regions, Aiven allowlisting, and whether the chosen Render plan has usable egress/networking for that policy.
- **TLS/CA errors:** verify the Render Secret File name/path, that `MYSQL_ATTR_SSL_CA` exactly matches it, and that the certificate is the appropriate Aiven CA. Do not disable peer verification.
- **Access denied / unknown database / missing tables:** verify the database name and dedicated user's grants. After backup and migration review, apply pending migrations manually; startup does not apply them.
- **Sessions or cache fail because tables are missing:** confirm migrations for `sessions` and `cache` have been applied to this database. Do not switch to file-backed sessions/cache as a durability workaround on Free instances.
- **Uploads disappear:** this is expected for local storage on an ephemeral filesystem; use persistent object storage for files that must survive redeploys.
- **First request is slow:** a Free service may have been sleeping and needs to start before serving the request.

## Manual completion checklist

- [ ] Choose the Render region based on Aiven's region and verify actual connectivity.
- [ ] Add the Aiven CA as a Render Secret File and set the CA path.
- [ ] Set all production environment variables and preserve `APP_KEY` securely.
- [ ] Verify Aiven's network allowlist and dedicated-user grants.
- [ ] Review and manually apply migrations with a backup and release plan.
- [ ] Test `/up`, authentication, and required assignment workflows.
- [ ] Decide whether the Free tier's sleep, ephemeral disk, and networking limitations are acceptable for the intended use.
