@extends('layouts.admin')

@section('page-title', 'Customers')

@section('content')

{{-- Search --}}
<div class="admin-card" style="margin-bottom: 1.5rem; padding: 1rem 1.5rem;">
    <form method="GET" action="{{ route('admin.customers.index') }}" style="display: flex; gap: 0.75rem; align-items: flex-end; flex-wrap: wrap;">
        <div style="flex: 1; min-width: 220px;">
            <label class="admin-label" for="search">Search</label>
            <input
                type="text"
                id="search"
                name="search"
                class="admin-input"
                placeholder="Name or email&hellip;"
                value="{{ request('search') }}"
            >
        </div>
        <div style="display: flex; gap: 0.5rem; padding-bottom: 0.125rem;">
            <button type="submit" class="admin-btn admin-btn-primary">Search</button>
            @if(request('search'))
                <a href="{{ route('admin.customers.index') }}" class="admin-btn admin-btn-outline">Clear</a>
            @endif
        </div>
        <div style="margin-left: auto; padding-bottom: 0.125rem;">
            <span style="font-size: 0.8125rem; color: #6b7280;">{{ $customers->total() }} {{ Str::plural('customer', $customers->total()) }}</span>
        </div>
    </form>
</div>

<div class="admin-card" style="padding: 0;">
    <div style="overflow-x: auto;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Customer</th>
                    <th>Email</th>
                    <th>Joined</th>
                    <th style="text-align: right;">Orders</th>
                    <th style="text-align: right;">Total Spent</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($customers as $customer)
                <tr>
                    <td>
                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                            <div style="width: 36px; height: 36px; border-radius: 50%; background-color: #2C4C3B; display: flex; align-items: center; justify-content: center; color: #F4F1EA; font-size: 0.75rem; font-weight: 700; flex-shrink: 0; font-family: 'Montserrat', sans-serif;">
                                {{ strtoupper(substr($customer->name, 0, 1)) }}{{ strtoupper(substr(strstr($customer->name, ' '), 1, 1)) }}
                            </div>
                            <div>
                                <div style="font-weight: 600; font-size: 0.875rem;">{{ $customer->name }}</div>
                            </div>
                        </div>
                    </td>
                    <td style="color: #6b7280; font-size: 0.875rem;">{{ $customer->email }}</td>
                    <td style="color: #6b7280; font-size: 0.8125rem; white-space: nowrap;">
                        {{ $customer->created_at->format('M j, Y') }}
                    </td>
                    <td style="text-align: right; font-size: 0.875rem;">
                        {{ $customer->orders_count ?? 0 }}
                    </td>
                    <td style="text-align: right; font-weight: 600; font-size: 0.875rem;">
                        ${{ number_format($customer->total_spent ?? 0, 2) }}
                    </td>
                    <td>
                        <a href="{{ route('admin.customers.show', $customer) }}" class="admin-btn admin-btn-outline" style="font-size: 0.75rem; padding: 0.25rem 0.625rem;">View</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" style="text-align: center; color: #9ca3af; padding: 3rem;">
                        No customers found.
                        @if(request('search'))
                            <a href="{{ route('admin.customers.index') }}" style="color: #2C4C3B; margin-left: 0.375rem;">Clear search</a>
                        @endif
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($customers->hasPages())
    <div style="padding: 1rem 1.5rem; border-top: 1px solid #f3f4f6;">
        {{ $customers->appends(request()->query())->links() }}
    </div>
    @endif
</div>

@endsection
