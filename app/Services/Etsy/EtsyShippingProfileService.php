<?php

namespace App\Services\Etsy;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class EtsyShippingProfileService
{
    public function __construct(private readonly EtsyClient $client) {}

    /**
     * Fetch active shipping profiles from Etsy, cached for one hour.
     *
     * @return array<int, array{id: int, title: string}>
     */
    public function getProfiles(): array
    {
        $shopId = Setting::get('etsy.shop_id');

        if (! $shopId) {
            return [];
        }

        return Cache::remember("etsy.shipping_profiles.{$shopId}", 3600, function () use ($shopId) {
            $response = $this->client->get("/application/shops/{$shopId}/shipping-profiles");

            return collect($response['results'] ?? [])
                ->reject(fn ($p) => $p['is_deleted'])
                ->map(fn ($p) => ['id' => $p['shipping_profile_id'], 'title' => $p['title']])
                ->values()
                ->toArray();
        });
    }
}
