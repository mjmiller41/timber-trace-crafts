#!/bin/bash
set -e

echo "=== Timber Trace Crafts Deployment ==="

# Install/update PHP dependencies (no dev, optimized autoloader)
composer install --no-dev --optimize-autoloader --no-interaction

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
