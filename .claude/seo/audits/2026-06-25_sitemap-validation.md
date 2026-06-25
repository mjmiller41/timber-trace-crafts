# Sitemap Validation & Generation — 2026-06-25

**Tool:** claude-seo v2.2.0 seo-sitemap (Mode 1: Validate + patch)
**URL / Scope:** https://timbertracecrafts.com/sitemap.xml (Laravel SitemapController)
**Score:** n/a (structural fix, not scored)
**Previous score:** first run

---

## Validation Results

### ✅ Passing
- Valid XML format (DOMDocument parse: no errors)
- URL count well under 50k limit (16 URLs)
- Sitemap referenced in `public/robots.txt` with `Sitemap:` directive
- HTTPS URLs only (in production; localhost in dev)
- No noindexed URLs included (cart/checkout/admin/account all excluded)
- No redirect URLs included (`/about` redirect not in sitemap — correct)
- No sitemap index needed (16 URLs < 50k)
- RSS feed (`/journal/feed.xml`) correctly excluded
- `order-status` form page correctly excluded

### ❌ Fixed

| Issue | Severity | Resolution |
|---|---|---|
| `<changefreq>` on every URL | Info | Removed — Google ignores this tag |
| `<priority>` on every URL | Info | Removed — Google ignores this tag |
| Static pages (home, shop, journal) had no `<lastmod>` | Medium | Added — pulls from most recent product/post `updated_at` |
| `Page` model not queried — 4 policy pages missing | Medium | Fixed — SitemapController now queries `Page` model |
| URLs hardcoded as strings | Low | Fixed — now uses `route()` helper throughout |

### Pages Added by Fix

Previously missing from sitemap (now included via `Page` model query):
- `/privacy-policy`
- `/terms-and-conditions`
- `/return-policy`
- `/shipping-policy`

These are E-E-A-T trust signals for Google Shopping and organic search.

---

## Final URL Inventory (16 URLs)

| URL | Type | lastmod source |
|---|---|---|
| `/` | Static | most recent product updated_at |
| `/shop` | Static | most recent product updated_at |
| `/journal` | Static | most recent post updated_at |
| `/about-us` | Page model | page.updated_at |
| `/contact` | Page model | page.updated_at |
| `/faq` | Page model | page.updated_at |
| `/privacy-policy` | Page model | page.updated_at |
| `/return-policy` | Page model | page.updated_at |
| `/shipping-policy` | Page model | page.updated_at |
| `/terms-and-conditions` | Page model | page.updated_at |
| `/product/butterfly-earrings-design-1` | Product | product.updated_at |
| `/product/butterfly-earrings-design-2` | Product | product.updated_at |
| `/product/butterfly-earrings-design-3` | Product | product.updated_at |
| `/product/teardrop-earrings` | Product | product.updated_at |
| `/product/personalized-heart-jewelry-box` | Product | product.updated_at |
| `/product/america-250-tumbler` | Product | product.updated_at |

---

## Files Changed
- `app/Http/Controllers/SitemapController.php` — added Page model query
- `resources/views/sitemap.blade.php` — removed deprecated tags, added lastmod, added pages loop, switched to route()
