# Timber Trace Crafts — Implementation Plan

## Decisions Made (from your answers + defaults for gaps)

| Topic | Decision |
|---|---|
| Framework | Laravel 11 (PHP 8.2–8.5) |
| Frontend | Blade templates + Tailwind CSS + Alpine.js (minimal) |
| Database | MySQL 8.x |
| Payments | Square Web Payments SDK + Square Payments API |
| Transactional email | Hostinger built-in SMTP via Laravel Mail |
| Marketing email | Sender.net API |
| Analytics | Umami.js (cloud-hosted) |
| Caching | LiteSpeed Cache (LSCache) via response headers in Laravel middleware |
| Deployment | Hostinger Git auto-pull on push to `main` |
| Domain | timbertracecrafts.com (transferring to Hostinger) |
| Tax | Florida-only manual rate in admin settings; hooks ready for TaxJar later |
| Shipping labels | Manual tracking entry in admin → Pirate Ship integration in Phase 2 |
| Marketplace sync | Phase 3 (Etsy, eBay, Amazon Handmade) |
| Admin roles | Single admin role (sole proprietor); expandable later |
| Sales reports | Basic dashboard: revenue by period, top products, order counts |
| Low stock alerts | Email to admin when variant stock falls below configurable threshold |
| Media library | Full shared library; images/videos uploaded once, attached to products |
| Customer messaging | Contact form submissions visible in admin inbox; replies via external email |

---

## Technology Stack

```
Laravel 11
├── Blade (server-rendered templates — no SPA, no React)
├── Tailwind CSS (compiled, design system tokens as CSS variables)
├── Alpine.js (image gallery, cart qty controls, mobile menu, dropdowns only)
├── MySQL 8.x
├── Square PHP SDK (payments)
├── Laravel Mail + Hostinger SMTP
├── Sender.net API client (Phase 2)
└── LiteSpeed Cache headers (middleware)
```

**Why Laravel over the existing custom MVC:** Laravel gives you Eloquent ORM, migrations, queues (for email), form validation, file storage, artisan commands, and a mature ecosystem — all things the custom MVC would have to rebuild. It runs cleanly on Hostinger shared hosting.

---

## Database Schema

### Users & Auth
```sql
users               (id, name, email, password, role[customer|admin], email_verified_at, remember_token, created_at)
password_reset_tokens (email, token, created_at)
addresses           (id, user_id, label, first_name, last_name, line1, line2, city, state, zip, country, phone, is_default)
```

### Catalog
```sql
categories          (id, parent_id, name, slug, description, image_id, sort_order, meta_title, meta_description)
tags                (id, name, slug, type[wood_species|style|other])
products            (id, name, slug, sku_base, description, short_description, category_id,
                     price, sale_price, personalization_type[none|included|addon],
                     personalization_price, personalization_prompt, personalization_max_chars,
                     status[active|draft|archived], featured, sort_order,
                     meta_title, meta_description, created_at)
product_tags        (product_id, tag_id)
product_variants    (id, product_id, sku, label, material_code, stock_qty,
                     low_stock_threshold, sort_order)
product_media       (id, product_id, variant_id[nullable], media_id, sort_order, is_primary)
```

### Media
```sql
media               (id, filename, original_name, disk, path, mime_type, size_bytes,
                     alt_text, uploaded_by, created_at)
```

### Reviews
```sql
product_reviews     (id, product_id, order_item_id[nullable], user_id[nullable], guest_name,
                     guest_email, rating[1-5], title, body,
                     status[pending|approved|rejected], admin_note, created_at)
```

### Wishlists & Restock
```sql
wishlists           (id, user_id, product_variant_id, created_at)
restock_requests    (id, product_variant_id, email, notified_at, created_at)
```

### Coupons
```sql
coupons             (id, code, description, type[percent|fixed], value,
                     min_order_amount, max_uses[nullable], used_count,
                     applies_to[all|category|product],
                     category_id[nullable], product_id[nullable],
                     starts_at[nullable], expires_at[nullable], active)
```

### Orders
```sql
orders              (id, user_id[nullable], guest_email, status[pending_payment|processing|
                     in_production|shipped|delivered|refunded|cancelled],
                     subtotal, discount_amount, shipping_amount, tax_amount, total,
                     coupon_id[nullable], coupon_code_snapshot,
                     square_payment_id, square_order_id,
                     shipping_method, gift_message,
                     shipping_first_name, shipping_last_name, shipping_line1,
                     shipping_line2, shipping_city, shipping_state, shipping_zip,
                     shipping_country, shipping_phone,
                     billing_first_name, billing_last_name, billing_line1,
                     billing_line2, billing_city, billing_state, billing_zip,
                     billing_country, ip_address, notes, created_at)
order_items         (id, order_id, product_id, variant_id, sku_snapshot, name_snapshot,
                     variant_label_snapshot, personalization_text,
                     price_snapshot, qty, subtotal)
order_status_history (id, order_id, status, note, created_by[nullable], created_at)
shipments           (id, order_id, carrier, service, tracking_number, shipped_at,
                     estimated_delivery, label_url[nullable])
```

### Shipping
```sql
shipping_methods    (id, name, carrier[usps], service_code, description,
                     price_override[nullable], is_free_base, sort_order, active)
-- Seed: USPS Ground (free), USPS Priority (+$X), USPS Priority Express (+$X)
-- Prices set in admin settings
```

### Tax
```sql
tax_rates           (id, state_code, rate_percent, label, active)
-- Seed: FL = 6% (configurable in admin)
```

### Journal
```sql
journal_posts       (id, user_id, title, slug, excerpt, body, featured_image_id[nullable],
                     status[draft|published], published_at, meta_title, meta_description)
journal_post_tags   (post_id, tag_id)
```

### CMS / Static Pages
```sql
pages               (id, title, slug, body, meta_title, meta_description, updated_at)
-- Seeds: faq, privacy-policy, terms-and-conditions, return-policy, about-us
```

### Settings
```sql
settings            (key VARCHAR(100) PK, value TEXT, group, label, updated_at)
-- Examples: store.name, store.email, social.instagram_url, tax.fl_rate,
--           shipping.priority_upcharge, shipping.priority_express_upcharge,
--           email.order_confirmation_subject, low_stock.alert_threshold_default
```

### Contact
```sql
contact_submissions (id, name, email, subject, message, status[new|read|replied],
                     admin_note, created_at)
```

---

## URL Structure

### Public
```
/                           Home / Landing
/shop                       Shop (all products)
/shop?category=jewelry      Filtered by category
/shop?tag=cherry            Filtered by tag (wood species)
/product/{slug}             Product detail
/cart                       Cart
/checkout                   Checkout
/checkout/confirmation/{id} Order confirmation
/order-status               No-login order tracking form
/journal                    Journal list
/journal/{slug}             Journal post
/about                      About Us
/contact                    Contact
/faq                        FAQ
/privacy-policy             Privacy Policy
/terms-and-conditions       Terms & Conditions
/return-policy              Return Policy
/account                    My Account (requires login)
/account/orders             My Orders
/account/orders/{id}        Order Detail
/account/wishlist           My Wishlist
/account/addresses          Saved Addresses
/account/profile            Profile & Password
/login                      Login
/register                   Register
/forgot-password            Password Reset
```

### Admin (all require admin role)
```
/admin                      Dashboard
/admin/orders               Orders list
/admin/orders/{id}          Order detail + status management
/admin/orders/{id}/packing-slip  Print packing slip
/admin/products             Products list
/admin/products/create      New product
/admin/products/{id}/edit   Edit product
/admin/categories           Categories & Tags
/admin/media                Media library
/admin/coupons              Coupons
/admin/customers            Customers list
/admin/customers/{id}       Customer detail
/admin/reviews              Review approval queue
/admin/restock              Restock requests
/admin/journal              Journal posts list
/admin/journal/create       New post
/admin/journal/{id}/edit    Edit post
/admin/messages             Contact form inbox
/admin/pages                Static page editor list
/admin/pages/{slug}/edit    Edit static page
/admin/reports              Sales reports
/admin/settings             Store settings
```

---

## Feature Specifications

### Product Variants & Personalization
- Each product has a `personalization_type`:
  - `none` — no personalization field shown
  - `included` — text field shown, no extra charge
  - `addon` — text field shown, `personalization_price` added to cart total
- `personalization_prompt` is the label shown to the customer (e.g., "What name would you like engraved?")
- `personalization_max_chars` limits input length
- Variant selected via swatches (material/wood species label, e.g., "Cherry" "Walnut")
- Selecting an out-of-stock variant hides Add to Cart, shows restock request form

### Cart
- Session-based for guests, database-persisted for logged-in users
- Merges session cart into account cart on login
- Line items: product, variant, qty, personalization text, price
- Coupon code field with live validation
- Gift message textarea
- Shipping method selector (shown with pricing)
- Real-time subtotal / discount / tax / total calculation (Alpine.js, no AJAX — recalculate on form submit)

### Checkout
- Step 1: Contact info (if guest) + shipping address
- Step 2: Shipping method selection
- Step 3: Review order + payment (Square Web Payments Card element)
- Single page with Alpine.js step visibility (no full-page reloads between steps)
- On payment success: create order record, clear cart, redirect to confirmation
- Florida tax: apply FL rate if shipping to FL, otherwise 0% (expandable later)

### Square Integration
- Frontend: Square Web Payments SDK card tokenization
- Backend: Square Payments API `CreatePayment` with card nonce
- Order amount sent to Square matches server-calculated total (never trust client)
- Webhook endpoint for payment events (capture failures, refunds)

### Shipping Method Selection
- Three methods seeded: USPS Ground (free), USPS Priority, USPS Priority Express
- Priority and Priority Express upcharges set in admin settings
- All three always available (no weight/zone calculation)

### Email Notifications (Hostinger SMTP)
Triggered events:
1. Order placed → customer order confirmation
2. Order status changed → customer notification (configurable per status)
3. Order shipped → customer shipping notification with tracking number
4. Review submitted → admin notification
5. Contact form submitted → admin notification  
6. Low stock threshold hit → admin notification
7. Restock request fulfilled → customer notification (Phase 2, Sender.net batch)

Templates are Blade views with inline CSS (email-safe).

### Media Library
- Supports JPEG, PNG, WebP, MP4
- Uploads stored in `storage/app/public/media/` (symlinked to `public/storage/`)
- Product editor has drag-and-drop gallery builder
- Videos show inline player on product page (HTML5 `<video>`)
- Image lightbox on product page (Alpine.js, no heavy library)

### LiteSpeed Cache Strategy
- Public product pages: `Cache-Control: public, max-age=3600` + LSCache tag headers
- Cart, checkout, account pages: `Cache-Control: no-store`
- Admin: `Cache-Control: no-store`
- Cache purge on product/price update via artisan command called in admin save hooks

### SEO (Phase 2, but schema in place from Phase 1)
- `<title>`, `<meta name="description">`, `<link rel="canonical">` on every page
- Open Graph + Twitter Card tags
- `sitemap.xml` generated via artisan command (scheduled weekly)
- JSON-LD `Product` schema on product pages
- JSON-LD `Article` schema on journal posts
- JSON-LD `Organization` + `LocalBusiness` on home page

---

## Admin Features

### Dashboard Widgets
- Revenue today / this week / this month / this year
- Orders by status (counts)
- Low stock alerts (variants below threshold)
- Pending reviews count
- Unread contact messages count
- Last 10 orders table

### Order Management
- Filter by: status, date range, search (name, email, order #)
- Bulk action: mark as processing
- Order detail: line items, customer, addresses, payment info, status timeline, shipment tracking entry, internal notes, print packing slip button
- Status change dropdown with optional note → logged to `order_status_history` → triggers customer email

### Product Editor
- Name, slug (auto-generated), category, tags
- Short description (used in shop grid), full description (rich text)
- Price, sale price (optional)
- Personalization config (type, price, prompt, max chars)
- Variant builder: add/remove/reorder variants (label, SKU, stock qty, low-stock threshold)
- Media gallery: drag images from media library or upload directly; drag to reorder; mark primary; attach video; optionally link to specific variant
- SEO fields (meta title, meta description — with character count)
- Status (active / draft / archived)
- Featured toggle

### Reports (Phase 2 detail, but basic in Phase 1)
- Phase 1: Revenue table by month, top 10 products by revenue
- Phase 2: Charts (Chart.js), category breakdown, coupon usage report

---

## Deployment Pipeline

### Repository Structure
```
timbertracecrafts/
├── app/
│   ├── Http/Controllers/   (Admin/, Auth/, Shop/, Api/)
│   ├── Models/
│   ├── Mail/
│   ├── Services/           (SquareService, CartService, TaxService, etc.)
│   └── Middleware/         (AdminMiddleware, LiteSpeedCacheMiddleware)
├── config/
├── database/
│   ├── migrations/
│   └── seeders/            (ShippingMethodSeeder, SettingsSeeder, PageSeeder)
├── public/                 ← Hostinger document root
│   ├── index.php
│   └── storage/            ← symlink to ../storage/app/public
├── resources/
│   ├── views/
│   │   ├── layouts/        (app.blade.php, admin.blade.php, email.blade.php)
│   │   ├── shop/
│   │   ├── account/
│   │   ├── admin/
│   │   ├── emails/
│   │   └── pages/
│   └── css/ js/
├── routes/
│   ├── web.php
│   └── admin.php
├── storage/
├── .env.example            (committed; .env never committed)
├── .gitignore
└── composer.json
```

### Hostinger Setup (one-time)
1. SSH into Hostinger, set document root to `public/`
2. Clone repo from GitHub
3. `composer install --no-dev --optimize-autoloader`
4. Copy `.env.example` to `.env`, fill in credentials
5. `php artisan migrate --seed`
6. `php artisan storage:link`
7. Set up Hostinger Git deployment webhook pointing to `main` branch

### Deploy on Push to `main`
Hostinger auto-pulls → post-pull script runs:
```bash
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## Phase 1 Build Order

1. **Foundation**: Laravel install, Tailwind + Alpine.js setup, design tokens as CSS variables, base layouts (public + admin), auth scaffolding
2. **Database**: All migrations, seeders (settings, shipping methods, static pages)
3. **Media Library**: Upload, list, delete — reused across all admin editors
4. **Catalog**: Category/tag CRUD, product editor with variants + media, product list admin
5. **Shop Pages**: Home, shop grid (filter/sort/paginate), product detail (gallery, variant selector, personalization, add to cart)
6. **Cart**: Session/DB cart, coupon field stub (logic in Phase 2), gift message, shipping method selection
7. **Checkout**: Multi-step form, Square payment integration, order creation, confirmation page
8. **Order Management Admin**: List, detail, status change, tracking entry, packing slip print view
9. **Customer Emails**: Order confirmation, shipping notification, status change notifications
10. **Customer Account**: My orders, order detail, saved addresses, profile/password
11. **Public Static Pages**: About, Contact, FAQ, Privacy, T&C, Return Policy (all editable via admin page editor)
12. **Order Status Lookup**: No-login form
13. **Admin Dashboard**: Widgets, low stock alerts, recent orders
14. **Settings Admin**: Store info, SMTP config, shipping upcharges, FL tax rate

## Phase 2 Build Order

1. Coupon/discount system (apply at cart, validate server-side at checkout)
2. Product reviews (form on product page, approval queue in admin)
3. Wishlist (heart icon on product cards + dedicated account page)
4. Restock request form + admin batch-notify feature
5. Journal/blog (admin editor, public list + post pages)
6. Sender.net integration (newsletter signup widget, triggered campaigns)
7. Umami analytics snippet
8. SEO: sitemap generation, JSON-LD, full meta audit
9. LiteSpeed cache header tuning
10. Pirate Ship API: generate label from order detail in admin
11. Admin reports: revenue charts, top products, category breakdown

## Phase 3 Build Order

1. Etsy API sync (list products, update inventory)
2. eBay API sync
3. Amazon Handmade (manual CSV export as fallback; API if available)
4. Gift cards
5. Promotions automation (scheduled sales, countdown timers)

---

## Open Questions (to resolve before building)

- **Square credentials**: You'll need a Square developer account, sandbox + production keys. Do you already have these, or do we set them up as part of Phase 1?
- **Unanswered Q25–28** (defaulted above):
  - Reports: Basic revenue table + top products (Phase 1), charts (Phase 2) ✓
  - Low stock alerts: Email to admin at configurable per-variant threshold ✓
  - Media library: Full shared library ✓
  - Customer messaging: Admin can view contact submissions; replies via external email ✓
- **Priority/Priority Express pricing**: What are the upcharge amounts you want to start with?
- **Pirate Ship**: Is Phase 2 timing acceptable, or do you need label generation at launch?

---

## What This Will NOT Include (scope boundaries)

- No live chat widget (contact form only)
- No loyalty/rewards program
- No gift wrapping
- No real-time shipping rate calculation (flat upcharges only)
- No multi-currency (USD only)
- No multi-language
- No Swoole/async server mode — Hostinger shared hosting uses LiteSpeed/FPM only
