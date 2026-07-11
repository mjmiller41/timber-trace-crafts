<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Newsletter signup -> Klaviyo bulk-subscribe (fires the welcome flow).
 *
 * The Klaviyo HTTP call is always faked — these tests never touch the network.
 * The endpoint is deliberately fail-soft: a Klaviyo hiccup must never surface as
 * an error to the visitor, and a missing config must skip the call, not crash.
 */
class NewsletterSubscribeTest extends TestCase
{
    private function configureKlaviyo(): void
    {
        Config::set('services.klaviyo.private_key', 'pk_test_123');
        Config::set('services.klaviyo.newsletter_list_id', 'LIST123');
        Config::set('services.klaviyo.api_revision', '2024-10-15');
    }

    #[Test]
    public function a_valid_signup_sends_a_correctly_shaped_klaviyo_request(): void
    {
        $this->configureKlaviyo();
        Http::fake(['a.klaviyo.com/*' => Http::response(['data' => []], 202)]);

        $response = $this->post(route('newsletter.store'), [
            'email' => 'jane@gmail.com',
            '_honey' => '',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('newsletter_success');

        Http::assertSent(function ($request) {
            $body = $request->data();

            return $request->url() === 'https://a.klaviyo.com/api/profile-subscription-bulk-create-jobs/'
                && str_contains($request->header('Authorization')[0], 'Klaviyo-API-Key pk_test_123')
                && $request->header('revision')[0] === '2024-10-15'
                && data_get($body, 'data.attributes.profiles.data.0.attributes.email') === 'jane@gmail.com'
                && data_get($body, 'data.attributes.profiles.data.0.attributes.subscriptions.email.marketing.consent') === 'SUBSCRIBED'
                && data_get($body, 'data.relationships.list.data.id') === 'LIST123';
        });
    }

    #[Test]
    public function it_skips_klaviyo_and_still_succeeds_when_not_configured(): void
    {
        Config::set('services.klaviyo.private_key', null);
        Config::set('services.klaviyo.newsletter_list_id', null);
        Http::fake();

        $response = $this->post(route('newsletter.store'), [
            'email' => 'jane@gmail.com',
            '_honey' => '',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('newsletter_success');
        Http::assertNothingSent();
    }

    #[Test]
    public function a_klaviyo_failure_is_swallowed_and_the_visitor_sees_success(): void
    {
        $this->configureKlaviyo();
        Http::fake(['a.klaviyo.com/*' => Http::response(['errors' => ['boom']], 500)]);

        $response = $this->post(route('newsletter.store'), [
            'email' => 'jane@gmail.com',
            '_honey' => '',
        ]);

        // Fail-soft: still a success redirect, no error surfaced to the user.
        $response->assertRedirect();
        $response->assertSessionHas('newsletter_success');
        $response->assertSessionMissing('errors');
    }

    #[Test]
    public function an_invalid_email_is_rejected_and_klaviyo_is_never_called(): void
    {
        $this->configureKlaviyo();
        Http::fake();

        $response = $this->post(route('newsletter.store'), [
            'email' => 'not-an-email',
            '_honey' => '',
        ]);

        $response->assertSessionHasErrors('email');
        Http::assertNothingSent();
    }

    #[Test]
    public function a_filled_honeypot_is_dropped_and_klaviyo_is_never_called(): void
    {
        $this->configureKlaviyo();
        Http::fake();

        // Bots fill every field; the honeypot middleware short-circuits before
        // the controller, so no Klaviyo subscribe happens.
        $response = $this->post(route('newsletter.store'), [
            'email' => 'bot@gmail.com',
            '_honey' => 'i am a bot',
        ]);

        $response->assertRedirect();
        Http::assertNothingSent();
    }
}
