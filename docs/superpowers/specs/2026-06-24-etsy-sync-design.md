# Etsy Sync — Design Spec
_Date: 2026-06-24_

## Overview

Bidirectional sync between this Laravel app and the Timber Trace Crafts Etsy shop. This app is the source of truth for products and pricing. Etsy is a sales channel.

**Scope:** Products/listings, inventory/stock, orders (inbound), shipment updates (outbound).

---

## 1. Data Model

### Migrations

**`add_etsy_listing_id_to_products_table`**
- `etsy_listing_id` — nullable string, unique — links a local product to an Etsy listing

**`add_etsy_receipt_id_to_orders_table`**
- `etsy_receipt_id` — nullable string, unique — prevents duplicate imports of the same Etsy order

### Settings (stored in existing `settings` table)

| Key | Description |
|---|---|
| `etsy.keystring` | Public API key from Etsy developer portal |
| `etsy.access_token` | OAuth access token (1hr TTL) |
| `etsy.refresh_token` | OAuth refresh token (90 day TTL) |
| `etsy.token_expires_at` | ISO datetime of access token expiry |
| `etsy.shop_id` | Etsy shop ID, resolved after first OAuth connect |
| `etsy.orders_last_synced_at` | Timestamp used for incremental order pulls |

### .env additions
```
ETSY_KEYSTRING=your-etsy-keystring
ETSY_SHARED_SECRET=your-etsy-shared-secret
```

---

## 2. Services

### `App\Services\Etsy\EtsyClient`

Thin HTTP wrapper around Etsy API v3 (`https://api.etsy.com/v3`).

- Reads `etsy.access_token` and `etsy.token_expires_at` from settings on each call
- If token expires within 5 minutes, calls `EtsyOAuthService::refreshToken()` before proceeding
- Attaches `x-api-key` (keystring) and `Authorization: Bearer {token}` headers
- Throws `App\Exceptions\EtsyApiException` on non-2xx responses
- Methods: `get(string $path, array $query)`, `put(string $path, array $body)`, `post(string $path, array $body)`

### `App\Services\Etsy\EtsyOAuthService`

Handles standard OAuth2 flow using keystring + shared secret (server-side app, no PKCE needed).

**`buildAuthUrl(): string`**
- Generates a random `state` token, stores in session (`etsy_oauth_state`) for CSRF protection
- Returns Etsy authorization URL (`https://www.etsy.com/oauth/connect`) with `client_id` (keystring), `redirect_uri`, `response_type=code`, `state`, and scopes: `listings_r listings_w inventory_r inventory_w transactions_r shops_r`

**`handleCallback(string $code, string $state): void`**
- Validates `state` against session value (CSRF check)
- POSTs to `https://api.etsy.com/v3/public/oauth/token` with `grant_type=authorization_code`, `client_id`, `client_secret` (shared secret), `redirect_uri`, and `code`
- Saves access token, refresh token, expiry, and shop ID to settings

**`refreshToken(): void`**
- POSTs `grant_type=refresh_token` with `client_id`, `client_secret`, and `refresh_token` to token endpoint
- Updates `etsy.access_token` and `etsy.token_expires_at` in settings

### `App\Services\Etsy\EtsyProductSync`

Pushes local products to Etsy. Never reads Etsy data back into local products (local wins).

**`syncProduct(Product $product): void`**
- If `etsy_listing_id` is null: creates a new Etsy draft listing, saves the returned ID to `products.etsy_listing_id`
- If `etsy_listing_id` is set: updates the existing listing
- Syncs: title, description, price (from lowest variant or product price), status (`draft` for new, preserves existing state for updates)
- Pushes primary product image if available

**`syncAll(): SyncResult`**
- Iterates all active products, calls `syncProduct()` on each
- Respects Etsy rate limits with a 250ms delay between calls
- Returns counts of created / updated / failed

### `App\Services\Etsy\EtsyInventorySync`

Pushes variant stock quantities to Etsy. Called after local stock changes.

**`syncProduct(Product $product): void`**
- Skips if product has no `etsy_listing_id`
- Fetches current Etsy inventory for the listing
- Rebuilds the full products/offerings array with updated quantities from local variants
- PUTs the full inventory payload (Etsy requires the complete array, no partial updates)

**`syncAll(): SyncResult`**
- Iterates all products with an `etsy_listing_id`, calls `syncProduct()` on each

### `App\Services\Etsy\EtsyOrderSync`

Pulls Etsy orders into the local database.

**`sync(): SyncResult`**
- Reads `etsy.orders_last_synced_at` from settings (defaults to 30 days ago on first run)
- Fetches receipts from `GET /v3/application/shops/{shop_id}/receipts?min_created={timestamp}` (paginated, 100 per page)
- For each receipt: skips if `etsy_receipt_id` already exists in orders table
- Creates local order with status `processing`, populates shipping address from receipt, sets `etsy_receipt_id`
- Creates order items from receipt transactions
- Updates `etsy.orders_last_synced_at` to now on success

### `App\Services\Etsy\EtsyShipmentSync`

Pushes tracking info to Etsy when a local order is marked shipped.

**`pushShipment(Order $order, Shipment $shipment): void`**
- Skips if `order->etsy_receipt_id` is null (not an Etsy order)
- POSTs to `POST /v3/application/shops/{shop_id}/receipts/{receipt_id}/tracking`
- Payload: carrier name, tracking number
- Logs success/failure; does not throw (shipping update failure should not block local flow)

---

## 3. Artisan Commands

| Command | Service | Description |
|---|---|---|
| `etsy:sync-products` | `EtsyProductSync::syncAll()` | Push all active products to Etsy |
| `etsy:sync-inventory` | `EtsyInventorySync::syncAll()` | Push stock quantities for all linked products |
| `etsy:sync-orders` | `EtsyOrderSync::sync()` | Pull new Etsy orders into local DB |

All commands output a summary table (created/updated/skipped/failed counts) and log errors.

---

## 4. Scheduler

In `routes/console.php`:

```php
Schedule::command('etsy:sync-orders')->everyFifteenMinutes();
Schedule::command('etsy:sync-inventory')->everyThirtyMinutes();
Schedule::command('etsy:sync-products')->dailyAt('02:00');
```

---

## 5. Shipment Hook

In `Admin\ShipmentController` (or wherever shipments are created): after saving a new `Shipment`, call `EtsyShipmentSync::pushShipment($order, $shipment)` if the order has an `etsy_receipt_id`.

---

## 6. Admin UI

### Routes

```
GET  /admin/etsy                → EtsyController@index
GET  /admin/etsy/connect        → EtsyController@connect      (starts OAuth)
GET  /admin/etsy/callback       → EtsyController@callback     (OAuth return)
POST /admin/etsy/disconnect     → EtsyController@disconnect
POST /admin/etsy/sync/products  → EtsyController@syncProducts
POST /admin/etsy/sync/inventory → EtsyController@syncInventory
POST /admin/etsy/sync/orders    → EtsyController@syncOrders
```

### `Admin\EtsyController`

- `index()`: passes connection status, shop name, token expiry, and last-synced timestamps to view
- `connect()`: calls `EtsyOAuthService::buildAuthUrl()` and redirects
- `callback()`: calls `EtsyOAuthService::handleCallback()`, redirects to index with success/error flash
- `disconnect()`: clears all `etsy.*` settings
- `syncProducts/syncInventory/syncOrders()`: run the respective sync service, redirect back with result flash

### View — `resources/views/admin/etsy/index.blade.php`

**Disconnected state:**
- "Connect to Etsy" button → `/admin/etsy/connect`

**Connected state:**
- Shop name and connection status badge
- Token expires at (human-readable)
- Three sync cards (Products / Inventory / Orders), each showing last synced time and a "Sync Now" button
- "Disconnect" button

---

## 7. Error Handling

- `EtsyApiException` caught in all sync services; individual item failures are logged and counted but do not abort the full sync run
- Token refresh failure logs and re-throws (sync cannot proceed without a valid token)
- Shipment push failure logs only (non-blocking)
- Admin UI shows last error message per sync type (stored in settings as `etsy.{type}_last_error`)

---

## 8. Testing

- Unit tests for `EtsyOAuthService` (URL generation, token storage)
- Unit tests for `Coupon::calculateDiscount` patterns apply — test each sync service with mock `EtsyClient`
- Feature test: admin Etsy page loads, connect redirects to Etsy URL
- Feature test: callback stores tokens and redirects
- Feature test: sync commands run without error against mock client

---

## Out of Scope

- Pushing product images to Etsy (images must be added manually on Etsy after listing creation)
- Syncing Etsy product changes back to local (local always wins)
- Multi-shop support
- Etsy coupon/discount sync
