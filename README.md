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

### Directory structure on server

```
~/
├── public_html/          ← web root (domain points here)
│   └── (empty or other sites)
└── timber-trace-crafts/  ← Laravel app (Git deploys here)
    ├── .env              ← never web-accessible
    ├── public/           ← domain webroot points here
    └── ...
```

### Hostinger hPanel setup

1. **Git deploy path** — hPanel → Advanced → Git → set deploy directory to `timber-trace-crafts`
2. **Domain webroot** — hPanel → Domains → your domain → Web Root → set to `timber-trace-crafts/public`
3. Push to `main` to trigger the first deploy

### First-time server setup (via SSH)

After the first deploy, SSH into the server and run:

```bash
cd ~/timber-trace-crafts
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
cd ~/timber-trace-crafts
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
