@extends('layouts.admin')

@section('page-title', 'Tags')

@section('content')

<div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem;">
    <div>
        <a href="{{ route('admin.categories.index') }}" style="font-size: 0.8125rem; color: #6b7280;">
            &larr; Categories &amp; Tags
        </a>
    </div>
    <a href="{{ route('admin.tags.create') }}" class="admin-btn" style="background: #2C4C3B; color: #fff;">
        + New Tag
    </a>
</div>

<div class="admin-card" style="padding: 0;">
    <div style="overflow-x: auto;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Slug</th>
                    <th>Type</th>
                    <th style="text-align: right;">Products</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($tags as $tag)
                <tr>
                    <td style="font-weight: 500;">{{ $tag->name }}</td>
                    <td style="font-family: monospace; font-size: 0.8125rem; color: #6b7280;">{{ $tag->slug }}</td>
                    <td>
                        @php
                            $typeBadge = match($tag->type) {
                                'wood_species' => 'admin-badge-warning',
                                'style'        => 'admin-badge-info',
                                default        => 'admin-badge-neutral',
                            };
                            $typeLabel = match($tag->type) {
                                'wood_species' => 'Wood Species',
                                'style'        => 'Style',
                                default        => 'General',
                            };
                        @endphp
                        <span class="{{ $typeBadge }}">{{ $typeLabel }}</span>
                    </td>
                    <td style="text-align: right; color: #6b7280;">{{ $tag->products_count }}</td>
                    <td>
                        <div style="display: flex; gap: 0.375rem; justify-content: flex-end;">
                            <a href="{{ route('admin.tags.edit', $tag) }}" class="admin-btn admin-btn-outline" style="font-size: 0.75rem; padding: 0.25rem 0.625rem;">Edit</a>
                            <form method="POST" action="{{ route('admin.tags.destroy', $tag) }}"
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
                    <td colspan="5" style="text-align: center; color: #9ca3af; padding: 2.5rem;">No tags yet.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($tags->hasPages())
    <div style="padding: 1rem 1.5rem; border-top: 1px solid #f3f4f6;">
        {{ $tags->links() }}
    </div>
    @endif
</div>

@endsection
