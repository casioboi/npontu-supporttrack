# Operations runbook

This runbook covers release, recovery, and day-to-day operations. For first-time Windows/XAMPP installation, see [Deployment](DEPLOYMENT.md). Adapt infrastructure-specific items, such as backup retention and alert routing, to the deployment environment.

## Local development

1. Install PHP 8.2+, Composer, PDO MySQL, and MySQL 8 or a compatible MariaDB release.
2. Install dependencies, copy `.env.example` to `.env`, and generate `APP_KEY`.
3. Create the local database and configure its connection in `.env`. Keep `.env` local.
4. Set `APP_TIMEZONE` to the team's operational timezone.
5. Run `php artisan migrate`.
6. Create an administrator with `php artisan supporttrack:admin`.
7. Start the application with `php artisan serve` and open `/login`.
8. Run `php artisan test` before submitting application changes. Tests use in-memory SQLite and do not validate the local MySQL connection.

Optional frontend asset build:

```sh
npm install
npm run build
```

The current Blade layout serves CSS from `public/css`, so this build is not required for local application use.

## Production release checklist

### Before release

- Confirm the release commit and review its changes.
- Confirm automated tests pass in CI or the release environment.
- Confirm the target database, application URL, timezone, and session settings.
- Verify the `.env` and all credentials are supplied by the deployment platform and are not part of the release.
- Take and verify a restorable database backup before schema changes.
- Confirm that `storage/` and `bootstrap/cache/` are writable by the application process.

### Release

1. Deploy the application with the document root set to `public/`.
2. Install optimized production dependencies with `composer install --no-dev --optimize-autoloader`.
3. Apply additive schema changes with `php artisan migrate --force`.
4. Create or verify the initial administrator using the hidden prompt command `php artisan supporttrack:admin`. Do not reuse a bootstrap password from another environment.
5. Clear stale cached configuration when environment values have changed, then run `php artisan optimize`.
6. Confirm HTTPS and production debug settings (`APP_ENV=production`, `APP_DEBUG=false`).

Do not use destructive schema commands (`migrate:fresh`, `migrate:refresh`, or `migrate:reset`) on production data. Prefer a forward migration to correct a schema issue. A rollback of a migration that drops columns can permanently remove update details; assess the migration and take a backup before considering rollback.

### Post-release smoke test

Use designated test accounts and non-sensitive test activity data:

1. Sign in as an administrator and confirm the dashboard and personnel page load.
2. Create or verify a staff account and sign in as staff.
3. Submit a Pending update with Work Done, Outstanding Issues, and Recommended Next Steps; verify Findings can be left blank.
4. Confirm the activity appears in the current handover and on the following day's handover as carried forward.
5. Sign in as another authorised staff member and confirm the pending activity can be reviewed and continued without reassignment.
6. Submit a Done update and verify that the activity no longer appears as pending on later handover dates.
7. Review the earlier handover date and confirm it still shows the historical Pending status.
8. Submit a Done update with only Work Done; verify Findings and the two pending-only fields may be empty.
9. Verify an attempted Pending update without either required pending-only field is rejected.
10. Check a report using an explicit end date and verify its status reflects that date.

Use the application log and host-level health checks to investigate failures. Avoid recording credentials, session tokens, or unnecessary personal data in support tickets.

The application currently exposes Laravel's `/up` health endpoint. Configure host-level monitoring to use it; it does not replace database, queue, storage, or user-journey checks.

## Backup and recovery

- Back up the production database on a schedule appropriate to the activity's operational importance.
- Encrypt backups, restrict access, and store copies separately from the application host.
- Periodically test restoration into an isolated environment; a successful backup job alone does not prove recoverability.
- Include database and required application configuration in recovery planning. Do not back up or expose production `.env` values in source control.
- Document the organisation's recovery point, recovery time, and retention objectives before production use.

## Security operations

- Keep the runtime and dependencies maintained; review Composer security advisories before upgrades.
- Use a least-privilege database account and rotate credentials through the secret manager.
- Enforce HTTPS and secure cookie/session settings at the application host.
- Keep `APP_DEBUG=false` in production and protect application logs.
- Deactivate staff accounts that no longer require access; do not delete accounts to correct activity history.
- Treat activity updates as audit records. Correct an incorrect update by submitting a new update rather than editing or deleting the old one.
