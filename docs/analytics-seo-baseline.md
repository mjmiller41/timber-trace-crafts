# Analytics + Technical SEO Baseline (TIM-6)

_Last updated: 2026-07-04 · Owner: Founding Engineer_

This is the "what is measurable and what to watch" baseline for
timbertracecrafts.com. It documents the SEO foundation that already ships and
the analytics layer wired up under TIM-6.

## 1. Technical SEO — what validates today

All of the following are live in the Laravel app and covered by tests
(`tests/Feature/SeoAnalyticsTest.php`, `ProductPageTest.php`):

| Element | Status | Where |
|---|---|---|
| `<title>`, meta description, canonical, robots meta | ✅ | `layouts/app.blade.php` (per-page overridable via `@section`) |
| Open Graph + Twitter Card tags | ✅ | `layouts/app.blade.php` |
| Organization + Person + WebSite JSON-LD (`@graph`) | ✅ | `layouts/app.blade.php` |
| Product + Offer + BreadcrumbList JSON-LD | ✅ | `shop/product.blade.php` (price, availability, condition, seller) |
| `sitemap.xml` (products, journal posts, pages) | ✅ | `SitemapController` → `/sitemap.xml` |
| `robots.txt` (crawl rules + AI-crawler policy + Sitemap ref) | ✅ | `public/robots.txt` |
| `llms.txt` | ✅ | `public/llms.txt` |
| Journal RSS feed | ✅ | `/journal/feed.xml` |
| Bing IndexNow submission | ✅ | `seo:indexnow` command |

**Validation:** run `php artisan test --compact tests/Feature/SeoAnalyticsTest.php`.
For external confirmation once deployed, validate a product URL in Google's
[Rich Results Test](https://search.google.com/test/rich-results) and submit the
sitemap in Google Search Console + Bing Webmaster Tools (needs CEO account
access — see §4).

## 2. Analytics — how it works

Two complementary layers, both privacy-respecting and zero recurring cost:

### a) Client-side: Umami (page views + engagement)
- Cookieless, GDPR-friendly, no consent banner required.
- Renders **only** when `UMAMI_WEBSITE_ID` is set (`config/analytics.php`), so
  the site ships zero third-party tracking until credentials exist.
- Free via Umami Cloud (free tier) or self-hosted.

### b) Server-side: first-party funnel log (source of truth for revenue)
- Written by `App\Support\Analytics::record()` to a dedicated daily log channel
  → `storage/logs/analytics.log` (90-day retention).
- **Ad-blocker-proof**: captures conversions even when client JS is blocked.
- Records only non-PII attributes (ids, counts, amounts) — never names/emails.

## 3. Funnel events (the key events to watch)

| Event | Fires when | Client (Umami) | Server log |
|---|---|---|---|
| `add_to_cart` | Item added to cart | `data-umami-event` on button | `CartController@add` |
| `begin_checkout` | Checkout page opened | `data-umami-event` on CTA | `CheckoutController@index` |
| `purchase` | Order confirmation shown | `umami.track()` on confirmation | `CheckoutController@confirmation` (once per order/session) |

The canonical list lives in `App\Support\Analytics::EVENTS`; keep client tags,
server calls, and this table in sync.

## 4. First metrics to watch

Once traffic starts (post-launch / post first Etsy → site referrals):

1. **Sessions & top landing pages** (Umami) — is organic search finding us?
2. **Add-to-cart rate** = `add_to_cart` ÷ sessions — product page persuasion.
3. **Checkout start rate** = `begin_checkout` ÷ `add_to_cart` — cart friction.
4. **Purchase conversion rate** = `purchase` ÷ sessions — the headline number.
5. **Cart→purchase drop-off** = 1 − (`purchase` ÷ `begin_checkout`) — checkout UX.
6. **Revenue per session** = Σ purchase `value` ÷ sessions — ties to the
   $1,000/mo goal.

Server-side counts are the trustworthy denominator for #2–#6; Umami provides the
session/traffic numerator and channel breakdown.

## 5. What needs CEO action (escalations)

None of these block the code above — the funnel already records server-side.

- **Umami website provisioning** — create a free Umami Cloud site for
  `timbertracecrafts.com` (or approve self-hosting), then set `UMAMI_SCRIPT_URL`
  + `UMAMI_WEBSITE_ID` in production `.env`. This turns on the client/traffic layer.
- **Google Search Console + Bing Webmaster Tools** — verify the domain and
  submit `sitemap.xml` so indexation and search queries become measurable.
- (Optional, later) **GA4** — only if a heavier analytics stack is wanted; Umami
  covers the privacy-first baseline without it.
