<?php

namespace Tests\Feature;

use App\Mail\RestockNotificationMail;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\RestockRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class RestockNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_submit_restock_request(): void
    {
        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->create(['product_id' => $product->id]);

        $response = $this->post(route('restock.store'), [
            'email' => 'customer@example.com',
            'variant_id' => $variant->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('restock_requests', [
            'product_variant_id' => $variant->id,
            'email' => 'customer@example.com',
            'notified_at' => null,
        ]);
    }

    public function test_duplicate_restock_request_is_ignored(): void
    {
        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->create(['product_id' => $product->id]);

        $this->post(route('restock.store'), ['email' => 'customer@example.com', 'variant_id' => $variant->id]);
        $this->post(route('restock.store'), ['email' => 'customer@example.com', 'variant_id' => $variant->id]);

        $this->assertDatabaseCount('restock_requests', 1);
    }

    public function test_admin_can_send_restock_notifications(): void
    {
        Mail::fake();

        $admin = User::factory()->create(['role' => 'admin']);
        $product = Product::factory()->create(['name' => 'Oak Shelf']);
        $variant = ProductVariant::factory()->create(['product_id' => $product->id, 'label' => 'Medium']);

        RestockRequest::create(['product_variant_id' => $variant->id, 'email' => 'a@example.com']);
        RestockRequest::create(['product_variant_id' => $variant->id, 'email' => 'b@example.com']);

        $response = $this->actingAs($admin)->post(route('admin.restock.notify', $variant));

        $response->assertRedirect(route('admin.restock.index'));
        Mail::assertQueued(RestockNotificationMail::class, 2);

        $this->assertDatabaseMissing('restock_requests', ['notified_at' => null]);
    }

    public function test_notify_skips_already_notified_requests(): void
    {
        Mail::fake();

        $admin = User::factory()->create(['role' => 'admin']);
        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->create(['product_id' => $product->id]);

        RestockRequest::create(['product_variant_id' => $variant->id, 'email' => 'a@example.com', 'notified_at' => now()]);
        RestockRequest::create(['product_variant_id' => $variant->id, 'email' => 'b@example.com']);

        $this->actingAs($admin)->post(route('admin.restock.notify', $variant));

        Mail::assertQueued(RestockNotificationMail::class, 1);
    }
}
