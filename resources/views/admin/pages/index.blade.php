@extends('layouts.admin')

@section('page-title', 'Pages')

@section('content')

<div class="admin-card" style="padding: 0;">
    <div style="overflow-x: auto;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Slug</th>
                    <th>Last Updated</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($pages as $page)
                <tr>
                    <td style="font-weight: 500;">{{ $page->title }}</td>
                    <td style="font-family: monospace; font-size: 0.8125rem; color: #6b7280;">{{ $page->slug }}</td>
                    <td style="font-size: 0.8125rem; color: #6b7280;">
                        {{ $page->updated_at?->format('M j, Y') ?? '—' }}
                    </td>
                    <td>
                        <div style="display: flex; gap: 0.375rem; justify-content: flex-end;">
                            <a href="{{ route('page.show', $page->slug) }}" target="_blank"
                               class="admin-btn admin-btn-outline" style="font-size: 0.75rem; padding: 0.25rem 0.625rem;">
                                View &#x2197;
                            </a>
                            <a href="{{ route('admin.pages.edit', $page) }}"
                               class="admin-btn admin-btn-outline" style="font-size: 0.75rem; padding: 0.25rem 0.625rem;">
                                Edit
                            </a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" style="text-align: center; color: #9ca3af; padding: 2.5rem;">No pages found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
