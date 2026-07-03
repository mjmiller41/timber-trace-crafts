<?php

namespace App\Services\Etsy;

use App\Exceptions\EtsyApiException;
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

    public function patch(string $path, array $body = []): array
    {
        return $this->request('PATCH', $path, body: $body);
    }

    public function post(string $path, array $body = []): array
    {
        return $this->request('POST', $path, body: $body);
    }

    /**
     * POST a file as multipart/form-data (e.g. uploadListingImage).
     *
     * @param  array<string, mixed>  $body
     */
    public function postFile(string $path, array $body, string $fileField, string $fileContents, string $filename): array
    {
        return $this->request('POST_FILE', $path, body: $body, file: [$fileField, $fileContents, $filename]);
    }

    public function delete(string $path): void
    {
        $this->request('DELETE', $path);
    }

    private const MAX_ATTEMPTS = 3;

    /**
     * @param  array{0: string, 1: string, 2: string}|null  $file  [field, contents, filename]
     */
    private function request(string $method, string $path, array $query = [], array $body = [], ?array $file = null): array
    {
        $this->oauth->refreshIfExpired();

        for ($attempt = 1; $attempt <= self::MAX_ATTEMPTS; $attempt++) {
            $accessToken = $this->oauth->getAccessToken();

            $pending = Http::withHeaders([
                'x-api-key' => config('services.etsy.keystring').':'.config('services.etsy.shared_secret'),
                'Authorization' => "Bearer {$accessToken}",
            ]);

            $response = match ($method) {
                'GET' => $pending->get(self::BASE_URL.$path, $query),
                'PUT' => $pending->asJson()->put(self::BASE_URL.$path, $body),
                'POST' => $pending->asForm()->post(self::BASE_URL.$path, $body),
                'POST_FILE' => $pending->attach($file[0], $file[1], $file[2])->post(self::BASE_URL.$path, $body),
                'PATCH' => $pending->asForm()->patch(self::BASE_URL.$path, $body),
                'DELETE' => $pending->delete(self::BASE_URL.$path),
                default => throw new \InvalidArgumentException("Unsupported HTTP method: {$method}"),
            };

            if ($response->successful()) {
                return $response->json() ?? [];
            }

            // A single 429/5xx would otherwise fail an entire product sync, burn
            // a webhook-import retry, or abort mid-pagination through order history.
            $isRetryable = $response->status() === 429 || $response->status() >= 500;

            if (! $isRetryable || $attempt === self::MAX_ATTEMPTS) {
                throw new EtsyApiException(
                    "Etsy API error {$response->status()} on {$method} {$path}: {$response->body()}",
                    $response->status()
                );
            }

            usleep(500_000 * $attempt);
        }

        throw new EtsyApiException("Etsy API request exhausted retries on {$method} {$path}");
    }
}
