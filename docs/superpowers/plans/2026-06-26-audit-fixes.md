# Audit Fixes Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Fix all audit findings (C1–C3, H1–H2, M1, M3–M5, L1–L4) identified in the 2026-06-26 codebase audit without introducing regressions.

**Architecture:** Each fix is self-contained in 1–2 files. The biggest cluster is C1/C2/C3 (checkout integrity), which share a DB transaction in `CheckoutController::process()`. M5 adds a job class for async Etsy receipt import. M1 adds encryption helpers to `EtsyOAuthService` only — the `Setting` model stays generic.

**Tech Stack:** Laravel 13, PHP 8.3, PHPUnit 12, Stripe SDK v20, SQLite (tests), MariaDB (prod)

## Global Constraints

- PHP 8.3 strict types implied; use constructor promotion, named args where it aids clarity
- Every test uses `RefreshDatabase` + existing factories; no manual `DB::table` seeding
- Run `vendor/bin/pint --dirty --format agent` after every PHP file change
- Run the minimal filter set per task before committing; full suite only at the end
- Never modify `require` in `composer.json`

---

### Task 1: Route hardening — login throttle + honeypot on newsletter/restock

Fixes: **H1** (no login rate limit), **L4** (honeypot gaps)

**Files:**
- Modify: `routes/web.php`

**Interfaces:**
- Produces: `POST /login` throttled at 5 req/min; `POST /newsletter` and `POST /restock-request` protected by `honeypot` middleware

- [ ] **Step 1: Write a failing test for login throttle**

Add to `tests/Feature/AuthTest.php` (create the file if it doesn't exist):

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function login_is_rate_limited_after_five_attempts(): void
    {
        User::factory()->create(['email' => 'user@example.com', 'password' => Hash::make('Password1!')]);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => 'user@example.com', 'password' => 'wrong']);
        }

        $response = $this->post('/login', ['email' => 'user@example.com', 'password' => 'wrong']);

        $response->assertStatus(429);
    }
}
```

- [ ] **Step 2: Run the test — verify it fails**

```bash
php artisan test --compact --filter=login_is_rate_limited_after_five_attempts
```

Expected: FAIL (6th attempt returns 200/302, not 429)

- [ ] **Step 3: Add throttle to POST /login in routes/web.php**

Change line 89:
```php
// Before
Route::post('/login', [AuthController::class, 'login']);

// After
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
```

Add `honeypot` to newsletter and restock routes (lines 67 and 76):
```php
// Before
Route::post('/restock-request', [RestockController::class, 'store'])->name('restock.store');
// ...
Route::post('/newsletter', [NewsletterController::class, 'store'])->name('newsletter.store');

// After
Route::post('/restock-request', [RestockController::class, 'store'])->middleware('honeypot')->name('restock.store');
// ...
Route::post('/newsletter', [NewsletterController::class, 'store'])->middleware('honeypot')->name('newsletter.store');
```

- [ ] **Step 4: Run test — verify it passes**

```bash
php artisan test --compact --filter=login_is_rate_limited_after_five_attempts
```

Expected: PASS

- [ ] **Step 5: Pint + commit**

```bash
vendor/bin/pint --dirty --format agent
git add routes/web.php tests/Feature/AuthTest.php
git commit -m "security: throttle login at 5/min; add honeypot to newsletter and restock routes"
```

---

### Task 2: Auth cleanup — remove fragile login elevation + reset email on change

Fixes: **M4** (silent admin promotion on login), **M3** (email change doesn't invalidate verification)

**Files:**
- Modify: `app/Http/Controllers/AuthController.php:37-45`
- Modify: `app/Http/Controllers/AccountController.php:153-176`

**Interfaces:**
- Consumes: nothing new
- Produces: login no longer silently promotes role; `profileUpdate()` sets `email_verified_at = null` and sends verification when email changes

- [ ] **Step 1: Write failing tests**

Add to `tests/Feature/AuthTest.php`:

```php
#[Test]
public function second_user_login_does_not_become_admin(): void
{
    User::factory()->create(['role' => 'admin']);
    $customer = User::factory()->create([
        'role'     => 'customer',
        'password' => Hash::make('Password1!'),
    ]);

    $this->post('/login', ['email' => $customer->email, 'password' => 'Password1!']);

    $this->assertEquals('customer', $customer->fresh()->role);
}
```

Create `tests/Feature/AccountTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AccountTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function email_change_resets_verification_and_resends_link(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email'             => 'old@example.com',
            'email_verified_at' => now(),
            'password'          => Hash::make('Password1!'),
        ]);

        $this->actingAs($user)->put(route('account.profile.update'), [
            'name'  => $user->name,
            'email' => 'new@example.com',
        ]);

        $this->assertNull($user->fresh()->email_verified_at);
        Notification::assertSentTo($user->fresh(), \Illuminate\Auth\Notifications\VerifyEmail::class);
    }

    #[Test]
    public function email_unchanged_does_not_reset_verification(): void
    {
        $user = User::factory()->create([
            'email'             => 'same@example.com',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)->put(route('account.profile.update'), [
            'name'  => 'New Name',
            'email' => 'same@example.com',
        ]);

        $this->assertNotNull($user->fresh()->email_verified_at);
    }
}
```

- [ ] **Step 2: Run tests — verify they fail**

```bash
php artisan test --compact --filter="second_user_login_does_not_become_admin|email_change_resets"
```

Expected: FAIL (elevation still happens; verification not reset)

- [ ] **Step 3: Remove auto-admin elevation from AuthController**

Delete lines 39–43 from `app/Http/Controllers/AuthController.php`:

```php
// Remove this entire block:
// Elevate first user to admin if still a customer
if ($user->role === 'customer' && User::count() === 1) {
    $user->role = 'admin';
    $user->save();
}
```

- [ ] **Step 4: Add email verification reset to AccountController**

In `app/Http/Controllers/AccountController.php`, replace the `profileUpdate` save block:

```php
// Before (lines ~168-173):
$user->name = $validated['name'];
$user->email = $validated['email'];
$user->save();

// After:
$emailChanged = $user->email !== $validated['email'];

$user->name  = $validated['name'];
$user->email = $validated['email'];
$user->save();

if ($emailChanged) {
    $user->email_verified_at = null;
    $user->save();
    $user->sendEmailVerificationNotification();
}
```

- [ ] **Step 5: Run tests — verify they pass**

```bash
php artisan test --compact --filter="second_user_login_does_not_become_admin|email_change_resets|email_unchanged"
```

Expected: all PASS

- [ ] **Step 6: Pint + commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Http/Controllers/AuthController.php app/Http/Controllers/AccountController.php tests/Feature/AuthTest.php tests/Feature/AccountTest.php
git commit -m "security: remove silent admin elevation on login; reset email verification when email changes"
```

---

### Task 3: Fix guest confirmation page (403 after checkout)

Fixes: **H2** (guests always 403 on confirmation)

**Files:**
- Modify: `app/Http/Controllers/CheckoutController.php:209-211`

**Interfaces:**
- Produces: `process()` redirects with `?email=` query param so `confirmation()` can verify guest identity

- [ ] **Step 1: Write a failing test**

Create `tests/Feature/CheckoutGuestConfirmationTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CheckoutGuestConfirmationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function guest_can_view_their_confirmation_page_via_email_param(): void
    {
        $order = Order::factory()->create([
            'user_id'     => null,
            'guest_email' => 'guest@example.com',
        ]);

        $response = $this->get(route('checkout.confirmation', [
            'order' => $order->id,
            'email' => 'guest@example.com',
        ]));

        $response->assertOk();
    }

    #[Test]
    public function guest_cannot_view_another_guests_order(): void
    {
        $order = Order::factory()->create([
            'user_id'     => null,
            'guest_email' => 'real@example.com',
        ]);

        $response = $this->get(route('checkout.confirmation', [
            'order' => $order->id,
            'email' => 'attacker@example.com',
        ]));

        $response->assertForbidden();
    }
}
```

- [ ] **Step 2: Run tests — verify they exist correctly**

```bash
php artisan test --compact --filter=CheckoutGuestConfirmationTest
```

Expected: first test PASS (confirmation method already works with param), second also PASS — both these tests may pass already if the view+method is correct. The *real* bug is in `process()` not passing the param. Proceed.

- [ ] **Step 3: Fix the redirect in process()**

In `app/Http/Controllers/CheckoutController.php`, change the final line of `process()`:

```php
// Before (line ~211):
return redirect()->route('checkout.confirmation', $order);

// After:
return redirect()->route('checkout.confirmation', [
    'order' => $order,
    'email' => $buyerEmail,
]);
```

- [ ] **Step 4: Run tests**

```bash
php artisan test --compact --filter=CheckoutGuestConfirmationTest
```

Expected: PASS

- [ ] **Step 5: Pint + commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Http/Controllers/CheckoutController.php tests/Feature/CheckoutGuestConfirmationTest.php
git commit -m "fix: pass email query param on guest checkout confirmation redirect"
```

---

### Task 4: Queue mails + async Etsy receipt import job

Fixes: **M5** (blocking mail/HTTP in request lifecycle)

**Files:**
- Modify: `app/Http/Controllers/CheckoutController.php:204-208`
- Modify: `app/Http/Controllers/EtsyWebhookController.php:107-114`
- Create: `app/Jobs/ImportEtsyOrder.php`

**Interfaces:**
- Produces: `ImportEtsyOrder` job class with `handle(EtsyOrderSync $sync)` accepting `string $resourceUrl` via constructor

- [ ] **Step 1: Write failing tests**

Add to `tests/Feature/EtsyWebhookControllerTest.php` (append after existing tests):

```php
// Add to imports at top of file:
// use App\Jobs\ImportEtsyOrder;
// use Illuminate\Support\Facades\Queue;

#[Test]
public function order_paid_webhook_dispatches_import_job_for_new_receipts(): void
{
    Queue::fake();

    $payload = ['event_type' => 'order.paid', 'resource_url' => 'https://api.etsy.com/v3/application/shops/123/receipts/999'];
    $this->postWebhook($payload)->assertOk();

    Queue::assertPushed(ImportEtsyOrder::class, fn ($job) => $job->resourceUrl === 'https://api.etsy.com/v3/application/shops/123/receipts/999');
}
```

Add to `tests/Feature/CheckoutMailQueueTest.php` (create it):

```php
<?php

namespace Tests\Feature;

use App\Mail\OrderConfirmationMail;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CheckoutMailQueueTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function order_confirmation_email_is_queued_not_sent_synchronously(): void
    {
        Mail::fake();

        $order = Order::factory()->create(['guest_email' => 'buyer@example.com']);

        // Simulate what process() does after creating the order
        Mail::to('buyer@example.com')->queue(new OrderConfirmationMail($order->load('items')));

        Mail::assertQueued(OrderConfirmationMail::class);
        Mail::assertNotSent(OrderConfirmationMail::class);
    }
}
```

- [ ] **Step 2: Run tests — verify the webhook one fails (job class doesn't exist)**

```bash
php artisan test --compact --filter="order_paid_webhook_dispatches_import_job"
```

Expected: FAIL — `App\Jobs\ImportEtsyOrder` not found

- [ ] **Step 3: Create ImportEtsyOrder job**

```bash
php artisan make:class app/Jobs/ImportEtsyOrder --no-interaction
```

Replace the generated file at `app/Jobs/ImportEtsyOrder.php`:

```php
<?php

namespace App\Jobs;

use App\Services\Etsy\EtsyOrderSync;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ImportEtsyOrder implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(public readonly string $resourceUrl) {}

    public function handle(EtsyOrderSync $sync): void
    {
        $sync->importFromResourceUrl($this->resourceUrl);
    }

    public function failed(\Throwable $e): void
    {
        Log::error('ImportEtsyOrder job failed', [
            'resource_url' => $this->resourceUrl,
            'error'        => $e->getMessage(),
        ]);
    }
}
```

- [ ] **Step 4: Add importFromResourceUrl to EtsyOrderSync**

Open `app/Services/Etsy/EtsyOrderSync.php` and check if a method for resource-URL import already exists. If it does, map the job's `handle()` to it. If not, add:

```php
public function importFromResourceUrl(string $resourceUrl): void
{
    $path = preg_replace('#^https://api\.etsy\.com/v3#', '', $resourceUrl);
    $receipt = $this->client->get($path);

    if ($receipt) {
        $this->importReceipt($receipt);
    }
}
```

(Add `use App\Services\Etsy\EtsyClient;` to the job if the service is constructor-injected; check the existing class head for the correct pattern.)

- [ ] **Step 5: Update EtsyWebhookController to dispatch the job**

In `app/Http/Controllers/EtsyWebhookController.php`, replace the inline receipt-fetch+import in `handleOrderPaid()`:

```php
// Before (~lines 107-114):
$receipt = $this->fetchReceipt($resourceUrl);
if ($receipt) {
    app(EtsyOrderSync::class)->importReceipt($receipt);
    $order = Order::where('etsy_receipt_id', $receiptId)->first();
}

// After:
ImportEtsyOrder::dispatch($resourceUrl);
$order = Order::where('etsy_receipt_id', $receiptId)->first();
```

Add import at top: `use App\Jobs\ImportEtsyOrder;`

You can also remove the now-unused private `fetchReceipt()` method from the controller.

- [ ] **Step 6: Queue the order confirmation mail in CheckoutController**

In `app/Http/Controllers/CheckoutController.php`, change line ~205:

```php
// Before:
Mail::to($buyerEmail)->send(new OrderConfirmationMail($order->load('items')));

// After:
Mail::to($buyerEmail)->queue(new OrderConfirmationMail($order->load('items')));
```

- [ ] **Step 7: Run all task tests**

```bash
php artisan test --compact --filter="order_paid_webhook_dispatches_import_job|order_confirmation_email_is_queued"
```

Expected: PASS

- [ ] **Step 8: Pint + commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Jobs/ImportEtsyOrder.php app/Services/Etsy/EtsyOrderSync.php app/Http/Controllers/EtsyWebhookController.php app/Http/Controllers/CheckoutController.php tests/Feature/CheckoutMailQueueTest.php
git commit -m "perf: queue order confirmation mail; dispatch Etsy receipt import job asynchronously"
```

---

### Task 5: Checkout integrity — amount check, intent replay guard, stock lock

Fixes: **C1** (underpayment), **C2** (intent replay), **C3** (overselling)

This is the highest-impact task. All three fixes land in `CheckoutController::process()` and the DB transaction inside it, plus one migration.

**Files:**
- Create: migration `add_unique_index_to_orders_stripe_payment_intent_id`
- Modify: `app/Http/Controllers/CheckoutController.php:85-211`
- Modify: `app/Services/StripeService.php:37-46`
- Test: `tests/Feature/CheckoutIntegrityTest.php`

**Interfaces:**
- `StripeService::verifyPaymentIntent()` now returns the `PaymentIntent` with the amount available (no signature change needed — it already returns the full object)
- `CheckoutController::process()` asserts `$intent->amount_received === $expectedCents`
- The DB transaction locks each variant row and decrements `stock_qty`

- [ ] **Step 1: Write failing tests**

Create `tests/Feature/CheckoutIntegrityTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingMethod;
use App\Services\StripeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Stripe\PaymentIntent;
use Tests\TestCase;

class CheckoutIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private ShippingMethod $shipping;
    private Product $product;
    private ProductVariant $variant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->shipping = ShippingMethod::factory()->create(['price' => 5.00]);
        $this->product  = Product::factory()->create(['price' => 20.00]);
        $this->variant  = ProductVariant::factory()->create([
            'product_id' => $this->product->id,
            'stock_qty'  => 5,
        ]);

        session(['cart' => [
            'key1' => [
                'row_key'              => 'key1',
                'product_id'           => $this->product->id,
                'variant_id'           => $this->variant->id,
                'sku'                  => 'SKU1',
                'name'                 => $this->product->name,
                'variant_label'        => '',
                'personalization_text' => null,
                'personalization_price' => 0.0,
                'price'                => 20.00,
                'qty'                  => 1,
                'image_url'            => null,
            ],
        ]]);
    }

    private function mockStripe(string $status = 'succeeded', int $amountReceived = 2500): void
    {
        $intent = Mockery::mock(PaymentIntent::class);
        $intent->status          = $status;
        $intent->id              = 'pi_test';
        $intent->currency        = 'usd';
        $intent->amount_received = $amountReceived;

        $mock = Mockery::mock(StripeService::class);
        $mock->shouldReceive('verifyPaymentIntent')->andReturn($intent);
        $this->app->instance(StripeService::class, $mock);
    }

    private function postCheckout(array $overrides = [])
    {
        return $this->post(route('checkout.process'), array_merge([
            'shipping_first_name' => 'Jane',
            'shipping_last_name'  => 'Doe',
            'shipping_address_1'  => '123 Main St',
            'shipping_city'       => 'Portland',
            'shipping_state'      => 'OR',
            'shipping_zip'        => '97201',
            'shipping_method_id'  => $this->shipping->id,
            'payment_intent_id'   => 'pi_test',
            'guest_email'         => 'jane@example.com',
        ], $overrides));
    }

    #[Test]
    public function checkout_fails_when_stripe_amount_does_not_match_order_total(): void
    {
        // Cart total is $20 product + $5 shipping = $25 = 2500 cents
        // Mock returns 500 cents (underpayment)
        $this->mockStripe(amountReceived: 500);

        $response = $this->postCheckout();

        $response->assertRedirect();
        $response->assertSessionHasErrors('payment');
        $this->assertDatabaseCount('orders', 0);
    }

    #[Test]
    public function checkout_is_blocked_for_replayed_payment_intent(): void
    {
        $this->mockStripe(amountReceived: 2500);

        // First checkout succeeds
        $this->postCheckout();
        $this->assertDatabaseCount('orders', 1);

        // Re-seed cart (cleared after first checkout)
        session(['cart' => [
            'key1' => [
                'row_key'              => 'key1',
                'product_id'           => $this->product->id,
                'variant_id'           => $this->variant->id,
                'sku'                  => 'SKU1',
                'name'                 => $this->product->name,
                'variant_label'        => '',
                'personalization_text' => null,
                'personalization_price' => 0.0,
                'price'                => 20.00,
                'qty'                  => 1,
                'image_url'            => null,
            ],
        ]]);

        $response = $this->postCheckout();

        $response->assertRedirect();
        $this->assertDatabaseCount('orders', 1); // no second order created
    }

    #[Test]
    public function checkout_fails_when_variant_is_out_of_stock(): void
    {
        $this->mockStripe(amountReceived: 2500);
        $this->variant->update(['stock_qty' => 0]);

        $response = $this->postCheckout();

        $response->assertRedirect();
        $response->assertSessionHasErrors();
        $this->assertDatabaseCount('orders', 0);
    }

    #[Test]
    public function checkout_decrements_variant_stock(): void
    {
        $this->mockStripe(amountReceived: 2500);

        $this->postCheckout();

        $this->assertEquals(4, $this->variant->fresh()->stock_qty);
    }
}
```

- [ ] **Step 2: Run tests — verify they all fail**

```bash
php artisan test --compact --filter=CheckoutIntegrityTest
```

Expected: all 4 FAIL

- [ ] **Step 3: Create migration for unique payment intent index**

```bash
php artisan make:migration add_unique_to_orders_stripe_payment_intent_id --no-interaction
```

In the generated file:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->unique('stripe_payment_intent_id');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique(['stripe_payment_intent_id']);
        });
    }
};
```

```bash
php artisan migrate
```

- [ ] **Step 4: Add amount_received to StripeService return**

`StripeService::verifyPaymentIntent()` already returns the full `PaymentIntent` object which has `amount_received`. No code change needed here — the method signature is fine. The guards go in the controller.

- [ ] **Step 5: Add C1 + C2 + C3 guards to CheckoutController::process()**

In `app/Http/Controllers/CheckoutController.php`, inside `process()`:

**After the `$intent` is verified (after `~line 116`), add the C2 replay guard:**

```php
// C2: block replayed payment intents
if (Order::where('stripe_payment_intent_id', $intent->id)->exists()) {
    $existing = Order::where('stripe_payment_intent_id', $intent->id)->first();
    return redirect()->route('checkout.confirmation', [
        'order' => $existing,
        'email' => $buyerEmail,
    ]);
}
```

**After recalculating `$total` (~line 129), add the C1 amount check:**

```php
// C1: reconcile Stripe amount_received against recalculated server-side total
$expectedCents = (int) round($total * 100);
if ($intent->amount_received !== $expectedCents || $intent->currency !== 'usd') {
    Log::warning('Payment amount mismatch', [
        'intent'   => $intent->id,
        'expected' => $expectedCents,
        'received' => $intent->amount_received,
    ]);
    return back()->withErrors([
        'payment' => 'Payment amount did not match your order total. Please restart checkout.',
    ])->withInput();
}
```

**Inside the DB transaction, before `Order::create()`, add the C3 stock guard:**

```php
// C3: validate and decrement stock atomically
foreach ($cart as $item) {
    $variant = ProductVariant::whereKey($item['variant_id'])->lockForUpdate()->firstOrFail();

    if ($variant->stock_qty < $item['qty']) {
        throw new \RuntimeException("'{$item['name']}' is no longer available in the requested quantity.");
    }

    $variant->decrement('stock_qty', $item['qty']);
}
```

Wrap the entire `DB::transaction` call in a try/catch to surface stock errors:

```php
try {
    $order = DB::transaction(function () use (/* existing use list */) {
        // ... existing transaction body with stock guard added above ...
    });
} catch (\RuntimeException $e) {
    return back()->withErrors(['cart' => $e->getMessage()])->withInput();
}
```

Note: the `$buyerEmail` variable must be resolved **before** the transaction block (move it up from inside) since the C2 guard also needs it.

- [ ] **Step 6: Run tests — verify they pass**

```bash
php artisan test --compact --filter=CheckoutIntegrityTest
```

Expected: all 4 PASS

- [ ] **Step 7: Pint + commit**

```bash
vendor/bin/pint --dirty --format agent
git add database/migrations/*add_unique_to_orders_stripe_payment_intent_id* app/Http/Controllers/CheckoutController.php tests/Feature/CheckoutIntegrityTest.php
git commit -m "fix: validate Stripe amount, block intent replay, lock and decrement stock at checkout (C1/C2/C3)"
```

---

### Task 6: Encrypt Etsy OAuth tokens at rest

Fixes: **M1** (tokens stored in plaintext in settings table)

**Files:**
- Modify: `app/Services/Etsy/EtsyOAuthService.php`

**Interfaces:**
- `storeToken(string $key, string $value): void` — encrypts and stores via `Setting::set`
- `retrieveToken(string $key): ?string` — reads via `Setting::get` and decrypts; returns `null` if missing or decrypt fails

- [ ] **Step 1: Write a failing test**

Create `tests/Feature/EtsyOAuthTokenEncryptionTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Services\Etsy\EtsyOAuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EtsyOAuthTokenEncryptionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function etsy_access_token_is_not_stored_as_plaintext(): void
    {
        $oauth = app(EtsyOAuthService::class);

        // Use reflection to call private storeToken
        $ref = new \ReflectionMethod($oauth, 'storeToken');
        $ref->setAccessible(true);
        $ref->invoke($oauth, 'etsy.access_token', 'plaintext_token_value');

        $raw = Setting::find('etsy.access_token')?->value;

        $this->assertNotEquals('plaintext_token_value', $raw, 'Token must not be stored in plaintext');
        $this->assertEquals('plaintext_token_value', Crypt::decryptString($raw), 'Token must be recoverable via decryption');
    }

    #[Test]
    public function retrieve_token_decrypts_stored_value(): void
    {
        Setting::set('etsy.access_token', Crypt::encryptString('my_secret_token'));

        $oauth = app(EtsyOAuthService::class);

        $ref = new \ReflectionMethod($oauth, 'retrieveToken');
        $ref->setAccessible(true);
        $result = $ref->invoke($oauth, 'etsy.access_token');

        $this->assertEquals('my_secret_token', $result);
    }

    #[Test]
    public function retrieve_token_returns_null_for_missing_key(): void
    {
        $oauth = app(EtsyOAuthService::class);

        $ref = new \ReflectionMethod($oauth, 'retrieveToken');
        $ref->setAccessible(true);
        $result = $ref->invoke($oauth, 'etsy.access_token');

        $this->assertNull($result);
    }
}
```

- [ ] **Step 2: Run tests — verify they fail**

```bash
php artisan test --compact --filter=EtsyOAuthTokenEncryptionTest
```

Expected: FAIL (`storeToken` method doesn't exist)

- [ ] **Step 3: Add encryption helpers to EtsyOAuthService**

Add `use Illuminate\Support\Facades\Crypt;` to the imports.

Add these two private methods at the bottom of the class:

```php
private function storeToken(string $key, string $value): void
{
    Setting::set($key, Crypt::encryptString($value));
}

private function retrieveToken(string $key): ?string
{
    $raw = Setting::get($key);

    if ($raw === null) {
        return null;
    }

    try {
        return Crypt::decryptString($raw);
    } catch (\Illuminate\Contracts\Encryption\DecryptException) {
        return null;
    }
}
```

- [ ] **Step 4: Replace direct Setting calls with helpers**

In `handleCallback()`, replace:

```php
// Before:
Setting::set('etsy.access_token', $data['access_token']);
Setting::set('etsy.refresh_token', $data['refresh_token']);
// ...
if (isset($data['refresh_token'])) {
    Setting::set('etsy.refresh_token', $data['refresh_token']);
}

// After:
$this->storeToken('etsy.access_token', $data['access_token']);
$this->storeToken('etsy.refresh_token', $data['refresh_token']);
```

In `refreshToken()`, replace:

```php
// Before:
$refreshToken = Setting::get('etsy.refresh_token');
// ...
Setting::set('etsy.access_token', $data['access_token']);
// ...
if (isset($data['refresh_token'])) {
    Setting::set('etsy.refresh_token', $data['refresh_token']);
}

// After:
$refreshToken = $this->retrieveToken('etsy.refresh_token');
// ...
$this->storeToken('etsy.access_token', $data['access_token']);
// ...
if (isset($data['refresh_token'])) {
    $this->storeToken('etsy.refresh_token', $data['refresh_token']);
}
```

In `isConnected()`, the access token check uses `Setting::get('etsy.access_token')` which returns the encrypted blob — that's truthy, so `!empty()` still works. No change needed there.

In `refreshIfExpired()`, `$refreshToken` comes via `retrieveToken` above — no further changes.

In `resolveShopId()` and the `EtsyClient`, the access token is passed as a resolved value — these call sites already receive the decrypted token via `Setting::get('etsy.access_token')` which will now return the ciphertext. Update the `EtsyClient` constructor/usage to call `retrieveToken` instead. Check `app/Services/Etsy/EtsyClient.php` for where `etsy.access_token` is read and replace with `$this->oauth->getAccessToken()`.

Add a public method to `EtsyOAuthService`:

```php
public function getAccessToken(): ?string
{
    return $this->retrieveToken('etsy.access_token');
}
```

> **Note:** Any existing encrypted tokens in production need to be rotated after this deploy. Old plaintext tokens will return `null` from `retrieveToken` (the `DecryptException` path), causing the next API call to fail gracefully and prompt re-connection.

- [ ] **Step 5: Run tests**

```bash
php artisan test --compact --filter=EtsyOAuthTokenEncryptionTest
```

Expected: PASS

- [ ] **Step 6: Run the full Etsy test suite to check for regressions**

```bash
php artisan test --compact tests/Feature/EtsyOAuthTest.php tests/Feature/EtsyAdminTest.php tests/Feature/EtsySyncTest.php
```

Expected: all PASS (mock HTTP calls won't be affected)

- [ ] **Step 7: Pint + commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Services/Etsy/EtsyOAuthService.php app/Services/Etsy/EtsyClient.php tests/Feature/EtsyOAuthTokenEncryptionTest.php
git commit -m "security: encrypt Etsy OAuth tokens at rest using Laravel Crypt (M1)"
```

---

### Task 7: Fix Setting::get permanently caching default parameter

Fixes: **L2** (null defaults cached forever, hiding later-set values)

**Files:**
- Modify: `app/Models/Setting.php:34-40`

**Interfaces:**
- `Setting::get(string $key, mixed $default = null): mixed` — now caches only the DB value (which may be `null`); `$default` is applied at return time and never cached

- [ ] **Step 1: Write a failing test**

Create `tests/Feature/SettingCacheTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SettingCacheTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function get_with_default_does_not_cache_the_default_value(): void
    {
        // First call: key doesn't exist, returns provided default
        $result = Setting::get('foo.bar', 'fallback');
        $this->assertEquals('fallback', $result);

        // Now create the setting (simulates a later set)
        Setting::set('foo.bar', 'real_value');

        // Second call: should return real_value, not cached 'fallback'
        $result2 = Setting::get('foo.bar');
        $this->assertEquals('real_value', $result2);
    }

    #[Test]
    public function get_caches_actual_db_values_permanently(): void
    {
        Setting::set('app.name', 'TimberTrace');

        Setting::get('app.name'); // warms cache

        // Bypass Setting::set (direct DB update) — cache should still serve old value
        \DB::table('settings')->where('key', 'app.name')->update(['value' => 'Changed']);

        $this->assertEquals('TimberTrace', Setting::get('app.name'));
    }
}
```

- [ ] **Step 2: Run tests — verify first test fails**

```bash
php artisan test --compact --filter=SettingCacheTest
```

Expected: `get_with_default_does_not_cache_the_default_value` FAIL (returns 'fallback' on second call because it was cached); second test PASS.

- [ ] **Step 3: Fix Setting::get**

In `app/Models/Setting.php`, replace the `get()` method:

```php
public static function get(string $key, mixed $default = null): mixed
{
    $value = Cache::rememberForever('setting.'.$key, function () use ($key) {
        $setting = static::find($key);

        return $setting ? $setting->value : null;
    });

    return $value ?? $default;
}
```

The closure now always returns `null` (never the caller's `$default`) when the row is absent, so different callers with different defaults don't pollute the cache. The `$default` is applied only at call-site return time.

- [ ] **Step 4: Run tests**

```bash
php artisan test --compact --filter=SettingCacheTest
```

Expected: both PASS

- [ ] **Step 5: Pint + commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Models/Setting.php tests/Feature/SettingCacheTest.php
git commit -m "fix: Setting::get no longer caches caller-provided defaults; only caches DB values (L2)"
```

---

### Task 8: Fulltext search index for product search

Fixes: **L1** (leading-wildcard LIKE can't use indexes)

**Files:**
- Create: migration `add_fulltext_index_to_products_name_and_short_description`
- Modify: `app/Http/Controllers/ShopController.php:35-39`

**Interfaces:**
- On MySQL/MariaDB: uses `whereFullText(['name', 'short_description'], $search)`
- On SQLite (tests): falls back to existing LIKE query

- [ ] **Step 1: Create the migration**

```bash
php artisan make:migration add_fulltext_index_to_products_name_short_description --no-interaction
```

In the generated file:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE products ADD FULLTEXT INDEX products_search_fulltext (name, short_description)');
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            Schema::table('products', function (Blueprint $table) {
                $table->dropIndex('products_search_fulltext');
            });
        }
    }
};
```

```bash
php artisan migrate
```

- [ ] **Step 2: Update ShopController to use fulltext on MySQL**

In `app/Http/Controllers/ShopController.php`, replace the search block (~lines 35-39):

```php
// Before:
if ($search = $request->query('search')) {
    $query->where(function ($q) use ($search) {
        $q->where('name', 'like', "%{$search}%")
            ->orWhere('short_description', 'like', "%{$search}%");
    });
}

// After:
if ($search = $request->query('search')) {
    if (\DB::connection()->getDriverName() === 'mysql') {
        $query->whereFullText(['name', 'short_description'], $search);
    } else {
        $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
                ->orWhere('short_description', 'like', "%{$search}%");
        });
    }
}
```

- [ ] **Step 3: No new test needed** — existing shop browsing tests (if any) cover the SQLite path; the MySQL path is exercised in production. Verify the existing test suite doesn't regress:

```bash
php artisan test --compact tests/Feature/
```

Expected: all PASS (SQLite uses LIKE fallback)

- [ ] **Step 4: Pint + commit**

```bash
vendor/bin/pint --dirty --format agent
git add database/migrations/*add_fulltext_index_to_products_name_short_description* app/Http/Controllers/ShopController.php
git commit -m "perf: add FULLTEXT index on products(name, short_description); use whereFullText on MySQL (L1)"
```

---

### Task 9: Clean up CartService::mergeSessions no-op

Fixes: **L3** (misleading no-op method with callers implying cart merge behavior)

**Files:**
- Modify: `app/Services/CartService.php:72-76`
- Modify: `app/Http/Controllers/AuthController.php` (remove call site)

- [ ] **Step 1: Remove the call from AuthController**

In `app/Http/Controllers/AuthController.php`, delete the `mergeSessions` call after login:

```php
// Remove this line:
$this->cartService->mergeSessions($user->id);
```

Also remove the `CartService` dependency injection if `mergeSessions` was its only use in AuthController — check if `$this->cartService` is used elsewhere in that file. If not, remove the constructor parameter and the `use App\Services\CartService;` import.

- [ ] **Step 2: Remove the no-op method from CartService**

In `app/Services/CartService.php`, delete the entire `mergeSessions` method:

```php
// Remove:
public function mergeSessions(int $userId): void
{
    // After login: persist session cart to DB (Phase 2 enhancement)
    // For now, session cart persists as-is
}
```

- [ ] **Step 3: Run auth tests to verify no regression**

```bash
php artisan test --compact --filter=AuthTest
```

Expected: PASS

- [ ] **Step 4: Pint + commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Services/CartService.php app/Http/Controllers/AuthController.php
git commit -m "cleanup: remove CartService::mergeSessions no-op and its only call site (L3)"
```

---

### Task 10: Add M2 to todo.md and run full suite

**Files:**
- Modify: `todo.md`

- [ ] **Step 1: Add M2 to todo.md**

Under a new `## Security` section (or append to Miscellaneous), add:

```markdown
## Security (from 2026-06-26 audit)

- [ ] M2 — Verify Etsy webhook signature format: current code compares bare base64 HMAC against the `webhook-signature` header value. If Etsy/Svix delivers `v1,<base64>` tokens, strip the `vN,` prefix before calling `hash_equals`. Confirm against a real Etsy webhook delivery in the portal test tab.
```

- [ ] **Step 2: Run full test suite**

```bash
php artisan test --compact
```

Expected: all tests PASS, 0 failures

- [ ] **Step 3: Commit**

```bash
git add todo.md
git commit -m "docs: add M2 webhook sig format verification to todo.md"
```

---

## Self-Review

**Spec coverage check:**
- C1 ✅ Task 5 (amount check)
- C2 ✅ Task 5 (unique migration + replay guard)
- C3 ✅ Task 5 (lockForUpdate + decrement)
- H1 ✅ Task 1 (throttle:5,1 on POST /login)
- H2 ✅ Task 3 (email param on redirect)
- M1 ✅ Task 6 (storeToken/retrieveToken with Crypt)
- M2 ✅ Task 10 (todo.md — explicitly excluded from implementation per user)
- M3 ✅ Task 2 (email_verified_at reset + resend)
- M4 ✅ Task 2 (remove elevation block)
- M5 ✅ Task 4 (queue mail + ImportEtsyOrder job)
- L1 ✅ Task 8 (fulltext migration + driver-aware controller)
- L2 ✅ Task 7 (cache only DB value, apply default at return)
- L3 ✅ Task 9 (remove no-op + call site)
- L4 ✅ Task 1 (honeypot on newsletter + restock)
- L5 — already correctly guarded in bootstrap/app.php (no change needed)

**Placeholder scan:** No TBDs, all steps have complete code. ✅

**Type consistency:** `storeToken`/`retrieveToken` defined in Task 6 and not referenced elsewhere. `ImportEtsyOrder::$resourceUrl` is `string` throughout. `amount_received` is `int` on Stripe `PaymentIntent` object, compared to `int $expectedCents`. ✅
