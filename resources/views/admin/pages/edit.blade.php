@extends('layouts.admin')

@section('page-title', 'Edit Page')

@section('content')

<div>

    <div style="margin-bottom: 1rem;">
        <a href="{{ route('admin.pages.index') }}" style="font-size: 0.8125rem; color: #6b7280;">
            &larr; Back to Pages
        </a>
    </div>

    <div class="admin-card">
        <div class="admin-card-header">
            <span class="admin-card-title">{{ $page->title }}</span>
            @if($page->updated_at)
            <span style="font-size: 0.8125rem; color: #6b7280;">Last updated {{ $page->updated_at->diffForHumans() }}</span>
            @endif
        </div>

        @if($errors->any())
        <div style="background: #fee2e2; border: 1px solid #fca5a5; padding: 0.875rem 1rem; margin-bottom: 1.25rem; border-radius: 0.25rem;">
            <p style="font-size: 0.8125rem; font-weight: 600; color: #991b1b; margin-bottom: 0.375rem;">Please fix the following errors:</p>
            <ul style="margin: 0; padding-left: 1.25rem; font-size: 0.8125rem; color: #991b1b;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <form method="POST" action="{{ route('admin.pages.update', $page) }}">
            @csrf
            @method('PUT')

            <div style="margin-bottom: 1rem;">
                <label class="admin-label" for="title">Title <span style="color: #ba1a1a;">*</span></label>
                <input
                    type="text"
                    id="title"
                    name="title"
                    class="admin-input"
                    value="{{ old('title', $page->title) }}"
                    required
                >
            </div>

            <div style="margin-bottom: 1rem;">
                <label class="admin-label" for="body">Content <span style="color: #ba1a1a;">*</span></label>
                <textarea id="body" name="body" required>{{ old('body', $page->body) }}</textarea>
                @include('components.admin.zencomposer', ['targetId' => 'body', 'height' => '600px'])
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem; padding-top: 1rem; border-top: 1px solid #f3f4f6;">
                <div>
                    <label class="admin-label" for="meta_title">Meta Title</label>
                    <input
                        type="text"
                        id="meta_title"
                        name="meta_title"
                        class="admin-input"
                        value="{{ old('meta_title', $page->meta_title) }}"
                        placeholder="Defaults to page title"
                    >
                </div>

                <div>
                    <label class="admin-label" for="meta_description">Meta Description</label>
                    <input
                        type="text"
                        id="meta_description"
                        name="meta_description"
                        class="admin-input"
                        value="{{ old('meta_description', $page->meta_description) }}"
                        placeholder="Optional, max 500 chars"
                        maxlength="500"
                    >
                </div>
            </div>

            <div style="display: flex; gap: 0.75rem; padding-top: 0.5rem; border-top: 1px solid #f3f4f6;">
                <button type="submit" class="admin-btn admin-btn-primary">Save Page</button>
                <a href="{{ route('page.show', $page->slug) }}" target="_blank"
                   class="admin-btn admin-btn-outline">View Page &#x2197;</a>
                <a href="{{ route('admin.pages.index') }}" class="admin-btn admin-btn-outline">Cancel</a>
            </div>
        </form>
    </div>

</div>

@endsection
