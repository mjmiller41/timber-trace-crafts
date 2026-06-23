@extends('layouts.admin')

@section('page-title', 'Shipping Methods')

@section('content')

<div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem;">
    <p style="font-size: 0.875rem; color: #6b7280; margin: 0;">Configure the shipping options shown at checkout.</p>
    <a href="{{ route('admin.shipping.create') }}" class="admin-btn" style="background: #2C4C3B; color: #fff;">
        + New Shipping Method
    </a>
</div>

<div class="admin-card" style="padding: 0;">
    <div style="overflow-x: auto;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Service Code</th>
                    <th>Price Override</th>
                    <th>Sort</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($methods as $method)
                <tr>
                    <td>
                        <div style="font-weight: 500;">{{ $method->name }}</div>
                        @if($method->description)
                        <div style="font-size: 0.75rem; color: #6b7280;">{{ $method->description }}</div>
                        @endif
                    </td>
                    <td style="font-family: monospace; font-size: 0.8125rem; color: #6b7280;">{{ $method->service_code }}</td>
                    <td>
                        @if($method->price_override !== null)
                            <span style="font-weight: 500;">${{ number_format($method->price_override, 2) }}</span>
                        @elseif($method->is_free_base)
                            <span class="admin-badge-success">Free</span>
                        @else
                            <span style="color: #9ca3af;">Calculated</span>
                        @endif
                    </td>
                    <td style="color: #6b7280; text-align: center;">{{ $method->sort_order }}</td>
                    <td>
                        @if($method->active)
                            <span class="admin-badge-success">Active</span>
                        @else
                            <span class="admin-badge-neutral">Inactive</span>
                        @endif
                    </td>
                    <td>
                        <div style="display: flex; gap: 0.375rem; justify-content: flex-end;">
                            <a href="{{ route('admin.shipping.edit', $method) }}"
                               class="admin-btn admin-btn-outline" style="font-size: 0.75rem; padding: 0.25rem 0.625rem;">
                                Edit
                            </a>
                            <form method="POST" action="{{ route('admin.shipping.destroy', $method) }}"
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
                    <td colspan="6" style="text-align: center; color: #9ca3af; padding: 2.5rem;">
                        No shipping methods configured.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
