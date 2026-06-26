<?php

namespace App\Services\Etsy;

use App\Exceptions\EtsyApiException;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;

class EtsyOAuthService
{
    private const AUTH_URL = 'https://www.etsy.com/oauth/connect';

    private const TOKEN_URL = 'https://api.etsy.com/v3/public/oauth/token';

    private const SCOPES = 'listings_r listings_w listings_d transactions_r transactions_w shops_r shops_w feedback_r';

    public function buildAuthUrl(): string
    {
        $state = bin2hex(random_bytes(16));

        // PKCE: verifier is a random secret; challenge is its SHA-256 digest, base64url-encoded
        $verifier = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
        $challenge = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');

        session([
            'etsy_oauth_state' => $state,
            'etsy_oauth_code_verifier' => $verifier,
        ]);

        return self::AUTH_URL.'?'.http_build_query([
            'response_type' => 'code',
            'redirect_uri' => $this->callbackUrl(),
            'scope' => self::SCOPES,
            'client_id' => config('services.etsy.keystring'),
            'state' => $state,
            'code_challenge' => $challenge,
            'code_challenge_method' => 'S256',
        ]);
    }

    public function handleCallback(string $code, string $state): void
    {
        if ($state !== session('etsy_oauth_state')) {
            throw new \RuntimeException('Invalid OAuth state — possible CSRF attempt.');
        }

        $verifier = session('etsy_oauth_code_verifier');

        if (! $verifier) {
            throw new \RuntimeException('Missing PKCE code verifier — session may have expired.');
        }

        // PKCE token exchange uses code_verifier instead of client_secret
        $response = Http::post(self::TOKEN_URL, [
            'grant_type' => 'authorization_code',
            'client_id' => config('services.etsy.keystring'),
            'redirect_uri' => $this->callbackUrl(),
            'code' => $code,
            'code_verifier' => $verifier,
        ]);

        if (! $response->successful()) {
            throw new EtsyApiException('Token exchange failed: '.$response->body());
        }

        $data = $response->json();

        Setting::set('etsy.access_token', $data['access_token']);
        Setting::set('etsy.refresh_token', $data['refresh_token']);
        Setting::set('etsy.token_expires_at', now()->addSeconds($data['expires_in'])->toISOString());

        $shopId = $this->resolveShopId($data['access_token']);
        Setting::set('etsy.shop_id', (string) $shopId);

        session()->forget(['etsy_oauth_state', 'etsy_oauth_code_verifier']);
    }

    public function refreshIfExpired(): void
    {
        $expiresAt = Setting::get('etsy.token_expires_at');

        if (! $expiresAt || now()->addMinutes(5)->greaterThan($expiresAt)) {
            $this->refreshToken();
        }
    }

    public function refreshToken(): void
    {
        $refreshToken = Setting::get('etsy.refresh_token');

        if (! $refreshToken) {
            throw new \RuntimeException('No Etsy refresh token. Reconnect via Admin → Etsy.');
        }

        $response = Http::post(self::TOKEN_URL, [
            'grant_type' => 'refresh_token',
            'client_id' => config('services.etsy.keystring'),
            'client_secret' => config('services.etsy.shared_secret'),
            'refresh_token' => $refreshToken,
        ]);

        if (! $response->successful()) {
            throw new EtsyApiException('Token refresh failed: '.$response->body());
        }

        $data = $response->json();

        Setting::set('etsy.access_token', $data['access_token']);
        Setting::set('etsy.token_expires_at', now()->addSeconds($data['expires_in'])->toISOString());

        if (isset($data['refresh_token'])) {
            Setting::set('etsy.refresh_token', $data['refresh_token']);
        }
    }

    public function isConnected(): bool
    {
        return ! empty(Setting::get('etsy.access_token')) && ! empty(Setting::get('etsy.shop_id'));
    }

    private function callbackUrl(): string
    {
        if ($override = config('services.etsy.redirect_uri')) {
            return $override;
        }

        try {
            return route('admin.etsy.callback');
        } catch (\Exception) {
            return url('/admin/etsy/callback');
        }
    }

    private function resolveShopId(string $accessToken): int
    {
        $headers = [
            'x-api-key' => config('services.etsy.keystring').':'.config('services.etsy.shared_secret'),
            'Authorization' => "Bearer {$accessToken}",
        ];

        // Try /users/me first — returns { user_id, shop_id } in one call.
        // Etsy /users/me requires the shared secret in x-api-key for some app types,
        // so we fall back to fetching by known shop name if that fails.
        $meResponse = Http::withHeaders($headers)->get('https://api.etsy.com/v3/application/users/me');

        if ($meResponse->successful() && $meResponse->json('shop_id')) {
            return (int) $meResponse->json('shop_id');
        }

        // Fallback: resolve via shop name (works with keystring + bearer token)
        $shopName = config('services.etsy.shop_name', 'timbertracecrafts');
        $shopResponse = Http::withHeaders($headers)->get("https://api.etsy.com/v3/application/shops/{$shopName}");

        if (! $shopResponse->successful()) {
            throw new EtsyApiException(
                'Failed to resolve Etsy shop ID. /users/me: '.$meResponse->body().' | /shops: '.$shopResponse->body()
            );
        }

        $shopId = $shopResponse->json('shop_id');

        if (! $shopId) {
            throw new EtsyApiException('No shop_id returned from Etsy API.');
        }

        return (int) $shopId;
    }
}
