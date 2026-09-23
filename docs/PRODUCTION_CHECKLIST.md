# Production launch checklist

Use this checklist before and after going live on cPanel / shared hosting. Full steps: [PRODUCTION_DEPLOYMENT.md](PRODUCTION_DEPLOYMENT.md).

Mark each item only after you have verified it on the production host.

---

## Server

- [ ] PHP **8.3+** selected for the domain (MultiPHP / Select PHP Version)
- [ ] Cron PHP binary is also **8.3+** (`php -v` on the path used in cron)
- [ ] Apache (or host web server) with `mod_rewrite` (or equivalent) for `public/.htaccess`
- [ ] MySQL or MariaDB database created
- [ ] Required PHP extensions enabled: `ctype`, `curl`, `dom`, `fileinfo`, `filter`, `hash`, `mbstring`, `openssl`, `pcre`, `pdo`, `pdo_mysql`, `session`, `tokenizer`, `xml`, `bcmath`, `json`, `zip` (and `intl` if available)
- [ ] Document root points to Laravel `public/` only
- [ ] SSL / HTTPS enabled for the domain (`APP_URL` uses `https://`)

## Laravel

- [ ] Production `.env` created on the server (not committed)
- [ ] `APP_KEY` generated
- [ ] `APP_ENV=production`
- [ ] `APP_DEBUG=false`
- [ ] `QUEUE_CONNECTION=sync`
- [ ] `SESSION_DRIVER=database`
- [ ] `CACHE_STORE=database`
- [ ] `SESSION_SECURE_COOKIE=true` (HTTPS)
- [ ] `composer install --no-dev --optimize-autoloader` completed (or equivalent vendor upload)
- [ ] `php artisan config:cache` / `route:cache` / `view:cache` after `.env` is final (optional but recommended)
- [ ] `storage/` and `bootstrap/cache/` writable by web and cron users
- [ ] `php artisan storage:link` completed if public disk files are used

## Database

- [ ] MySQL credentials in `.env` verified
- [ ] `php artisan migrate --force` succeeded
- [ ] `php artisan db:seed --class=SourceSeeder --force` succeeded
- [ ] Database backup taken before migrate / major changes
- [ ] Sessions / cache / password_reset_tokens tables present

## Mail

- [ ] `MAIL_MAILER=smtp` (not `log`) for real user email
- [ ] SMTP host, port, username, password set
- [ ] `MAIL_FROM_ADDRESS` is a mailbox or allowed sender on the domain
- [ ] SPF (and DKIM if available) considered for the sending domain
- [ ] Registration verification email received in a real inbox
- [ ] Password reset email received in a real inbox

## Collection

- [ ] At least one permitted public source enabled in Admin → Sources
- [ ] Agent Reach remains disabled
- [ ] cPanel cron calls `php artisan opportunities:collect` **or** `schedule:run` — not both
- [ ] `OPPORTUNITY_SCHEDULE_COLLECTION=false` when using direct collect cron
- [ ] Overlap protection confirmed (second overlapping run reports already running / lock)
- [ ] Source health updates after a run
- [ ] Source runs recorded with counts
- [ ] Second collect does not create duplicate opportunities for unchanged listings

## Security

- [ ] HTTPS enforced
- [ ] `APP_DEBUG=false` (no stack traces in browser)
- [ ] No API keys or DB passwords in admin UI or HTML source
- [ ] Authorization: users cannot access other users’ saves, proposals, applications, notifications
- [ ] Admin routes return 403 for non-admins
- [ ] SSRF protections remain active (private IPs / credential URLs blocked)
- [ ] Rate limits remain on login, registration, password email, verification resend, proposal generation
- [ ] `.env`, `storage/logs`, and application internals not web-accessible

## Verification (smoke)

### Authentication

- [ ] Register
- [ ] Login
- [ ] Logout
- [ ] Password reset flow
- [ ] Email verification flow

### Profile

- [ ] Update profile
- [ ] Add / remove skills
- [ ] Preferences persist

### Opportunities

- [ ] Collect sources
- [ ] Normalize / dedupe / persist
- [ ] Match scores appear for profiled users

### Dashboard and browsing

- [ ] High matches
- [ ] Opportunities list
- [ ] Opportunity detail
- [ ] Saved opportunities

### Proposals

- [ ] Generate (if `AI_ENABLED=true` and key configured) **or** safe disabled/missing-key behavior
- [ ] Edit / regenerate / mark ready

### Applications

- [ ] Create / track
- [ ] Mark applied
- [ ] Status changes and history

### Admin

- [ ] Users
- [ ] Sources and health
- [ ] Source runs
- [ ] System errors

### Notifications

- [ ] Unread count
- [ ] Mark read / mark all read

### Health

- [ ] `GET /up` returns success
- [ ] No obvious runtime errors or broken authz in a short browser walkthrough

---

## Out of scope for this launch phase

Do not block launch on: RAG, embeddings, vector DB, autonomous agents, auto-apply, browser automation, Chrome extension, billing, Stripe, mobile app, CRM, team features, white-label, extra job sources, advanced analytics, or career-page scraping.
