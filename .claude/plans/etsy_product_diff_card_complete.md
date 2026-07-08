# Etsy Product Diff — Admin Card with Per-Field Conflict Resolution

**Completed:** 2026-07-07

## Task Checklist

- [x] Extract listing-diff logic into `app/Services/Etsy/EtsyListingDiff.php` (expanded field set: title, description, price, quantity, tags, status) *(done 2026-07-07)*
- [x] Refactor `EtsyDiffCommand` to use the new service for listings (orders/sections stay in the command); fix the `published`→`active` status-mapping bug *(done 2026-07-07)*
- [x] Add `EtsyController::diffProducts()` — run diff, cache report 1h, redirect back *(done 2026-07-07)*
- [x] Pass cached `diffReport` from `EtsyController::index()` to the view *(done 2026-07-07)*
- [x] Add `EtsyController::resolveDiff()` — validate per-field choices, apply Etsy→DB quietly, push merged product DB→Etsy when any "keep website" chosen, prune resolved conflicts from cache *(done 2026-07-07)*
- [x] Add routes `admin.etsy.diff.products` (POST) and `admin.etsy.diff.resolve` (POST) *(done 2026-07-07)*
- [x] Add "Product Diff" card + conflict-resolution results section to `resources/views/admin/etsy/index.blade.php` *(done 2026-07-07)*
- [x] Feature tests: `tests/Feature/EtsyDiffAdminTest.php` (diff run, keep-etsy apply, keep-website push, auth guard) — 14 tests / 46 assertions passing *(done 2026-07-07)*
- [x] Verify existing `EtsyDiffCommandTest` still passes after refactor; run `vendor/bin/pint --dirty --format agent` *(done 2026-07-07)*

---

## Context

The store syncs products bidirectionally with Etsy, but data drifts (manual edits on Etsy, failed syncs). A CLI diff exists (`php artisan etsy:diff`) that reports drift, but it's terminal-only, compares only price/title/status, and offers no way to resolve differences. This feature surfaces the product diff on the **Admin → Etsy** page as a new card matching the existing sync-card format, focuses the output on conflicts, and lets the admin pick — per field — whether the website or Etsy value wins.

## Existing code to reuse

| What | Where |
|---|---|
| Diff + pagination + money logic | `app/Console/Commands/EtsyDiffCommand.php` (`diffListings`, `fetchAllListings`, `compareListingToProduct`, `money`) — extract, don't duplicate |
| DB → Etsy push | `app/Services/Etsy/EtsyProductSync::syncProduct()` (handles PATCH + inventory endpoint for price/stock + stale-listing recovery) |
| Card layout & flash messages | `resources/views/admin/etsy/index.blade.php` (`admin-card` grid, POST + `admin-btn-secondary` buttons) |
| Controller error pattern | `EtsyController::syncProducts()` — try/catch → redirect with `success`/`error` flash |
| Test patterns | `tests/Feature/EtsyAdminTest.php`, `tests/Feature/EtsyDiffCommandTest.php` (Http::fake of Etsy endpoints) |

## Design

### 1. `app/Services/Etsy/EtsyListingDiff.php` (new)

Constructor takes `EtsyClient` (same convention as other `Etsy/*` services).

- `diff(): array` — fetches all listings for the shop (move `fetchAllListings()` pagination here), keys DB products by `etsy_listing_id`, returns:
  ```php
  [
    'generated_at' => ISO string,
    'etsyOnly'  => [...],   // listings with no linked product
    'dbOnly'    => [...],   // products whose etsy_listing_id vanished from Etsy
    'conflicts' => [        // both exist, fields differ
      ['listing_id', 'product_id', 'product_name',
       'differences' => ['price' => ['db' => 24.0, 'etsy' => 26.0], ...]]
    ],
    'matched'   => int,
  ]
  ```
- Compared fields (expanded set — mirrors what `EtsyProductSync::buildListingPayload()` actually pushes):
  - **title** — `html_entity_decode` Etsy title vs `$product->name`
  - **description** — Etsy plain text vs `strip_tags($product->description ?? $product->short_description ?? '')`, whitespace-normalized before compare
  - **price** — `money($listing['price'])` vs `(float) ($product->sale_price ?? $product->price)` (the effective price the push uses)
  - **quantity** — `$listing['quantity']` vs `$product->variants->sum('stock_qty')`
  - **tags** — `$listing['tags']` vs `$product->etsy_tags ?? []`, order-insensitive compare
  - **status** — Etsy `state` vs mapped DB status: `active`→`active`, `draft`→`draft`, skip `archived`. **Note:** the CLI currently maps `published`→`active`, but the products enum is `['active','draft','archived']` — `published` never occurs. Fix during extraction.
- Eager-load `variants` on the product query to avoid N+1 on quantity compare.

Refactor `EtsyDiffCommand::diffListings()` to delegate to this service; orders/sections diffing stays in the command untouched.

### 2. Controller — `app/Http/Controllers/Admin/EtsyController.php`

- `diffProducts(): RedirectResponse` (POST) — runs `EtsyListingDiff::diff()` inline (same as the other sync buttons), `Cache::put('etsy.product_diff', $report, now()->addHour())`, redirect with success flash. Try/catch → error flash, matching `syncProducts()`.
- `index()` — add `'diffReport' => Cache::get('etsy.product_diff')` to the view data.
- `resolveDiff(Request $request): RedirectResponse` (POST) — input shape:
  ```
  resolutions[<product_id>][<field>] = 'db' | 'etsy'
  ```
  Validate: product ids exist, fields in the allowed set, values `in:db,etsy`. Per product:
  1. Look up the product's conflict entry in the cached report (source of the Etsy values — no re-fetch). If a product isn't in the cached report, skip it.
  2. Apply every `'etsy'` choice to the DB via **`updateQuietly()`** (must not re-fire `ProductObserver` → `SyncProductToEtsy`):
     - title → `name` · description → `description` · tags → `etsy_tags` · status → `status` (mapped back)
     - price → `sale_price` if currently set, else `price` (write to the effective column)
     - quantity → single-variant products only: write the variant's `stock_qty`; multi-variant products get keep-website only (UI disables the Etsy option for that field)
  3. If any field for that product chose `'db'`, push once via `EtsyProductSync::syncProduct($product)` — because Etsy-side merges were applied first, the single push converges both sides on the chosen merged state.
  4. Remove resolved conflict entries from the cached report and re-cache, so the page reflects progress without a full re-fetch.

  Redirect with a summary flash (`N field(s) resolved across M product(s)`).

### 3. Routes — `routes/web.php` (inside the existing admin etsy group, after line 262)

```php
Route::post('/etsy/diff/products', [EtsyController::class, 'diffProducts'])->name('etsy.diff.products');
Route::post('/etsy/diff/resolve', [EtsyController::class, 'resolveDiff'])->name('etsy.diff.resolve');
```

### 4. View — `resources/views/admin/etsy/index.blade.php`

- **New card** in the existing grid (same markup pattern as Products/Inventory cards): title "Product Diff", blurb "Compare Etsy listing data against website products and resolve differences.", POST form to `admin.etsy.diff.products`, button "Run Diff". If a cached report exists, show "Last run: {generated_at diffForHumans}" like the Orders card does.
- **Results section** below the grid, rendered only when `$diffReport` is set:
  - Summary line: `X conflicts · Y Etsy-only · Z website-only · N matched`.
  - **Conflicts (the focus)**: one `<form>` POSTing to `admin.etsy.diff.resolve`. Per conflicting product, a block showing the product name (linked to `admin.products.edit`) and per differing field a row: field name, website value, Etsy value, and two radios (`Keep website` / `Keep Etsy`) named `resolutions[{product_id}][{field}]`. Long values (description) truncated with `Str::limit`. Quantity on multi-variant products: Etsy radio disabled with a hint. Single "Apply selected" submit button at the bottom.
  - **Etsy-only / website-only**: read-only collapsible lists (informational — importing/removing listings is already covered by the existing sync buttons; out of scope here).
- Inline styles, no new CSS/JS build needed — matches the page's current style-attribute approach, so no `npm run build` required.

### 5. Tests — `tests/Feature/EtsyDiffAdminTest.php` (new)

Use `Http::fake` for `api.etsy.com` endpoints and the connected-state setup from `EtsyAdminTest` / `EtsyDiffCommandTest`:

- `admin_can_run_a_product_diff` — fake one listing that differs in price+title from a factory product; POST diff route; assert cache populated and page renders both values.
- `resolve_keep_etsy_updates_the_product_quietly` — seed cached report; POST resolve with `etsy` choices; assert DB updated (`name`, `price`) and **no** outbound Etsy HTTP call was made.
- `resolve_keep_website_pushes_to_etsy` — POST resolve with a `db` choice; assert a PATCH to the listing endpoint was sent (Http::fake assertion).
- `mixed_resolution_merges_then_pushes_once` — one product, `etsy` for title + `db` for price; assert DB name updated and exactly one push.
- `non_admin_cannot_access_diff_routes` — 403.
- Re-run `tests/Feature/EtsyDiffCommandTest.php` to confirm the command refactor is behavior-neutral.

## Out of scope

- Importing Etsy-only listings / deleting DB-only products from the diff UI (existing sync buttons cover creation; deletion is destructive and stays manual).
- Diffing orders/sections in the UI (command keeps that).
- Queued/background diff runs — inline like every other button on this page.
- Image diffing.

## Verification

1. `php artisan test --compact tests/Feature/EtsyDiffAdminTest.php tests/Feature/EtsyDiffCommandTest.php tests/Feature/EtsyAdminTest.php`
2. `vendor/bin/pint --dirty --format agent`
3. Manual: local stack (`composer run dev`), visit `/admin/etsy`, run diff against the connected shop, resolve one field each direction, re-run diff and confirm it reports matched. **Caution:** local artisan/web hits the PROD MySQL DB — treat manual resolution clicks as production writes; prefer verifying via the test suite first.
