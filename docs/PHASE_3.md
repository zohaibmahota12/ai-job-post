# Phase 3 — AI proposals, applications, and notifications

Opportunity Hunter does not automatically submit applications.

Phase 3 adds optional AI-assisted proposal drafting, proposal versioning, richer application tracking, and in-app notifications. Discovery, deterministic matching, and manual apply-from-source remain Phase 2 behavior.

## Architecture

```text
User opens opportunity
  → optional Generate proposal
      → ProposalGenerator
          → AiManager → AiProvider (OpenAI HTTP)
          → ProposalContextBuilder (verified profile + opportunity only)
          → ProposalPromptBuilder (system rules + untrusted opportunity DATA)
          → GeneratedProposalParser (validate JSON / plain text)
          → ProposalVersionService (preserve previous drafts)
  → user reviews / edits
  → Mark Ready (explicit)
  → Apply Externally (original http/https URL only)
  → Mark as Applied (manual status)
  → track INTERVIEW / HIRED / REJECTED
  → UserNotification rows
```

AI is optional. With `AI_ENABLED=false` the rest of the app continues to work; only generation endpoints fail with a clear message.

## Environment

```env
AI_ENABLED=false
AI_PROVIDER=openai
AI_API_KEY=
AI_MODEL=gpt-4o-mini
AI_BASE_URL=https://api.openai.com/v1
AI_TIMEOUT=30
AI_CONNECT_TIMEOUT=5
AI_MAX_TOKENS=1200
AI_RATE_LIMIT_PER_MINUTE=5
```

Secrets stay in environment configuration. Admin screens do not display AI API keys.

## Proposal statuses

Existing values are preserved:

- `draft`
- `ready`
- `archived`

AI generation always leaves status as `draft`. Ready requires an explicit user action (`Mark ready` or status select).

## Proposal versions

Table `proposal_versions` stores prior content/subject with source:

- `ai`
- `user`
- `regenerate`

Regeneration snapshots the current draft before replacing it. Failures leave the active proposal unchanged.

## Application workflow

Existing statuses are preserved:

`NEW` → `SAVED` → `PROPOSAL_DRAFT` → `APPLIED` → `INTERVIEW` → `HIRED` / `REJECTED`

Optional fields: `contact_name`, `follow_up_at`, `external_url`.

`application_status_histories` records status transitions with `changed_by` and optional note.

**Apply Externally** opens the opportunity’s safe `http`/`https` URL. **Mark as Applied** only updates local tracking.

## Notifications

Existing `notifications` table (`UserNotification`) is extended in behavior, not replaced.

Examples:

- `proposal.generated`
- `proposal.generation_failed`
- `proposal.ready`
- `application.applied`
- `application.interview`
- `application.hired`
- `application.rejected`

HTTP/database only — no WebSockets.

## Prompt safety and privacy

- Opportunity text is treated as untrusted DATA.
- System instructions tell the model never to follow instructions embedded in listings.
- Only the signed-in user’s profile fields, skills, and the target opportunity/match context are sent.
- Passwords, session data, API keys, and other users’ data are never sent.
- Proposal body is stored and rendered as plain text (escaped in Blade).

## Rate limiting

Authenticated proposal generate/regenerate routes use `throttle:proposal-generation` (`AI_RATE_LIMIT_PER_MINUTE`, default 5/minute/user).

## Failure behavior

Timeouts, HTTP errors, invalid credentials, empty/malformed responses, and disabled AI surface safe user messages. Existing drafts are not overwritten on failure. Diagnostics are logged without secrets.

## cPanel notes

- `QUEUE_CONNECTION=sync` remains valid; no worker/Redis required.
- Configure AI env vars in the host environment or `.env`.
- Collection cron from Phase 2 is unchanged.

## Limitations

- Only the OpenAI-compatible HTTP provider is implemented.
- No auto-apply, browser automation, outreach email, billing, embeddings, or RAG.
- High-match opportunity alerts for new listings remain a later phase concern unless triggered by existing flows.

See [ARCHITECTURE.md](ARCHITECTURE.md) and [ROADMAP.md](ROADMAP.md).
