# TPT Aggregator — production deployment

## One-time host setup

### PHP / OPcache
Open `/etc/php/8.2/cli/php.ini` and `/etc/php/8.2/fpm/php.ini` (or `C:\xampp\php\php.ini` on Windows). Set:

```ini
; Free 30–50% on every request once warm
opcache.enable=1
opcache.enable_cli=1
opcache.memory_consumption=192
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=20000
opcache.validate_timestamps=0   ; production only — flush opcache on deploy
opcache.revalidate_freq=0
opcache.fast_shutdown=1

; PII encryption + crypt
extension=openssl
extension=mbstring
extension=gd
extension=curl
extension=intl
extension=fileinfo

; Upload limits — must allow PDF + POD jpeg uploads
upload_max_filesize = 12M
post_max_size = 16M
memory_limit = 256M

; Sessions — prefer Redis or DB in production
; session.save_handler = redis
; session.save_path    = "tcp://127.0.0.1:6379?database=0"
```

### MySQL — slow query log
Enable while diagnosing perf only (file grows fast):

```ini
[mysqld]
slow_query_log      = ON
slow_query_log_file = /var/log/mysql/tpt-slow.log
long_query_time     = 0.2
log_queries_not_using_indexes = ON
```

Analyse with `mysqldumpslow -s t /var/log/mysql/tpt-slow.log | head -30`.

### Cron (Linux)
```cron
# Nightly DB backup
0 2 * * *      cd /var/www/tpt && /usr/bin/php spark tpt:db-backup

# Monthly restore drill — alerts you if backups are silently broken
0 4 1 * *      cd /var/www/tpt && /usr/bin/php spark tpt:db-restore-test

# Weekly compliance digest (Monday 9 AM)
0 9 * * 1      cd /var/www/tpt && /usr/bin/php spark tpt:compliance-digest

# Email queue drain — runs every 5 min
*/5 * * * *    cd /var/www/tpt && /usr/bin/php spark tpt:email-flush

# GPS refresh — every minute during business hours
* 6-22 * * *   cd /var/www/tpt && /usr/bin/php spark tpt:gps-refresh

# Health heartbeat — keeps /_health green
*/2 * * * *    touch /var/www/tpt/writable/cron-heartbeat
```

### Background worker (systemd)
`/etc/systemd/system/tpt-queue.service`:
```ini
[Unit]
Description=TPT job-queue worker
After=mysql.service

[Service]
Type=simple
User=www-data
WorkingDirectory=/var/www/tpt
ExecStart=/usr/bin/php spark tpt:queue-work --max=200 --memory=128
Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target
```
Then `systemctl enable --now tpt-queue`.

### `.env` — production essentials
```dotenv
CI_ENVIRONMENT = production
app.baseURL    = "https://tpt.example.com/"

# DB
database.default.hostname = 127.0.0.1
database.default.database = tpt_prod
database.default.username = tpt_app
database.default.password = '«strong-password»'

# Cache (file is fine; redis preferred at scale)
cache.handler = file
# cache.handler = redis
# cache.redis = {host: 127.0.0.1, port: 6379}

# Monitoring (optional but recommended)
sentry.dsn  = "https://<key>@<host>/<project_id>"
tpt.release = "tpt@2026-05-14"

# Email — already auto-decrypted from settings table (Brevo/SES/SMTP)
```

## Deploy script

`deploy.sh`:
```bash
#!/usr/bin/env bash
set -euo pipefail

cd /var/www/tpt

# 1. Pull latest code
git fetch --depth=20
git reset --hard origin/main

# 2. Composer — production deps, optimised autoloader
composer install --no-dev --optimize-autoloader --classmap-authoritative --no-interaction

# 3. Run pending migrations
php spark migrate --all

# 4. Restart worker so it picks up new code
sudo systemctl reload-or-restart tpt-queue

# 5. Flush PHP opcache (since validate_timestamps=0)
# Replace with your reload trigger — e.g. systemctl reload php8.2-fpm
sudo systemctl reload php8.2-fpm || true

# 6. Smoke check
curl -fsS https://tpt.example.com/_health | jq .status
```

`deploy.bat` (Windows):
```bat
@echo off
cd /d C:\xampp\htdocs\tpt

git fetch --depth=20
git reset --hard origin/main

composer install --no-dev --optimize-autoloader --classmap-authoritative --no-interaction || goto :error

C:\xampp\php\php.exe spark migrate --all || goto :error

REM Restart Apache so OPcache reloads
net stop  Apache2.4 && net start Apache2.4

curl -fsS http://localhost/tpt/public/_health
goto :EOF

:error
echo Deploy failed at step %errorlevel%
exit /b %errorlevel%
```

## Verification checklist (post-deploy)

- [ ] `curl /_health` returns `"status":"ok"`
- [ ] Login works on a fresh incognito session
- [ ] Background worker is running (`systemctl status tpt-queue` or `tasklist | grep queue-work`)
- [ ] `writable/logs/` shows new entries
- [ ] One sample notification (e.g. test booking) reaches WhatsApp + email
- [ ] PDF download works on an existing invoice
- [ ] Sentry receives the test event: `php -r "require 'vendor/autoload.php'; (new App\Libraries\Sentry())::capture(new RuntimeException('deploy smoke'));"`
