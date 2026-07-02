# Audit Findings 2026-07-01 — Remediation Plan

**Source:** `TASKS.md` → section "## Audit findings — 2026-07-01" (30 items, numbered 2–32).
**Branch/worktree:** `claude/goofy-clarke-822732`, caught up to `main` @ `3bac1c0`.
**Out of scope here:** #1 (checkout capture-then-fail; feeds #28, tracked separately) and #16 (media R2 `put()` failure — merged into the Active "Investigate prod R2 `put()` failure" task).

## Context

A code audit dated 2026-07-01 surfaced 30 security and correctness defects across five subsystems. Several are launch-blocking: the storefront purchase path can be manipulated to pay the wrong price or ship free, and the Etsy sync can silently drop orders or brick its own connection. The findings carry precise `file:line` references and one (finding #2 in `CartController.php`) was spot-verified as accurate before planning, so the audit's locations are treated as trustworthy.

Goal: close every finding with a minimal, convention-matching fix and a test that locks in the corrected behavior, sequenced so the highest-risk purchase-path and Etsy defects land first. Each fix reuses existing patterns (`throttle:` middleware, `Product::currentPrice()`/variant price precedence, `SyncResult`, observer `quiet` saves, signed routes) rather than introducing new abstractions.

## Task Checklist

### Phase 1 — Storefront purchase path (highest risk; launch-blocking)
- [x] #2 [High] Reject cart variant that doesn't belong to the product (`CartController@add`) *(done 2026-07-02)*
- [x] #10 [Med] Block draft/archived products and disabled variants from the purchase path *(done 2026-07-02)*
- [x] #9 [Med] Revalidate cart line prices at checkout instead of trusting the add-time snapshot *(done 2026-07-02)*
- [x] #7 [Med] Reject inactive shipping method id instead of defaulting to free shipping *(done 2026-07-02)*
- [x] #8 [Med] Fix inert guest-email validation in checkout *(done 2026-07-02)*
- [x] #28 [Low] Sort cart lines by `variant_id` before locking to avoid deadlock *(done 2026-07-02)*
- [x] #22 [Low] Clamp percent coupons to `max:100` + clamp discount to subtotal *(done 2026-07-02)*
- [x] #23 [Low] Uppercase coupon code on save so lookups match *(done 2026-07-02)*
- [x] #29 [Low] Document/normalize tax `rate_percent` semantics + uppercase state list *(done 2026-07-02)*

### Phase 2 — Etsy sync robustness (needs queue worker)
- [x] #3 [High] Make Etsy order import transactional + retry-idempotent *(done 2026-07-02)*
- [x] #4 [High] Only advance `orders_last_synced_at` on a successful fetch *(done 2026-07-02)*
- [x] #5 [High] Lock OAuth token refresh to prevent concurrent-refresh brick *(done 2026-07-02)*
- [x] #11 [Med] Use quiet saves in `EtsyProductSync` + add `ShouldBeUnique` to `SyncProductToEtsy` *(done 2026-07-02)*
- [x] #12 [Med] Add 429/5xx retry to `EtsyClient`, prevent overlapping scheduled syncs, set job timeouts *(done 2026-07-02)*
- [x] #13 [Med] Stop swallowing Etsy webhook status events (queue them; NACK on failure) *(done 2026-07-02)*
- [x] #14 [Med] Log + fail loudly when `ImportEtsyOrder` gets an empty receipt payload *(done 2026-07-02)*
- [x] #30 [Low] Populate `etsy_transaction_id` on imported order items *(done 2026-07-02)*

### Phase 3 — Auth, session & security headers
- [x] #6 [Med] Add rate limits to coupon/forgot/reset/contact/register/newsletter/restock POSTs *(done 2026-07-02)*
- [x] #20 [Med] Set `SESSION_SECURE_COOKIE=true` in production config/env *(done 2026-07-02 — defaults true when APP_ENV=production, self-healing even if the env var is never set on the server)*
- [x] #21 [Med] Add tests for account-order ownership + admin `updateStatus`/`addShipment` *(done 2026-07-02)*
- [x] #24 [Low] Require `current_password` when the account email changes *(done 2026-07-02)*
- [x] #25 [Low] Regenerate session after registration login *(done 2026-07-02)*
- [x] #26 [Low] Expire order-tracking signed links (90-day temporary signed routes) *(done 2026-07-02)*
- [x] #27 [Low] Add a Content-Security-Policy header (report-only to start) *(done 2026-07-02)*

### Phase 4 — Admin inbox / mail
- [x] #15 [Med] Harden `ImapService` against malformed `Date:`/charset so one bad email can't brick the inbox *(done 2026-07-02)*

### Phase 5 — Ops, infra & config hygiene
- [x] #17 [Med] `db:import-hostinger`: wrap in transaction + backup, exclude remote `jobs` table *(done 2026-07-02)*
- [x] #18 [Med] Move prod DB host/name/user out of VCS defaults into env *(done 2026-07-02)*
- [x] #19 [Med] Fix deploy/setup build step so `npm run build` has its dev tooling *(done 2026-07-02 — removed the broken build step entirely; assets ship via the pre-push hook, Node isn't on the server)*
- [x] #31 [Low] Harden media console commands (webp dupes, delete-before-update, dead-URL abort) *(done 2026-07-02)*
- [x] #32 [Low] Repo/config hygiene: dedupe skills dir, drop unused sanctum, fix `.mcp.json` path, refresh `.env.production` *(partial 2026-07-02 — symlinked the two duplicated skill dirs, fixed `.mcp.json`'s stale path. NOT done: removing `laravel/sanctum` is a dependency change requiring approval per project guidelines; `.env.production` lives only on the live server, unreachable from this repo — both flagged for the operator.)*

---

## Full Plan

Conventions for every fix: run `vendor/bin/pint --dirty --format agent` after PHP edits; add/extend a PHPUnit **feature** test (unit where it's pure logic) and run it with `php artisan test --compact --filter=…`; commit per subsystem group, not per file. Tests run on sqlite `:memory:` with `sync` queue — no external services needed.

### Phase 1 — Storefront purchase path

**#2 [High] Cross-product variant price manipulation** — `app/Http/Controllers/CartController.php:27-35,55`.
Fix: after loading the variant, assert `$variant->product_id === $product->id`; abort/redirect back with an error otherwise. Do it before pricing at line 55. Test: `CartTest` — POST product A + variant B → 422/redirect with error, cart unchanged.

**#10 [Med] Draft/archived products & disabled variants purchasable** — same controller, add path.
Fix: reject when `$product->status` is not the purchasable state or `$variant->is_enabled` is false. Reuse whatever storefront listing uses for `status`. Test: disabled variant and non-active product each rejected.

**#9 [Med] Cart price snapshot never revalidated** — `CartController.php:55`, `CheckoutController.php:126`.
Fix: at checkout, recompute each line's authoritative price (`$variant->price ?? $product->currentPrice()`) and reject/repri­ce if the snapshot diverges. Keep `price_snapshot` for display but never charge from it. Test: change a product price after add-to-cart → checkout uses the new price.

**#7 [Med] Inactive shipping method → free shipping** — `app/Http/Controllers/CheckoutController.php:62-65,120-123`.
Fix: replace bare `exists:` with a lookup constrained to active methods; when null, fail validation instead of falling through to `$shippingAmount = 0.0`. Test: POST an inactive shipping method id → rejected, not zero-rated.

**#8 [Med] Guest email validation inert** — `CheckoutController.php:89,134`.
Fix: correct the `required_if` to reference the real auth state (guest = unauthenticated), guard the array key to avoid the post-capture 500. Test: guest checkout with empty/omitted email → validation error before payment.

**#28 [Low] Checkout lock-ordering deadlock** — `CheckoutController.php:165-179`.
Fix: sort cart lines by `variant_id` before the locking loop so concurrent checkouts acquire locks in a consistent order. Test: unit-order the lines and assert deterministic lock sequence.

**#22 [Low] Percent coupon >100 → negative total** — `app/Http/Controllers/Admin/CouponController.php:30,52`.
Fix: `max:100` validation for `percent` type; clamp computed discount to subtotal in checkout. Test: admin can't save 150% percent coupon; a 100% coupon floors total at 0.

**#23 [Low] Coupon casing mismatch** — `CartController.php:89` uppercases lookups; admin saves as typed.
Fix: uppercase `code` on save in `CouponController`. Test: mixed-case admin code matches an uppercased apply.

**#29 [Low] Tax config footguns** — `app/Services/TaxService.php:12-26`.
Fix: document that `rate_percent` stores a fraction (or rename accessor), and uppercase/normalize the configured state list so `'fl'` matches. Test: `TaxServiceTest` — lowercase state still taxes; fraction interpreted correctly.

### Phase 2 — Etsy sync robustness

**#3 [High] Order import not transactional / retry not idempotent** — `app/Services/Etsy/EtsyOrderSync.php:90-129`, `app/Jobs/ImportEtsyOrder.php:25`.
Fix: wrap order + items in `DB::transaction`; make import idempotent via `updateOrCreate` on `etsy_receipt_id` so a retry after partial failure heals instead of tripping the unique index. Test: simulate mid-loop failure → no itemless order; re-run → single complete order.

**#4 [High] Watermark advances on failure** — `EtsyOrderSync.php:69`.
Fix: only set `orders_last_synced_at = now()` after a successful receipts fetch/import; on failure, leave it so the window retries. Test: failed fetch leaves watermark unchanged.

**#5 [High] Unlocked OAuth refresh race** — `app/Services/Etsy/EtsyOAuthService.php:80-116`.
Fix: guard the refresh with `Cache::lock` (atomic); re-read the stored token inside the lock so a concurrent caller uses the freshly rotated token. Test: two concurrent refresh calls → one network refresh, both end with the valid token.

**#11 [Med] Observer echo + no uniqueness guard** — `app/Services/Etsy/EtsyProductSync.php:31-44`, `app/Observers/ProductObserver.php:22`, `app/Jobs/SyncProductToEtsy.php`.
Fix: use `saveQuietly()`/`updateQuietly()` (or `Model::withoutEvents`) in the sync writes so importing doesn't re-queue a push; add `ShouldBeUnique` to `SyncProductToEtsy`. Test: import a listing → no `SyncProductToEtsy` queued; unique key set correctly.

**#12 [Med] No 429/5xx retry; overlapping syncs; no job timeouts** — `app/Services/Etsy/EtsyClient.php:45-57`, `routes/console.php:11-13`.
Fix: add retry with backoff on 429/5xx in `EtsyClient`; add `->withoutOverlapping()` to the scheduled sync commands; set `$timeout` on the Etsy jobs. Test: mocked 429-then-200 succeeds; assert schedule uses withoutOverlapping.

**#13 [Med] Webhook status events swallowed** — `app/Http/Controllers/EtsyWebhookController.php:39-47,125-159`.
Fix: queue `order.canceled/shipped/delivered` handling like `order.paid`, and return non-2xx on genuine failure so Etsy redelivers (keep 200 only for verified-and-accepted). Test: handler failure → non-2xx response; success path queues a job.

**#14 [Med] Silent success on empty receipt payload** — `EtsyOrderSync.php:74-84`.
Fix: treat empty/`null` body as an error — log it and don't mark the receipt imported. Test: empty-body fetch logs and does not create/complete an order.

**#30 [Low] Missing `etsy_transaction_id` on items** — `EtsyOrderSync.php:121-128`.
Fix: populate from `$transaction['transaction_id']` so the dedup unique index is live. Test: imported item carries the transaction id.

### Phase 3 — Auth, session & security headers

**#6 [Med] Missing rate limits on public POSTs** — `routes/web.php:51,82,95,97` + `/register`, `/newsletter`, `/restock-request`.
Fix: add `throttle:` to each per existing patterns (login `5,1`, checkout `60,1`). Suggested: coupon `10,1`, forgot/reset `6,1`, contact `5,1`, register `5,1`, newsletter/restock `10,1`. Test: exceeding the limit returns 429.

**#20 [Med] Session cookie missing `Secure`** — `config/session.php:172`.
Fix: set `SESSION_SECURE_COOKIE=true` in `.env.production` (and document in `.env.example`). Verify prod config resolves true.

**#21 [Med] No tests for order ownership** — `app/Http/Controllers/AccountController.php:32-42`; admin `updateStatus`/`addShipment`.
Fix: add `AccountTest` cases — a user can't view another user's order (403/404); cover admin status/shipment endpoints. Test: the new cases pass.

**#24 [Low] Email change without re-confirmation** — `AccountController.php:153-175`.
Fix: require `current_password` (validated with the `current_password` rule) when `email` changes. Test: email change without correct password rejected.

**#25 [Low] Session not regenerated on registration** — `app/Http/Controllers/AuthController.php:67`.
Fix: call `$request->session()->regenerate()` after `Auth::login()`, mirroring login at `:32`. Test: session id changes across register.

**#26 [Low] Order-tracking signed links never expire** — `resources/views/emails/order-confirmation.blade.php:95` (+ shipped/status emails).
Fix: generate links with `URL::temporarySignedRoute(..., now()->addDays(90))`; ensure the route uses `signed`/valid-signature middleware. Test: expired link rejected, fresh link accepted.

**#27 [Low] No CSP header** — `app/Http/Middleware/SecurityHeaders.php:15-22`.
Fix: add a `Content-Security-Policy-Report-Only` header allowing `js.stripe.com` and the R2 host; tighten to enforcing later. Test: response carries the CSP header.

### Phase 4 — Admin inbox / mail

**#15 [Med] One malformed email bricks the inbox** — `app/Services/ImapService.php:63-71,168-171`.
Fix: wrap `Carbon::parse` on the `Date:` header and `mb_convert_encoding` in guards (try/catch + charset fallback) so a single bad message degrades gracefully and stays deletable. Test: a message with a garbage date/unknown charset still lists and can be removed.

### Phase 5 — Ops, infra & config hygiene

**#17 [Med] `db:import-hostinger` destructive** — `app/Console/Commands/ImportFromHostinger.php:47,126-133`.
Fix: take a local backup + wrap the import in a transaction; exclude the remote `jobs` table so it can't clobber the live queue. Test: command unit — jobs table skipped; abort path restores.

**#18 [Med] Prod DB creds in VCS** — `ExportToHostinger.php:14-17`, `ImportFromHostinger.php:12-15`, `memory/project_infra.md:11-12`.
Fix: move host/name/user to env/config defaults (no hardcoded prod values in command signatures); scrub the committed endpoint. Verify commands still resolve from env.

**#19 [Med] Deploy/setup build step can't work** — `deploy.sh:19-20`, `setup.sh:43-44`.
Fix: since `npm ci --omit=dev` strips vite/tailwind, either drop `--omit=dev` for the build stage or rely solely on the pre-push hook's committed `public/build/` and remove the failing build line. Verify `deploy.sh` runs to completion in a dry run.

**#31 [Low] Media console command gaps** — `SyncMediaFromStorage.php:24`, `media:migrate-products`, `media:backfill-webp`.
Fix: skip `.webp` siblings when syncing (no dup records); update DB before deleting source in migrate; make backfill continue past a dead URL instead of aborting. Test: sync doesn't create webp dupes.

**#32 [Low] Repo/config hygiene.**
Fix: dedupe `.agents/skills/` vs `.claude/skills/` (keep one, symlink the other); remove unused `laravel/sanctum` (confirm no usage first); repoint `.mcp.json` at the real artisan path; refresh `.env.production` (correct `LOG_LEVEL`, `FILESYSTEM_DISK=r2`, current `AWS_*`/`ETSY_*`/`IMAP_*`/`STRIPE_WEBHOOK_SECRET`). Verify Boost MCP server starts and the suite still passes.

## Verification (end-to-end)

1. Per fix: `php artisan test --compact --filter=<TestName>`.
2. Per phase: run the subsystem's test files (e.g. `tests/Feature/CartTest.php`, `CheckoutTest.php`, Etsy feature tests, `AccountTest.php`, `TaxServiceTest.php`).
3. Before finalizing: `vendor/bin/pint --dirty --format agent`, then full suite `php artisan test --compact` (ask before running the entire suite per project convention).
4. Mirror each completed item back to `TASKS.md` (check its box + `(done 2026-07-02)`), and check it off in this plan file.
5. Do not hand-edit `public/build/`; the pre-push hook rebuilds assets.
