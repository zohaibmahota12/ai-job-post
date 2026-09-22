# Opportunity Hunter

Opportunity Hunter is a multi-user web app for developers who want a private shortlist of freelance projects and remote jobs. Each account has its own profile, skills, saved listings, proposal drafts, and application notes.

The product does not apply, send email to clients, or submit forms on your behalf. You review a listing and apply yourself.

This repository is the Phase 1 foundation: accounts, profiles, the database, a source adapter seam, and a dashboard. Live collection is not turned on.

## Requirements

- PHP 8.3 or newer, with `mbstring`, `xml`, `curl`, `sqlite3`, `mysql`, `bcmath`, and `intl`
- Composer
- Node.js 20 or newer, only to compile CSS
- MySQL 8 for a cPanel deployment
- SQLite is enough for local development and the test suite

The app does not need Docker, Redis, a queue worker, or root access.

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

Point the domain document root at the `public` directory. Do not expose the project root.

Cron can call the collection command directly:

```cron
0 6 * * * php /home/USER/opportunity-hunter/artisan opportunities:collect
```

`OPPORTUNITY_SCHEDULE_COLLECTION` stays `false` until collection is implemented. When it is `true`, a one-minute cron entry is enough:

```cron
* * * * * php /home/USER/opportunity-hunter/artisan schedule:run
```

Use `QUEUE_CONNECTION=sync`, `CACHE_STORE=database` or `file`, and `SESSION_DRIVER=database`. No Redis process is required.

## Tests

```bash
php artisan test
vendor/bin/pint --dirty
```

## Docs

- [Architecture](docs/ARCHITECTURE.md)
- [Roadmap](docs/ROADMAP.md)
- [Agent Reach findings](docs/AGENT_REACH.md)
