<?php

namespace Tests\Feature\Admin;

use App\Mail\OrderStatusChangedMail;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OrderControllerTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    #[Test]
    public function admin_can_update_order_status(): void
    {
        Mail::fake();

        $order = Order::factory()->create(['status' => 'processing', 'user_id' => User::factory()->create()->id]);

        $response = $this->actingAs($this->admin())->patch(route('admin.orders.status', $order), [
            'status' => 'shipped',
        ]);

        $response->assertRedirect();
        $this->assertEquals('shipped', $order->fresh()->status);
        $this->assertDatabaseHas('order_status_history', [
            'order_id' => $order->id,
            'status' => 'shipped',
        ]);
        Mail::assertQueued(OrderStatusChangedMail::class);
    }

    #[Test]
    public function non_admin_cannot_update_order_status(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $order = Order::factory()->create(['status' => 'processing']);

        $response = $this->actingAs($customer)->patch(route('admin.orders.status', $order), [
            'status' => 'shipped',
        ]);

        $response->assertForbidden();
        $this->assertEquals('processing', $order->fresh()->status);
    }

    #[Test]
    public function admin_can_add_a_shipment(): void
    {
        $order = Order::factory()->create(['status' => 'processing']);

        $response = $this->actingAs($this->admin())->post(route('admin.orders.shipment', $order), [
            'carrier' => 'USPS',
            'tracking_number' => 'TRACK123',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('shipments', [
            'order_id' => $order->id,
            'tracking_number' => 'TRACK123',
        ]);
        $this->assertEquals('shipped', $order->fresh()->status);
    }

    #[Test]
    public function non_admin_cannot_add_a_shipment(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $order = Order::factory()->create(['status' => 'processing']);

        $response = $this->actingAs($customer)->post(route('admin.orders.shipment', $order), [
            'carrier' => 'USPS',
            'tracking_number' => 'TRACK123',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('shipments', ['order_id' => $order->id]);
    }
}
