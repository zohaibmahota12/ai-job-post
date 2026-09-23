# Production deployment (cPanel / shared hosting)

Opportunity Hunter targets PHP 8.3+, Apache, MySQL, and cPanel cron on shared hosting. Local development and automated tests use SQLite. Redis, Supervisor, Docker, Kubernetes, and persistent queue workers are not required.

## 1. PHP version

- PHP **8.3+** with extensions commonly required by Laravel: `bcmath`, `ctype`, `curl`, `dom`, `fileinfo`, `json`, `mbstring`, `openssl`, `pdo`, `pdo_mysql`, `tokenizer`, `xml`, `zip`
- Confirm with `php -v` in the cPanel terminal or SSH session

## 2. Composer dependencies

From the application root (the directory that contains `artisan`):

```bash
composer install --no-dev --optimize-autoloader
```

If the host cannot run Composer, build `vendor/` locally with the same PHP major version and upload it.

## 3. Document root

Point the domain (or subdomain) document root to:

```text
/home/USER/opportunity-hunter/public
```

Replace `USER` and `opportunity-hunter` with your cPanel account and deployment folder. Do **not** expose the project root above `public/`.

## 4. Environment file

Copy `.env.example` to `.env` on the server and set at least:

```env
APP_NAME="Opportunity Hunter"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.example

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=sync

MAIL_MAILER=smtp
# …provider-specific mail settings…

OPPORTUNITY_SCHEDULE_COLLECTION=false
```

Generate the application key:

```bash
php artisan key:generate
```

AI proposal generation remains optional. Leave `AI_ENABLED=false` unless you configure a compatible provider.

## 5. MySQL

Create a MySQL database and user in cPanel. Grant that user access only to the application database. Use `DB_CONNECTION=mysql` in production.

## 6. Migrations

```bash
php artisan migrate --force
php artisan db:seed --class=SourceSeeder --force
```

`SourceSeeder` registers Agent Reach (disabled), adapter templates, and verified public sources (Remotive, RemoteOK, We Work Remotely programming RSS, Jobicy). Real sources remain **disabled** until an operator enables them.

## 7. Storage permissions

```bash
chmod -R ug+rwx storage bootstrap/cache
```

Ensure the web user can write to `storage/` and `bootstrap/cache/`. Create the storage link if your host supports it:

```bash
php artisan storage:link
```

## 8. Cache and config optimization

After deploying `.env` changes:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Clear when troubleshooting:

```bash
php artisan optimize:clear
```

## 9. cPanel cron (recommended)

Preferred: call collection directly once per day. Example (adjust PHP binary and path):

```bash
0 6 * * * /usr/bin/php /home/USER/opportunity-hunter/artisan opportunities:collect >/dev/null 2>&1
```

This path is a placeholder. Use your real home directory and deploy folder.

Collection uses a cache lock (`OPPORTUNITY_COLLECTION_LOCK_SECONDS`, default 900) so overlapping cron invocations do not run concurrently when the database cache store is available.

## 10. Optional Laravel scheduler

Only if you prefer `schedule:run`:

1. Set `OPPORTUNITY_SCHEDULE_COLLECTION=true`
2. Add a minutely cron:

```bash
* * * * * /usr/bin/php /home/USER/opportunity-hunter/artisan schedule:run >/dev/null 2>&1
```

The scheduled collection command also uses `withoutOverlapping(120)`.

## 11. Mail configuration

Use SMTP (or your host’s mailer) for password resets and verification. Default local `log` mailer is not suitable for production user email.

## 12. Source collection

1. Enable only sources with a permitted public endpoint
2. Prefer daily collection to respect provider rate limits and attribution
3. Inspect Admin → Sources for health (`Healthy` / `Warning` / `Failing` / `Disabled`)
4. Inspect Admin → Collection runs for counts and errors
5. Agent Reach stays optional and disabled by default

Manual one-shot:

```bash
php artisan opportunities:collect
php artisan opportunities:collect --source=remotive_remote_jobs
```

## 13. Logs

- Application logs: `storage/logs/laravel.log`
- Collection failures also appear as admin System Errors (secrets redacted)

## 14. Troubleshooting

| Symptom | Check |
| --- | --- |
| Blank page / 500 | `APP_DEBUG=false` still shows logs in `storage/logs`; confirm document root is `public/` |
| Vite / CSS missing | Run `npm ci && npm run build` locally and deploy `public/build` |
| Collection overlap message | Another run holds the cache lock; wait or clear stale locks with `php artisan cache:clear` if a run crashed |
| SSRF / blocked URL | Sources must use public http(s) hosts; private DNS and credential URLs are rejected |
| Empty opportunities | Sources may be disabled; enable one configured source and re-run collect |
| Mail not sending | Configure SMTP; do not leave `MAIL_MAILER=log` in production |

## Security notes

- Keep `APP_DEBUG=false` in production
- Do not store API secrets in `sources.config`
- Outbound source HTTP continues to block private/link-local addresses, credential URLs, oversized responses, and unsafe redirects
- CSRF and admin middleware remain required for mutating routes

See also [ARCHITECTURE.md](ARCHITECTURE.md), [PHASE_4.md](PHASE_4.md), and [AGENT_REACH.md](AGENT_REACH.md).
