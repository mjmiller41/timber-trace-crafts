# Timber Trace Crafts Project Handoff

Last updated: 2026-06-22

## Workspace

- Correct project folder: `/home/michael/Code/Projects/timber-trace-crafts-custom-codex`
- If Codex shows "Current working directory missing", the old chat likely attached to a malformed path under:
  `/mnt/c/Program Files/WindowsApps/.../app/resources/\\wsl.localhost\Ubuntu-24.04\home\michael\Code\Projects\timber-trace-crafts-custom-codex`
- The real folder exists and should be used directly as a saved local Codex project.
- At the time of this handoff, this folder contains `.agents` assets and guidance but no scaffolded PHP app yet.

## Brand

- Public name: Timber Trace Crafts
- Tagline: Precision Laser-Cut Woodcrafts & Custom Decor
- Location: Avon Park, FL
- Market: United States only
- Business emphasis: both ready-made products and handmade/custom work
- Style direction: rustic handmade with a refined "Deep Forest" editorial feel
- Logo source: `/mnt/d/Documents/TimberTraceCrafts/Images/Logo/logo.png`

## Design System

Read `.agents/timber_trace_crafts_design_plan/` before UI work.

- Primary background: Oak Sand `#F4F1EA`
- Primary text: Charcoal `#333333`
- Primary/accent green: Deep Forest Green `#2C4C3B`
- Secondary accents: Rich Mahogany `#4A2C11`, Pine Shadow `#1E3529`, Walnut `#8C7B6C`
- Heading font: Playfair Display
- Body/UI font: Montserrat
- Monospace: Source Code Pro
- Visual language: flat editorial surfaces, tactile warmth, thin borders, minimal shadows, crisp or lightly rounded controls
- Existing prototype screens include landing, shop, product, cart/checkout, workshop, and typography examples.

## Source Files And Product Assets

- Etsy product export: `/mnt/d/Downloads/EtsyListingsDownload.csv`
- Google taxonomy workbook: `/mnt/d/Downloads/taxonomy-with-ids.en-US.xlsx`
- Product images/videos: `.agents/product-images/Images/`
- Product media naming is SKU based, e.g. `BOX-HRT-BBPLY3-01-IMG1.jpg`, `TMBLR-AM250-STNLS20-01-VID1.mp4`
- Etsy CSV currently has 6 product rows.
- Product media inventory observed: 127 files, 26 SKU groups, 11 unmatched/lifestyle files.
- Google taxonomy workbook structure begins with columns: `Category ID`, `Attribute 1`, `Attribute 2`, etc.

## Launch Product Scope

Launch with 6 products and 3 main category families:

- Laser cut hardwood jewelry
- Jewelry boxes
- Tumblers

Known taxonomy/category targets:

- `Home & Garden > Kitchen & Dining > Tableware > Drinkware > Mugs`
- `Apparel & Accessories > Jewelry > Earrings`
- `Health & Beauty > Jewelry > Cleaning & Care > Jewelry Holders` for jewelry boxes

Product requirements:

- Mix of stocked inventory and personalized products
- Variants required, tracked by variant
- Variant examples: wood species, color, felt lining color for jewelry boxes
- Personalization fields: engraved name/message/date/monogram
- Customer file uploads for custom engraving should be designed for future products
- Inventory tracking by variant
- Low-stock warnings
- Out-of-stock products remain visible with restock request option
- Back-in-stock notifications
- SKU numbers required
- Weight and dimensions required for shipping/admin metadata
- Sale price support, no compare-at pricing
- Reviews with admin approval
- Review photos allowed
- Related products
- Tags and subcategories

## Shop Experience

- Filters: category, price, material, custom/personalized, availability, rating
- Search products, descriptions, and tags
- Sort by newest, price, popularity, featured
- Wishlist/favorites
- Recently viewed products
- Landing page shows featured products and categories
- Separate custom project request flow for custom projects, B2B, and bulk orders
- Custom work requires a quote before purchase

## Cart, Checkout, Payments

- Guest checkout allowed; email required
- Account creation encouraged, not required
- Payment processor: Stripe
- Stripe account/API keys do not exist yet
- Support Apple Pay/Google Pay if Stripe allows
- Customer order confirmation emails
- Admin new order emails
- Customer order notes
- Terms acceptance before checkout
- Save abandoned carts; abandoned cart emails later
- Coupons required
- Coupon types: percent and fixed discounts by product, category, or custom grouping
- Gift cards later
- No gift wrapping
- Gift message text supported

## Shipping, Tax, Returns

- Carrier: USPS
- Shipping methods:
  - USPS Ground: free
  - USPS Priority: $7 extra
  - USPS Priority Express: $35 extra
  - Local pickup: yes
- Shipping does not depend on product weight for launch pricing
- No free-shipping threshold because ground is already free
- Processing time: 1-2 business days for in-stock, 3-5 business days for personalized
- Returns: 30 days
- Personalized/custom returns: defective items only
- Return shipping paid by customer
- Exchanges supported
- Cancellations: 24 hours for personalized/custom items, before shipped for all others
- Sales tax: Florida only, Avon Park rate 7.5%

## Accounts

- My Account includes orders/history, wishlist, and shipping info
- Customers can view order history
- Reorder past items
- Saved addresses
- Do not store payment methods
- Download invoices/receipts
- Track order status
- Order statuses: pending, paid, processing, shipped, completed, canceled, refunded
- Email verification required
- Password reset required
- Login: email/password only, no social login

## Admin

- Users: owner now, possible future employees
- Future roles may include owner, manager, fulfillment, support
- For launch, admins have all permissions
- Admin login can share customer auth system with admin gates
- Dashboard: sales, orders, low stock, popular products
- CSV product import/export
- Bulk image upload
- Media manager: folders, alt text, compression, optional WebP conversion
- Product image drag-and-drop ordering
- Order status changes
- Refund support from admin
- Packing slips
- Shipping labels, including Avery 5126 self-adhesive format
- Customer notes
- No admin impersonation
- Audit logs for important actions
- Admin-managed content: FAQ, policies, homepage sections, content updates/additions
- Navigation/menu should be template/include PHP, not admin-managed
- Admin-managed coupons
- Admin-managed tax/shipping settings
- Admin-managed site settings: logo, social links, contact info
- Admin-managed custom order requests
- Admin-managed contact form messages
- Admin-visible error logs

## Content Pages

Required public pages:

- Home/landing
- Shop
- Category pages
- Product pages
- Cart
- Checkout
- Order confirmation
- Privacy policy
- Terms and conditions
- Return policy
- FAQ
- Contact us
- About us
- My account
- Customer login/register
- Wishlist
- Recently viewed
- Custom orders / B2B / bulk quote request
- Gallery/portfolio
- Journal
- Materials/sizing/care guide
- Cookie preferences

Content notes:

- No final policy/legal text exists yet; draft placeholder policy copy clearly marked for review.
- FAQ should be generated from products and business policies.
- About page story should include: Avon Park, FL; one-person operation; Michael J. Miller; disabled veteran owned; Desert Storm; quality craftsmanship and material selection; products blend laser precision with hand-crafted quality.
- Contact page should include form, email, phone, address, and social links.
- Newsletter signup yes, future journal/blog.
- Journal topics: new products, business updates, crafting techniques, processes.
- Gallery/portfolio yes.
- Testimonials section/page yes, but none exist yet.

## Emails And Notifications

- From email: `michael@timbertracecrafts.com`
- SMTP/mail: Hostinger mail
- Launch emails: welcome, password reset, order confirmation, order shipped with tracking, refund, contact form received
- Branded HTML emails
- Admin-editable email templates

## Technical Hosting Decisions

- Host: Hostinger Business Web Hosting shared server
- Runtime: PHP/MySQL on LiteSpeed with `.htaccess`
- PHP available up to 8.5
- Database: MariaDB 11.8.6
- Domain: `timbertracecrafts.com`
- SSL handled by Hostinger
- Deployment: Hostinger Git integration
- No staging environment for now
- Composer dependencies allowed
- npm/Vite/Tailwind allowed if useful, but no Node build step on the server
- Prioritize lightweight, fast page serving and long-term maintainability
- Database migrations required
- Seed data from Etsy CSV
- Uploaded media stored on filesystem with database records
- Automatic image resizing/thumbnails required
- Backups handled by Hostinger
- Analytics: Umami

## Security And Compliance

- Admin 2FA required
- Login attempts rate-limited
- Contact forms use CAPTCHA
- Checkout should use CAPTCHA/fraud protection as appropriate
- Admin sessions expire quickly
- Customer "remember me" supported
- Password rules required
- Do not store sensitive customer notes
- GDPR/CCPA cookie consent and cookie banner required
- Accessibility and keyboard/screen reader support required from the start

## UX Decisions

- Mobile-first
- Product pages use thumbnails with lightbox ability, not image-heavy layouts
- Cart is both drawer while browsing and page for checkout
- Checkout is single-page
- Admin UI: utilitarian, moderate density
- Dark mode yes
- Accessibility yes

## Minimum Live Version

"Minimum version live first" means:

A customer can browse real products, view details/media, choose variants/personalization, add to cart, check out with Stripe, receive emails, and track the order. The owner can log in as admin, manage products/images/inventory/orders/customers/coupons/basic pages/settings, and fulfill orders.

Defer until after initial launch if necessary:

- Gift cards
- Advanced employee roles/permissions
- Abandoned cart emails
- Advanced reporting
- Heavy custom-upload engraving workflows
- Journal polish

## Recommended App Plan

Scaffold a lightweight custom PHP app in this folder:

- `public/`
- `app/`
- `config/`
- `database/`
- `storage/`
- `bin/`

Follow `.agents/AGENTS.md` rules:

- Use `getenv()`, not `$_ENV`
- Local DB: user `admin`, password `admin`, database `timber_trace_crafts`
- Do not pre-seed admin credentials; first registered user should auto-elevate to admin
- Use `App\Core\Request` and `App\Core\Response`
- Route dynamic params through `config/routes.php`
- Keep Swoole compatibility in mind; do not share one static PDO across requests
- Use PHPSession for FPM and FileSession for Swoole-compatible sessions

Suggested implementation phases:

1. Scaffold app structure, Composer autoloading, router, request/response, config, `.htaccess`.
2. Add database layer, migrations, auth, sessions, CSRF, validation, flash messages.
3. Build catalog schema: products, variants, options, inventory, media, categories, tags.
4. Import Etsy CSV, taxonomy workbook, logo, and SKU-matched media.
5. Build storefront: home, shop, category, product, cart, checkout, account, policies/content.
6. Integrate Stripe, shipping, Florida tax, order creation, transactional emails.
7. Build admin MVP: dashboard, products, media, orders, customers, coupons, content/settings.
8. Add reviews, restock requests, custom quotes, gallery, journal, care guide.
9. Add security polish: admin 2FA, CAPTCHA hooks, rate limits, cookie preferences, audit/error logs.
10. Prepare Hostinger Git deployment, `.env.example`, migration/seed commands, launch checklist.

