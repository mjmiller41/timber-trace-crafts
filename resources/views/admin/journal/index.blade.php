@extends('layouts.admin')

@section('page-title', 'Journal Posts')

@section('content')

<div class="admin-card" style="margin-bottom: 1.5rem; padding: 1rem 1.5rem;">
    <div style="display: flex; align-items: center; justify-content: space-between;">
        <span style="font-size: 0.875rem; color: #6b7280;">{{ $posts->total() }} {{ Str::plural('post', $posts->total()) }}</span>
        <div style="display: flex; gap: 0.5rem;">
            <a href="{{ route('admin.journal.import') }}" class="admin-btn admin-btn-outline">
                Import Drafts
            </a>
            <a href="{{ route('admin.journal.create') }}" class="admin-btn" style="background-color: #2C4C3B; color: #fff;">
                + New Post
            </a>
        </div>
    </div>
</div>

<div class="admin-card" style="padding: 0;">
    <div style="overflow-x: auto;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Status</th>
                    <th>Published At</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($posts as $post)
                <tr>
                    <td>
                        <div style="font-weight: 600; font-size: 0.9375rem;">{{ $post->title }}</div>
                        @if($post->excerpt)
                        <div style="font-size: 0.75rem; color: #6b7280; margin-top: 0.125rem;">{{ Str::limit($post->excerpt, 80) }}</div>
                        @endif
                    </td>
                    <td>
                        @if($post->isScheduled())
                            <span class="admin-badge-warning">Scheduled</span>
                        @elseif($post->status === 'published')
                            <span class="admin-badge-success">Published</span>
                        @else
                            <span class="admin-badge-neutral">Draft</span>
                        @endif
                    </td>
                    <td style="font-size: 0.8125rem; color: #6b7280; white-space: nowrap;">
                        {{ $post->published_at ? $post->published_at->format('M j, Y') : '—' }}
                    </td>
                    <td>
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <a href="{{ route('admin.journal.edit', $post) }}" class="admin-btn admin-btn-outline" style="font-size: 0.75rem; padding: 0.25rem 0.625rem;">Edit</a>
                            <form method="POST" action="{{ route('admin.journal.destroy', $post) }}"
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
                    <td colspan="4" style="text-align: center; color: #9ca3af; padding: 3rem;">
                        No posts yet. <a href="{{ route('admin.journal.create') }}" style="color: #2C4C3B;">Write the first one</a>.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($posts->hasPages())
    <div style="padding: 1rem 1.5rem; border-top: 1px solid #f3f4f6;">
        {{ $posts->links() }}
    </div>
    @endif
</div>

@endsection
