<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderStatusController extends Controller
{
    public function index(): View
    {
        return view('order.status');
    }

    public function lookup(Request $request): mixed
    {
        $validated = $request->validate([
            'order_number' => ['required', 'integer', 'min:1'],
            'email' => ['required', 'email', 'max:255'],
        ]);

        $email = strtolower($validated['email']);

        $order = Order::with(['items', 'shipments', 'statusHistory'])
            ->where('id', $validated['order_number'])
            ->where(function ($q) use ($email) {
                $q->where('guest_email', $email)
                    ->orWhereHas('user', fn ($u) => $u->where('email', $email));
            })
            ->first();

        if (! $order) {
            return back()
                ->withErrors(['lookup' => 'No order found with that order number and email address.'])
                ->withInput();
        }

        return view('order.status', compact('order'));
    }

    /**
     * Show an order via a signed deep-link (used in order emails). The `signed`
     * middleware authorises the request, so no email is needed in the URL.
     */
    public function view(Order $order): View
    {
        $order->load(['items', 'shipments', 'statusHistory']);

        return view('order.status', compact('order'));
    }
}
