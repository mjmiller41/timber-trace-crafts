<?php

namespace Tests\Feature;

use App\Models\GiftCard;
use App\Services\GiftCardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GiftCardServiceTest extends TestCase
{
    use RefreshDatabase;

    private GiftCardService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(GiftCardService::class);
    }

    #[Test]
    public function issue_creates_a_card_with_balance_and_ledger_entry(): void
    {
        $card = $this->service->issue(50.00, ['recipient_email' => 'gift@example.com']);

        $this->assertSame('50.00', $card->balance);
        $this->assertSame('50.00', $card->initial_balance);
        $this->assertTrue($card->active);
        $this->assertStringStartsWith('GC-', $card->code);
        $this->assertDatabaseHas('gift_card_transactions', [
            'gift_card_id' => $card->id,
            'type' => 'issue',
            'amount' => '50.00',
            'balance_after' => '50.00',
        ]);
    }

    #[Test]
    public function issue_rejects_non_positive_amounts(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->service->issue(0);
    }

    #[Test]
    public function partial_redemption_decrements_balance_and_leaves_remainder(): void
    {
        $card = GiftCard::factory()->balance(50.00)->create();

        $applied = DB::transaction(fn () => $this->service->redeem($card, 20.00, null));

        $this->assertSame(20.00, $applied);
        $this->assertSame('30.00', $card->fresh()->balance);
        $this->assertDatabaseHas('gift_card_transactions', [
            'gift_card_id' => $card->id,
            'type' => 'redeem',
            'amount' => '-20.00',
            'balance_after' => '30.00',
        ]);
    }

    #[Test]
    public function redemption_is_capped_at_the_remaining_balance(): void
    {
        $card = GiftCard::factory()->balance(15.00)->create();

        $applied = DB::transaction(fn () => $this->service->redeem($card, 40.00, null));

        $this->assertSame(15.00, $applied);
        $this->assertSame('0.00', $card->fresh()->balance);
    }

    #[Test]
    public function inactive_or_expired_cards_redeem_nothing(): void
    {
        $inactive = GiftCard::factory()->balance(25.00)->inactive()->create();
        $expired = GiftCard::factory()->balance(25.00)->expired()->create();

        $this->assertSame(0.0, DB::transaction(fn () => $this->service->redeem($inactive, 10.00, null)));
        $this->assertSame(0.0, DB::transaction(fn () => $this->service->redeem($expired, 10.00, null)));
        $this->assertSame('25.00', $inactive->fresh()->balance);
        $this->assertSame('25.00', $expired->fresh()->balance);
    }

    #[Test]
    public function balance_cannot_be_double_spent_across_two_redemptions(): void
    {
        $card = GiftCard::factory()->balance(30.00)->create();

        $first = DB::transaction(fn () => $this->service->redeem($card, 25.00, null));
        $second = DB::transaction(fn () => $this->service->redeem($card->fresh(), 25.00, null));

        $this->assertSame(25.00, $first);
        $this->assertSame(5.00, $second);
        $this->assertSame('0.00', $card->fresh()->balance);
        // Total redeemed never exceeds the original balance.
        $this->assertEquals(-30.00, (float) $card->transactions()->where('type', 'redeem')->sum('amount'));
    }

    #[Test]
    public function refund_credits_balance_back(): void
    {
        $card = GiftCard::factory()->balance(10.00)->create();
        DB::transaction(fn () => $this->service->redeem($card, 10.00, null));

        $this->assertSame('0.00', $card->fresh()->balance);

        $this->service->refund($card->fresh(), 4.00, null);

        $this->assertSame('4.00', $card->fresh()->balance);
        $this->assertDatabaseHas('gift_card_transactions', [
            'gift_card_id' => $card->id,
            'type' => 'refund',
            'amount' => '4.00',
        ]);
    }
}
