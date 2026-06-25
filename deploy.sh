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

# Build frontend assets
npm ci --omit=dev
npm run build

# Set correct permissions on storage and cache
chmod -R 775 storage bootstrap/cache

echo "=== Deployment complete ==="
