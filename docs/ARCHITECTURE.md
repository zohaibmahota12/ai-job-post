# Architecture

Opportunity Hunter is a Laravel 13 application on PHP 8.3. Production is expected to be Apache, MySQL, and cPanel cron. Local development and tests use SQLite. There is no Docker service, Redis dependency, queue worker, or WebSocket server.

## System shape

```text
Browser
  → Laravel HTTP (sessions, CSRF, policies)
    → Per-user profile, skills, saves, proposals, applications

cPanel cron
  → php artisan opportunities:collect
  → Source adapter (rss | json_api | …)
  → Safe HTTP fetch (SSRF-safe)
  → Parse / normalize / validate
  → Deduplicate
  → opportunities table
  → Deterministic match + score per active user
  → opportunity_matches
  → Dashboard
```

Users share the opportunity catalog. Everything about a person's work — profile, skills, matches, saves, proposals, applications, notifications — is stored with that user's id and is queried or authorized against the signed-in account.

## Multi-user model

`users.role` is `user` or `admin`. `users.is_active` lets an admin disable an account. Login rejects a correct password on a disabled account, and later requests from that session are logged out.

A profile row is created with the user. Skills live in `skills` and are attached through `user_skills`. Two accounts can reference the same skill row without sharing any other data.

Opportunities are not private. Saved rows, proposals, applications, notifications, and match rows are. Controllers load those records and return 404 when the policy says the current user does not own them.

## Authentication

Framework facilities, not a second auth library:

- Passwords use Laravel's `hashed` cast (bcrypt)
- Login, registration, and password-reset posts are rate limited by email and IP
- Password reset tokens live in `password_reset_tokens`
- Email verification uses Laravel's signed URL and `users.email_verified_at`
- Unverified users can open the verification screen and log out. The dashboard and private data require `verified`
- Sessions are regenerated on login and invalidated on logout
- CSRF is the default `web` middleware. Blade forms include `@csrf`
- Mail defaults to the `log` mailer until SMTP is configured

Create an admin with `php artisan user:make-admin {email}` after that person has registered.

## Database

| Table | Role |
| --- | --- |
| `users` | Account, role, active flag, password, verification timestamp |
| `password_reset_tokens` | Reset tokens |
| `sessions` | Database sessions |
| `user_profiles` | Bio, experience, location, job type, remote preference, budget, currency, keywords |
| `skills` | Canonical skill name and slug |
| `user_skills` | User ↔ skill |
| `sources` | External source registry. `key` is unique. `driver` selects an adapter. `type` is the source family. `config` holds non-secret endpoint settings. Health summary: `last_run_at`, `last_success_at`, `last_failure_at`, `last_error`, `consecutive_failures`, last item/created/updated/duplicated counts, `last_duration_ms` |
| `source_runs` | One row per collection attempt, with found/created/updated/skipped/duplicated counts |
| `opportunities` | Normalized listing, raw payload, `canonical_url`, content hash |
| `opportunity_skills` | Opportunity ↔ skill |
| `opportunity_matches` | Per-user match row with numeric `score` and JSON `reasons` breakdown |
| `saved_opportunities` | Per-user saves |
| `proposals` | Draft content, subject, status, nullable AI provider/model metadata, `generated_by_ai` |
| `proposal_versions` | Lightweight proposal history (`ai` / `user` / `regenerate`) |
| `applications` | Manual status tracking plus optional contact/follow-up/external URL |
| `application_status_histories` | Status transition history per application |
| `notifications` | Per-user in-app notices |
| `system_errors` | Failures an admin can read without shell access to log files |

Compensation uses `budget_min` / `budget_max` / `currency` for both project budgets and salary-like ranges. Separate salary columns are not required for Phase 2.

`opportunities.content_hash` is unique. `(source_id, external_id)` is unique. `canonical_url` is indexed for URL-based deduplication.

## Source adapter architecture

`SourceAdapter` requires:

- `driver()` — registry key (`rss`, `json_api`, `agent_reach`)
- `type()` — source family
- `collect(Source)` — fetch, parse, return `CollectionResult` of `RawOpportunity`

Registered adapters:

| Driver | Status |
| --- | --- |
| `rss` | Implemented. Public RSS 2.0 / Atom via `SafeHttpFetcher` |
| `json_api` | Configuration-driven public JSON API adapter. Seeded Remotive, RemoteOK, and Jobicy endpoints ship disabled until an operator enables them |
| `agent_reach` | Refuses to collect. Optional boundary only. See [AGENT_REACH.md](AGENT_REACH.md) |

Seeded real sources (disabled by default): `remotive_remote_jobs`, `remoteok_api`, `weworkremotely_programming_rss`, `jobicy_remote_jobs`. See [PHASE_4.md](PHASE_4.md).

Source-specific parsing stays inside the adapter. The collector never depends on Agent Reach.

`SafeHttpFetcher` validates `http`/`https` URLs, blocks credentials in URLs, blocks localhost/private/link-local/metadata ranges, resolves DNS and rejects private answers, enforces connect/response timeouts, caps response size, follows a limited number of redirects with re-validation on each hop, and retries only connection failures and HTTP 5xx (bounded; 4xx and blocked URLs are not retried).

## Collection lifecycle

```text
opportunities:collect
  → for each matching source (by key or driver filter)
      → create source_run (running)
      → skip if disabled
      → adapter.collect()
      → normalize each RawOpportunity
      → deduplicate
      → create or update Opportunity + skills
      → sync matches for touched opportunities against active verified users
      → finish source_run + update source last_* fields
  → continue after individual source failures
```

Repeated runs do not create duplicate opportunities. Exit code is non-zero when any source fails.

### Deduplication order

1. Same `source_id` + `external_id`
2. Same `canonical_url` (host + path, lowercased, `www.` stripped, tracking query params removed, meaningful query params kept and sorted)
3. Same `content_hash` fingerprint of normalized title + company + canonical URL

### Lifecycle

Opportunities use `open`, `closed`, and `expired`. Collection may mark a listing closed when a source supplies an explicit closed signal. Opportunities with a known past `deadline_at` are marked expired (never deleted). Deadlines are never invented.

### Matching and scoring

The seven Phase 1 deterministic checks remain:

- skills, keywords, job type, workplace, location, budget (same currency only), experience

`MatchScorer` turns those outcomes into an explainable score using weights from `config/opportunity.php`:

| Factor | Weight |
| --- | --- |
| skills | 30 (proportional to overlap vs opportunity skills) |
| keywords | 20 (proportional to preferred keyword hits; excluded keyword → 0 / fail) |
| experience | 15 |
| job type | 10 |
| workplace | 10 |
| location | 5 |
| budget | 10 |

Unknown factors earn 0 points and stay labeled `unknown`. Scores persist on `opportunity_matches` (unique per user/opportunity). Recalculation happens:

- after collection for touched opportunities (all active verified users)
- after profile or skill changes (that user vs open opportunities)
- when a user opens an opportunity detail page (that pair)

## Security

- Eloquent / query-builder bindings for SQL
- Blade `{{ }}` for HTML (descriptions are plain text, not raw HTML)
- Listing links only for `http` / `https`
- SSRF protections on source fetching
- Validation in form requests; controllers persist `validated()` fields
- `role` and `is_active` are not mass assignable
- Proposal AI columns are not mass assignable
- Admin routes use `admin` middleware
- Source config never displays keys named like `api_key`, `token`, `secret`, `password`, `authorization`, or `auth`
- Do not store secrets in `sources.config`

## Shared hosting / cPanel cron

- Document root: `public/`
- PHP 8.3+
- MySQL via `DB_CONNECTION=mysql`
- `QUEUE_CONNECTION=sync`
- `CACHE_STORE=database` and `SESSION_DRIVER=database`
- Collection is **not** scheduled unless `OPPORTUNITY_SCHEDULE_COLLECTION=true`

Recommended cPanel cron (direct artisan call):

```bash
0 6 * * * /usr/bin/php /home/USER/opportunity-hunter/artisan opportunities:collect >/dev/null 2>&1
```

Or via the scheduler (only if the schedule flag is enabled):

```bash
* * * * * /usr/bin/php /home/USER/opportunity-hunter/artisan schedule:run >/dev/null 2>&1
```

Do not introduce Redis, Supervisor, Horizon, Docker, Kubernetes, systemd, or persistent workers for collection.

## Intended user workflow

View opportunity → review score breakdown → optional AI or manual proposal draft → review/edit → mark ready → open original source URL → apply manually → record application status → receive in-app notifications.

There is no auto-apply button and no client email outreach. Opportunity Hunter does not automatically submit applications.

Optional AI configuration lives in `config/ai.php` / `AI_*` environment variables. See [PHASE_3.md](PHASE_3.md).

## Known limitations

- HTML career-page scraping is not implemented as a universal scraper
- Agent Reach is not invoked
- Matching all users after collection is synchronous; fine for small SaaS on shared hosting, not a background worker architecture
- Descriptions are escaped plain text; HTML from feeds/APIs is stripped at normalize time
- AI proposal generation depends on an external provider when enabled; the app stays usable when AI is disabled
- Seeded public sources remain disabled until an operator enables them and accepts provider attribution/rate limits

See [Agent Reach findings](AGENT_REACH.md), [Phase 3](PHASE_3.md), [Phase 4](PHASE_4.md), [Production deployment](PRODUCTION_DEPLOYMENT.md), and [Roadmap](ROADMAP.md).
