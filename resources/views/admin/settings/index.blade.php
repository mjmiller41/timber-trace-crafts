@extends('layouts.admin')

@section('page-title', 'Settings')

@section('content')

@if(session('success'))
<div style="background: #f0fdf4; border: 1px solid #86efac; border-radius: 0.25rem; padding: 0.75rem 1rem; margin-bottom: 1.25rem; font-size: 0.875rem; color: #166534;">
    {{ session('success') }}
</div>
@endif

<form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data" style="max-width: 760px;">
    @csrf
    @method('PUT')

    @php
        $groupLabels = [
            'store'     => 'Store',
            'email'     => 'Email',
            'payments'  => 'Payments',
            'shipping'  => 'Shipping',
            'tax'       => 'Tax',
            'inventory' => 'Inventory',
            'social'    => 'Social Media',
            'analytics' => 'Analytics',
        ];
        $brandKeys = ['store.name', 'store.tagline', 'store.logo_path'];
        $logoPath  = $settings['store']?->firstWhere('key', 'store.logo_path')?->value ?? '';
        $logoUrl   = $logoPath ? Storage::disk(config('filesystems.default'))->url($logoPath) : asset('images/logo.png');
    @endphp

    {{-- Brand Identity card --}}
    <div class="admin-card" style="margin-bottom: 1.5rem;">
        <div class="admin-card-header">
            <span class="admin-card-title">Brand Identity</span>
        </div>

        <div style="display: flex; flex-direction: column; gap: 1.25rem;">

            {{-- Logo --}}
            <div>
                <label class="admin-label">Logo</label>
                <div style="display: flex; align-items: center; gap: 1.25rem; margin-bottom: 0.5rem;">
                    <img src="{{ $logoUrl }}" alt="Current logo" style="height: 56px; width: auto; object-fit: contain; background: #f9f9f7; border: 1px solid #e5e1d8; padding: 6px;">
                    <div>
                        <input type="file" name="logo" id="logo" accept="image/*" class="admin-input" style="max-width: 320px; padding: 0.375rem;">
                        <p style="font-size: 0.75rem; color: #9ca3af; margin-top: 0.25rem;">PNG or JPG, max 2 MB. Leave blank to keep current logo.</p>
                    </div>
                </div>
            </div>

            {{-- Store Name --}}
            @php $nameSetting = $settings['store']?->firstWhere('key', 'store.name'); @endphp
            <div>
                <label class="admin-label" for="setting_store_name">Store Name</label>
                <input type="text" id="setting_store_name" name="settings[store.name]" class="admin-input"
                    value="{{ old('settings[store.name]', $nameSetting?->value) }}" style="max-width: 480px;" autocomplete="off">
            </div>

            {{-- Tagline --}}
            @php $taglineSetting = $settings['store']?->firstWhere('key', 'store.tagline'); @endphp
            <div>
                <label class="admin-label" for="setting_store_tagline">Tagline</label>
                <input type="text" id="setting_store_tagline" name="settings[store.tagline]" class="admin-input"
                    value="{{ old('settings[store.tagline]', $taglineSetting?->value) }}" style="max-width: 480px;" autocomplete="off">
            </div>

        </div>
    </div>

    @foreach($settings as $group => $groupSettings)
    @php
        $visibleSettings = $groupSettings->reject(fn($s) => in_array($s->key, $brandKeys));
    @endphp
    @if($visibleSettings->isEmpty()) @continue @endif
    <div class="admin-card" style="margin-bottom: 1.5rem;">
        <div class="admin-card-header">
            <span class="admin-card-title">{{ $groupLabels[$group] ?? ucfirst($group) }}</span>
        </div>

        <div style="display: flex; flex-direction: column; gap: 1rem;">
            @foreach($visibleSettings as $setting)
            <div>
                <label class="admin-label" for="setting_{{ $setting->key }}">
                    {{ $setting->label ?? $setting->key }}
                </label>
                @php
                    $inputKey = 'settings[' . $setting->key . ']';
                    $isSecret = str_contains($setting->key, 'token') || str_contains($setting->key, 'secret');
                @endphp
                <input
                    type="{{ $isSecret ? 'password' : 'text' }}"
                    id="setting_{{ $setting->key }}"
                    name="{{ $inputKey }}"
                    class="admin-input"
                    value="{{ old($inputKey, $setting->value) }}"
                    autocomplete="{{ $isSecret ? 'new-password' : 'off' }}"
                    style="max-width: 480px;"
                >
                @if($isSecret)
                <p style="font-size: 0.75rem; color: #9ca3af; margin-top: 0.25rem;">Stored securely. Leave blank to keep current value.</p>
                @endif
            </div>
            @endforeach
        </div>
    </div>
    @endforeach

    {{-- Etsy Listing Default Values --}}
    <div class="admin-card" style="margin-bottom: 1.5rem;">
        <div class="admin-card-header">
            <span class="admin-card-title">Etsy Listing Default Values</span>
        </div>
        <p style="font-size: 0.8125rem; color: #6b7280; margin-bottom: 1.25rem;">
            These values are used when pushing a product to Etsy that doesn't have its own per-product values set.
            Per-product overrides (set on the product edit page) always take precedence.
        </p>

        <div style="display: flex; flex-direction: column; gap: 1.25rem;">

            <div>
                <label class="admin-label" for="setting_etsy_taxonomy_id">Default Taxonomy ID</label>
                <input
                    type="number"
                    id="setting_etsy_taxonomy_id"
                    name="settings[etsy.taxonomy_id]"
                    class="admin-input"
                    value="{{ old('settings[etsy.taxonomy_id]', $etsyDefaults['taxonomy_id']) }}"
                    placeholder="e.g. 1208"
                    style="max-width: 240px;"
                    autocomplete="off"
                >
                <p class="admin-hint">Find taxonomy IDs in <code>.claude/etsy_data/seller_taxonomy.json</code>.</p>
            </div>

            <div>
                <label class="admin-label" for="setting_etsy_shipping_profile_id">Default Shipping Profile</label>
                <select id="setting_etsy_shipping_profile_id" name="settings[etsy.shipping_profile_id]" class="admin-input" style="max-width: 480px;">
                    <option value="">— None —</option>
                    @foreach($etsyShippingProfiles as $profile)
                        <option value="{{ $profile['id'] }}" @selected($etsyDefaults['shipping_profile_id'] == $profile['id'])>
                            {{ $profile['title'] }}
                        </option>
                    @endforeach
                </select>
                @if(empty($etsyShippingProfiles))
                    <p class="admin-hint">Connect Etsy to load shipping profiles.</p>
                @endif
            </div>

            <div>
                <label class="admin-label" for="setting_etsy_return_policy_id">Default Return Policy</label>
                <select id="setting_etsy_return_policy_id" name="settings[etsy.return_policy_id]" class="admin-input" style="max-width: 480px;">
                    <option value="">— None —</option>
                    @foreach($etsyReturnPolicies as $policy)
                        <option value="{{ $policy['id'] }}" @selected($etsyDefaults['return_policy_id'] == $policy['id'])>
                            {{ $policy['title'] }}
                        </option>
                    @endforeach
                </select>
                @if(empty($etsyReturnPolicies))
                    <p class="admin-hint">Connect Etsy to load return policies.</p>
                @endif
            </div>

        </div>
    </div>

    <div style="display: flex; gap: 0.75rem;">
        <button type="submit" class="admin-btn admin-btn-primary">Save Settings</button>
    </div>
</form>

@endsection
