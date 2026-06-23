@extends('layouts.admin')

@section('page-title', 'Reviews')

@section('content')

{{-- Status Filter Tabs --}}
<div style="display: flex; gap: 0; border-bottom: 2px solid #e5e7eb; margin-bottom: 1.5rem;">
    @php
        $tabs = [
            ''         => 'All',
            'pending'  => 'Pending',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
        ];
        $activeStatus = request('status', '');
    @endphp
    @foreach($tabs as $value => $label)
    <a
        href="{{ route('admin.reviews.index', array_filter(['status' => $value])) }}"
        style="padding: 0.625rem 1.25rem; font-size: 0.8125rem; font-weight: 600; text-decoration: none; border-bottom: 2px solid {{ $activeStatus === $value ? '#2C4C3B' : 'transparent' }}; margin-bottom: -2px; color: {{ $activeStatus === $value ? '#2C4C3B' : '#6b7280' }}; transition: color 0.15s;"
    >
        {{ $label }}
    </a>
    @endforeach

    @if($activeStatus === 'pending' && $reviews->total() > 0)
    <form method="POST" action="{{ route('admin.reviews.index') }}" style="margin-left: auto; display: flex; align-items: center;" id="bulk-approve-form">
        @csrf
        @method('PATCH')
        <input type="hidden" name="bulk_action" value="approved">
        <button type="submit" class="admin-btn" style="font-size: 0.75rem; background-color: #2C4C3B; color: #fff;">
            Approve All Pending
        </button>
    </form>
    @endif
</div>

<div class="admin-card" style="padding: 0;">
    <div style="overflow-x: auto;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Reviewer</th>
                    <th>Rating</th>
                    <th>Title / Excerpt</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($reviews as $review)
                <tr>
                    <td style="font-size: 0.8125rem; max-width: 160px;">
                        @if($review->product)
                        <a href="{{ route('admin.products.edit', $review->product) }}" style="color: #2C4C3B; font-weight: 500;">
                            {{ Str::limit($review->product->name, 40) }}
                        </a>
                        @else
                        <span style="color: #9ca3af;">—</span>
                        @endif
                    </td>
                    <td>
                        <div style="font-size: 0.875rem; font-weight: 500;">
                            {{ $review->user?->name ?? $review->guest_name ?? 'Guest' }}
                        </div>
                        @if($review->user)
                        <div style="font-size: 0.75rem; color: #6b7280;">{{ $review->user->email }}</div>
                        @elseif($review->guest_email)
                        <div style="font-size: 0.75rem; color: #6b7280;">{{ $review->guest_email }}</div>
                        @endif
                    </td>
                    <td style="white-space: nowrap;">
                        <span style="color: #F59E0B; font-size: 0.9375rem;">
                            @for($i = 1; $i <= 5; $i++){{ $i <= $review->rating ? '★' : '☆' }}@endfor
                        </span>
                        <span style="font-size: 0.75rem; color: #6b7280; margin-left: 0.25rem;">({{ $review->rating }}/5)</span>
                    </td>
                    <td style="max-width: 240px;">
                        @if($review->title)
                        <div style="font-size: 0.8125rem; font-weight: 600; margin-bottom: 0.125rem;">{{ $review->title }}</div>
                        @endif
                        <div style="font-size: 0.75rem; color: #6b7280;">{{ Str::limit($review->body, 100) }}</div>
                    </td>
                    <td>
                        @php
                            $badge = match($review->status) {
                                'approved' => 'admin-badge-success',
                                'rejected' => 'admin-badge-error',
                                default    => 'admin-badge-warning',
                            };
                        @endphp
                        <span class="{{ $badge }}">{{ ucfirst($review->status) }}</span>
                    </td>
                    <td style="font-size: 0.8125rem; color: #6b7280; white-space: nowrap;">
                        {{ $review->created_at->format('M j, Y') }}
                    </td>
                    <td>
                        <div style="display: flex; align-items: center; gap: 0.375rem; flex-wrap: nowrap;">
                            @if($review->status !== 'approved')
                            <form method="POST" action="{{ route('admin.reviews.status', $review) }}" style="display: inline;">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="status" value="approved">
                                <button type="submit" class="admin-btn admin-btn-outline" style="font-size: 0.6875rem; padding: 0.2rem 0.5rem; color: #166534; border-color: #bbf7d0;">Approve</button>
                            </form>
                            @endif

                            @if($review->status !== 'rejected')
                            <form method="POST" action="{{ route('admin.reviews.status', $review) }}" style="display: inline;">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="status" value="rejected">
                                <button type="submit" class="admin-btn admin-btn-outline" style="font-size: 0.6875rem; padding: 0.2rem 0.5rem; color: #991b1b; border-color: #fca5a5;">Reject</button>
                            </form>
                            @endif

                            <form method="POST" action="{{ route('admin.reviews.status', $review) }}"
                                  @submit.prevent="$dispatch('confirm-delete', {form: $el})">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="admin-btn admin-btn-danger" style="font-size: 0.6875rem; padding: 0.2rem 0.5rem;">Del</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" style="text-align: center; color: #9ca3af; padding: 3rem;">
                        No reviews found
                        @if($activeStatus)
                            with status "{{ $activeStatus }}".
                            <a href="{{ route('admin.reviews.index') }}" style="color: #2C4C3B; margin-left: 0.375rem;">View all</a>
                        @else
                            .
                        @endif
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($reviews->hasPages())
    <div style="padding: 1rem 1.5rem; border-top: 1px solid #f3f4f6;">
        {{ $reviews->appends(request()->query())->links() }}
    </div>
    @endif
</div>

@endsection
