@extends('layouts.admin')

@section('page-title', 'Coupons')

@section('content')

<div class="admin-card" style="margin-bottom: 1.5rem; padding: 1rem 1.5rem;">
    <div style="display: flex; align-items: center; justify-content: space-between;">
        <span style="font-size: 0.875rem; color: #6b7280;">{{ $coupons->total() }} {{ Str::plural('coupon', $coupons->total()) }}</span>
        <a href="{{ route('admin.coupons.create') }}" class="admin-btn" style="background-color: #2C4C3B; color: #fff;">
            + New Coupon
        </a>
    </div>
</div>

<div class="admin-card" style="padding: 0;">
    <div style="overflow-x: auto;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Type</th>
                    <th>Value</th>
                    <th>Applies To</th>
                    <th>Min Order</th>
                    <th>Uses</th>
                    <th>Expires</th>
                    <th>Active</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($coupons as $coupon)
                <tr>
                    <td>
                        <span style="font-family: monospace; font-weight: 600; font-size: 0.9375rem; color: #1E3529;">{{ $coupon->code }}</span>
                        @if($coupon->description)
                            <div style="font-size: 0.75rem; color: #6b7280; margin-top: 0.125rem;">{{ $coupon->description }}</div>
                        @endif
                    </td>
                    <td style="color: #6b7280; font-size: 0.8125rem;">
                        {{ $coupon->type === 'percent' ? 'Percent Off' : 'Fixed Amount' }}
                    </td>
                    <td style="font-weight: 600;">
                        @if($coupon->type === 'percent')
                            {{ number_format($coupon->value, 0) }}%
                        @else
                            ${{ number_format($coupon->value, 2) }}
                        @endif
                    </td>
                    <td style="font-size: 0.8125rem;">
                        @if($coupon->applies_to === 'category' && $coupon->category)
                            Category: {{ $coupon->category->name }}
                        @elseif($coupon->applies_to === 'product' && $coupon->product)
                            Product: {{ $coupon->product->name }}
                        @else
                            All Products
                        @endif
                    </td>
                    <td style="font-size: 0.8125rem; color: #6b7280;">
                        {{ $coupon->min_order_amount ? '$' . number_format($coupon->min_order_amount, 2) : '—' }}
                    </td>
                    <td style="font-size: 0.8125rem;">
                        {{ $coupon->used_count }}
                        @if($coupon->max_uses)
                            / {{ $coupon->max_uses }}
                        @else
                            / <span style="color: #6b7280;">unlimited</span>
                        @endif
                    </td>
                    <td style="font-size: 0.8125rem; color: #6b7280; white-space: nowrap;">
                        {{ $coupon->expires_at ? $coupon->expires_at->format('M j, Y') : '—' }}
                        @if($coupon->expires_at && $coupon->expires_at->isPast())
                            <span class="admin-badge-error" style="font-size: 0.6875rem; margin-left: 0.25rem;">Expired</span>
                        @endif
                    </td>
                    <td>
                        <form method="POST" action="{{ route('admin.coupons.update', $coupon) }}" style="display: inline;">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="code" value="{{ $coupon->code }}">
                            <input type="hidden" name="type" value="{{ $coupon->type }}">
                            <input type="hidden" name="value" value="{{ $coupon->value }}">
                            <input type="hidden" name="active" value="{{ $coupon->active ? '0' : '1' }}">
                            <button type="submit" style="background: none; border: none; cursor: pointer; padding: 0;">
                                @if($coupon->active)
                                    <span class="admin-badge-success" title="Click to deactivate" style="cursor: pointer;">Active</span>
                                @else
                                    <span class="admin-badge-neutral" title="Click to activate" style="cursor: pointer;">Inactive</span>
                                @endif
                            </button>
                        </form>
                    </td>
                    <td>
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <a href="{{ route('admin.coupons.edit', $coupon) }}" class="admin-btn admin-btn-outline" style="font-size: 0.75rem; padding: 0.25rem 0.625rem;">Edit</a>
                            <form method="POST" action="{{ route('admin.coupons.destroy', $coupon) }}"
                                  @submit.prevent="$dispatch('confirm-delete', {form: $el})">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="admin-btn admin-btn-danger" style="font-size: 0.75rem; padding: 0.25rem 0.625rem;">Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" style="text-align: center; color: #9ca3af; padding: 3rem;">
                        No coupons yet. <a href="{{ route('admin.coupons.create') }}" style="color: #2C4C3B;">Create one</a>.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($coupons->hasPages())
    <div style="padding: 1rem 1.5rem; border-top: 1px solid #f3f4f6;">
        {{ $coupons->links() }}
    </div>
    @endif
</div>

@endsection
