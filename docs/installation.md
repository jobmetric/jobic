# Jobic installation

Ship the application with Composer production dependencies, `.env.example`, and the complete `public/vuero/build` directory. Run `composer install --no-dev --optimize-autoloader` and `npm ci && npm run build` when preparing the release, not from the browser. Set the web document root to `public`; never expose the project root or private storage.

Requirements: PHP 8.3+, Laravel's required extensions, `phpredis`, and `pdo_mysql` or `pdo_pgsql`. Create an empty MySQL, MariaDB or PostgreSQL database with privileges to create tables and indexes. Redis is required. The PHP process needs write access to the environment directory, `.env`, `storage`, and `bootstrap/cache`, and must be able to create the public storage link.

The first HTTP request creates a missing `.env`, generates a missing APP_KEY, and redirects to `/installation`. An existing valid key is preserved. An invalid existing key must be restored manually. Existing environment files are not replaced with example defaults. CLI commands never silently initialize the environment.

## Wizard

1. Language (English by default, Persian selectable), requirements, environment and debug (production / disabled by default).
2. Database settings and successful connection test against an empty database.
3. Required Redis connection test, optional SMTP settings.
4. Logging and session settings. File sessions and cache remain the defaults; queue stays synchronous.
5. Site name, URL, and administrator credentials.
6. Verify, migrate, seed production defaults, create administrator, link storage, finalize.

SMTP can be configured later under **Settings → Email**. Blank password fields preserve stored credentials. The installer never writes an administrator password to `.env`; its temporary private state holds only a password hash. The production seeder intentionally contains no demo users or random settings.

`INSTALLATION_STEP` identifies the last saved form, `INSTALLATION_TASK` the number of completed execution tasks, and `INSTALLATION_STATUS` is pending/configuring/running/failed/completed. `INSTALLATION` is the completion marker. Legacy installations without status are detected by the migrations and users tables plus at least one user. A failed database connection never proves the database empty. Existing accounts are not overwritten.

## Resume and recover

Refresh or return in the same browser to resume. After a failed execution task, correct the server issue and select Start / resume. Successful tasks are checkpointed; migrations use Laravel's migration ledger. Never use `migrate:fresh` to recover a production database.

If the owning browser session is lost, run:

```sh
php artisan installation:release
```

Then open the installer in the replacement browser. This releases ownership only; it does not erase progress or permit reinstalling a completed system. A single mutation holds a file lock. Configuration and state replacements are atomic. Installer sessions and throttling use private file storage regardless of selected database/Redis/session settings.

Execution uses sequential HTTP requests without a worker. A single migration still runs within one HTTP request: hosting PHP/proxy timeouts must accommodate the largest migration. Deploy without prebuilt environment-specific config caches. Environment writes invalidate config cache; if persistent workers are introduced later, restart them after changing mail settings.

## Validation

```sh
php artisan test
vendor/bin/pint --dirty --test
npm run build
```

Feature tests isolate env/state and exercise real migrations using temporary SQLite databases; connection adapters are mocked where external services are unavailable. This is not a substitute for release verification against real MySQL, MariaDB, PostgreSQL and phpredis servers.

The independent browser fixture uses `tests/Browser/installation-router.php` and `playwright.installation.config.js`. Create an empty sandbox with `storage/framework/{views,sessions,cache/data}`, `storage/logs`, and `storage/app`. Set `JOBIC_BROWSER_SANDBOX` to its absolute path and run:

```sh
php -S 127.0.0.1:8917 -t public tests/Browser/installation-router.php
npx playwright test --config=playwright.installation.config.js
```

Use a fresh sandbox for each run. The fixture replaces requirement checks; browser tests use a real saved first step and failed DB connection, then deterministic API responses to exercise the remaining UI. Never deploy the test router as a production entry point.

### Package migrations and existing deployments

The install task runs Laravel `migrate --database=installation --force` without a path restriction. Laravel combines application migrations with paths registered by discovered package providers and orders migration filenames together; package migrations are not a separate final batch. The installation integration test checks core and JobMetric tables.

Legacy detection only recognizes an existing database and closes the installer; it deliberately does not perform upgrades or seeds. Packages installed after the last migration can therefore remain pending on an existing deployment. Check `php artisan migrate:status` and apply `php artisan migrate --force` as part of deployment. Never use `migrate:fresh` for an upgrade.
