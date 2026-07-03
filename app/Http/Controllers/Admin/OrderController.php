<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\OrderShippedMail;
use App\Mail\OrderStatusChangedMail;
use App\Models\Order;
use App\Services\Etsy\EtsyClient;
use App\Services\Etsy\EtsyOAuthService;
use App\Services\Etsy\EtsyShipmentSync;
use App\Services\StripeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Stripe\Exception\ApiErrorException;

class OrderController extends Controller
{
    public function index(): View
    {
        Cache::forget('etsy.new_orders');

        $orders = Order::with('user')
            ->withCount('items')
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

        // A Stripe order with a live balance must be refunded through the Stripe
        // API (which sets this status itself), not by flipping the label here.
        // Etsy/manual orders have no PaymentIntent, so they refund freely.
        if ($validated['status'] === 'refunded' && $order->isStripeRefundable()) {
            return redirect()->route('admin.orders.show', $order)
                ->with('error', 'Use the Refund card to refund a Stripe payment — issuing the refund sets this status automatically.');
        }

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
                        ->queue(new OrderStatusChangedMail($order));
                }
            } catch (\Throwable $e) {
                Log::error('Status change email failed', ['order' => $order->id, 'error' => $e->getMessage()]);
            }
        }

        return redirect()->route('admin.orders.show', $order)->with('success', 'Order status updated.');
    }

    public function refund(Request $request, Order $order, StripeService $stripe): RedirectResponse
    {
        if (! $order->isStripeRefundable()) {
            return redirect()->route('admin.orders.show', $order)
                ->with('error', 'This order has no refundable Stripe payment.');
        }

        $validated = $request->validate([
            'amount' => ['nullable', 'numeric', 'min:0.01', 'max:'.$order->refundableAmount()],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        // Default to the full remaining balance when no amount is supplied.
        $amount = isset($validated['amount']) ? round((float) $validated['amount'], 2) : $order->refundableAmount();
        $amountCents = (int) round($amount * 100);

        try {
            $refund = $stripe->refundPayment($order->stripe_payment_intent_id, $amountCents);
        } catch (ApiErrorException $e) {
            Log::error('Stripe refund failed', ['order' => $order->id, 'error' => $e->getMessage()]);

            return redirect()->route('admin.orders.show', $order)
                ->with('error', 'Stripe refund failed: '.$e->getMessage());
        }

        $refundedTotal = round((float) $order->refunded_amount + $amount, 2);
        $isFullyRefunded = $refundedTotal >= (float) $order->total;

        $order->update([
            'stripe_refund_id' => $refund->id,
            'refunded_amount' => $refundedTotal,
            'refunded_at' => now(),
            'status' => $isFullyRefunded ? 'refunded' : $order->status,
        ]);

        $noteParts = array_filter([
            ($isFullyRefunded ? 'Full' : 'Partial').' refund of $'.number_format($amount, 2).' issued via Stripe',
            $validated['note'] ?? null,
        ]);

        $order->statusHistory()->create([
            'status' => $isFullyRefunded ? 'refunded' : $order->status,
            'note' => implode(' — ', $noteParts),
            'created_by' => auth()->id(),
        ]);

        if ($isFullyRefunded) {
            try {
                $email = $order->user?->email ?? $order->guest_email;
                if ($email) {
                    Mail::to($email)->queue(new OrderStatusChangedMail($order));
                }
            } catch (\Throwable $e) {
                Log::error('Refund email failed', ['order' => $order->id, 'error' => $e->getMessage()]);
            }
        }

        return redirect()->route('admin.orders.show', $order)
            ->with('success', ($isFullyRefunded ? 'Full' : 'Partial').' refund of $'.number_format($amount, 2).' processed.');
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
        $etsyFlash = [];
        if ($order->etsy_receipt_id) {
            $pushed = (new EtsyShipmentSync(new EtsyClient(new EtsyOAuthService)))->pushShipment($order, $shipment);
            $etsyFlash = $pushed
                ? ['success_etsy' => 'Tracking sent to Etsy.']
                : ['error_etsy' => 'Etsy tracking push failed — check logs.'];
        }

        // Notify customer
        try {
            $email = $order->user?->email ?? $order->guest_email;
            if ($email) {
                Mail::to($email)
                    ->queue(new OrderShippedMail($order, $shipment));
            }
        } catch (\Throwable $e) {
            Log::error('Shipped email failed', ['order' => $order->id, 'error' => $e->getMessage()]);
        }

        return redirect()->route('admin.orders.show', $order)
            ->with('success', 'Shipment added.')
            ->with($etsyFlash);
    }

    public function packingSlip(Order $order): View
    {
        $order->load(['items.variant.product', 'user']);

        return view('admin.orders.packing-slip', compact('order'));
    }
}
