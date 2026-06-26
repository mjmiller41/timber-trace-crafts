@extends('layouts.admin')

@section('page-title', 'Products')

@section('content')

{{-- Header --}}
<div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.25rem;">
    <h2 style="font-family: 'Playfair Display', serif; font-size: 1.25rem; font-weight: 300; color: #333;">All Products</h2>
    <a href="{{ route('admin.products.create') }}" class="admin-btn" style="background: #2C4C3B; color: #fff;">+ New Product</a>
</div>

{{-- Filters --}}
<div class="admin-card" style="margin-bottom: 1.5rem; padding: 1rem 1.5rem;">
    <form method="GET" action="{{ route('admin.products.index') }}" style="display: flex; gap: 0.75rem; align-items: flex-end; flex-wrap: wrap;">

        <div style="flex: 1; min-width: 200px;">
            <label class="admin-label" for="search">Search</label>
            <input type="text" id="search" name="search" class="admin-input" placeholder="Product name or SKU…" value="{{ request('search') }}">
        </div>

        <div style="min-width: 160px;">
            <label class="admin-label" for="category">Category</label>
            <select id="category" name="category" class="admin-input">
                <option value="">All Categories</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" @selected(request('category') == $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
        </div>

        <div style="min-width: 140px;">
            <label class="admin-label" for="status">Status</label>
            <select id="status" name="status" class="admin-input">
                <option value="">All Statuses</option>
                <option value="active"   @selected(request('status') === 'active')>Active</option>
                <option value="draft"    @selected(request('status') === 'draft')>Draft</option>
                <option value="archived" @selected(request('status') === 'archived')>Archived</option>
            </select>
        </div>

        <div style="display: flex; gap: 0.5rem; padding-bottom: 0.125rem;">
            <button type="submit" class="admin-btn admin-btn-primary">Filter</button>
            @if(request()->hasAny(['search', 'category', 'status']))
                <a href="{{ route('admin.products.index') }}" class="admin-btn admin-btn-outline">Clear</a>
            @endif
        </div>

    </form>
</div>

{{-- Products Table --}}
<div class="admin-card" style="padding: 0;">

    <div style="overflow-x: auto;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th style="width: 60px;"></th>
                    <th>Product</th>
                    <th>SKU Base</th>
                    <th>Category</th>
                    <th>Status</th>
                    <th style="text-align: right;">Price</th>
                    <th style="text-align: right;">Variants</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($products as $product)
                <tr>
                    <td>
                        @php $imgUrl = $product->primary_image_url ?? null; @endphp
                        @if($imgUrl)
                            <img src="{{ $imgUrl }}" alt="{{ $product->name }}" style="width: 50px; height: 50px; object-fit: cover; border-radius: 0.125rem; border: 1px solid #e5e7eb;">
                        @else
                            <div style="width: 50px; height: 50px; background: #f3f4f6; border-radius: 0.125rem; display: flex; align-items: center; justify-content: center; color: #d1d5db; font-size: 1.25rem;">&#x1F4F7;</div>
                        @endif
                    </td>
                    <td>
                        <div style="font-weight: 600;">{{ $product->name }}</div>
                        @if($product->featured)
                            <span class="admin-badge-info" style="font-size: 0.6875rem; margin-top: 0.25rem;">Featured</span>
                        @endif
                        @if($product->isOnSale())
                            <span class="admin-badge-success" style="font-size: 0.6875rem; margin-top: 0.25rem; margin-left: 0.25rem;">On Sale</span>
                        @endif
                    </td>
                    <td style="font-size: 0.8125rem; color: #6b7280; font-family: monospace;">{{ $product->sku_base }}</td>
                    <td style="font-size: 0.8125rem; color: #6b7280;">{{ $product->category?->name ?? '—' }}</td>
                    <td>
                        @php
                            $badge = match($product->status) {
                                'active'   => 'admin-badge-success',
                                'draft'    => 'admin-badge-neutral',
                                'archived' => 'admin-badge-error',
                                default    => 'admin-badge-neutral',
                            };
                        @endphp
                        <span class="{{ $badge }}">{{ ucfirst($product->status) }}</span>
                    </td>
                    <td style="text-align: right; white-space: nowrap;">
                        @if($product->isOnSale())
                            <span style="text-decoration: line-through; color: #9ca3af; font-size: 0.8125rem;">${{ number_format($product->price, 2) }}</span>
                            <span style="font-weight: 600; color: #059669;">${{ number_format($product->sale_price, 2) }}</span>
                        @else
                            <span style="font-weight: 600;">${{ number_format($product->price, 2) }}</span>
                        @endif
                    </td>
                    <td style="text-align: right; color: #6b7280;">
                        {{ $product->variants_count ?? $product->variants->count() }}
                    </td>
                    <td>
                        <div style="display: flex; gap: 0.375rem; justify-content: flex-end;">
                            <a href="{{ route('admin.products.edit', $product) }}" class="admin-btn admin-btn-outline" style="font-size: 0.75rem; padding: 0.25rem 0.625rem;">Edit</a>
                            <form method="POST" action="{{ route('admin.products.destroy', $product) }}"
                                  @submit.prevent="$dispatch('confirm-delete', {
                                      form: $el,
                                      message: {{ $product->sold_on_etsy && $product->etsy_listing_id
                                          ? Illuminate\Support\Js::from('This product is listed on Etsy (listing #' . $product->etsy_listing_id . '). Deleting it here will also permanently remove it from Etsy. This cannot be undone.')
                                          : Illuminate\Support\Js::from('Are you sure? This action cannot be undone.') }}
                                  })">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="admin-btn admin-btn-danger" style="font-size: 0.75rem; padding: 0.25rem 0.625rem;">Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" style="text-align: center; color: #9ca3af; padding: 3rem;">
                        No products found.
                        @if(request()->hasAny(['search', 'category', 'status']))
                            <a href="{{ route('admin.products.index') }}" style="color: #2C4C3B; margin-left: 0.375rem;">Clear filters</a>
                        @endif
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if($products->hasPages())
    <div style="padding: 1rem 1.5rem; border-top: 1px solid #f3f4f6;">
        {{ $products->appends(request()->query())->links() }}
    </div>
    @endif

</div>

@endsection
