# Timber & Trace Crafts

E-commerce store for handmade wood and resin jewelry, built with Laravel 13.

## Local Development

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm run dev
```

## Deployment (Hostinger Business — Git Integration)

### How it works

The repo deploys to `public_html` via Hostinger's Git integration on every push to `main`. The root `.htaccess` redirects all traffic into `public/` and blocks direct HTTP access to `.env` and other sensitive files.

```
~/public_html/
├── .htaccess        ← redirects to public/, blocks .env access
├── .env             ← protected — not web-accessible
├── public/
│   └── .htaccess    ← Laravel routing
└── app/, routes/, etc.
```

### Hostinger hPanel setup

1. hPanel → Advanced → Git → connect repo, set deploy directory to `public_html`, branch `main`
2. Auto-deploy fires on every push to `main`

### First-time server setup (via SSH)

After the first deploy, SSH into the server and run:

```bash
cd ~/public_html
bash setup.sh
```

The script will:
- Install Composer dependencies
- Create `.env` and prompt you to fill in production values
- Generate the app key
- Run database migrations
- Build frontend assets (if npm is available)
- Cache config, routes, and views
- Set storage permissions and create the storage symlink

### Ongoing deploys

Push to `main` — Hostinger auto-deploys. A pre-push git hook automatically runs `npm run build` and commits the built assets before every push, so the server always gets up-to-date CSS/JS.

If the push includes new migrations, SSH in and run:

```bash
cd ~/public_html
php artisan migrate --force
php artisan config:cache
```

### Frontend assets

`public/build/` is committed to the repo because Node.js is unavailable on Hostinger shared hosting. The pre-push hook handles this automatically, but if you ever need to build manually:

```bash
npm run build
git add public/build/
git commit -m "chore: rebuild frontend assets"
git push
```

To install the pre-push hook on a new machine after cloning:

```bash
cat > .git/hooks/pre-push << 'EOF'
#!/bin/bash
set -e

echo ">>> Building frontend assets for deployment..."
npm run build

git add public/build/
git diff --cached --quiet public/build/ || git commit -m "chore: rebuild frontend assets"

echo ">>> Syncing images to Hostinger..."
rsync -avz --delete -e "ssh -p 65002" \
  /home/michael/Code/Projects/timber-trace-crafts/storage/app/public/ \
  u903552178@timbertracecrafts.com:~/domains/timbertracecrafts.com/public_html/storage/app/public/

echo ">>> Done."
EOF
chmod +x .git/hooks/pre-push
```

The hook runs automatically on every `git push` and:
1. Builds frontend CSS/JS assets
2. Commits any changed build files
3. Rsyncs `storage/app/public/` to the server (new images upload, deleted images are removed)

### Required `.env` values for production

```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_db
DB_USERNAME=your_user
DB_PASSWORD=your_password

MAIL_MAILER=smtp
MAIL_HOST=...
MAIL_PORT=465
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_FROM_ADDRESS=...
MAIL_FROM_NAME="Timber & Trace Crafts"

STRIPE_KEY=pk_live_...
STRIPE_SECRET=sk_live_...
STRIPE_WEBHOOK_SECRET=whsec_...
```
