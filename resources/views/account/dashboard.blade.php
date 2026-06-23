@extends('layouts.app')

@section('title', 'My Account')

@section('content')
<div class="page-container py-12">

    {{-- Page Header --}}
    <div class="mb-8 pb-6 border-b border-walnut/20">
        <p class="section-label mb-2">Account</p>
        <h1 class="font-heading text-3xl font-light">Hello, {{ auth()->user()->name }}</h1>
    </div>

    <div class="flex flex-col lg:flex-row gap-10">

        {{-- Sidebar --}}
        @include('account.partials.sidebar')

        {{-- Main Content --}}
        <div class="flex-1 min-w-0">

            {{-- Stats Row --}}
            <div class="grid grid-cols-2 gap-4 mb-10">

                <div class="card p-6">
                    <p class="section-label mb-2">Total Orders</p>
                    <p class="font-heading text-4xl font-light">
                        {{ auth()->user()->orders()->count() }}
                    </p>
                </div>

                <div class="card p-6">
                    <p class="section-label mb-2">Wishlist Items</p>
                    <p class="font-heading text-4xl font-light">
                        {{ \App\Models\Wishlist::where('user_id', auth()->id())->count() }}
                    </p>
                </div>

            </div>

            {{-- Recent Orders --}}
            <div class="mb-10">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="font-heading text-xl font-light">Recent Orders</h2>
                    <a href="{{ route('account.orders') }}" class="text-xs font-semibold uppercase tracking-widest text-walnut hover:text-charcoal transition-colors">
                        View All
                    </a>
                </div>

                @php
                    $recentOrders = auth()->user()->orders()->orderByDesc('created_at')->limit(3)->get();
                @endphp

                @if($recentOrders->isEmpty())
                    <div class="card p-8 text-center">
                        <p class="text-walnut text-sm">No orders yet.</p>
                    </div>
                @else
                    <div class="card overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b border-walnut/20">
                                        <th class="text-left py-3 px-4 section-label font-semibold">Order</th>
                                        <th class="text-left py-3 px-4 section-label font-semibold">Date</th>
                                        <th class="text-left py-3 px-4 section-label font-semibold hidden sm:table-cell">Status</th>
                                        <th class="text-right py-3 px-4 section-label font-semibold">Total</th>
                                        <th class="py-3 px-4"></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-walnut/10">
                                    @foreach($recentOrders as $order)
                                        <tr class="hover:bg-walnut/5 transition-colors">
                                            <td class="py-3 px-4 font-medium">#{{ $order->id }}</td>
                                            <td class="py-3 px-4 text-walnut">{{ $order->created_at->format('M j, Y') }}</td>
                                            <td class="py-3 px-4 hidden sm:table-cell">
                                                @php
                                                    $statusMap = [
                                                        'pending_payment' => ['class' => 'admin-badge-warning', 'label' => 'Pending Payment'],
                                                        'processing'      => ['class' => 'admin-badge-info',    'label' => 'Processing'],
                                                        'in_production'   => ['class' => 'admin-badge-info',    'label' => 'In Production'],
                                                        'shipped'         => ['class' => 'admin-badge-success', 'label' => 'Shipped'],
                                                        'delivered'       => ['class' => 'admin-badge-success', 'label' => 'Delivered'],
                                                        'cancelled'       => ['class' => 'admin-badge-error',   'label' => 'Cancelled'],
                                                        'refunded'        => ['class' => 'admin-badge-error',   'label' => 'Refunded'],
                                                    ];
                                                    $badge = $statusMap[$order->status] ?? ['class' => 'admin-badge-neutral', 'label' => ucwords(str_replace('_', ' ', $order->status))];
                                                @endphp
                                                <span class="{{ $badge['class'] }}">{{ $badge['label'] }}</span>
                                            </td>
                                            <td class="py-3 px-4 text-right font-medium">${{ number_format($order->total, 2) }}</td>
                                            <td class="py-3 px-4 text-right">
                                                <a href="{{ route('account.orders.show', $order) }}" class="text-xs font-semibold uppercase tracking-widest text-walnut hover:text-charcoal transition-colors">
                                                    View
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Quick Links --}}
            <div>
                <h2 class="font-heading text-xl font-light mb-4">Quick Links</h2>
                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('account.orders') }}" class="btn-outline text-sm">
                        View All Orders
                    </a>
                    <a href="{{ route('account.wishlist') }}" class="btn-outline text-sm">
                        My Wishlist
                    </a>
                    <a href="{{ route('account.addresses') }}" class="btn-outline text-sm">
                        Saved Addresses
                    </a>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection
