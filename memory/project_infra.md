---
name: project-infra
description: Hosting, storage, deployment config for Timber Trace Crafts — Hostinger + Cloudflare R2
metadata:
  type: project
---

## Hosting — Hostinger Business (shared)

- Domain: timbertracecrafts.com
- Server IP: 5.183.10.138
- SSH: `ssh -p 65002 u903552178@5.183.10.138`
- Deploy path: `~/domains/timbertracecrafts.com/public_html`
- Git integration: hPanel → Advanced → Git → auto-deploys `main` → `public_html`
  on push to GitHub (`origin` = `git@github.com:mjmiller41/timber-trace-crafts.git`).
  **Correction (2026-07-04): the live `public_html` IS a real git checkout** —
  `git -C public_html rev-parse HEAD` returns the deployed SHA and advances on
  each auto-deploy. The earlier "opaque synced copy / not a git repo" belief was
  wrong. This is what makes the deploy race-free: poll the server HEAD until it
  equals the pushed SHA, THEN run `deploy.sh`. Hostinger still runs no
  post-publish hook, so `deploy.sh` (migrate, cache, env decrypt) is invoked over
  SSH — but now automatically by `ship.sh`, not by hand. See "AI-driven deploy".
- Root `.htaccess` redirects all traffic into `public/` and blocks `.env` access
- Node.js unavailable on server — `public/build/` is committed to git and built locally via pre-push hook
- `exec()` disabled — `php artisan storage:link` fails; use `ln -s` directly in terminal instead

## Image Storage — Cloudflare R2

- Bucket: `timber-trace-crafts`
- Public URL: `https://pub-82fe4a94d274416a9b5ab8028bcd8627.r2.dev`
- Endpoint: `https://3692088bc3f65a6e2a74ae1b1da92c73.r2.cloudflarestorage.com`
- FILESYSTEM_DISK=r2 in both local and production .env
- All media records have `disk='r2'`
- `Media::url()` resolves correct URL via `Storage::disk($this->disk)->url($this->path)`
- Never use `asset('storage/...')` — always use `$media->url()` or `Storage::disk('r2')->url($path)`
- `php artisan media:upload-to-r2` — uploads local images to R2 and updates all media records

**Why:** Eliminated symlink complexity on shared hosting, decoupled image serving from Hostinger entirely.

## Key artisan commands

- `php artisan media:sync` — insert media DB records for any files in storage missing from DB
- `php artisan media:upload-to-r2` — upload local images to R2 and set disk='r2' on all records

## AI-driven deploy — `ship.sh` (added 2026-07-04)

One command replaces the whole manual release (encrypt → commit → push → SSH →
`deploy.sh`). Run from repo root **on `main`**:

- `./ship.sh` — full pipeline: re-encrypt `.env.production` only if it drifted
  from the committed ciphertext (CRLF-normalized, order-insensitive compare) →
  `npm run build` + commit `public/build/` → push `main` → **poll the server's
  published HEAD until it equals the pushed SHA** (race-free; timeout 480s) →
  SSH `bash deploy.sh` → `curl` smoke-test the live site (must be 200).
- `./ship.sh --prep-only` — encrypt-if-needed + build + commit; no push/deploy.
- `./ship.sh --remote-only [SHA]` — skip prep/push; wait for SHA (default local
  `main`) to publish, then `deploy.sh` + verify. Used by the pre-push hook.

Encryption is automatic and keyless-to-the-human: `ship.sh` reads the key from
`~/.secrets/ttc-env-key` (a chmod-600 mirror of the server's copy, outside git;
mirror it with `ssh … 'cat ~/.secrets/ttc-env-key' > ~/.secrets/ttc-env-key`).
No manual `env:encrypt`/`env:decrypt`, no manual SSH.

`.git/hooks/pre-push` (replaced the old build+reminder hook): stands down when
`ship.sh` drives (`TTC_SHIP=1`); on a bare `git push` of `main` it builds assets
and backgrounds `./ship.sh --remote-only <sha>` (log:
`storage/logs/deploy-bare-push.log`) so a plain push still auto-deploys. Caveat:
a pre-push hook can't inject new commits into the in-flight push, so production
**env re-encryption must go through `./ship.sh`** (bare push won't carry it).

Requires passwordless SSH (confirmed working) and the local key file.

## Production env — encrypted-at-rest workflow (added 2026-07-02)

`.env` and `.env.production` are gitignored and never committed. Instead, an
encrypted snapshot ships with the code so `deploy.sh` can rebuild `.env` on
every deploy instead of it drifting out of sync (this is what caused the
`SESSION_SECURE_COOKIE`/`APP_ENV` config bug found during the 2026-07-01 audit).

- **To update production secrets:** maintain a local `.env.production` (gitignored,
  real values, never committed), then run:
  `php artisan env:encrypt --env=production --key=<key>`
  This produces `.env.production.encrypted` — ciphertext, safe to commit. Commit
  and push it like any other file.
- **The encryption key** lives only in a password manager and, on the server, in
  `~/.secrets/ttc-env-key` (outside `public_html`, outside git, `chmod 600`).
  Never write it into any file under the repo.
- **Gotcha (hit 2026-07-02):** `env:encrypt`'s printed key includes a literal
  `base64:` prefix, e.g. `base64:Dh2DVgt...qo=` — that prefix is part of the
  key, not a label. Store the FULL string, prefix included, in both the
  password manager and `~/.secrets/ttc-env-key`. Stripping it produces a raw
  string of the wrong byte length and `env:decrypt` fails with "Unsupported
  cipher or incorrect key length".
- **On the server**, `deploy.sh` runs
  `php artisan env:decrypt --env=production --key="$(cat ~/.secrets/ttc-env-key)" --filename=.env --force`
  before migrations/caching, regenerating `.env` from the encrypted file every deploy.
- `setup.sh` does the same on first-time provisioning if `~/.secrets/ttc-env-key`
  already exists; otherwise it falls back to the old manual `nano .env` flow.
- **If the key ever leaks** (accidental commit, exposed log), rotate everything in
  the file, not just the key — it's symmetric encryption (Stripe keys, Etsy OAuth
  secret, IMAP password, `APP_KEY`, etc.).
- Hostinger's auto-deploy still does NOT run `deploy.sh` itself. Previously this
  meant a manual `ssh … && bash deploy.sh` after every push; **as of 2026-07-04
  `ship.sh` automates it** (see "AI-driven deploy" above). The manual fallback
  remains valid: `ssh -p 65002 u903552178@5.183.10.138` then
  `cd ~/domains/timbertracecrafts.com/public_html && bash deploy.sh`.

## Local ↔ Hostinger DB sync (`db:export-hostinger` / `db:import-hostinger`)

Lessons from a long 2026-07-03 session — read before touching prod data.

- **Remote MySQL works** from a dev machine: host `195.35.61.20:3306` (panel
  hostname `srv2141.hstgr.io`), db `u903552178_ttc`, user
  `u903552178_ttc_admin`, Remote-MySQL access host `%` (hPanel → Databases →
  Remote MySQL). It was never actually broken.
- **CRLF gotcha (cost hours):** `.env.production` has **CRLF** line endings.
  Extracting a value in shell with `cut`/`sed` keeps a trailing `\r`, so
  `DB_PASSWORD` becomes `password\r` and every connection fails with
  `SQLSTATE[HY000] [1045] Access denied ... (using password: YES)` — which looks
  exactly like a wrong password or a missing grant but is neither. Laravel's
  dotenv strips `\r`, so the app/prod is unaffected; only manual shell
  extraction hits this. **Always pipe env values through `tr -d '\r'`** (and
  strip surrounding quotes): `... | tr -d '\r' | sed -E 's/^"(.*)"$/\1/'`. This
  single bug produced a false "missing `user@%` grant / Hostinger platform
  limitation" diagnosis AND an unnecessary prod DB-password reset. Avoid both.
- **`db:export-hostinger` is DESTRUCTIVE** — it TRUNCATEs then reinserts each
  target table (auto-backs-up to `storage/app/backups/hostinger-<ts>/` first).
  ALWAYS scope with `--tables=` (repeatable); non-interactive needs `--force`.
  NEVER blind-push the accumulator tables that live only on prod: `orders`,
  `order_items`, `order_status_history`, `shipments`, `users`, `addresses`,
  `contact_submissions`, `product_reviews`, `wishlists`. Catalog-only pushes
  (`products`, `product_variants`, sometimes `settings`) are the normal case.
- **Verify syncs by CONTENT, not hash-equality.** A local↔prod hash match only
  proves the two sides *agree* — not that they hold the *intended* values. On
  2026-07-03 a mid-session revert of the LOCAL db (cause never pinned down)
  meant a "verified" export pushed OLD data to prod and both matched as old =
  false success. When confirming a push, assert the actual expected strings
  (e.g. the specific product titles) and/or check against the true source (Etsy
  live inventory) — not just `source == destination`.
