# No-Slop Audit — Timber Trace Crafts

> Design audit run 2026-06-28 against 11 full-page screenshots (home, shop, journal, about, contact) and the live design tokens in `resources/css/app.css` + font loading in `resources/views/layouts/app.blade.php`.

## Overall verdict

This design is **coasting on the Warm Cream default** — and the code proves it, not just the eye. The background token is literally `--color-oak-sand: #F4F1EA`, the *exact* hex the cream-default cluster is named after, paired with **Playfair Display + Montserrat** — the single most over-used AI/template font pairing in existence. So the canary fires twice: the cream palette and the default type system.

The saving grace: unlike most cream-default sites, a warm-wood palette is genuinely *appropriate* for a laser-cut hardwood shop — the products are warm-toned, so this reads as defensible-for-subject rather than lazy-by-accident. The deep forest-green hero band (`#2C4C3B`) is a real choice that lifts the page out of pure cream monotony.

Biggest thing holding the design back: **every section is built from the identical template — tiny all-caps tracked eyebrow + big Playfair headline — and that eyebrow appears 15+ times across the site as decoration, not information.** It's the tell that sections were stamped from one mold rather than designed.

## The signature test

*What is the one memorable visual element that could only belong to this brand?*

**There isn't one in the site chrome — and that's the central failure.** The most distinctive thing in every screenshot is the **laser-cut filigree/fretwork pattern on the products themselves** (the lattice on the heart box, the cut-out butterfly wings, the teardrop earrings). That motif is genuinely yours and instantly recognizable — but it appears *nowhere* in the design system. No fretwork divider, no lattice border, no cut-out treatment on a heading or card edge, nothing in the logo lockup. The brand's signature is sitting in the product photos, and the site refuses to pick it up. Swap "Timber Trace Crafts" for any other Etsy woodcraft shop name and the layout works identically.

## Findings by dimension

### 1. Identity & Differentiation — Needs work
- **What's happening:** Palette is `#F4F1EA` cream / `#2C4C3B` forest green / `#4A2C11` mahogany / `#8C7B6C` walnut. The wood tones are subject-true, but the *system* around them (zero border-radius, hairline rules, all-caps eyebrows) is interchangeable with any "artisanal" template.
- **Why it matters:** A customer can't tell the brand apart from the next handmade shop until they're already looking at a product photo.
- **Fix:** Build the laser-cut motif into the chrome. Take one real fretwork pattern from a product, render it as an SVG, and use it as a section divider or a faint corner inlay on the forest-green bands. Give cards a subtle laser-kerf edge instead of a plain hairline. One signature device, used 2–3 times, is enough.

### 2. Hero & Thesis — Needs work
- **What's happening:** "Handcrafted in Hardwood. Worn with Pride." over a forest-green band, with an eyebrow that reads **"TIMBER TRACE CRAFTS"** — i.e., it repeats the brand name already in the logo two inches to the left. Sub-copy "Every piece begins as raw timber and becomes something to treasure… made to last a lifetime" is well-written but generic-craft.
- **Why it matters:** The eyebrow is pure noise, and the thesis says nothing a competitor couldn't.
- **Fix:** Kill the redundant eyebrow. Replace it with something only this brand can say — the laser precision angle ("Cut to 0.1mm. Finished by hand.") or a material claim. Lead the hero image with a *product*, not a market-tent scene; the box is lost in the current shot.

### 3. Typography — Critical
- **What's happening:** `--font-heading: 'Playfair Display'` + `--font-body: 'Montserrat'`, headings at `font-weight: 300`. The textbook default pairing.
- **Why it matters:** It's the most legible "an AI/template chose this" signal on the entire site — and Montserrat has very little character at the small label sizes the design leans on heavily.
- **Fix:** Keep a high-contrast serif display if desired, but pick one with more specificity — **Fraunces** (optical "wonky" axis suits handmade) or **Cormorant** for the headline. Swap Montserrat for a body face with warmth at small sizes — **Inter Tight** (safe but more current), or a humanist like **Söhne** / **Public Sans**. Vary weight/size so hierarchy isn't carried by size alone.

### 4. Structure as Information — Needs work
- **What's happening:** No fake `01 / 02 / 03` numbering (good). But the all-caps tracked eyebrow is applied to *every* section: FEATURED COLLECTION, BROWSE BY CATEGORY, NEW ARRIVALS, EXPLORE ×3, OUR PHILOSOPHY, FROM THE WORKSHOP, STAY CONNECTED, GET IN TOUCH, RESPONSE TIME, WHEN WE RESPOND, FOLLOW ALONG, NEWSLETTER. And there are **two newsletter signups** stacked (the "Join Our Community" block immediately above an identical footer "NEWSLETTER" block).
- **Why it matters:** When every section has the same label treatment, the label stops encoding anything — it's wallpaper. The doubled newsletter is redundancy the structure should have caught.
- **Fix:** Demote eyebrows to the 2–3 sections where they actually classify content; let the rest open on the headline alone. Remove one of the two newsletter blocks.

### 5. Motion & Interaction — Needs work (inferred)
- **What's happening:** Static screenshots, but the CSS shows only `transition: opacity 0.2s` on buttons and basic `x-cloak`. No evidence of considered motion.
- **Why it matters:** Laser cutting is *motion* — a tracing, revealing action. That's a free, on-brand interaction going unused.
- **Fix:** On hover, animate a product card's image with a subtle "engrave reveal" (mask wipe) rather than a plain fade. One signature interaction tied to the craft, not scroll-fade-in everywhere.

### 6. Copy & Voice — Pass (with notes)
- **What's happening:** Section titles are pleasant but template-generic: "Crafted with Precision," "Fresh from the Studio," "Join Our Community." However, the **"Our Philosophy" pull quote — *"Every piece tells the story of the wood it came from"*** with the sustainably-sourced-hardwoods copy is genuinely good, specific, and brand-true. The About page's plain-language "What is laser-cut woodworking?" is smart for both humans and AI search. CTAs are mostly specific ("SHOP TUMBLERS," "SHOP JEWELRY BOXES").
- **Why it matters:** The voice works; it's the section *headlines* that default.
- **Fix:** Carry the philosophy section's specificity up into the headlines. "Fresh from the Studio" → name what's actually new.

### Bug (not slop)
The Journal post "The Best Handmade Gifts for Women" renders a **broken/placeholder image** (gray icon) on both the Journal index and the homepage Journal block — a missing featured image / `primary_image_url`. Fix regardless of design.

## Priority list

1. **Cut the redundant eyebrows and the duplicate newsletter.** Pure subtraction. Remove the hero's "TIMBER TRACE CRAFTS" eyebrow, drop eyebrows from sections that don't classify content, delete one of the two stacked newsletter blocks. Immediately reads less templated.
2. **Replace the Playfair + Montserrat pairing.** Highest-leverage single change for de-slopping — the most recognizable default tell. Try Fraunces (headline) + a humanist sans (body).
3. **Introduce one laser-cut signature device.** Pull a real fretwork pattern into an SVG and use it as a section divider or card-edge treatment 2–3 times. The difference between "an artisanal template" and "the laser-cut shop."

## What's working — don't touch
Forest-green hero band, the Our Philosophy pull-quote section, the product photography, and the plain-language About copy. Build the rest up to their level.
