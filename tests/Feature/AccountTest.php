<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Models\Wishlist;
use Illuminate\Auth\Notifications\VerifyEmail;
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
            'email' => 'old@example.com',
            'email_verified_at' => now(),
            'password' => Hash::make('Password1!'),
        ]);

        $this->actingAs($user)->put(route('account.profile.update'), [
            'name' => $user->name,
            'email' => 'new@example.com',
            'current_password' => 'Password1!',
        ]);

        $this->assertNull($user->fresh()->email_verified_at);
        Notification::assertSentTo($user->fresh(), VerifyEmail::class);
    }

    #[Test]
    public function email_change_without_current_password_is_rejected(): void
    {
        $user = User::factory()->create([
            'email' => 'old@example.com',
            'password' => Hash::make('Password1!'),
        ]);

        $response = $this->actingAs($user)->put(route('account.profile.update'), [
            'name' => $user->name,
            'email' => 'new@example.com',
        ]);

        $response->assertSessionHasErrors('current_password');
        $this->assertEquals('old@example.com', $user->fresh()->email);
    }

    #[Test]
    public function email_change_with_wrong_current_password_is_rejected(): void
    {
        $user = User::factory()->create([
            'email' => 'old@example.com',
            'password' => Hash::make('Password1!'),
        ]);

        $response = $this->actingAs($user)->put(route('account.profile.update'), [
            'name' => $user->name,
            'email' => 'new@example.com',
            'current_password' => 'WrongPassword1!',
        ]);

        $response->assertSessionHasErrors('current_password');
        $this->assertEquals('old@example.com', $user->fresh()->email);
    }

    #[Test]
    public function email_unchanged_does_not_reset_verification(): void
    {
        $user = User::factory()->create([
            'email' => 'same@example.com',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)->put(route('account.profile.update'), [
            'name' => 'New Name',
            'email' => 'same@example.com',
        ]);

        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    #[Test]
    public function a_user_cannot_view_another_users_order(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($other)
            ->get(route('account.orders.show', $order))
            ->assertForbidden();
    }

    #[Test]
    public function a_user_can_view_their_own_order(): void
    {
        $owner = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($owner)
            ->get(route('account.orders.show', $order))
            ->assertOk();
    }

    #[Test]
    public function a_user_cannot_view_another_users_order_invoice(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($other)
            ->get(route('account.orders.invoice', $order))
            ->assertForbidden();
    }

    #[Test]
    public function wishlist_add_persists_the_variant(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->create(['product_id' => $product->id]);

        $this->actingAs($user)->post(route('account.wishlist.add'), [
            'variant_id' => $variant->id,
        ])->assertRedirect();

        $this->assertDatabaseHas('wishlists', [
            'user_id' => $user->id,
            'product_variant_id' => $variant->id,
        ]);
    }

    #[Test]
    public function wishlist_remove_deletes_the_variant(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->create(['product_id' => $product->id]);

        Wishlist::create([
            'user_id' => $user->id,
            'product_variant_id' => $variant->id,
        ]);

        $this->actingAs($user)->delete(route('account.wishlist.remove', $variant->id))
            ->assertRedirect();

        $this->assertDatabaseMissing('wishlists', [
            'user_id' => $user->id,
            'product_variant_id' => $variant->id,
        ]);
    }

    #[Test]
    public function reorder_adds_available_lines_to_the_cart_at_current_price(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['status' => 'active', 'price' => 30.00]);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'is_enabled' => true,
            'price' => null,
            'stock_qty' => 5,
        ]);

        $order = Order::factory()->create(['user_id' => $user->id]);
        $order->items()->create([
            'product_id' => $product->id,
            'variant_id' => $variant->id,
            'name_snapshot' => $product->name,
            'sku_snapshot' => 'SKU-'.uniqid(),
            // Snapshot price is stale; reorder must use the current product price.
            'price_snapshot' => 12.00,
            'qty' => 2,
            'subtotal' => 24.00,
        ]);

        $response = $this->actingAs($user)->post(route('account.orders.reorder', $order));

        $response->assertRedirect(route('cart.index'));
        $response->assertSessionHas('success');

        $cart = session('cart');
        $this->assertCount(1, $cart);
        $line = array_values($cart)[0];
        $this->assertSame($variant->id, $line['variant_id']);
        $this->assertSame(2, $line['qty']);
        // Current product price (30), not the stale snapshot (12).
        $this->assertEquals(30.00, $line['price']);
    }

    #[Test]
    public function reorder_skips_unavailable_lines_and_reports_them(): void
    {
        $user = User::factory()->create();

        $active = Product::factory()->create(['status' => 'active', 'price' => 20.00]);
        $activeVariant = ProductVariant::factory()->create([
            'product_id' => $active->id,
            'is_enabled' => true,
            'stock_qty' => 5,
        ]);

        $inactive = Product::factory()->create(['status' => 'draft', 'price' => 20.00]);
        $inactiveVariant = ProductVariant::factory()->create([
            'product_id' => $inactive->id,
            'is_enabled' => true,
            'stock_qty' => 5,
        ]);

        $order = Order::factory()->create(['user_id' => $user->id]);
        $order->items()->create([
            'product_id' => $active->id,
            'variant_id' => $activeVariant->id,
            'name_snapshot' => $active->name,
            'sku_snapshot' => 'SKU-'.uniqid(),
            'price_snapshot' => 20.00,
            'qty' => 1,
            'subtotal' => 20.00,
        ]);
        $order->items()->create([
            'product_id' => $inactive->id,
            'variant_id' => $inactiveVariant->id,
            'name_snapshot' => 'Discontinued Piece',
            'sku_snapshot' => 'SKU-'.uniqid(),
            'price_snapshot' => 20.00,
            'qty' => 1,
            'subtotal' => 20.00,
        ]);

        $response = $this->actingAs($user)->post(route('account.orders.reorder', $order));

        $response->assertRedirect(route('cart.index'));
        $this->assertCount(1, session('cart'));
        $this->assertStringContainsString('Discontinued Piece', session('success'));
    }

    #[Test]
    public function reorder_with_no_available_lines_redirects_back_with_error(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['status' => 'draft', 'price' => 20.00]);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'is_enabled' => true,
            'stock_qty' => 5,
        ]);

        $order = Order::factory()->create(['user_id' => $user->id]);
        $order->items()->create([
            'product_id' => $product->id,
            'variant_id' => $variant->id,
            'name_snapshot' => $product->name,
            'sku_snapshot' => 'SKU-'.uniqid(),
            'price_snapshot' => 20.00,
            'qty' => 1,
            'subtotal' => 20.00,
        ]);

        $response = $this->actingAs($user)->post(route('account.orders.reorder', $order));

        $response->assertRedirect(route('account.orders.show', $order));
        $response->assertSessionHas('error');
        $this->assertEmpty(session('cart', []));
    }

    #[Test]
    public function reorder_forbidden_for_another_users_order(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $owner->id]);

        $response = $this->actingAs($stranger)->post(route('account.orders.reorder', $order));

        $response->assertForbidden();
    }
}
