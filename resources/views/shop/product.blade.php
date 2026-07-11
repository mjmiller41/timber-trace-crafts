@extends('layouts.app')

@section('title', $product->name)
@section('meta_description', Str::limit(strip_tags($product->description ?? $product->name), 155))
@section('og_type', 'product')

@section('content')

@php
    $images = $product->media
        ->filter(fn($m) => $m->media && str_contains($m->media->mime_type ?? '', 'image'))
        ->map(fn($m) => $m->media->url())
        ->values()
        ->toArray();

    $videos = $product->media
        ->filter(fn($m) => $m->media && str_contains($m->media->mime_type ?? '', 'video'))
        ->map(fn($m) => $m->media->url())
        ->values()
        ->toArray();

    $allMedia = collect($images)->map(fn($u) => [
            'type'    => 'image',
            'url'     => $u,
            'webp_url' => preg_replace('/\.(png|jpe?g)$/i', '.webp', $u),
        ])
        ->concat(collect($videos)->map(fn($u) => ['type' => 'video', 'url' => $u, 'webp_url' => null]))
        ->values()
        ->toArray();

    $variantsJson = $product->variants->map(fn($v) => [
        'id'                  => $v->id,
        'label'               => $v->label,
        'stock_qty'           => $v->stock_qty,
        'low_stock_threshold' => $v->low_stock_threshold,
        // Absolute per-variant price override, or null to use the product price.
        'price'               => $v->price !== null ? (float) $v->price : null,
    ])->toJson();

    $pricingJson = json_encode([
        'price'        => (float) $product->price,
        'salePrice'    => $product->sale_price !== null ? (float) $product->sale_price : null,
        'currentPrice' => (float) $product->currentPrice(),
    ]);

    $avgRating = $product->reviews->where('status', 'approved')->avg('rating');
    $reviewCount = $product->reviews->where('status', 'approved')->count();
    $ogImage = $images[0] ?? asset('images/og-default.jpg');

    // Card data for the "Recently viewed" strip (persisted client-side in localStorage).
    $recentlyViewedCard = [
        'slug'         => $product->slug,
        'name'         => $product->name,
        'url'          => url('/product/'.$product->slug),
        'image'        => $product->primary_image_url,
        'image_webp'   => $product->primary_image_url
            ? preg_replace('/\.(png|jpe?g)$/i', '.webp', $product->primary_image_url)
            : null,
        'price'        => (float) $product->price,
        'sale_price'   => $product->sale_price !== null ? (float) $product->sale_price : null,
        'category'     => $product->category?->name,
        'out_of_stock' => $product->isOutOfStock(),
    ];

    // Social sharing (Facebook + Pinterest use real web share intents;
    // Instagram has none, so its button copies the link client-side).
    $shareUrl = url()->current();
    $shareFacebook = 'https://www.facebook.com/sharer/sharer.php?u='.urlencode($shareUrl);
    $sharePinterest = 'https://www.pinterest.com/pin/create/button/?'.http_build_query([
        'url' => $shareUrl,
        'media' => $ogImage,
        'description' => $product->name,
    ]);
@endphp

@push('head')
<meta property="og:image" content="{{ $ogImage }}">
<meta name="twitter:image" content="{{ $ogImage }}">
@endpush

@push('schema')
@php
    $productSchema = [
        '@context'    => 'https://schema.org',
        '@type'       => 'Product',
        'name'        => $product->name,
        'description' => Str::limit(strip_tags($product->description ?? ''), 500),
        'image'       => $images,
        'url'         => url()->current(),
        'sku'         => $product->sku ?? (string) $product->id,
        'brand'       => ['@type' => 'Brand', 'name' => $siteName],
        'offers'      => [
            '@type'          => 'Offer',
            'url'            => url()->current(),
            'priceCurrency'  => 'USD',
            'price'          => number_format($product->currentPrice(), 2, '.', ''),
            'priceValidUntil' => now()->addYear()->toDateString(),
            'itemCondition'  => 'https://schema.org/NewCondition',
            'availability'   => $product->availabilitySchemaUrl(),
            'seller'         => ['@id' => url('/').'#organization'],
        ],
    ];
    if ($reviewCount > 0) {
        $productSchema['aggregateRating'] = [
            '@type'       => 'AggregateRating',
            'ratingValue' => number_format($avgRating, 1),
            'reviewCount' => $reviewCount,
            'bestRating'  => 5,
            'worstRating' => 1,
        ];
    }
    $breadcrumbSchema = [
        '@context'        => 'https://schema.org',
        '@type'           => 'BreadcrumbList',
        'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => url('/')],
            ['@type' => 'ListItem', 'position' => 2, 'name' => 'Shop', 'item' => route('shop')],
            ['@type' => 'ListItem', 'position' => 3, 'name' => $product->name, 'item' => url()->current()],
        ],
    ];
@endphp
<script type="application/ld+json">{!! json_encode($productSchema) !!}</script>
<script type="application/ld+json">{!! json_encode($breadcrumbSchema) !!}</script>
@endpush

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
                    <picture class="w-full h-full">
                        <source x-bind:srcset="currentItem.webp_url" type="image/webp">
                        <img :src="currentItem.url"
                             :alt="'{{ $product->name }}'"
                             class="w-full h-full object-cover">
                    </picture>
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
                                <picture class="w-full h-full">
                                    <source x-bind:srcset="item.webp_url" type="image/webp">
                                    <img :src="item.url" alt="" class="w-full h-full object-cover">
                                </picture>
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
        <div x-data="variantSelector({{ $variantsJson }}, {{ $pricingJson }})">

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

            {{-- Price — server-rendered as static text so a no-JS/text-only agent can
                 read the current price (and the struck-through original on sale);
                 Alpine hydrates the SAME nodes and keeps them in sync with the
                 selected variant. The price is intentionally OUTSIDE any in-stock
                 gate, so it is never hidden on a sold-out product. --}}
            @php
                $isOnSale = $product->isOnSale();
                $fmtCurrent = '$'.number_format((float) $product->currentPrice(), 2);
                $fmtRegular = '$'.number_format((float) $product->price, 2);
            @endphp
            <div class="flex items-baseline gap-3 mb-4">
                {{-- Sale price + struck-through original (shown when the product is on sale). --}}
                <span x-show="onSale" @unless($isOnSale) style="display:none;" @endunless
                      class="font-body text-2xl font-600 text-mahogany"
                      x-text="formatPrice(displayPrice)">{{ $fmtCurrent }}</span>
                <span x-show="onSale" @unless($isOnSale) style="display:none;" @endunless
                      class="font-body text-lg text-walnut line-through"
                      x-text="formatPrice(compareAtPrice)">{{ $fmtRegular }}</span>
                <span x-show="onSale" class="section-label bg-mahogany text-white px-2 py-0.5"
                      style="font-size:0.625rem;{{ $isOnSale ? '' : 'display:none;' }}">Sale</span>
                {{-- Regular price (shown when not on sale). --}}
                <span x-show="!onSale" @if($isOnSale) style="display:none;" @endif
                      class="font-body text-2xl font-500 text-charcoal"
                      x-text="formatPrice(displayPrice)">{{ $fmtCurrent }}</span>
            </div>

            {{-- Availability — server-rendered stock state (never hidden on OOS);
                 Alpine refines it to the selected variant on hydration. --}}
            <p class="section-label mb-6 {{ $product->isOutOfStock() ? 'text-mahogany' : 'text-forest-green' }}"
               x-text="inStock ? (lowStock ? 'Low Stock' : 'In Stock') : 'Out of Stock'">{{ $product->isOutOfStock() ? 'Out of Stock' : 'In Stock' }}</p>

            {{-- Short description --}}
            @if($product->description)
                <div class="font-body text-sm text-charcoal/80 leading-relaxed mb-8 prose prose-sm max-w-none">
                    {!! nl2br(e(strip_tags($product->description))) !!}
                </div>
            @endif

            {{-- ── Variant Selector ── --}}
            @if($product->variants->isNotEmpty())
                <div class="mb-8">
                    <div class="flex items-center justify-between mb-3">
                        <p class="section-label">Select Wood</p>
                        <p class="section-label" x-text="selectedLabel ? selectedLabel : ''"></p>
                    </div>

                    {{-- Each variant button is server-rendered (label + stock state as
                         static text) so a text-only agent can read per-variant
                         availability without executing Alpine. Alpine hydrates the SAME
                         nodes: selection ring, disabled state, and hover styling stay
                         reactive via the bindings below, keyed to each variant's id. --}}
                    <div class="flex flex-wrap gap-2">
                        @foreach($product->variants as $variant)
                            @php
                                $vOut = $variant->stock_qty <= 0;
                                $vLow = ! $vOut && $variant->stock_qty <= ($variant->low_stock_threshold ?? 5);
                                $vIdx = $loop->index;
                            @endphp
                            <button
                                @click="selectVariant(variants[{{ $vIdx }}])"
                                :disabled="variants[{{ $vIdx }}].stock_qty === 0"
                                @if($vOut) disabled @endif
                                class="relative px-4 py-2 font-body text-sm tracking-wide border transition-all duration-150"
                                :class="{
                                    'border-forest-green text-forest-green ring-1 ring-forest-green': selectedId === {{ $variant->id }},
                                    'border-walnut/40 text-walnut hover:border-charcoal hover:text-charcoal': selectedId !== {{ $variant->id }} && variants[{{ $vIdx }}].stock_qty > 0,
                                    'opacity-40 cursor-not-allowed border-walnut/20 text-walnut/40': variants[{{ $vIdx }}].stock_qty === 0
                                }">
                                <span :class="variants[{{ $vIdx }}].stock_qty === 0 ? 'line-through' : ''"
                                      class="{{ $vOut ? 'line-through' : '' }}">{{ $variant->label }}</span>
                                @if($vOut)
                                    <span class="ml-1 text-xs">· Out of Stock</span>
                                @elseif($vLow)
                                    <span class="ml-1 text-xs text-mahogany font-600">In Stock · Low Stock</span>
                                @else
                                    <span class="ml-1 text-xs text-forest-green">In Stock</span>
                                @endif
                            </button>
                        @endforeach
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

                    <button type="submit" class="btn-forest w-full py-4 text-base" data-umami-event="add_to_cart">
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

            {{-- ── Add to Wishlist ── --}}
            <div class="mt-4">
                @auth
                    <form method="POST" action="{{ route('account.wishlist.add') }}">
                        @csrf
                        <input type="hidden" name="variant_id" x-bind:value="selectedId">
                        <button type="submit"
                                :disabled="!selectedId"
                                class="btn-outline w-full flex items-center justify-center gap-2 disabled:opacity-40 disabled:cursor-not-allowed">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" />
                            </svg>
                            Add to Wishlist
                        </button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="btn-outline w-full flex items-center justify-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" />
                        </svg>
                        Log in to Save to Wishlist
                    </a>
                @endauth
            </div>

            {{-- ── Share ── --}}
            <div x-data="shareButtons(@js($shareUrl))" class="mt-6 flex items-center gap-3">
                <p class="section-label">Share</p>
                <div class="flex items-center gap-2">
                    {{-- Facebook --}}
                    <a href="{{ $shareFacebook }}" target="_blank" rel="noopener noreferrer"
                       aria-label="Share on Facebook"
                       class="w-9 h-9 flex items-center justify-center border border-walnut/30 text-walnut hover:border-charcoal hover:text-charcoal transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-4 h-4">
                            <path d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987H7.898V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.891h-2.33v6.987C18.343 21.128 22 16.991 22 12z"/>
                        </svg>
                    </a>
                    {{-- Pinterest --}}
                    <a href="{{ $sharePinterest }}" target="_blank" rel="noopener noreferrer"
                       aria-label="Save to Pinterest"
                       class="w-9 h-9 flex items-center justify-center border border-walnut/30 text-walnut hover:border-charcoal hover:text-charcoal transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-4 h-4">
                            <path d="M12.017 0C5.396 0 .029 5.367.029 11.987c0 5.079 3.158 9.417 7.618 11.162-.105-.949-.199-2.403.041-3.439.219-.937 1.406-5.957 1.406-5.957s-.359-.72-.359-1.781c0-1.663.967-2.911 2.168-2.911 1.024 0 1.518.769 1.518 1.688 0 1.029-.653 2.567-.992 3.992-.285 1.193.6 2.165 1.775 2.165 2.128 0 3.768-2.245 3.768-5.487 0-2.861-2.063-4.869-5.008-4.869-3.41 0-5.409 2.562-5.409 5.199 0 1.033.394 2.143.889 2.741.099.12.112.225.085.345-.09.375-.293 1.199-.334 1.363-.053.225-.172.271-.401.165-1.495-.69-2.433-2.878-2.433-4.646 0-3.776 2.748-7.252 7.92-7.252 4.158 0 7.392 2.967 7.392 6.923 0 4.135-2.607 7.462-6.233 7.462-1.214 0-2.354-.629-2.758-1.379l-.749 2.848c-.269 1.045-1.004 2.352-1.498 3.146 1.123.345 2.306.535 3.55.535 6.607 0 11.985-5.365 11.985-11.987C24.003 5.367 18.636.001 12.017.001z"/>
                        </svg>
                    </a>
                    {{-- Instagram (copies link — IG has no web share intent) --}}
                    <button type="button" @click="copyLink()"
                            aria-label="Copy link to share on Instagram"
                            class="w-9 h-9 flex items-center justify-center border border-walnut/30 text-walnut hover:border-charcoal hover:text-charcoal transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-4 h-4">
                            <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/>
                        </svg>
                    </button>
                </div>
                {{-- Copy confirmation --}}
                <span x-show="copied" x-cloak x-transition
                      class="font-body text-xs text-forest-green">Link copied — paste into your Instagram story or bio</span>
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
@php
    $approvedReviews = $product->reviews->where('status', 'approved');
    $reviewPhotos = $approvedReviews->filter(fn ($r) => $r->hasImage())->values();
@endphp
<div id="reviews" class="border-t border-walnut/20 py-16 md:py-20" x-data="{ lightbox: null }">
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

            {{-- Customer photos strip — pulls every approved review that carries a
                 photo into a scannable gallery above the written reviews. --}}
            @if($reviewPhotos->isNotEmpty())
                <div class="mb-12">
                    <p class="section-label mb-3">Photos from customers</p>
                    <div class="flex gap-3 overflow-x-auto pb-2">
                        @foreach($reviewPhotos as $photo)
                            <button
                                type="button"
                                x-on:click="lightbox = @js($photo->image_url)"
                                class="flex-shrink-0 w-24 h-24 md:w-28 md:h-28 overflow-hidden border border-walnut/20 group"
                                aria-label="View customer photo from {{ $photo->reviewer_name }}"
                            >
                                <img
                                    src="{{ $photo->image_url }}"
                                    alt="Customer photo of {{ $product->name }} by {{ $photo->reviewer_name }}"
                                    loading="lazy"
                                    class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105"
                                >
                            </button>
                        @endforeach
                    </div>
                </div>
            @endif

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
                                        {{ $review->reviewer_name }}
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
                            @if($review->hasImage())
                                <button
                                    type="button"
                                    x-on:click="lightbox = @js($review->image_url)"
                                    class="mt-4 block w-28 h-28 overflow-hidden border border-walnut/20 group"
                                    aria-label="View photo from this review"
                                >
                                    <img
                                        src="{{ $review->image_url }}"
                                        alt="Customer photo of {{ $product->name }} by {{ $review->reviewer_name }}"
                                        loading="lazy"
                                        class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105"
                                    >
                                </button>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- Lightbox for review photos --}}
    <div
        x-show="lightbox"
        x-cloak
        x-transition.opacity
        x-on:click="lightbox = null"
        x-on:keydown.escape.window="lightbox = null"
        class="fixed inset-0 z-[60] flex items-center justify-center bg-black/80 p-4"
        style="display: none;"
        role="dialog"
        aria-modal="true"
        aria-label="Review photo"
    >
        <img :src="lightbox" alt="Customer review photo, enlarged" class="max-w-full max-h-[90vh] object-contain shadow-2xl">
        <button
            type="button"
            x-on:click="lightbox = null"
            class="absolute top-4 right-4 text-white/80 hover:text-white"
            aria-label="Close photo"
        >
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
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

{{-- ============================================================ --}}
{{-- RECENTLY VIEWED (client-side, localStorage) --}}
{{-- ============================================================ --}}
<div x-data="recentlyViewed({{ json_encode($recentlyViewedCard) }})"
     x-show="items.length > 0"
     x-cloak
     class="border-t border-walnut/20 py-16 md:py-20">
    <div class="page-container">
        <p class="section-label mb-3">Recently Viewed</p>
        <h2 class="font-heading text-3xl font-light text-charcoal mb-10">Pieces You've Looked At</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6 md:gap-8">
            <template x-for="item in items.slice(0, 4)" :key="item.slug">
                <article class="group">
                    <a :href="item.url" class="block" :aria-label="item.name">
                        {{-- Image --}}
                        <div class="kerf-frame relative overflow-hidden bg-surface aspect-square mb-3">
                            <template x-if="item.image">
                                <picture>
                                    <source :srcset="item.image_webp" type="image/webp">
                                    <img :src="item.image" :alt="item.name"
                                         class="w-full h-full object-cover transition-transform duration-700 ease-out group-hover:scale-[1.04]"
                                         loading="lazy">
                                </picture>
                            </template>
                            <template x-if="!item.image">
                                <div class="w-full h-full flex items-center justify-center" style="background-color: #EDE8DF;">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor" class="w-12 h-12" style="color: #C4B9AA;">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5z" />
                                    </svg>
                                </div>
                            </template>
                            <template x-if="item.out_of_stock">
                                <div class="absolute top-2.5 left-2.5"
                                     style="background-color: rgba(51,51,51,0.85); color: #F4F1EA; font-size: 0.625rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.12em; padding: 3px 8px; font-family: var(--font-body);">
                                    Out of Stock
                                </div>
                            </template>
                            <template x-if="!item.out_of_stock && onSale(item)">
                                <div class="absolute top-2.5 left-2.5"
                                     style="background-color: #4A2C11; color: #F4F1EA; font-size: 0.625rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.12em; padding: 3px 8px; font-family: var(--font-body);">
                                    Sale
                                </div>
                            </template>
                        </div>
                        {{-- Info --}}
                        <div>
                            <p class="section-label mb-1" style="font-size: 0.625rem;" x-show="item.category" x-text="item.category"></p>
                            <h3 class="font-heading leading-snug mb-1.5 transition-colors duration-200 group-hover:text-forest-green"
                                style="font-size: 1rem;" x-text="item.name"></h3>
                            <div class="flex items-center gap-2">
                                <template x-if="onSale(item)">
                                    <span style="font-size: 0.9375rem; font-weight: 600; color: #4A2C11; font-family: var(--font-body);" x-text="formatPrice(item.sale_price)"></span>
                                </template>
                                <template x-if="onSale(item)">
                                    <span style="font-size: 0.8125rem; color: #8C7B6C; text-decoration: line-through; font-family: var(--font-body);" x-text="formatPrice(item.price)"></span>
                                </template>
                                <template x-if="!onSale(item)">
                                    <span style="font-size: 0.9375rem; font-weight: 500; color: #333333; font-family: var(--font-body);" x-text="formatPrice(item.price)"></span>
                                </template>
                            </div>
                        </div>
                    </a>
                </article>
            </template>
        </div>
    </div>
</div>

@endsection

