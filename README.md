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

## Image Storage — Cloudflare R2

All product and media images are stored in Cloudflare R2 (S3-compatible object storage). Images are served directly from R2's CDN — Hostinger never handles image traffic.

- **Bucket:** `timber-trace-crafts`
- **Public URL:** `https://pub-82fe4a94d274416a9b5ab8028bcd8627.r2.dev`
- **Endpoint:** `https://3692088bc3f65a6e2a74ae1b1da92c73.r2.cloudflarestorage.com`

The `Media::url()` model method resolves the correct URL per disk. All views use `$media->url()` — never `asset('storage/...')`.

### Adding new images

Upload via the admin panel — images go directly to R2. No manual steps needed.

### Initial migration (one-time)

To upload all local images to R2 and update media DB records:

```bash
php artisan media:upload-to-r2
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

### Server SSH access

```
ssh -p 65002 u903552178@5.183.10.138
```

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
- Cache config, routes, and views
- Set storage permissions and create the storage symlink

After setup, update media records to use R2:

```bash
php artisan media:upload-to-r2
```

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

### Pre-push hook

Install on a new machine after cloning:

```bash
cat > .git/hooks/pre-push << 'EOF'
#!/bin/bash
set -e

echo ">>> Building frontend assets for deployment..."
npm run build

git add public/build/
git diff --cached --quiet public/build/ || git commit -m "chore: rebuild frontend assets"

echo ">>> Done."
EOF
chmod +x .git/hooks/pre-push
```

### Required `.env` values for production

```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://timbertracecrafts.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_db
DB_USERNAME=your_user
DB_PASSWORD=your_password

FILESYSTEM_DISK=r2

R2_ACCESS_KEY_ID=your_key
R2_SECRET_ACCESS_KEY=your_secret
R2_BUCKET=timber-trace-crafts
R2_ENDPOINT=https://3692088bc3f65a6e2a74ae1b1da92c73.r2.cloudflarestorage.com
R2_PUBLIC_URL=https://pub-82fe4a94d274416a9b5ab8028bcd8627.r2.dev

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
