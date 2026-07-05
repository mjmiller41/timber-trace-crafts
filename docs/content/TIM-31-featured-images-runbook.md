# TIM-31 — Pillar-post featured images: go-live runbook

Everything is prepared and verified locally. Going live requires **CEO-authorized
prod access** (same gate as `ship.sh`), because it (a) deploys the FAQPage/alt
code and (b) writes to the live DB + R2 media bucket.

## What's already committed to `main`

- `App\Support\FaqExtractor` + `journal/show.blade.php` → **FAQPage JSON-LD** on all
  three pillar posts (they each have a 5-question FAQ), and featured `<img>` now
  uses the media `alt_text`.
- `journal:attach-featured {slug} {image} [--alt=]` artisan command — uploads an
  image to the media disk and sets it as the post's featured image, defaulting alt
  text from the draft `cover_image_alt` frontmatter.
- `resources/blog-featured/*.jpg` — the three hero images (below), 1600px, verified.
- Tests: `tests/Feature/JournalTest.php` (25/25 green), covering FAQPage emit/omit
  and alt_text.

## Image provenance (all no-attribution licenses, CC0-equivalent)

| Post slug | File | Source | License |
|---|---|---|---|
| `personalized-laser-engraved-gifts` | `resources/blog-featured/personalized-laser-engraved-gifts.jpg` | [Pexels 38030790](https://www.pexels.com/photo/precision-laser-engraving-on-wooden-surface-38030790/) (Sóc Năng Động) | Pexels License — attribution not required |
| `how-laser-cutting-and-engraving-works` | `resources/blog-featured/how-laser-cutting-and-engraving-works.jpg` | [Pexels 7254462](https://www.pexels.com/photo/close-up-shot-of-a-cnc-laser-7254462/) (Opt Lasers) | Pexels License — attribution not required |
| `complete-guide-to-wood-earrings` | `resources/blog-featured/complete-guide-to-wood-earrings.jpg` | [Unsplash](https://unsplash.com/photos/assortment-of-intricate-wooden-laser-cut-earrings-47h4o-KNVpk) (Валентина Вехкалахти) | Unsplash License — attribution not required |

Note: post #2's laser glow is cool-toned rather than warm; it's the best "active
focused laser on wood" match found. Swap later if the CEO prefers a warmer frame.

## Go-live (run on prod, in `~/Code/timbertracecrafts.com`, after `git pull` on `main`)

Deploy first (ships the FAQPage/alt code), then attach the three images. Alt text is
read automatically from each draft's `cover_image_alt` — no `--alt` needed.

```bash
# 1. Deploy code (FAQPage JSON-LD + alt) — CEO-authorized
./ship.sh   # or the standard deploy path

# 2. Attach featured images (writes prod DB + R2). Run on the prod app:
php artisan journal:attach-featured personalized-laser-engraved-gifts      resources/blog-featured/personalized-laser-engraved-gifts.jpg
php artisan journal:attach-featured how-laser-cutting-and-engraving-works  resources/blog-featured/how-laser-cutting-and-engraving-works.jpg
php artisan journal:attach-featured complete-guide-to-wood-earrings        resources/blog-featured/complete-guide-to-wood-earrings.jpg
```

## Verify after go-live

- Load `/journal/{slug}` for each — hero image renders, `<img alt>` matches the
  `cover_image_alt`.
- View source: two `application/ld+json` blocks — `BlogPosting` and `FAQPage`.
- Validate one URL in Google Rich Results Test (FAQ + Article).
- OG image: `og:image` now points at the featured image (was the default).
