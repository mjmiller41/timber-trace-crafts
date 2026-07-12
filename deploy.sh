#!/bin/bash
set -e

echo "=== Timber Trace Crafts Deployment ==="

# Install/update PHP dependencies (no dev, optimized autoloader)
composer install --no-dev --optimize-autoloader --no-interaction

# Decrypt .env.production.encrypted (committed to git) into the real .env this
# process reads. The decryption key lives ONLY in this file, outside the git
# repo and outside public_html — never commit it. See memory/project_infra.md.
# IMPORTANT: the file must hold the FULL key string `php artisan env:encrypt`
# printed, including the leading "base64:" — stripping that prefix produces a
# raw string of the wrong byte length and env:decrypt fails with "Unsupported
# cipher or incorrect key length".
ENV_KEY_FILE="$HOME/.secrets/ttc-env-key"
# Fall back to the gitignored in-repo copy if the home copy is missing.
if [ ! -f "$ENV_KEY_FILE" ] && [ -f ".secrets/ttc-env-key" ]; then
    ENV_KEY_FILE=".secrets/ttc-env-key"
fi
if [ ! -f "$ENV_KEY_FILE" ]; then
    echo "Missing env key at ~/.secrets/ttc-env-key and ./.secrets/ttc-env-key — cannot decrypt .env.production.encrypted. Aborting." >&2
    exit 1
fi
php artisan env:decrypt --env=production --key="$(cat "$ENV_KEY_FILE")" --filename=.env --force

# Run pending migrations
php artisan migrate --force

# Clear and rebuild caches
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Frontend assets are built locally and committed to public/build/ by the
# pre-push git hook (Node isn't available on the server, and `npm ci --omit=dev`
# strips vite/tailwind since they're devDependencies) — nothing to build here.

# Set correct permissions on storage and cache
chmod -R 775 storage bootstrap/cache

echo "=== Deployment complete ==="
