<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(): View
    {
        $excluded = ['cancelled', 'refunded', 'pending_payment'];

        $revenueByMonth = Order::whereNotIn('status', $excluded)
            ->selectRaw('substr(created_at, 1, 7) as month, SUM(total) as revenue, COUNT(*) as orders')
            ->groupByRaw('substr(created_at, 1, 7)')
            ->orderByRaw('substr(created_at, 1, 7) DESC')
            ->limit(12)
            ->get();

        $topProducts = OrderItem::join('orders', 'order_items.order_id', '=', 'orders.id')
            ->whereNotIn('orders.status', $excluded)
            ->selectRaw('name_snapshot as name, SUM(order_items.qty) as units_sold, SUM(order_items.subtotal) as revenue')
            ->groupBy('name_snapshot')
            ->orderByDesc('revenue')
            ->limit(10)
            ->get();

        return view('admin.reports.index', compact('revenueByMonth', 'topProducts'));
    }
}
