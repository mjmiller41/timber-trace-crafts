@php
    $isEditing = isset($product);
    $formAction = $isEditing
        ? route('admin.products.update', $product)
        : route('admin.products.store');
@endphp

{{-- Page header --}}
<div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem;">
    <div>
        <a href="{{ route('admin.products.index') }}" style="font-size: 0.8125rem; color: #8C7B6C; display: inline-flex; align-items: center; gap: 0.25rem; margin-bottom: 0.375rem;">
            &#8592; Back to Products
        </a>
        <h1 style="font-family: 'Playfair Display', serif; font-size: 1.5rem; font-weight: 300; color: #333;">
            {{ $isEditing ? 'Edit: ' . $product->name : 'New Product' }}
        </h1>
    </div>
</div>

@if($errors->any())
<div style="background: #fee2e2; border: 1px solid #fca5a5; border-radius: 0.25rem; padding: 1rem 1.25rem; margin-bottom: 1.5rem;">
    <p style="font-size: 0.875rem; font-weight: 600; color: #991b1b; margin-bottom: 0.5rem;">Please fix the following errors:</p>
    <ul style="margin: 0; padding-left: 1.25rem; font-size: 0.8125rem; color: #991b1b;">
        @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<form method="POST" action="{{ $formAction }}" id="product-form"
      x-data="productForm()"
      @submit="handleSubmit($event)">
    @csrf
    @if($isEditing)
        @method('PUT')
    @endif

    <div style="display: grid; grid-template-columns: 1fr 320px; gap: 1.5rem; align-items: start;">

        {{-- LEFT: Main Sections --}}
        <div style="display: flex; flex-direction: column; gap: 1.5rem;">

            {{-- Basic Info --}}
            <div class="admin-card">
                <div class="admin-card-header">
                    <span class="admin-card-title">Basic Info</span>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                    <div style="grid-column: span 2;">
                        <label class="admin-label" for="name">Product Name <span style="color: #ba1a1a;">*</span></label>
                        <input
                            type="text"
                            id="name"
                            name="name"
                            class="admin-input"
                            value="{{ old('name', $product->name ?? '') }}"
                            required
                            @input="syncSlug($event.target.value)"
                        >
                        @error('name') <p class="admin-error-text">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="admin-label" for="slug">Slug <span style="color: #ba1a1a;">*</span></label>
                        <input
                            type="text"
                            id="slug"
                            name="slug"
                            class="admin-input"
                            value="{{ old('slug', $product->slug ?? '') }}"
                            x-model="slug"
                            required
                        >
                        <p class="admin-hint">Auto-generated from name — editable.</p>
                        @error('slug') <p class="admin-error-text">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="admin-label" for="sku_base">SKU Base</label>
                        <input
                            type="text"
                            id="sku_base"
                            name="sku_base"
                            class="admin-input"
                            value="{{ old('sku_base', $product->sku_base ?? '') }}"
                            placeholder="e.g. WB-OAK"
                        >
                        @error('sku_base') <p class="admin-error-text">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="admin-label" for="category_id">Category</label>
                        <select id="category_id" name="category_id" class="admin-input">
                            <option value="">— No Category —</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" @selected(old('category_id', $product->category_id ?? '') == $cat->id)>
                                    {{ $cat->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('category_id') <p class="admin-error-text">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="admin-label" for="sort_order">Sort Order</label>
                        <input
                            type="number"
                            id="sort_order"
                            name="sort_order"
                            class="admin-input"
                            value="{{ old('sort_order', $product->sort_order ?? 0) }}"
                            min="0"
                        >
                    </div>
                </div>

                <div style="display: flex; gap: 1.5rem; align-items: center; padding-top: 0.875rem; border-top: 1px solid #f3f4f6;">
                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; font-size: 0.875rem; font-weight: 500; color: #333;">
                        <input
                            type="checkbox"
                            name="featured"
                            value="1"
                            @checked(old('featured', $product->featured ?? false))
                            style="width: 1rem; height: 1rem; accent-color: #2C4C3B; cursor: pointer;"
                        >
                        Featured product
                    </label>
                </div>
            </div>

            {{-- Pricing --}}
            <div class="admin-card">
                <div class="admin-card-header">
                    <span class="admin-card-title">Pricing</span>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div>
                        <label class="admin-label" for="price">Price <span style="color: #ba1a1a;">*</span></label>
                        <div style="position: relative;">
                            <span style="position: absolute; left: 0.75rem; top: 50%; transform: translateY(-50%); color: #9ca3af; font-size: 0.875rem;">$</span>
                            <input
                                type="number"
                                id="price"
                                name="price"
                                class="admin-input"
                                style="padding-left: 1.5rem;"
                                value="{{ old('price', $product->price ?? '') }}"
                                step="0.01"
                                min="0"
                                required
                                placeholder="0.00"
                            >
                        </div>
                        @error('price') <p class="admin-error-text">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="admin-label" for="sale_price">Sale Price <span style="color: #9ca3af; font-weight: 400;">(optional)</span></label>
                        <div style="position: relative;">
                            <span style="position: absolute; left: 0.75rem; top: 50%; transform: translateY(-50%); color: #9ca3af; font-size: 0.875rem;">$</span>
                            <input
                                type="number"
                                id="sale_price"
                                name="sale_price"
                                class="admin-input"
                                style="padding-left: 1.5rem;"
                                value="{{ old('sale_price', $product->sale_price ?? '') }}"
                                step="0.01"
                                min="0"
                                placeholder="0.00"
                            >
                        </div>
                        <p class="admin-hint">Leave blank if not on sale.</p>
                        @error('sale_price') <p class="admin-error-text">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            {{-- Personalization --}}
            <div class="admin-card" x-data="{ persType: '{{ old('personalization_type', $product->personalization_type ?? 'none') }}' }">
                <div class="admin-card-header">
                    <span class="admin-card-title">Personalization</span>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div>
                        <label class="admin-label" for="personalization_type">Personalization Type</label>
                        <select id="personalization_type" name="personalization_type" class="admin-input" x-model="persType">
                            <option value="none"     @selected(old('personalization_type', $product->personalization_type ?? 'none') === 'none')>None</option>
                            <option value="included" @selected(old('personalization_type', $product->personalization_type ?? '') === 'included')>Included (no extra charge)</option>
                            <option value="addon"    @selected(old('personalization_type', $product->personalization_type ?? '') === 'addon')>Add-on (extra charge)</option>
                        </select>
                    </div>

                    <div x-show="persType === 'addon'" x-cloak>
                        <label class="admin-label" for="personalization_price">Personalization Price</label>
                        <div style="position: relative;">
                            <span style="position: absolute; left: 0.75rem; top: 50%; transform: translateY(-50%); color: #9ca3af; font-size: 0.875rem;">$</span>
                            <input
                                type="number"
                                id="personalization_price"
                                name="personalization_price"
                                class="admin-input"
                                style="padding-left: 1.5rem;"
                                value="{{ old('personalization_price', $product->personalization_price ?? '') }}"
                                step="0.01"
                                min="0"
                                placeholder="0.00"
                            >
                        </div>
                    </div>

                    <div x-show="persType !== 'none'" x-cloak style="grid-column: span 2;">
                        <label class="admin-label" for="personalization_prompt">Prompt Shown to Customer</label>
                        <input
                            type="text"
                            id="personalization_prompt"
                            name="personalization_prompt"
                            class="admin-input"
                            value="{{ old('personalization_prompt', $product->personalization_prompt ?? '') }}"
                            placeholder="e.g. Enter the name to engrave…"
                        >
                    </div>

                    <div x-show="persType !== 'none'" x-cloak>
                        <label class="admin-label" for="personalization_max_chars">Max Characters</label>
                        <input
                            type="number"
                            id="personalization_max_chars"
                            name="personalization_max_chars"
                            class="admin-input"
                            value="{{ old('personalization_max_chars', $product->personalization_max_chars ?? 50) }}"
                            min="1"
                        >
                    </div>
                </div>
            </div>

            {{-- Descriptions --}}
            <div class="admin-card">
                <div class="admin-card-header">
                    <span class="admin-card-title">Descriptions</span>
                </div>

                <div style="margin-bottom: 1rem;">
                    <label class="admin-label" for="short_description">Short Description</label>
                    <textarea
                        id="short_description"
                        name="short_description"
                        class="admin-input"
                        rows="3"
                        placeholder="Brief product summary shown in shop grid (150 chars recommended)…"
                        style="resize: vertical;"
                    >{{ old('short_description', $product->short_description ?? '') }}</textarea>
                    <p class="admin-hint">Used in shop listing cards. Keep under 150 characters.</p>
                    @error('short_description') <p class="admin-error-text">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="admin-label" for="description">Full Description</label>
                    <textarea
                        id="description"
                        name="description"
                        class="admin-input"
                        rows="10"
                        placeholder="Full product description…"
                        style="resize: vertical;"
                    >{{ old('description', $product->description ?? '') }}</textarea>
                    <p class="admin-hint">WYSIWYG editor in Phase 2 — plain text/HTML for now.</p>
                    @error('description') <p class="admin-error-text">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- Variants --}}
            <div class="admin-card"
                 x-data="variantManager({{ json_encode(
                     isset($product) ? $product->variants->map(fn($v) => [
                         'id'                  => $v->id,
                         'label'               => $v->label,
                         'sku'                 => $v->sku,
                         'material_code'       => $v->material_code,
                         'stock_qty'           => $v->stock_qty,
                         'low_stock_threshold' => $v->low_stock_threshold,
                         'sort_order'          => $v->sort_order,
                     ])->values()->toArray()
                     : []
                 ) }})">
                <div class="admin-card-header">
                    <span class="admin-card-title">Variants</span>
                    <button type="button" class="admin-btn admin-btn-outline" @click="addVariant()" style="font-size: 0.75rem;">+ Add Variant</button>
                </div>

                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; font-size: 0.8125rem;">
                        <thead>
                            <tr>
                                <th style="text-align: left; padding: 0.5rem 0.75rem; font-size: 0.6875rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.08em; color: #8C7B6C; border-bottom: 1px solid #e5e7eb; white-space: nowrap;">Label</th>
                                <th style="text-align: left; padding: 0.5rem 0.75rem; font-size: 0.6875rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.08em; color: #8C7B6C; border-bottom: 1px solid #e5e7eb; white-space: nowrap;">SKU</th>
                                <th style="text-align: left; padding: 0.5rem 0.75rem; font-size: 0.6875rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.08em; color: #8C7B6C; border-bottom: 1px solid #e5e7eb; white-space: nowrap;">Material Code</th>
                                <th style="text-align: right; padding: 0.5rem 0.75rem; font-size: 0.6875rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.08em; color: #8C7B6C; border-bottom: 1px solid #e5e7eb; white-space: nowrap;">Stock</th>
                                <th style="text-align: right; padding: 0.5rem 0.75rem; font-size: 0.6875rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.08em; color: #8C7B6C; border-bottom: 1px solid #e5e7eb; white-space: nowrap;">Low Stock</th>
                                <th style="text-align: right; padding: 0.5rem 0.75rem; font-size: 0.6875rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.08em; color: #8C7B6C; border-bottom: 1px solid #e5e7eb; white-space: nowrap;">Sort</th>
                                <th style="border-bottom: 1px solid #e5e7eb; width: 2rem;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(variant, index) in variants" :key="variant._key">
                                <tr style="border-bottom: 1px solid #f3f4f6;">
                                    <td style="padding: 0.5rem 0.5rem;">
                                        <template x-if="variant.id">
                                            <input type="hidden" :name="'variants[' + index + '][id]'" :value="variant.id">
                                        </template>
                                        <input
                                            type="text"
                                            :name="'variants[' + index + '][label]'"
                                            x-model="variant.label"
                                            class="admin-input"
                                            style="min-width: 120px;"
                                            placeholder="e.g. Small Oak"
                                        >
                                    </td>
                                    <td style="padding: 0.5rem 0.5rem;">
                                        <input
                                            type="text"
                                            :name="'variants[' + index + '][sku]'"
                                            x-model="variant.sku"
                                            class="admin-input"
                                            style="min-width: 110px; font-family: monospace; font-size: 0.8125rem;"
                                            placeholder="WB-OAK-SM"
                                        >
                                    </td>
                                    <td style="padding: 0.5rem 0.5rem;">
                                        <input
                                            type="text"
                                            :name="'variants[' + index + '][material_code]'"
                                            x-model="variant.material_code"
                                            class="admin-input"
                                            style="min-width: 100px; font-family: monospace; font-size: 0.8125rem;"
                                            placeholder="OAK"
                                        >
                                    </td>
                                    <td style="padding: 0.5rem 0.5rem;">
                                        <input
                                            type="number"
                                            :name="'variants[' + index + '][stock_qty]'"
                                            x-model="variant.stock_qty"
                                            class="admin-input"
                                            style="min-width: 70px; text-align: right;"
                                            min="0"
                                            placeholder="0"
                                        >
                                    </td>
                                    <td style="padding: 0.5rem 0.5rem;">
                                        <input
                                            type="number"
                                            :name="'variants[' + index + '][low_stock_threshold]'"
                                            x-model="variant.low_stock_threshold"
                                            class="admin-input"
                                            style="min-width: 70px; text-align: right;"
                                            min="0"
                                            placeholder="5"
                                        >
                                    </td>
                                    <td style="padding: 0.5rem 0.5rem;">
                                        <input
                                            type="number"
                                            :name="'variants[' + index + '][sort_order]'"
                                            x-model="variant.sort_order"
                                            class="admin-input"
                                            style="min-width: 60px; text-align: right;"
                                            min="0"
                                            placeholder="0"
                                        >
                                    </td>
                                    <td style="padding: 0.5rem 0.25rem; text-align: center;">
                                        <button
                                            type="button"
                                            @click="removeVariant(index)"
                                            style="background: none; border: none; cursor: pointer; color: #9ca3af; font-size: 1rem; padding: 0.25rem; transition: color 0.15s;"
                                            onmouseover="this.style.color='#991b1b'"
                                            onmouseout="this.style.color='#9ca3af'"
                                            title="Remove variant"
                                        >&#x2715;</button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <div x-show="variants.length === 0" style="text-align: center; color: #9ca3af; padding: 1.5rem; font-size: 0.875rem;">
                    No variants yet. Click "Add Variant" to add one.
                </div>
            </div>

            {{-- SEO (Collapsible) --}}
            <div class="admin-card" x-data="{ open: false }">
                <button
                    type="button"
                    class="admin-card-header"
                    @click="open = !open"
                    style="width: 100%; text-align: left; cursor: pointer; margin-bottom: 0;"
                >
                    <span class="admin-card-title">SEO</span>
                    <span style="font-size: 0.75rem; color: #8C7B6C;" x-text="open ? '▲ Collapse' : '▼ Expand'"></span>
                </button>

                <div x-show="open" x-cloak style="margin-top: 1.25rem;" x-data="{ metaTitle: '{{ old('meta_title', $product->meta_title ?? '') }}', metaDesc: '{{ old('meta_description', $product->meta_description ?? '') }}' }">

                    <div style="margin-bottom: 1rem;">
                        <label class="admin-label" for="meta_title">
                            Meta Title
                            <span style="float: right; font-weight: 400; color: #9ca3af;" x-text="metaTitle.length + '/60'"></span>
                        </label>
                        <input
                            type="text"
                            id="meta_title"
                            name="meta_title"
                            class="admin-input"
                            value="{{ old('meta_title', $product->meta_title ?? '') }}"
                            maxlength="60"
                            x-model="metaTitle"
                            placeholder="Appears in browser tab and search results"
                        >
                    </div>

                    <div>
                        <label class="admin-label" for="meta_description">
                            Meta Description
                            <span style="float: right; font-weight: 400; color: #9ca3af;" x-text="metaDesc.length + '/160'"></span>
                        </label>
                        <textarea
                            id="meta_description"
                            name="meta_description"
                            class="admin-input"
                            rows="3"
                            maxlength="160"
                            x-model="metaDesc"
                            placeholder="Brief description for search engine results…"
                            style="resize: vertical;"
                        >{{ old('meta_description', $product->meta_description ?? '') }}</textarea>
                    </div>

                </div>
            </div>

        </div>
        {{-- end left column --}}

        {{-- RIGHT: Status + Submit --}}
        <div style="display: flex; flex-direction: column; gap: 1rem;">

            {{-- Status --}}
            <div class="admin-card">
                <div class="admin-card-header">
                    <span class="admin-card-title">Status</span>
                </div>
                <select name="status" id="status" class="admin-input" style="margin-bottom: 0;">
                    <option value="draft"    @selected(old('status', $product->status ?? 'draft') === 'draft')>Draft</option>
                    <option value="active"   @selected(old('status', $product->status ?? '') === 'active')>Active (visible in shop)</option>
                    <option value="archived" @selected(old('status', $product->status ?? '') === 'archived')>Archived</option>
                </select>
            </div>

            {{-- Media Note --}}
            <div class="admin-card" style="background: #f9fafb; border-style: dashed;">
                <div style="font-size: 0.8125rem; color: #6b7280; line-height: 1.6;">
                    <div style="font-weight: 600; color: #333; margin-bottom: 0.375rem;">&#x1F4F7; Media</div>
                    Media management coming in Phase 2 — upload images via the Media Library and attach to products.
                    @if($isEditing)
                        <br><br>
                        <a href="{{ route('admin.media.index') }}" style="color: #2C4C3B; font-weight: 600;">Go to Media Library →</a>
                    @endif
                </div>
            </div>

            {{-- Submit Actions --}}
            <div class="admin-card">
                <div style="display: flex; flex-direction: column; gap: 0.625rem;">
                    <button type="submit" name="_action" value="save" class="admin-btn admin-btn-primary" style="justify-content: center; width: 100%; padding: 0.625rem;">
                        Save Product
                    </button>
                    @if($isEditing)
                    <a
                        href="{{ route('admin.products.show', $product) ?? '#' }}"
                        target="_blank"
                        class="admin-btn admin-btn-outline"
                        style="justify-content: center; width: 100%; padding: 0.625rem; text-align: center;"
                    >
                        View on Site &#x2197;
                    </a>
                    @endif
                    <a href="{{ route('admin.products.index') }}" class="admin-btn admin-btn-outline" style="justify-content: center; width: 100%; padding: 0.625rem; text-align: center;">
                        Cancel
                    </a>
                </div>
            </div>

            @if($isEditing)
            {{-- Etsy Push --}}
            <div class="admin-card" style="border-color: #f59e0b;">
                <div class="admin-card-header" style="margin-bottom: 0.75rem;">
                    <span class="admin-card-title" style="font-size: 0.8125rem;">Etsy</span>
                    @if($product->etsy_listing_id)
                        <span style="font-size: 0.6875rem; color: #6b7280; font-family: monospace;">#{{ $product->etsy_listing_id }}</span>
                    @else
                        <span style="font-size: 0.6875rem; color: #9ca3af;">Not linked</span>
                    @endif
                </div>
                <form method="POST" action="{{ route('admin.etsy.push.product', $product) }}">
                    @csrf
                    <button type="submit" class="admin-btn admin-btn-outline" style="width: 100%; justify-content: center; font-size: 0.8125rem; border-color: #f59e0b; color: #92400e;"
                        onclick="return confirm({{ Illuminate\Support\Js::from('Push '.$product->name.' to Etsy?') }})">
                        {{ $product->etsy_listing_id ? '↑ Update on Etsy' : '↑ Create on Etsy' }}
                    </button>
                </form>
            </div>
            @endif

        </div>
        {{-- end right column --}}

    </div>
</form>

@push('scripts')
<script>
    function productForm() {
        return {
            slug: '{{ old('slug', $product->slug ?? '') }}',
            slugManuallyEdited: {{ isset($product) && $product->slug ? 'true' : 'false' }},

            syncSlug(name) {
                if (this.slugManuallyEdited) return;
                this.slug = name
                    .toLowerCase()
                    .trim()
                    .replace(/[^a-z0-9\s-]/g, '')
                    .replace(/[\s]+/g, '-')
                    .replace(/-+/g, '-');
            },

            handleSubmit(e) {
                // Allow normal submission — hook for future use
            },
        };
    }

    function variantManager(initialVariants) {
        return {
            variants: initialVariants.map((v, i) => ({ ...v, _key: i })),
            _nextKey: initialVariants.length,

            addVariant() {
                this.variants.push({
                    _key: this._nextKey++,
                    id: null,
                    label: '',
                    sku: '',
                    material_code: '',
                    stock_qty: 0,
                    low_stock_threshold: 5,
                    sort_order: this.variants.length,
                });
            },

            removeVariant(index) {
                this.variants.splice(index, 1);
            },
        };
    }
</script>
@endpush
