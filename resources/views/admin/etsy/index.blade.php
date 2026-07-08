@extends('layouts.admin')

@section('page-title', 'Etsy Sync')

@section('content')

@if(session('success'))
<div style="background: #f0fdf4; border: 1px solid #86efac; border-radius: 0.25rem; padding: 0.75rem 1rem; margin-bottom: 1.25rem; font-size: 0.875rem; color: #166534;">
    {{ session('success') }}
</div>
@endif

@if(session('error'))
<div style="background: #fef2f2; border: 1px solid #fca5a5; border-radius: 0.25rem; padding: 0.75rem 1rem; margin-bottom: 1.25rem; font-size: 0.875rem; color: #991b1b;">
    {{ session('error') }}
</div>
@endif

<div class="admin-card" style="max-width: 760px;">
    <div class="admin-card-header">
        <span class="admin-card-title">Etsy Connection</span>

        @if($isConnected)
            <span style="font-size: 0.75rem; font-weight: 600; color: #166534; background: #dcfce7; padding: 0.25rem 0.625rem; border-radius: 9999px;">Connected</span>
        @else
            <span style="font-size: 0.75rem; font-weight: 600; color: #991b1b; background: #fee2e2; padding: 0.25rem 0.625rem; border-radius: 9999px;">Disconnected</span>
        @endif
    </div>

    @if($isConnected)
        <div style="padding: 1.25rem; display: flex; flex-direction: column; gap: 0.75rem;">
            <div style="font-size: 0.875rem; color: #6b7280;">
                <strong>Shop ID:</strong> {{ $shopId }}
            </div>
            @if($tokenExpiresAt)
            <div style="font-size: 0.875rem; color: #6b7280;">
                <strong>Token expires:</strong> {{ \Carbon\Carbon::parse($tokenExpiresAt)->diffForHumans() }}
            </div>
            @endif

            <form method="POST" action="{{ route('admin.etsy.disconnect') }}" style="margin-top: 0.5rem;">
                @csrf
                <button type="submit" class="admin-btn admin-btn-danger" onclick="return confirm('Disconnect from Etsy?')">
                    Disconnect
                </button>
            </form>
        </div>
    @else
        <div style="padding: 1.25rem;">
            <p style="font-size: 0.875rem; color: #6b7280; margin-bottom: 1rem;">
                Connect your Etsy shop to enable bidirectional sync of products, inventory, and orders.
            </p>
            <a href="{{ route('admin.etsy.connect') }}" class="admin-btn admin-btn-primary">
                Connect to Etsy
            </a>
        </div>
    @endif
</div>

@if($isConnected)
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem; max-width: 760px; margin-top: 1.5rem;">

    {{-- Products --}}
    <div class="admin-card">
        <div class="admin-card-header">
            <span class="admin-card-title">Products</span>
        </div>
        <div style="padding: 1rem;">
            <p style="font-size: 0.8rem; color: #6b7280; margin-bottom: 0.75rem;">
                Push active products to Etsy as draft listings.
            </p>
            <form method="POST" action="{{ route('admin.etsy.sync.products') }}">
                @csrf
                <button type="submit" class="admin-btn admin-btn-secondary">Sync Now</button>
            </form>
        </div>
    </div>

    {{-- Inventory --}}
    <div class="admin-card">
        <div class="admin-card-header">
            <span class="admin-card-title">Inventory</span>
        </div>
        <div style="padding: 1rem;">
            <p style="font-size: 0.8rem; color: #6b7280; margin-bottom: 0.75rem;">
                Push current stock quantities to Etsy.
            </p>
            <form method="POST" action="{{ route('admin.etsy.sync.inventory') }}">
                @csrf
                <button type="submit" class="admin-btn admin-btn-secondary">Sync Now</button>
            </form>
        </div>
    </div>

    {{-- Orders --}}
    <div class="admin-card">
        <div class="admin-card-header">
            <span class="admin-card-title">Orders</span>
        </div>
        <div style="padding: 1rem;">
            @if($ordersLastSyncedAt)
            <p style="font-size: 0.75rem; color: #9ca3af; margin-bottom: 0.5rem;">
                Last synced: {{ \Carbon\Carbon::parse($ordersLastSyncedAt)->diffForHumans() }}
            </p>
            @endif
            <p style="font-size: 0.8rem; color: #6b7280; margin-bottom: 0.75rem;">
                Pull new Etsy orders into the orders table.
            </p>
            <form method="POST" action="{{ route('admin.etsy.sync.orders') }}">
                @csrf
                <button type="submit" class="admin-btn admin-btn-secondary">Sync Now</button>
            </form>
        </div>
    </div>

    {{-- Reviews --}}
    <div class="admin-card">
        <div class="admin-card-header">
            <span class="admin-card-title">Reviews</span>
        </div>
        <div style="padding: 1rem;">
            <p style="font-size: 0.8rem; color: #6b7280; margin-bottom: 0.75rem;">
                Pull Etsy buyer reviews. Imported reviews require approval before displaying on the site.
            </p>
            <form method="POST" action="{{ route('admin.etsy.sync.reviews') }}">
                @csrf
                <button type="submit" class="admin-btn admin-btn-secondary">Sync Now</button>
            </form>
        </div>
    </div>

    {{-- Product Diff --}}
    <div class="admin-card">
        <div class="admin-card-header">
            <span class="admin-card-title">Product Diff</span>
        </div>
        <div style="padding: 1rem;">
            @if($diffReport)
            <p style="font-size: 0.75rem; color: #9ca3af; margin-bottom: 0.5rem;">
                Last run: {{ \Carbon\Carbon::parse($diffReport['generated_at'])->diffForHumans() }}
            </p>
            @endif
            <p style="font-size: 0.8rem; color: #6b7280; margin-bottom: 0.75rem;">
                Compare Etsy listing data against website products and resolve differences.
            </p>
            <form method="POST" action="{{ route('admin.etsy.diff.products') }}">
                @csrf
                <button type="submit" class="admin-btn admin-btn-secondary">Run Diff</button>
            </form>
        </div>
    </div>

</div>

@if($diffReport)
<div class="admin-card" style="max-width: 760px; margin-top: 1.5rem;">
    <div class="admin-card-header">
        <span class="admin-card-title">Diff Results</span>
        <span style="font-size: 0.75rem; color: #6b7280;">
            {{ count($diffReport['conflicts']) }} conflict(s) ·
            {{ count($diffReport['etsyOnly']) }} Etsy-only ·
            {{ count($diffReport['dbOnly']) }} website-only ·
            {{ $diffReport['matched'] }} matched
        </span>
    </div>

    <div style="padding: 1.25rem;">

        {{-- Conflicts: per-field resolution --}}
        @if(count($diffReport['conflicts']))
        <form method="POST" action="{{ route('admin.etsy.diff.resolve') }}">
            @csrf

            @foreach($diffReport['conflicts'] as $conflict)
            <div style="border: 1px solid #e5e7eb; border-radius: 0.375rem; margin-bottom: 1rem;">
                <div style="padding: 0.625rem 0.875rem; background: #f9fafb; border-bottom: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center;">
                    <a href="{{ route('admin.products.edit', $conflict['product_id']) }}" style="font-size: 0.875rem; font-weight: 600; color: #1f2937; text-decoration: none;">
                        {{ $conflict['product_name'] }}
                    </a>
                    <span style="font-size: 0.75rem; color: #9ca3af;">Listing #{{ $conflict['listing_id'] }}</span>
                </div>

                <table style="width: 100%; font-size: 0.8rem; border-collapse: collapse;">
                    <thead>
                        <tr style="color: #6b7280; text-align: left;">
                            <th style="padding: 0.5rem 0.875rem; font-weight: 600;">Field</th>
                            <th style="padding: 0.5rem 0.875rem; font-weight: 600;">Website</th>
                            <th style="padding: 0.5rem 0.875rem; font-weight: 600;">Etsy</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($conflict['differences'] as $field => $values)
                        @php
                            $format = fn ($v) => is_array($v) ? implode(', ', $v) : (string) $v;
                            $quantityLocked = $field === 'quantity' && $conflict['variant_count'] > 1;
                        @endphp
                        <tr style="border-top: 1px solid #f3f4f6;">
                            <td style="padding: 0.5rem 0.875rem; font-weight: 600; color: #374151; vertical-align: top; text-transform: capitalize;">{{ $field }}</td>
                            <td style="padding: 0.5rem 0.875rem; vertical-align: top;">
                                <label style="display: flex; gap: 0.5rem; align-items: flex-start; cursor: pointer;">
                                    <input type="radio" name="resolutions[{{ $conflict['product_id'] }}][{{ $field }}]" value="db" style="margin-top: 0.15rem;">
                                    <span style="color: #374151;">{{ \Illuminate\Support\Str::limit($format($values['db']), 160) }}</span>
                                </label>
                            </td>
                            <td style="padding: 0.5rem 0.875rem; vertical-align: top;">
                                <label style="display: flex; gap: 0.5rem; align-items: flex-start; {{ $quantityLocked ? 'cursor: not-allowed; opacity: 0.5;' : 'cursor: pointer;' }}">
                                    <input type="radio" name="resolutions[{{ $conflict['product_id'] }}][{{ $field }}]" value="etsy" style="margin-top: 0.15rem;" @if($quantityLocked) disabled @endif>
                                    <span style="color: #374151;">{{ \Illuminate\Support\Str::limit($format($values['etsy']), 160) }}</span>
                                </label>
                                @if($quantityLocked)
                                <div style="font-size: 0.7rem; color: #9ca3af; margin-top: 0.25rem;">Multi-variant product — quantity can only be kept from the website side.</div>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endforeach

            <p style="font-size: 0.75rem; color: #9ca3af; margin-bottom: 0.75rem;">
                Pick the value to keep for each field. Choosing a website value pushes the full product to Etsy, which also resolves that product's unpicked fields toward the website.
            </p>
            <button type="submit" class="admin-btn admin-btn-primary">Apply Selected</button>
        </form>
        @else
        <p style="font-size: 0.875rem; color: #6b7280;">No conflicts — all linked products match their Etsy listings.</p>
        @endif

        {{-- Etsy-only listings (informational) --}}
        @if(count($diffReport['etsyOnly']))
        <details style="margin-top: 1.25rem;">
            <summary style="font-size: 0.8rem; font-weight: 600; color: #374151; cursor: pointer;">
                Etsy-only listings ({{ count($diffReport['etsyOnly']) }}) — no linked website product
            </summary>
            <ul style="font-size: 0.8rem; color: #6b7280; margin: 0.5rem 0 0 1.25rem; list-style: disc;">
                @foreach($diffReport['etsyOnly'] as $item)
                <li>[{{ $item['listing_id'] }}] {{ $item['title'] }} ({{ $item['state'] }}@if($item['price'] !== null) — ${{ number_format($item['price'], 2) }}@endif)</li>
                @endforeach
            </ul>
        </details>
        @endif

        {{-- Website-only products (informational) --}}
        @if(count($diffReport['dbOnly']))
        <details style="margin-top: 0.75rem;">
            <summary style="font-size: 0.8rem; font-weight: 600; color: #374151; cursor: pointer;">
                Website-only products ({{ count($diffReport['dbOnly']) }}) — linked listing missing on Etsy
            </summary>
            <ul style="font-size: 0.8rem; color: #6b7280; margin: 0.5rem 0 0 1.25rem; list-style: disc;">
                @foreach($diffReport['dbOnly'] as $item)
                <li><a href="{{ route('admin.products.edit', $item['product_id']) }}" style="color: #374151;">{{ $item['name'] }}</a> (etsy_listing_id: {{ $item['etsy_listing_id'] }})</li>
                @endforeach
            </ul>
        </details>
        @endif

    </div>
</div>
@endif
@endif

@endsection
