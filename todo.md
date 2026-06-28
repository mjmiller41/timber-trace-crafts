# Timber Trace Crafts — Master Todo

> Single source of truth for all project tasks. Add every new task here AND on any domain-specific checklist.

---

## Security

- [x] M2: Add CSRF token rotation on privilege escalation — regenerate CSRF token after any role/permission change (e.g., admin elevation) so attacker-held pre-escalation tokens become invalid. See docs/superpowers/plans/2026-06-26-audit-fixes.md for context.

## Miscellaneous

- [x] Add an admin dashboard button or link to the account page, visible only if the user has admin priveleges.

## Etsy Integration

- [x] Get whsec\_ signing secret from Webhook Portal → update ETSY_WEBHOOK_SECRET in prod .env
- [x] Update webhook URL in portal (remove ?secret=...)
- [x] Test webhook via Etsy's Testing tab
- [x] Review sync — test the button in Admin → Etsy
- [x] Email notification on new order (not yet built)
- [x] OAuth connect flow working via ngrok in local dev
- [x] Shipping profiles — set `etsy.shipping_profile_id` and `etsy.taxonomy_id` in admin settings (needed before product push creates real listings)
- [x] Product push to Etsy — add observer on Product model for publish/update/delete events to auto-sync
- [x] Shipment push — hook EtsyShipmentSync into OrderController when order marked shipped (send tracking to Etsy)
- [x] Production queue worker — set up cron or supervisor on Hostinger to keep `php artisan queue:work` running so auto-sync jobs are processed
- [x] Add variant button on admin/products/create does nothing when clicked no visible error

---

## SEO

- [x] Hero image WebP swap
- [x] Title bug fix
- [x] Canonical tags + noindex on private pages
- [x] XML sitemap + hardened robots.txt
- [x] OG / Twitter Card meta tags
- [x] Structured data JSON-LD (Organization, Product, BlogPosting, etc.)
- [x] Security headers middleware
- [x] Async Google Fonts
- [x] Newsletter 404 fix
- [x] llms.txt AI fact sheet
- [x] R2 cache headers — set `Cache-Control: public, max-age=31536000, immutable` on all R2 image objects (Cloudflare R2 dashboard)
- [x] Update hero `<img src>` and `@push('preload')` href in `resources/views/home/index.blade.php` to point to R2 URL

---

## GEO / AI Search (plan: `.claude/plans/geo_optimization.md`)

### Phase 1 — Code & Content

- [x] Write FAQ page content (10-15 Q&A entries: materials, shipping, custom orders, care)
- [x] Rewrite product description openers — remove emoji/promo intro, lead with 40-60 word factual block
- [x] Add question-based H2s to about-us ("What is Timber Trace Crafts?", "What materials does Timber Trace Crafts use?", etc.)
- [x] Add 134-167 word "What is laser-cut woodworking?" definition block to about-us (above the fold)
- [x] Add wood materials comparison table to about-us (Maple vs Baltic Birch vs Basswood)
- [ ] Submit site to Bing IndexNow for Bing Copilot indexation acceleration

### Phase 2 — Content Publishing

- [ ] Publish 1st journal post with answer-first structure (40-60 word factual opener per H2)
- [ ] Publish 2nd journal post — include at least one data table or comparison
- [ ] Publish 3rd journal post — use question-based H2s throughout
- [ ] Write "How laser-cut wood earrings are made" pillar post (1,500+ words, step-by-step)

### Phase 3 — Off-Site Brand Presence

- [ ] Create YouTube channel "Timber Trace Crafts" — post first studio process video
- [ ] Post second YouTube video (hand-finishing or inlay process)
- [ ] Create LinkedIn company page for Timber Trace Crafts
- [ ] Participate in r/woodworking — share a genuine behind-the-scenes post (not a link drop)
- [ ] Participate in r/crafts — answer a question about laser cutting or wood finishing
- [ ] Add YouTube and LinkedIn URLs to `social.*` settings in admin

### Phase 4 — Schema & Entity

- [ ] Add YouTube and LinkedIn URLs to Organization `sameAs` array in `layouts/app.blade.php`
- [ ] Add `jobTitle` and `description` to Person entity for Michael J. Miller in global schema
- [ ] Add `SiteLinksSearchBox` schema (SearchAction) once traffic justifies it

---

## Blog Content (plan: `.claude/plans/blog_content_strategy.md`)

### FLOW 90-Day Calendar (gift-intent first)

- [x] Create featured image for Week 1 blog post before publishing
- [ ] Week 1: "The Best Handmade Gifts for Women Who Appreciate the Details" (pillar, ~2,500 words)
- [ ] Week 2: "Valentine's Day Jewelry Gift Guide" (listicle, publish by Jan 10)
- [ ] Week 3: "How Wood Earrings Are Made: A Look Inside the Workshop" (how-to)
- [ ] Week 4: "Personalized Jewelry Box Ideas: How to Make a Gift She'll Keep Forever" (listicle)
- [ ] Week 6: "What to Get a Woman Who Has Everything: 7 Handmade Ideas" (listicle)
- [ ] Week 7: "Why We Started Timber Trace Crafts" (thought-leadership / brand story)
- [ ] Week 8: "Mother's Day Gifts from a Small Maker" (listicle, publish by Apr 1)
- [ ] Week 10: "Handmade Wedding Party Gifts: A Complete Guide for Brides" (pillar)
- [ ] Week 12: "Wood vs. Metal Earrings: Which Is Better for Sensitive Ears?" (comparison)
- [ ] After each post: run `/blog analyze`, score 80+, add internal links to product pages, apply tags

### Month 1 Content

- [ ] Write Pillar 6: "How Laser Cutting and Engraving Works" (~3,000 words)
- [ ] Write Spoke 3.2: "Can you wear wood earrings in the shower?" (FAQ, ~500 words)
- [ ] Write Spoke 5.2: "Are engraved tumblers dishwasher safe?" (FAQ, ~500 words)
- [ ] Publish all three with featured images, tags, and SEO meta filled in

### Month 2 Content — Personalization Hub

- [ ] Write Pillar 1: "Ultimate Guide to Personalized Laser-Engraved Gifts" (~3,500 words)
- [ ] Write Spoke 1.1: "How laser engraving personalization works"
- [ ] Write Spoke 1.2: "What can and can't be laser engraved"
- [ ] Write Spoke 1.7: "Laser engraving vs. laser cutting: what's the difference?"
- [ ] Set up Pinterest scheduling — pin every post with image + excerpt on publish day
- [ ] Begin monthly AI citation tracking (10 target queries in ChatGPT + Perplexity)

### Month 3 Content — Gift Season + Wood Jewelry Cluster

- [ ] Write Pillar 2 gift guide hub + first 2 seasonal spokes (next upcoming holiday)
- [ ] Write Pillar 3: "The Complete Guide to Wood Earrings" (~3,000 words)
- [ ] Write Spoke 3.3: "Are wood earrings hypoallergenic?"
- [ ] Run `/blog analyze` on all published posts; update any scoring below 70
- [ ] Review AI citation tracking results; adjust content angle based on what's being extracted

---

## Audit findings — 2026-06-28

- [x] **[High]** Wishlist add/remove queries non-existent `variant_id` column — `app/Http/Controllers/AccountController.php:63`
      Fix: Use `product_variant_id` in the `firstOrCreate` attributes (line 63-66) and the `where` clause (line 73-74); the `wishlists` table and `Wishlist` model use `product_variant_id`. Both actions currently throw a SQL column-not-found 500. Add an AccountTest case for wishlist add/remove.
- [x] **[High]** Order status lookup has no rate limiting (PII exposure / ID enumeration) — `routes/web.php:64`
      Fix: Add `->middleware('throttle:5,1')` to the `order.status.lookup` route. It returns full order PII (address, items, gift message) gated only by email + sequential integer order ID, while all other sensitive endpoints are throttled.
- [x] **[Medium]** New Etsy orders never trigger admin notification (async race) — `app/Http/Controllers/EtsyWebhookController.php:104`
      Also fixed: webhook signature verification rejected real Etsy/Svix `v1,`-prefixed signatures (401). Now strips the scheme prefix before comparing.
      Fix: For new receipts the controller dispatches `ImportEtsyOrder` async then immediately re-queries the order (still null), skipping `Cache::increment('etsy.new_orders')` and `EtsyNewOrderMail`. Move the cache increment + admin email into the job after the order is persisted, and queue the mail instead of `->send()`.
- [ ] **[Medium]** Variant-level price ignored when adding to cart — `app/Http/Controllers/CartController.php:53`
      Fix: Cart stores `$product->currentPrice()` and never consults `$variant->price`, so per-variant price overrides are not charged. Use `$variant->price ?? $product->currentPrice()`. (Confirm variant pricing is intended to override.)
- [ ] **[Medium]** Coupon `max_uses` can be exceeded under concurrent checkouts — `app/Models/Coupon.php:61`, `app/Http/Controllers/CheckoutController.php:236`
      Fix: `isValid()` reads `used_count` without a lock; the increment happens later in the order transaction. Lock the coupon row (`lockForUpdate`) and re-check `used_count < max_uses` inside the same `DB::transaction` before incrementing.
- [ ] **[Low]** Guest email leaked in confirmation URL query string — `app/Http/Controllers/CheckoutController.php:254`
      Fix: Redirect passes `?email=` (lands in access logs, history, Referer). Stash email/authorization in session or sign the confirmation URL instead.
- [ ] **[Low]** First registered user silently promoted to admin — `app/Http/Controllers/AuthController.php:56`
      Fix: `register()` grants `role = 'admin'` when `User::count() === 0`. If `/register` is reachable before an admin is seeded, an outsider can claim admin. Seed the admin explicitly and remove auto-promotion, or gate behind a one-time setup token.
