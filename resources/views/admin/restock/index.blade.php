@extends('layouts.admin')

@section('page-title', 'Restock Requests')

@section('content')

@if(session('success'))
<div style="background: #f0fdf4; border: 1px solid #86efac; border-radius: 0.25rem; padding: 0.75rem 1rem; margin-bottom: 1.25rem; font-size: 0.875rem; color: #166534;">
    {{ session('success') }}
</div>
@endif

<div class="admin-card" style="padding: 0;">
    <div style="overflow-x: auto;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Product / Variant</th>
                    <th>Email</th>
                    <th>Requested</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($requests as $restockRequest)
                <tr>
                    <td>
                        @if($restockRequest->variant?->product)
                        <a href="{{ route('admin.products.edit', $restockRequest->variant->product) }}"
                           style="color: #2C4C3B; font-weight: 500; text-decoration: none;">
                            {{ $restockRequest->variant->product->name }}
                        </a>
                        @else
                        <span style="color: #9ca3af;">—</span>
                        @endif
                        @if($restockRequest->variant)
                        <div style="font-size: 0.75rem; color: #6b7280; margin-top: 0.125rem;">
                            {{ $restockRequest->variant->label }}
                        </div>
                        @endif
                    </td>
                    <td style="font-size: 0.875rem;">{{ $restockRequest->email }}</td>
                    <td style="font-size: 0.8125rem; color: #6b7280; white-space: nowrap;">
                        {{ $restockRequest->created_at->format('M j, Y') }}
                    </td>
                    <td>
                        @if($restockRequest->variant)
                        <form method="POST" action="{{ route('admin.restock.notify', $restockRequest->variant) }}">
                            @csrf
                            <button type="submit" class="admin-btn admin-btn-outline" style="font-size: 0.75rem; padding: 0.25rem 0.75rem; white-space: nowrap;">
                                Notify All for Variant
                            </button>
                        </form>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" style="text-align: center; color: #9ca3af; padding: 2.5rem;">
                        No pending restock requests.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($requests->hasPages())
    <div style="padding: 1rem 1.5rem; border-top: 1px solid #f3f4f6;">
        {{ $requests->links() }}
    </div>
    @endif
</div>

@endsection
