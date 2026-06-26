@extends('layouts.admin')

@section('page-title', 'Edit Post')

@section('content')

<div>

    <div style="margin-bottom: 1rem;">
        <a href="{{ route('admin.journal.index') }}" style="font-size: 0.8125rem; color: #6b7280;">
            &larr; Back to Journal
        </a>
    </div>

    <div class="admin-card">
        <div class="admin-card-header">
            <span class="admin-card-title">{{ $post->title }}</span>
            <span style="font-size: 0.8125rem; color: #6b7280;">Last updated {{ $post->updated_at->diffForHumans() }}</span>
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

        <form method="POST" action="{{ route('admin.journal.update', $post) }}" enctype="multipart/form-data"
              x-data="{ publish() { document.getElementById('status').value = 'published'; this.$el.submit(); } }">
            @csrf
            @method('PUT')

            @include('admin.journal.form')

            <div style="display: flex; align-items: center; justify-content: space-between; padding-top: 0.75rem; border-top: 1px solid #f3f4f6; flex-wrap: wrap; gap: 0.75rem;">
                <div style="display: flex; gap: 0.75rem;">
                    <button type="submit" class="admin-btn admin-btn-primary">Save Changes</button>
                    @if($post->status === 'draft')
                    <button type="button" @click="publish()" class="admin-btn" style="background-color: #2C4C3B; color: #fff;">
                        Publish Now
                    </button>
                    @endif
                    <a href="{{ route('admin.journal.index') }}" class="admin-btn admin-btn-outline">Cancel</a>
                </div>
                <a href="{{ route('admin.journal.preview', $post) }}" target="_blank" class="admin-btn admin-btn-outline" style="font-size: 0.8125rem;">
                    Preview &nearr;
                </a>
            </div>
        </form>
    </div>

</div>

@endsection
