# TPT Aggregator — fresh-host install guide

This walks you through getting the app running on a new LAMP-style server (cPanel / VPS / dedicated). Assumes you've never set up TPT on this host before.

## 1. Upload

Copy the **entire project folder** to the server, preserving the directory structure:

```
your-host:/var/www/tpt/        ← project root
├── app/
├── public/                    ← this should be the web document-root (or sub-folder)
├── writable/                  ← must be writable by the web user
├── vendor/                    ← if missing, run `composer install --no-dev` on the host
├── install/                   ← the web installer — delete after setup
├── tpt-install.sql            ← seed database — delete after setup
├── INSTALL.md                 ← this file
└── DEPLOY.md                  ← production tuning (cron, OPcache, monitoring)
```

If `vendor/` wasn't uploaded, SSH in and run:

```bash
cd /var/www/tpt
composer install --no-dev --optimize-autoloader --classmap-authoritative
```

## 2. File permissions

The web server (www-data / apache / nobody) must be able to write to `writable/`:

```bash
chown -R www-data:www-data /var/www/tpt/writable
chmod -R 775 /var/www/tpt/writable
```

On cPanel without shell, set folder perms to **755**, files **644**, then 775 on `writable/` and its sub-folders via File Manager. Also make sure the **project root** is writable (the installer writes `.env`).

## 3. Point your web root at `/public/`

In `httpd.conf` / virtualhost:

```apache
<VirtualHost *:80>
    ServerName tpt.example.com
    DocumentRoot /var/www/tpt/public

    <Directory /var/www/tpt/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

On shared hosting, the panel usually has a **Document Root** field — point it at `/path/to/tpt/public`.

> Don't expose the project root to the public web — only `/public/` should be reachable. Source code, `.env`, and the SQL seed must stay behind the document root.

## 4. Run the installer

Browse to:

```
http://your-host.example.com/install/
```

(If you placed the project in a sub-folder, e.g. `https://tpt.example.com/tpt/`, the installer is at `https://tpt.example.com/tpt/install/`.)

The wizard runs **5 steps**:

| Step | What it does |
|---|---|
| 1 — Environment | PHP version, required extensions, writable folders, `vendor/` present, SQL seed found |
| 2 — Database | host/port/user/password — DB auto-created if missing (needs CREATE privilege) |
| 3 — Site URL | Auto-detected from the request; confirm or override |
| 4 — Import | Load `tpt-install.sql` (fresh) OR skip if you already have data; migrations run after either choice |
| 5 — Done | Locks itself by creating `writable/.installed` |

## 5. Sign in & change the seeded password

Default credentials baked into the seed:

| Role | Email | Password |
|---|---|---|
| **Administrator** | `admin@tpt.local` | `admin@123` |
| Super-Admin (lands on `/sys`) | `super@tpt.local` | `SuperSecret123` |
| Management | `mgr@tpt.local` | `Demo@123` |
| CRM Manager / Executive | `crm.mgr@tpt.local` / `crm.exec@tpt.local` | `Demo@123` |
| Purchase Manager / Executive | `pur.mgr@tpt.local` / `pur.exec@tpt.local` | `Demo@123` |
| Operations Manager / Executive | `ops.mgr@tpt.local` / `ops.exec@tpt.local` | `Demo@123` |
| Accounts | `accounts@tpt.local` | `Demo@123` |
| Client Portal | `portal1@example.com` / `portal2@example.com` / `portal3@example.com` | `Portal@123` |

**Change every seeded password immediately** via the topbar avatar → Profile → Change password. The HIBP breached-password check is on by default.

## 6. Lock down the install artefacts

After login works:

```bash
rm -rf /var/www/tpt/install
rm /var/www/tpt/tpt-install.sql
```

Both contain bootstrap material (default passwords, seed data, install logic) that has no business on a live server. The `writable/.installed` lock will keep the installer dormant even if the directory isn't removed, but deleting it is cleaner.

## 7. Hook up the cron jobs

Open [DEPLOY.md](DEPLOY.md) and copy the `cron` section into the server's crontab. Critical entries:

- `tpt:db-backup` — nightly
- `tpt:db-restore-test` — monthly (verifies backups are restorable)
- `tpt:queue-work` — long-running worker (use systemd, not cron)
- `tpt:compliance-digest` — weekly Monday 9 AM
- `tpt:email-flush` — every 5 min
- `tpt:gps-refresh` — every 5–10 min during business hours
- `touch writable/cron-heartbeat` — every 5 min (feeds `/_health`)

## 8. (Optional) Wire up Sentry

In `.env`:

```dotenv
sentry.dsn  = 'https://<publicKey>@<host>/<projectId>'
tpt.release = 'tpt@2026-05-14'
```

Then: `php spark tpt:sentry-test` — should fire a synthetic event in sentry.io within ~10 s.

## 9. Configure integrations

Open **Settings** (admin sidebar) and walk these in order:

- **Email** — pick Brevo / SES / SMTP, paste creds, click "Send test"
- **WhatsApp** — set `whatsapp.*` in `.env`; verify via WhatsApp → Templates → Test send
- **GPS** — set `loconav.apiKey` in `.env`, or FastTag credentials in Settings → GPS sources
- **E-Way Bill / E-Invoice** — set `cleartax.*` in `.env`
- **GST returns** — set your business's home state as `our_state` in Settings (drives intrastate vs IGST)

## 10. Final smoke check

```bash
curl -s https://tpt.example.com/_health | jq .
```

Should return:
```json
{
  "status": "ok",
  "checks": {
    "db":          { "ok": true },
    "writable":    { "ok": true },
    "email_queue": { "ok": true },
    "last_cron":   { "ok": true }
  }
}
```

If `last_cron` shows "no heartbeat yet", your cron jobs haven't fired yet — wait 5 min after wiring them up.

---

## Re-running the installer later

The installer refuses to run once `writable/.installed` exists. To re-walk setup:

```bash
rm /var/www/tpt/writable/.installed
# then revisit /install/
```

Each step reads the existing `.env` as defaults, so it's safe to re-run just to tweak one value. **Choosing "Fresh install" on step 4 again will overwrite your data** — pick "Skip import" if you only want to update config.

---

## Hardening (post-install)

```bash
# Lock everything down to least privilege
find . -type d -exec chmod 755 {} \;
find . -type f -exec chmod 644 {} \;
chmod -R 775 writable public/uploads
chown -R www-data:www-data .

# Flip environment
sed -i 's/^CI_ENVIRONMENT.*/CI_ENVIRONMENT = production/' .env

# Verify (.env shouldn't be web-accessible)
curl -I https://tpt.example.com/.env   # expect 403 or 404
```

In the app:

- Enable 2FA on every admin/manager account (Profile → Two-factor)
- Set `super_admin_ip_allowlist` to your office IP in Settings → Diagnostics
- Restrict `/sys/*` at the firewall level if practical

---

## Troubleshooting

- **Step 1: "Extension X missing"** — install it via your package manager (Ubuntu: `apt install php8.2-mbstring php8.2-mysql php8.2-curl php8.2-gd php8.2-intl php8.2-fileinfo`). On cPanel: PHP Selector → enable extensions.
- **Step 2: "Could not connect"** — DB user needs CREATE privileges; or create the DB manually in your panel first, then re-run.
- **Step 4: import fails with permissions error** — DB user lacks DROP/CREATE on the schema. `GRANT ALL PRIVILEGES ON your_db.* TO 'your_user'@'host';`
- **Every page 404s after install** — virtualhost isn't pointing at `/public/`, or `mod_rewrite` is off. Check `apachectl -M | grep rewrite`.
- **403 on the root URL** — `Options -Indexes` plus a missing `DirectoryIndex index.php`. Already added to `public/.htaccess` in this build; if you're behind nginx + php-fpm, configure the equivalent `try_files $uri $uri/ /index.php?$args;`.
- **Sessions don't stick** — `writable/session/` isn't writable. `chmod 775 writable/session`.
- **"Could not write .env"** during install — project root isn't writable; `chmod 775` on the project root before running setup.
- **Login loops** — encryption key may be missing/wrong. Compare your `.env` against `env` (the bundled template).
