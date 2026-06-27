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
            'user_id' => null,
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
            'user_id' => null,
            'guest_email' => 'real@example.com',
        ]);

        $response = $this->get(route('checkout.confirmation', [
            'order' => $order->id,
            'email' => 'attacker@example.com',
        ]));

        $response->assertForbidden();
    }
}
