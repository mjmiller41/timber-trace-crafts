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
- `etsy.readiness_state_id` = 1488822641920 (made_to_order, 1-3 days)
- `etsy.shipping_profile_id` = 303514857493 (copied from earrings listing)
- `etsy.taxonomy_id` — not set globally; set per-product via `etsy_taxonomy_id` column

### Taxonomy IDs (common)
- Dangle & Drop Earrings: **1208**
- Tumblers & Water Glasses: **1071**
- Jewelry Boxes (under Jewelry): **6102**
- Jewelry Boxes (under Storage): **6105**

### Active Etsy Listings (production)
- 4517004325 — America 250 Tumbler
- 4511088718 — Personalized Heart Jewelry Box
- 4507368334 — Butterfly Earrings Design 3
- 4507325946 — Butterfly Earrings Design 2
- 4506611612 — Butterfly Earrings Design 1
- 4505102326 — Teardrop Earrings
- 4528126178 — "test" (draft, can delete)

### Pending / Known Gaps
- **Bulk product push** — `etsy:sync-products` was failing (7/7 failed); individual push now works; bulk not retested
- **Product images** — push does not upload images to Etsy; would need `uploadListingImage` endpoint
- **Inventory sync** — `etsy:sync-inventory` exists but untested after recent changes
- **Etsy tags / materials / shop_section_id** — not included in push payload yet
- **New product creation** — requires `etsy_taxonomy_id` and `etsy_shipping_profile_id` set on product first (copy from existing via tinker or etsy:link)

### API Notes
- `x-api-key` header format: `keystring:shared_secret`
- All write requests (POST/PATCH/PUT) must use `application/x-www-form-urlencoded` (`.asForm()`)
- Update listing: `PATCH /v3/application/shops/{shop_id}/listings/{listing_id}` (NOT PUT, NOT unscoped)
- Create listing: `POST /v3/application/shops/{shop_id}/listings` — requires `taxonomy_id` and `readiness_state_id`
- Webhook payload: only contains `event_type` + `resource_url`; must fetch full receipt from resource_url

**Why:** How to apply: reference when touching any Etsy service class or adding new Etsy endpoints.
