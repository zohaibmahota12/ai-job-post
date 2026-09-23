# Opportunity Hunter

Opportunity Hunter is a multi-user web app for developers who want a private shortlist of freelance projects and remote jobs. Each account has its own profile, skills, saved listings, proposal drafts, and application notes.

The product does not apply, send email to clients, or submit forms on your behalf. You review a listing and apply yourself.

Phases 1–4 are complete: accounts, profiles, opportunity collection, deterministic matching, AI proposals (optional), applications, and production-ready sources. Phase 5 documents cPanel production launch.

## Requirements

- PHP **8.3+** with: `ctype`, `curl`, `dom`, `fileinfo`, `filter`, `hash`, `mbstring`, `openssl`, `pcre`, `pdo`, `pdo_mysql` (production) or `pdo_sqlite` (local/tests), `session`, `tokenizer`, `xml`, `bcmath`, `json`, `zip` (recommended), `intl` (recommended)
- Composer
- Node.js 20 or newer, only to compile CSS
- MySQL 8 / MariaDB for a cPanel deployment
- SQLite is enough for local development and the test suite

The app does not need Docker, Redis, a queue worker, Supervisor, or root access.

## Local setup

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
npm install
npm run build
php artisan serve --host=127.0.0.1 --port=47231
```

Open [http://127.0.0.1:47231](http://127.0.0.1:47231).

Register an account, then confirm the verification message. With `MAIL_MAILER=log`, that message is written to `storage/logs/laravel.log`.

Promote an existing user to admin:

```bash
php artisan user:make-admin you@example.com
```

## Production database

In `.env`:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=opportunity_hunter
DB_USERNAME=your_user
DB_PASSWORD=your_password
```

Then run `php artisan migrate --force --seed`.

## Shared hosting

See the full guide and launch checklist:

- [Production deployment (cPanel)](docs/PRODUCTION_DEPLOYMENT.md)
- [Production checklist](docs/PRODUCTION_CHECKLIST.md)

Point the domain document root at the `public` directory. Do not expose the project root.

Recommended cron (keep `OPPORTUNITY_SCHEDULE_COLLECTION=false`):

```cron
0 6 * * * /usr/bin/php /home/USER/opportunity-hunter/artisan opportunities:collect
```

Do not also enable `schedule:run` for collection unless you turn the direct collect cron off and set `OPPORTUNITY_SCHEDULE_COLLECTION=true`.

Use `APP_ENV=production`, `APP_DEBUG=false`, `QUEUE_CONNECTION=sync`, `CACHE_STORE=database`, and `SESSION_DRIVER=database`. No Redis process is required.

## Tests

Requires PHP 8.3+:

```bash
php artisan test --compact
vendor/bin/pint --dirty --format agent
```

## Docs

- [Architecture](docs/ARCHITECTURE.md)
- [Production deployment](docs/PRODUCTION_DEPLOYMENT.md)
- [Production checklist](docs/PRODUCTION_CHECKLIST.md)
- [Roadmap](docs/ROADMAP.md)
- [Agent Reach findings](docs/AGENT_REACH.md)
