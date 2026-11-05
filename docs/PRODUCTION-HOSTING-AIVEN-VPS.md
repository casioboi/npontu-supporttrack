# Production Deployment: Ubuntu VPS, Docker, Nginx, and Aiven MySQL

This is the authoritative production deployment guide for Npontu SupportTrack. The intended architecture is a Linux VPS running the Laravel application in Docker, with host Nginx providing public HTTP/HTTPS and Let's Encrypt certificates. Laravel connects to the existing Aiven MySQL database over TLS. No VPS has been provisioned and no production deployment or database operation is claimed.

Do not target, select, or modify the separate Skaybek database. The production database name is `npontu_supporttrack`; do not rename or recreate it. Use a dedicated, least-privilege application account in place of `avnadmin`.

## Architecture and implementation notes

- **Public traffic:** Internet → host Nginx on ports 80/443 → `127.0.0.1:8080` → application container on port 80.
- **Application runtime:** Docker runs the production FrankenPHP/PHP 8.4 image as the unprivileged `www-data` user. FrankenPHP's bundled Caddy serves Laravel inside the container; it is not the public proxy and does not manage public HTTPS certificates.
- **Database:** the app uses the host, port, database, username, and password in a protected VPS environment file. Compose mounts the Aiven CA read-only at `/run/secrets/aiven-ca.pem`; Laravel uses it and enables server-certificate verification.
- **Startup and caching:** the container entrypoint runs `php artisan optimize` with the runtime environment before starting the web server. This caches configuration, routes, events, and views for that release. A failure to optimize stops startup and is visible in container logs.
- **No automatic schema or data changes:** neither image build nor container startup runs migrations or seeders. Migrations require a separate, reviewed command after a verified backup.
- **Persistence:** sessions and cache use Aiven-backed database tables; the current app has no user-upload workflow. Container logs go to Docker's rotating `json-file` log driver. If uploads or other local durable files are added later, provision persistent storage or object storage before deploying that feature.
- **Workers and assets:** the current workflows are synchronous; do not start a queue worker or scheduler unless the application later adds a requirement for one. The active interface uses checked-in `public/css` assets, so a Vite build is not needed for this deployment.
- **Health check:** Compose checks Laravel's `/up` endpoint. This confirms the web app responds; it does not prove that the database, mail delivery, or user workflows are healthy.

A previous Vercel deployment plan was superseded by this VPS design. During planning, Vercel Static IPs were documented as unsupported for Container Images, which did not fit the stable-source-IP allowlist required by the selected Aiven setup. This is historical context, not evidence that a Vercel deployment was attempted. The VPS provides a stable source IP that can be allowlisted at Aiven; do not make Aiven publicly accessible as a workaround for network restrictions.

## 1. Provision the VPS and domain

1. Select a maintained Ubuntu LTS release (Ubuntu 24.04 LTS is suitable), in a region reasonably close to Aiven. Size the VPS for the expected PHP concurrency and database connection limits.
2. Assign a static public IPv4 address. Record it for Aiven's allowlist and DNS; do not guess or substitute a placeholder address.
3. Use a domain or subdomain that you own or are authorized to administer. Create an `A` record pointing to the VPS IPv4. Create an `AAAA` record only if the VPS has working IPv6 and its firewall is configured for it.
4. In Aiven, confirm the existing service and `npontu_supporttrack` database. Create a dedicated SupportTrack application user with only the permissions the app requires. Keep administrative and backup credentials separate. Restrict the Aiven allowlist to the VPS static IP and any separately authorized administration IPs.
5. Download the current public CA certificate from the Aiven service's connection details. It is not a password or private key. Transfer it securely to the VPS as described below.
6. Configure a production mail provider and a sender domain you are authorized to use. Invitation and password-reset flows require working mail delivery; `MAIL_MAILER=log` is not production delivery.

Scope the runtime database account to the SupportTrack database and grant only its required runtime privileges. Reviewed schema changes may need temporary DDL privileges; do not leave broad administrative permissions on the runtime account. Never use `avnadmin` as the permanent application account.

## 2. Connect from Windows over SSH

Run these commands in **Windows PowerShell**. If you already have an SSH key, use it instead of creating another one.

```powershell
ssh-keygen -t ed25519 -C "npontu-supporttrack-deploy"
ssh -i "$env:USERPROFILE\.ssh\id_ed25519" DEPLOY_USER@VPS_IP
```

Replace `DEPLOY_USER` and `VPS_IP` with the account and address supplied by the VPS provider. Install the public key (`id_ed25519.pub`) through the provider's authorized-key mechanism. Never copy or send the private key (`id_ed25519`). For initial setup, use the provider's console if SSH key authentication is not yet configured.

Commands from this point onward are run **on the Linux VPS over SSH**, unless marked otherwise.

## 3. Update Ubuntu and install Docker, Nginx, and Certbot

```sh
sudo apt update
sudo apt upgrade -y
sudo apt install -y ca-certificates curl gnupg git nginx certbot python3-certbot-nginx openssl
```

Install Docker Engine and the Compose plugin from Docker's official Ubuntu repository:

```sh
sudo install -m 0755 -d /etc/apt/keyrings
sudo curl -fsSL https://download.docker.com/linux/ubuntu/gpg -o /etc/apt/keyrings/docker.asc
sudo chmod a+r /etc/apt/keyrings/docker.asc
echo "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.asc] https://download.docker.com/linux/ubuntu $(. /etc/os-release && echo "${UBUNTU_CODENAME:-$VERSION_CODENAME}") stable" | sudo tee /etc/apt/sources.list.d/docker.list >/dev/null
sudo apt update
sudo apt install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin
sudo systemctl enable --now docker
sudo docker version
sudo docker compose version
```

Use `sudo docker`/`sudo docker compose` for deployment operations. Membership in the `docker` group effectively grants root-level control of the host; only grant it to trusted administrators.

## 4. Configure the firewall

Allow SSH before enabling UFW. If SSH uses a nonstandard port, add a rule for that port first. Restrict SSH to known source addresses where practical.

```sh
sudo ufw default deny incoming
sudo ufw default allow outgoing
sudo ufw allow OpenSSH
sudo ufw allow 'Nginx Full'
sudo ufw status
sudo ufw enable
sudo ufw status verbose
```

Only public web ports 80 and 443 and the required SSH port should be inbound. Do not open MySQL/Aiven or the app's internal port 8080 on the VPS firewall. Confirm the VPS provider firewall/security group has matching rules.

## 5. Clone the repository on the VPS

Review the Windows worktree before any GitHub push; this repository may contain unfinished or unrelated changes. Do not push `.env`, passwords, private keys, live data, or deployment backups.

On the **VPS**:

```sh
sudo install -d -o "$USER" -g "$USER" -m 0755 /opt/npontu-supporttrack
git clone https://github.com/casioboi/npontu-supporttrack.git /opt/npontu-supporttrack
cd /opt/npontu-supporttrack
git status --short
```

If the directory already contains a checkout, inspect it and its branch first; update with `git pull --ff-only` only after confirming the intended branch and preserving local changes. Do not overwrite an existing checkout.

## 6. Install the Aiven CA and create protected environment settings

On **Windows PowerShell**, transfer the downloaded public certificate (replace the local path and VPS account):

```powershell
scp .\aiven-ca.pem DEPLOY_USER@VPS_IP:/tmp/aiven-ca.pem
```

On the **VPS**, install it outside the repository. The certificate is mounted read-only and Compose refuses to create its host path if it is missing:

```sh
sudo install -d -o root -g root -m 0700 /etc/npontu-supporttrack
sudo install -o root -g root -m 0644 /tmp/aiven-ca.pem /etc/npontu-supporttrack/aiven-ca.pem
openssl x509 -in /etc/npontu-supporttrack/aiven-ca.pem -noout
```

The final command must succeed. If it fails, stop and obtain the correct CA from Aiven. Keep the CA out of secrets and private-key directories; never substitute a client private key or disable verification.

Create a root-readable production environment file:

```sh
sudo install -o root -g root -m 0600 /dev/null /etc/npontu-supporttrack/app.env
sudo nano /etc/npontu-supporttrack/app.env
```

Enter real values directly in the editor on the VPS. This example contains placeholders only:

```dotenv
APP_NAME="Npontu SupportTrack"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://YOUR_DOMAIN
APP_TIMEZONE=Africa/Accra

LOG_CHANNEL=stderr
LOG_LEVEL=info

DB_CONNECTION=mysql
DB_HOST=YOUR_AIVEN_MYSQL_HOST
DB_PORT=YOUR_AIVEN_MYSQL_PORT
DB_DATABASE=npontu_supporttrack
DB_USERNAME=YOUR_DEDICATED_SUPPORTTRACK_USER
DB_PASSWORD=YOUR_AIVEN_PASSWORD

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax
CACHE_STORE=database
QUEUE_CONNECTION=sync

MAIL_MAILER=smtp
MAIL_SCHEME=smtp
MAIL_HOST=YOUR_APPROVED_SMTP_HOST
MAIL_PORT=587
MAIL_USERNAME=YOUR_SMTP_USERNAME
MAIL_PASSWORD=YOUR_SMTP_PASSWORD
MAIL_FROM_ADDRESS=notifications@YOUR_VERIFIED_DOMAIN
MAIL_FROM_NAME="${APP_NAME}"
```

Use the exact existing Aiven database name. `MYSQL_ATTR_SSL_CA` is injected by Compose as `/run/secrets/aiven-ca.pem`; do not add a different path in the environment file. Compose also enforces production mode, debug-off, secure/HTTP-only session cookies, and the database/cache/queue drivers.

The environment file and CA are outside Git and the Docker build context. Keep the environment file mode `0600`; verify it without displaying its contents:

```sh
sudo stat -c '%a %U:%G %n' /etc/npontu-supporttrack/app.env /etc/npontu-supporttrack/aiven-ca.pem
```

Expected modes are `600 root:root` for `app.env` and `644 root:root` for the public CA. Do not paste credentials into chat, GitHub, command arguments, screenshots, or logs.

## 7. Build the image and set the stable Laravel key

On the **VPS**, validate the Compose file after both protected files exist, then build:

```sh
cd /opt/npontu-supporttrack
sudo docker compose -f compose.production.yaml config --quiet
sudo docker compose -f compose.production.yaml build --pull
```

Generate one application key using the built image. The entrypoint is bypassed for this one-off key command; no database connection or migration is performed:

```sh
sudo docker compose -f compose.production.yaml run --rm --no-deps --entrypoint php app artisan key:generate --show
```

Copy the output directly into `APP_KEY` in `/etc/npontu-supporttrack/app.env` using `sudo nano`. Treat the key as a secret. Do not regenerate it on every release: changing it invalidates encrypted sessions and other encrypted values. Recheck the file mode after editing.

## 8. Verify Aiven connectivity and TLS without changing data

The production image loads the CA path from Compose and Laravel sets PDO's CA option plus server-certificate verification when that path is configured. Do not disable identity verification to work around an error.

Run Laravel's read-only database inspection:

```sh
sudo docker compose -f compose.production.yaml run --rm --no-deps app php artisan db:show --no-interaction
```

Confirm the output reports MySQL and the intended `npontu_supporttrack` database. Stop if the host, port, database, TLS certificate, DNS, Aiven service status, or IP allowlist is wrong. This command does not migrate or seed the database.

## 9. Configure Nginx as the public reverse proxy

On the **VPS**, install the repository's Nginx site sample and replace `YOUR_DOMAIN` with the real domain:

```sh
sudo cp /opt/npontu-supporttrack/deploy/nginx/npontu-supporttrack.conf /etc/nginx/sites-available/npontu-supporttrack
sudo nano /etc/nginx/sites-available/npontu-supporttrack
sudo ln -s /etc/nginx/sites-available/npontu-supporttrack /etc/nginx/sites-enabled/npontu-supporttrack
sudo nginx -t
sudo systemctl reload nginx
```

The site proxies to `127.0.0.1:8080`, forwards the original host and HTTPS/proxy headers, and does not expose the app container directly. Laravel trusts forwarded proxy headers; keep the app port bound to loopback as in Compose.

After DNS points to this VPS and ports 80/443 are reachable, request the certificate:

```sh
sudo certbot --nginx -d YOUR_DOMAIN
sudo certbot renew --dry-run
```

Use the real authorized domain in both commands. Confirm the Certbot renewal timer is enabled (`systemctl list-timers 'certbot*'`). Do not claim HTTPS is ready until a browser or `curl` confirms a valid certificate.

## 10. Start the app and check runtime permissions

On the **VPS**:

```sh
cd /opt/npontu-supporttrack
sudo docker compose -f compose.production.yaml up -d
sudo docker compose -f compose.production.yaml ps
sudo docker compose -f compose.production.yaml logs --tail=100 app
sudo docker compose -f compose.production.yaml exec -T app sh -lc 'id; test -w storage && test -w bootstrap/cache'
```

The process should run as `www-data`; the last command should succeed, confirming Laravel can write runtime caches and storage. Startup runs `php artisan optimize` using the protected runtime environment before starting FrankenPHP. No migrations or seeders run during build or startup. If startup fails, inspect logs and fix the reported issue rather than bypassing optimization or TLS checks.

## 11. Verify HTTPS and application behavior

On the **VPS**:

```sh
curl --fail --show-error --head https://YOUR_DOMAIN/up
sudo docker compose -f compose.production.yaml ps
```

Then use a browser to verify:

1. The login page loads over HTTPS and has no certificate warning.
2. An authorized account can log in and reach the correct dashboard.
3. Reports and handover load, and activity history is consistent.
4. Invitation and password-reset mail arrive from the approved sender, if those features are enabled.

Use staging or a separately approved test account for activity creation and updates first. Those actions write to the populated production database. Perform production write-path smoke tests only with explicit approval and an agreed test record; do not delete or overwrite existing records as cleanup.

## 12. Database migration and data safety

The Aiven database is already populated. Do not create/recreate it, change its name, run seeders, use `migrate:fresh`, `migrate:refresh`, `migrate:reset`, truncate tables, or target the separate Skaybek database.

Before any release with schema changes:

1. Confirm an Aiven-managed backup exists and that the recovery procedure has been tested by restoring to a separate non-production database.
2. Review the intended migrations and confirm they target only `npontu_supporttrack`.
3. Inspect current status without applying changes:

   ```sh
   sudo docker compose -f compose.production.yaml exec -T app php artisan migrate:status
   sudo docker compose -f compose.production.yaml exec -T app php artisan migrate --pretend
   ```

4. If the reported pending changes are unexpected or their effect on existing records is unclear, stop and get a reviewed migration plan. Never treat a successful `migrate:status` as approval to migrate.
5. Only after backup and explicit release approval, run the reviewed additive migrations manually:

   ```sh
   sudo docker compose -f compose.production.yaml exec -T app php artisan migrate --force
   ```

Migrations are deliberately not part of Docker build, entrypoint, or normal startup. Do not run `db:seed` against this populated database.

## 13. Logs, backup, updates, restart, and recovery

**Logs and errors (VPS):**

```sh
sudo docker compose -f compose.production.yaml logs --tail=200 app
sudo journalctl -u nginx --since "1 hour ago"
sudo tail -n 100 /var/log/nginx/error.log
```

Docker retains up to five 10 MB app log files. Do not publish logs without checking for personal data or secrets. Laravel has debug mode disabled in the Compose service.

**Backups:** enable Aiven's managed backup/retention for the MySQL service, confirm successful backup timestamps, and periodically rehearse a restore into an isolated non-production database. Before any migration, take and verify a fresh restorable backup. Keep backup/recovery credentials separate from the runtime DB user and protect exported files outside the repository.

**Update from GitHub (VPS):**

```sh
cd /opt/npontu-supporttrack
git status --short
git pull --ff-only
sudo docker compose -f compose.production.yaml build --pull
sudo docker compose -f compose.production.yaml up -d --force-recreate
sudo docker compose -f compose.production.yaml ps
sudo docker compose -f compose.production.yaml logs --tail=100 app
```

Review the Git status and release contents before pulling; do not overwrite local changes. If a release includes migrations, take the backup and perform the separate reviewed migration process above. Environment-only changes can be applied by editing the protected file and recreating the app so startup rebuilds Laravel's cached configuration.

**Restart and recovery (VPS):**

```sh
sudo docker compose -f compose.production.yaml restart app
sudo docker compose -f compose.production.yaml ps
sudo docker compose -f compose.production.yaml logs --tail=100 app
sudo nginx -t
sudo systemctl reload nginx
```

For a bad application release, redeploy a reviewed known-good Git revision and rebuild/recreate the app. Do not automatically reverse database migrations or restore over production data; use the approved recovery plan and a verified backup. If the VPS fails, provision a replacement, restore the protected environment and CA from secure copies, apply the Aiven allowlist/DNS updates, and deploy a reviewed revision.

## 14. Troubleshooting

- **Aiven timeout or refused connection:** check Aiven service health, the exact host/port, outbound VPS access, and that Aiven allowlists the VPS's current static IP. Do not open the database to all addresses.
- **TLS/CA error:** confirm `/etc/npontu-supporttrack/aiven-ca.pem` is the current public CA, the Compose bind source exists, and the container sees `/run/secrets/aiven-ca.pem`. Keep server-certificate verification enabled.
- **Database mismatch or missing tables:** verify `DB_DATABASE=npontu_supporttrack` and inspect with `db:show`/`migrate:status`. Stop if the target or pending migrations are unexpected; do not seed, recreate, or migrate blindly.
- **Container exits before serving:** inspect `docker compose logs app`; startup optimization must succeed. Check the stable `APP_KEY`, environment file syntax/permissions, writable Laravel cache paths, and required runtime values.
- **Nginx 502 or `/up` failure:** check Compose status/logs, confirm the app is listening at `127.0.0.1:8080`, and test `sudo nginx -t`. `/up` does not test Aiven; run `php artisan db:show` separately.
- **HTTPS or renewal failure:** verify domain DNS, provider/UFW ports 80 and 443, Nginx configuration, and `sudo certbot renew --dry-run`. Do not claim valid HTTPS until an external client confirms the certificate.
- **Invitation/reset email not received:** confirm the provider accepts the configured `MAIL_SCHEME`, credentials, sender identity, and verified domain; inspect Laravel logs and provider status without sharing secret-bearing logs.
- **Incorrect generated URLs or cookies:** confirm `APP_URL` is the public `https://` origin and keep the app port private behind Nginx. Recreate the app after editing runtime variables so startup rebuilds Laravel's cached configuration.

## 15. Production readiness checklist

- [ ] Ubuntu VPS, static IP, SSH access, DNS, provider firewall, and host UFW are configured.
- [ ] Aiven allowlists only the intended VPS/admin IPs; app uses a dedicated least-privilege user and the unchanged `npontu_supporttrack` database.
- [ ] `app.env` is outside Git with mode `0600`; `APP_ENV=production`, `APP_DEBUG=false`, stable `APP_KEY`, correct `APP_URL`, and required values are set.
- [ ] Aiven CA is current, mounted read-only, and Laravel's PDO server-certificate verification is enabled.
- [ ] Docker Compose configuration validates; image builds; container runs as `www-data`; storage/cache permissions and `/up` health check pass.
- [ ] Nginx proxies only to loopback port 8080; public app access is HTTPS with valid, renewing Let's Encrypt certificates.
- [ ] Login, dashboard, activity, update, handover, and reports are tested with approved accounts; write-path testing is explicitly authorized.
- [ ] Production mail is configured and invitation/password-reset delivery is verified, if required.
- [ ] Logs are available and reviewed without exposing credentials or user data; errors do not reveal debug traces to users.
- [ ] Aiven backups/retention are enabled and a restore has been rehearsed outside production.
- [ ] Persistent data requirements are understood; no production migrations or seeders run automatically.
- [ ] Restart and application rollback procedures are documented and tested without destructive database rollback.

**Status:** preparation only. This guide and the local Docker/Nginx configuration do not prove that a VPS, domain, Aiven production network path, mail service, or deployment is working.

## Information required before deployment

- VPS provider/region, static public IPv4, Ubuntu version, and an authorized SSH account/key.
- Domain/subdomain and confirmation that you can edit its DNS.
- Confirmation of Aiven's existing database name, host/port, TLS CA, allowlist, and a dedicated app database user. Provide passwords only through the protected VPS environment file, not chat or GitHub.
- Approved production mail provider/sender details, supplied through the same protected secret-handling process.
- Approved release revision and a restorable Aiven backup before any migration.
