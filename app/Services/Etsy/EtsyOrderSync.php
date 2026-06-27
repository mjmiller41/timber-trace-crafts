<?php

namespace App\Services\Etsy;

use App\Exceptions\EtsyApiException;
use App\Models\Order;
use App\Models\Setting;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Log;

class EtsyOrderSync
{
    public function __construct(private readonly EtsyClient $client) {}

    public function sync(): SyncResult
    {
        $result = new SyncResult;
        $shopId = Setting::get('etsy.shop_id');
        $lastSynced = Setting::get('etsy.orders_last_synced_at');
        $minCreated = $lastSynced ? strtotime($lastSynced) : now()->subDays(30)->timestamp;

        $offset = 0;
        $limit = 100;

        do {
            try {
                $response = $this->client->get("/application/shops/{$shopId}/receipts", [
                    'min_created' => $minCreated,
                    'limit' => $limit,
                    'offset' => $offset,
                    'was_paid' => true,
                ]);
            } catch (EtsyApiException $e) {
                Log::error('Etsy order sync failed', ['error' => $e->getMessage()]);
                $result->failed++;
                break;
            }

            $receipts = $response['results'] ?? [];
            $count = $response['count'] ?? 0;

            foreach ($receipts as $receipt) {
                $receiptId = (string) $receipt['receipt_id'];

                if (Order::where('etsy_receipt_id', $receiptId)->exists()) {
                    $result->skipped++;

                    continue;
                }

                try {
                    $this->importReceipt($receipt);
                    $result->created++;
                } catch (UniqueConstraintViolationException $e) {
                    $result->skipped++;
                } catch (\Throwable $e) {
                    $result->failed++;
                    Log::error('Etsy receipt import failed', [
                        'receipt_id' => $receiptId,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $offset += $limit;
            usleep(250_000);
        } while (count($receipts) === $limit);

        Setting::set('etsy.orders_last_synced_at', now()->toISOString());

        return $result;
    }

    public function importFromResourceUrl(string $resourceUrl): void
    {
        $path = preg_replace('#^https://api\.etsy\.com/v3#', '', $resourceUrl);
        $receipt = $this->client->get($path);

        if ($receipt) {
            $this->importReceipt($receipt);
        }
    }

    public function importReceipt(array $receipt): void
    {
        [$firstName, $lastName] = $this->splitName($receipt['name'] ?? '');

        $order = Order::create([
            'etsy_receipt_id' => (string) $receipt['receipt_id'],
            'status' => 'processing',
            'guest_email' => $receipt['buyer_email'] ?? null,
            'subtotal' => $this->toDollars($receipt['subtotal'] ?? []),
            'shipping_amount' => $this->toDollars($receipt['total_shipping_cost'] ?? []),
            'tax_amount' => $this->toDollars($receipt['total_tax_cost'] ?? []),
            'total' => $this->toDollars($receipt['grandtotal'] ?? []),
            'discount_amount' => 0,
            'shipping_method' => 'Etsy',
            'shipping_first_name' => $firstName,
            'shipping_last_name' => $lastName,
            'shipping_line1' => $receipt['first_line'] ?? '',
            'shipping_line2' => $receipt['second_line'] ?? null,
            'shipping_city' => $receipt['city'] ?? '',
            'shipping_state' => $receipt['state'] ?? '',
            'shipping_zip' => $receipt['zip'] ?? '',
            'shipping_country' => $receipt['country_iso'] ?? 'US',
            'billing_first_name' => $firstName,
            'billing_last_name' => $lastName,
            'billing_line1' => $receipt['first_line'] ?? '',
            'billing_line2' => $receipt['second_line'] ?? null,
            'billing_city' => $receipt['city'] ?? '',
            'billing_state' => $receipt['state'] ?? '',
            'billing_zip' => $receipt['zip'] ?? '',
            'billing_country' => $receipt['country_iso'] ?? 'US',
        ]);

        foreach ($receipt['transactions'] ?? [] as $transaction) {
            $price = $this->toDollars($transaction['price'] ?? []);

            $order->items()->create([
                'name_snapshot' => $transaction['title'] ?? '',
                'sku_snapshot' => $transaction['sku'] ?? '',
                'variant_label_snapshot' => '',
                'price_snapshot' => $price,
                'qty' => $transaction['quantity'] ?? 1,
                'subtotal' => $price * ($transaction['quantity'] ?? 1),
            ]);
        }
    }

    private function toDollars(array $money): float
    {
        $amount = $money['amount'] ?? 0;
        $divisor = $money['divisor'] ?? 100;

        return $divisor > 0 ? round($amount / $divisor, 2) : 0.0;
    }

    private function splitName(string $name): array
    {
        $parts = explode(' ', trim($name), 2);

        return [$parts[0] ?? '', $parts[1] ?? ''];
    }
}
