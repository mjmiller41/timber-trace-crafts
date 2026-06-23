@extends('layouts.admin')

@section('page-title', 'Categories & Tags')

@section('content')

<div x-data="{ activeTab: 'categories' }">

    {{-- Tabs --}}
    <div style="display: flex; gap: 0; border-bottom: 2px solid #e5e7eb; margin-bottom: 1.5rem;">
        <button
            type="button"
            @click="activeTab = 'categories'"
            :class="activeTab === 'categories'
                ? 'border-b-2 border-forest-green text-charcoal font-semibold'
                : 'text-gray-500'"
            style="padding: 0.75rem 1.5rem; background: none; border: none; cursor: pointer; font-size: 0.9375rem; font-family: 'Montserrat', sans-serif; font-weight: 500; border-bottom: 3px solid transparent; margin-bottom: -2px; transition: color 0.15s;"
            :style="activeTab === 'categories'
                ? 'border-bottom-color: #2C4C3B; color: #333;'
                : 'border-bottom-color: transparent; color: #6b7280;'"
        >
            Categories
        </button>
        <button
            type="button"
            @click="activeTab = 'tags'"
            style="padding: 0.75rem 1.5rem; background: none; border: none; cursor: pointer; font-size: 0.9375rem; font-family: 'Montserrat', sans-serif; font-weight: 500; border-bottom: 3px solid transparent; margin-bottom: -2px; transition: color 0.15s;"
            :style="activeTab === 'tags'
                ? 'border-bottom-color: #2C4C3B; color: #333;'
                : 'border-bottom-color: transparent; color: #6b7280;'"
        >
            Tags
        </button>
    </div>

    {{-- CATEGORIES TAB --}}
    <div x-show="activeTab === 'categories'" x-cloak>

        {{-- New Category Toggle --}}
        <div x-data="{ showForm: false }" style="margin-bottom: 1.5rem;">

            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
                <h2 style="font-family: 'Playfair Display', serif; font-size: 1.125rem; font-weight: 300; color: #333;">Categories</h2>
                <button
                    type="button"
                    @click="showForm = !showForm"
                    class="admin-btn"
                    style="background: #2C4C3B; color: #fff;"
                >
                    <span x-text="showForm ? '✕ Cancel' : '+ New Category'"></span>
                </button>
            </div>

            {{-- Inline New Category Form --}}
            <div x-show="showForm" x-cloak class="admin-card" style="margin-bottom: 1.5rem;">
                <div class="admin-card-header">
                    <span class="admin-card-title">New Category</span>
                </div>
                <form method="POST" action="{{ route('admin.categories.store') }}"
                      x-data="{ name: '', slug: '', slugManual: false }"
                      style="display: grid; grid-template-columns: 1fr 1fr 1fr 180px auto; gap: 0.875rem; align-items: end;">
                    @csrf

                    <div>
                        <label class="admin-label" for="cat_name">Name <span style="color: #ba1a1a;">*</span></label>
                        <input
                            type="text"
                            id="cat_name"
                            name="name"
                            class="admin-input"
                            x-model="name"
                            @input="if (!slugManual) { slug = name.toLowerCase().trim().replace(/[^a-z0-9\s-]/g, '').replace(/\s+/g, '-').replace(/-+/g, '-') }"
                            placeholder="e.g. Serving Boards"
                            required
                        >
                    </div>

                    <div>
                        <label class="admin-label" for="cat_slug">Slug</label>
                        <input
                            type="text"
                            id="cat_slug"
                            name="slug"
                            class="admin-input"
                            x-model="slug"
                            @focus="slugManual = true"
                            placeholder="serving-boards"
                        >
                    </div>

                    <div>
                        <label class="admin-label" for="cat_parent">Parent</label>
                        <select id="cat_parent" name="parent_id" class="admin-input">
                            <option value="">— Top Level —</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="admin-label" for="cat_sort">Sort Order</label>
                        <input type="number" id="cat_sort" name="sort_order" class="admin-input" value="0" min="0">
                    </div>

                    <div style="padding-bottom: 0.125rem;">
                        <button type="submit" class="admin-btn admin-btn-primary">Save</button>
                    </div>

                </form>
            </div>

        </div>

        {{-- Categories Table --}}
        <div class="admin-card" style="padding: 0;">
            <div style="overflow-x: auto;">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Slug</th>
                            <th>Parent</th>
                            <th style="text-align: right;">Products</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($categories as $category)
                        <tr>
                            <td>
                                <span style="{{ $category->parent_id ? 'padding-left: 1.25rem; color: #6b7280;' : 'font-weight: 600;' }}">
                                    {{ $category->parent_id ? '└ ' : '' }}{{ $category->name }}
                                </span>
                            </td>
                            <td style="font-family: monospace; font-size: 0.8125rem; color: #6b7280;">{{ $category->slug }}</td>
                            <td style="color: #6b7280; font-size: 0.875rem;">{{ $category->parent?->name ?? '—' }}</td>
                            <td style="text-align: right; color: #6b7280;">{{ $category->products_count ?? $category->products->count() }}</td>
                            <td>
                                <div style="display: flex; gap: 0.375rem; justify-content: flex-end;">
                                    <a href="{{ route('admin.categories.edit', $category) }}" class="admin-btn admin-btn-outline" style="font-size: 0.75rem; padding: 0.25rem 0.625rem;">Edit</a>
                                    <form method="POST" action="{{ route('admin.categories.destroy', $category) }}"
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
                            <td colspan="5" style="text-align: center; color: #9ca3af; padding: 2.5rem;">No categories yet.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
    {{-- end categories tab --}}

    {{-- TAGS TAB --}}
    <div x-show="activeTab === 'tags'" x-cloak>

        <div x-data="{ showForm: false }" style="margin-bottom: 1.5rem;">

            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
                <h2 style="font-family: 'Playfair Display', serif; font-size: 1.125rem; font-weight: 300; color: #333;">Tags</h2>
                <button
                    type="button"
                    @click="showForm = !showForm"
                    class="admin-btn"
                    style="background: #2C4C3B; color: #fff;"
                >
                    <span x-text="showForm ? '✕ Cancel' : '+ New Tag'"></span>
                </button>
            </div>

            {{-- Inline New Tag Form --}}
            <div x-show="showForm" x-cloak class="admin-card" style="margin-bottom: 1.5rem;">
                <div class="admin-card-header">
                    <span class="admin-card-title">New Tag</span>
                </div>
                <form method="POST" action="{{ route('admin.tags.store') }}"
                      x-data="{ name: '', slug: '', slugManual: false }"
                      style="display: grid; grid-template-columns: 1fr 1fr 180px auto; gap: 0.875rem; align-items: end;">
                    @csrf

                    <div>
                        <label class="admin-label" for="tag_name">Name <span style="color: #ba1a1a;">*</span></label>
                        <input
                            type="text"
                            id="tag_name"
                            name="name"
                            class="admin-input"
                            x-model="name"
                            @input="if (!slugManual) { slug = name.toLowerCase().trim().replace(/[^a-z0-9\s-]/g, '').replace(/\s+/g, '-').replace(/-+/g, '-') }"
                            placeholder="e.g. White Oak"
                            required
                        >
                    </div>

                    <div>
                        <label class="admin-label" for="tag_slug">Slug</label>
                        <input
                            type="text"
                            id="tag_slug"
                            name="slug"
                            class="admin-input"
                            x-model="slug"
                            @focus="slugManual = true"
                            placeholder="white-oak"
                        >
                    </div>

                    <div>
                        <label class="admin-label" for="tag_type">Type</label>
                        <select id="tag_type" name="type" class="admin-input">
                            <option value="general">General</option>
                            <option value="wood_species">Wood Species</option>
                            <option value="style">Style</option>
                        </select>
                    </div>

                    <div style="padding-bottom: 0.125rem;">
                        <button type="submit" class="admin-btn admin-btn-primary">Save</button>
                    </div>

                </form>
            </div>

        </div>

        {{-- Tags Table --}}
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
                            <td style="text-align: right; color: #6b7280;">{{ $tag->products_count ?? $tag->products->count() }}</td>
                            <td>
                                <div style="display: flex; gap: 0.375rem; justify-content: flex-end;">
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
        </div>

    </div>
    {{-- end tags tab --}}

</div>

@endsection
