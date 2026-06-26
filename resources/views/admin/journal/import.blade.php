@extends('layouts.admin')

@section('page-title', 'Import Blog Drafts')

@section('content')

<div>
    <div style="margin-bottom: 1rem;">
        <a href="{{ route('admin.journal.index') }}" style="font-size: 0.8125rem; color: #6b7280;">
            &larr; Back to Journal
        </a>
    </div>

    @if(session('success'))
    <div style="background: #dcfce7; border: 1px solid #86efac; padding: 0.875rem 1rem; margin-bottom: 1.25rem; border-radius: 0.25rem; font-size: 0.875rem; color: #166534;">
        {{ session('success') }}
    </div>
    @endif

    <div class="admin-card">
        <div class="admin-card-header">
            <span class="admin-card-title">Drafts in .claude/blog/posts/</span>
        </div>

        <p style="font-size: 0.8125rem; color: #6b7280; margin-bottom: 1.25rem;">
            Importing creates a <strong>draft</strong> journal post from the file's frontmatter and body.
            Re-importing an already-imported slug will update the existing post.
            Featured images must be uploaded separately via the post editor.
        </p>

        @if($drafts->isEmpty())
        <div style="text-align: center; color: #9ca3af; padding: 3rem;">
            No draft files found. Write a post with <code>/blog write</code> and commit it to git first.
        </div>
        @else
        <div style="overflow-x: auto;">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>File</th>
                        <th>Format</th>
                        <th>Last Modified</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($drafts as $draft)
                    <tr>
                        <td>
                            <div style="font-weight: 600; font-size: 0.9375rem; font-family: monospace;">{{ $draft['filename'] }}</div>
                            <div style="font-size: 0.75rem; color: #6b7280; margin-top: 0.125rem;">slug: {{ $draft['slug'] }}</div>
                        </td>
                        <td>
                            <span style="font-family: monospace; font-size: 0.8125rem; background: #f3f4f6; padding: 0.125rem 0.375rem; border-radius: 0.25rem;">
                                .{{ $draft['extension'] }}
                            </span>
                        </td>
                        <td style="font-size: 0.8125rem; color: #6b7280; white-space: nowrap;">
                            {{ $draft['modified_at']->format('M j, Y g:ia') }}
                        </td>
                        <td>
                            @if($draft['imported'])
                                <span class="admin-badge-success">Imported</span>
                            @else
                                <span class="admin-badge-neutral">Pending</span>
                            @endif
                        </td>
                        <td>
                            <form method="POST" action="{{ route('admin.journal.import.file', $draft['filename']) }}">
                                @csrf
                                <button type="submit" class="admin-btn admin-btn-outline" style="font-size: 0.75rem; padding: 0.25rem 0.625rem;">
                                    {{ $draft['imported'] ? 'Re-import' : 'Import' }}
                                </button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</div>

@endsection
