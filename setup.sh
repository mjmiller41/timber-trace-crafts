#!/bin/bash
set -e

DEPLOY_DIR=~/public_html

echo "=== Timber Trace Crafts — First-Time Server Setup ==="
echo "Deploy directory: $DEPLOY_DIR"
echo ""

cd "$DEPLOY_DIR"

# Install PHP dependencies
echo ">>> Installing PHP dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction

# Set up .env
ENV_KEY_FILE="$HOME/.secrets/ttc-env-key"
if [ ! -f "$DEPLOY_DIR/.env" ]; then
    if [ -f "$ENV_KEY_FILE" ] && [ -f "$DEPLOY_DIR/.env.production.encrypted" ]; then
        echo ">>> Decrypting .env.production.encrypted with $ENV_KEY_FILE..."
        php artisan env:decrypt --env=production --key="$(cat "$ENV_KEY_FILE")" --filename=.env --force
    else
        echo ">>> No $ENV_KEY_FILE found — falling back to manual .env entry."
        echo "    (Run 'php artisan env:encrypt --env=production' locally and store the"
        echo "    printed key at $ENV_KEY_FILE on this server to skip this next time.)"
        cp "$DEPLOY_DIR/.env.example" "$DEPLOY_DIR/.env"
        echo ""
        echo ">>> .env file created. Fill in your production values now."
        echo "    Press Enter to open nano, save with Ctrl+X then Y."
        read -r
        nano "$DEPLOY_DIR/.env"
    fi
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

# Frontend assets are built locally and committed to public/build/ by the
# pre-push git hook — Node isn't available on the server, and even where it is,
# `npm ci --omit=dev` strips vite/tailwind since they're devDependencies.
echo ">>> Frontend assets ship via public/build/ (committed by the pre-push hook) — nothing to build here."

# Cache for production
echo ">>> Caching config, routes, views..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Storage permissions and symlink
echo ">>> Setting permissions and creating storage symlink..."
chmod -R 775 storage bootstrap/cache

# php artisan storage:link uses exec() which Hostinger shared hosting disables
# Create the symlink directly instead
if [ ! -L "$DEPLOY_DIR/public/storage" ]; then
    ln -s "$DEPLOY_DIR/storage/app/public" "$DEPLOY_DIR/public/storage"
    echo ">>> Storage symlink created."
else
    echo ">>> Storage symlink already exists."
fi

echo ""
echo "=== Setup complete! ==="
