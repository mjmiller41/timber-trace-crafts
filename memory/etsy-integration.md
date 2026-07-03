---
name: etsy-integration
description: Etsy OAuth + webhook + product push + order sync status — what works, what's pending
metadata:
  type: project
---

## Status: Mostly Working

### Done
- **OAuth 2.0 + PKCE** — connect/disconnect flow, token refresh, shop ID stored in settings
- **Webhook** — live on production; HMAC-SHA256 verification; handles `order.paid`, `order.canceled`, `order.shipped`, `order.delivered`
- **Admin email on order.paid** — `EtsyNewOrderMail` sent to admin on new Etsy order
- **Review sync** — `php artisan etsy:sync-reviews` pulls Etsy reviews into local DB
- **Order sync** — `php artisan etsy:sync-orders` imports Etsy receipts as orders
- **etsy:link** — maps local products to Etsy listing IDs, copies metadata (taxonomy, shipping profile)
- **Individual product push** — "↑ Update on Etsy" / "↑ Create on Etsy" button on product edit page; PATCH for existing, POST for new
- **etsy:export** — dumps all shop data to JSON; saved in `.claude/etsy_data/`

### Key Settings (stored in DB via Setting model)
- `etsy.shop_id` = 64205843
- `etsy.readiness_state_id` — NOT set globally (was null in local DB, caused the bulk-push 400s); now set per-product via `etsy_readiness_state_id`, copied by `etsy:link`
- Readiness states: 1488822641920 = made_to_order 1-3 days · 1478211423469 = ready_to_ship 1-2 days. Heart box is made_to_order; tumbler + all earrings are ready_to_ship
- `etsy.shipping_profile_id` = 303514857493 (copied from earrings listing)
- `etsy.taxonomy_id` — not set globally; set per-product via `etsy_taxonomy_id` column

### Taxonomy IDs (common)
- Dangle & Drop Earrings: **1208**
- Tumblers & Water Glasses: **1071**
- Jewelry Boxes (under Jewelry): **6102**
- Jewelry Boxes (under Storage): **6105**

### Active Etsy Listings (production, all linked to local products 2026-07-03)
- 4517004325 — America 250 Tumbler (product #1), $25
- 4511088718 — Personalized Heart Jewelry Box (product #2), $40
- 4507368334 — Butterfly Earrings Design 3 (product #5, SKUs -03)
- 4507325946 — Butterfly Earrings Design 2 (product #4, SKUs -02)
- 4506611612 — Butterfly Earrings Design 1 (product #3, SKUs -01)
- 4505102326 — Teardrop Earrings (product #6)

### Working as of 2026-07-03
- **Bulk product push** — `etsy:sync-products` 6/6 OK. Root cause of old 7/7 failure: products weren't linked (tried to create duplicates) + missing readiness_state_id on inventory offerings
- **Inventory sync** — `etsy:sync-inventory` 6/6 OK; pushes LOCAL price/stock to live, so keep local aligned before running
- **Image upload** — `etsy:sync-images` + auto-upload when push creates a listing; tracked via `product_media.etsy_listing_image_id`; skips listings with manually-uploaded images unless `--force` (would append/duplicate)

### OAuth token is single-owner across environments (IMPORTANT — learned 2026-07-03)
Local and prod share ONE Etsy connection (same shop). Etsy **rotates the refresh token on every use**, so whenever one environment refreshes or reconnects, the OTHER environment's token is immediately invalidated. Symptom: `No Etsy refresh token` / `invalid_grant`.
- **Prod must own the live connection.** Etsy webhooks (`order.paid`, etc.) can only reach the public site, so treat PROD as the token owner and reconnect Etsy in the **prod** admin.
- **Do NOT run token-refreshing Etsy commands locally** during normal ops. If you must do local Etsy work (e.g. re-linking, pulling live inventory), it WILL break prod's token — reconnect prod afterward.
- Sync direction for catalog/stock: pull FROM Etsy on whichever env has a live token, write local, then push local→prod via `db:export-hostinger` (see project_infra DB-sync notes). Etsy is the source of truth for titles, descriptions, and stock.

### Pending / Known Gaps
- **Etsy tags / materials / shop_section_id** — included in push payload only if set on product (copied by etsy:link)
- **New product creation** — requires `etsy_taxonomy_id`, `etsy_shipping_profile_id`, `etsy_readiness_state_id` on product first (all copied by etsy:link)
- **Product names/descriptions** — DECIDED (2026-07-03): Etsy is canonical for name + description; DB verified matching all 6 live listings byte-for-byte. Butterfly products #3/#4/#5 intentionally share the identical Etsy title (distinguished by SKU -01/-02/-03; slugs suffixed -2/-3). Etsy API returns text HTML-encoded — etsy:link/etsy:diff decode it
- **Tumbler price** — local was $20, live Etsy $25; local set to $25 (2026-07-03) to match live. If $20 was intentional for the own-store price, revisit

### API Notes
- `x-api-key` header format: `keystring:shared_secret`
- All write requests (POST/PATCH/PUT) must use `application/x-www-form-urlencoded` (`.asForm()`)
- Update listing: `PATCH /v3/application/shops/{shop_id}/listings/{listing_id}` (NOT PUT, NOT unscoped)
- Create listing: `POST /v3/application/shops/{shop_id}/listings` — requires `taxonomy_id` and `readiness_state_id`
- Webhook payload: only contains `event_type` + `resource_url`; must fetch full receipt from resource_url

**Why:** How to apply: reference when touching any Etsy service class or adding new Etsy endpoints.
