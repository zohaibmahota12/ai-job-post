# Architecture

Opportunity Hunter is a Laravel 13 application on PHP 8.3. Production is expected to be Apache, MySQL, and cPanel cron. Local development and tests use SQLite. There is no Docker service, Redis dependency, queue worker, or WebSocket server.

## System shape

```text
Browser
  → Laravel HTTP (sessions, CSRF, policies)
  → Per-user profile, skills, saves, proposals, applications

cPanel cron
  → php artisan opportunities:collect
  → Source adapter
  → Normalizer
  → Deduplicator
  → opportunities table

Match checks run when a user opens a listing.
They do not write a score and they do not apply.
```

Users share the opportunity catalog. Everything about a person's work — profile, skills, matches, saves, proposals, applications, notifications — is stored with that user's id and is queried or authorized against the signed-in account.

## Multi-user model

`users.role` is `user` or `admin`. `users.is_active` lets an admin disable an account. Login rejects a correct password on a disabled account, and later requests from that session are logged out.

A profile row is created with the user. Skills live in `skills` and are attached through `user_skills`. Two accounts can reference the same skill row without sharing any other data. The application does not ship a hardcoded skill list.

Opportunities are not private. Saved rows, proposals, applications, notifications, and match rows are. Controllers load those records and return 404 when the policy says the current user does not own them, so a changed id does not reveal another account's draft.

## Authentication

Framework facilities, not a second auth library:

- Passwords use Laravel's `hashed` cast (bcrypt)
- Login, registration, and password-reset posts are rate limited by email and IP
- Password reset tokens live in `password_reset_tokens`
- Email verification uses Laravel's signed URL and `users.email_verified_at`. A separate verification-token table would duplicate that mechanism, so it is not created
- Unverified users can open the verification screen and log out. The dashboard and private data require `verified`
- Sessions are regenerated on login and invalidated on logout
- CSRF is the default `web` middleware. Blade forms include `@csrf`
- Mail defaults to the `log` mailer until SMTP is configured

Create an admin with `php artisan user:make-admin {email}` after that person has registered. The seeder does not insert a person.

## Database

| Table | Role |
| --- | --- |
| `users` | Account, role, active flag, password, verification timestamp |
| `password_reset_tokens` | Reset tokens |
| `sessions` | Database sessions |
| `user_profiles` | Bio, experience, location, job type, remote preference, budget, currency, keywords |
| `skills` | Canonical skill name and slug |
| `user_skills` | User ↔ skill |
| `sources` | External source registry. `driver` selects an adapter |
| `source_runs` | One row per collection attempt |
| `opportunities` | Normalized listing, raw payload, content hash |
| `opportunity_skills` | Opportunity ↔ skill |
| `opportunity_matches` | Per-user match row. `score` is nullable until scoring exists |
| `saved_opportunities` | Per-user saves |
| `proposals` | Draft content, status, nullable AI provider/model metadata |
| `applications` | Manual status: `NEW`, `SAVED`, `PROPOSAL_DRAFT`, `APPLIED`, `INTERVIEW`, `HIRED`, `REJECTED` |
| `notifications` | Per-user in-app notices |
| `system_errors` | Failures an admin can read without shell access to log files |

Foreign keys cascade when the parent user, skill, or opportunity is removed. Deleting a source nulls `opportunities.source_id` so listings are not destroyed with a connector. Deleting a proposal nulls `applications.proposal_id`.

`opportunities.content_hash` is unique. `(source_id, external_id)` is unique so a repeated external id from the same source collapses. Cross-source duplicates use the hash of normalized title, company, and canonical URL, ignoring `www` and query strings.

## Opportunity pipeline

```text
SourceAdapter::collect()
  → RawOpportunity
  → OpportunityNormalizer
  → OpportunityDeduplicator
  → opportunities + opportunity_skills
```

`AgentReachSourceAdapter` is the only production adapter. It refuses to collect. Tests register a fixture adapter to prove the pipeline stores one row and skips the duplicate on the next run.

`php artisan opportunities:collect` always writes a `source_runs` row. Disabled sources are `skipped`. A thrown adapter error is `failed` and also stored in `system_errors`. The command's exit code is non-zero when any source fails, which cPanel can email. The command does not call the matcher.

The daily schedule in `routes/console.php` is gated by `OPPORTUNITY_SCHEDULE_COLLECTION` and uses `withoutOverlapping`. The flag defaults to false.

## Matching

`MatchEvaluator` runs seven deterministic checks:

- skill overlap
- preferred and excluded keywords
- job type
- workplace
- location
- budget, only when the currency matches
- years of experience against `required_experience_years`

Each check returns pass, fail, or unknown. `MatchEvaluation::score()` returns null. The opportunity page shows the notes. The dashboard's high-match list reads stored scores and stays empty until a later phase writes them.

## Security

- Eloquent and query-builder bindings for SQL
- Blade `{{ }}` for HTML
- Listing links are rendered only for `http` and `https` URLs
- Validation is in form requests. Controllers persist `validated()` fields, not the raw request
- `role` and `is_active` are not mass assignable
- Proposal AI columns are not mass assignable
- Admin routes use an `admin` middleware check on the session user
- An admin cannot disable their own row from the user list

## Shared hosting

- Document root: `public/`
- PHP 8.3
- MySQL via `DB_CONNECTION=mysql`
- `QUEUE_CONNECTION=sync` so work finishes inside the cron process
- `CACHE_STORE=database` and `SESSION_DRIVER=database` avoid Redis
- Cron: `php /home/USER/opportunity-hunter/artisan opportunities:collect`
- Compiled CSS is produced with `npm run build` before upload if the host has no Node.js

## Extension points

- Add a class that implements `App\Sources\SourceAdapter` and register it on `SourceManager`
- Insert a `sources` row whose `driver` matches that class
- Add a `MatchCriterion` and include it in the `MatchEvaluator` binding
- Persist evaluations into `opportunity_matches` when scoring starts
- Fill `proposals.ai_provider`, `ai_model`, and `ai_metadata` only when a generator exists, and still require the user to send the proposal

See [Agent Reach findings](AGENT_REACH.md) and [Roadmap](ROADMAP.md).
