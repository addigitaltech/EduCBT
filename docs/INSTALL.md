# Installation guide (Phase 1)

> Phase 1 code is untested until the CI jobs have run. Do not use it for real examinations.

## Requirements

- PHP 8.3 or newer with the extensions: mbstring, intl, bcmath, sodium, pdo (sqlite or mysql), openssl, curl, fileinfo
- Composer 2
- Node 20+ and npm (only on the machine that builds the assets)
- MariaDB 10.6+ or MySQL 8 for production (SQLite is fine for development and tests)

## 1. Get the code and create the framework files

```bash
git clone <repository-url> educbt && cd educbt
bash scripts/bootstrap.sh --no-tests
```

The script creates the standard Laravel files around the project's own files, installs dependencies and builds the front-end assets into `public/build`.

## 2. Choose the profile and configure

```bash
cp .env.school.example .env      # or: cp .env.central.example .env
php artisan key:generate
```

Edit `.env`: set `APP_URL`, the database settings and, for production, `APP_ENV=production`, `APP_DEBUG=false`, `SESSION_SECURE_COOKIE=true` (behind HTTPS). Never commit `.env`.

## 3. Create the database tables and the first administrator

```bash
php artisan migrate --force
php artisan app:install
```

`app:install` records which profile this installation is, creates the built-in roles, and creates the first administrator. If you leave the password empty, a temporary one is shown once and must be changed at first sign-in. It refuses to run twice.

## 4. Background jobs and scheduled tasks

**Shared hosting (cPanel, usually the central platform).** Add one cron job, every minute:

```
* * * * * cd /home/USER/educbt && php artisan schedule:run >> /dev/null 2>&1
```

Keep `QUEUE_VIA_SCHEDULER=true`. The scheduler then drains the database queue every minute.

**School server (Ubuntu).** Run a dedicated worker and still run the scheduler. Set `QUEUE_VIA_SCHEDULER=false`, then:

```
# /etc/systemd/system/educbt-worker.service
[Unit]
Description=EduCBT queue worker
After=network.target mariadb.service

[Service]
User=www-data
WorkingDirectory=/var/www/educbt
ExecStart=/usr/bin/php artisan queue:work --sleep=3 --tries=3 --max-time=3600
Restart=always

[Install]
WantedBy=multi-user.target
```

```
# crontab for www-data
* * * * * cd /var/www/educbt && php artisan schedule:run >> /dev/null 2>&1
```

Use a UPS on the school server so confirmed saves survive power cuts.

## 5. Check it works

- Open `/health` - it should return `{"status":"ok", ...}`.
- Sign in, then open **System health**. Warnings about the scheduler disappear a minute after cron starts.

## Web server

Point the document root at `public/`. Folder permissions: `storage/` and `bootstrap/cache/` must be writable by the web user.

## Updating

Phase 1 has no update tooling yet. Signed, checked updates arrive in a later phase.
