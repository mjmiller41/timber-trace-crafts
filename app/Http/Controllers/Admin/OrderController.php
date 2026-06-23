<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\OrderShippedMail;
use App\Mail\OrderStatusChangedMail;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(): View
    {
        $orders = Order::with('user')
            ->orderByDesc('created_at')
            ->paginate(25);

        return view('admin.orders.index', compact('orders'));
    }

    public function show(Order $order): View
    {
        $order->load(['items.variant.product', 'user', 'shipments']);

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
            'tracking_number' => ['required', 'string', 'max:100'],
            'shipped_at' => ['nullable', 'date'],
        ]);

        $order->shipments()->create([
            'carrier' => $validated['carrier'],
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

        // Notify customer
        try {
            $email = $order->user?->email ?? $order->guest_email;
            if ($email) {
                $shipment = $order->shipments()->latest()->first();
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
