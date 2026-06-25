# Full SEO Audit — 2026-06-25

**Tool:** claude-seo v2.2.0 (10 parallel subagents)
**URL / Scope:** https://timbertracecrafts.com (full site)
**Score:** 40/100
**Previous score:** first run

---

# Timber Trace Crafts — Full SEO Audit Report
**URL:** https://timbertracecrafts.com | **Date:** 2026-06-25 | **Stack:** Laravel 13, PHP 8.3
**Plugin:** claude-seo v2.2.0 (agricidaniel) | **Agents run:** 10 parallel subagents

## SEO Health Score: 40 / 100

| Category | Weight | Score | Weighted |
|---|---|---|---|
| Technical SEO | 22% | 48/100 | 10.6 |
| Content Quality | 23% | 61/100 | 14.0 |
| On-Page SEO | 20% | 30/100 | 6.0 |
| Schema / Structured Data | 10% | 0/100 | 0.0 |
| Performance (CWV) | 10% | 35/100 | 3.5 |
| AI Search Readiness | 10% | 34/100 | 3.4 |
| Images | 5% | 45/100 | 2.3 |
| **TOTAL** | | | **40/100** |

---

## CRITICAL — Fix Immediately

### C-1: `@stack` vs `@yield` Title Bug — ALL Pages Show "Home | Timber Trace Crafts"
- `layouts/app.blade.php` line 6 uses `@stack('title', 'Home')` but every template uses `@section('title', ...)`
- `@stack` only renders `@push` content — `@section` is ignored, so every page title is "Home | Timber Trace Crafts"
- **Fix (2 min):** Change to `@yield('title', 'Home')` in `layouts/app.blade.php`
- **Verify:** Browser devtools → `<title>` on any product page

### C-2: www/Non-www Duplicate Site — PageRank Split
- `https://www.timbertracecrafts.com` returns HTTP 200 with full content (not redirected)
- Two live copies of every URL, splitting all PageRank and crawl budget
- **Fix:** 301 redirect `https://www.*` → `https://*` at Hostinger hPanel/DNS level
- **Verify:** `curl -I https://www.timbertracecrafts.com` should return 301

### C-3: No Canonical Tags on Any Page
- Layout has `@hasSection('canonical')` but no template ever sets `@section('canonical', ...)`
- Shop filter URLs (`?category=jewelry`, `?sort=newest`, `?page=2`) all crawlable with no canonical
- **Fix:** Add default self-referencing canonical to `layouts/app.blade.php` head

### C-4: No XML Sitemap
- `/sitemap.xml` returns 404, no `Sitemap:` directive in `robots.txt`
- Six product pages + content pages discovery-dependent on link crawling only
- **Fix:** Add `SitemapController` route at `/sitemap.xml`, update `public/robots.txt`

### C-5: Zero Structured Data — No Rich Results, No Google Shopping, No AI Citations
- Not a single `<script type="application/ld+json">` block exists on any page
- Blocks Product rich results, Google Shopping free listings, AI Overview eligibility

### C-6: Review Filter Bug — Zero Reviews Ever Display
- `product.blade.php` re-filters with `->where('approved', true)` but column is `status`, not `approved`
- Controller correctly loads `where('status', 'approved')` but view drops all results

### C-7: Hero Image 2.56 MB PNG — LCP Killer
- `lifestyle-1.png` on R2 CDN: 2.56 MB, no WebP, no `fetchpriority="high"`, no `<link rel="preload">`
- R2 serves ALL product images with zero `Cache-Control` headers

---

## HIGH — Fix Within 1 Week

| # | Finding | File | Fix |
|---|---|---|---|
| H-1 | Product description truncated at 400 chars | `shop/product.blade.php` line 179 | Remove `Str::limit($product->description, 400)` |
| H-2 | No indexable category pages — only `?category=` query strings | Routes | Create `/collections/jewelry`, `/collections/jewelry-boxes`, `/collections/tumblers` |
| H-3 | No OG/Twitter Card meta tags | `layouts/app.blade.php` | Add `og:title`, `og:description`, `og:image`, `og:type` |
| H-4 | Google Fonts synchronous render-blocking | `layouts/app.blade.php` line 15 | Self-host via Fontsource or async load; trim from 9 weights to 4 |
| H-5 | Missing security headers + PHP version exposed | Hostinger hPanel / middleware | Add HSTS, X-Content-Type-Options, X-Frame-Options |
| H-5b | `Cache-Control: no-cache, private` on all public HTML | Laravel middleware | Set `Cache-Control: public, s-maxage=60` for guest responses |
| H-6 | No `llms.txt` file | `/public/llms.txt` | Create with brand facts, founder bio, product list, content permissions |
| H-7 | Etsy sync creates drafts only — listings never live | `EtsyProductSync.php` | Change `'state' => 'draft'` to `'state' => 'active'` |

---

## MEDIUM — Fix Within 1 Month

| # | Finding |
|---|---|
| M-1 | Homepage title "Home" → keyword-bearing string |
| M-2 | Wood species inconsistency across pages |
| M-3 | `/about` returns 404 — add redirect to `/about-us` |
| M-4 | Cart/login/register need `<meta name="robots" content="noindex, follow">` |
| M-5 | Shop `?tag=` filter URLs crawlable with no canonical |
| M-6 | No author byline on journal posts |
| M-7 | No physical address/phone in footer in crawlable format |
| M-8 | Etsy seller rating not surfaced on product pages |
| M-9 | America 250 Tumbler underpriced — $20 vs $28–45 Etsy median |
| M-10 | No YouTube presence |
| M-11 | `/newsletter` route referenced in footer but doesn't exist |
| M-12 | Product gallery image alt text identical for all images |
| M-13 | No `width`/`height` on hero image or logo — CLS risk |

---

## LOW — Backlog

| # | Finding |
|---|---|
| L-1 | `X-Powered-By: PHP/8.3.30` exposed |
| L-2 | Logo `alt=""` across 3 instances |
| L-3 | No founding year on About page |
| L-4 | `priceValidUntil` on sale prices needs `sale_price_ends_at` DB column |
| L-5 | CSS bundle cached only 7 days — safe to set 1-year cache |
| L-6 | IndexNow not implemented |
| L-7 | Inline `onmouseover`/`onmouseout` handlers in footer |
| L-8 | Journal section on homepage should be hidden when zero posts exist |
| L-9 | Duplicate newsletter form — homepage version uses `action="#"` |

---

## Sub-Agent Scores

| Agent | Score |
|---|---|
| seo-technical | 48/100 |
| seo-content (E-E-A-T) | 61/100 |
| seo-schema | 0/100 |
| seo-sitemap | Critical — no sitemap exists |
| seo-performance | ~35/100 |
| seo-geo | 34/100 |
| seo-sxo | 38/100 |
| seo-ecommerce | 42/100 |
| seo-backlinks | 0 links confirmed |
| seo-cluster | 5 clusters, 12 posts, greenfield |

---

## Status as of 2026-06-25

All 19 items from the seo_optimization plan were completed this date.
See `.claude/plans/seo_optimization_complete.md` for the full remediation record.
