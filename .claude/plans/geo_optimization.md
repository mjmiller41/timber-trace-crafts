# GEO / AI Search Optimization Plan

**Baseline score:** 52/100 (2026-06-25)
**Target score:** 75/100
**Audit source:** `.claude/seo/audits/2026-06-25_geo-audit.md`

---

## Task Checklist

### Phase 1 — Code & Content (can do now)

- [ ] Write FAQ page content (10-15 Q&A entries covering materials, shipping, custom orders, care)
- [ ] Rewrite product description openers — remove emoji/promo intro, lead with 40-60 word factual block
- [ ] Add question-based H2s to the about-us page ("What makes Timber Trace Crafts different?", "What materials does Timber Trace Crafts use?", etc.)
- [ ] Add a 134-167 word "What is laser-cut woodworking?" definition block to about-us — front-loaded above the fold
- [ ] Add a wood materials comparison table to about-us (Maple vs Baltic Birch vs Basswood — grain, durability, finish)
- [ ] Add `dateModified` meta signal: ensure all Page/JournalPost `updated_at` is touched when content is refreshed
- [ ] Submit site to Bing IndexNow for Bing Copilot indexation acceleration

### Phase 2 — Content Publishing

- [ ] Publish first journal post with answer-first structure (40-60 word factual opener per H2)
- [ ] Publish second journal post — include at least one data table or comparison
- [ ] Publish third journal post — use question-based H2s throughout
- [ ] Write and publish a "How laser-cut wood earrings are made" pillar post (1,500+ words, step-by-step)

### Phase 3 — Off-Site Brand Presence

- [ ] Create YouTube channel "Timber Trace Crafts" — post first studio process video (laser cutting demo)
- [ ] Post second YouTube video (hand-finishing or inlay process)
- [ ] Create LinkedIn company page for Timber Trace Crafts
- [ ] Participate in r/woodworking — share a genuine behind-the-scenes post (not a link drop)
- [ ] Participate in r/crafts — answer a question about laser cutting or wood finishing
- [ ] Add YouTube and LinkedIn URLs to `social.*` settings in admin and to Organization `sameAs` schema

### Phase 4 — Schema & Entity

- [ ] Add YouTube channel URL and LinkedIn URL to Organization `sameAs` array in `layouts/app.blade.php`
- [ ] Add `jobTitle` and `description` to the Person entity for Michael J. Miller in the global schema
- [ ] Add `SiteLinksSearchBox` schema (SearchAction) once site traffic justifies it

---

## Full Plan

### Context

GEO (Generative Engine Optimization) is AI search readiness — the factors that determine whether ChatGPT, Perplexity, Google AI Overviews, and Bing Copilot cite timbertracecrafts.com when users ask about handmade wood jewelry, laser-cut woodcrafts, or veteran-owned artisan businesses.

The June 2026 audit scored 52/100. Technical foundations are solid (full SSR, explicit AI crawler rules, Person schema, llms.txt). The ceiling is content and off-site brand presence.

**Key insight from the Ahrefs 75k-brand study:** brand mentions correlate 3× more strongly with AI citations than backlinks. YouTube presence alone has a 0.737 citation correlation — higher than domain authority.

---

### Phase 1 — Code & Content (highest ROI, no new platforms needed)

#### 1. FAQ Page Content

The FAQ page exists in the database (`/faq`) but has empty content. This is the single easiest win: structured Q&A is the format AI systems cite most readily.

**Target Q&A topics:**
- What materials does Timber Trace Crafts use?
- How are the earrings made?
- Are all products handmade?
- Do you offer custom or personalized orders?
- How long does shipping take?
- What is your return policy?
- How do I care for wooden jewelry?
- Are your products safe for sensitive skin?
- What makes laser-cut different from hand-carved?
- Do you ship internationally?

Each answer: 40-80 words, factual, self-contained. No marketing language.

#### 2. Product Description Openers

Current openers start with emoji + promotional language. AI systems don't cite promo copy.

**Fix pattern:**
- Remove emoji line
- Lead with a factual sentence: "The [Product Name] is a handcrafted [material] [type], precision laser-cut and hand-finished in Avon Park, Florida."
- Follow with materials, dimensions, use case

#### 3. About-Us Question Headings

Current H2s: "Built on Dedication, Precision, and Genuine Artistry", "Where Precision Meets Tradition"  
These are brand statements. AI systems match headings to query patterns.

**Replacement targets:**
- "What is Timber Trace Crafts?" (definition block, 134-167 words)
- "What materials does Timber Trace Crafts use?" (leads to materials table)
- "Who founded Timber Trace Crafts?" (Michael J. Miller bio)
- "What makes laser-cut wood jewelry different?" (process explainer)

#### 4. Wood Materials Comparison Table

Tables are cited at 2× the rate of prose for comparative queries. A Maple vs Baltic Birch vs Basswood table (grain, weight, finish quality, best use case) positions the about-us page for "what wood is best for jewelry" and similar queries.

#### 5. Bing IndexNow

IndexNow is a single API call that pushes URLs to Bing's index. Bing Copilot draws from Bing's index. Takes 15 minutes and accelerates citation eligibility on that platform.

---

### Phase 2 — Content Publishing

Journal posts are the freshness signal that drives Google AI Mode citation (content under 3 months old is 3× more likely to be cited). Three published posts with answer-first structure significantly improves AI Mode visibility.

**Answer-first structure rule (every H2 section):**
1. Question-based H2 (matches real search queries)
2. 40-60 word direct answer — the citation capsule
3. Elaboration paragraphs
4. Data point or source attribution where possible

**Post ideas with high citation potential:**
- "How laser-cut wood earrings are made: a step-by-step process" (process authority)
- "Wood types for handmade jewelry: Maple, Baltic Birch, and Basswood compared" (leverages materials table)
- "What is a diode laser and how is it used in woodworking?" (definition play, captures AI Mode)
- "Veteran-owned small businesses: why Michael J. Miller started Timber Trace Crafts" (brand story, E-E-A-T)

---

### Phase 3 — Off-Site Brand Presence

88-92% of AI citations come from off-site signals. The three most impactful platforms for this business:

#### YouTube (0.737 citation correlation — highest of any platform)

- Short "process" videos perform best: laser engraving in action, hand-finishing, inlay work
- Even 60-second clips build the brand entity YouTube needs to associate with the topic
- Title format: "How I make laser-cut wood earrings | Timber Trace Crafts"
- No production budget needed — a phone camera showing the laser in action is sufficient

#### Reddit (46.7% of Perplexity citations)

- r/woodworking (3.2M members) — "Here's a piece I finished this week" posts with process detail
- r/crafts — answer questions about laser cutting, wood finishing, or earring making
- r/etsy — share behind-the-scenes; respond to "looking for handmade earrings" posts
- Rule: never post links as the primary content. Be genuinely useful first.

#### LinkedIn

- Company page takes 30 minutes to set up
- Moderate citation signal (B2B platforms favor LinkedIn; craft/consumer brands see less impact)
- Still worth having as an entity anchor for Google's Knowledge Graph

---

### Phase 4 — Schema & Entity Cleanup

Once YouTube and LinkedIn are live, add those URLs to the Organization `sameAs` array. This directly updates Google's entity graph for the brand.

The Person entity for Michael J. Miller currently has `name`, `url`, `worksFor`. Adding `jobTitle: "Founder & Artisan"` and a short `description` strengthens the E-E-A-T entity signal for the author of journal posts.

---

### Success Metrics

| Metric | How to measure | Target |
|---|---|---|
| GEO score | Re-run `/seo geo` | 75/100 |
| Perplexity citation | Search "handmade wood earrings artisan" | Site cited within 60 days of YouTube launch |
| ChatGPT citation | Search "veteran-owned woodcraft jewelry" | Site cited within 90 days |
| Google AI Mode | GSC → Discover → AI Overview impressions | Appears within 30 days of 3 journal posts |
| Bing Copilot | Search brand name | Indexed and cited within 2 weeks of IndexNow |

---

### Dependency Order

```
FAQ content (Phase 1) ──────────────────────────────────────────▶ can publish now
Product description fix (Phase 1) ──────────────────────────────▶ can do now
About-us heading rewrite (Phase 1) ─────────────────────────────▶ can do now
Journal posts (Phase 2) ─────────────────────────────────────────▶ can start now
YouTube channel (Phase 3) ──────────────────────────────────────▶ requires brand decision
Reddit presence (Phase 3) ──────────────────────────────────────▶ can start now (no account needed beyond existing)
Schema update (Phase 4) ────── depends on ──────── YouTube/LinkedIn URLs live
```

Everything in Phase 1 and the journal writing in Phase 2 can be done without any new platform accounts. YouTube is the single off-site action with the highest citation payoff.
