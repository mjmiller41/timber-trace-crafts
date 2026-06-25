<?php

namespace App\Console\Commands;

use App\Models\EtsyShopSection;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Setting;
use App\Services\Etsy\EtsyClient;
use App\Services\Etsy\EtsyOAuthService;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class EtsyImportCommand extends Command
{
    protected $signature = 'etsy:import
                            {--type=all : What to import — sections, listings, orders, or all}';

    protected $description = 'Import Etsy data into the local database';

    private const SECTION_CATEGORY_MAP = [
        57999630 => 3, // Jewelry → Jewelry
        58705275 => 2, // Boxes → Jewelry Boxes
        58852823 => 1, // Tumblers → Tumblers
    ];

    private const ETSY_STATUS_MAP = [
        'paid' => 'processing',
        'completed' => 'delivered',
        'open' => 'pending',
        'payment processing' => 'pending',
        'canceled' => 'cancelled',
        'fully refunded' => 'cancelled',
        'partially refunded' => 'processing',
    ];

    private EtsyClient $client;

    private string $shopId;

    public function handle(EtsyOAuthService $oauth): int
    {
        if (! $oauth->isConnected()) {
            $this->error('Etsy is not connected. Go to Admin → Etsy to authorize.');

            return 1;
        }

        $this->client = new EtsyClient($oauth);
        $this->shopId = (string) Setting::get('etsy.shop_id');

        $type = $this->option('type');

        if (in_array($type, ['all', 'sections'])) {
            $this->importSections();
        }

        if (in_array($type, ['all', 'listings'])) {
            $this->importListings();
        }

        if (in_array($type, ['all', 'orders'])) {
            $this->importOrders();
        }

        $this->info('Done.');

        return 0;
    }

    private function importSections(): void
    {
        $this->info('Importing shop sections...');

        $response = $this->client->get("/application/shops/{$this->shopId}/sections");
        $sections = $response['results'] ?? [];

        foreach ($sections as $section) {
            EtsyShopSection::updateOrCreate(
                ['etsy_section_id' => $section['shop_section_id']],
                [
                    'title' => $section['title'],
                    'rank' => $section['rank'],
                    'active_listing_count' => $section['active_listing_count'],
                ]
            );
            $this->line("  ✓ Section: {$section['title']}");
        }

        $this->info('  Imported '.count($sections).' section(s).');
    }

    private function importListings(): void
    {
        $this->info('Importing Etsy listings as products...');

        $listings = $this->fetchAll('/listings');
        $imported = 0;

        foreach ($listings as $listing) {
            $listingId = (string) $listing['listing_id'];

            if (Product::where('etsy_listing_id', $listingId)->exists()) {
                $this->line("  ~ Skipping [{$listingId}] already imported: {$listing['title']}");

                continue;
            }

            $price = $this->money($listing['price'] ?? null) ?? 0;
            $sectionId = $listing['shop_section_id'] ?? null;
            $categoryId = $sectionId ? (self::SECTION_CATEGORY_MAP[$sectionId] ?? null) : null;

            $slug = $this->uniqueSlug(Str::slug($listing['title']));

            $isPersonalizable = $listing['is_personalizable'] ?? false;

            Product::create([
                'name' => $listing['title'],
                'slug' => $slug,
                'sku_base' => 'ETSY-'.$listingId,
                'description' => $listing['description'] ?? '',
                'short_description' => Str::limit(strip_tags($listing['description'] ?? ''), 200),
                'category_id' => $categoryId,
                'price' => $price,
                'status' => 'active',
                'featured' => false,
                'sort_order' => 0,
                'etsy_listing_id' => $listingId,
                'etsy_state' => $listing['state'],
                'etsy_tags' => $listing['tags'] ?? [],
                'etsy_materials' => $listing['materials'] ?? [],
                'etsy_who_made' => $listing['who_made'] ?? null,
                'etsy_when_made' => $listing['when_made'] ?? null,
                'etsy_is_supply' => $listing['is_supply'] ?? null,
                'etsy_processing_min' => $listing['processing_min'] ?? null,
                'etsy_processing_max' => $listing['processing_max'] ?? null,
                'etsy_item_weight' => $listing['item_weight'] ?? null,
                'etsy_item_weight_unit' => $listing['item_weight_unit'] ?? null,
                'etsy_item_length' => $listing['item_length'] ?? null,
                'etsy_item_width' => $listing['item_width'] ?? null,
                'etsy_item_height' => $listing['item_height'] ?? null,
                'etsy_item_dimensions_unit' => $listing['item_dimensions_unit'] ?? null,
                'etsy_taxonomy_id' => $listing['taxonomy_id'] ?? null,
                'etsy_shop_section_id' => $sectionId,
                'etsy_shipping_profile_id' => $listing['shipping_profile_id'] ?? null,
                'etsy_return_policy_id' => $listing['return_policy_id'] ?? null,
                'personalization_type' => $isPersonalizable ? 'addon' : 'none',
            ]);

            $this->line("  ✓ [{$listingId}] {$listing['title']} (\${$price})");
            $imported++;
        }

        $this->info("  Imported {$imported} listing(s).");
    }

    private function importOrders(): void
    {
        $this->info('Importing Etsy receipts as orders...');

        $receipts = $this->fetchAll('/receipts');
        $imported = 0;

        foreach ($receipts as $receipt) {
            $receiptId = (string) $receipt['receipt_id'];

            if (Order::where('etsy_receipt_id', $receiptId)->exists()) {
                $this->line("  ~ Skipping [{$receiptId}] already imported.");

                continue;
            }

            $nameParts = explode(' ', $receipt['name'] ?? '', 2);

            $order = Order::create([
                'status' => self::ETSY_STATUS_MAP[$receipt['status']] ?? 'pending',
                'etsy_receipt_id' => $receiptId,
                'message_from_buyer' => $receipt['message_from_buyer'] ?? null,
                'etsy_is_paid' => $receipt['is_paid'],
                'etsy_is_shipped' => $receipt['is_shipped'],
                'etsy_payment_method' => $receipt['payment_method'] ?? null,
                'gift_message' => $receipt['gift_message'] ?? null,
                'subtotal' => $this->money($receipt['subtotal'] ?? null) ?? 0,
                'shipping_amount' => $this->money($receipt['total_shipping_cost'] ?? null) ?? 0,
                'tax_amount' => $this->money($receipt['total_tax_cost'] ?? null) ?? 0,
                'discount_amount' => $this->money($receipt['discount_amt'] ?? null) ?? 0,
                'total' => $this->money($receipt['grandtotal'] ?? null) ?? 0,
                'shipping_first_name' => $nameParts[0] ?? '',
                'shipping_last_name' => $nameParts[1] ?? '',
                'shipping_line1' => $receipt['first_line'] ?? '',
                'shipping_line2' => $receipt['second_line'] ?? null,
                'shipping_city' => $receipt['city'] ?? '',
                'shipping_state' => $receipt['state'] ?? '',
                'shipping_zip' => $receipt['zip'] ?? '',
                'shipping_country' => $receipt['country_iso'] ?? 'US',
                'billing_first_name' => $nameParts[0] ?? '',
                'billing_last_name' => $nameParts[1] ?? '',
                'billing_line1' => $receipt['first_line'] ?? '',
                'billing_line2' => $receipt['second_line'] ?? null,
                'billing_city' => $receipt['city'] ?? '',
                'billing_state' => $receipt['state'] ?? '',
                'billing_zip' => $receipt['zip'] ?? '',
                'billing_country' => $receipt['country_iso'] ?? 'US',
                'shipping_method' => 'etsy',
                'notes' => 'Imported from Etsy receipt #'.$receiptId,
            ]);

            $this->importTransactions($order, $receipt['transactions'] ?? []);

            $this->line("  ✓ [{$receiptId}] {$receipt['name']} — \${$order->total}");
            $imported++;
        }

        $this->info("  Imported {$imported} order(s).");
    }

    private function importTransactions(Order $order, array $transactions): void
    {
        foreach ($transactions as $tx) {
            $product = Product::where('etsy_listing_id', (string) ($tx['listing_id'] ?? ''))->first();
            $price = $this->money($tx['price'] ?? null) ?? 0;
            $qty = $tx['quantity'] ?? 1;

            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $product?->id,
                'etsy_transaction_id' => (string) $tx['transaction_id'],
                'name_snapshot' => $tx['title'] ?? '',
                'sku_snapshot' => $tx['sku'] ?? '',
                'price_snapshot' => $price,
                'qty' => $qty,
                'subtotal' => round($price * $qty, 2),
                'etsy_expected_ship_date' => isset($tx['expected_ship_date']) && $tx['expected_ship_date']
                    ? date('Y-m-d H:i:s', $tx['expected_ship_date'])
                    : null,
                'etsy_buyer_coupon' => $tx['buyer_coupon'] ?? null,
                'etsy_shop_coupon' => $tx['shop_coupon'] ?? null,
            ]);
        }
    }

    private function fetchAll(string $path): Collection
    {
        $all = collect();
        $limit = 100;
        $offset = 0;

        do {
            $response = $this->client->get("/application/shops/{$this->shopId}{$path}", [
                'limit' => $limit,
                'offset' => $offset,
            ]);
            $results = $response['results'] ?? [];
            $all = $all->merge($results);
            $offset += $limit;
        } while (count($results) === $limit);

        return $all;
    }

    private function uniqueSlug(string $base): string
    {
        $slug = $base;
        $i = 2;

        while (Product::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }

    private function money(?array $money): ?float
    {
        if (! $money || ! isset($money['amount'], $money['divisor']) || $money['divisor'] === 0) {
            return null;
        }

        return round($money['amount'] / $money['divisor'], 2);
    }
}
