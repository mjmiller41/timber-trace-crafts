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
