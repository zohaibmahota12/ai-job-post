# Roadmap

## Phase 1 — Foundation

Implemented.

- Accounts: registration, login, logout, password reset, email verification, sessions, and an admin role
- Per-user profiles and a normalized skill catalog
- Database tables for opportunities, sources, matches, saves, proposals, applications, and notifications
- Source adapter seam, with Agent Reach registered but not called
- Deterministic match checks without a persisted numeric score
- Dashboard and admin area
- cPanel-friendly collection command shell

## Phase 2 — Opportunity ingestion, scoring, and dashboard

Implemented in this repository.

- Real RSS/Atom adapter with SSRF-safe HTTP fetching and fixture-based tests
- Generic public JSON API adapter template (no commercial provider claimed as verified)
- Collection lifecycle: fetch → parse → normalize → validate → deduplicate → store/update → match → score
- Deterministic explainable match scores persisted on `opportunity_matches`
- Dashboard high-match list, opportunity filters, and detail breakdown
- Admin enable/disable sources and richer source-run counters
- Agent Reach remains optional and outside the core pipeline
- cPanel cron documentation; schedule flag still defaults to off

## Phase 3 — AI proposal generation, application tracking, notifications

Implemented. See [PHASE_3.md](PHASE_3.md).

- Optional OpenAI-compatible proposal generation with provider abstraction
- Proposal review/edit, version history, draft/ready/archived workflow
- Manual external apply + application status history
- In-app notifications for proposal and application events
- Dashboard sections for proposal work and application pipeline
- AI remains optional (`AI_ENABLED=false` by default)

Opportunity Hunter does not automatically submit applications.

## Phase 4 — Operational hardening

Not implemented.

- Notify a user when a new listing fits their profile (broader match alerts)
- Optional enablement of the daily schedule flag in production
- Backup notes, stricter security headers, mail delivery review

Do not treat later phases as complete. Do not add auto-apply, browser automation, billing, embeddings, or vector search under Phase 3.
