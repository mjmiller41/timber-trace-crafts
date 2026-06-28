# Timber Trace Crafts — Master Todo

> Maintained by the `/todo` skill — single source of truth for project tasks. Grouped by category, sorted by priority; completed sections are archived at the bottom.

---

## Image & Media System

### Shared media picker

- [x] **[High]** Build reusable `<x-admin.media-picker>` modal wrapping the `mediaPickerModal` Alpine component — tabbed UI to **upload new** OR **browse/search the existing library**; emits selected media via the `media-picker:picked:{channel}` event. Reused by product form and journal featured-image field. Backed by a JSON branch on `admin.media.index`.

### Product media (replaces the Phase 2 placeholder)

- [x] **[High]** Product form media section — `resources/views/admin/products/form.blade.php`. Pick/upload images, set primary, reorder (via `productMediaManager`), per-image alt text. Removed the "coming in Phase 2" placeholder card.
- [x] **[High]** Per-variant image assignment using `product_media.variant_id` — assign specific images to specific variants in the product form.
- [x] **[High]** Persist product media in `ProductController@store/@update` — attach/detach `product_media` rows with `sort_order`, `is_primary`, `variant_id`, `alt_text`; validated, with one primary enforced.

### Storage pipeline (R2 + WebP)

- [x] **[High]** Push uploads to the `r2` disk on store (MediaController + JournalController upload paths) instead of the local `public` disk; record the correct `disk` on Media. (Uploads route through `MediaUploader` to the configured `FILESYSTEM_DISK`, which is `r2` in prod.)
- [x] **[High]** Generate WebP variants on upload; ensure `Media::url()` + the `primary_image_url`/`<picture>` webp derivation are consistent end-to-end. (`MediaUploader` writes a `.webp` sibling at the path the frontend `<picture>` derives.)

### Journal featured image

- [x] **[Medium]** Swap the journal featured-image direct-upload field for the shared media picker (upload OR choose from library), keeping `featured_image_id` wiring — `resources/views/admin/journal/form.blade.php`.

### Editor responsiveness

- [x] **[Medium]** Make ZenComposer fluid — replaced the hardcoded `480px` height with a `.zencomposer-shell` flex container (`clamp(22rem, 60vh, 48rem)`, min 22rem) and `height: '100%'` on the editor.
- [x] **[Low]** Harden the TUI image-editor overlay across breakpoints — added a `max-width: 640px` rule that stacks the canvas above a full-width control bar — `admin/media/index.blade.php`.

### Cohesion & tests

- [x] **[Medium]** Verify product cards, product page, wishlist, and journal all render the new media via the `primary_image_url` + `<picture>` webp pattern consistently. (Product views already consistent; brought journal show/index/tag + home cards onto the webp `<picture>` pattern and fixed broken `featured_image` guards → `featured_image_id`.)
- [x] **[Medium]** Feature tests — product media attach/detach/reorder/primary, per-variant assignment, journal picker, R2 upload + WebP variant generation. (Full suite: 112 passing.)

---

## Blog Content (plan: `.claude/plans/blog_content_strategy.md`)

### FLOW 90-Day Calendar (gift-intent first)

- [ ] **[Medium]** Week 1: "The Best Handmade Gifts for Women Who Appreciate the Details" (pillar, ~2,500 words)
- [ ] **[Medium]** Week 2: "Valentine's Day Jewelry Gift Guide" (listicle, publish by Jan 10)
- [ ] **[Medium]** Week 3: "How Wood Earrings Are Made: A Look Inside the Workshop" (how-to)
- [ ] **[Medium]** Week 4: "Personalized Jewelry Box Ideas: How to Make a Gift She'll Keep Forever" (listicle)
- [ ] **[Medium]** Week 6: "What to Get a Woman Who Has Everything: 7 Handmade Ideas" (listicle)
- [ ] **[Medium]** Week 7: "Why We Started Timber Trace Crafts" (thought-leadership / brand story)
- [ ] **[Medium]** Week 8: "Mother's Day Gifts from a Small Maker" (listicle, publish by Apr 1)
- [ ] **[Medium]** Week 10: "Handmade Wedding Party Gifts: A Complete Guide for Brides" (pillar)
- [ ] **[Medium]** Week 12: "Wood vs. Metal Earrings: Which Is Better for Sensitive Ears?" (comparison)
- [ ] **[Medium]** After each post: run `/blog analyze`, score 80+, add internal links to product pages, apply tags
- [x] **[Medium]** Create featured image for Week 1 blog post before publishing

### Month 1 Content

- [ ] **[Medium]** Write Pillar 6: "How Laser Cutting and Engraving Works" (~3,000 words)
- [ ] **[Medium]** Write Spoke 3.2: "Can you wear wood earrings in the shower?" (FAQ, ~500 words)
- [ ] **[Medium]** Write Spoke 5.2: "Are engraved tumblers dishwasher safe?" (FAQ, ~500 words)
- [ ] **[Medium]** Publish all three with featured images, tags, and SEO meta filled in

### Month 2 Content — Personalization Hub

- [ ] **[Medium]** Write Pillar 1: "Ultimate Guide to Personalized Laser-Engraved Gifts" (~3,500 words)
- [ ] **[Medium]** Write Spoke 1.1: "How laser engraving personalization works"
- [ ] **[Medium]** Write Spoke 1.2: "What can and can't be laser engraved"
- [ ] **[Medium]** Write Spoke 1.7: "Laser engraving vs. laser cutting: what's the difference?"
- [ ] **[Medium]** Set up Pinterest scheduling — pin every post with image + excerpt on publish day
- [ ] **[Medium]** Begin monthly AI citation tracking (10 target queries in ChatGPT + Perplexity)

### Month 3 Content — Gift Season + Wood Jewelry Cluster

- [ ] **[Medium]** Write Pillar 2 gift guide hub + first 2 seasonal spokes (next upcoming holiday)
- [ ] **[Medium]** Write Pillar 3: "The Complete Guide to Wood Earrings" (~3,000 words)
- [ ] **[Medium]** Write Spoke 3.3: "Are wood earrings hypoallergenic?"
- [ ] **[Medium]** Run `/blog analyze` on all published posts; update any scoring below 70
- [ ] **[Medium]** Review AI citation tracking results; adjust content angle based on what's being extracted

---

## GEO / AI Search (plan: `.claude/plans/geo_optimization.md`)

### Phase 1 — Code & Content

- [ ] **[Medium]** Submit site to Bing IndexNow for Bing Copilot indexation acceleration
- [x] **[Medium]** Write FAQ page content (10-15 Q&A entries: materials, shipping, custom orders, care)
- [x] **[Medium]** Rewrite product description openers — remove emoji/promo intro, lead with 40-60 word factual block
- [x] **[Medium]** Add question-based H2s to about-us ("What is Timber Trace Crafts?", "What materials does Timber Trace Crafts use?", etc.)
- [x] **[Medium]** Add 134-167 word "What is laser-cut woodworking?" definition block to about-us (above the fold)
- [x] **[Medium]** Add wood materials comparison table to about-us (Maple vs Baltic Birch vs Basswood)

### Phase 2 — Content Publishing

- [ ] **[Medium]** Publish 1st journal post with answer-first structure (40-60 word factual opener per H2)
- [ ] **[Medium]** Publish 2nd journal post — include at least one data table or comparison
- [ ] **[Medium]** Publish 3rd journal post — use question-based H2s throughout
- [ ] **[Medium]** Write "How laser-cut wood earrings are made" pillar post (1,500+ words, step-by-step)

### Phase 3 — Off-Site Brand Presence

- [ ] **[Medium]** Create YouTube channel "Timber Trace Crafts" — post first studio process video
- [ ] **[Medium]** Post second YouTube video (hand-finishing or inlay process)
- [ ] **[Medium]** Create LinkedIn company page for Timber Trace Crafts
- [ ] **[Medium]** Participate in r/woodworking — share a genuine behind-the-scenes post (not a link drop)
- [ ] **[Medium]** Participate in r/crafts — answer a question about laser cutting or wood finishing
- [ ] **[Medium]** Add YouTube and LinkedIn URLs to `social.*` settings in admin

### Phase 4 — Schema & Entity

- [ ] **[Medium]** Add YouTube and LinkedIn URLs to Organization `sameAs` array in `layouts/app.blade.php`
- [ ] **[Medium]** Add `jobTitle` and `description` to Person entity for Michael J. Miller in global schema
- [ ] **[Medium]** Add `SiteLinksSearchBox` schema (SearchAction) once traffic justifies it

---

## Archived

### Security — completed 2026-06-28

- [x] **[Medium]** M2: Add CSRF token rotation on privilege escalation — regenerate CSRF token after any role/permission change (e.g., admin elevation) so attacker-held pre-escalation tokens become invalid. See docs/superpowers/plans/2026-06-26-audit-fixes.md for context.

### Miscellaneous — completed 2026-06-28

- [x] **[Medium]** Add an admin dashboard button or link to the account page, visible only if the user has admin priveleges.

### Etsy Integration — completed 2026-06-28

- [x] **[Medium]** Get whsec\_ signing secret from Webhook Portal → update ETSY_WEBHOOK_SECRET in prod .env
- [x] **[Medium]** Update webhook URL in portal (remove ?secret=...)
- [x] **[Medium]** Test webhook via Etsy's Testing tab
- [x] **[Medium]** Review sync — test the button in Admin → Etsy
- [x] **[Medium]** Email notification on new order (not yet built)
- [x] **[Medium]** OAuth connect flow working via ngrok in local dev
- [x] **[Medium]** Shipping profiles — set `etsy.shipping_profile_id` and `etsy.taxonomy_id` in admin settings (needed before product push creates real listings)
- [x] **[Medium]** Product push to Etsy — add observer on Product model for publish/update/delete events to auto-sync
- [x] **[Medium]** Shipment push — hook EtsyShipmentSync into OrderController when order marked shipped (send tracking to Etsy)
- [x] **[Medium]** Production queue worker — set up cron or supervisor on Hostinger to keep `php artisan queue:work` running so auto-sync jobs are processed
- [x] **[Medium]** Add variant button on admin/products/create does nothing when clicked no visible error

### SEO — completed 2026-06-28

- [x] **[Medium]** Hero image WebP swap
- [x] **[Medium]** Title bug fix
- [x] **[Medium]** Canonical tags + noindex on private pages
- [x] **[Medium]** XML sitemap + hardened robots.txt
- [x] **[Medium]** OG / Twitter Card meta tags
- [x] **[Medium]** Structured data JSON-LD (Organization, Product, BlogPosting, etc.)
- [x] **[Medium]** Security headers middleware
- [x] **[Medium]** Async Google Fonts
- [x] **[Medium]** Newsletter 404 fix
- [x] **[Medium]** llms.txt AI fact sheet
- [x] **[Medium]** R2 cache headers — set `Cache-Control: public, max-age=31536000, immutable` on all R2 image objects (Cloudflare R2 dashboard)
- [x] **[Medium]** Update hero `<img src>` and `@push('preload')` href in `resources/views/home/index.blade.php` to point to R2 URL

### Audit findings — completed 2026-06-28

- [x] **[High]** Wishlist add/remove queries non-existent `variant_id` column — `app/Http/Controllers/AccountController.php:63`
      Fix: Use `product_variant_id` in the `firstOrCreate` attributes (line 63-66) and the `where` clause (line 73-74); the `wishlists` table and `Wishlist` model use `product_variant_id`. Both actions currently throw a SQL column-not-found 500. Add an AccountTest case for wishlist add/remove.
- [x] **[High]** Order status lookup has no rate limiting (PII exposure / ID enumeration) — `routes/web.php:64`
      Fix: Add `->middleware('throttle:5,1')` to the `order.status.lookup` route. It returns full order PII (address, items, gift message) gated only by email + sequential integer order ID, while all other sensitive endpoints are throttled.
- [x] **[Medium]** New Etsy orders never trigger admin notification (async race) — `app/Http/Controllers/EtsyWebhookController.php:104`
      Also fixed: webhook signature verification rejected real Etsy/Svix `v1,`-prefixed signatures (401). Now strips the scheme prefix before comparing.
      Fix: For new receipts the controller dispatches `ImportEtsyOrder` async then immediately re-queries the order (still null), skipping `Cache::increment('etsy.new_orders')` and `EtsyNewOrderMail`. Move the cache increment + admin email into the job after the order is persisted, and queue the mail instead of `->send()`.
- [x] **[Medium]** Variant-level price ignored when adding to cart — `app/Http/Controllers/CartController.php:53`
      Also added: the product page price now updates reactively to the selected variant (`variantSelector` + reactive price block).
      Fix: Cart stores `$product->currentPrice()` and never consults `$variant->price`, so per-variant price overrides are not charged. Use `$variant->price ?? $product->currentPrice()`. (Confirm variant pricing is intended to override.)
- [x] **[Medium]** Coupon `max_uses` can be exceeded under concurrent checkouts — `app/Models/Coupon.php:61`, `app/Http/Controllers/CheckoutController.php:236`
      Fixed (Option A): lock the coupon row + re-check inside the order transaction; honour the already-paid order and log an overshoot warning rather than rejecting. Covered by a deterministic concurrency test.
      Fix: `isValid()` reads `used_count` without a lock; the increment happens later in the order transaction. Lock the coupon row (`lockForUpdate`) and re-check `used_count < max_uses` inside the same `DB::transaction` before incrementing.
- [x] **[Low]** Guest email leaked in confirmation URL query string — `app/Http/Controllers/CheckoutController.php:254`
      Fixed: guest confirmation access is now authorised via `session('confirmed_orders')`; the `?email=` param is gone. (Note: order emails still link to `/order-status?...&email=` — separate pre-existing leak, see below.)
      Fix: Redirect passes `?email=` (lands in access logs, history, Referer). Stash email/authorization in session or sign the confirmation URL instead.
- [x] **[Low]** First registered user silently promoted to admin — `app/Http/Controllers/AuthController.php:56`
      Fixed: registration always creates a `customer`; admins are promoted explicitly via the new `php artisan app:make-admin {email}` command.
      Fix: `register()` grants `role = 'admin'` when `User::count() === 0`. If `/register` is reachable before an admin is seeded, an outsider can claim admin. Seed the admin explicitly and remove auto-promotion, or gate behind a one-time setup token.

### Audit follow-ups — completed 2026-06-28

- [x] **[Low]** Order emails leak guest email in the "Track Order" URL — `resources/views/emails/{order-confirmation,order-status-changed,order-shipped}.blade.php`
      Fixed: added a `signed` `order.status.view` route; emails now link to `URL::signedRoute(...)` with no email in the URL.
- [x] **[Perf]** Admin JS bundle is ~712 kB (Vite warns >500 kB) — `resources/js/admin.js`
      Fixed: `tui-image-editor` is now dynamically imported in `initEditor()`; the initial admin bundle dropped from ~712 kB to ~6 kB and the editor loads in its own chunk on demand.
