# Deployment guide

This guide deploys the CPSU-Hinigaran Academic Performance Predictor on a conventional PHP 8.2+ server. The application uses Laravel 11, SQLite for a small single-server installation or MySQL 8 for a multi-user production installation.

## Requirements

- PHP 8.2–8.4 with `ctype`, `curl`, `dom`, `fileinfo`, `filter`, `gd`, `iconv`, `libxml`, `mbstring`, `openssl`, `pdo`, `pdo_mysql` (or `pdo_sqlite`), `session`, `simplexml`, `tokenizer`, `xml`, `xmlreader`, `xmlwriter`, `zip`, and `zlib`. Laravel Excel 3.1 currently uses PhpSpreadsheet 1.x, whose supported PHP range ends before PHP 8.5; use PHP 8.2, 8.3, or 8.4 for this Laravel 11 lockfile.
- Composer 2.
- Node.js 18 or newer and npm 9 or newer for the production asset build.
- A web server whose document root points to `/srv/prediction/public` (adjust the path).
- HTTPS in production. Set `SESSION_SECURE_COOKIE=true` only after HTTPS is working.

## First deployment

The compiled `public/build` bundle is included for fresh checkouts and shared-hosting deployments. Regenerate it after frontend changes with `npm ci && npm run build`.

```bash
cd /srv/prediction
composer install --no-dev --prefer-dist --no-interaction
composer check-platform-reqs
cp .env.example .env
php artisan key:generate
```

Edit `.env` before running migrations:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://predictor.example.edu.ph
APP_TIMEZONE=Asia/Manila

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=cpsu_predictor
DB_USERNAME=cpsu_predictor
DB_PASSWORD="use-a-secret-from-your-secret-manager"

SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax
CACHE_STORE=redis
QUEUE_CONNECTION=sync
LOG_LEVEL=info
MAIL_MAILER=smtp
```

Create the database and a least-privilege database user in MySQL first. Do not commit `.env` or database credentials.

Build assets and prepare the application:

```bash
npm ci
npm run build
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Create the first real administrator (the command asks for a hidden password confirmation):

```bash
php artisan app:create-admin --name="System Administrator" --email="admin@example.edu.ph"
```

For non-interactive provisioning, pass `--password` and `--password-confirmation` through your secret manager; do not place real credentials in shell history.

Do **not** run `migrate:fresh --seed` in production. The demo seeder is intentionally blocked when `APP_ENV=production` unless `ALLOW_DEMO_SEED=true` is explicitly set. Demo accounts use the password `password` and are only for local evaluation.

Give the PHP worker ownership of the runtime directories:

```bash
chown -R www-data:www-data storage bootstrap/cache
chmod -R ug+rwX storage bootstrap/cache
```

## Web server

For Nginx, use `/srv/prediction/deploy/nginx.conf.example` as a starting point and replace the domain, certificate paths, and socket/user settings. For Apache, use the vhost example and ensure `mod_rewrite` and `AllowOverride All` are enabled. The document root must be `public/`; never expose the project root.

The included `/up` endpoint is suitable for a load balancer or uptime monitor.

## Operations

- Run `php artisan queue:work --tries=3 --timeout=90` under Supervisor if a queue connection other than `sync` is selected.
- Schedule `php artisan schedule:run` every minute if future scheduled jobs are added; the current application has no required cron job.
- Back up the database and `storage/app/private` regularly. Do not back up `.env` into a public directory.
- Rotate application logs and monitor disk usage for imports, sessions, and logs.
- Re-run `php artisan optimize:clear` whenever configuration, routes, or environment values change, then rebuild caches for production.

## Local/demo deployment

SQLite is the quickest zero-configuration option:

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate:fresh --seed
npm ci
npm run build
php artisan serve --host=0.0.0.0 --port=8000
```

Demo users are listed in the project README. Replace all demo passwords before using any shared environment.

## Security note

The requested Laravel 11 constraint is retained. The current Composer audit reports upstream advisories against the Laravel 11 line, including an email-validation CRLF issue. The application adds `email:rfc` plus `App\Rules\SafeEmail` at every account/email boundary, but the framework advisory cannot be fully eliminated without upgrading to a patched major framework release. Treat a public internet deployment as conditional on that upgrade or an approved vendor patch, and run `composer audit` as part of release approval.

