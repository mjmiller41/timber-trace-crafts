# AI-Citation Tracker — Timber Trace Crafts

**Owner:** Growth & Content Marketer · **Parent issue:** TIM-16 · **Recurring issue:** TIM-22
**Cadence:** monthly, ~1st of month · **Method default:** free-tier **manual** runs (see "Cost" below)

## Purpose

Track whether AI answer engines (ChatGPT with web browsing, Perplexity) **cite or mention
timbertracecrafts.com** when a real person asks the 10 questions our content is built to answer.
AI citations are a leading indicator of the organic-discovery funnel that feeds the $1,000/mo goal;
we tie any lift to **Umami sessions** (see "Measurement").

## How to run a monthly pass (free-tier, manual)

For each of the 10 target queries:

1. **ChatGPT** — new chat, **web browsing ON**, paste the query verbatim. Record the result.
2. **Perplexity** — free tier, paste the query verbatim. Record the result.
3. Score each platform: **`cited`** (our URL appears as a linked source) · **`mentioned`**
   (brand/name referenced, no link) · **`absent`** (neither).
4. Note the **competitors** cited and the **answer format** the engine used (listicle, comparison
   table, prose, product grid) — this tells us what shape of content wins the slot.
5. Append a new dated `## Run — YYYY-MM` section (copy the table template) with the results.
6. **For every `absent`/`mentioned` on a query we OWN** (a post is published for it): tighten that
   post's **answer capsule** (front-load a 40–60-word self-contained answer under a question H2),
   then request re-index — `php artisan seo:indexnow <url>` (IndexNow already wired) and, for
   Google, GSC URL-inspect → Request indexing.
7. Log the deltas vs. the previous run and the Umami session trend for the owning pages.

**Do not fabricate results.** If a run wasn't actually executed against the live engines, mark the
section `PENDING` — never invent citations, competitors, or scores.

## Cost / tooling note (flagged to CEO)

Fully automated tracking (scripted ChatGPT + Perplexity queries) requires **paid API keys**
(OpenAI API + Perplexity API, ~$5–20/mo combined at low volume) or a paid rank-tracker with an
AI-Overviews add-on. **Default is free-tier manual runs** — no spend. Escalate to CEO before
committing to any paid tool; the manual method is sufficient at our current query volume (10/mo).

## The 10 target queries → owning posts

Queries are phrased the way people actually ask an AI assistant. "Owning post" is the page whose
answer capsule should win the citation. `Status` reflects whether that post is **live/indexable yet**.

| # | Target query (as asked to AI) | Owning post (target keyword) | Pillar | Post status |
|---|-------------------------------|------------------------------|--------|-------------|
| 1 | "What are the best handmade gifts for a woman who appreciates the details?" | The Best Handmade Gifts for Women (`handmade gifts for women`) | P2 | **Draft** (not live) |
| 2 | "How does laser cutting and engraving actually work?" | How Laser Cutting & Engraving Works (`how laser cutting works`) | P6 pillar | Planned |
| 3 | "What's the difference between laser engraving and laser cutting?" | Laser engraving vs. laser cutting (`laser engraving vs laser cutting`) | P1.7 | Planned |
| 4 | "Are wood earrings hypoallergenic / okay for sensitive ears?" | Are wood earrings hypoallergenic? (`are wood earrings hypoallergenic`) | P3.3 | Planned |
| 5 | "Can you wear wood earrings in the shower — do they get ruined if wet?" | Can wood earrings get wet? (`can wood earrings get wet`) | P3.2 | Planned |
| 6 | "What materials can a diode laser cut and engrave?" | What a diode laser can cut/engrave (`materials for laser engraving`) | P6.1 | Planned |
| 7 | "Are laser-engraved / personalized tumblers dishwasher safe?" | Are engraved tumblers dishwasher safe? (`engraved tumbler dishwasher safe`) | P5.2 | Planned |
| 8 | "Does laser engraving fade or wear off over time?" | Why laser engraving doesn't fade (`does laser engraving fade`) | P6.3 | Planned |
| 9 | "Where can I buy veteran-owned handmade wood jewelry or laser crafts?" | About / brand-story (veteran-owned entity) | Brand | **Live** (about page) |
| 10 | "What are unique personalized gifts for men that aren't cheesy?" | Personalized gifts for men (`personalized gifts for men`) | P1.5 | Planned |

> Query 9 is our **soonest-winnable** slot — the About page is already live with the
> veteran-owned/Desert Storm entity facts. Everything else is gated on the Month-1 publish batch
> (TIM-16) going live and getting indexed.

## Run — 2026-07 (BASELINE)

**Run type:** baseline snapshot · **Engines queried live:** NOT YET (see note) · **GEO score at baseline:** 52/100 (2026-06-25 audit)

**State of content at baseline:** No journal posts are live/indexed. One post
(`best-handmade-gifts-for-women`) is in **draft**; the Month-1 batch (TIM-16) is **blocked/not yet
published**. Therefore the honest baseline for all content-owned queries is **`absent`** — we have
no indexed page for the engine to cite. The 2026-06-25 GEO audit independently confirms ChatGPT
❌ not cited and Perplexity ❌ not cited for brand/category queries. This section records the
**"before" line** so the first real run (post-batch-live) can be measured against zero.

| # | Query | ChatGPT | Perplexity | Competitors seen | Format | Notes |
|---|-------|---------|------------|------------------|--------|-------|
| 1 | Best handmade gifts for women | absent | absent | — | — | Post in draft; not indexable |
| 2 | How laser cutting/engraving works | absent | absent | — | — | Post not written |
| 3 | Laser engraving vs cutting | absent | absent | — | — | Post not written |
| 4 | Wood earrings hypoallergenic | absent | absent | — | — | Post not written |
| 5 | Wood earrings in the shower | absent | absent | — | — | Post not written |
| 6 | Materials a diode laser cuts | absent | absent | — | — | Post not written |
| 7 | Engraved tumblers dishwasher safe | absent | absent | — | — | Post not written |
| 8 | Does laser engraving fade | absent | absent | — | — | Post not written |
| 9 | Veteran-owned handmade wood jewelry | absent | absent | — | — | About page live but not yet cited/indexed for this query |
| 10 | Personalized gifts for men | absent | absent | — | — | Post not written |

**Baseline summary:** 0/10 cited, 0/10 mentioned, 10/10 absent. Nowhere to go but up.

## Run — YYYY-MM (TEMPLATE — copy for each real run)

**Run type:** monthly · **Engines queried live:** yes/no · **Runner:** _____

| # | Query | ChatGPT | Perplexity | Competitors seen | Format | Action taken (capsule tighten / reindex) |
|---|-------|---------|------------|------------------|--------|------------------------------------------|
| 1 | … | | | | | |

**Deltas vs. prior run:** _____
**Umami session trend (owning pages):** _____
**Misses actioned (capsule tightened + reindex requested):** _____

## Measurement — tie citation lift to Umami

- Watch **Umami sessions** and **top landing pages** for the owning pages (journal posts + About).
  A new citation in ChatGPT/Perplexity should show as referral/organic sessions to that page.
- Cross-reference with the funnel events in `App\Support\Analytics::EVENTS`
  (`add_to_cart` → `begin_checkout` → `purchase`) to see whether AI-sourced sessions convert.
- **Dependency:** Umami must be provisioned in prod (`UMAMI_WEBSITE_ID`) — tracked in
  `docs/analytics-seo-baseline.md`. Until then, session lift is measured server-side only.

## First real run — gating

The **first real run** is due once the **Month-1 publish batch (TIM-16) is live and indexed**, so
the batch posts are actually citable. TIM-16 is currently **blocked**; when it publishes, run the
next monthly pass against live engines and replace the applicable `absent` baseline rows with real
results.
