@extends('layouts.admin')

@section('page-title', 'Edit Shipping Method')

@section('content')

<div style="max-width: 600px;">

    <div style="margin-bottom: 1rem;">
        <a href="{{ route('admin.shipping.index') }}" style="font-size: 0.8125rem; color: #6b7280;">
            &larr; Back to Shipping Methods
        </a>
    </div>

    <div class="admin-card">
        <div class="admin-card-header">
            <span class="admin-card-title">{{ $shipping->name }}</span>
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

        <form method="POST" action="{{ route('admin.shipping.update', $shipping) }}">
            @csrf
            @method('PUT')

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                <div>
                    <label class="admin-label" for="name">Name <span style="color: #ba1a1a;">*</span></label>
                    <input type="text" id="name" name="name" class="admin-input"
                        value="{{ old('name', $shipping->name) }}" required>
                </div>

                <div>
                    <label class="admin-label" for="service_code">Service Code <span style="color: #ba1a1a;">*</span></label>
                    <input type="text" id="service_code" name="service_code" class="admin-input"
                        value="{{ old('service_code', $shipping->service_code) }}" required>
                </div>
            </div>

            <div style="margin-bottom: 1rem;">
                <label class="admin-label" for="description">Description</label>
                <input type="text" id="description" name="description" class="admin-input"
                    value="{{ old('description', $shipping->description) }}"
                    placeholder="e.g. Arrives in 5–7 business days">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                <div>
                    <label class="admin-label" for="price_override">Price Override ($)</label>
                    <input type="number" id="price_override" name="price_override" class="admin-input"
                        value="{{ old('price_override', $shipping->price_override) }}"
                        step="0.01" min="0" placeholder="Leave blank to calculate">
                    <p style="font-size: 0.75rem; color: #9ca3af; margin-top: 0.25rem;">Overrides carrier-calculated rate.</p>
                </div>

                <div>
                    <label class="admin-label" for="sort_order">Sort Order</label>
                    <input type="number" id="sort_order" name="sort_order" class="admin-input"
                        value="{{ old('sort_order', $shipping->sort_order) }}" min="0">
                </div>
            </div>

            <div style="margin-bottom: 1.5rem; display: flex; gap: 1.5rem;">
                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; font-size: 0.875rem;">
                    <input type="hidden" name="is_free_base" value="0">
                    <input type="checkbox" name="is_free_base" value="1"
                        {{ old('is_free_base', $shipping->is_free_base) ? 'checked' : '' }}
                        style="width: 1rem; height: 1rem; accent-color: #2C4C3B;">
                    Free shipping base
                </label>

                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; font-size: 0.875rem;">
                    <input type="hidden" name="active" value="0">
                    <input type="checkbox" name="active" value="1"
                        {{ old('active', $shipping->active) ? 'checked' : '' }}
                        style="width: 1rem; height: 1rem; accent-color: #2C4C3B;">
                    Active
                </label>
            </div>

            <div style="display: flex; gap: 0.75rem; padding-top: 0.5rem; border-top: 1px solid #f3f4f6;">
                <button type="submit" class="admin-btn admin-btn-primary">Save Changes</button>
                <a href="{{ route('admin.shipping.index') }}" class="admin-btn admin-btn-outline">Cancel</a>
            </div>
        </form>
    </div>

</div>

@endsection
