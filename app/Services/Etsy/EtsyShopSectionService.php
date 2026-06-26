<?php

namespace App\Services\Etsy;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class EtsyShopSectionService
{
    public function __construct(private readonly EtsyClient $client) {}

    /**
     * Fetch shop sections from Etsy, cached for one hour.
     *
     * @return array<int, array{id: int, title: string}>
     */
    public function getSections(): array
    {
        $shopId = Setting::get('etsy.shop_id');

        if (! $shopId) {
            return [];
        }

        return Cache::remember("etsy.shop_sections.{$shopId}", 3600, function () use ($shopId) {
            $response = $this->client->get("/application/shops/{$shopId}/sections");

            return collect($response['results'] ?? [])
                ->sortBy('rank')
                ->map(fn ($s) => ['id' => $s['shop_section_id'], 'title' => $s['title']])
                ->values()
                ->toArray();
        });
    }

    /**
     * Create a new section on Etsy and bust the sections cache.
     *
     * @return array{id: int, title: string}
     */
    public function createSection(string $title): array
    {
        $shopId = Setting::get('etsy.shop_id');

        $response = $this->client->post("/application/shops/{$shopId}/sections", ['title' => $title]);

        Cache::forget("etsy.shop_sections.{$shopId}");

        return ['id' => $response['shop_section_id'], 'title' => $response['title']];
    }
}
