# First Batch — Publish-Ready Etsy Listings

**Owner:** Founding Engineer · **Status:** approved, ready to publish · **Date:** 2026-07-04 (TIM-4)

## Approval

The CEO/board **accepted** the TIM-4 `request_confirmation` (2026-07-04) approving the
recommended first batch at the proposed pricing — concepts **#1 Cutting Board, #2 Coaster
Set, #3 Ornament** from [product-concepts-2026.md](./product-concepts-2026.md). No price or
selection overrides were supplied, so the placeholder ranges are finalized to the concrete
launch prices below (adjustable by the founder at publish time).

Each package below is complete Etsy listing content authored per the
[Listing Playbook](./listing-playbook.md). **The only thing left before publishing is
photos** — physical production / TIM-5 mockups — and publishing in the **prod** admin
(Etsy token is owned by prod; see `memory/etsy-integration.md`). Use Playbook **Path A**
(author directly in Etsy) for these.

Category attributes to confirm at publish (taxonomy_id + shop section) are per-product;
reuse shipping profile `303514857493` unless size/weight differs.

---

## Listing 1 — Personalized Cutting Board

- **Final launch price:** Small **$28** · Medium **$36** · Large **$45**
- **Quantity/stock:** made-to-order (set per Etsy) · **Readiness:** `made_to_order` 1–3 days (`1488822641920`)
- **Variations:**
  - Size: Small / Medium / Large  → drives price ($28 / $36 / $45)
  - Wood: Maple / Walnut / Cherry  (no upcharge)
  - Personalization text (buyer-supplied, required)
- **Title:** `Personalized Cutting Board | Engraved Hardwood Charcuterie Board | Wedding, Housewarming & Anniversary Gift`
- **Tags (13):** cutting board · personalized gift · engraved board · charcuterie board · wedding gift · housewarming gift · custom wood board · anniversary gift · kitchen gift · closing gift · hardwood board · bridal shower gift · gift for couple
- **Description:**

> Make every meal feel like a celebration with a cutting board engraved just for them. 🌿
>
> Each board is precision laser-engraved with your custom text — a family name, a wedding date, a favorite recipe, or a heartfelt note — into solid, food-safe hardwood. The design is burned permanently into the grain, so it never peels or fades, and the natural wood tones make it as beautiful on the counter as it is useful in the kitchen.
>
> Item Details:
> * Material: Solid hardwood (Maple, Walnut, or Cherry)
> * Finish: Food-safe mineral oil / beeswax, hand-rubbed
> * Size: Small, Medium, or Large (dimensions in the listing photos)
> * Design: Permanent laser-engraved text and artwork
> * Personalization: Add your name, date, or short phrase at checkout
>
> A standout gift for weddings, housewarmings, anniversaries, closings, and bridal showers — practical enough to use daily, personal enough to treasure.
>
> Designed and crafted with care at Timber Trace Crafts in Avon Park, Florida. 🌲

- **Photos needed (min 5):** hero on white · styled with food · size comparison · engraving detail · wood variation grid · (then: sample personalized name · packaging · edge/thickness · care graphic · kitchen lifestyle)

---

## Listing 2 — Personalized Wood Coaster Set of 4

- **Final launch price:** **$24** (set of 4)
- **Readiness:** `made_to_order` 1–3 days (`1488822641920`)
- **Variations:**
  - Wood: Birch / Walnut / Cherry  (no upcharge)
  - Design: Monogram / Family name / Custom text
  - Personalization text (buyer-supplied, required)
- **Title:** `Personalized Wood Coaster Set of 4 | Engraved Hardwood Coasters | Housewarming & Wedding Gift`
- **Tags (13):** wood coasters · personalized coasters · engraved coasters · housewarming gift · coaster set · custom coasters · wedding gift · hardwood coasters · monogram coaster · gift for couple · barware gift · new home gift · rustic home decor
- **Description:**

> Protect the table and personalize the room with a set of four laser-engraved wood coasters. ☕
>
> Each coaster is engraved with your choice of monogram, family name, or custom design and finished to bring out the warm, natural grain of the wood. Substantial enough to feel premium, and a perfect little upgrade for a coffee table, bar cart, or new home.
>
> Item Details:
> * Material: Solid hardwood / Baltic birch (set of 4)
> * Finish: Natural, hand-rubbed protective finish
> * Size: ~4" round or square (see photos)
> * Design: Laser-engraved monogram, name, or custom art
> * Personalization: Add your text or initials at checkout
>
> A thoughtful, affordable gift for housewarmings, weddings, and the coffee lover who has everything — and an easy add-on to any order.
>
> Designed and crafted with care at Timber Trace Crafts in Avon Park, Florida. 🌲

- **Photos needed (min 5):** hero set of 4 · styled on coffee table · single coaster scale · engraving detail · wood variations · (then: sample monogram · packaging · stacked angle · dimensions graphic · living-room lifestyle)

---

## Listing 3 — Personalized Christmas Ornament

- **Final launch price:** **$14**
- **Readiness:** `made_to_order` 1–3 days (`1488822641920`)
- **Variations:**
  - Design: Bauble / Snowflake / Tree / First Christmas
  - Personalization text (buyer-supplied, required)
- **Title:** `Personalized Christmas Ornament | Custom Name & Date Wood Ornament | Laser-Cut Keepsake Gift`
- **Tags (13):** christmas ornament · personalized ornament · custom ornament · wood ornament · name ornament · first christmas gift · laser cut ornament · keepsake ornament · holiday gift · family ornament · stocking stuffer · 2026 ornament · tree decoration
- **Description:**

> Turn the tree into a keepsake with an ornament engraved just for them. ❄️
>
> Each ornament is laser-cut and engraved from natural wood with your custom name, date, or message — a "Baby's First Christmas," a couple's first home, a family name, or the year. Lightweight, delicate, and made to come back out every December for years to come.
>
> Item Details:
> * Material: Baltic birch / hardwood
> * Finish: Natural, lightly sealed
> * Size: ~3–4" (see photos)
> * Design: Laser-cut shape with engraved personalization
> * Personalization: Add name, date, or short phrase at checkout
> * Includes: Ribbon/twine for hanging
>
> A perfect stocking stuffer, holiday gift, or annual keepsake — order early for the Q4 rush.
>
> Designed and crafted with care at Timber Trace Crafts in Avon Park, Florida. 🌲

- **Photos needed (min 5):** hero on white · on the tree · scale in hand · engraving detail · design options grid · (then: sample personalized name · packaging · reverse/hanging · size graphic · holiday lifestyle)

---

## Publish checklist (per listing, from the Playbook DoD)

- [ ] Photos shot/generated (≥5, hero + styled/scale/detail/variations)
- [ ] In **prod** Etsy admin: Add a listing → paste title, description, all 13 tags
- [ ] Set price(s) + variations + quantity
- [ ] Set category/taxonomy + readiness state + shipping profile `303514857493`
- [ ] Publish
- [ ] (optional) `php artisan etsy:link` to mirror into the own-store; then `etsy:diff` clean

**Founder action:** produce photos for the three products, then publish (or hand back and
I'll seed them locally and push via Path B once photos exist). Prices above are the
approved launch prices — adjust in-editor if materials/labor math changes.
