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

- `SupportTrackTest`: login, dashboard, activity creation and status updates, conditional field validation, server-controlled identity/time, activity history, handover/report filters, and personnel-management restrictions.
- `StaffModuleTest`: authenticated workspace access, staff dashboard/activity scoping, pending work continuation, history and report privacy, profile password changes, stale-form conflicts, and CSV export.
- `HandoverTest`: date-specific status, pending carryover, personnel/status filters, awaiting activities, activity creation boundaries, and configured timezone boundaries.
- `ExampleTest`: a basic unit-test smoke check.

The current suite does not cover every browser interaction or deployment environment. In particular, no MySQL production migration rehearsal, cross-browser accessibility review, or deployment-host verification is implied by passing the SQLite suite. Features such as issue reporting and priority management do not exist and therefore have no corresponding tests.

## Production deployment guidance

The repository is not currently deployed. For a future deployment:

1. Provision a maintained PHP 8.2+ runtime, Composer, required PHP extensions, and a supported MySQL/MariaDB database.
2. Deploy the application with the web server document root set to the Laravel `public` directory; do not expose the repository root.
3. Install production PHP dependencies:

   ```sh
   composer install --no-dev --optimize-autoloader
   ```

4. Configure environment variables outside source control. Set `APP_ENV=production`, `APP_DEBUG=false`, a valid generated `APP_KEY`, the public `APP_URL`, operational timezone, and least-privilege database credentials. Use HTTPS and configure secure session cookies for the host.
5. Ensure `storage/` and `bootstrap/cache/` are writable by the application process. Keep `.env`, logs, backups, and generated secrets inaccessible from the web.
6. Take and verify a restorable database backup before schema changes. Apply migrations carefully:

   ```sh
   php artisan migrate --force
   ```

   Do not run destructive migrations against production data without an approved backup and recovery plan.
7. Create the first administrator through `php artisan supporttrack:admin` in a secured deployment session. If a seeder bootstrap password is used, remove it from the deployment environment immediately afterward.
8. Build frontend assets only if required by the release:

   ```sh
   npm install
   npm run build
   ```

   The current interface serves CSS from `public/css`.
9. Clear stale cached configuration when environment values change, then run `php artisan optimize` as appropriate for the deployment.
10. Configure database and file backups, log monitoring, TLS renewal, and a recovery procedure. Check Laravel's `/up` health endpoint and perform a smoke test with designated accounts.

Never put credentials, private keys, live user data, or session tokens in documentation or Git.

## Operational references

See [operations.md](operations.md) for a release smoke test, backup and recovery considerations, and operational security checks. See [SECURITY.md](SECURITY.md) for security controls and limitations.
