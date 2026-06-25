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

</div>
@endif

@endsection
