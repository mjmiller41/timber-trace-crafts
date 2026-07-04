# Etsy Listing Playbook — Timber Trace Crafts

**Owner:** Founding Engineer · **Status:** living doc · **Last updated:** 2026-07-04 (TIM-4)

The repeatable, low-effort workflow for taking a laser-engraved product from idea to a
live, search-optimized Etsy listing. The technical sync machinery already exists (see
[Etsy sync design](../superpowers/specs/2026-06-24-etsy-sync-design.md) and the
`etsy:*` artisan commands); this doc is the **content + process** layer that makes each
new listing cheap and consistent.

Etsy is the **source of truth** for a listing's title, description, tags, and stock.
The local store mirrors it. Write the listing content here first (using the template
below), get founder confirmation, then create it — either directly in Etsy's editor or
by seeding the local product and running the push commands.

---

## 1. The 8-part listing template

Every new listing is authored by filling in these 8 slots. Copy the block in
[§6 Fill-in template](#6-copy-paste-fill-in-template) for each product.

| # | Slot | Rule of thumb |
|---|------|---------------|
| 1 | **Title** | Front-load the strongest keyword. Etsy weights the first ~40 chars and the whole title (max 140). Use `\|` pipes to stack keyword phrases. |
| 2 | **Tags (13)** | All 13, every time. Multi-word long-tail phrases, ≤20 chars each. Mirror how a gift buyer searches, not how a maker describes. |
| 3 | **Description** | Benefit-first opening line, then the story, then a scannable **Item Details** block, then personalization + gift framing, then the Avon Park signature. |
| 4 | **Photos (10 slots)** | Hero on white → in-use/styled → scale/size → detail/engraving → variation grid → personalization example → packaging → back/interior → sizing chart/graphic → lifestyle. |
| 5 | **Variations** | Wood species / color / size / personalization text. Each combination = a variant SKU with its own price + stock. |
| 6 | **Pricing** | Placeholder only — **founder confirms** (materials + labor + Etsy fees). Anchor to current catalog: earrings $15, tumbler $25, keepsake box $40. |
| 7 | **Category / attributes** | Taxonomy ID + shop section + made-to-order vs ready-to-ship readiness state. |
| 8 | **Shipping profile** | Reuse an existing profile (`etsy.shipping_profile_id = 303514857493`) unless size/weight differs materially. |

---

## 2. Title formula

```
[Primary keyword / product] | [Material or style qualifier] | [Occasion / gift angle] | [Audience or bonus keyword]
```

Proven live examples (keep this voice):

- `Personalized Heart Jewelry Box | Laser-Cut Wooden Keepsake Box | Wedding, Anniversary, or Valentine's Gift`
- `Solid Hardwood Butterfly Earrings | Laser-Cut Dangle Jewelry | Natural Boho | Eco-Friendly Gift`
- `Laser Etched Stainless Steel Travel Mug, 1776-2026 Eagle Patriotic Cup` (occasion-led)

Rules:
- First 2–4 words carry the search. "Personalized", "Custom", "Handmade", "Laser-Cut",
  "Solid Hardwood" are high-intent openers when true.
- Name the **occasion** ("Wedding", "Anniversary", "Valentine's", "Bridesmaid",
  "Housewarming") — gift buyers search occasions.
- No keyword stuffing past readability; Etsy penalizes and buyers bounce.

---

## 3. The 13-tag strategy

Etsy gives every listing 13 tags — **use all 13**, it is the single biggest free ranking
lever. Guidelines:

- **Multi-word, long-tail.** "wooden jewelry box" beats "jewelry". Each tag ≤ 20 chars.
- **Cover 4 buckets:** (a) what it is, (b) material/style, (c) occasion/recipient,
  (d) long-tail buyer phrases.
- Don't repeat a single word across many tags — vary the phrasing to widen the net.
- Tags should echo the title but not just duplicate it word-for-word.

Reusable tag bank by product family (pick/adjust 13):

- **Wood jewelry:** `wooden earrings`, `laser cut earrings`, `boho jewelry`,
  `lightweight earrings`, `natural wood jewelry`, `eco friendly gift`,
  `dangle earrings`, `gift for her`, `handmade jewelry`, `hardwood earrings`.
- **Keepsake / boxes:** `wooden keepsake box`, `personalized jewelry box`,
  `custom wood box`, `anniversary gift`, `wedding keepsake`, `ring box`,
  `engraved wood box`, `valentines gift`, `bridesmaid gift`.
- **Drinkware:** `engraved tumbler`, `personalized travel mug`, `laser etched cup`,
  `stainless steel mug`, `patriotic gift`, `coffee lover gift`, `custom tumbler`.
- **Home / signs:** `personalized sign`, `custom wood sign`, `housewarming gift`,
  `wall decor`, `wedding sign`, `family name sign`, `engraved wood sign`.

> **Resolved (TIM-10):** the launch catalog now stores `etsy_tags` **and** `etsy_materials`
> locally, and `etsy:sync-products` pushes both (`buildListingPayload` emits `tags` +
> `materials`). New products carry them from creation via the admin editor — **Etsy Tags**,
> **Materials**, and **Storefront Tags** fields on the product form. The one-time backfill
> for the first 6 products lives in `etsy:backfill-materials` (idempotent, data-only; run
> with `--apply`). After backfilling materials, a prod `etsy:sync-products` run pushes them
> to the live listings.

---

## 4. Description skeleton

Match the established Timber Trace voice (benefit-led, warm, specific, lightly emoji-accented):

```
[One-line hook: the benefit / feeling, not the specs.]

[1–2 sentences of story: what it is, how it's made, why it's special.]

Item Details:
* Material: [exact — e.g. 3mm Baltic Birch Plywood / 3mm Solid Hardwood / stainless steel]
* Finish: [e.g. Tried & True Danish Oil — 100% natural, zero-VOC, skin-safe]
* Size: [dimensions]
* Design: [what the laser does — engraved / cut / etched]
* Personalization: [what the buyer provides, if any — name, date, short phrase]

[Gift framing: name 2–3 occasions this suits.]

Designed and crafted with care at Timber Trace Crafts in Avon Park, Florida. 🌲
```

Keep the **Item Details** block — it scans well on mobile and feeds Etsy's attributes.

---

## 5. Photo & mockup slots (10)

Etsy allows 10 images + 1 video. Prioritize the first 3 (they drive click-through):

1. **Hero** — product on clean white/neutral, fills frame.
2. **In use / styled** — worn, on a table, held.
3. **Scale** — next to a hand/coin/common object.
4. **Detail** — close-up of the engraving/cut quality.
5. **Variations** — grid of wood species / colors offered.
6. **Personalization example** — a sample engraved name/date.
7. **Packaging** — how it arrives (gift-ready sells).
8. **Alternate angle** — back, interior (box), or reverse.
9. **Info graphic** — dimensions / care / "what you get" text overlay.
10. **Lifestyle** — in a giftable context (bridal, holiday, desk).

Mockup/personalization images can be **generated** rather than photographed — this is the
job of the design/mockup pipeline (TIM-5). Slots 6 and 9 especially are template-driven.

---

## 6. Copy-paste fill-in template

```md
### [Product working name]

**Title:** …
**Price (placeholder — founder confirms):** $…
**Category / taxonomy_id:** …    **Readiness:** made_to_order | ready_to_ship
**Variations:** [dimension: options] …

**Tags (13):**
1. …  2. …  3. …  4. …  5. …  6. …  7. …
8. …  9. …  10. …  11. …  12. …  13. …

**Description:**
[hook]

[story]

Item Details:
* Material: …
* Finish: …
* Size: …
* Design: …
* Personalization: …

[gift framing]

Designed and crafted with care at Timber Trace Crafts in Avon Park, Florida. 🌲

**Photo shot list:** hero · styled · scale · detail · variations · personalization example · packaging · alt angle · info graphic · lifestyle
```

---

## 7. Publish workflow (mechanical steps)

Two paths. **Prefer Path A** for the first listings; move to Path B once a product is
also sold on the own-store.

### Path A — author directly in Etsy (fastest, no local seeding)
1. Fill the §6 template; get founder confirmation on concept + price.
2. In Etsy: **Add a listing** → paste title, description, all 13 tags, upload the 10 photos.
3. Set price, quantity, variations, category, and shipping profile.
4. Publish. Then run `php artisan etsy:sync-orders` / rely on the live webhook for orders.
5. To mirror into the own-store later, run `php artisan etsy:link` to map it to a local product.

### Path B — seed locally, push to Etsy (for cross-channel products)
1. Create the product locally (admin panel or seeder) with description, price, variants,
   media, **`etsy_taxonomy_id`, `etsy_shipping_profile_id`, `etsy_readiness_state_id`**,
   and `etsy_materials` + tags.
2. `php artisan etsy:link` — copies taxonomy/shipping/readiness metadata from Etsy where linked.
3. `php artisan etsy:sync-products` — creates/updates the listing (POST new / PATCH existing).
4. `php artisan etsy:sync-images` — uploads photos (skips manually-uploaded unless `--force`).
5. `php artisan etsy:sync-inventory` — pushes local price/stock (⚠️ local overwrites live).
6. `php artisan etsy:diff` — verify local matches live.

> ⚠️ **Token warning (see `memory/etsy-integration.md`):** local and prod share ONE Etsy
> connection and Etsy rotates the refresh token on every use. **Prod owns the live token.**
> Do not run token-refreshing Etsy commands locally during normal ops, or you invalidate
> prod's connection (which the order webhooks depend on).

### Readiness states (Etsy)
- `1488822641920` = made_to_order, 1–3 days (personalized items, e.g. keepsake box)
- `1478211423469` = ready_to_ship, 1–2 days (stocked items, e.g. earrings, tumbler)

---

## 8. Definition of done for a new listing

- [ ] Title ≤140 chars, keyword front-loaded
- [ ] All 13 tags set, multi-word, ≤20 chars each
- [ ] Description has hook + Item Details block + gift framing + Avon Park signature
- [ ] ≥5 photos (hero + at least styled/scale/detail/variations)
- [ ] Variations + per-variant price/stock set
- [ ] Category, readiness state, shipping profile set
- [ ] **Founder confirmed concept + price**
- [ ] `etsy:diff` clean (Path B) or listing published (Path A)
```
