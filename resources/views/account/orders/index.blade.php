@extends('layouts.app')

@section('title', 'My Orders')

@section('content')
<div class="page-container py-12">

    <div class="mb-8 pb-6 border-b border-walnut/20">
        <p class="section-label mb-2">Account</p>
        <h1 class="font-heading text-3xl font-light">My Orders</h1>
    </div>

    <div class="flex flex-col lg:flex-row gap-10">

        @include('account.partials.sidebar')

        <div class="flex-1 min-w-0">

            @if($orders->isEmpty())
                <div class="card p-12 text-center">
                    <p class="font-heading text-xl font-light mb-3">No orders yet</p>
                    <p class="text-sm text-walnut mb-6">You haven't placed any orders yet.</p>
                    <a href="{{ route('shop') }}" class="btn-primary">
                        Start Shopping
                    </a>
                </div>
            @else
                <div class="card overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-walnut/20">
                                    <th class="text-left py-3 px-4 section-label">Order</th>
                                    <th class="text-left py-3 px-4 section-label hidden sm:table-cell">Date</th>
                                    <th class="text-left py-3 px-4 section-label hidden md:table-cell">Items</th>
                                    <th class="text-left py-3 px-4 section-label">Status</th>
                                    <th class="text-right py-3 px-4 section-label">Total</th>
                                    <th class="py-3 px-4"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-walnut/10">
                                @foreach($orders as $order)
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
                                    <tr class="hover:bg-walnut/5 transition-colors">
                                        <td class="py-3.5 px-4 font-medium">#{{ $order->id }}</td>
                                        <td class="py-3.5 px-4 text-walnut hidden sm:table-cell">
                                            {{ $order->created_at->format('M j, Y') }}
                                        </td>
                                        <td class="py-3.5 px-4 text-walnut hidden md:table-cell">
                                            {{ $order->items_count ?? $order->items->count() }}
                                            {{ ($order->items_count ?? $order->items->count()) === 1 ? 'item' : 'items' }}
                                        </td>
                                        <td class="py-3.5 px-4">
                                            <span class="{{ $badge['class'] }}">{{ $badge['label'] }}</span>
                                        </td>
                                        <td class="py-3.5 px-4 text-right font-medium">
                                            ${{ number_format($order->total, 2) }}
                                        </td>
                                        <td class="py-3.5 px-4 text-right">
                                            <a href="{{ route('account.orders.show', $order) }}" class="btn-outline text-xs px-3 py-1.5">
                                                View
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                @if($orders->hasPages())
                    <div class="mt-6">
                        {{ $orders->links() }}
                    </div>
                @endif
            @endif

        </div>
    </div>
</div>
@endsection
