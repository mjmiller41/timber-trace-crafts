<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\OrderShippedMail;
use App\Mail\OrderStatusChangedMail;
use App\Models\Order;
use App\Services\Etsy\EtsyClient;
use App\Services\Etsy\EtsyOAuthService;
use App\Services\Etsy\EtsyShipmentSync;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(): View
    {
        Cache::forget('etsy.new_orders');

        $orders = Order::with('user')
            ->orderByDesc('created_at')
            ->paginate(25);

        return view('admin.orders.index', compact('orders'));
    }

    public function show(Order $order): View
    {
        $order->load(['items.product', 'items.variant', 'user', 'shipments', 'statusHistory.creator']);

        return view('admin.orders.show', compact('order'));
    }

    public function updateStatus(Request $request, Order $order): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:pending_payment,processing,in_production,shipped,delivered,refunded,cancelled'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $oldStatus = $order->status;
        $order->update(['status' => $validated['status']]);

        $order->statusHistory()->create([
            'status' => $validated['status'],
            'note' => $validated['note'] ?? null,
            'created_by' => auth()->id(),
        ]);

        if ($oldStatus !== $validated['status']) {
            try {
                $email = $order->user?->email ?? $order->guest_email;
                if ($email) {
                    Mail::to($email)
                        ->send(new OrderStatusChangedMail($order));
                }
            } catch (\Throwable $e) {
                Log::error('Status change email failed', ['order' => $order->id, 'error' => $e->getMessage()]);
            }
        }

        return redirect()->route('admin.orders.show', $order)->with('success', 'Order status updated.');
    }

    public function addShipment(Request $request, Order $order): RedirectResponse
    {
        $validated = $request->validate([
            'carrier' => ['required', 'string', 'max:100'],
            'service' => ['nullable', 'string', 'max:100'],
            'tracking_number' => ['required', 'string', 'max:100'],
            'shipped_at' => ['nullable', 'date'],
        ]);

        $shipment = $order->shipments()->create([
            'carrier' => $validated['carrier'],
            'service' => $validated['service'] ?? null,
            'tracking_number' => $validated['tracking_number'],
            'shipped_at' => $validated['shipped_at'] ?? now(),
        ]);

        if (in_array($order->status, ['processing', 'in_production'])) {
            $order->update(['status' => 'shipped']);
            $order->statusHistory()->create([
                'status' => 'shipped',
                'note' => 'Shipment added — tracking: '.$validated['tracking_number'],
                'created_by' => auth()->id(),
            ]);
        }

        // Push tracking to Etsy if this is an Etsy order
        try {
            $etsySync = new EtsyShipmentSync(new EtsyClient(new EtsyOAuthService));
            $etsySync->pushShipment($order, $shipment);
        } catch (\Throwable $e) {
            Log::error('Etsy shipment hook failed', ['order_id' => $order->id, 'error' => $e->getMessage()]);
        }

        // Notify customer
        try {
            $email = $order->user?->email ?? $order->guest_email;
            if ($email) {
                Mail::to($email)
                    ->send(new OrderShippedMail($order, $shipment));
            }
        } catch (\Throwable $e) {
            Log::error('Shipped email failed', ['order' => $order->id, 'error' => $e->getMessage()]);
        }

        return redirect()->route('admin.orders.show', $order)->with('success', 'Shipment added.');
    }

    public function packingSlip(Order $order): View
    {
        $order->load(['items.variant.product', 'user']);

        return view('admin.orders.packing-slip', compact('order'));
    }
}
