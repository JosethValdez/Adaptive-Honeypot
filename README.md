# A dynamic web page generator + SSH honeypot

A two-tier honeypot pretending to be a single neglected late-90s/early-2000s internal corporate server.

- **Web tier** generates decoy pages on-demand with a small local LLM (Ollama), so the attacker surface is infinite, internally consistent, and unfingerprintable.
- **SSH tier** is a Cowrie container wearing the same identity.
- Both surfaces are powered by a shared `identity.json`, so pivoting attackers see a coherent machine.

**Status:** Phase 2 complete (web + SSH live). Phase 3 (cross-surface observations) is next.

## The idea

Standard web honeypots are fixed decoy pages. Once an attacker sees `/admin.php` twice, it's obviously fake. Here, unknown paths trigger on-demand generation: the LLM creates a believable page for that filename, injects baits, caches the result, and serves it. The surface appears infinite and unfingerprintable.

## Tech stack

| Component | Tech | Role |
|-----------|------|------|
| Web tier | XAMPP (Apache + PHP) on Windows | Serves honeypot pages |
| Generation | Python + Ollama (`qwen2.5-coder:1.5b`) | On-demand page synthesis |
| SSH tier | Cowrie in Docker | SSH honeypot with shared identity |
| Identity | [config/identity.json](config/identity.json) | Single source of truth (hostname, OS, users, pages) |
| Observations | SQLite (`observations.db`) | Write-once snapshot store (Phase 3) |

## Architecture

```
Attacker -> Apache -> go.php
                       |
                       +-- log CLICK to clicks.log
                       +-- if cached: 302 to <name>.php
                       +-- else: spawn page_agent.py, 302 to gen_loading.php
                                                 |
                                                 +-- Ollama generates HTML
                                                 +-- linkfix.fix    (rewrite <a> through go.php)
                                                 +-- bait.inject    (cred comment + login form)
                                                 +-- observations.record (write-once snapshot)
                                                 +-- write <name>.php + <name>.ready

gen_loading.php polls every 2s -> 302 to <name>.php once .ready exists

Form POSTs -> trap.php -> traps.log + "Authentication Failed"

Cowrie SSH (Phase 2) reads honeyfs + cowrie.cfg generated from the same identity.json
```

## Files overview

### Generation pipeline
| File | Purpose |
|------|---------|
| [web_agent.py](web_agent.py) | Generates `index.php` |
| [page_agent.py](page_agent.py) | Generates `<name>.php` on demand |
| [linkfix.py](linkfix.py) | Rewrites `<a>` through `go.php`, strips malformed tags |
| [bait.py](bait.py) | Injects deterministic cred comments + login forms |
| [trap.php](trap.php) | Logs form submissions to `traps.log` |

### Routing (web tier)
| File | Purpose |
|------|---------|
| [go.php](go.php) | Single router for all internal links; spawns generators; logs to `clicks.log` |
| [gen_loading.php](gen_loading.php) | Polling loader for on-demand pages |
| [loading.php](loading.php) | Polling loader for `index.php` |

### Identity & config
| File | Purpose |
|------|---------|
| [config/identity.json](config/identity.json) | **Edit this** — hostname, OS, users, pages, services |
| [config/identity.py](config/identity.py) | JSON loader with cache |
| [config/cowrie_sync.py](config/cowrie_sync.py) | Generates `cowrie.cfg` + `honeyfs/etc/*` from `identity.json` |
| [config/cowrie/](config/cowrie/) | Generated config + honeyfs overlay (docker-compose bind mounts) |

### Docker & observations
| File | Purpose |
|------|---------|
| [docker-compose.yml](docker-compose.yml) | Cowrie container; note: uses `cowrie.cfg`, not `.local` |
| [config/observations.py](config/observations.py) | Write-once SQLite store (Phase 3) |

### Logs (generated)
- `clicks.log` — go.php access log
- `traps.log` — captured form submissions
- `cowrie-logs/cowrie.json` — Cowrie events, one per line

## Quick start

### 1. Web tier (Windows / XAMPP)

```
✓ Project already at c:\xampp\htdocs\server
✓ venv exists (activate with venv\Scripts\activate)
```

1. Start XAMPP Apache
2. Start Ollama: `ollama pull qwen2.5-coder:1.5b`
3. Hit `http://localhost/server/go.php?p=index.php&label=Server+Links`

(First hit generates, cached hits serve instantly.)

### 2. SSH tier (Docker)

```bash
# Regenerate config if you edited identity.json
python config/cowrie_sync.py

# Start container
docker compose up -d

# Test login (password: web1234)
ssh -p 2222 webmaster@localhost

# Monitor events
tail -f cowrie-logs/cowrie.json
```

**Verified (Phase 2):**
- `webmaster` logs in; `admin`, `backup`, `nobody` reject all passwords
- Prompt: `webmaster@srvweb01:/$`
- `/etc/motd`, `/etc/issue` are RHEL-themed
- `/proc/version` shows kernel 2.6.9-55.EL

### Useful commands
```bash
# Inspect / manage the observation DB
python -m config.observations init
python -m config.observations list
python -m config.observations list web
python -m config.observations show index.php

# Force a regen of index.php (dev only — uses ncat trigger)
bash listener.sh    # one shell
# then connect to 127.0.0.1:54321 from another shell to fire handler.sh
```

## Known limitations

| Issue | Details | Fix |
|-------|---------|-----|
| **`/etc/redhat-release` invisible in SSH** | File exists on disk but not in Cowrie's `fs.pickle` (emulated FS index) | Rebuild `fs.pickle` with Cowrie's `bin/createfs` |
| **`go.php` Windows-only** | Uses `cmd /c start` to spawn generators | Phase 4: add `GEN_CMD` env var for Linux |
| **Locked accounts regex hazard** | Cowrie reads passwords as regex; bare `*` or `!` is regex-special | `cowrie_sync.py` substitutes a 64-hex literal at generation time |

## Next phases

### Phase 3 — Cross-pollinate observations
- `observer.py` daemon that tails `cowrie.json` and records SSH file/command events into `observations.db` (same store as web side)
- Projects web observations into Cowrie's honeyfs (e.g., attacker hit `/db_backup.sql` on web → `/var/backups/db_backup.sql` appears in SSH)

### Phase 4 — Containerize everything
- Compose services: `web` (php-apache), `ollama`, `cowrie`, `observer`
- Persist DB and all logs to host volumes
- Fix Windows-only `go.php` spawn with `GEN_CMD` env var

## Design conventions

- **Identity is the contract** — Don't hardcode strings; derive everything from `config/identity.json`
- **Bait is deterministic per path** — Same filename always gets same bait (md5-seeded RNG). Consistency is load-bearing.
- **Observations are write-once** — First snapshot is frozen; repeats only bump counters
- **All internal links route through `go.php`** — Direct `<a href="X.php">` links bypass logging
- **No external links** — The site must look like a closed intranet
- **Aesthetic: late-90s corporate** — HTML 4.01 Transitional, plain white, no CSS/JS/frameworks. Unfingerprintable.
