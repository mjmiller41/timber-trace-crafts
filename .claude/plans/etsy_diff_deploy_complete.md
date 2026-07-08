**Completed:** 2026-07-08

# Deploy Plan — Etsy Product Diff (feature/etsy-product-diff → prod)

## Task Checklist

- [x] Run full test suite on the feature branch (`php artisan test --compact`, ~225 tests) — must be green
- [x] Merge to main: `git checkout main && git merge --no-ff feature/etsy-product-diff && git branch -d feature/etsy-product-diff`
- [x] Confirm the batch is ONLY this feature: `git log --oneline origin/main..main` (expect merge commit + f1c5936, nothing else)
- [x] Verify prod env is in sync before shipping (false-drift gotcha): `php artisan env:decrypt --env=production --filename=.env.production.check`, diff CRLF/order-normalized vs `.env.production`, delete the check file
- [x] Get explicit go for the deploy (ship.sh runs `migrate --force` on the LIVE DB — a no-op this time, zero migrations, but authorization is per-deploy)
- [x] Deploy: `./ship.sh` from repo root on `main`
- [x] Verify: server HEAD == pushed full SHA; smoke test 200; `php artisan migrate:status` over SSH shows no pending
- [x] Functional check on prod: /admin/etsy loads, "Run Diff" card present, run one diff and eyeball the report (do NOT click Apply as a "test" — resolutions are real prod + Etsy writes)
- [x] Tag the release: `git tag -a vX.Y.Z -m "Etsy product diff card" && git push origin --tags`
- [x] Mark this plan complete

---

## Context

Ship commit `f1c5936` (Etsy product diff card with per-field conflict resolution) to production on Hostinger. Code-only change: no migrations, no new env vars, no queue-worker dependency (diff runs inline), no frontend build changes required (view is inline-styled Blade — though ship.sh runs `npm run build` regardless; a no-diff build commits nothing).

## Pipeline facts (from docs/DEPLOYMENT-HANDOFF.md / deployment memory)

- **One command**: `./ship.sh` on `main` — encrypt-on-drift → `npm run build` + commit `public/build/` → push (`TTC_SHIP=1` stands down the pre-push hook) → poll Hostinger `public_html` HEAD until it equals the pushed SHA (480s) → SSH `bash deploy.sh` (composer, env:decrypt, `migrate --force`, cache warms) → curl smoke test.
- **Batch release**: ship.sh deploys ALL of `origin/main..main`. Verified today: backlog is empty, so this deploys exactly this feature.
- **False drift is expected**: ship.sh always prints "Production env drifted — re-encrypting". Benign *if* local `.env.production` matches the committed ciphertext — hence the pre-ship sync check above. If the diff shows real differences, STOP: that deploy would change prod secrets.
- **Shared worktree**: ship.sh requires `main` and pushes the shared tree — don't run while any sibling agent is mid-flight on another branch.
- If ship.sh's poll times out, re-run remote steps with a FULL 40-char SHA: `./ship.sh --remote-only $(git rev-parse origin/main)`.

## Risk assessment

| Risk | Level | Why |
|---|---|---|
| DB migrations | None | Feature adds no migrations; `migrate --force` is a no-op |
| Secrets | Low | No env changes; re-encrypt is ciphertext-only if sync check passes |
| Runtime | Low | New routes are admin-gated POSTs; existing pages untouched except additive Blade on /admin/etsy |
| Etsy API | Low | Diff is read-only against Etsy; writes only happen on explicit "Apply Selected" |

## Rollback

Code-only, so rollback = redeploy previous main: `git revert -m 1 <merge-sha>` on main, then `./ship.sh` again (or reset to the previous tag). No schema to unwind.

## Post-deploy functional verification (prod)

1. `/admin/etsy` → 200, "Product Diff" card renders.
2. Click **Run Diff** — expect a results card with conflict/Etsy-only/website-only/matched counts and "Last run: …" on the card.
3. Sanity-check a couple of reported conflicts against Etsy's seller dashboard.
4. Leave **Apply Selected** alone unless resolving real drift intentionally — every apply writes prod DB and/or pushes to the live Etsy shop.
