#!/bin/bash
set -e

DEPLOY_DIR=~/public_html

echo "=== Timber & Trace Crafts — First-Time Server Setup ==="
echo "Deploy directory: $DEPLOY_DIR"
echo ""

cd "$DEPLOY_DIR"

# Install PHP dependencies
echo ">>> Installing PHP dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction

# Set up .env
if [ ! -f "$DEPLOY_DIR/.env" ]; then
    cp "$DEPLOY_DIR/.env.example" "$DEPLOY_DIR/.env"
    echo ""
    echo ">>> .env file created. Fill in your production values now."
    echo "    Press Enter to open nano, save with Ctrl+X then Y."
    read -r
    nano "$DEPLOY_DIR/.env"
else
    echo ">>> .env already exists, skipping."
fi

# Generate app key if not set
if ! grep -q "^APP_KEY=base64:" "$DEPLOY_DIR/.env"; then
    echo ">>> Generating application key..."
    php artisan key:generate --force
else
    echo ">>> APP_KEY already set, skipping."
fi

# Run migrations
echo ">>> Running database migrations..."
php artisan migrate --force

# Build frontend assets
if command -v npm &> /dev/null; then
    echo ">>> Building frontend assets..."
    npm ci --omit=dev
    npm run build
else
    echo ">>> npm not found — skipping frontend build."
    echo "    Build assets locally with 'npm run build' and commit public/build/."
fi

# Cache for production
echo ">>> Caching config, routes, views..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Storage permissions and symlink
echo ">>> Setting permissions and creating storage symlink..."
chmod -R 775 storage bootstrap/cache
php artisan storage:link

echo ""
echo "=== Setup complete! ==="
