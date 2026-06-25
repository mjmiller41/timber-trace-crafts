# Schema Markup Audit — 2026-06-25

**Tool:** claude-seo v2.2.0 seo-schema
**URL / Scope:** https://timbertracecrafts.com (all pages, source-code analysis)
**Score:** n/a (validation pass — all critical/high issues fixed in same session)
**Previous score:** 0/100 (baseline audit, 2026-06-25 — no schema existed)

---

## Schema Detected (Post-Fix)

| Page | Schema Types | Format | Status |
|------|-------------|--------|--------|
| All pages | Organization + WebSite (`@graph`) | JSON-LD server-rendered | ✅ |
| `/product/*` | Product + Offer + BreadcrumbList | JSON-LD server-rendered | ✅ |
| `/product/*` (with reviews) | AggregateRating | JSON-LD server-rendered | ✅ |
| `/journal/*` | BlogPosting | JSON-LD server-rendered | ✅ |

---

## Issues Found & Fixed

### Fixed This Session

| Severity | Schema | Property | Fix Applied |
|---|---|---|---|
| CRITICAL | Offer | `priceValidUntil` | Added — `now()->addYear()->toDateString()` |
| HIGH | Offer | `itemCondition` | Added — `https://schema.org/NewCondition` |
| HIGH | AggregateRating | `bestRating`, `worstRating` | Added — 5 and 1 |
| HIGH | BlogPosting | `mainEntityOfPage` | Fixed from string URL to `{"@type":"WebPage","@id":"..."}` |
| MEDIUM | Product | `url` | Added |
| MEDIUM | BlogPosting | `url`, `inLanguage` | Added — `en-US` |
| MEDIUM | Organization | `description`, `email`, `contactPoint` | Added |
| INFO | BlogPosting | `image: null` | Fixed — key omitted when no featured image |

### Remaining (Requires User Input)

| Severity | Schema | Issue | Resolution |
|---|---|---|---|
| MEDIUM | LocalBusiness | No local business schema | ~~N/A — e-commerce only, no physical storefront~~ |
| HIGH | BlogPosting | `author` is Organization, not Person | ✅ Fixed — Person entity "Michael J. Miller" added to @graph, BlogPosting author wired by @id |
| INFO | WebSite | No `SearchAction` (sitelinks search box) | Deferred — low value at current traffic level |

---

## Schema Inventory (Final State)

### Organization + WebSite (every page)
```json
{
  "@context": "https://schema.org",
  "@graph": [
    {
      "@type": "Organization",
      "@id": "https://timbertracecrafts.com/#organization",
      "name": "Timber Trace Crafts",
      "url": "https://timbertracecrafts.com/",
      "description": "Handcrafted laser-cut wood earrings, jewelry boxes, and engraved tumblers made in Avon Park, Florida.",
      "email": "hello@timbertracecrafts.com",
      "logo": {"@type": "ImageObject", "url": "[logo url]"},
      "contactPoint": {"@type": "ContactPoint", "email": "hello@timbertracecrafts.com", "contactType": "customer service", "areaServed": "US"},
      "sameAs": ["[instagram]", "[facebook]", "[pinterest]"]
    },
    {
      "@type": "WebSite",
      "@id": "https://timbertracecrafts.com/#website",
      "url": "https://timbertracecrafts.com/",
      "name": "Timber Trace Crafts",
      "publisher": {"@id": "https://timbertracecrafts.com/#organization"}
    }
  ]
}
```

### Product (per product page)
- Required: name ✅, image ✅, offers ✅
- Offer: url ✅, price ✅, priceCurrency ✅, priceValidUntil ✅ (NEW), itemCondition ✅ (NEW), availability ✅, seller ✅
- AggregateRating: ratingValue ✅, reviewCount ✅, bestRating ✅ (NEW), worstRating ✅ (NEW)
- BreadcrumbList: 3 levels (Home > Shop > Product) ✅

---

## Next Steps

1. **LocalBusiness schema** — Provide street address to add `CraftBusiness` local entity
2. **Author Person entity** — Add named author to BlogPosting for E-E-A-T
3. **Rich Results Test** — Run https://search.google.com/test/rich-results on a product page to confirm pricing badge eligibility
4. **GSC monitoring** — Check Search Console → Enhancements → Products after next crawl
