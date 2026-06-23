@extends('layouts.admin')

@section('page-title', 'New Tag')

@section('content')

<div style="max-width: 480px;">

    <div style="margin-bottom: 1rem;">
        <a href="{{ route('admin.tags.index') }}" style="font-size: 0.8125rem; color: #6b7280;">
            &larr; Back to Tags
        </a>
    </div>

    <div class="admin-card">
        <div class="admin-card-header">
            <span class="admin-card-title">New Tag</span>
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

        <form method="POST" action="{{ route('admin.tags.store') }}"
              x-data="{ name: '', slug: '', slugManual: false }">
            @csrf

            <div style="margin-bottom: 1rem;">
                <label class="admin-label" for="name">Name <span style="color: #ba1a1a;">*</span></label>
                <input
                    type="text"
                    id="name"
                    name="name"
                    class="admin-input"
                    x-model="name"
                    @input="if (!slugManual) { slug = name.toLowerCase().trim().replace(/[^a-z0-9\s-]/g, '').replace(/\s+/g, '-').replace(/-+/g, '-') }"
                    value="{{ old('name') }}"
                    placeholder="e.g. White Oak"
                    required
                >
            </div>

            <div style="margin-bottom: 1rem;">
                <label class="admin-label" for="slug">Slug <span style="color: #ba1a1a;">*</span></label>
                <input
                    type="text"
                    id="slug"
                    name="slug"
                    class="admin-input"
                    x-model="slug"
                    @focus="slugManual = true"
                    value="{{ old('slug') }}"
                    placeholder="white-oak"
                    required
                >
            </div>

            <div style="margin-bottom: 1.5rem;">
                <label class="admin-label" for="type">Type</label>
                <select id="type" name="type" class="admin-input">
                    <option value="general" @selected(old('type') === 'general')>General</option>
                    <option value="wood_species" @selected(old('type') === 'wood_species')>Wood Species</option>
                    <option value="style" @selected(old('type') === 'style')>Style</option>
                </select>
            </div>

            <div style="display: flex; gap: 0.75rem; padding-top: 0.5rem; border-top: 1px solid #f3f4f6;">
                <button type="submit" class="admin-btn admin-btn-primary">Create Tag</button>
                <a href="{{ route('admin.tags.index') }}" class="admin-btn admin-btn-outline">Cancel</a>
            </div>
        </form>
    </div>

</div>

@endsection
