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
}
