<?php

namespace App\Services\Etsy;

use App\Models\Order;
use App\Models\Setting;
use App\Models\Shipment;
use Illuminate\Support\Facades\Log;

class EtsyShipmentSync
{
    public function __construct(private readonly EtsyClient $client) {}

    public function pushShipment(Order $order, Shipment $shipment): bool
    {
        if (! $order->etsy_receipt_id) {
            return false;
        }

        $shopId = Setting::get('etsy.shop_id');

        try {
            $this->client->post(
                "/application/shops/{$shopId}/receipts/{$order->etsy_receipt_id}/tracking",
                [
                    'carrier_name' => $shipment->carrier,
                    'tracking_code' => $shipment->tracking_number,
                    'send_bcc' => true,
                ]
            );

            return true;
        } catch (\Throwable $e) {
            Log::error('Etsy shipment push failed', [
                'order_id' => $order->id,
                'receipt_id' => $order->etsy_receipt_id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
