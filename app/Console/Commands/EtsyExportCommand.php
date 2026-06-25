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

        $this->info('Fetching shop info...', 'v');
        $export['shop'] = $client->get("/application/shops/{$shopId}");

        $this->info('Fetching shop sections...', 'v');
        $export['sections'] = $client->get("/application/shops/{$shopId}/sections");

        $this->info('Fetching shipping profiles...', 'v');
        $export['shipping_profiles'] = $client->get("/application/shops/{$shopId}/shipping-profiles");

        $this->info('Fetching return policies...', 'v');
        $export['return_policies'] = $client->get("/application/shops/{$shopId}/return-policies");

        $this->info('Fetching readiness state definitions...', 'v');
        $export['readiness_states'] = $client->get("/application/shops/{$shopId}/readiness-state-definitions");

        $this->info('Fetching production partners...', 'v');
        $export['production_partners'] = $client->get("/application/shops/{$shopId}/production-partners");

        $this->info('Fetching active listings...', 'v');
        $export['listings_active'] = $this->paginate($client, "/application/shops/{$shopId}/listings", ['state' => 'active', 'includes' => ['Images', 'Videos', 'Inventory', 'ShippingProfile', 'MainImage']]);

        $this->info('Fetching inactive listings...', 'v');
        $export['listings_inactive'] = $this->paginate($client, "/application/shops/{$shopId}/listings", ['state' => 'inactive', 'includes' => ['Images', 'Videos', 'Inventory', 'ShippingProfile', 'MainImage']]);

        $this->info('Fetching draft listings...', 'v');
        $export['listings_draft'] = $this->paginate($client, "/application/shops/{$shopId}/listings", ['state' => 'draft', 'includes' => ['Images', 'Videos', 'Inventory', 'ShippingProfile', 'MainImage']]);

        $this->info('Fetching expired listings...', 'v');
        $export['listings_expired'] = $this->paginate($client, "/application/shops/{$shopId}/listings", ['state' => 'expired', 'includes' => ['Images', 'Videos', 'Inventory', 'ShippingProfile', 'MainImage']]);

        $this->info('Fetching sold_out listings...', 'v');
        $export['listings_sold_out'] = $this->paginate($client, "/application/shops/{$shopId}/listings", ['state' => 'sold_out', 'includes' => ['Images', 'Videos', 'Inventory', 'ShippingProfile', 'MainImage']]);

        $this->info('Fetching receipts (orders)...', 'v');
        $export['receipts'] = $this->paginate($client, "/application/shops/{$shopId}/receipts", ['includes' => ['Transactions', 'Buyer', 'Shipments']]);

        $this->info('Fetching reviews...', 'v');
        $export['reviews'] = $this->paginate($client, "/application/shops/{$shopId}/reviews");

        $this->info('Fetching transactions...', 'v');
        $export['transactions'] = $this->paginate($client, "/application/shops/{$shopId}/transactions");

        $this->info('Fetching buyer taxonomy...', 'v');
        $export['buyer_taxonomy'] = $client->get('/application/buyer-taxonomy/nodes');

        $this->info('Fetching seller taxonomy...', 'v');
        $export['seller_taxonomy'] = $client->get('/application/seller-taxonomy/nodes');

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
