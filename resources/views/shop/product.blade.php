@extends('layouts.app')

@section('title', $product->name)
@section('meta_description', Str::limit(strip_tags($product->description ?? $product->name), 155))

@section('content')

@php
    $images = $product->media
        ->filter(fn($m) => $m->media && str_contains($m->media->mime_type ?? '', 'image'))
        ->map(fn($m) => asset('storage/' . $m->media->path))
        ->values()
        ->toArray();

    $videos = $product->media
        ->filter(fn($m) => $m->media && str_contains($m->media->mime_type ?? '', 'video'))
        ->map(fn($m) => asset('storage/' . $m->media->path))
        ->values()
        ->toArray();

    $allMedia = collect($images)->map(fn($u) => ['type' => 'image', 'url' => $u])
        ->concat(collect($videos)->map(fn($u) => ['type' => 'video', 'url' => $u]))
        ->values()
        ->toArray();

    $variantsJson = $product->variants->map(fn($v) => [
        'id'                  => $v->id,
        'label'               => $v->label,
        'stock_qty'           => $v->stock_qty,
        'low_stock_threshold' => $v->low_stock_threshold,
    ])->toJson();

    $avgRating = $product->reviews->where('approved', true)->avg('rating');
    $reviewCount = $product->reviews->where('approved', true)->count();
@endphp

{{-- ============================================================ --}}
{{-- BREADCRUMB --}}
{{-- ============================================================ --}}
<div class="border-b border-walnut/20 py-5">
    <div class="page-container">
        <nav class="section-label" aria-label="Breadcrumb">
            <a href="{{ route('home') }}" class="hover:text-forest-green transition-colors">Home</a>
            <span class="mx-2 text-walnut/40">/</span>
            <a href="{{ route('shop') }}" class="hover:text-forest-green transition-colors">Shop</a>
            @if($product->category)
                <span class="mx-2 text-walnut/40">/</span>
                <a href="{{ route('shop') }}?category={{ $product->category->slug }}"
                   class="hover:text-forest-green transition-colors">{{ $product->category->name }}</a>
            @endif
            <span class="mx-2 text-walnut/40">/</span>
            <span class="text-charcoal">{{ $product->name }}</span>
        </nav>
    </div>
</div>

{{-- ============================================================ --}}
{{-- PRODUCT DETAIL --}}
{{-- ============================================================ --}}
<div class="page-container py-12 md:py-20">
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 lg:gap-20">

        {{-- ====================================================== --}}
        {{-- LEFT: GALLERY --}}
        {{-- ====================================================== --}}
        <div x-data="gallery({{ json_encode($allMedia) }})" class="space-y-4">

            {{-- Main display --}}
            <div class="relative aspect-square bg-surface overflow-hidden">
                <template x-if="currentItem && currentItem.type === 'image'">
                    <img :src="currentItem.url"
                         :alt="'{{ $product->name }}'"
                         class="w-full h-full object-cover">
                </template>
                <template x-if="currentItem && currentItem.type === 'video'">
                    <video :src="currentItem.url" controls class="w-full h-full object-contain bg-charcoal"></video>
                </template>
                <template x-if="!currentItem || items.length === 0">
                    <div class="w-full h-full flex items-center justify-center bg-surface">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="0.75" stroke="currentColor" class="w-20 h-20 text-walnut/20">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5z" />
                        </svg>
                    </div>
                </template>

                {{-- Prev/Next arrows --}}
                <template x-if="items.length > 1">
                    <div>
                        <button @click="prev()"
                                class="absolute left-3 top-1/2 -translate-y-1/2 bg-white/90 text-charcoal w-9 h-9 flex items-center justify-center hover:bg-white transition-colors"
                                aria-label="Previous image">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                            </svg>
                        </button>
                        <button @click="next()"
                                class="absolute right-3 top-1/2 -translate-y-1/2 bg-white/90 text-charcoal w-9 h-9 flex items-center justify-center hover:bg-white transition-colors"
                                aria-label="Next image">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                            </svg>
                        </button>
                    </div>
                </template>
            </div>

            {{-- Thumbnails --}}
            <template x-if="items.length > 1">
                <div class="flex gap-2 overflow-x-auto pb-1">
                    <template x-for="(item, index) in items" :key="index">
                        <button @click="setIndex(index)"
                                class="flex-shrink-0 w-16 h-16 overflow-hidden border-2 transition-colors duration-150"
                                :class="currentIndex === index ? 'border-forest-green' : 'border-transparent hover:border-walnut/50'">
                            <template x-if="item.type === 'image'">
                                <img :src="item.url" alt="" class="w-full h-full object-cover">
                            </template>
                            <template x-if="item.type === 'video'">
                                <div class="w-full h-full bg-charcoal flex items-center justify-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5 text-white/70">
                                        <path d="M8 5v14l11-7z"/>
                                    </svg>
                                </div>
                            </template>
                        </button>
                    </template>
                </div>
            </template>
        </div>

        {{-- ====================================================== --}}
        {{-- RIGHT: PRODUCT DETAILS --}}
        {{-- ====================================================== --}}
        <div x-data="variantSelector({{ $variantsJson }})">

            {{-- Category label --}}
            @if($product->category)
                <p class="section-label mb-3">{{ $product->category->name }}</p>
            @endif

            {{-- Name --}}
            <h1 class="font-heading text-4xl md:text-5xl font-light text-charcoal leading-tight mb-5">
                {{ $product->name }}
            </h1>

            {{-- Rating summary --}}
            @if($reviewCount > 0)
                <div class="flex items-center gap-2 mb-5">
                    <div class="flex text-mahogany text-sm" aria-label="{{ number_format($avgRating, 1) }} out of 5 stars">
                        @for($i = 1; $i <= 5; $i++)
                            <span>{{ $i <= round($avgRating) ? '★' : '☆' }}</span>
                        @endfor
                    </div>
                    <a href="#reviews" class="section-label hover:text-charcoal transition-colors">
                        {{ $reviewCount }} {{ Str::plural('review', $reviewCount) }}
                    </a>
                </div>
            @endif

            {{-- Price --}}
            <div class="flex items-baseline gap-3 mb-6">
                @if($product->sale_price && $product->sale_price < $product->price)
                    <span class="font-body text-2xl font-600 text-mahogany">
                        ${{ number_format($product->currentPrice(), 2) }}
                    </span>
                    <span class="font-body text-lg text-walnut line-through">
                        ${{ number_format($product->price, 2) }}
                    </span>
                    <span class="section-label bg-mahogany text-white px-2 py-0.5" style="font-size:0.625rem;">Sale</span>
                @else
                    <span class="font-body text-2xl font-500 text-charcoal">
                        ${{ number_format($product->currentPrice(), 2) }}
                    </span>
                @endif
            </div>

            {{-- Short description --}}
            @if($product->description)
                <div class="font-body text-sm text-charcoal/80 leading-relaxed mb-8 prose prose-sm max-w-none">
                    {!! nl2br(e(Str::limit(strip_tags($product->description), 400))) !!}
                </div>
            @endif

            {{-- ── Variant Selector ── --}}
            @if($product->variants->isNotEmpty())
                <div class="mb-8">
                    <div class="flex items-center justify-between mb-3">
                        <p class="section-label">Select Wood</p>
                        <p class="section-label" x-text="selectedLabel ? selectedLabel : ''"></p>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <template x-for="variant in variants" :key="variant.id">
                            <button
                                @click="selectVariant(variant)"
                                :disabled="variant.stock_qty === 0"
                                class="relative px-4 py-2 font-body text-sm tracking-wide border transition-all duration-150"
                                :class="{
                                    'border-forest-green text-forest-green ring-1 ring-forest-green': selectedId === variant.id,
                                    'border-walnut/40 text-walnut hover:border-charcoal hover:text-charcoal': selectedId !== variant.id && variant.stock_qty > 0,
                                    'opacity-40 cursor-not-allowed border-walnut/20 text-walnut/40': variant.stock_qty === 0
                                }">
                                <span :class="variant.stock_qty === 0 ? 'line-through' : ''" x-text="variant.label"></span>
                                <template x-if="variant.stock_qty > 0 && variant.stock_qty <= variant.low_stock_threshold">
                                    <span class="ml-1 text-xs text-mahogany font-600">Low Stock</span>
                                </template>
                                <template x-if="variant.stock_qty === 0">
                                    <span class="ml-1 text-xs">· Out of Stock</span>
                                </template>
                            </button>
                        </template>
                    </div>
                </div>
            @endif

            {{-- ── Add to Cart Form ── --}}
            <div x-show="inStock" x-cloak>
                <form method="POST" action="{{ route('cart.add') }}" class="space-y-5">
                    @csrf
                    <input type="hidden" name="product_id" value="{{ $product->id }}">
                    <input type="hidden" name="variant_id" x-bind:value="selectedId">

                    {{-- Personalization --}}
                    @if(in_array($product->personalization_type ?? '', ['included', 'addon']))
                        <div>
                            <label class="section-label block mb-2">
                                {{ $product->personalization_prompt ?? 'Personalization' }}
                                @if($product->personalization_type === 'addon' && $product->personalization_price)
                                    <span class="text-walnut"> (+${{ number_format($product->personalization_price, 2) }})</span>
                                @endif
                            </label>
                            <input type="text"
                                   name="personalization_text"
                                   class="form-field"
                                   placeholder="{{ $product->personalization_prompt ?? 'Enter your text...' }}"
                                   maxlength="100">
                        </div>
                    @endif

                    {{-- Quantity --}}
                    <div>
                        <label class="section-label block mb-2">Quantity</label>
                        <input type="number"
                               name="qty"
                               value="1"
                               min="1"
                               max="99"
                               class="form-field w-24 text-center">
                    </div>

                    <button type="submit" class="btn-forest w-full py-4 text-base">
                        Add to Cart
                    </button>
                </form>
            </div>

            {{-- ── Restock Request Form ── --}}
            <div x-show="!inStock" x-cloak>
                <div class="border border-walnut/30 bg-surface p-6 mb-5">
                    <p class="section-label mb-2">Out of Stock</p>
                    <p class="font-body text-sm text-walnut">This variant is currently unavailable. Enter your email and we'll notify you when it's back.</p>
                </div>
                <form method="POST" action="{{ route('restock.store') }}" class="space-y-4">
                    @csrf
                    <input type="hidden" name="variant_id" x-bind:value="selectedId">
                    <input type="email"
                           name="email"
                           placeholder="your@email.com"
                           required
                           class="form-field"
                           value="{{ auth()->user()?->email ?? '' }}">
                    <button type="submit" class="btn-outline w-full">
                        Notify Me When Available
                    </button>
                </form>
            </div>

            {{-- Tags --}}
            @if($product->tags->isNotEmpty())
                <div class="mt-8 pt-6 border-t border-walnut/20">
                    <p class="section-label mb-3">Wood Species & Tags</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach($product->tags as $tag)
                            <a href="{{ route('shop') }}?tag={{ $tag->slug }}"
                               class="font-body text-xs tracking-widest uppercase px-3 py-1.5 border border-walnut/30 text-walnut hover:border-charcoal hover:text-charcoal transition-colors">
                                {{ $tag->name }}
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

        </div>{{-- /x-data variantSelector --}}
    </div>
</div>

{{-- ============================================================ --}}
{{-- REVIEWS --}}
{{-- ============================================================ --}}
<div id="reviews" class="border-t border-walnut/20 py-16 md:py-20">
    <div class="page-container">
        <div class="max-w-3xl">
            <div class="flex items-end justify-between mb-10">
                <div>
                    <p class="section-label mb-2">Customer Reviews</p>
                    @if($reviewCount > 0)
                        <div class="flex items-center gap-3">
                            <div class="flex text-mahogany text-xl">
                                @for($i = 1; $i <= 5; $i++)
                                    <span>{{ $i <= round($avgRating) ? '★' : '☆' }}</span>
                                @endfor
                            </div>
                            <span class="font-body text-sm text-walnut">
                                {{ number_format($avgRating, 1) }} out of 5 · {{ $reviewCount }} {{ Str::plural('review', $reviewCount) }}
                            </span>
                        </div>
                    @else
                        <h2 class="font-heading text-2xl font-light">No reviews yet</h2>
                    @endif
                </div>
            </div>

            @php $approvedReviews = $product->reviews->where('approved', true); @endphp

            @if($approvedReviews->isEmpty())
                <div class="py-8 border-t border-walnut/20">
                    <p class="font-body text-sm text-walnut">Be the first to leave a review.</p>
                </div>
            @else
                <div class="space-y-8">
                    @foreach($approvedReviews as $review)
                        <div class="border-t border-walnut/20 pt-8">
                            <div class="flex items-start justify-between mb-3">
                                <div>
                                    <p class="font-body text-sm font-600 text-charcoal mb-0.5">
                                        {{ $review->reviewer_name ?? 'Verified Buyer' }}
                                    </p>
                                    <div class="flex text-mahogany text-sm" aria-label="{{ $review->rating }} stars">
                                        @for($i = 1; $i <= 5; $i++)
                                            <span>{{ $i <= $review->rating ? '★' : '☆' }}</span>
                                        @endfor
                                    </div>
                                </div>
                                <p class="section-label">
                                    {{ \Carbon\Carbon::parse($review->created_at)->format('M j, Y') }}
                                </p>
                            </div>
                            @if($review->title)
                                <h4 class="font-body text-sm font-600 mb-2">{{ $review->title }}</h4>
                            @endif
                            <p class="font-body text-sm text-charcoal/80 leading-relaxed">{{ $review->body }}</p>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>

{{-- ============================================================ --}}
{{-- RELATED PRODUCTS --}}
{{-- ============================================================ --}}
@if($relatedProducts->isNotEmpty())
    <div class="border-t border-walnut/20 py-16 md:py-20 bg-surface">
        <div class="page-container">
            <p class="section-label mb-3">You Might Also Like</p>
            <h2 class="font-heading text-3xl font-light text-charcoal mb-10">Related Pieces</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6 md:gap-8">
                @foreach($relatedProducts->take(4) as $product)
                    @include('components.product-card', ['product' => $product])
                @endforeach
            </div>
        </div>
    </div>
@endif

@endsection

