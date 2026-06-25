<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Services\Etsy\EtsyClient;
use App\Services\Etsy\EtsyOAuthService;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class EtsyDiffCommand extends Command
{
    protected $signature = 'etsy:diff
                            {--type=all : What to diff — listings, orders, sections, or all}
                            {--save : Save full JSON report to storage/app/}';

    protected $description = 'Pull all Etsy data and diff against the local database';

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
        $report = ['generated_at' => now()->toISOString()];

        if (in_array($type, ['all', 'listings'])) {
            $this->info('Fetching Etsy listings...');
            $report['listings'] = $this->diffListings();
        }

        if (in_array($type, ['all', 'orders'])) {
            $this->info('Fetching Etsy receipts...');
            $report['orders'] = $this->diffOrders();
        }

        if (in_array($type, ['all', 'sections'])) {
            $this->info('Fetching Etsy shop sections...');
            $report['sections'] = $this->diffSections();
        }

        $this->showSummary($report);

        if ($this->option('save')) {
            $filename = 'etsy-diff-'.now()->format('Y-m-d-His').'.json';
            Storage::put($filename, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            $this->line('');
            $this->info("Full report saved: storage/app/{$filename}");
        }

        return 0;
    }

    private function diffListings(): array
    {
        $etsyListings = $this->fetchAllListings();
        $this->line("  Found {$etsyListings->count()} listings on Etsy.");

        $dbProducts = Product::whereNotNull('etsy_listing_id')->get()->keyBy('etsy_listing_id');
        $etsyById = $etsyListings->keyBy('listing_id');

        $etsyOnly = [];
        $conflicts = [];
        $matched = 0;

        foreach ($etsyListings as $listing) {
            $id = (string) $listing['listing_id'];

            if (! $dbProducts->has($id)) {
                $etsyOnly[] = [
                    'listing_id' => $id,
                    'title' => $listing['title'],
                    'state' => $listing['state'],
                    'price' => $this->money($listing['price'] ?? null),
                    'tags' => $listing['tags'] ?? [],
                    'shop_section_id' => $listing['shop_section_id'],
                ];

                continue;
            }

            $product = $dbProducts->get($id);
            $diff = $this->compareListingToProduct($listing, $product);

            if (! empty($diff)) {
                $conflicts[] = [
                    'listing_id' => $id,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'differences' => $diff,
                ];
            } else {
                $matched++;
            }
        }

        // DB products linked to Etsy but not found on Etsy
        $dbOnly = $dbProducts->filter(fn ($p) => ! $etsyById->has($p->etsy_listing_id))
            ->map(fn ($p) => ['product_id' => $p->id, 'name' => $p->name, 'etsy_listing_id' => $p->etsy_listing_id])
            ->values()
            ->toArray();

        return compact('etsyOnly', 'conflicts', 'dbOnly', 'matched');
    }

    private function diffOrders(): array
    {
        $etsyReceipts = $this->fetchAllReceipts();
        $this->line("  Found {$etsyReceipts->count()} receipts on Etsy.");

        $dbOrders = Order::whereNotNull('etsy_receipt_id')->get()->keyBy('etsy_receipt_id');
        $etsyById = $etsyReceipts->keyBy('receipt_id');

        $etsyOnly = [];
        $conflicts = [];
        $matched = 0;

        foreach ($etsyReceipts as $receipt) {
            $id = (string) $receipt['receipt_id'];

            if (! $dbOrders->has($id)) {
                $etsyOnly[] = [
                    'receipt_id' => $id,
                    'buyer_name' => $receipt['name'] ?? null,
                    'status' => $receipt['status'],
                    'is_paid' => $receipt['is_paid'],
                    'is_shipped' => $receipt['is_shipped'],
                    'grandtotal' => $this->money($receipt['grandtotal'] ?? null),
                    'created_at' => date('Y-m-d', $receipt['create_timestamp']),
                    'transaction_count' => count($receipt['transactions'] ?? []),
                ];

                continue;
            }

            $order = $dbOrders->get($id);
            $diff = $this->compareReceiptToOrder($receipt, $order);

            if (! empty($diff)) {
                $conflicts[] = [
                    'receipt_id' => $id,
                    'order_id' => $order->id,
                    'differences' => $diff,
                ];
            } else {
                $matched++;
            }
        }

        $dbOnly = $dbOrders->filter(fn ($o) => ! $etsyById->has($o->etsy_receipt_id))
            ->map(fn ($o) => ['order_id' => $o->id, 'etsy_receipt_id' => $o->etsy_receipt_id, 'status' => $o->status])
            ->values()
            ->toArray();

        return compact('etsyOnly', 'conflicts', 'dbOnly', 'matched');
    }

    private function diffSections(): array
    {
        $response = $this->client->get("/application/shops/{$this->shopId}/sections");
        $sections = $response['results'] ?? [];

        return array_map(fn ($s) => [
            'section_id' => $s['shop_section_id'],
            'title' => $s['title'],
            'rank' => $s['rank'],
            'active_listings' => $s['active_listing_count'],
        ], $sections);
    }

    private function fetchAllListings(): Collection
    {
        $all = collect();
        $limit = 100;
        $offset = 0;

        do {
            $response = $this->client->get("/application/shops/{$this->shopId}/listings", [
                'limit' => $limit,
                'offset' => $offset,
            ]);
            $results = $response['results'] ?? [];
            $all = $all->merge($results);
            $offset += $limit;
        } while (count($results) === $limit);

        return $all;
    }

    private function fetchAllReceipts(): Collection
    {
        $all = collect();
        $limit = 100;
        $offset = 0;

        do {
            $response = $this->client->get("/application/shops/{$this->shopId}/receipts", [
                'limit' => $limit,
                'offset' => $offset,
            ]);
            $results = $response['results'] ?? [];
            $all = $all->merge($results);
            $offset += $limit;
        } while (count($results) === $limit);

        return $all;
    }

    private function compareListingToProduct(array $listing, Product $product): array
    {
        $diff = [];

        $etsyPrice = $this->money($listing['price'] ?? null);
        if ($etsyPrice !== null && (float) $product->price !== $etsyPrice) {
            $diff['price'] = ['db' => (float) $product->price, 'etsy' => $etsyPrice];
        }

        $etsyTitle = $listing['title'] ?? null;
        if ($etsyTitle && $product->name !== $etsyTitle) {
            $diff['title'] = ['db' => $product->name, 'etsy' => $etsyTitle];
        }

        $etsyState = $listing['state'] ?? null;
        $dbStatus = $product->status;
        $mappedState = match ($dbStatus) {
            'published' => 'active',
            'draft' => 'draft',
            default => null,
        };
        if ($etsyState && $mappedState && $etsyState !== $mappedState) {
            $diff['status'] = ['db' => $dbStatus, 'etsy' => $etsyState];
        }

        return $diff;
    }

    private function compareReceiptToOrder(array $receipt, Order $order): array
    {
        $diff = [];

        $etsyTotal = $this->money($receipt['grandtotal'] ?? null);
        if ($etsyTotal !== null && (float) $order->total !== $etsyTotal) {
            $diff['total'] = ['db' => (float) $order->total, 'etsy' => $etsyTotal];
        }

        return $diff;
    }

    private function money(?array $money): ?float
    {
        if (! $money || ! isset($money['amount'], $money['divisor']) || $money['divisor'] === 0) {
            return null;
        }

        return round($money['amount'] / $money['divisor'], 2);
    }

    private function showSummary(array $report): void
    {
        $this->line('');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->line('  ETSY DIFF SUMMARY');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        if (isset($report['listings'])) {
            $l = $report['listings'];
            $this->line('');
            $this->line('  <fg=yellow>LISTINGS</>');
            $this->table(
                ['Category', 'Count'],
                [
                    ['Etsy only (not in DB)', count($l['etsyOnly'])],
                    ['DB only (not on Etsy)', count($l['dbOnly'])],
                    ['Conflicts (both exist, data differs)', count($l['conflicts'])],
                    ['Matched (identical)', $l['matched']],
                ]
            );

            if (! empty($l['etsyOnly'])) {
                $this->line('  <fg=cyan>Etsy-only listings:</>');
                foreach ($l['etsyOnly'] as $item) {
                    $this->line("    [{$item['listing_id']}] {$item['title']} ({$item['state']}) \${$item['price']}");
                }
            }

            if (! empty($l['conflicts'])) {
                $this->line('  <fg=red>Conflicts:</>');
                foreach ($l['conflicts'] as $c) {
                    $this->line("    [{$c['listing_id']}] {$c['product_name']}");
                    foreach ($c['differences'] as $field => $vals) {
                        $this->line("      {$field}: DB=\"{$vals['db']}\" | Etsy=\"{$vals['etsy']}\"");
                    }
                }
            }

            if (! empty($l['dbOnly'])) {
                $this->line('  <fg=red>DB-only (etsy_listing_id not found on Etsy):</>');
                foreach ($l['dbOnly'] as $item) {
                    $this->line("    [product #{$item['product_id']}] {$item['name']} (etsy_id: {$item['etsy_listing_id']})");
                }
            }
        }

        if (isset($report['orders'])) {
            $o = $report['orders'];
            $this->line('');
            $this->line('  <fg=yellow>ORDERS / RECEIPTS</>');
            $this->table(
                ['Category', 'Count'],
                [
                    ['Etsy only (not in DB)', count($o['etsyOnly'])],
                    ['DB only (not on Etsy)', count($o['dbOnly'])],
                    ['Conflicts', count($o['conflicts'])],
                    ['Matched', $o['matched']],
                ]
            );

            if (! empty($o['etsyOnly'])) {
                $this->line('  <fg=cyan>Etsy-only receipts (need import):</>');
                foreach ($o['etsyOnly'] as $item) {
                    $paid = $item['is_paid'] ? 'paid' : 'unpaid';
                    $this->line("    [{$item['receipt_id']}] {$item['buyer_name']} — \${$item['grandtotal']} {$paid} ({$item['created_at']}) — {$item['transaction_count']} item(s)");
                }
            }
        }

        if (isset($report['sections'])) {
            $this->line('');
            $this->line('  <fg=yellow>SHOP SECTIONS</>');
            if (empty($report['sections'])) {
                $this->line('  No sections on Etsy.');
            } else {
                $this->table(
                    ['Section ID', 'Title', 'Rank', 'Active Listings'],
                    array_map(fn ($s) => [$s['section_id'], $s['title'], $s['rank'], $s['active_listings']], $report['sections'])
                );
            }
        }

        $this->line('');
        $this->line('Run with <fg=green>--save</> to write the full JSON report to storage/app/');
    }
}
