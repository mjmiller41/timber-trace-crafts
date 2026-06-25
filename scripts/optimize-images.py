#!/usr/bin/env python3
"""
Image optimization pipeline for Timber Trace Crafts.

For every image in storage/app/public/products/:
  - PNG  → produces .webp + .jpg fallback, deletes original .png
  - JPG  → produces .webp, keeps .jpg as fallback (already correct format)
  - JPEG → produces .webp, renames to .jpg fallback, deletes .jpeg

Run from the project root:
    python3 scripts/optimize-images.py [--dry-run]
"""

import argparse
import os
import sys
from pathlib import Path
from PIL import Image

PRODUCTS_DIR = Path(__file__).parent.parent / "storage" / "app" / "public" / "products"
WEBP_QUALITY = 80
JPG_QUALITY  = 85
SOURCE_EXTS  = {".png", ".jpg", ".jpeg"}


def human_kb(path: Path) -> float:
    return path.stat().st_size / 1024


def convert(src: Path, dry_run: bool) -> dict:
    stem = src.stem
    ext  = src.suffix.lower()
    webp = src.with_suffix(".webp")
    jpg  = src.with_name(stem + ".jpg")

    result = {
        "src": src,
        "webp": None,
        "jpg": None,
        "deleted": [],
        "orig_kb": human_kb(src),
        "webp_kb": 0,
        "jpg_kb": 0,
    }

    img = Image.open(src).convert("RGB")

    # --- WebP ---
    if not dry_run:
        img.save(webp, "WEBP", quality=WEBP_QUALITY, method=6)
    result["webp"] = webp
    result["webp_kb"] = human_kb(webp) if webp.exists() else 0

    # --- JPG fallback ---
    if ext == ".png":
        # PNG has no JPG yet — create one and delete the PNG
        if not dry_run:
            img.save(jpg, "JPEG", quality=JPG_QUALITY, optimize=True)
        result["jpg"] = jpg
        result["jpg_kb"] = human_kb(jpg) if jpg.exists() else 0
        result["deleted"].append(src)
        if not dry_run:
            src.unlink()

    elif ext == ".jpeg":
        # Normalise .jpeg → .jpg extension, delete .jpeg
        if not dry_run and not jpg.exists():
            src.rename(jpg)
        elif dry_run:
            pass  # just record intent
        result["jpg"] = jpg
        result["jpg_kb"] = human_kb(jpg) if jpg.exists() else human_kb(src)
        if src.exists():
            result["deleted"].append(src)
            if not dry_run:
                src.unlink()

    else:  # .jpg — re-encode at target quality to reduce file size
        if not dry_run:
            img.save(jpg, "JPEG", quality=JPG_QUALITY, optimize=True)
        result["jpg"] = jpg
        result["jpg_kb"] = human_kb(jpg) if jpg.exists() else 0

    return result


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("--dry-run", action="store_true", help="Preview without writing files")
    args = parser.parse_args()

    if not PRODUCTS_DIR.exists():
        print(f"ERROR: {PRODUCTS_DIR} not found. Run from project root.")
        sys.exit(1)

    sources = sorted(
        f for f in PRODUCTS_DIR.iterdir()
        if f.suffix.lower() in SOURCE_EXTS
    )

    if not sources:
        print("No image files found.")
        sys.exit(0)

    print(f"{'DRY RUN — ' if args.dry_run else ''}Processing {len(sources)} images in {PRODUCTS_DIR}\n")
    print(f"{'File':<50} {'Orig':>7}  {'WebP':>7}  {'JPG':>7}  {'Saved':>7}  Action")
    print("-" * 100)

    total_orig = total_after = 0

    for src in sources:
        r = convert(src, dry_run=args.dry_run)

        new_size = r["webp_kb"] + (r["jpg_kb"] if r["jpg"] and r["jpg"] != src else 0)
        saved_pct = (1 - new_size / r["orig_kb"]) * 100 if r["orig_kb"] else 0

        deleted_str = f"delete {', '.join(d.name for d in r['deleted'])}" if r["deleted"] else "keep"
        webp_str    = f"{r['webp_kb']:>6.0f}K" if r["webp_kb"] else "    --"
        jpg_str     = f"{r['jpg_kb']:>6.0f}K"  if r["jpg_kb"]  else "    --"

        total_orig  += r["orig_kb"]
        total_after += new_size

        print(
            f"{src.name:<50} {r['orig_kb']:>6.0f}K  {webp_str}  {jpg_str}  "
            f"{saved_pct:>5.0f}%   {deleted_str}"
        )

    print("-" * 100)
    overall_pct = (1 - total_after / total_orig) * 100 if total_orig else 0
    print(
        f"{'TOTAL':<50} {total_orig/1024:>5.1f}MB  "
        f"{'→ ' + str(round(total_after/1024, 1)) + 'MB':>16}  "
        f"{overall_pct:>5.0f}%  overall savings"
    )

    if args.dry_run:
        print("\n[DRY RUN] No files were written or deleted. Remove --dry-run to apply.")
    else:
        print("\nDone. Upload storage/app/public/products/ to R2.")


if __name__ == "__main__":
    main()
