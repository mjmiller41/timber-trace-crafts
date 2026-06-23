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

    public function lookup(Request $request): View
    {
        $validated = $request->validate([
            'order_number' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:255'],
        ]);

        $order = Order::where('order_number', $validated['order_number'])
            ->where('email', strtolower($validated['email']))
            ->firstOrFail();

        return view('order.status', compact('order'));
    }
}
