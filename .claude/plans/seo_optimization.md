# SEO Optimization Plan — Timber Trace Crafts

**Goal:** Bring timbertracecrafts.com from 40/100 to 75+/100 SEO health by fixing all critical bugs, adding structured data, hardening technical SEO, and improving on-page signals.

**Tech Stack:** Laravel 13, PHP 8.3, Blade templates, Tailwind v4, Alpine.js v3

---

## Task Checklist

- [x] Task 1: Fix page title bug — `@yield('title')` in layout *(done 2026-06-25)*
- [x] Task 2: Fix review filter bug — `status` column, not `approved` *(done 2026-06-25)*
- [x] Task 3: Add default canonical tags + noindex on private pages *(done 2026-06-25)*
- [x] Task 4: XML sitemap at `/sitemap.xml` + harden `robots.txt` *(done 2026-06-25)*
- [x] Task 5: OG / Twitter Card meta tags with per-page overrides *(done 2026-06-25)*
- [x] Task 6: Structured data JSON-LD — Organization, WebSite, Product, Offer, AggregateRating, BreadcrumbList, BlogPosting *(done 2026-06-25)*
- [ ] Task 7: Hero image — convert `lifestyle-1.png` → WebP, upload to R2, set R2 cache headers, update `<img>` src + `@push('preload')` href, run `npm run build` *(code done; 5 manual steps remain)*
- [x] Task 8: SecurityHeaders middleware — HSTS, nosniff, X-Frame-Options, public cache for guests *(done 2026-06-25)*
- [x] Task 9: Async Google Fonts — trim from 9 to 5 weights, non-render-blocking *(done 2026-06-25)*
- [x] Task 10: Remove `Str::limit(400)` from product description *(done 2026-06-25)*
- [x] Task 11: 301 redirect `/about` → `/about-us` *(done 2026-06-25)*
- [x] Task 12: Update homepage title to keyword-bearing string *(done 2026-06-25)*
- [x] Task 13: Fix newsletter 404 — create `/newsletter` POST route and controller *(done 2026-06-25)*
- [x] Task 14: Replace inline `onmouseover` handlers in footer with CSS hover classes *(done 2026-06-25)*
- [x] Task 15: Create `/public/llms.txt` for AI crawler brand fact sheet *(done 2026-06-25)*
- [x] Task 16: Add author byline to journal post pages *(done 2026-06-25)*
- [x] Task 17: Hide journal section on homepage when no published posts *(done 2026-06-25)*
- [x] Task 18: Wire homepage newsletter form to `/newsletter` route *(done 2026-06-25)*
- [x] Task 19: Set 1-year `Cache-Control` on Vite-fingerprinted static assets *(done 2026-06-25)*

---

## ⚠️ Remaining: Task 7 Manual Steps

The code (preconnect, preload slot, `fetchpriority`, logo alt + dimensions) is done. These steps require manual action outside the codebase:

1. ~~Convert `lifestyle-1.png` → WebP~~ *(file is already WebP despite .png extension)*
2. ~~Upload to R2~~ *(in progress 2026-06-25)*
3. Set `Cache-Control: public, max-age=31536000, immutable` on all R2 image objects (Cloudflare R2 dashboard or `wrangler r2 object put`)
4. Update `resources/views/home/index.blade.php` — change hero `<img src>` and `@push('preload')` href to point to the R2 URL
5. Run `npm run build` to compile updated footer CSS classes

---

## Global Constraints

- PHP 8.3, Laravel 13 — use `php artisan make:` for new files
- Run `vendor/bin/pint --dirty --format agent` after every PHP file change
- Run `php artisan test --compact` after tasks that touch controllers/models
- All routes must be named; use `route()` helper, not `url()`
- No new npm/composer dependencies without approval

---

## Priority Order Summary

| # | Task | Impact | Status |
|---|------|--------|--------|
| 1 | Title bug fix | Critical | ✅ Done |
| 2 | Review filter bug | Critical | ✅ Done |
| 3 | www 301 redirect | Critical | ✅ Done (Hostinger config) |
| 4 | Canonical tags + noindex | Critical | ✅ Done |
| 5 | XML sitemap + robots.txt | Critical | ✅ Done |
| 6 | OG / Twitter Card tags | High | ✅ Done |
| 7 | Structured data JSON-LD | Critical | ✅ Done |
| 8 | Hero image WebP + R2 cache | Critical | ⚠️ Partial — manual steps remain |
| 9 | Security headers | High | ✅ Done |
| 10 | Async Google Fonts | High | ✅ Done |
| 11 | Remove product description truncation | High | ✅ Done |
| 12 | /about redirect | Medium | ✅ Done |
| 13 | Homepage title keyword | Medium | ✅ Done |
| 14 | Fix newsletter 404 | Medium | ✅ Done |
| 15 | Replace footer inline handlers | Low | ✅ Done |
| 16 | Create llms.txt | High | ✅ Done |
| 17 | Author byline on journal | Medium | ✅ Done |
| 18 | Hide empty journal section | Low | ✅ Done |
| 19 | Fix homepage newsletter form | Medium | ✅ Done |
| 20 | CSS 1-year cache | Low | ✅ Done |

---

## Task Detail

### Task 1: Fix page title bug (C-1)

`@stack('title', 'Home')` only renders `@push` content — every template uses `@section('title', ...)` which `@stack` silently ignores.

**Fix:** `resources/views/layouts/app.blade.php:6`
```blade
{{-- Before --}}
<title>@stack('title', 'Home') | Timber Trace Crafts</title>

{{-- After --}}
<title>@yield('title', 'Home') | Timber Trace Crafts</title>
```

---

### Task 2: Fix review filter bug (C-6)

Controller loads reviews `where('status', 'approved')` correctly. View re-filters using `->where('approved', true)` — column is `status`, not `approved`. Drops every review.

**Fix:** `resources/views/shop/product.blade.php:33-34,322`
```blade
{{-- Before --}}
$avgRating = $product->reviews->where('approved', true)->avg('rating');
$reviewCount = $product->reviews->where('approved', true)->count();

{{-- After --}}
$avgRating = $product->reviews->where('status', 'approved')->avg('rating');
$reviewCount = $product->reviews->where('status', 'approved')->count();
```

---

### Task 3: Canonical tags + noindex (C-3, M-4, M-5)

**Fix:** `resources/views/layouts/app.blade.php:7-9`
```blade
{{-- After --}}
<link rel="canonical" href="@yield('canonical', url()->current())">
<meta name="robots" content="@yield('robots', 'index, follow')">
```

Add `@section('robots', 'noindex, follow')` to: cart, checkout, login, register views.

---

### Task 4: XML sitemap + robots.txt (C-4)

- `app/Http/Controllers/SitemapController.php` — renders products + journal posts
- `resources/views/sitemap.blade.php` — XML template
- `routes/web.php` — `GET /sitemap.xml`
- `public/robots.txt` — disallow admin/cart/checkout/auth + Sitemap directive

---

### Task 5: OG / Twitter Card meta tags (H-3)

Added to `layouts/app.blade.php` with per-page `@yield` overrides. Product view sets `og:type=product`, journal show sets `og:type=article`.

---

### Task 6: Structured data JSON-LD (C-5)

- **Organization + WebSite**: in `layouts/app.blade.php` before `</head>`
- **Product + Offer + AggregateRating + BreadcrumbList**: `@push('schema')` in `shop/product.blade.php`
- **BlogPosting**: `@push('schema')` in `journal/show.blade.php`

---

### Task 7: Hero image optimization (C-7) — ⚠️ PARTIAL

**Code done:**
- R2 CDN preconnect in layout
- `@stack('preload')` slot in layout
- `@push('preload')` with `fetchpriority="high"` in homepage view
- Logo `<img>` tags: alt text + width/height attributes

**Manual steps still needed** — see "Remaining" section above.

---

### Task 8: Security headers (H-5)

`app/Http/Middleware/SecurityHeaders.php` — sets `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`, `Strict-Transport-Security` (on HTTPS), removes `X-Powered-By`, sets `public, s-maxage=60` cache for guest HTML responses. Registered in `bootstrap/app.php`.

---

### Tasks 9–19

All completed 2026-06-25. See Task Checklist for summary. Full implementation details were in the original plan.

---

### Content Gaps (Editorial — no code required)

| Priority | Gap | Action |
|---|---|---|
| 1 | No care instructions for wood jewelry | Add to product pages and/or a journal post |
| 2 | No waterproof/durability guidance | Add to product FAQ or description |
| 3 | No hypoallergenic hardware info | Add to earring product descriptions |
| 4 | Material education (wood vs resin) | Publish journal post |
| 5 | Personalization flow unclear | Add "How to Customize" section to product pages |
| 6 | America 250 Tumbler lacks bicentennial context | Update product description |
| 7 | No shipping timeline on product pages | Add estimated ship time to product view |
| 8 | No gift wrapping / gift message info | Add to checkout or product pages |
