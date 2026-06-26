@extends('layouts.admin')

@section('page-title', 'Edit Category')

@section('content')

<div style="max-width: 640px;">

    <div style="margin-bottom: 1rem;">
        <a href="{{ route('admin.categories.index') }}" style="font-size: 0.8125rem; color: #6b7280;">
            &larr; Back to Categories &amp; Tags
        </a>
    </div>

    <div class="admin-card">
        <div class="admin-card-header">
            <span class="admin-card-title">{{ $category->name }}</span>
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

        <form method="POST" action="{{ route('admin.categories.update', $category) }}">
            @csrf
            @method('PUT')

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                <div>
                    <label class="admin-label" for="name">Name <span style="color: #ba1a1a;">*</span></label>
                    <input
                        type="text"
                        id="name"
                        name="name"
                        class="admin-input"
                        value="{{ old('name', $category->name) }}"
                        placeholder="e.g. Serving Boards"
                        required
                    >
                </div>

                <div>
                    <label class="admin-label" for="slug">Slug <span style="color: #ba1a1a;">*</span></label>
                    <input
                        type="text"
                        id="slug"
                        name="slug"
                        class="admin-input"
                        value="{{ old('slug', $category->slug) }}"
                        placeholder="serving-boards"
                        required
                    >
                </div>
            </div>

            <div style="margin-bottom: 1rem;">
                <label class="admin-label" for="parent_id">Parent Category</label>
                <select id="parent_id" name="parent_id" class="admin-input">
                    <option value="">— Top Level —</option>
                    @foreach($parents as $parent)
                        <option value="{{ $parent->id }}" @selected(old('parent_id', $category->parent_id) == $parent->id)>
                            {{ $parent->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div style="margin-bottom: 1rem;">
                <label class="admin-label" for="description">Description</label>
                <textarea id="description" name="description" class="admin-input" rows="3"
                    placeholder="Optional description">{{ old('description', $category->description) }}</textarea>
            </div>

            <div style="display: grid; grid-template-columns: 120px 1fr; gap: 1rem; margin-bottom: 1.5rem; align-items: end;">
                <div>
                    <label class="admin-label" for="sort_order">Sort Order</label>
                    <input type="number" id="sort_order" name="sort_order" class="admin-input"
                        value="{{ old('sort_order', $category->sort_order) }}" min="0">
                </div>
                <div>
                    <label class="admin-label" for="etsy_taxonomy_id">
                        Etsy Taxonomy ID
                        <span style="font-weight: 400; color: #9ca3af;">(auto-fills on products)</span>
                    </label>
                    <input type="number" id="etsy_taxonomy_id" name="etsy_taxonomy_id" class="admin-input"
                        value="{{ old('etsy_taxonomy_id', $category->etsy_taxonomy_id) }}"
                        placeholder="e.g. 1208 — see .claude/etsy_data/seller_taxonomy.json">
                </div>
            </div>

            <div style="display: flex; gap: 0.75rem; padding-top: 0.5rem; border-top: 1px solid #f3f4f6;">
                <button type="submit" class="admin-btn admin-btn-primary">Save Changes</button>
                <a href="{{ route('admin.categories.index') }}" class="admin-btn admin-btn-outline">Cancel</a>
            </div>
        </form>
    </div>

</div>

@endsection
