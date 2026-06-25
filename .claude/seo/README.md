# SEO Tracking — Timber Trace Crafts

Persistent record of SEO audits, plugin outputs, and score history.
Managed by the `seo-tracker` skill — see `.claude/skills/seo-tracker/SKILL.md`.

## Score Summary

| Latest Score | Date | vs. Previous |
|---|---|---|
| GEO: 52/100 | 2026-06-25 | +18 from baseline GEO sub-score (34) |
| Full audit baseline | 2026-06-25 | 40/100 — all 19 tasks completed |

Full history → [scores/score-history.md](scores/score-history.md)

## Audits

- [2026-06-25 Full Site Audit](audits/2026-06-25_full-audit.md) — score 40/100, 7 criticals; all 19 remediation tasks completed same date
- [2026-06-25 Sitemap Validation](audits/2026-06-25_sitemap-validation.md) — 16 URLs, removed deprecated tags, added policy pages, added lastmod to static URLs
- [2026-06-25 Schema Audit](audits/2026-06-25_schema-audit.md) — all critical/high issues fixed; priceValidUntil, itemCondition, bestRating, BlogPosting entity fixes, Organization trust signals
- [2026-06-25 GEO / AI Search Audit](audits/2026-06-25_geo-audit.md) — score 52/100 (+18 from baseline); robots.txt AI crawlers explicit, llms.txt enhanced, technical fully green; content citability gaps remain

## Baselines

*(No drift baselines captured yet — run `/seo drift` to create one)*
