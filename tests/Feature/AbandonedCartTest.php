<?php

namespace Tests\Feature;

use App\Mail\AbandonedCartMail;
use App\Models\Cart;
use App\Models\CartEmailSuppression;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AbandonedCartTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Persist a cart row directly, bypassing the session flow, so command
     * behaviour can be tested against known timestamps/stages.
     */
    private function makeCart(array $attributes = []): Cart
    {
        return Cart::create(array_merge([
            'token' => (string) Str::uuid(),
            'user_id' => null,
            'email' => 'shopper@example.com',
            'contents' => [[
                'name' => 'Engraved Cutting Board',
                'variant_label' => 'Walnut',
                'qty' => 1,
                'price' => 42.00,
                'personalization_price' => 0,
            ]],
            'item_count' => 1,
            'subtotal' => 42.00,
            'unsubscribe_token' => (string) Str::uuid(),
            'last_activity_at' => now()->subHours(6),
            'reminder_stage' => 0,
            'converted_at' => null,
        ], $attributes));
    }

    #[Test]
    public function adding_to_cart_persists_a_cart_row(): void
    {
        $product = Product::factory()->create(['price' => 25.00, 'sale_price' => null]);
        $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'price' => null]);

        $this->post(route('cart.add'), [
            'product_id' => $product->id,
            'variant_id' => $variant->id,
            'qty' => 2,
        ]);

        $this->assertDatabaseCount('carts', 1);
        $cart = Cart::first();
        $this->assertSame(2, $cart->item_count);
        $this->assertSame(50.0, (float) $cart->subtotal);
        $this->assertNotNull($cart->unsubscribe_token);
    }

    #[Test]
    public function command_does_nothing_when_disabled(): void
    {
        config(['cart.reminders.enabled' => false]);
        Mail::fake();

        $user = User::factory()->create(['email' => 'shopper@example.com']);
        $this->makeCart(['user_id' => $user->id]);

        $this->artisan('cart:send-abandoned-reminders')->assertSuccessful();

        Mail::assertNothingSent();
    }

    #[Test]
    public function command_sends_a_reminder_to_an_idle_logged_in_cart(): void
    {
        config(['cart.reminders.enabled' => true]);
        Mail::fake();

        $user = User::factory()->create(['email' => 'shopper@example.com']);
        $this->makeCart(['user_id' => $user->id, 'last_activity_at' => now()->subHours(6)]);

        $this->artisan('cart:send-abandoned-reminders')->assertSuccessful();

        Mail::assertQueued(AbandonedCartMail::class, 1);
        $this->assertSame(1, Cart::first()->reminder_stage);
        $this->assertNotNull(Cart::first()->reminder_sent_at);
    }

    #[Test]
    public function reminders_are_idempotent_across_runs(): void
    {
        config(['cart.reminders.enabled' => true]);
        Mail::fake();

        $user = User::factory()->create(['email' => 'shopper@example.com']);
        $this->makeCart(['user_id' => $user->id, 'last_activity_at' => now()->subHours(6)]);

        $this->artisan('cart:send-abandoned-reminders');
        // Second run: still only 6h idle, stage 1 already sent, stage 2 not due.
        $this->artisan('cart:send-abandoned-reminders');

        Mail::assertQueued(AbandonedCartMail::class, 1);
    }

    #[Test]
    public function second_stage_fires_after_the_longer_idle_window(): void
    {
        config(['cart.reminders.enabled' => true]);
        Mail::fake();

        $user = User::factory()->create(['email' => 'shopper@example.com']);
        // Idle 30h and stage 1 already sent → stage 2 (24h) is due.
        $this->makeCart([
            'user_id' => $user->id,
            'last_activity_at' => now()->subHours(30),
            'reminder_stage' => 1,
            'reminder_sent_at' => now()->subHours(20),
        ]);

        $this->artisan('cart:send-abandoned-reminders')->assertSuccessful();

        Mail::assertQueued(AbandonedCartMail::class, 1);
        $this->assertSame(2, Cart::first()->reminder_stage);
    }

    #[Test]
    public function guest_carts_are_skipped_unless_enabled(): void
    {
        config(['cart.reminders.enabled' => true, 'cart.reminders.email_guests' => false]);
        Mail::fake();

        $this->makeCart(['user_id' => null, 'email' => 'guest@example.com']);

        $this->artisan('cart:send-abandoned-reminders')->assertSuccessful();
        Mail::assertNothingSent();

        // Flip the guest flag and it should now send.
        config(['cart.reminders.email_guests' => true]);
        $this->artisan('cart:send-abandoned-reminders')->assertSuccessful();
        Mail::assertQueued(AbandonedCartMail::class, 1);
    }

    #[Test]
    public function suppressed_emails_are_never_sent(): void
    {
        config(['cart.reminders.enabled' => true]);
        Mail::fake();

        $user = User::factory()->create(['email' => 'shopper@example.com']);
        $this->makeCart(['user_id' => $user->id]);
        CartEmailSuppression::create(['email' => 'shopper@example.com', 'created_at' => now()]);

        $this->artisan('cart:send-abandoned-reminders')->assertSuccessful();

        Mail::assertNothingSent();
    }

    #[Test]
    public function carts_with_a_matching_order_are_marked_converted_not_emailed(): void
    {
        config(['cart.reminders.enabled' => true]);
        Mail::fake();

        $user = User::factory()->create(['email' => 'shopper@example.com']);
        $this->makeCart(['user_id' => $user->id]);
        Order::factory()->create(['user_id' => $user->id, 'guest_email' => null]);

        $this->artisan('cart:send-abandoned-reminders')->assertSuccessful();

        Mail::assertNothingSent();
        $this->assertNotNull(Cart::first()->converted_at);
    }

    #[Test]
    public function stale_carts_beyond_max_age_are_ignored(): void
    {
        config(['cart.reminders.enabled' => true, 'cart.reminders.max_age_hours' => 168]);
        Mail::fake();

        $user = User::factory()->create(['email' => 'shopper@example.com']);
        $this->makeCart(['user_id' => $user->id, 'last_activity_at' => now()->subHours(200)]);

        $this->artisan('cart:send-abandoned-reminders')->assertSuccessful();
        Mail::assertNothingSent();
    }

    #[Test]
    public function unsubscribe_link_suppresses_the_email(): void
    {
        $cart = $this->makeCart(['email' => 'optout@example.com']);

        $response = $this->get(route('cart.unsubscribe', ['token' => $cart->unsubscribe_token]));

        $response->assertOk();
        $this->assertTrue(CartEmailSuppression::suppresses('optout@example.com'));
    }
}
