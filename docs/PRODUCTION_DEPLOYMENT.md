# Production deployment (cPanel / shared hosting)

Opportunity Hunter targets **PHP 8.3+**, Apache, MySQL (or MariaDB), and cPanel cron on shared hosting.

Local development and automated tests use SQLite. Redis, Supervisor, Docker, Kubernetes, and persistent queue workers are **not** part of this architecture.

Keep `QUEUE_CONNECTION=sync`. Collection runs in the cron process itself.

---

## 1. Create the MySQL database (cPanel)

1. Open **cPanel → MySQL® Databases** (or **MySQL Database Wizard**).
2. Create a database, for example `account_opportunity`.
3. Note the full database name cPanel shows (often prefixed with the account name).

## 2. Create the database user

1. Create a MySQL user with a strong password.
2. Do not reuse the cPanel account password.

## 3. Assign privileges

1. Add the user to the database with **ALL PRIVILEGES** on that database only.
2. Confirm the host is typically `localhost` (use the host cPanel lists in connection examples).

## 4. Upload the application

Upload the application so the project root (the folder that contains `artisan`, `app/`, `public/`) lives outside the public web tree when possible, for example:

```text
/home/USER/opportunity-hunter/
```

Options without SSH:

- cPanel **File Manager** upload (ZIP + Extract)
- FTP/SFTP client

If the host cannot run Composer, build `vendor/` on a machine with **PHP 8.3+** matching production major version, then upload `vendor/` with the app. Prefer running Composer on the server when the host provides PHP 8.3 CLI.

Do **not** upload a local `.env` that contains development secrets. Create `.env` on the server from `.env.example`.

## 5. Document root → Laravel `public/`

Point the domain or subdomain document root to:

```text
/home/USER/opportunity-hunter/public
```

Replace `USER` and `opportunity-hunter` with your account and folder names.

Do **not** point the document root at the project root. Files such as `.env`, `storage/logs`, and `vendor/` must stay outside the public web root.

Confirm `public/.htaccess` is present (Laravel ships with rewrite rules for Apache).

## 6. Configure PHP 8.3+

In **cPanel → MultiPHP Manager** (or **Select PHP Version**):

1. Select **PHP 8.3** or newer for the domain.
2. Enable required extensions (names vary slightly by host):

| Extension | Purpose |
| --- | --- |
| `ctype` | Laravel core |
| `curl` | Outbound HTTP (sources, optional AI) |
| `dom` | XML/RSS parsing |
| `fileinfo` | Uploads / MIME |
| `filter` | Input filtering |
| `hash` | Hashing |
| `mbstring` | Strings |
| `openssl` | TLS, encryption |
| `pcre` | Regex |
| `pdo` | Database |
| `pdo_mysql` | MySQL |
| `session` | Sessions |
| `tokenizer` | Blade / framework |
| `xml` | XML |
| `bcmath` | Numeric precision |
| `json` | JSON columns / APIs |
| `zip` | Composer / archives (recommended) |

Optional but recommended when available: `intl`.

Confirm CLI PHP used by cron is also 8.3+:

```bash
/usr/bin/php -v
# or the MultiPHP path your host documents, e.g.
/opt/cpanel/ea-php83/root/usr/bin/php -v
```

Do **not** lower the application requirement to PHP 8.2. Composer and PHPUnit require PHP 8.3+.

## 7. Install Composer dependencies

From the application root (directory containing `artisan`):

```bash
composer install --no-dev --optimize-autoloader
```

cPanel alternatives when SSH is unavailable:

- **Setup Node.js / Composer** or **Terminal** tools if your host provides them
- Run Composer locally on PHP 8.3+, then upload `vendor/`
- Some hosts provide a “PHP Composer” UI in Softaculous / Application Manager

## 8. Configure `.env`

Copy `.env.example` to `.env` on the server and set production values:

```env
APP_NAME="Opportunity Hunter"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.example

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_cpanel_database
DB_USERNAME=your_cpanel_db_user
DB_PASSWORD=your_cpanel_db_password

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=sync

MAIL_MAILER=smtp
MAIL_HOST=mail.your-domain.example
MAIL_PORT=587
MAIL_USERNAME=notifications@your-domain.example
MAIL_PASSWORD=your_mailbox_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="notifications@your-domain.example"
MAIL_FROM_NAME="${APP_NAME}"

# Prefer HTTPS session cookies on production (null also follows HTTPS requests)
SESSION_SECURE_COOKIE=true

OPPORTUNITY_SCHEDULE_COLLECTION=false
OPPORTUNITY_COLLECTION_LOCK_SECONDS=900

AI_ENABLED=false
AI_PROVIDER=openai
AI_API_KEY=
AI_MODEL=gpt-4o-mini
```

Production hard requirements:

```text
APP_ENV=production
APP_DEBUG=false
QUEUE_CONNECTION=sync
```

Do not commit real credentials. Do not leave `MAIL_MAILER=log` if users must receive verification or password-reset mail.

## 9. Generate the application key

```bash
php artisan key:generate --force
```

If you cannot run Artisan, generate a key on a trusted PHP 8.3 machine with the same app and copy only the `APP_KEY=` line into production `.env`. Never reuse a development key in production.

## 10. Run migrations

```bash
php artisan migrate --force
```

Migrations are MySQL-compatible (JSON columns, string status fields, foreign keys, unique constraints including `content_hash` and `(source_id, external_id)`, and a **prefix** index on long `canonical_url` values for InnoDB utf8mb4).

## 11. Seed required source configuration

```bash
php artisan db:seed --class=SourceSeeder --force
```

`SourceSeeder` registers Agent Reach (disabled), adapter templates, and verified public sources (Remotive, RemoteOK, We Work Remotely programming RSS, Jobicy). Real sources remain **disabled** until an operator enables them in Admin → Sources.

Create the first admin after registering that account:

```bash
php artisan user:make-admin you@example.com
```

## 12. Storage link

If the host supports Artisan and symlinks:

```bash
php artisan storage:link
```

cPanel File Manager alternative: create a symlink from `public/storage` → `../storage/app/public` if your plan allows links. Phase 5 does not require public uploads for core collection; skip only if the host blocks symlinks and you are not storing public files.

## 13. Storage and cache permissions

Ensure the web and cron PHP users can write:

```text
storage/
storage/app/
storage/framework/
storage/framework/cache/
storage/framework/sessions/
storage/framework/views/
storage/logs/
bootstrap/cache/
```

Typical shared-hosting fix (paths vary):

```bash
chmod -R ug+rwx storage bootstrap/cache
```

In File Manager, set folders to writable by the account user (often `755`/`775` depending on host policy).

## 14. Configure mail

Use SMTP (or the host’s authenticated mailer) for:

- Email verification
- Password reset
- Any future application notification emails that use Laravel mail

Recommended settings:

| Setting | Typical production value |
| --- | --- |
| `MAIL_MAILER` | `smtp` |
| `MAIL_HOST` | Host SMTP hostname from cPanel |
| `MAIL_PORT` | `587` (TLS) or host-documented port |
| `MAIL_ENCRYPTION` / `MAIL_SCHEME` | `tls` / `smtp` as required by the host |
| `MAIL_FROM_ADDRESS` | Address on your domain |
| `MAIL_FROM_NAME` | Product name |

DNS considerations (ask the host or DNS provider):

- **SPF** for the sending domain
- **DKIM** if the host provides keys
- **DMARC** optional but recommended

Testing procedure:

1. Register a new account with a mailbox you control.
2. Confirm the verification email arrives (not only in `storage/logs`).
3. Request a password reset and confirm delivery.
4. Do **not** send unsolicited email to job clients; the product does not auto-apply or email employers.

## 15. Configure cron

**Recommended (simplest, no duplicate paths):** call collection directly once per day.

```bash
0 6 * * * /usr/bin/php /home/USER/opportunity-hunter/artisan opportunities:collect >/dev/null 2>&1
```

Replace:

- `/usr/bin/php` with the host’s PHP **8.3+** CLI path
- `/home/USER/opportunity-hunter` with the real application root

In **cPanel → Cron Jobs**, choose the schedule and paste the command.

Collection safety (already implemented):

- Cache lock (`OPPORTUNITY_COLLECTION_LOCK_SECONDS`, default 900) via `CACHE_STORE=database`
- Per-source failure isolation (one failing source does not abort the whole run loop)
- Source health fields and `source_runs` history
- Bounded HTTP retries for connection/5xx only
- Non-zero exit when any enabled source fails
- Secrets redacted in CLI output, logs, and system errors

Keep:

```env
OPPORTUNITY_SCHEDULE_COLLECTION=false
```

so Laravel’s scheduler does **not** also run collection.

## 16. Optional Laravel scheduler (not recommended when using direct cron)

Only if you prefer `schedule:run` instead of a direct collection cron:

1. Set `OPPORTUNITY_SCHEDULE_COLLECTION=true`
2. **Remove** the direct `opportunities:collect` cron entry
3. Add:

```bash
* * * * * /usr/bin/php /home/USER/opportunity-hunter/artisan schedule:run >/dev/null 2>&1
```

Never enable both the direct collect cron and `schedule:run` with scheduling on — that can double-collect.

The scheduled command also uses `withoutOverlapping(120)` in addition to the collector cache lock.

## 17. Verify logs

- Application log: `storage/logs/laravel.log`
- Collection failures: Admin → Errors (`system_errors`, secrets redacted via `SecretRedactor`)
- Health endpoint: `GET /up` (Laravel health route)

With `APP_DEBUG=false`, browsers must not show stack traces or credentials.

## 18. Verify health and first collection

1. Open `https://your-domain.example/up`
2. Register, verify email, log in
3. Promote an admin, enable one permitted source
4. Run:

```bash
php artisan opportunities:collect
```

5. Confirm Admin → Sources health, Admin → Collection runs, opportunities and matches
6. Run collect again and confirm duplicates are counted, not recreated

Leave Agent Reach disabled. Do not add new sources during production hardening.

## 19. Cache optimization (optional)

After `.env` is stable:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Troubleshooting:

```bash
php artisan optimize:clear
```

Remember: after `config:cache`, only change configuration through `.env` plus a rebuild of the config cache — never call `env()` from application code.

## 20. Rollback guidance

| Situation | Action |
| --- | --- |
| Bad deploy of PHP/Blade/assets | Restore previous upload or Git tag; keep `.env` and `storage/` |
| Bad migration | Restore DB backup taken before `migrate --force`; avoid `migrate:rollback` on production unless you understand data loss |
| Bad cron / stuck lock | Disable cron; if a crashed run left a lock, `php artisan cache:clear` (database cache) after confirming no collect is running |
| Mail misconfiguration | Set `MAIL_MAILER=log` only temporarily while debugging; switch back to SMTP before inviting users |
| Emergency | Set a maintenance page via host tools, or `php artisan down` if CLI is available |

Always take a MySQL backup from cPanel before migrations or major uploads.

---

## Security checklist (runtime)

- `APP_DEBUG=false`
- Secrets only in `.env` (never in `sources.config` or admin UI)
- Admin UI uses `Source::safeConfig()` (credential keys stripped)
- CSRF on mutating web routes
- Policies / ownership checks for user-owned records
- Admin routes behind `admin` middleware
- SSRF protections in `SafeHttpFetcher` (private DNS/IP, credential URLs, redirect re-validation, size limits, bounded retries)
- Auth and AI generation rate limits
- Password hashing via Laravel `hashed` cast
- `SecretRedactor` on collection/system errors

See also [ARCHITECTURE.md](ARCHITECTURE.md), [PRODUCTION_CHECKLIST.md](PRODUCTION_CHECKLIST.md), [PHASE_4.md](PHASE_4.md), and [AGENT_REACH.md](AGENT_REACH.md).
