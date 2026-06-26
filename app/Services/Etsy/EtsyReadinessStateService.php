<?php

namespace App\Services\Etsy;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class EtsyReadinessStateService
{
    public function __construct(private readonly EtsyClient $client) {}

    /**
     * Fetch shop processing profiles (readiness states) from Etsy, cached for one hour.
     *
     * @return array<int, array{id: int, title: string}>
     */
    public function getStates(): array
    {
        $shopId = Setting::get('etsy.shop_id');

        if (! $shopId) {
            return [];
        }

        return Cache::remember("etsy.readiness_states.{$shopId}", 3600, function () use ($shopId) {
            $response = $this->client->get("/application/shops/{$shopId}/readiness-state-definitions");

            return collect($response['results'] ?? [])
                ->map(fn ($s) => [
                    'id' => $s['readiness_state_id'],
                    'title' => $this->buildLabel($s),
                ])
                ->values()
                ->toArray();
        });
    }

    /** @param array<string, mixed> $state */
    private function buildLabel(array $state): string
    {
        $label = match ($state['readiness_state'] ?? '') {
            'ready_to_ship' => 'Ready to ship',
            'made_to_order' => 'Made to order',
            default => ucwords(str_replace('_', ' ', $state['readiness_state'] ?? 'Unknown')),
        };

        $days = $state['processing_days_display_label'] ?? null;

        return $days ? "{$label} — {$days}" : $label;
    }
}
