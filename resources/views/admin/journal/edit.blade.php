@extends('layouts.admin')

@section('page-title', 'Edit Post')

@section('content')

<div style="max-width: 860px;">

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

        <form method="POST" action="{{ route('admin.journal.update', $post) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            @include('admin.journal.form')

            <div style="display: flex; gap: 0.75rem; padding-top: 0.5rem; border-top: 1px solid #f3f4f6;">
                <button type="submit" class="admin-btn admin-btn-primary">Save Changes</button>
                <a href="{{ route('admin.journal.index') }}" class="admin-btn admin-btn-outline">Cancel</a>
            </div>
        </form>
    </div>

</div>

@endsection
