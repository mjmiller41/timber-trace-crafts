<?php

namespace Tests\Feature;

use App\Mail\GiftCardIssuedMail;
use App\Mail\GiftCardPurchaseReceiptMail;
use App\Models\GiftCard;
use App\Services\StripeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\Test;
use Stripe\PaymentIntent;
use Tests\TestCase;

class GiftCardPurchaseTest extends TestCase
{
    use RefreshDatabase;

    private string $secret = 'whsec_test_secret';

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('services.stripe.webhook_secret', $this->secret);
    }

    /**
     * @param  array<string, string>  $metadata
     */
    private function giftCardEvent(string $paymentIntentId, int $amountCents, array $metadata): array
    {
        return [
            'id' => 'evt_gc_1',
            'type' => 'payment_intent.succeeded',
            'data' => ['object' => [
                'object' => 'payment_intent',
                'id' => $paymentIntentId,
                'amount_received' => $amountCents,
                'metadata' => array_merge(['type' => 'giftcard_purchase'], $metadata),
            ]],
        ];
    }

    private function postEvent(array $event): TestResponse
    {
        $payload = json_encode($event);
        $timestamp = time();
        $sig = 't='.$timestamp.',v1='.hash_hmac('sha256', $timestamp.'.'.$payload, $this->secret);

        return $this->call(
            'POST',
            '/webhooks/stripe',
            [], [], [],
            ['HTTP_STRIPE_SIGNATURE' => $sig, 'CONTENT_TYPE' => 'application/json'],
            $payload
        );
    }

    // ---------------------------------------------------------------------
    // Purchase page + PaymentIntent creation
    // ---------------------------------------------------------------------

    #[Test]
    public function the_gift_card_purchase_page_renders(): void
    {
        $this->get(route('gift-cards.show'))
            ->assertOk()
            ->assertSee('Gift Cards');
    }

    #[Test]
    public function creating_a_payment_intent_validates_and_returns_a_client_secret(): void
    {
        $this->mock(StripeService::class)
            ->shouldReceive('createPaymentIntent')
            ->once()
            ->andReturn(PaymentIntent::constructFrom(['id' => 'pi_test_123', 'client_secret' => 'pi_secret_abc']));

        $this->postJson(route('gift-cards.payment-intent'), [
            'amount' => 50,
            'recipient_email' => 'recipient@example.com',
            'recipient_name' => 'Sam',
            'purchaser_email' => 'buyer@example.com',
            'message' => 'Happy birthday!',
        ])->assertOk()->assertJson(['client_secret' => 'pi_secret_abc']);
    }

    #[Test]
    public function creating_a_payment_intent_rejects_an_out_of_range_amount(): void
    {
        $this->postJson(route('gift-cards.payment-intent'), [
            'amount' => 5, // below the $10 minimum
            'recipient_email' => 'recipient@example.com',
            'purchaser_email' => 'buyer@example.com',
        ])->assertStatus(422)->assertJsonValidationErrorFor('amount');
    }

    #[Test]
    public function creating_a_payment_intent_requires_recipient_and_purchaser_emails(): void
    {
        $this->postJson(route('gift-cards.payment-intent'), [
            'amount' => 50,
        ])->assertStatus(422)
            ->assertJsonValidationErrorFor('recipient_email')
            ->assertJsonValidationErrorFor('purchaser_email');
    }

    // ---------------------------------------------------------------------
    // Webhook-driven fulfilment
    // ---------------------------------------------------------------------

    #[Test]
    public function a_confirmed_payment_issues_the_card_and_emails_recipient_and_purchaser(): void
    {
        Mail::fake();

        $response = $this->postEvent($this->giftCardEvent('pi_gc_1', 5000, [
            'amount' => '50',
            'recipient_email' => 'recipient@example.com',
            'recipient_name' => 'Sam',
            'purchaser_email' => 'buyer@example.com',
            'message' => 'Enjoy!',
            'send_date' => '',
        ]));

        $response->assertOk();

        $card = GiftCard::where('purchase_payment_intent_id', 'pi_gc_1')->first();
        $this->assertNotNull($card);
        $this->assertEquals('50.00', $card->balance);
        $this->assertEquals('recipient@example.com', $card->recipient_email);
        $this->assertEquals('buyer@example.com', $card->purchaser_email);
        $this->assertDatabaseHas('gift_card_transactions', [
            'gift_card_id' => $card->id,
            'type' => 'issue',
        ]);

        Mail::assertQueued(GiftCardIssuedMail::class, fn ($m) => $m->hasTo('recipient@example.com') && $m->giftCard->code === $card->code);
        Mail::assertQueued(GiftCardPurchaseReceiptMail::class, fn ($m) => $m->hasTo('buyer@example.com'));
    }

    #[Test]
    public function no_card_exists_before_the_webhook_confirms_payment(): void
    {
        // Creating the PaymentIntent (client step) must never issue a card by itself.
        $this->mock(StripeService::class)
            ->shouldReceive('createPaymentIntent')
            ->andReturn(PaymentIntent::constructFrom(['id' => 'pi_test_123', 'client_secret' => 'pi_secret_abc']));

        $this->postJson(route('gift-cards.payment-intent'), [
            'amount' => 50,
            'recipient_email' => 'recipient@example.com',
            'purchaser_email' => 'buyer@example.com',
        ])->assertOk();

        $this->assertDatabaseCount('gift_cards', 0);
    }

    #[Test]
    public function a_duplicate_webhook_delivery_is_idempotent(): void
    {
        Mail::fake();

        $event = $this->giftCardEvent('pi_gc_dup', 7500, [
            'amount' => '75',
            'recipient_email' => 'recipient@example.com',
            'purchaser_email' => 'buyer@example.com',
        ]);

        $this->postEvent($event)->assertOk();
        $this->postEvent($event)->assertOk(); // redelivery

        $this->assertEquals(1, GiftCard::where('purchase_payment_intent_id', 'pi_gc_dup')->count());
        Mail::assertQueued(GiftCardIssuedMail::class, 1);
        Mail::assertQueued(GiftCardPurchaseReceiptMail::class, 1);
    }

    #[Test]
    public function a_future_send_date_defers_the_recipient_email(): void
    {
        Mail::fake();

        $sendDate = now()->addDays(5)->toDateString();

        $this->postEvent($this->giftCardEvent('pi_gc_future', 2500, [
            'amount' => '25',
            'recipient_email' => 'recipient@example.com',
            'purchaser_email' => 'buyer@example.com',
            'send_date' => $sendDate,
        ]))->assertOk();

        // Recipient mail is deferred; purchaser receipt still goes out now.
        Mail::assertQueued(GiftCardIssuedMail::class, function ($mail) {
            return $mail->delay !== null;
        });
        Mail::assertQueued(GiftCardPurchaseReceiptMail::class);
    }
}
