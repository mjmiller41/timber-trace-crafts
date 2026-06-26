<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Services\Etsy\EtsyOAuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class EtsyOAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_build_auth_url_contains_required_params(): void
    {
        $service = new EtsyOAuthService;
        $url = $service->buildAuthUrl();

        $this->assertStringContainsString('etsy.com/oauth/connect', $url);
        $this->assertStringContainsString('response_type=code', $url);
        $this->assertStringContainsString('listings_r', $url);
        $this->assertStringContainsString('code_challenge=', $url);
        $this->assertStringContainsString('code_challenge_method=S256', $url);
        $this->assertNotNull(session('etsy_oauth_state'));
        $this->assertNotNull(session('etsy_oauth_code_verifier'));
    }

    public function test_handle_callback_stores_tokens_and_shop_id(): void
    {
        Http::fake([
            'api.etsy.com/v3/public/oauth/token' => Http::response([
                'access_token' => 'test-access-token',
                'refresh_token' => 'test-refresh-token',
                'expires_in' => 3600,
                'token_type' => 'Bearer',
            ], 200),
            'api.etsy.com/v3/application/users/me' => Http::response([
                'user_id' => 99,
                'shop_id' => 12345678,
            ], 200),
        ]);

        $state = 'test-state-value';
        session([
            'etsy_oauth_state' => $state,
            'etsy_oauth_code_verifier' => 'test-code-verifier',
        ]);

        $service = new EtsyOAuthService;
        $service->handleCallback('auth-code', $state);

        $this->assertEquals('test-access-token', Setting::get('etsy.access_token'));
        $this->assertEquals('test-refresh-token', Setting::get('etsy.refresh_token'));
        $this->assertEquals('12345678', Setting::get('etsy.shop_id'));
        $this->assertNotNull(Setting::get('etsy.token_expires_at'));
    }

    public function test_handle_callback_rejects_invalid_state(): void
    {
        session(['etsy_oauth_state' => 'correct-state']);

        $service = new EtsyOAuthService;

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid OAuth state');
        $service->handleCallback('auth-code', 'wrong-state');
    }

    public function test_refresh_token_updates_access_token(): void
    {
        Http::fake([
            'api.etsy.com/v3/public/oauth/token' => Http::response([
                'access_token' => 'new-access-token',
                'expires_in' => 3600,
            ], 200),
        ]);

        Setting::set('etsy.refresh_token', 'old-refresh-token');
        Setting::set('etsy.access_token', 'old-access-token');

        $service = new EtsyOAuthService;
        $service->refreshToken();

        $this->assertEquals('new-access-token', Setting::get('etsy.access_token'));
    }

    public function test_is_connected_returns_true_when_tokens_exist(): void
    {
        Setting::set('etsy.access_token', 'some-token');
        Setting::set('etsy.shop_id', '12345');

        $service = new EtsyOAuthService;

        $this->assertTrue($service->isConnected());
    }

    public function test_is_connected_returns_false_when_no_token(): void
    {
        $service = new EtsyOAuthService;

        $this->assertFalse($service->isConnected());
    }
}
