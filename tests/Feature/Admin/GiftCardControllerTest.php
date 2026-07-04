<?php

namespace Tests\Feature\Admin;

use App\Models\GiftCard;
use App\Models\User;
use App\Services\GiftCardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GiftCardControllerTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    #[Test]
    public function index_renders_gift_cards(): void
    {
        $card = app(GiftCardService::class)->issue(50, ['recipient_email' => 'jane@example.com']);

        $response = $this->actingAs($this->admin())->get(route('admin.gift-cards.index'));

        $response->assertOk();
        $response->assertSee($card->code);
        $response->assertSee('jane@example.com');
    }

    #[Test]
    public function index_search_filters_by_code_and_email(): void
    {
        $match = app(GiftCardService::class)->issue(50, ['recipient_email' => 'target@example.com']);
        $other = app(GiftCardService::class)->issue(50, ['recipient_email' => 'someone@example.com']);

        $response = $this->actingAs($this->admin())->get(route('admin.gift-cards.index', ['q' => 'target@example.com']));

        $response->assertOk();
        $response->assertSee($match->code);
        $response->assertDontSee($other->code);
    }

    #[Test]
    public function index_status_filter_hides_inactive_cards_when_filtering_active(): void
    {
        $active = app(GiftCardService::class)->issue(50);
        $voided = GiftCard::factory()->inactive()->create();

        $response = $this->actingAs($this->admin())->get(route('admin.gift-cards.index', ['status' => 'active']));

        $response->assertOk();
        $response->assertSee($active->code);
        $response->assertDontSee($voided->code);
    }

    #[Test]
    public function issuing_a_card_creates_it_with_a_ledger_entry(): void
    {
        $response = $this->actingAs($this->admin())->post(route('admin.gift-cards.store'), [
            'amount' => 75.50,
            'recipient_email' => 'gift@example.com',
        ]);

        $card = GiftCard::where('recipient_email', 'gift@example.com')->firstOrFail();

        $response->assertRedirect(route('admin.gift-cards.show', $card));
        $this->assertEquals('75.50', (string) $card->balance);
        $this->assertEquals('75.50', (string) $card->initial_balance);
        $this->assertDatabaseHas('gift_card_transactions', [
            'gift_card_id' => $card->id,
            'type' => 'issue',
            'amount' => 75.50,
        ]);
    }

    #[Test]
    public function issuing_rejects_a_zero_amount(): void
    {
        $response = $this->actingAs($this->admin())->post(route('admin.gift-cards.store'), [
            'amount' => 0,
        ]);

        $response->assertSessionHasErrors('amount');
        $this->assertDatabaseCount('gift_cards', 0);
    }

    #[Test]
    public function void_deactivates_a_fully_available_card(): void
    {
        $card = app(GiftCardService::class)->issue(50);

        $response = $this->actingAs($this->admin())->post(route('admin.gift-cards.void', $card));

        $response->assertRedirect(route('admin.gift-cards.show', $card));
        $this->assertFalse($card->fresh()->active);
    }

    #[Test]
    public function voiding_a_partially_redeemed_card_requires_confirmation(): void
    {
        $service = app(GiftCardService::class);
        $card = $service->issue(50);
        $service->redeem($card, 20);

        $this->assertEquals('30.00', (string) $card->fresh()->balance);

        // Without confirm: guarded, stays active.
        $this->actingAs($this->admin())->post(route('admin.gift-cards.void', $card));
        $this->assertTrue($card->fresh()->active);

        // With confirm: voided.
        $this->actingAs($this->admin())->post(route('admin.gift-cards.void', $card), ['confirm' => 1]);
        $this->assertFalse($card->fresh()->active);
    }

    #[Test]
    public function reactivate_restores_a_voided_card(): void
    {
        $card = GiftCard::factory()->inactive()->create();

        $this->actingAs($this->admin())->post(route('admin.gift-cards.reactivate', $card));

        $this->assertTrue($card->fresh()->active);
    }

    #[Test]
    public function refund_credits_the_balance_and_writes_a_ledger_entry(): void
    {
        $card = app(GiftCardService::class)->issue(50);

        $response = $this->actingAs($this->admin())->post(route('admin.gift-cards.refund', $card), [
            'amount' => 15,
        ]);

        $response->assertRedirect(route('admin.gift-cards.show', $card));
        $this->assertEquals('65.00', (string) $card->fresh()->balance);
        $this->assertDatabaseHas('gift_card_transactions', [
            'gift_card_id' => $card->id,
            'type' => 'refund',
            'amount' => 15,
        ]);
    }

    #[Test]
    public function show_displays_the_redemption_history(): void
    {
        $service = app(GiftCardService::class);
        $card = $service->issue(50);
        $service->redeem($card, 20, null, 'Order checkout');

        $response = $this->actingAs($this->admin())->get(route('admin.gift-cards.show', $card));

        $response->assertOk();
        $response->assertSee($card->code);
        $response->assertSee('Order checkout');
        $response->assertSee('30.00');
    }

    #[Test]
    public function non_admin_cannot_view_gift_cards(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $this->actingAs($customer)
            ->get(route('admin.gift-cards.index'))
            ->assertForbidden();
    }

    #[Test]
    public function non_admin_cannot_issue_a_gift_card(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $this->actingAs($customer)
            ->post(route('admin.gift-cards.store'), ['amount' => 50])
            ->assertForbidden();

        $this->assertDatabaseCount('gift_cards', 0);
    }
}
