# Agent Reach findings

Verified against the upstream repository on 22 September 2026:

- Repository: [https://github.com/Panniantong/Agent-Reach](https://github.com/Panniantong/Agent-Reach)
- Package version in `pyproject.toml` on `main`: 1.5.0
- License: MIT (Copyright (c) 2025 Agent Eyes)
- Python requirement: `>=3.10`
- Console entry point: `agent-reach` → `agent_reach.cli:main`

Primary sources read:

- `pyproject.toml`
- `docs/README_en.md`
- `docs/install.md`
- `agent_reach/cli.py`
- `agent_reach/core.py`
- `agent_reach/channels/__init__.py`
- `agent_reach/channels/boss.py`

Anything not established by those files is marked `UNKNOWN — NEEDS VERIFICATION`.

## What it is

The project describes itself as a capability layer, not a wrapper and not a job API. `agent_reach/core.py` says Agent Reach installs and health-checks upstream tools. After that, an agent calls those tools directly. The `AgentReach` Python class only exposes `doctor()` and `doctor_report()`.

There is no method that returns a list of freelance jobs.

## Installation

Documented install:

```bash
pip install https://github.com/Panniantong/agent-reach/archive/main.zip
agent-reach install --env=auto
```

`agent-reach install` is check-only unless `--system` is passed. `--system` is allowed to install system packages, global tools, config, and skill files. Optional `--channels` values documented in `docs/install.md` and `cli.py`: `opencli`, `twitter`, `xiaoyuzhou`, `xueqiu`, `xiaohongshu`, `reddit`, `facebook`, `instagram`, `bilibili`, `linkedin`, `boss`, `all`.

Core Python dependencies from `pyproject.toml`: `requests`, `feedparser`, `python-dotenv`, `loguru`, `pyyaml`, `rich`, `yt-dlp[default]`.

Optional extras: `browser` (Playwright), `cookies` (`browser-cookie3`), `all` (those plus `mcp[cli]`).

Config and tokens are stored under `~/.agent-reach/`, not in this application.

## CLI

Commands registered in `agent_reach/cli.py`:

- `setup`
- `install`
- `configure`
- `doctor` (`--json` for a machine-readable report)
- `uninstall`
- `skill`
- `format` (currently `xhs` only)
- `transcribe`
- `check-update`
- `watch`
- `version`

There is no `search`, `jobs`, or `serve` command.

## Supported channels

Registered in `agent_reach/channels/__init__.py`:

GitHub, Twitter/X, YouTube, Reddit, Facebook, Instagram, Bilibili, XiaoHongShu, LinkedIn, Boss直聘 (`boss`), Xiaoyuzhou, V2EX, Xueqiu, RSS, Exa search, and a generic web reader.

The English readme describes the usual backends: Jina Reader, `gh`, `yt-dlp`, `feedparser`, Exa via `mcporter`, and several cookie or browser-session tools. Boss直聘 is a job channel. Its module documents `boss-agent-cli` over the Chrome DevTools Protocol on `127.0.0.1:9222`, using a real logged-in Chrome profile. Headless mode is explicitly treated as unsafe. The doctor check does not search jobs.

LinkedIn full profile and job search is documented as an optional MCP server (`mcp-server-linkedin` via `uvx`), with Jina Reader as the public-page fallback.

## Authentication

| Area | Verified behavior |
| --- | --- |
| Package install | No API key for the package itself |
| Web, RSS, public GitHub, YouTube, V2EX | Documented as usable without an extra key after the upstream tool exists |
| Exa search | Free Exa key, wired through `mcporter` |
| Twitter, Reddit, XiaoHongShu, Xueqiu | Cookies or a logged-in browser session |
| Boss直聘 | User logs into zhipin.com in a dedicated Chrome window. The tool must not ask for the password |
| LinkedIn full search | MCP server login flow |

## Infrastructure questions

| Question | Finding |
| --- | --- |
| Runtime | Python 3.10+ plus the upstream CLIs each channel needs |
| Docker required for the core package? | Not declared in `pyproject.toml` or the install guide |
| Docker required for some channels? | `UNKNOWN — NEEDS VERIFICATION`. The readme's tier notes mention complex setups. The files read for this phase do not show a required Docker image for the core CLI |
| Persistent process | The core CLI is not a daemon. Boss直聘 requires a long-lived Chrome process with a debugging port. LinkedIn MCP is started as a server process. `UNKNOWN — NEEDS VERIFICATION` for every other optional MCP backend |
| Redis | Not listed in the core dependencies. `UNKNOWN — NEEDS VERIFICATION` whether an optional channel pulls it in transitively |
| Remote HTTP API | No serve command and no HTTP server in `core.py`. It is a local CLI |
| Callable from PHP on shared hosting | Only if the host can execute Python 3.10+ and the chosen upstream binaries. Typical cPanel accounts cannot run a dedicated Chrome debugging port or install arbitrary global CLIs |
| Safe to call from this app today | No. Phase 1 does not invoke it |

## Integration choice for this app

Agent Reach is the internet-access layer, but it is not an opportunity API. Phase 2 should:

1. Run `agent-reach doctor --json` only as a health check, and only where Python is actually installed.
2. Add one adapter per verified upstream that can return public listing data without impersonating the user.
3. Prefer channels that are a single command and exit, such as RSS or a public HTTP read, over channels that need a desktop browser.
4. Keep Boss直聘 and other logged-in browser channels off shared hosting. They need a local Chrome profile and must not be automated into an apply flow.
5. Normalize whatever text comes back into `RawOpportunity`. Do not pretend the CLI already returns this app's schema.

`App\Sources\AgentReachSourceAdapter` throws `SourceCollectionException` on purpose. The seeded `agent_reach` source is disabled. Enabling it and running `php artisan opportunities:collect` records a failed run and writes a system error. It does not invent listings.
