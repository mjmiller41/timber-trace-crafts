# Timber Trace Crafts — Editorial Calendar & Content Engine

_Owner: Growth & Content Marketer. Source of truth for the organic-growth content program (issue TIM-16, parent TIM-11). Aligned to `TASKS.md` "Someday" content plan and the company goal: $1,000/mo revenue by end of 2026, grown organically._

## 1. Strategy in one paragraph

Build **topic authority** around personalized laser-engraved wood gifts using a hub-and-spoke model: a few deep **pillar** posts (evergreen, 2,500–3,500 words) each surrounded by shorter **spoke** posts (buying guides, FAQs, comparisons) that link up to their pillar and across to real product pages. Every post is written **answer-first** (a 40–60 word factual opener under each H2) so it ranks in Google *and* gets cited by AI answer engines (ChatGPT, Perplexity, Google AI Overviews). We measure the funnel (impressions → clicks → sessions → add-to-cart) and AI-citation appearances monthly, then adjust angle.

## 2. Content clusters (hub → spokes)

| Cluster | Pillar (hub) | Spokes | Primary products it sells |
|---|---|---|---|
| **A. Personalized gifts** | Ultimate Guide to Personalized Laser-Engraved Gifts (~3,500w) | How laser engraving personalization works · What can/can't be laser engraved · Laser engraving vs. laser cutting | Jewelry box, tumbler, all |
| **B. Wood earrings** | The Complete Guide to Wood Earrings (~3,000w) | How wood earrings are made · Wood vs. metal for sensitive ears · Are wood earrings hypoallergenic? · Can you wear them in the shower? | Butterfly & teardrop earrings ($15) |
| **C. How it's made** | How Laser Cutting & Engraving Works (~3,000w) | (feeds A & B; establishes E-E-A-T / maker credibility) | All |
| **D. Gift guides (seasonal hub)** | Gift Guide Hub + seasonal spokes | Best handmade gifts for women (LIVE) · Valentine's · Mother's Day · "woman who has everything" · wedding party gifts | All, by occasion |
| **E. Brand & trust** | Why We Started Timber Trace Crafts (brand story) | Care guide (LIVE page) · FAQ spokes (tumbler dishwasher-safe, etc.) | All |

## 3. Internal-link architecture (real URLs — route is singular `/product/{slug}`)

Every post links to its pillar, 1–2 sibling spokes, and 2–4 product pages. Canonical targets:

- Jewelry box → `/product/personalized-heart-jewelry-box-laser-cut-wooden-keepsake-box-wedding-anniversary-or-valentines-gift`
- Butterfly earrings → `/product/solid-hardwood-butterfly-earrings-laser-cut-dangle-jewelry-natural-boho-eco-friendly-gift` (variants `-2`, `-3`)
- Teardrop earrings → `/product/solid-hardwood-teardrop-earrings-laser-cut-dangle-jewelry-natural-boho-eco-friendly-gift`
- Tumbler → `/product/america-250-tumbler-laser-etched-stainless-steel-travel-mug-1776-2026-eagle-patriotic-cup`
- Shop all → `/` · Journal hub → `/journal` · Care guide → `/care-guide`
- Published pillar spoke → `/journal/best-handmade-gifts-for-women`

> Do NOT use `/products/...` (plural) or short invented slugs — they 404. Always the singular `/product/` + the full real slug above.

## 4. Answer-first rotation (per the TASKS.md publish spec)

To vary structure across the Month-1 batch and cover multiple AI-citation formats:
1. **Post 1** — 40–60 word factual opener under every H2 (definitional, citation-friendly).
2. **Post 2** — includes a data table / comparison (tables get pulled into AI Overviews and featured snippets).
3. **Post 3** — question-based H2s throughout ("How does…", "What is…") for AEO/People-Also-Ask capture.

## 5. Publishing pipeline (how a post actually goes live)

1. Draft markdown → `.claude/blog/posts/{slug}.md` with frontmatter (`title, slug, excerpt, meta_title, meta_description, tags: [..], status, published_at`). H1 is auto-rendered from `title` — do **not** add an H1 in the body.
2. Run **`/blog analyze`** → must score **80+** before publish. Iterate if under.
3. Commit on a content branch (path-scoped, never `-A`/`-a` — shared working tree).
4. Founding Engineer merges to `main` + deploys; admin **Journal → Import** ingests the draft (auto-creates tags), then attaches the **featured image** from the Media Library and sets status/published_at.
5. Post-publish: pin to Pinterest w/ cover image + excerpt; repurpose (see §7); add to AI-citation tracker (§6).

## 6. Monthly AI-citation tracking (10 target queries)

Run on ~the 1st of each month in **ChatGPT** and **Perplexity** (free tiers); log whether timbertracecrafts.com is cited/linked, and which competitors are. Template: `docs/content/ai-citation-tracker.md`.

Target queries:
1. best personalized laser engraved gifts
2. are wood earrings hypoallergenic
3. wood vs metal earrings for sensitive ears
4. how does laser engraving personalization work
5. what materials can be laser engraved
6. laser engraving vs laser cutting difference
7. best handmade gifts for women who appreciate details
8. how are wood earrings made
9. personalized wooden jewelry box ideas
10. are laser engraved tumblers dishwasher safe

Action loop: if we're not cited on a query our content targets → check passage-level citability (crisp 40–60w definitional answer, named stat + source, clear entity naming "Timber Trace Crafts"), then rewrite the weak passage and request re-index.

## 7. Distribution per post (compounding reach)

On publish day, every post → Pinterest pin (cover + excerpt) → 1 Twitter/X thread OR LinkedIn post → newsletter mention. Pillars additionally → a Reddit value-first comment/post where relevant (r/woodworking, r/crafts — never a link drop) and a YouTube short/process clip when footage exists. Use `/blog repurpose`.

## 8. Schedule

**Month 1 (batch — TIM-16, publish together):**
- P1 · Pillar A — *Ultimate Guide to Personalized Laser-Engraved Gifts* — answer-first openers
- P2 · Pillar B — *The Complete Guide to Wood Earrings* — data table/comparison
- P3 · Pillar C — *How Laser Cutting & Engraving Works* — question-based H2s

_(Pillar D spoke "Best Handmade Gifts for Women" is already LIVE and seeds cluster D.)_

**Month 2:** Gift Guide Hub (Pillar D) + next seasonal spoke; Wood-earrings spokes (hypoallergenic; wood vs. metal); brand story.
**Month 3:** Personalization spokes (how it works; what can/can't be engraved; cut vs. engrave); FAQ spokes (shower, dishwasher); comparison posts. Refresh Month-1 pillars (30-day freshness signal for AI citation).

**Cadence:** ~1 post/week. Every post passes the gate in §5.2 (score 80+, real sourced stats, internal links, tags, SEO meta).
