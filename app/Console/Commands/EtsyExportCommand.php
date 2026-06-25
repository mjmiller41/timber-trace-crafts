<?php

namespace App\Console\Commands;

use App\Models\Setting;
use App\Services\Etsy\EtsyClient;
use App\Services\Etsy\EtsyOAuthService;
use Illuminate\Console\Command;

class EtsyExportCommand extends Command
{
    protected $signature = 'etsy:export';

    protected $description = 'Export all Etsy shop data to JSON (stdout)';

    public function handle(EtsyOAuthService $oauth): int
    {
        $client = new EtsyClient($oauth);
        $shopId = Setting::get('etsy.shop_id');

        $export = [];

        $endpoints = [
            'shop' => fn () => $client->get("/application/shops/{$shopId}"),
            'sections' => fn () => $client->get("/application/shops/{$shopId}/sections"),
            'shipping_profiles' => fn () => $client->get("/application/shops/{$shopId}/shipping-profiles"),
            'return_policies' => fn () => $client->get("/application/shops/{$shopId}/return-policies"),
            'readiness_states' => fn () => $client->get("/application/shops/{$shopId}/readiness-state-definitions"),
            'production_partners' => fn () => $client->get("/application/shops/{$shopId}/production-partners"),
            'listings_active' => fn () => $this->paginate($client, "/application/shops/{$shopId}/listings", ['state' => 'active', 'includes' => ['images', 'videos', 'inventory', 'shipping']]),
            'listings_inactive' => fn () => $this->paginate($client, "/application/shops/{$shopId}/listings", ['state' => 'inactive', 'includes' => ['images', 'videos', 'inventory', 'shipping']]),
            'listings_draft' => fn () => $this->paginate($client, "/application/shops/{$shopId}/listings", ['state' => 'draft', 'includes' => ['images', 'videos', 'inventory', 'shipping']]),
            'listings_expired' => fn () => $this->paginate($client, "/application/shops/{$shopId}/listings", ['state' => 'expired', 'includes' => ['images', 'videos', 'inventory', 'shipping']]),
            'listings_sold_out' => fn () => $this->paginate($client, "/application/shops/{$shopId}/listings", ['state' => 'sold_out', 'includes' => ['images', 'videos', 'inventory', 'shipping']]),
            'receipts' => fn () => $this->paginate($client, "/application/shops/{$shopId}/receipts", ['includes' => ['transactions', 'buyer', 'shipments']]),
            'reviews' => fn () => $this->paginate($client, "/application/shops/{$shopId}/reviews"),
            'transactions' => fn () => $this->paginate($client, "/application/shops/{$shopId}/transactions"),
            'buyer_taxonomy' => fn () => $client->get('/application/buyer-taxonomy/nodes'),
            'seller_taxonomy' => fn () => $client->get('/application/seller-taxonomy/nodes'),
        ];

        foreach ($endpoints as $key => $fetch) {
            fwrite(STDERR, "Fetching {$key}...\n");
            try {
                $export[$key] = $fetch();
            } catch (\Throwable $e) {
                fwrite(STDERR, "Skipped {$key}: {$e->getMessage()}\n");
                $export[$key] = ['error' => $e->getMessage()];
            }
        }

        $this->line(json_encode($export, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return self::SUCCESS;
    }

    private function paginate(EtsyClient $client, string $path, array $params = []): array
    {
        $results = [];
        $offset = 0;
        $limit = 100;

        do {
            $response = $client->get($path, array_merge($params, ['limit' => $limit, 'offset' => $offset]));
            $page = $response['results'] ?? [];
            $results = array_merge($results, $page);
            $offset += $limit;
        } while (count($page) === $limit);

        return ['count' => count($results), 'results' => $results];
    }
}
