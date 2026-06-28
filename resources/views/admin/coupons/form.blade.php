{{--
    Shared coupon form partial.
    Variables: $coupon (optional, for edit), $categories (Collection), $products (Collection)
    Usage: @include('admin.coupons.form')
--}}

<div x-data="{
    appliesTo: '{{ old('applies_to', $coupon->applies_to ?? 'all') }}',
    discountType: '{{ old('type', $coupon->type ?? 'percent') }}'
}">

{{-- Code --}}
<div style="margin-bottom: 1.25rem;">
    <label class="admin-label" for="code">Coupon Code <span style="color: #ba1a1a;">*</span></label>
    <input
        type="text"
        id="code"
        name="code"
        class="admin-input"
        value="{{ old('code', $coupon->code ?? '') }}"
        placeholder="SUMMER20"
        style="text-transform: uppercase; font-family: monospace; font-size: 1rem; letter-spacing: 0.05em;"
        oninput="this.value = this.value.toUpperCase()"
        required
    >
    @error('code') <p class="admin-error-text">{{ $message }}</p> @enderror
</div>

{{-- Description --}}
<div style="margin-bottom: 1.25rem;">
    <label class="admin-label" for="description">Description <span style="color: #9ca3af; font-weight: 400;">(optional)</span></label>
    <input
        type="text"
        id="description"
        name="description"
        class="admin-input"
        value="{{ old('description', $coupon->description ?? '') }}"
        placeholder="e.g. Summer sale 20% off"
    >
    @error('description') <p class="admin-error-text">{{ $message }}</p> @enderror
</div>

{{-- Type --}}
<div style="margin-bottom: 1.25rem;">
    <label class="admin-label">Discount Type <span style="color: #ba1a1a;">*</span></label>
    <div style="display: flex; gap: 1.5rem; margin-top: 0.5rem;">
        <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; font-size: 0.875rem;">
            <input
                type="radio"
                name="type"
                value="percent"
                x-model="discountType"
                {{ old('type', $coupon->type ?? 'percent') === 'percent' ? 'checked' : '' }}
                style="cursor: pointer;"
            >
            Percent Off
        </label>
        <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; font-size: 0.875rem;">
            <input
                type="radio"
                name="type"
                value="fixed"
                x-model="discountType"
                {{ old('type', $coupon->type ?? '') === 'fixed' ? 'checked' : '' }}
                style="cursor: pointer;"
            >
            Fixed Dollar Amount
        </label>
    </div>
    @error('type') <p class="admin-error-text">{{ $message }}</p> @enderror
</div>

{{-- Value --}}
<div style="margin-bottom: 1.25rem;">
    <label class="admin-label" for="value">Value <span style="color: #ba1a1a;">*</span></label>
    <div style="position: relative; max-width: 200px;">
        <span
            x-show="discountType === 'fixed'" x-cloak
            style="position: absolute; left: 0.75rem; top: 50%; transform: translateY(-50%); color: #6b7280; font-size: 0.875rem; pointer-events: none;"
        >$</span>
        <input
            type="number"
            id="value"
            name="value"
            class="admin-input"
            value="{{ old('value', isset($coupon) ? number_format($coupon->value, 2) : '') }}"
            step="0.01"
            min="0"
            placeholder="0.00"
            :style="discountType === 'fixed' ? 'padding-left: 1.75rem;' : ''"
            required
        >
        <span
            x-show="discountType === 'percent'" x-cloak
            style="position: absolute; right: 0.75rem; top: 50%; transform: translateY(-50%); color: #6b7280; font-size: 0.875rem; pointer-events: none;"
        >%</span>
    </div>
    @error('value') <p class="admin-error-text">{{ $message }}</p> @enderror
</div>

{{-- Applies To --}}
<div style="margin-bottom: 1.25rem;">
    <label class="admin-label" for="applies_to">Applies To</label>
    <select
        id="applies_to"
        name="applies_to"
        class="admin-input"
        x-model="appliesTo"
        style="max-width: 280px;"
    >
        <option value="all" {{ old('applies_to', $coupon->applies_to ?? 'all') === 'all' ? 'selected' : '' }}>All Products</option>
        <option value="category" {{ old('applies_to', $coupon->applies_to ?? '') === 'category' ? 'selected' : '' }}>Specific Category</option>
        <option value="product" {{ old('applies_to', $coupon->applies_to ?? '') === 'product' ? 'selected' : '' }}>Specific Product</option>
    </select>
    @error('applies_to') <p class="admin-error-text">{{ $message }}</p> @enderror
</div>

{{-- Category select (conditional) --}}
<div x-show="appliesTo === 'category'" style="margin-bottom: 1.25rem; display: none;">
    <label class="admin-label" for="category_id">Category</label>
    <select id="category_id" name="category_id" class="admin-input" style="max-width: 280px;">
        <option value="">— Select Category —</option>
        @foreach($categories as $cat)
            <option value="{{ $cat->id }}" {{ old('category_id', $coupon->category_id ?? '') == $cat->id ? 'selected' : '' }}>
                {{ $cat->name }}
            </option>
        @endforeach
    </select>
    @error('category_id') <p class="admin-error-text">{{ $message }}</p> @enderror
</div>

{{-- Product select (conditional) --}}
<div x-show="appliesTo === 'product'" style="margin-bottom: 1.25rem; display: none;">
    <label class="admin-label" for="product_id">Product</label>
    <select id="product_id" name="product_id" class="admin-input" style="max-width: 360px;">
        <option value="">— Select Product —</option>
        @foreach($products as $prod)
            <option value="{{ $prod->id }}" {{ old('product_id', $coupon->product_id ?? '') == $prod->id ? 'selected' : '' }}>
                {{ $prod->name }}
            </option>
        @endforeach
    </select>
    @error('product_id') <p class="admin-error-text">{{ $message }}</p> @enderror
</div>

{{-- Min Order & Max Uses --}}
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.25rem; max-width: 480px;">
    <div>
        <label class="admin-label" for="min_order_amount">Minimum Order Amount</label>
        <div style="position: relative;">
            <span style="position: absolute; left: 0.75rem; top: 50%; transform: translateY(-50%); color: #6b7280; font-size: 0.875rem; pointer-events: none;">$</span>
            <input
                type="number"
                id="min_order_amount"
                name="min_order_amount"
                class="admin-input"
                value="{{ old('min_order_amount', isset($coupon) ? $coupon->min_order_amount : '') }}"
                step="0.01"
                min="0"
                placeholder="0.00"
                style="padding-left: 1.75rem;"
            >
        </div>
        <p class="admin-hint">Leave blank for no minimum.</p>
        @error('min_order_amount') <p class="admin-error-text">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="admin-label" for="max_uses">Max Uses</label>
        <input
            type="number"
            id="max_uses"
            name="max_uses"
            class="admin-input"
            value="{{ old('max_uses', $coupon->max_uses ?? '') }}"
            step="1"
            min="1"
            placeholder="Unlimited"
        >
        <p class="admin-hint">Leave blank for unlimited.</p>
        @error('max_uses') <p class="admin-error-text">{{ $message }}</p> @enderror
    </div>
</div>

{{-- Valid From / Expires At --}}
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.25rem; max-width: 480px;">
    <div>
        <label class="admin-label" for="starts_at">Valid From</label>
        <input
            type="date"
            id="starts_at"
            name="starts_at"
            class="admin-input"
            value="{{ old('starts_at', isset($coupon) && $coupon->starts_at ? $coupon->starts_at->format('Y-m-d') : '') }}"
        >
        @error('starts_at') <p class="admin-error-text">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="admin-label" for="expires_at">Expires At</label>
        <input
            type="date"
            id="expires_at"
            name="expires_at"
            class="admin-input"
            value="{{ old('expires_at', isset($coupon) && $coupon->expires_at ? $coupon->expires_at->format('Y-m-d') : '') }}"
        >
        @error('expires_at') <p class="admin-error-text">{{ $message }}</p> @enderror
    </div>
</div>

{{-- Active --}}
<div style="margin-bottom: 1.5rem;">
    <label style="display: flex; align-items: center; gap: 0.625rem; cursor: pointer; font-size: 0.875rem; font-weight: 600; color: #333;">
        <input
            type="hidden"
            name="active"
            value="0"
        >
        <input
            type="checkbox"
            name="active"
            value="1"
            style="width: 1rem; height: 1rem; cursor: pointer;"
            {{ old('active', $coupon->active ?? true) ? 'checked' : '' }}
        >
        Active
    </label>
    <p class="admin-hint" style="margin-left: 1.625rem;">Inactive coupons cannot be used at checkout.</p>
</div>

</div>{{-- end x-data --}}
