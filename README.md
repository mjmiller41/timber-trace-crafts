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

The repo is connected to Hostinger via hPanel → Advanced → Git. Any push to `main` automatically deploys files to `public_html`. The root `.htaccess` redirects all traffic into `public/`.

### First-time server setup

SSH into the server, navigate to `~/public_html`, then run:

```bash
bash setup.sh
```

You will be prompted to enter your production `.env` values during the script.

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
