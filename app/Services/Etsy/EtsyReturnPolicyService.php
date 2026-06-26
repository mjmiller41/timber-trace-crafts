<?php

namespace App\Services\Etsy;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class EtsyReturnPolicyService
{
    public function __construct(private readonly EtsyClient $client) {}

    /**
     * Fetch return policies from Etsy, cached for one hour.
     * Builds a human-readable label from the policy fields since the API has no title.
     *
     * @return array<int, array{id: int, title: string}>
     */
    public function getPolicies(): array
    {
        $shopId = Setting::get('etsy.shop_id');

        if (! $shopId) {
            return [];
        }

        return Cache::remember("etsy.return_policies.{$shopId}", 3600, function () use ($shopId) {
            $response = $this->client->get("/application/shops/{$shopId}/policies/return");

            return collect($response['results'] ?? [])
                ->map(fn ($p) => [
                    'id' => $p['return_policy_id'],
                    'title' => $this->buildLabel($p),
                ])
                ->values()
                ->toArray();
        });
    }

    /** @param array<string, mixed> $policy */
    private function buildLabel(array $policy): string
    {
        $returns = $policy['accepts_returns'] ?? false;
        $exchanges = $policy['accepts_exchanges'] ?? false;
        $days = $policy['return_deadline'] ?? null;

        if ($returns && $exchanges) {
            $label = 'Returns & Exchanges';
        } elseif ($returns) {
            $label = 'Returns only';
        } elseif ($exchanges) {
            $label = 'Exchanges only';
        } else {
            return 'No returns or exchanges';
        }

        return $days ? "{$label} — {$days} days" : $label;
    }
}
