# Phase 4 — Production readiness

Phase 4 hardens Opportunity Hunter for continuous shared-hosting operation without redesigning the app or adding AI subsystems.

## Delivered

- Source health fields: consecutive failures, last failure, last item/created/updated/duplicated counts, last duration
- Explainable health states: Healthy, Warning, Failing, Disabled
- Bounded HTTP retries for connection failures and 5xx only
- Canonical URL tracking-parameter stripping while preserving meaningful query params
- HTML-stripped descriptions; explicit closed/expired listing signals; deadline-based expiry
- Collection lock compatible with database cache / cPanel cron
- Improved `opportunities:collect` summary output
- Admin source health + run-now action; richer source-run columns
- Secret redaction in collection diagnostics
- Seeded real public sources (disabled by default): Remotive, RemoteOK, We Work Remotely programming RSS, Jobicy
- Offline fixtures and regression tests
- [PRODUCTION_DEPLOYMENT.md](PRODUCTION_DEPLOYMENT.md)

## Real sources

| Key | Type | Endpoint | Enabled by default |
| --- | --- | --- | --- |
| `remotive_remote_jobs` | json_api | `https://remotive.com/api/remote-jobs` | No |
| `remoteok_api` | json_api | `https://remoteok.com/api` | No |
| `weworkremotely_programming_rss` | rss | `https://weworkremotely.com/categories/remote-programming-jobs.rss` | No |
| `jobicy_remote_jobs` | json_api | `https://jobicy.com/api/v2/remote-jobs` | No |

Operators must enable sources intentionally and respect each provider’s attribution and rate guidance. Agent Reach remains disabled and is not invoked during normal collection.

## Out of scope (still)

- Match alert emails / push notifications
- Billing, mobile apps, auto-apply, embeddings, RAG, browser automation
- Universal career-page scraping or CAPTCHA bypass
