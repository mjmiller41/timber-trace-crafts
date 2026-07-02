<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RecaptchaService
{
    /**
     * Whether reCAPTCHA is configured. When it isn't, verify() soft-passes
     * and the honeypot field remains the only bot defense on a form.
     */
    public function enabled(): bool
    {
        return filled(config('services.recaptcha.secret_key'));
    }

    /**
     * Verify a reCAPTCHA v3 token for the given action against Google's siteverify endpoint.
     */
    public function verify(?string $token, string $action, ?string $ip = null): bool
    {
        if (! $this->enabled()) {
            return true;
        }

        if (! $token) {
            return false;
        }

        try {
            $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
                'secret' => config('services.recaptcha.secret_key'),
                'response' => $token,
                'remoteip' => $ip,
            ]);
        } catch (\Throwable $e) {
            Log::warning('reCAPTCHA verification request failed', ['error' => $e->getMessage()]);

            return false;
        }

        if (! $response->successful()) {
            return false;
        }

        $data = $response->json();

        return ($data['success'] ?? false)
            && ($data['action'] ?? null) === $action
            && ($data['score'] ?? 0) >= config('services.recaptcha.min_score');
    }
}
