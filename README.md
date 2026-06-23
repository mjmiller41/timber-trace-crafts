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

Push to `main` — Hostinger auto-deploys. If the push includes new migrations, SSH in and run:

```bash
cd ~/public_html
php artisan migrate --force
php artisan config:cache
```

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
