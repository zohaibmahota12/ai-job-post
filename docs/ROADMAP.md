# Roadmap

Phase 1 is the only phase implemented in this repository. Later phases are listed so the boundaries stay clear. Do not treat them as work that is already done.

## Phase 1 — Foundation

Implemented now.

- Accounts: registration, login, logout, password reset, email verification, sessions, and an admin role
- Per-user profiles and a normalized skill catalog
- Database tables for opportunities, sources, matches, saves, proposals, applications, and notifications
- A source adapter seam, with Agent Reach registered but not called
- Deterministic match checks that do not produce a score
- A dashboard and a small admin area
- A cPanel-friendly collection command that records a run and stops

## Phase 2 — Opportunity collection and Agent Reach integration

Not implemented.

- Decide which Agent Reach channels are acceptable on shared hosting
- Call only verified upstream tools from a source adapter
- Map real payloads into `RawOpportunity`
- Keep the adapter from submitting applications or logging into job boards as the user

## Phase 3 — Deduplication and deterministic matching

Not implemented as an automated job.

The deduplicator and match criteria exist. This phase should persist `opportunity_matches` for each user after a collection run, still without a weighted score and without an AI ranker.

## Phase 4 — Scoring and dashboard

Not implemented.

- Turn criterion results into an explainable score
- Fill the high-match section of the dashboard from stored scores
- Keep the explanation next to the number

## Phase 5 — AI proposal generation

Not implemented.

- Generate a draft the user can edit
- Store provider and model metadata on the proposal
- Never send the draft

## Phase 6 — Notifications and scheduled collection

Not implemented.

- Notify a user when a new listing fits their profile
- Enable the daily schedule flag
- Keep cron as `php artisan schedule:run` or a direct artisan call

## Phase 7 — Production hardening

Not implemented.

- Backup and restore notes for cPanel MySQL
- Stricter content security headers
- Operational review of log retention, mail delivery, and failed-run alerts
