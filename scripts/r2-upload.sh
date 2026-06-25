#!/usr/bin/env bash
# Upload optimised product images to Cloudflare R2 via S3-compatible API.
# Usage: bash scripts/r2-upload.sh <bucket-name>
# Requires: aws CLI + R2_ACCESS_KEY_ID, R2_SECRET_ACCESS_KEY, R2_ENDPOINT in .env

set -uo pipefail   # -e removed intentionally: per-file errors should not abort the batch

BUCKET="${1:-}"
if [[ -z "$BUCKET" ]]; then
    echo "Usage: bash scripts/r2-upload.sh <bucket-name>"
    echo "       Find your bucket name in .env as R2_BUCKET"
    exit 1
fi

# Load credentials from .env if not already in environment
if [[ -f ".env" ]]; then
    export AWS_ACCESS_KEY_ID="${AWS_ACCESS_KEY_ID:-$(grep '^R2_ACCESS_KEY_ID=' .env | cut -d= -f2)}"
    export AWS_SECRET_ACCESS_KEY="${AWS_SECRET_ACCESS_KEY:-$(grep '^R2_SECRET_ACCESS_KEY=' .env | cut -d= -f2)}"
    R2_ENDPOINT="${R2_ENDPOINT:-$(grep '^R2_ENDPOINT=' .env | cut -d= -f2)}"
fi

if [[ -z "${AWS_ACCESS_KEY_ID:-}" || -z "${AWS_SECRET_ACCESS_KEY:-}" || -z "${R2_ENDPOINT:-}" ]]; then
    echo "ERROR: R2_ACCESS_KEY_ID, R2_SECRET_ACCESS_KEY, and R2_ENDPOINT must be set in .env"
    exit 1
fi

PRODUCTS_DIR="storage/app/public/products"
CACHE_CONTROL="public, max-age=31536000, immutable"

if [[ ! -d "$PRODUCTS_DIR" ]]; then
    echo "ERROR: $PRODUCTS_DIR not found. Run from project root."
    exit 1
fi

upload_file() {
    local file="$1"
    local name
    name=$(basename "$file")
    local ext="${file##*.}"

    case "$ext" in
        webp) mime="image/webp"  ;;
        jpg)  mime="image/jpeg"  ;;
        jpeg) mime="image/jpeg"  ;;
        png)  mime="image/png"   ;;
        *)    mime="application/octet-stream" ;;
    esac

    aws s3 cp "$file" "s3://${BUCKET}/products/${name}" \
        --endpoint-url "$R2_ENDPOINT" \
        --content-type "$mime" \
        --cache-control "$CACHE_CONTROL" \
        --region auto \
        --no-progress \
        2>&1
}

echo "=== Uploading images to R2 bucket: $BUCKET ==="
echo "    Endpoint: $R2_ENDPOINT"
echo ""

total=0
ok=0
fail=0
failed_files=()

# Collect all .webp and .jpg files via find (no glob ambiguity)
mapfile -t FILES < <(find "$PRODUCTS_DIR" -maxdepth 1 -type f \( -name "*.webp" -o -name "*.jpg" \) | sort)

total=${#FILES[@]}

if [[ $total -eq 0 ]]; then
    echo "No image files found in $PRODUCTS_DIR"
    exit 0
fi

echo "Found $total files to upload."
echo ""

for file in "${FILES[@]}"; do
    name=$(basename "$file")
    printf "  [%d/%d] %s ... " "$((ok + fail + 1))" "$total" "$name"

    output=$(upload_file "$file")
    exit_code=$?

    if [[ $exit_code -eq 0 ]]; then
        echo "OK"
        ((ok++))
    else
        echo "FAILED"
        echo "       $output"
        ((fail++))
        failed_files+=("$name")
    fi
done

echo ""
echo "=== Done: $ok uploaded, $fail failed (of $total total) ==="

if [[ ${#failed_files[@]} -gt 0 ]]; then
    echo ""
    echo "Failed files:"
    for f in "${failed_files[@]}"; do
        echo "  - $f"
    done
    echo ""
    echo "Re-run with the same command to retry failed files."
fi

echo ""
echo "Next steps:"
echo "  1. Verify: https://pub-82fe4a94d274416a9b5ab8028bcd8627.r2.dev/products/lifestyle-1.webp"
echo "  2. Delete old PNG objects from R2 dashboard if no longer needed"
echo "     Cloudflare → R2 → $BUCKET → Browse → products/"
