{{--
    Product card component.
    Usage: @include('components.product-card', ['product' => $product])
    Accepts Eloquent model or array with keys:
      slug, name, primary_image, price, sale_price, category_name, out_of_stock
--}}

@php
    $slug         = is_array($product) ? ($product['slug'] ?? '#') : $product->slug;
    $name         = is_array($product) ? ($product['name'] ?? '') : $product->name;
    $image        = is_array($product) ? ($product['primary_image'] ?? null) : ($product->primary_image_url ?? null);
    $imageWebp    = $image ? preg_replace('/\.(png|jpe?g)$/i', '.webp', $image) : null;
    $price        = is_array($product) ? ($product['price'] ?? null) : $product->price;
    $salePrice    = is_array($product) ? ($product['sale_price'] ?? null) : ($product->sale_price ?? null);
    $category     = is_array($product) ? ($product['category_name'] ?? null) : ($product->category?->name ?? null);
    $outOfStock   = is_array($product) ? ($product['out_of_stock'] ?? false) : $product->isOutOfStock();
    $productUrl   = url('/product/' . $slug);
    $onSale       = $salePrice && $salePrice < $price;
@endphp

<article class="group">
    <a href="{{ $productUrl }}" class="block" aria-label="{{ $name }}">

        {{-- Image container --}}
        <div class="kerf-frame relative overflow-hidden bg-surface aspect-square mb-3">
            @if($image)
                <picture>
                    @if($imageWebp)
                        <source srcset="{{ $imageWebp }}" type="image/webp">
                    @endif
                    <img
                        src="{{ $image }}"
                        alt="{{ $name }}"
                        class="w-full h-full object-cover transition-transform duration-700 ease-out group-hover:scale-[1.04]"
                        loading="lazy"
                    >
                </picture>
                {{-- Engrave-reveal: a laser-trace light pass on hover --}}
                <span class="engrave-reveal" aria-hidden="true"></span>
            @else
                {{-- Placeholder when no image --}}
                <div class="w-full h-full flex items-center justify-center" style="background-color: #EDE8DF;">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor" class="w-12 h-12" style="color: #C4B9AA;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                    </svg>
                </div>
            @endif

            {{-- Out of Stock overlay badge --}}
            @if($outOfStock)
                <div
                    class="absolute top-2.5 left-2.5"
                    style="background-color: rgba(51,51,51,0.85); color: #F4F1EA; font-size: 0.625rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.12em; padding: 3px 8px; font-family: var(--font-body);"
                >
                    Out of Stock
                </div>
            @elseif($onSale)
                <div
                    class="absolute top-2.5 left-2.5"
                    style="background-color: #4A2C11; color: #F4F1EA; font-size: 0.625rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.12em; padding: 3px 8px; font-family: var(--font-body);"
                >
                    Sale
                </div>
            @endif
        </div>

        {{-- Product info --}}
        <div>
            {{-- Category --}}
            @if($category)
                <p class="section-label mb-1" style="font-size: 0.625rem;">{{ $category }}</p>
            @endif

            {{-- Name --}}
            <h3
                class="font-heading leading-snug mb-1.5 transition-colors duration-200 group-hover:text-forest-green"
                style="font-size: 1rem;"
            >{{ $name }}</h3>

            {{-- Price --}}
            <div class="flex items-center gap-2">
                @if($onSale)
                    <span
                        style="font-size: 0.9375rem; font-weight: 600; color: var(--color-mahogany); font-family: var(--font-body);"
                    >${{ number_format($salePrice, 2) }}</span>
                    <span
                        style="font-size: 0.8125rem; color: var(--color-walnut); text-decoration: line-through; font-family: var(--font-body);"
                    >${{ number_format($price, 2) }}</span>
                @elseif($price !== null)
                    <span
                        style="font-size: 0.9375rem; font-weight: 500; color: var(--color-charcoal); font-family: var(--font-body);"
                    >${{ number_format($price, 2) }}</span>
                @endif

                @if($outOfStock)
                    <span
                        style="font-size: 0.6875rem; font-weight: 600; color: var(--color-walnut); font-family: var(--font-body);"
                    >— Sold Out</span>
                @endif
            </div>
        </div>

    </a>
</article>
