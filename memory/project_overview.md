---
name: project-overview
description: Complete spec for Timber Trace Crafts e-commerce site — stack, features, pages, deployment, phases, and known gaps
metadata:
  type: project
---

## Business Context
Timber Trace Crafts — laser cut/engraved wooden jewelry, boxes, crafts. Sole proprietor: **Michael J. Miller**, disabled veteran (Desert Storm), Avon Park, FL. Sells on Etsy currently; own store is the primary channel goal. Domain: timbertracecrafts.com (Hostinger).

## Current Products (as of 2026-06-22, seeded)
- Butterfly earrings — Design 1, 2, 3 (6 wood species each: Cherry, Mahogany, Maple, Padauk, Red Oak, Walnut)
- Teardrop earrings (same 6 species)
- Personalized Heart Jewelry Box (3mm Baltic Birch, Red felt lining, custom engraving)
- America 250 Tumbler (20oz stainless steel, laser-etched)

## SKU Convention
`[TYPE]-[DESIGN]-[MATERIAL][THICKNESS]-[VARIANT]`
Example: EAR-BFLY-CHY3-01

## Personalization Rules (per product config)
- **none** — no personalization offered
- **included** — personalization included in base price
- **addon** — personalization available at extra cost

## Tech Stack
- **Framework**: Laravel 13 (PHP 8.3)
- **Database**: MySQL
- **Frontend**: Laravel Blade + Tailwind CSS v4 + Alpine.js v3
- **Payments**: Stripe (switched from Square; account/keys not yet set up)
- **Transactional Email**: Hostinger SMTP (Laravel Mail)
- **Analytics**: Umami.js (planned, not yet integrated)
- **Deployment**: Hostinger Git auto-pull on push to main
- **APP_URL**: http://127.0.0.1:8001 (dev); update for production

## Design System
"Deep Forest" / "Artisanal Editorial" aesthetic
- Colors: Oak Sand (#F4F1EA) bg, Charcoal (#333333) text, Deep Forest Green (#2C4C3B) primary, Rich Mahogany (#4A2C11), Walnut (#8C7B6C) meta
- Fonts: Playfair Display (headings), Montserrat (body)
- Style: Minimalist editorial, heavy whitespace, flat tonal depth, no shadows

## Shipping
- Free USPS Ground on all orders
- Optional upgrades: USPS Priority, Priority Express (customer pays difference)
- Local pickup option
- Manual tracking number entry in admin → triggers customer email

## Tax
- Florida nexus only; 7.5% Avon Park rate configured in DB

## Built — Public Pages & Routes
home, shop (with search + category/price/sale filters), product detail (gallery, variants, reviews), cart (with coupon), checkout (Stripe payment intent), order-confirmation, order-status (no-login lookup), about-us, contact, journal, faq, privacy-policy, terms-and-conditions, return-policy, my-account (orders, wishlist, addresses, profile), login, register, forgot-password, reset-password, restock-request

## Built — Admin Pages & Routes
dashboard, orders (list + detail + packing slip + shipment + status change), products (CRUD), categories (CRUD), tags (CRUD), media library (upload/delete), coupons (CRUD), customers (list + detail), reviews (approval queue), journal posts (CRUD), restock requests (list + notify), contact messages (list + status), static page editor, settings, reports, shipping methods (CRUD)

## Built — Models
Address, Category, ContactSubmission, Coupon, JournalPost, Media, Order, OrderItem, OrderStatusHistory, Page, Product, ProductMedia, ProductReview, ProductVariant, RestockRequest, Setting, Shipment, ShippingMethod, Tag, TaxRate, User, Wishlist

## Built — Other
- Auth: remember me, rate limiting (throttle:60,1)
- Variants: stock tracking, low-stock threshold, out-of-stock restock request
- Wishlist (logged-in users)
- Guest checkout
- Coupons: percent or fixed, all/category/product scope
- Reviews: admin approval queue
- Alpine.js gallery component, variantSelector component (fixed 2026-06-22)

## Order Workflow
pending_payment → processing → in_production → shipped → delivered → refunded / cancelled

## NOT YET BUILT — Prioritized Gap List

### High priority (blocks launch or legally required)
1. **Stripe keys configured** — account doesn't exist yet; wire up .env + webhook
2. **Cookie consent / GDPR-CCPA banner** — legally required; show on first visit, persist choice
3. **Email verification** — required per spec; verify it's wired in AuthController
4. **Password strength rules** — require min length/complexity in register + reset
5. ~~**CAPTCHA** — on login, contact form, and checkout (prevent spam/fraud)~~ — **BUILT** (`app/Services/RecaptchaService.php`)
6. **About page content** — CMS page needs the real story: Michael J. Miller, disabled veteran, Desert Storm, Avon Park FL, one-person operation

### Medium priority (important UX / admin)
7. **Refund via Stripe API** — admin can set status "refunded" but no actual Stripe refund call
8. **Invoice/receipt download** — from My Account → order detail
9. **Recently viewed products** — Alpine.js + localStorage; shown on product and shop pages
10. **Gift message field at checkout** — per spec; verify it's in the checkout form
11. **Product search UI** — backend exists (ShopController has search query); wire up search box in shop header/sidebar
12. **Shop filter by tags** — tags exist in admin and on products; not exposed as shop filter
13. **Social sharing buttons on product pages** — Facebook, Pinterest, Instagram
14. **Reorder past items** — from My Account order history
15. ~~**Admin 2FA** — required per spec; can use TOTP (Laravel Fortify or custom)~~ — **BUILT** (`app/Services/TwoFactorAuthService.php`)
16. **Admin session expiry** — short idle timeout for admin routes
17. **Audit log** — log important admin actions (order status changes, product edits, etc.)
18. **Admin error log viewer** — view Laravel log from admin panel

### Lower priority / Post-launch
19. **Custom orders / B2B / bulk quote request flow** — separate page + form for custom work quotes (not just contact form)
20. **Gallery / portfolio page** — showcase completed work with photo grid
21. **Materials / sizing / care guide page** — static content page
22. **Dark mode** — CSS custom properties + Alpine toggle + localStorage
23. **Umami analytics** — add script to layout; configure in settings
24. **Shipping labels (Avery 5126 format)** — printable label from admin order detail
25. **Review photos** — allow customers to attach photos to reviews
26. **Customer file upload for custom engraving** — future product type; design upload field
27. **SEO sitemap.xml + JSON-LD structured data** — auto-generated, linked from robots.txt
28. **Abandoned cart emails** — defer post-launch
29. ~~**Gift cards** — defer post-launch~~ — **BUILT** (`app/Services/GiftCardService.php`, `GiftCard` model)
30. **Marketplace sync** (Etsy, eBay, Amazon Handmade) — Phase 3

## Phases
**Phase 1 (MVP — current focus)**
All built pages + Stripe wired + cookie consent + email verification + CAPTCHA + password rules + gift message + about page content + invoice download + Stripe refund action

**Phase 2 (Post-launch UX)**
Recently viewed, social sharing, reorder, tags filter, search UI, dark mode, Umami, custom quote flow, gallery, care guide, review photos, audit log, admin 2FA

**Phase 3 (Later)**
Shipping labels, marketplace sync, gift cards, abandoned cart, file uploads for engraving
