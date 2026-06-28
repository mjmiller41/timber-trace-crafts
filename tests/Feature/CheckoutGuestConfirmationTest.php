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
    public function guest_can_view_their_confirmation_page_when_authorised_in_session(): void
    {
        $order = Order::factory()->create([
            'user_id' => null,
            'guest_email' => 'guest@example.com',
        ]);

        $response = $this->withSession(['confirmed_orders' => [$order->id]])
            ->get(route('checkout.confirmation', ['order' => $order->id]));

        $response->assertOk();
    }

    #[Test]
    public function guest_without_session_authorisation_is_forbidden(): void
    {
        $order = Order::factory()->create([
            'user_id' => null,
            'guest_email' => 'guest@example.com',
        ]);

        // No confirmed_orders in session, and no email in the URL.
        $response = $this->get(route('checkout.confirmation', ['order' => $order->id]));

        $response->assertForbidden();
    }

    #[Test]
    public function guest_cannot_view_another_guests_order(): void
    {
        $own = Order::factory()->create(['user_id' => null, 'guest_email' => 'real@example.com']);
        $other = Order::factory()->create(['user_id' => null, 'guest_email' => 'someone@example.com']);

        // Session authorises only the guest's own order, not the other one.
        $response = $this->withSession(['confirmed_orders' => [$own->id]])
            ->get(route('checkout.confirmation', ['order' => $other->id]));

        $response->assertForbidden();
    }
}
