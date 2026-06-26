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
      x-data="productForm({{ Illuminate\Support\Js::from($categoryTaxonomyMap ?? []) }}, {{ Illuminate\Support\Js::from($etsyTaxonomySuggestions ?? []) }})"
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
                        <select id="category_id" name="category_id" class="admin-input"
                            @change="onCategoryChange($event.target.value)">
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

            {{-- Etsy Listing --}}
            <div class="admin-card" x-data="{ soldOnEtsy: {{ old('sold_on_etsy', $product->sold_on_etsy ?? false) ? 'true' : 'false' }} }">
                <div class="admin-card-header" style="margin-bottom: 0.75rem;">
                    <span class="admin-card-title">Etsy Listing</span>
                    @if(isset($product) && $product->etsy_listing_id)
                        <a href="https://www.etsy.com/listing/{{ $product->etsy_listing_id }}" target="_blank" style="font-size: 0.6875rem; color: #8C7B6C; text-decoration: underline;">
                            #{{ $product->etsy_listing_id }} ↗
                        </a>
                    @endif
                </div>

                {{-- Master toggle --}}
                <label style="display: flex; align-items: center; gap: 0.625rem; cursor: pointer; font-size: 0.875rem; font-weight: 600; color: #333; margin-bottom: 1rem;">
                    <input
                        type="checkbox"
                        name="sold_on_etsy"
                        value="1"
                        @checked(old('sold_on_etsy', $product->sold_on_etsy ?? false))
                        x-model="soldOnEtsy"
                        style="width: 1rem; height: 1rem; accent-color: #f59e0b; cursor: pointer;"
                    >
                    Sold on Etsy — sync this listing
                </label>

                {{-- Etsy-specific fields, visible only when sold_on_etsy is checked --}}
                <div
                    x-show="soldOnEtsy"
                    x-cloak
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 -translate-y-1"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100 translate-y-0"
                    x-transition:leave-end="opacity-0 -translate-y-1"
                    style="display: flex; flex-direction: column; gap: 1rem; border-top: 1px solid #fde68a; padding-top: 1rem;"
                >

                    {{-- Who / When / Supply --}}
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div>
                            <label class="admin-label" for="etsy_who_made">Who Made It</label>
                            <select id="etsy_who_made" name="etsy_who_made" class="admin-input">
                                <option value="i_did"         @selected(old('etsy_who_made', $product->etsy_who_made ?? 'i_did') === 'i_did')>I did</option>
                                <option value="collective"    @selected(old('etsy_who_made', $product->etsy_who_made ?? '') === 'collective')>A member of my shop</option>
                                <option value="someone_else"  @selected(old('etsy_who_made', $product->etsy_who_made ?? '') === 'someone_else')>Someone else</option>
                            </select>
                        </div>

                        <div>
                            <label class="admin-label" for="etsy_when_made">When Made</label>
                            <select id="etsy_when_made" name="etsy_when_made" class="admin-input">
                                <option value="made_to_order" @selected(old('etsy_when_made', $product->etsy_when_made ?? 'made_to_order') === 'made_to_order')>Made to order</option>
                                <option value="2020_2026"     @selected(old('etsy_when_made', $product->etsy_when_made ?? '') === '2020_2026')>2020–2026</option>
                                <option value="2010_2019"     @selected(old('etsy_when_made', $product->etsy_when_made ?? '') === '2010_2019')>2010–2019</option>
                                <option value="2000_2009"     @selected(old('etsy_when_made', $product->etsy_when_made ?? '') === '2000_2009')>2000–2009</option>
                                <option value="1990s"         @selected(old('etsy_when_made', $product->etsy_when_made ?? '') === '1990s')>1990s</option>
                                <option value="before_1700"  @selected(old('etsy_when_made', $product->etsy_when_made ?? '') === 'before_1700')>Before 1700</option>
                            </select>
                        </div>

                        <div>
                            <label class="admin-label" for="etsy_listing_type">Listing Type</label>
                            <select id="etsy_listing_type" name="etsy_listing_type" class="admin-input">
                                <option value="physical" @selected(old('etsy_listing_type', $product->etsy_listing_type ?? 'physical') === 'physical')>Physical</option>
                                <option value="digital"  @selected(old('etsy_listing_type', $product->etsy_listing_type ?? '') === 'digital')>Digital</option>
                                <option value="download" @selected(old('etsy_listing_type', $product->etsy_listing_type ?? '') === 'download')>Download</option>
                            </select>
                        </div>

                        <div style="display: flex; flex-direction: column; gap: 0.5rem; padding-top: 1.5rem;">
                            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; font-size: 0.8125rem;">
                                <input type="checkbox" name="etsy_is_taxable" value="1" @checked(old('etsy_is_taxable', $product->etsy_is_taxable ?? true)) style="width: 1rem; height: 1rem; accent-color: #2C4C3B;">
                                Taxable
                            </label>
                            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; font-size: 0.8125rem;">
                                <input type="checkbox" name="etsy_should_auto_renew" value="1" @checked(old('etsy_should_auto_renew', $product->etsy_should_auto_renew ?? true)) style="width: 1rem; height: 1rem; accent-color: #2C4C3B;">
                                Auto-renew listing
                            </label>
                            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; font-size: 0.8125rem;">
                                <input type="checkbox" name="etsy_is_customizable" value="1" @checked(old('etsy_is_customizable', $product->etsy_is_customizable ?? false)) style="width: 1rem; height: 1rem; accent-color: #2C4C3B;">
                                Customizable
                            </label>
                            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; font-size: 0.8125rem;">
                                <input type="checkbox" name="etsy_is_supply" value="1" @checked(old('etsy_is_supply', $product->etsy_is_supply ?? false)) style="width: 1rem; height: 1rem; accent-color: #2C4C3B;">
                                Is a supply/tool
                            </label>
                            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; font-size: 0.8125rem;">
                                <input type="checkbox" name="etsy_is_private" value="1" @checked(old('etsy_is_private', $product->etsy_is_private ?? false)) style="width: 1rem; height: 1rem; accent-color: #2C4C3B;">
                                Private listing
                            </label>
                        </div>
                    </div>

                    {{-- IDs --}}
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div style="position: relative;" @click.outside="showTaxonomyDropdown = false">
                            <label class="admin-label" for="etsy_taxonomy_search">Etsy Category</label>
                            <input type="hidden" name="etsy_taxonomy_id" :value="etsyTaxonomyId">
                            <input
                                type="text"
                                id="etsy_taxonomy_search"
                                class="admin-input"
                                x-model="etsyTaxonomyLabel"
                                @focus="showTaxonomyDropdown = filteredTaxonomySuggestions.length > 0"
                                @input="showTaxonomyDropdown = true"
                                placeholder="Select or search Etsy category…"
                                autocomplete="off"
                            >
                            <div x-show="showTaxonomyDropdown && filteredTaxonomySuggestions.length > 0" x-cloak
                                style="position: absolute; top: 100%; left: 0; right: 0; z-index: 50; background: #fff; border: 1px solid #d1d5db; border-radius: 0.25rem; box-shadow: 0 4px 12px rgba(0,0,0,0.1); max-height: 260px; overflow-y: auto; margin-top: 2px;">
                                <template x-for="item in filteredTaxonomySuggestions" :key="item.id">
                                    <div
                                        @click="selectTaxonomy(item)"
                                        :class="{ 'taxonomy-option-active': etsyTaxonomyId == item.id }"
                                        style="padding: 0.5rem 0.75rem; font-size: 0.8125rem; cursor: pointer; border-bottom: 1px solid #f3f4f6; color: #333; line-height: 1.4;"
                                        onmouseover="this.style.background='#fef3c7'"
                                        onmouseout="this.style.background=this.classList.contains('taxonomy-option-active') ? '#fef9ef' : '#fff'"
                                        :style="etsyTaxonomyId == item.id ? 'background:#fef9ef; font-weight:600;' : ''"
                                    >
                                        <span x-text="item.label" style="display:block;"></span>
                                        <span x-text="'ID: ' + item.id" style="display:block; font-size:0.6875rem; color:#9ca3af; font-family:monospace;"></span>
                                    </div>
                                </template>
                            </div>
                            <p class="admin-hint">Auto-fills from category. Required to create new listings.</p>
                        </div>
                        <div x-data="shopSectionPicker({{ Illuminate\Support\Js::from($etsyShopSections) }}, {{ old('etsy_shop_section_id', $product->etsy_shop_section_id ?? 'null') }})">
                            <label class="admin-label" for="etsy_shop_section_id">Shop Section</label>
                            <select id="etsy_shop_section_id" name="etsy_shop_section_id" class="admin-input" x-model="selectedId">
                                <option value="">— None —</option>
                                <template x-for="s in sections" :key="s.id">
                                    <option :value="s.id" x-text="s.title"></option>
                                </template>
                            </select>

                            {{-- Add new section inline --}}
                            <div x-show="!adding" style="margin-top: 0.375rem;">
                                <button type="button" @click="adding = true"
                                    style="font-size: 0.75rem; color: #8C7B6C; background: none; border: none; cursor: pointer; padding: 0; text-decoration: underline;">
                                    ＋ New section
                                </button>
                            </div>
                            <div x-show="adding" x-cloak style="display: flex; gap: 0.5rem; margin-top: 0.375rem; align-items: center;">
                                <input type="text" x-model="newTitle" @keydown.enter.prevent="addSection()"
                                    placeholder="Section name…" class="admin-input" style="flex: 1; font-size: 0.8125rem;">
                                <button type="button" @click="addSection()" :disabled="creating"
                                    class="admin-btn admin-btn-outline" style="font-size: 0.75rem; white-space: nowrap;"
                                    x-text="creating ? 'Adding…' : 'Add'">
                                </button>
                                <button type="button" @click="adding = false; newTitle = ''; sectionError = ''"
                                    style="background: none; border: none; cursor: pointer; color: #9ca3af; font-size: 1rem; padding: 0.25rem;">&#x2715;</button>
                            </div>
                            <p x-show="sectionError" x-text="sectionError" x-cloak
                                style="font-size: 0.75rem; color: #991b1b; margin-top: 0.25rem;"></p>
                        </div>
                        <div>
                            <label class="admin-label" for="etsy_shipping_profile_id">Shipping Profile</label>
                            <select id="etsy_shipping_profile_id" name="etsy_shipping_profile_id" class="admin-input">
                                <option value="">— None —</option>
                                @foreach($etsyShippingProfiles as $profile)
                                    <option value="{{ $profile['id'] }}" @selected(old('etsy_shipping_profile_id', $product->etsy_shipping_profile_id ?? '') == $profile['id'])>
                                        {{ $profile['title'] }}
                                    </option>
                                @endforeach
                            </select>
                            @if(empty($etsyShippingProfiles))
                                <p class="admin-hint">Connect Etsy to load shipping profiles.</p>
                            @endif
                        </div>
                        <div>
                            <label class="admin-label" for="etsy_return_policy_id">Return Policy</label>
                            <select id="etsy_return_policy_id" name="etsy_return_policy_id" class="admin-input">
                                <option value="">— None —</option>
                                @foreach($etsyReturnPolicies as $policy)
                                    <option value="{{ $policy['id'] }}" @selected(old('etsy_return_policy_id', $product->etsy_return_policy_id ?? '') == $policy['id'])>
                                        {{ $policy['title'] }}
                                    </option>
                                @endforeach
                            </select>
                            @if(empty($etsyReturnPolicies))
                                <p class="admin-hint">Connect Etsy to load return policies.</p>
                            @endif
                        </div>
                    </div>

                    {{-- Processing time --}}
                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem;">
                        <div>
                            <label class="admin-label" for="etsy_processing_min">Processing Min (days)</label>
                            <input type="number" id="etsy_processing_min" name="etsy_processing_min" class="admin-input"
                                value="{{ old('etsy_processing_min', $product->etsy_processing_min ?? '') }}"
                                min="0" placeholder="1">
                        </div>
                        <div>
                            <label class="admin-label" for="etsy_processing_max">Processing Max (days)</label>
                            <input type="number" id="etsy_processing_max" name="etsy_processing_max" class="admin-input"
                                value="{{ old('etsy_processing_max', $product->etsy_processing_max ?? '') }}"
                                min="0" placeholder="3">
                        </div>
                        <div>
                            <label class="admin-label" for="etsy_featured_rank">Featured Rank</label>
                            <input type="number" id="etsy_featured_rank" name="etsy_featured_rank" class="admin-input"
                                value="{{ old('etsy_featured_rank', $product->etsy_featured_rank ?? '') }}"
                                placeholder="-1 = not featured">
                        </div>
                    </div>

                    {{-- Dimensions --}}
                    <div>
                        <p class="admin-label" style="margin-bottom: 0.5rem;">Item Dimensions</p>
                        <div style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 0.5rem;">
                            <div>
                                <label class="admin-label" style="font-size: 0.6875rem;" for="etsy_item_weight">Weight</label>
                                <input type="number" id="etsy_item_weight" name="etsy_item_weight" class="admin-input"
                                    value="{{ old('etsy_item_weight', $product->etsy_item_weight ?? '') }}"
                                    step="0.01" min="0" placeholder="0.0">
                            </div>
                            <div>
                                <label class="admin-label" style="font-size: 0.6875rem;" for="etsy_item_weight_unit">Unit</label>
                                <select id="etsy_item_weight_unit" name="etsy_item_weight_unit" class="admin-input">
                                    <option value="oz"  @selected(old('etsy_item_weight_unit', $product->etsy_item_weight_unit ?? 'oz') === 'oz')>oz</option>
                                    <option value="g"   @selected(old('etsy_item_weight_unit', $product->etsy_item_weight_unit ?? '') === 'g')>g</option>
                                    <option value="lbs" @selected(old('etsy_item_weight_unit', $product->etsy_item_weight_unit ?? '') === 'lbs')>lbs</option>
                                    <option value="kg"  @selected(old('etsy_item_weight_unit', $product->etsy_item_weight_unit ?? '') === 'kg')>kg</option>
                                </select>
                            </div>
                            <div>
                                <label class="admin-label" style="font-size: 0.6875rem;" for="etsy_item_length">L</label>
                                <input type="number" id="etsy_item_length" name="etsy_item_length" class="admin-input"
                                    value="{{ old('etsy_item_length', $product->etsy_item_length ?? '') }}"
                                    step="0.01" min="0" placeholder="0.0">
                            </div>
                            <div>
                                <label class="admin-label" style="font-size: 0.6875rem;" for="etsy_item_width">W</label>
                                <input type="number" id="etsy_item_width" name="etsy_item_width" class="admin-input"
                                    value="{{ old('etsy_item_width', $product->etsy_item_width ?? '') }}"
                                    step="0.01" min="0" placeholder="0.0">
                            </div>
                            <div>
                                <label class="admin-label" style="font-size: 0.6875rem;" for="etsy_item_height">H</label>
                                <input type="number" id="etsy_item_height" name="etsy_item_height" class="admin-input"
                                    value="{{ old('etsy_item_height', $product->etsy_item_height ?? '') }}"
                                    step="0.01" min="0" placeholder="0.0">
                            </div>
                        </div>
                        <div style="margin-top: 0.5rem;">
                            <label class="admin-label" style="font-size: 0.6875rem;" for="etsy_item_dimensions_unit">Dimensions Unit</label>
                            <select id="etsy_item_dimensions_unit" name="etsy_item_dimensions_unit" class="admin-input" style="max-width: 120px;">
                                <option value="in" @selected(old('etsy_item_dimensions_unit', $product->etsy_item_dimensions_unit ?? 'in') === 'in')>inches</option>
                                <option value="cm" @selected(old('etsy_item_dimensions_unit', $product->etsy_item_dimensions_unit ?? '') === 'cm')>cm</option>
                            </select>
                        </div>
                    </div>

                    {{-- Tags / Materials / Style --}}
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div>
                            <label class="admin-label" for="etsy_tags_raw">Etsy Tags <span style="color: #9ca3af; font-weight: 400;">(comma-separated, max 13)</span></label>
                            <textarea id="etsy_tags_raw" name="etsy_tags_raw" class="admin-input" rows="3" style="resize: vertical;"
                                placeholder="wooden earrings, laser cut jewelry, boho wood jewelry">{{ old('etsy_tags_raw', implode(', ', $product->etsy_tags ?? [])) }}</textarea>
                        </div>
                        <div>
                            <label class="admin-label" for="etsy_materials_raw">Materials <span style="color: #9ca3af; font-weight: 400;">(comma-separated)</span></label>
                            <textarea id="etsy_materials_raw" name="etsy_materials_raw" class="admin-input" rows="3" style="resize: vertical;"
                                placeholder="hardwood, danish oil, sterling silver">{{ old('etsy_materials_raw', implode(', ', $product->etsy_materials ?? [])) }}</textarea>
                        </div>
                        <div style="grid-column: span 2;">
                            <label class="admin-label" for="etsy_style_raw">Style Tags <span style="color: #9ca3af; font-weight: 400;">(max 2, comma-separated)</span></label>
                            <input type="text" id="etsy_style_raw" name="etsy_style_raw" class="admin-input"
                                value="{{ old('etsy_style_raw', implode(', ', $product->etsy_style ?? [])) }}"
                                placeholder="Boho, Rustic">
                        </div>
                    </div>

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
                <button type="submit" form="etsy-push-form" class="admin-btn admin-btn-outline" style="width: 100%; justify-content: center; font-size: 0.8125rem; border-color: #f59e0b; color: #92400e;"
                    onclick="return confirm({{ Illuminate\Support\Js::from('Push '.$product->name.' to Etsy?') }})">
                    {{ $product->etsy_listing_id ? '↑ Update on Etsy' : '↑ Create on Etsy' }}
                </button>
            </div>
            @endif

        </div>
        {{-- end right column --}}

    </div>
</form>

@if($isEditing)
<form id="etsy-push-form" method="POST" action="{{ route('admin.etsy.push.product', $product) }}" style="display:none;">
    @csrf
</form>
@endif

@push('scripts')
<script>
    function productForm(categoryTaxonomyMap, taxonomySuggestions) {
        // Build flat id→label lookup from suggestions
        const allSuggestions = Object.values(taxonomySuggestions || {}).flat();
        const taxonomyLabelMap = {};
        allSuggestions.forEach(s => { taxonomyLabelMap[s.id] = s.label; });

        const initId = '{{ old('etsy_taxonomy_id', $product->etsy_taxonomy_id ?? '') }}';
        const initLabel = initId ? (taxonomyLabelMap[initId] || initId) : '';
        const initCatId = '{{ old('category_id', $product->category_id ?? '') }}';

        return {
            slug: '{{ old('slug', $product->slug ?? '') }}',
            slugManuallyEdited: {{ isset($product) && $product->slug ? 'true' : 'false' }},

            // Taxonomy combobox state
            etsyTaxonomyId: initId,
            etsyTaxonomyLabel: initLabel,
            showTaxonomyDropdown: false,
            taxonomyAutoFilled: false,
            currentCategoryId: initCatId,
            categoryTaxonomyMap: categoryTaxonomyMap || {},
            taxonomySuggestions: taxonomySuggestions || {},
            taxonomyLabelMap: taxonomyLabelMap,

            get currentSuggestions() {
                return this.taxonomySuggestions[this.currentCategoryId] || [];
            },

            get filteredTaxonomySuggestions() {
                const q = this.etsyTaxonomyLabel.toLowerCase().trim();
                const pool = this.currentSuggestions.length
                    ? this.currentSuggestions
                    : allSuggestions;
                if (!q) return pool;
                return pool.filter(s =>
                    s.label.toLowerCase().includes(q) ||
                    String(s.id).includes(q)
                );
            },

            selectTaxonomy(item) {
                this.etsyTaxonomyId = item.id;
                this.etsyTaxonomyLabel = item.label;
                this.showTaxonomyDropdown = false;
                this.taxonomyAutoFilled = true;
            },

            syncSlug(name) {
                if (this.slugManuallyEdited) return;
                this.slug = name
                    .toLowerCase()
                    .trim()
                    .replace(/[^a-z0-9\s-]/g, '')
                    .replace(/[\s]+/g, '-')
                    .replace(/-+/g, '-');
            },

            onCategoryChange(categoryId) {
                this.currentCategoryId = categoryId;
                // Auto-fill only if empty or previously auto-filled
                if (!this.etsyTaxonomyId || this.taxonomyAutoFilled) {
                    const suggestions = this.taxonomySuggestions[categoryId] || [];
                    if (suggestions.length) {
                        this.etsyTaxonomyId = suggestions[0].id;
                        this.etsyTaxonomyLabel = suggestions[0].label;
                        this.taxonomyAutoFilled = true;
                    } else {
                        if (this.taxonomyAutoFilled) {
                            this.etsyTaxonomyId = '';
                            this.etsyTaxonomyLabel = '';
                        }
                        this.taxonomyAutoFilled = false;
                    }
                }
            },

            handleSubmit(e) {
                // Allow normal submission — hook for future use
            },
        };
    }

    function shopSectionPicker(initialSections, currentId) {
        return {
            sections: initialSections || [],
            selectedId: currentId ? String(currentId) : '',
            adding: false,
            creating: false,
            newTitle: '',
            sectionError: '',

            async addSection() {
                const title = this.newTitle.trim();
                if (!title) return;

                this.creating = true;
                this.sectionError = '';

                try {
                    const res = await fetch('{{ route('admin.etsy.sections.store') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({ title }),
                    });

                    const data = await res.json();

                    if (!res.ok) {
                        this.sectionError = data.error || 'Failed to create section.';
                        return;
                    }

                    this.sections.push({ id: data.id, title: data.title });
                    this.selectedId = String(data.id);
                    this.newTitle = '';
                    this.adding = false;
                } catch {
                    this.sectionError = 'Network error — please try again.';
                } finally {
                    this.creating = false;
                }
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
