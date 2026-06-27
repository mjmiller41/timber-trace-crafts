@extends('layouts.app')

@section('title', isset($order) ? 'Order #' . $order->id : 'Track Your Order')

@section('content')

<div class="page-container py-16 md:py-24">
    <div class="max-w-xl mx-auto">

        @if(!isset($order))
            {{-- ============================================================ --}}
            {{-- LOOKUP FORM --}}
            {{-- ============================================================ --}}
            <div class="text-center mb-12">
                <p class="section-label mb-4">Order Tracking</p>
                <h1 class="font-heading text-4xl md:text-5xl font-light text-charcoal">Track Your Order</h1>
            </div>

            <div class="border border-walnut/20 bg-surface p-8 md:p-10">
                <p class="font-body text-sm text-walnut mb-8 leading-relaxed">
                    Enter your order number and the email address used at checkout to see your order status and tracking information.
                </p>

                <form method="POST" action="{{ route('order.status.lookup') }}" class="space-y-5">
                    @csrf

                    <div>
                        <label for="order_number" class="section-label block mb-2">Order Number</label>
                        <input type="text"
                               id="order_number"
                               name="order_number"
                               value="{{ old('order_number', request('order')) }}"
                               placeholder="e.g. 10042"
                               required
                               class="form-field {{ $errors->has('order_number') ? 'border-error' : '' }}">
                        @error('order_number')
                            <p class="font-body text-xs text-error mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="email" class="section-label block mb-2">Email Address</label>
                        <input type="email"
                               id="email"
                               name="email"
                               value="{{ old('email', request('email')) }}"
                               placeholder="you@example.com"
                               required
                               class="form-field {{ $errors->has('email') ? 'border-error' : '' }}">
                        @error('email')
                            <p class="font-body text-xs text-error mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    @if($errors->has('lookup'))
                        <div class="bg-error/10 border border-error/30 px-4 py-3">
                            <p class="font-body text-sm text-error">{{ $errors->first('lookup') }}</p>
                        </div>
                    @endif

                    <button type="submit" class="btn-primary w-full py-4">Look Up Order</button>
                </form>
            </div>

        @else
            {{-- ============================================================ --}}
            {{-- ORDER RESULT --}}
            {{-- ============================================================ --}}
            @php
                $statusMap = [
                    'pending'    => ['label' => 'Pending', 'class' => 'bg-yellow-50 text-yellow-800 border-yellow-200'],
                    'confirmed'  => ['label' => 'Confirmed', 'class' => 'bg-blue-50 text-blue-800 border-blue-200'],
                    'processing' => ['label' => 'Processing', 'class' => 'bg-blue-50 text-blue-800 border-blue-200'],
                    'shipped'    => ['label' => 'Shipped', 'class' => 'bg-forest-green/10 text-forest-green border-forest-green/20'],
                    'delivered'  => ['label' => 'Delivered', 'class' => 'bg-green-50 text-green-800 border-green-200'],
                    'cancelled'  => ['label' => 'Cancelled', 'class' => 'bg-red-50 text-red-800 border-red-200'],
                    'refunded'   => ['label' => 'Refunded', 'class' => 'bg-gray-50 text-gray-600 border-gray-200'],
                ];
                $statusInfo = $statusMap[$order->status] ?? ['label' => ucfirst($order->status), 'class' => 'bg-gray-50 text-gray-600 border-gray-200'];
            @endphp

            <div class="mb-10">
                <p class="section-label mb-3">Order Tracking</p>
                <div class="flex items-start justify-between gap-4 flex-wrap">
                    <h1 class="font-heading text-3xl md:text-4xl font-light text-charcoal">
                        Order #{{ $order->id }}
                    </h1>
                    <span class="inline-block border px-3 py-1.5 font-body text-xs font-600 tracking-wider uppercase {{ $statusInfo['class'] }}">
                        {{ $statusInfo['label'] }}
                    </span>
                </div>
                <p class="section-label mt-2">
                    Placed {{ \Carbon\Carbon::parse($order->created_at)->format('F j, Y') }}
                </p>
            </div>

            {{-- Tracking number --}}
            @if($order->status === 'shipped' && $order->shipments?->isNotEmpty())
                @foreach($order->shipments as $shipment)
                    <div class="border border-forest-green/30 bg-forest-green/5 px-6 py-5 mb-8">
                        <p class="section-label mb-2">Tracking Information</p>
                        <p class="font-body text-sm text-charcoal mb-1">
                            Carrier: <strong>{{ $shipment->carrier ?? 'USPS' }}</strong>
                        </p>
                        <p class="font-body text-sm text-charcoal">
                            Tracking #:
                            @if($shipment->tracking_url)
                                <a href="{{ $shipment->tracking_url }}" target="_blank" rel="noopener"
                                   class="font-600 text-forest-green underline hover:opacity-75 transition-opacity">
                                    {{ $shipment->tracking_number }}
                                </a>
                            @else
                                <strong>{{ $shipment->tracking_number }}</strong>
                            @endif
                        </p>
                    </div>
                @endforeach
            @endif

            {{-- Items summary --}}
            <div class="border border-walnut/20 bg-surface mb-8">
                <div class="px-6 py-4 border-b border-walnut/20">
                    <p class="section-label">Items in Your Order</p>
                </div>
                <div class="px-6 py-4 space-y-3">
                    @foreach($order->items as $item)
                        <div class="flex items-start justify-between gap-4 py-2">
                            <div>
                                <p class="font-body text-sm text-charcoal font-500">{{ $item->name_snapshot }}</p>
                                @if($item->variant_label_snapshot)
                                    <p class="section-label">{{ $item->variant_label_snapshot }}</p>
                                @endif
                                <p class="section-label">Qty: {{ $item->qty }}</p>
                            </div>
                            <p class="font-body text-sm text-charcoal font-600 flex-shrink-0">
                                ${{ number_format($item->subtotal, 2) }}
                            </p>
                        </div>
                    @endforeach
                </div>
                <div class="px-6 py-4 border-t border-walnut/20">
                    <div class="flex justify-between">
                        <span class="font-body text-base font-600 text-charcoal">Total</span>
                        <span class="font-body text-base font-600 text-charcoal">${{ number_format($order->total, 2) }}</span>
                    </div>
                </div>
            </div>

            {{-- Status Timeline --}}
            @if($order->statusHistory && $order->statusHistory->isNotEmpty())
                <div class="mb-10">
                    <p class="section-label mb-6">Order Timeline</p>
                    <div class="relative">
                        <div class="absolute left-3 top-3 bottom-3 w-px bg-walnut/20"></div>
                        <div class="space-y-6">
                            @foreach($order->statusHistory->sortByDesc('created_at') as $history)
                                @php
                                    $historyInfo = $statusMap[$history->status] ?? ['label' => ucfirst($history->status), 'class' => ''];
                                @endphp
                                <div class="flex items-start gap-5">
                                    <div class="w-6 h-6 rounded-full border-2 flex-shrink-0 z-10 flex items-center justify-center
                                                {{ $loop->first ? 'bg-forest-green border-forest-green' : 'bg-oak-sand border-walnut/40' }}">
                                        @if($loop->first)
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-3 h-3 text-white">
                                                <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                                            </svg>
                                        @endif
                                    </div>
                                    <div class="flex-1 pb-2">
                                        <div class="flex items-center justify-between gap-2 flex-wrap">
                                            <p class="font-body text-sm font-600 text-charcoal">{{ $historyInfo['label'] }}</p>
                                            <p class="section-label">
                                                {{ \Carbon\Carbon::parse($history->created_at)->format('M j, Y · g:i A') }}
                                            </p>
                                        </div>
                                        @if($history->note)
                                            <p class="font-body text-xs text-walnut mt-1">{{ $history->note }}</p>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            <div class="flex flex-col sm:flex-row gap-4">
                <a href="{{ route('shop') }}" class="btn-forest text-center py-4 flex-1">Continue Shopping</a>
                <a href="{{ route('order.status') }}" class="btn-outline text-center py-4 flex-1">Track Another Order</a>
            </div>

        @endif

    </div>
</div>

@endsection
