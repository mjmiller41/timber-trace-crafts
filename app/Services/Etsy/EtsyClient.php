<?php

namespace App\Services\Etsy;

use App\Exceptions\EtsyApiException;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;

class EtsyClient
{
    private const BASE_URL = 'https://api.etsy.com/v3';

    public function __construct(private readonly EtsyOAuthService $oauth) {}

    public function get(string $path, array $query = []): array
    {
        return $this->request('GET', $path, query: $query);
    }

    public function put(string $path, array $body = []): array
    {
        return $this->request('PUT', $path, body: $body);
    }

    public function post(string $path, array $body = []): array
    {
        return $this->request('POST', $path, body: $body);
    }

    public function delete(string $path): void
    {
        $this->request('DELETE', $path);
    }

    private function request(string $method, string $path, array $query = [], array $body = []): array
    {
        $this->oauth->refreshIfExpired();

        $accessToken = Setting::get('etsy.access_token');

        $pending = Http::withHeaders([
            'x-api-key' => config('services.etsy.shared_secret'),
            'Authorization' => "Bearer {$accessToken}",
        ]);

        $response = match ($method) {
            'GET' => $pending->get(self::BASE_URL.$path, $query),
            'PUT' => $pending->put(self::BASE_URL.$path, $body),
            'POST' => $pending->post(self::BASE_URL.$path, $body),
            'DELETE' => $pending->delete(self::BASE_URL.$path),
            default => throw new \InvalidArgumentException("Unsupported HTTP method: {$method}"),
        };

        if (! $response->successful()) {
            throw new EtsyApiException(
                "Etsy API error {$response->status()} on {$method} {$path}: {$response->body()}",
                $response->status()
            );
        }

        return $response->json() ?? [];
    }
}
