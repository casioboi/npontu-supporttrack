# Installation and Deployment

This guide covers a Windows/XAMPP development setup and general production guidance. It does not imply that a production deployment currently exists.

## Requirements

- PHP 8.2 or a compatible version satisfying `composer.json`, with the Laravel-required extensions. Enable PDO MySQL for MySQL/MariaDB and PDO SQLite for the in-memory test suite.
- Composer.
- MySQL 8 or compatible MariaDB for the documented database configuration.
- Git.
- XAMPP is a convenient Windows option for PHP and MySQL/MariaDB; VS Code or another editor is optional.
- Node.js and npm are optional for the current interface. The Blade layout loads application styles from `public/css`, so frontend dependencies are not required to run it.

## Windows/XAMPP local setup

### 1. Start services and clone the repository

Start MySQL in the XAMPP Control Panel. Open PowerShell and run:

```powershell
git clone https://github.com/casioboi/npontu-supporttrack.git
cd npontu-supporttrack
```

### 2. Install PHP dependencies and configure Laravel

```powershell
composer install
Copy-Item .env.example .env
php artisan key:generate
```

If PowerShell asks whether to overwrite an existing `.env`, keep the existing file unless you intentionally want to replace local configuration. Never commit `.env`.

### 3. Create and configure the database

In phpMyAdmin or a MySQL client, create a database:

```sql
CREATE DATABASE `npontu-supporttrack`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
```

Set the connection values in `.env`, adjusting the username and password to match your local MySQL account:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=npontu-supporttrack
DB_USERNAME=root
DB_PASSWORD=
APP_TIMEZONE=Africa/Accra
```

The blank password is only an example for a local XAMPP installation. Use a dedicated account and strong secret outside local development.

### 4. Migrate and create an administrator

```powershell
php artisan migrate --seed
php artisan supporttrack:admin
```

The seeder does not create a default user. It creates or updates an administrator only if `SUPPORTTRACK_ADMIN_EMAIL` and `SUPPORTTRACK_ADMIN_PASSWORD` are set (with optional `SUPPORTTRACK_ADMIN_NAME`). The interactive command is recommended for the first account: it prompts for name, email, and a confirmed password of at least 12 characters without echoing the password.

### 5. Run the application

```powershell
php artisan serve
```

Open [http://127.0.0.1:8000/login](http://127.0.0.1:8000/login) and sign in with the administrator account you created.

### Optional frontend build

Node.js/npm is not required for the current interface. If you are working on the Vite-managed assets:

```powershell
npm install
npm run build
```

## Testing

Install the Composer development dependencies and ensure PHP's PDO SQLite extension is enabled. Run:

```powershell
php artisan test
```

The test environment is configured in `phpunit.xml` to use in-memory SQLite, an array cache/session, and synchronous queues. It does not verify connectivity to the MySQL database in `.env`.

The feature suites cover:

- `SupportTrackTest`: login, dashboard, activity creation and status updates, conditional field validation, server-controlled identity/time, activity history, priority defaults/validation/filters and CSV fields, handover/report filters, and personnel-management restrictions.
- Activity-management coverage in `SupportTrackTest`: administrator-only editing of title, description, date, priority, and assignment; preservation of status and update history; and rejection of inactive assignments.
- `StaffModuleTest`: authenticated workspace access, staff dashboard/activity scoping, pending work continuation, history and report privacy, profile password changes, stale-form conflicts, and CSV export.
- `HandoverTest`: date-specific status, pending carryover, priority filters and ordering, personnel/status filters, awaiting activities, activity creation boundaries, and configured timezone boundaries.
- `ProductionFeatureTest`: multi-person assignments, inactive/non-staff assignment rejection, issue-report privacy and administrator resolution, invitation delivery, single-use password reset, and forced first-login password changes.
- `ExampleTest`: a basic unit-test smoke check.

The current suite does not cover every browser interaction or deployment environment. In particular, no MySQL production migration rehearsal, cross-browser accessibility review, or deployment-host verification is implied by passing the SQLite suite. The example environment uses `MAIL_MAILER=log`; this writes messages to application logs rather than delivering email.

## Production deployment

Production deployment instructions are maintained in one place: the [Ubuntu VPS, Docker, Nginx, HTTPS, and Aiven MySQL guide](PRODUCTION-HOSTING-AIVEN-VPS.md). Follow that guide rather than adapting the local XAMPP steps for production. It covers external secrets, Aiven TLS, manual migration safety, Nginx/Let's Encrypt, backups, releases, and recovery.

Never put credentials, private keys, live user data, or session tokens in documentation or Git. The repository is not currently deployed.

### Aiven MySQL with Vercel Container Runtime

The Docker image contains `storage/app/certificates/aiven-ca.pem`, the public Aiven CA certificate. It is not a database credential. Laravel's MySQL configuration enables PDO server-certificate verification whenever `MYSQL_ATTR_SSL_CA` is set; do not disable verification.

Keep the existing local `.env` pointed at the local development database. Configure the following values in the Vercel project's production Environment Variables instead; enter the Aiven hostname, port, username, and password from the Aiven service's connection details, never in Git:

```dotenv
APP_ENV=production
APP_DEBUG=false
DB_CONNECTION=mysql
DB_HOST=<Aiven service hostname>
DB_PORT=<Aiven service port>
DB_DATABASE=npontu_supporttrack
DB_USERNAME=<dedicated application database user>
DB_PASSWORD=<Aiven database password>
MYSQL_ATTR_SSL_CA=/app/storage/app/certificates/aiven-ca.pem
```

Set these values in Vercel's project settings for the intended deployment environment, then redeploy. Do not copy the local `.env` to Vercel. The CA path above is the absolute path inside the container.

**Network compatibility is a separate prerequisite:** Vercel's current [Static IP documentation](https://vercel.com/docs/networking/static-ips) states that Static IPs do not support Container Images. Therefore, this container cannot rely on Vercel Static IP egress to satisfy a restrictive Aiven IP allowlist. Do not broaden the populated production database's allowlist to all addresses as a workaround. Resolve a supported private/allowlisted network path or use a hosting runtime with stable egress before enabling production database access. TLS certificate verification protects the connection but does not replace network access restrictions.

## Operational references

See [operations.md](operations.md) for a release smoke test, backup and recovery considerations, and operational security checks. See [SECURITY.md](SECURITY.md) for security controls and limitations.
