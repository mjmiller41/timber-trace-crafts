# Blog Content Strategy — Timber Trace Crafts Journal

*Generated 2026-06-25 using /claude-blog strategy*

---

## Task Checklist

### Technical (Phase 1 & 2) — completed 2026-06-25
- [x] Fix Admin JournalController — status validation, tags sync, `$tags` variable *(done 2026-06-25)*
- [x] Fix `edit()` — alias `$journal` → `$post` so form partial renders *(done 2026-06-25)*
- [x] Fix Blade `@context`/`@type`/`@id` JSON-LD collision (escaped to `@@`) *(done 2026-06-25)*
- [x] Add eager-loading (`featuredImage`, `tags`, `author`) to public JournalController *(done 2026-06-25)*
- [x] Add RSS feed at `/journal/feed.xml` *(done 2026-06-25)*
- [x] Add `HasFactory` to `JournalPost` and `Tag` models *(done 2026-06-25)*
- [x] Create `JournalPostFactory` and `TagFactory` *(done 2026-06-25)*
- [x] Write 17 journal feature tests — 51/51 suite passing *(done 2026-06-25)*

### Phase 3 Enhancements
- [x] ZenComposer WYSIWYG editor — journal body, pages body *(done 2026-06-25)*
- [x] Featured image upload in admin journal form (inline Media record creation) *(done 2026-06-25)*
- [x] Related posts block on show view (by shared tags, take 3) *(done 2026-06-25)*
- [x] Reading time display (`ceil(str_word_count / 200)` minutes) *(done 2026-06-25)*
- [x] Tag archive pages — `GET /journal/tag/{tag}` + view *(done 2026-06-25)*
- [x] Fix journal/show.blade.php JSON-LD — convert @@context escape to json_encode() *(done 2026-06-25)*

### Month 1 Content — Week 3–4
- [ ] Write Pillar 6 pillar page: "How Laser Cutting and Engraving Works" (~3,000 words)
- [ ] Write Spoke 3.2: "Can you wear wood earrings in the shower?" (FAQ, ~500 words)
- [ ] Write Spoke 5.2: "Are engraved tumblers dishwasher safe?" (FAQ, ~500 words)
- [ ] Publish all three with featured images, tags, and SEO meta filled in

### Month 2 Content — Personalization Hub
- [ ] Write Pillar 1: "Ultimate Guide to Personalized Laser-Engraved Gifts" (~3,500 words)
- [ ] Write Spoke 1.1: "How laser engraving personalization works"
- [ ] Write Spoke 1.2: "What can and can't be laser engraved"
- [ ] Write Spoke 1.7: "Laser engraving vs. laser cutting: what's the difference?"
- [ ] Set up Pinterest scheduling — pin every post with image + excerpt on publish day
- [ ] Begin monthly AI citation tracking (10 target queries in ChatGPT + Perplexity)

### Month 3 Content — Gift Season + Wood Jewelry Cluster
- [ ] Write Pillar 2 gift guide hub + first 2 seasonal spokes (next upcoming holiday)
- [ ] Write Pillar 3 pillar page: "The Complete Guide to Wood Earrings" (~3,000 words)
- [ ] Write Spoke 3.3: "Are wood earrings hypoallergenic?"
- [ ] Run `/blog analyze` on all published posts; update any scoring below 70
- [ ] Add related posts block to journal show view (Phase 3 enhancement)
- [ ] Review AI citation tracking results; adjust content angle based on what's being extracted

---

## Executive Summary

The Timber Trace Crafts "Journal" blog is **substantially already built** — the model, database schema, admin CRUD, public routes, styled views, navigation links, and BlogPosting JSON-LD schema are all in place. The implementation work is primarily **bug-fixing the admin controller** (completed) and adding **SEO infrastructure** (completed). The larger opportunity is **content**: zero posts currently exist.

The store sells all types of **laser cut and laser engraved items** — wood is the primary material, but products span acrylic, leather, slate, anodized aluminum, and more. Product categories include jewelry (earrings), drinkware (tumblers), home décor, personalized gifts, ornaments, signage, and pet accessories. The blog should reflect this full breadth.

**Content velocity target**: 2 posts/month
**Primary goals**: Organic traffic, AI citation presence, brand storytelling, product cross-sells

---

## 1. Current State Audit

### ✅ Already Built

| Component | Status | Notes |
|-----------|--------|-------|
| `JournalPost` model | Complete | title, slug, excerpt, body, featured_image_id, status, published_at, meta_title, meta_description |
| Database migrations | Complete | `journal_posts` + `journal_post_tags` pivot |
| Public routes | Complete | `GET /journal`, `GET /journal/{slug}`, `GET /journal/feed.xml` |
| Admin CRUD routes | Complete | Full resource at `/admin/journal` |
| Public index view | Complete | 3-col grid, pagination, date, excerpt, "Read More" |
| Public show view | Complete | Prose styling, tags, breadcrumb, OG meta, BlogPosting JSON-LD |
| Admin form | Complete | Auto-slug, status dropdown, tags checkboxes, SEO meta section |
| Navigation links | Complete | Desktop nav, mobile nav, footer |
| Admin sidebar link | Complete | `/admin/journal` link present |
| BlogPosting schema | Complete | Emitted via `@push('schema')` in show view |
| Canonical tags | Complete | Default `url()->current()` in layout |
| Sitemap entries | Complete | Journal posts already in `SitemapController` |
| RSS feed | Complete | Added 2026-06-25 at `/journal/feed.xml` |

### ✅ Bugs Fixed — 2026-06-25

| Bug | Location | Status |
|-----|----------|--------|
| `$tags` not passed to `create()` or `edit()` views | `Admin/JournalController` | ✅ Fixed |
| `store()` validated `published` (boolean) instead of `status` (string) | `Admin/JournalController::store()` | ✅ Fixed |
| Tags not in validation or sync logic | `Admin/JournalController::store()` / `update()` | ✅ Fixed |
| `edit()` passed `$journal` but form partial expected `$post` | `Admin/JournalController::edit()` | ✅ Fixed |
| `@context`/`@type`/`@id` in JSON-LD parsed as Blade directives | `journal/show.blade.php` | ✅ Fixed (escaped to `@@`) |

### Phase 3 Enhancements — Not Started

| Feature | Priority | Notes |
|---------|----------|-------|
| Featured image upload in admin form | Medium | Use existing `Media` model upload pattern |
| Related posts block | Low | `whereHas('tags')->where('id','!=',$post->id)->take(3)` |
| Reading time display | Low | `ceil(str_word_count(strip_tags($post->body)) / 200)` |
| Tag archive pages (`/journal/tag/{tag}`) | Low | Additional SEO surface |
| WYSIWYG editor | Low | EasyMDE (CDN, no build step) — already noted in form.blade.php |

---

## 2. Content Strategy

### Brand Positioning

Timber Trace Crafts should own the intersection of **"laser craft + personal meaning"**. The common thread across wood earrings, engraved tumblers, custom signs, pet tags, ornaments, and coasters is *personalization with craft integrity*: items that feel designed for someone, made by hand, not mass-produced.

**Voice**: Warm, specific, maker-proud. The person behind the laser speaking to someone who wants more than an Amazon order.

**Content Tone**: Practical and personal. Explain the "why" behind material choices. Avoid generic "10 gift ideas" fluff — every post should have detail only a laser crafter can provide.

---

### What a Diode Laser Can Make (Full Product Range)

| Category | Example Products | Blog Angle |
|----------|-----------------|------------|
| **Jewelry** | Wood earrings, acrylic earrings, leather earrings | Care, style, material comparisons |
| **Drinkware** | Engraved tumblers, engraved wine glasses | Care, gifting, personalization options |
| **Home Décor** | Signs, wall art, coasters, keychains, bookmarks | Style, gifting, customization |
| **Personalized Gifts** | Name signs, wedding gifts, memorial items, pet gifts | Gift guides, "how to personalize" guides |
| **Seasonal / Holiday** | Ornaments, gift tags, holiday décor | Seasonal gift guides, deadline-driven content |
| **Pet Accessories** | Engraved pet tags, pet portrait items | Pet owner gifting niche |
| **Wedding & Events** | Place cards, favors, venue signs, keepsakes | Wedding niche search traffic |
| **Materials** | Wood (primary), acrylic, leather, slate, aluminum | Material education, care comparisons |

---

### Content Pillars & Cluster Architecture

#### Pillar 1: Personalized & Custom Laser-Engraved Gifts
*Highest-traffic opportunity — "personalized gifts" searched millions of times monthly*

| # | Spoke Topic | Template | Target Keyword |
|---|-------------|----------|----------------|
| P | Ultimate guide to personalized laser-engraved gifts | `pillar-page` | `personalized laser engraved gifts` |
| 1 | How laser engraving personalization works: fonts, designs, limits | `how-to-guide` | `how does laser engraving work` |
| 2 | What can and can't be laser engraved (honest guide) | `faq-knowledge` | `what can be laser engraved` |
| 3 | How to choose what to engrave: names, dates, coordinates, quotes | `how-to-guide` | `what to engrave on a gift` |
| 4 | Personalized gifts for women: laser-engraved ideas by budget | `listicle` | `personalized gifts for women` |
| 5 | Personalized gifts for men: laser-engraved options that aren't cheesy | `listicle` | `personalized gifts for men` |
| 6 | How far in advance to order custom laser gifts | `faq-knowledge` | `how long does custom engraving take` |
| 7 | Laser engraving vs. laser cutting: what's the difference? | `comparison` | `laser engraving vs laser cutting` |

---

#### Pillar 2: Gift Guides by Occasion
*Seasonal traffic spikes; publish each guide 6–8 weeks before the holiday*

| # | Spoke Topic | Template | Target Keyword | Publish By |
|---|-------------|----------|----------------|------------|
| P | Handcrafted gifts for every occasion | `pillar-page` | `handmade laser cut gifts` | Evergreen |
| 1 | Mother's Day gifts: personalized laser-engraved picks | `listicle` | `personalized mothers day gifts` | ~March 25 |
| 2 | Christmas gift guide: laser-cut and engraved for everyone on your list | `listicle` | `laser cut christmas gifts` | ~Nov 1 |
| 3 | Valentine's Day: wood and laser-engraved gift ideas | `listicle` | `laser engraved valentines day gifts` | ~Jan 1 |
| 4 | Wedding gifts that aren't on the registry: custom laser options | `listicle` | `custom laser engraved wedding gifts` | Evergreen |
| 5 | Graduation gifts: personalized laser-engraved keepsakes | `listicle` | `personalized graduation gifts` | ~April 1 |
| 6 | Birthday gift guide: unique laser-crafted presents under $75 | `listicle` | `unique birthday gifts laser engraved` | Evergreen |
| 7 | Memorial and sympathy gifts: meaningful laser-engraved keepsakes | `thought-leadership` | `personalized sympathy gifts` | Evergreen |
| 8 | Pet lover gifts: laser-engraved items for dog and cat owners | `listicle` | `laser engraved pet gifts` | Evergreen |

---

#### Pillar 3: Wood Jewelry — Care, Choosing, and Wearing

| # | Spoke Topic | Template | Target Keyword |
|---|-------------|----------|----------------|
| P | Complete guide to wood earrings | `pillar-page` | `wood earrings guide` |
| 1 | How to care for wood earrings (cleaning, oiling, storage) | `how-to-guide` | `how to care for wood earrings` |
| 2 | Can you wear wood earrings in the shower? | `faq-knowledge` | `can wood earrings get wet` |
| 3 | Are wood earrings hypoallergenic? The truth for sensitive ears | `faq-knowledge` | `are wood earrings hypoallergenic` |
| 4 | Wood vs. acrylic earrings: which are better? | `comparison` | `wood earrings vs acrylic earrings` |
| 5 | Why wood earrings are so lightweight (and why that matters) | `thought-leadership` | `lightweight earrings for sensitive ears` |
| 6 | Cherry vs. maple vs. walnut earrings: does the wood species matter? | `comparison` | `types of wood for earrings` |
| 7 | How to style wood earrings with any outfit | `how-to-guide` | `how to style wood earrings` |

---

#### Pillar 4: Laser-Crafted Home Décor & Signs

| # | Spoke Topic | Template | Target Keyword |
|---|-------------|----------|----------------|
| P | Buyer's guide to custom laser-cut wood signs | `pillar-page` | `custom laser cut wood signs` |
| 1 | Personalized family name signs: what to know before you order | `how-to-guide` | `personalized family name sign wood` |
| 2 | Laser-cut wood signs for the home: décor ideas by room | `listicle` | `laser cut wood signs home decor` |
| 3 | Custom wood signs as housewarming gifts | `listicle` | `custom wood sign housewarming gift` |
| 4 | Wood coasters: laser-engraved sets as gifts or home accents | `listicle` | `laser engraved wood coasters gift` |
| 5 | How to hang and care for a wood sign (so it lasts) | `how-to-guide` | `how to care for wood wall sign` |

---

#### Pillar 5: Engraved Drinkware — Tumblers, Mugs & More

| # | Spoke Topic | Template | Target Keyword |
|---|-------------|----------|----------------|
| P | Custom engraved tumblers: everything you need to know | `pillar-page` | `custom engraved tumbler` |
| 1 | How to care for a laser-engraved tumbler | `how-to-guide` | `how to care for engraved tumbler` |
| 2 | Are engraved tumblers dishwasher safe? | `faq-knowledge` | `engraved tumbler dishwasher safe` |
| 3 | Personalized tumblers as gifts: what to engrave and why | `how-to-guide` | `personalized tumbler gift ideas` |
| 4 | Engraved tumbler vs. printed tumbler: which lasts longer? | `comparison` | `engraved vs printed tumbler` |
| 5 | Best engraved drinkware gifts for coworkers | `listicle` | `engraved gifts for coworkers` |

---

#### Pillar 6: Behind the Laser — Craft Process & Materials
*Write this first — easiest from first-hand knowledge, builds E-E-A-T foundation*

| # | Spoke Topic | Template | Target Keyword |
|---|-------------|----------|----------------|
| P | How laser cutting and engraving works | `pillar-page` | `how laser cutting works` |
| 1 | What materials can a diode laser cut and engrave? | `how-to-guide` | `materials for laser engraving` |
| 2 | Wood species guide: which woods laser best? | `data-research` | `best wood for laser engraving` |
| 3 | Why laser-engraved gifts don't fade like printed ones | `thought-leadership` | `does laser engraving fade` |
| 4 | Acrylic vs. wood laser crafts: which should you choose? | `comparison` | `acrylic vs wood laser cut` |
| 5 | Leather laser engraving: what it looks like and when to choose it | `thought-leadership` | `laser engraved leather gifts` |
| 6 | From Etsy shop to our own store: why we made the move | `thought-leadership` | (brand story — AI citation signal) |
| 7 | Why every laser-engraved item looks slightly different | `thought-leadership` | `unique handmade laser gifts` |

---

#### Pillar 7: Wedding & Events Laser Crafts

| # | Spoke Topic | Template | Target Keyword |
|---|-------------|----------|----------------|
| P | Laser-cut wedding décor and gifts | `pillar-page` | `laser cut wedding decor` |
| 1 | Custom wedding favors: laser-engraved options guests will keep | `listicle` | `laser engraved wedding favors` |
| 2 | Personalized wedding gifts for the couple | `listicle` | `personalized laser engraved wedding gift` |
| 3 | Bridesmaid gifts: laser-engraved wood and acrylic ideas | `listicle` | `laser engraved bridesmaid gifts` |
| 4 | Laser-cut place cards and escort cards: what to order | `how-to-guide` | `laser cut wedding place cards` |
| 5 | Wedding sign ideas: wood, acrylic, and what works best | `comparison` | `wood wedding signs vs acrylic` |

---

## 3. AI Citation Surface Strategy

### On-Site (Apply to Every Post)

- **Answer-first H2s**: Every H2 opens with a 40-60 word direct answer
- **Citation capsules**: Each H2 ends with a self-contained 40-60 word summary AI can extract
- **FAQ schema**: Every post includes a `FAQPage` JSON-LD block with 3-5 Q&As
- **Question headings**: 60-70% of H2 headings phrased as questions
- **Entity consistency**: "wood earrings" not "wooden earrings" / "earring pieces" — pick one per post

### AI Citation Gap Opportunities

| Query | Opportunity Level |
|-------|-------------------|
| "what can be laser engraved" | **High** — no clean buyer-facing guide |
| "are wood earrings hypoallergenic" | **High** — zero authoritative answers |
| "can wood earrings get wet" | **High** — simple Q&A format wins |
| "does laser engraving fade" | **High** — definitive post from a maker |
| "engraved tumbler dishwasher safe" | **High** — clear answer + product trust |
| "personalized laser engraved gifts" | **Medium** — brand + craft story differentiates |
| "laser engraved wedding favors" | **Medium** — editorial guide would rank |

---

## 4. Quality Gate (Before Publishing Each Post)

- [ ] Score 80+ using `/blog analyze`
- [ ] Minimum 1,500 words for spoke posts; 2,500+ for pillar posts
- [ ] At least 1 featured image with descriptive alt text
- [ ] `meta_title` and `meta_description` filled in (admin SEO section)
- [ ] At least 2 internal links to product pages in the shop
- [ ] At least 1 FAQ section (3-5 Q&As) for schema markup
- [ ] Answer-first paragraph on every H2
- [ ] Tags applied (for related posts and tag archive)

---

## 5. Measurement Framework

### Traditional SEO (monthly)
- Organic search impressions + clicks (Google Search Console)
- Keyword rankings for target keywords
- Pages indexed (Site: operator check)

### AI Citation Metrics (monthly, manual)
- Search 10-15 target queries in ChatGPT, Perplexity, Google AI Overviews
- Track AI referral traffic in GA4: `source contains "chatgpt.com"`, `"perplexity.ai"`, `"claude.ai"`

### Business Impact
- Blog-influenced orders (UTM: `?utm_source=journal&utm_medium=internal`)
- Time on page for journal posts (GA4)

---

*Plan generated with `/claude-blog strategy` on 2026-06-25.*
*Content cluster architecture follows the hub-and-spoke model from blog-strategy v1.9.1.*
