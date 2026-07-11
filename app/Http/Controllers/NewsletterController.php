<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NewsletterController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        // `email:rfc` (well-formed) rather than `email:rfc,dns`: the DNS rule does
        // a live MX lookup on every signup — it rejects valid addresses on
        // MX-less/slow domains and puts a network call in the request path.
        // Klaviyo validates on its end and the welcome flow is double opt-in.
        $validated = $request->validate([
            'email' => ['required', 'email:rfc', 'max:254'],
        ]);

        $this->subscribeToKlaviyo($validated['email']);

        // Always return success to the visitor — a Klaviyo hiccup should never
        // surface as an error on the form. Failures are logged for follow-up.
        return back()->with('newsletter_success', 'Thanks for subscribing!');
    }

    /**
     * Subscribe the email to the Klaviyo newsletter list with marketing consent.
     * Uses the Bulk Subscribe Profiles endpoint so the "subscribed to list"
     * trigger fires the welcome flow. Non-blocking: logs and swallows failures.
     */
    private function subscribeToKlaviyo(string $email): void
    {
        $privateKey = config('services.klaviyo.private_key');
        $listId = config('services.klaviyo.newsletter_list_id');

        if (! $privateKey || ! $listId) {
            Log::warning('Newsletter signup received but Klaviyo is not configured', ['email' => $email]);

            return;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Klaviyo-API-Key '.$privateKey,
                'revision' => config('services.klaviyo.api_revision', '2024-10-15'),
                'accept' => 'application/json',
                'content-type' => 'application/json',
            ])->timeout(10)->post('https://a.klaviyo.com/api/profile-subscription-bulk-create-jobs/', [
                'data' => [
                    'type' => 'profile-subscription-bulk-create-job',
                    'attributes' => [
                        'custom_source' => 'Website footer',
                        'profiles' => [
                            'data' => [[
                                'type' => 'profile',
                                'attributes' => [
                                    'email' => $email,
                                    'subscriptions' => [
                                        'email' => ['marketing' => ['consent' => 'SUBSCRIBED']],
                                    ],
                                ],
                            ]],
                        ],
                    ],
                    'relationships' => [
                        'list' => ['data' => ['type' => 'list', 'id' => $listId]],
                    ],
                ],
            ]);

            if ($response->failed()) {
                Log::warning('Klaviyo newsletter subscribe failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('Klaviyo newsletter subscribe error', ['error' => $e->getMessage()]);
        }
    }
}
