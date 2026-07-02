<?php

namespace App\Services\Etsy;

use App\Exceptions\EtsyApiException;
use App\Models\Order;
use App\Models\Setting;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
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
        $fetchFailed = false;

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
                $fetchFailed = true;
                break;
            }

            $receipts = $response['results'] ?? [];
            $count = $response['count'] ?? 0;

            foreach ($receipts as $receipt) {
                $receiptId = (string) $receipt['receipt_id'];

                // Only skip receipts that already have a fully-imported order — a
                // previous run that failed mid-import can leave an itemless order,
                // which importReceipt() will heal via updateOrCreate.
                if (Order::where('etsy_receipt_id', $receiptId)->whereHas('items')->exists()) {
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

        // Only advance the watermark when the receipts fetch itself succeeded —
        // otherwise the failed window would be silently skipped on every future run.
        if (! $fetchFailed) {
            Setting::set('etsy.orders_last_synced_at', now()->toISOString());
        }

        return $result;
    }

    public function importFromResourceUrl(string $resourceUrl): ?Order
    {
        $path = preg_replace('#^https://api\.etsy\.com/v3#', '', $resourceUrl);
        $receipt = $this->client->get($path);

        if (! $receipt) {
            Log::error('Etsy receipt fetch returned an empty payload', ['resource_url' => $resourceUrl]);

            return null;
        }

        return $this->importReceipt($receipt);
    }

    /**
     * Create or heal an order from an Etsy receipt. Wrapped in a transaction and
     * keyed on etsy_receipt_id so a retry after a mid-import failure re-creates
     * the full item set instead of tripping the unique index on a half-built order.
     */
    public function importReceipt(array $receipt): Order
    {
        return DB::transaction(function () use ($receipt) {
            [$firstName, $lastName] = $this->splitName($receipt['name'] ?? '');

            $order = Order::updateOrCreate(
                ['etsy_receipt_id' => (string) $receipt['receipt_id']],
                [
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
                ]
            );

            // Idempotent retry: clear any partial item set from a prior failed
            // attempt before re-creating it from the (now complete) receipt.
            $order->items()->delete();

            foreach ($receipt['transactions'] ?? [] as $transaction) {
                $price = $this->toDollars($transaction['price'] ?? []);

                $order->items()->create([
                    'name_snapshot' => $transaction['title'] ?? '',
                    'sku_snapshot' => $transaction['sku'] ?? '',
                    'variant_label_snapshot' => '',
                    'etsy_transaction_id' => isset($transaction['transaction_id']) ? (string) $transaction['transaction_id'] : null,
                    'price_snapshot' => $price,
                    'qty' => $transaction['quantity'] ?? 1,
                    'subtotal' => $price * ($transaction['quantity'] ?? 1),
                ]);
            }

            return $order;
        });
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
