# Pinterest SOP — Timber Trace Crafts

**Account:** https://www.pinterest.com/TimberTraceCrafts/  
**Login:** marketer@timbertracecrafts.com (credentials in Paperclip Secrets)  
**Maintainer:** Growth & Content Marketer agent

---

## Boards

| Board name | Category | Purpose |
|---|---|---|
| Laser-Cut Wood Earrings | Women's Fashion | All earring products and earring-adjacent content |
| Jewelry & Keepsake Boxes | Design | Box products, personalization, wedding/anniversary |
| Engraved Tumblers & Drinkware | Food and Drinks | Tumbler products, seasonal drinkware |
| Handmade Gift Ideas | Gifts | Blog gift guides, cross-product gift context |
| Behind the Studio | Design | Process shots, maker story, studio photos |
| Woodworking & Craft Tips | DIY and Crafts | How-it's-made journal posts, laser/wood craft tips |

---

## Image spec

- **Ratio:** 2:3 (1000 × 1500 px recommended)
- **Format:** JPG or PNG, ≤10 MB
- **Source:** Use the R2 product hero photo for product pins. For blog pins, use the post's cover image or og:image.
- **Branding:** No logo overlay required — product photos already carry the Timber Trace Crafts brand.
- **Alt text:** Fill the "alt text" field on every pin for accessibility and search indexing.

---

## Caption formula

```
[Lead benefit or emotional hook]. [1–2 product detail sentences]. [CTA or occasion]. Handmade in Avon Park, FL.
```

**Examples:**

> *Solid-hardwood teardrop earrings, laser-cut for airy everyday wear. Feather-light, eco-friendly, 3mm hardwood. Great boho gift. Handmade in Avon Park, FL.*

> *Celebrate America's 250th with a laser-etched stainless steel travel mug — 1776–2026 eagle design. Keeps drinks cold 20+ hrs. Perfect patriotic gift. Handmade in Avon Park, FL.*

**Length:** 100–150 characters preferred; Pinterest shows a preview of ~100 chars, longer text is readable on expand.

---

## Link format

| Pin type | Link target |
|---|---|
| Product pin | `https://timbertracecrafts.com/product/{slug}` |
| Blog/journal pin | `https://timbertracecrafts.com/journal/{slug}` |
| General brand | `https://timbertracecrafts.com/` |

Always use the canonical `/product/` or `/journal/` URL — never an admin or Etsy URL.

---

## Pin-every-post workflow

**Target: pin within 24 hours of publish.**

1. New journal post or product goes live at `/journal/{slug}` or `/product/{slug}`.
2. Marketer agent drafts the pin batch (title, description, board, link) as a comment on the active TIM issue or a new child issue.
3. Execute: log in as marketer@timbertracecrafts.com → Profile → **+** Create pin → upload image → fill title, description, alt text, link → choose board → Publish.
4. Confirm published URL in the issue thread.

**Board routing by content type:**

| Content | Board |
|---|---|
| Earring product | Laser-Cut Wood Earrings |
| Box/keepsake product | Jewelry & Keepsake Boxes |
| Tumbler product | Engraved Tumblers & Drinkware |
| Gift guide / evergreen blog | Handmade Gift Ideas |
| How-it's-made / process blog | Behind the Studio or Woodworking & Craft Tips |
| Cross-category gift occasion | Handmade Gift Ideas |

---

## Rich Pins (one-time setup)

After boards and first pins exist:

1. Open https://developers.pinterest.com/tools/url-debugger/
2. Enter any product URL (e.g. `/product/solid-hardwood-teardrop-earrings-…`)
3. Click **Validate** — this reads the OG meta tags and activates Rich Pins for the domain.
4. All product pins will then auto-pull price, availability, and description from page meta.

Note: product pins will show `og-default.jpg` until the Founding Engineer wires per-product `og:image` (tracked in TIM-30).

---

## First-run board + pin setup

See the companion setup script at `outputs/timber-trace/marketing/pinterest-board-setup-script.md` for the full one-time execution guide (6 boards + 7 first pins with exact copy ready to paste).
