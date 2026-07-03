# Tasks

## Active
- [ ] **16. ** - [Medium]** Media write-path failures invisible — merged into Active item "Investigate prod R2 `put()` failure" (detail there).**
- [ ] **Stripe refund via API** - admin can mark "refunded" but no real Stripe call yet
- [ ] **Invoice/receipt download** - My Account → order detail
- [ ] **Recently viewed products** - Alpine.js + localStorage
- [ ] **Social sharing buttons on product pages** - Facebook, Pinterest, Instagram
- [ ] **Reorder past items** - from order history
- [ ] **Custom orders / B2B / bulk quote flow**
- [ ] **Gallery / portfolio page**
- [ ] **Materials / sizing / care guide page**
- [ ] **Dark mode**
- [ ] **Review photos**
- [ ] **Customer file upload for custom engraving**
- [ ] **Gift cards** - post-launch
- [ ] **Abandoned cart emails** - post-launch
- [ ] **Admin 2FA** - TOTP via Fortify or custom
- [ ] **Admin session idle timeout**
- [ ] **Audit log** - admin actions
- [ ] **Admin error log viewer**
- [ ] **Shipping labels (Avery 5126)** - printable from admin order detail
- [ ] **Umami analytics integration**
- [ ] **Marketplace sync** - Etsy/eBay/Amazon Handmade, Phase 3
- [ ] **Bulk Etsy product push** - retest, was 7/7 failing; individual push works
- [ ] **Etsy image upload on push** - needs uploadListingImage endpoint
- [ ] **Etsy inventory sync** - untested after recent changes
- [ ] **After each post** - run `/blog analyze`, score 80+, add internal links to product pages, apply tags

## Waiting On
- [ ] **Certificate of Insurance** - waiting on Michael to obtain/send to City of Avon Park (since 2026-06-25)

## Someday
- [ ] **Week 1 pillar** - "The Best Handmade Gifts for Women Who Appreciate the Details" (~2,500 words)
- [ ] **Week 2** - "Valentine's Day Jewelry Gift Guide" (listicle, publish by Jan 10)
- [ ] **Week 3** - "How Wood Earrings Are Made: A Look Inside the Workshop" (how-to)
- [ ] **Week 4** - "Personalized Jewelry Box Ideas: How to Make a Gift She'll Keep Forever" (listicle)
- [ ] **Week 6** - "What to Get a Woman Who Has Everything: 7 Handmade Ideas" (listicle)
- [ ] **Week 7** - "Why We Started Timber Trace Crafts" (brand story)
- [ ] **Week 8** - "Mother's Day Gifts from a Small Maker" (listicle, publish by Apr 1)
- [ ] **Week 10** - "Handmade Wedding Party Gifts: A Complete Guide for Brides" (pillar)
- [ ] **Week 12** - "Wood vs. Metal Earrings: Which Is Better for Sensitive Ears?" (comparison)
- [ ] **Pillar 6** - "How Laser Cutting and Engraving Works" (~3,000 words)
- [ ] **Spoke 3.2** - "Can you wear wood earrings in the shower?" (FAQ, ~500 words)
- [ ] **Spoke 5.2** - "Are engraved tumblers dishwasher safe?" (FAQ, ~500 words)
- [ ] **Pillar 1** - "Ultimate Guide to Personalized Laser-Engraved Gifts" (~3,500 words)
- [ ] **Spoke 1.1** - "How laser engraving personalization works"
- [ ] **Spoke 1.2** - "What can and can't be laser engraved"
- [ ] **Spoke 1.7** - "Laser engraving vs. laser cutting: what's the difference?"
- [ ] **Pillar 2** - gift guide hub + first 2 seasonal spokes (next upcoming holiday)
- [ ] **Pillar 3** - "The Complete Guide to Wood Earrings" (~3,000 words)
- [ ] **Spoke 3.3** - "Are wood earrings hypoallergenic?"
- [ ] **Publish Month 1 batch** - three posts with featured images, tags, SEO meta
- [ ] **Set up Pinterest scheduling** - pin every post with image + excerpt on publish day
- [ ] **Monthly AI citation tracking** - 10 target queries in ChatGPT + Perplexity; review and adjust content angle
- [ ] **Publish journal posts with answer-first structure** - 1st (40-60 word factual opener per H2), 2nd (include a data table/comparison), 3rd (question-based H2s throughout)
- [ ] **"How laser-cut wood earrings are made" pillar post** - 1,500+ words, step-by-step
- [ ] **Create YouTube channel** - "Timber Trace Crafts"; post first studio process video, then a hand-finishing/inlay video
- [ ] **Create LinkedIn company page**
- [ ] **Participate in r/woodworking** - genuine behind-the-scenes post (not a link drop)
- [ ] **Participate in r/crafts** - answer a laser-cutting/wood-finishing question
- [ ] **Add YouTube + LinkedIn URLs to `social.*` admin settings**
- [ ] **Add YouTube + LinkedIn to Organization `sameAs` array** - `layouts/app.blade.php`
- [ ] **Add `jobTitle` + `description` to Person entity** - Michael J. Miller, global schema
- [ ] **Add `SiteLinksSearchBox` schema (SearchAction)** - once traffic justifies it
- [ ] **Get Certificate of Insurance to City of Avon Park** - for Local Business Tax Receipt application; City wants Avon Park listed as certificate holder (contact: dperez@avonpark.city, since 2026-06-25)

## Done
- [x] **Gift message field at checkout** (2026-07-03) - fixed: field now lives in checkout Step 3 form; was orphaned on cart page (never submitted) while controller already persisted it
- [x] **Product search UI** (2026-07-03) - search box wired into shop sidebar (desktop + mobile), preserves active filters/sort
- [x] **Shop filter by tags** (2026-07-03) - added Style tag section to shop sidebar (wood-species already present)
- [x] **Submit site to Bing IndexNow** (2026-07-03) - `services.indexnow.key` + root key file + `seo:indexnow` command; set prod APP_URL then run without --dry-run
