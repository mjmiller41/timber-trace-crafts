<?php

namespace App\Jobs;

use App\Models\Order;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class UpdateEtsyOrderStatus implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 30;

    public int $timeout = 30;

    public function __construct(
        public readonly string $receiptId,
        public readonly string $action,
    ) {}

    public function handle(): void
    {
        match ($this->action) {
            'canceled' => Order::where('etsy_receipt_id', $this->receiptId)->update(['status' => 'cancelled']),
            'shipped' => Order::where('etsy_receipt_id', $this->receiptId)->update([
                'etsy_is_shipped' => true,
                'status' => 'shipped',
            ]),
            'delivered' => Order::where('etsy_receipt_id', $this->receiptId)->update(['status' => 'delivered']),
            default => throw new \InvalidArgumentException("Unknown Etsy order status action: {$this->action}"),
        };
    }

    public function failed(\Throwable $e): void
    {
        Log::error('UpdateEtsyOrderStatus job failed', [
            'receipt_id' => $this->receiptId,
            'action' => $this->action,
            'error' => $e->getMessage(),
        ]);
    }
}
