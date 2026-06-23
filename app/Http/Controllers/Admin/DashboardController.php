<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactSubmission;
use App\Models\Order;
use App\Models\ProductReview;
use App\Models\ProductVariant;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        // Revenue figures — only count paid/fulfilled orders, not cancelled/refunded
        $excludedStatuses = ['cancelled', 'refunded', 'pending_payment'];

        $todayRevenue = Order::whereNotIn('status', $excludedStatuses)
            ->whereDate('created_at', today())
            ->sum('total');

        $weekRevenue = Order::whereNotIn('status', $excludedStatuses)
            ->where('created_at', '>=', now()->startOfWeek())
            ->sum('total');

        $monthRevenue = Order::whereNotIn('status', $excludedStatuses)
            ->where('created_at', '>=', now()->startOfMonth())
            ->sum('total');

        // Order counts by status
        $rawCounts = Order::selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        $orderCounts = [
            'pending_payment' => $rawCounts['pending_payment'] ?? 0,
            'processing' => $rawCounts['processing'] ?? 0,
            'in_production' => $rawCounts['in_production'] ?? 0,
            'shipped' => $rawCounts['shipped'] ?? 0,
            'delivered' => $rawCounts['delivered'] ?? 0,
        ];

        // Recent orders (last 10)
        $recentOrders = Order::with('user')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        // Low-stock variants (at or below their low_stock_threshold)
        $lowStockVariants = ProductVariant::with('product')
            ->whereColumn('stock_qty', '<=', 'low_stock_threshold')
            ->orderBy('stock_qty')
            ->limit(20)
            ->get();

        // Pending reviews count
        $pendingReviews = ProductReview::where('status', 'pending')->count();

        // Unread contact messages count
        $unreadMessages = ContactSubmission::where('status', 'new')->count();

        return view('admin.dashboard', compact(
            'todayRevenue',
            'weekRevenue',
            'monthRevenue',
            'orderCounts',
            'recentOrders',
            'lowStockVariants',
            'pendingReviews',
            'unreadMessages',
        ));
    }
}
