<?php

namespace Tests\Feature;

use App\Exceptions\EtsyApiException;
use App\Services\Etsy\EtsyClient;
use App\Services\Etsy\EtsyOAuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

class EtsyClientTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_returns_decoded_json(): void
    {
        Http::fake([
            'api.etsy.com/*' => Http::response(['listing_id' => 123], 200),
        ]);

        $oauth = Mockery::mock(EtsyOAuthService::class);
        $oauth->shouldReceive('refreshIfExpired')->once();
        $oauth->shouldReceive('getAccessToken')->andReturn('test-token');

        $client = new EtsyClient($oauth);

        $result = $client->get('/application/listings/123');

        $this->assertEquals(['listing_id' => 123], $result);
    }

    public function test_non_200_response_throws_etsy_api_exception(): void
    {
        Http::fake([
            'api.etsy.com/*' => Http::response(['error' => 'Not found'], 404),
        ]);

        $oauth = Mockery::mock(EtsyOAuthService::class);
        $oauth->shouldReceive('refreshIfExpired')->once();
        $oauth->shouldReceive('getAccessToken')->andReturn('test-token');

        $client = new EtsyClient($oauth);

        $this->expectException(EtsyApiException::class);
        $client->get('/application/listings/999');
    }

    public function test_404_is_not_retried(): void
    {
        Http::fake([
            'api.etsy.com/*' => Http::response(['error' => 'Not found'], 404),
        ]);

        $oauth = Mockery::mock(EtsyOAuthService::class);
        $oauth->shouldReceive('refreshIfExpired')->once();
        $oauth->shouldReceive('getAccessToken')->andReturn('test-token');

        $client = new EtsyClient($oauth);

        try {
            $client->get('/application/listings/999');
        } catch (EtsyApiException) {
            // expected
        }

        Http::assertSentCount(1);
    }

    public function test_429_is_retried_and_succeeds(): void
    {
        Http::fake([
            'api.etsy.com/*' => Http::sequence()
                ->push(['error' => 'Too Many Requests'], 429)
                ->push(['listing_id' => 123], 200),
        ]);

        $oauth = Mockery::mock(EtsyOAuthService::class);
        $oauth->shouldReceive('refreshIfExpired')->once();
        $oauth->shouldReceive('getAccessToken')->andReturn('test-token');

        $client = new EtsyClient($oauth);

        $result = $client->get('/application/listings/123');

        $this->assertEquals(['listing_id' => 123], $result);
        Http::assertSentCount(2);
    }

    public function test_5xx_exhausts_retries_and_throws(): void
    {
        Http::fake([
            'api.etsy.com/*' => Http::response(['error' => 'Server Error'], 503),
        ]);

        $oauth = Mockery::mock(EtsyOAuthService::class);
        $oauth->shouldReceive('refreshIfExpired')->once();
        $oauth->shouldReceive('getAccessToken')->andReturn('test-token');

        $client = new EtsyClient($oauth);

        $this->expectException(EtsyApiException::class);

        try {
            $client->get('/application/listings/999');
        } finally {
            Http::assertSentCount(3);
        }
    }
}
