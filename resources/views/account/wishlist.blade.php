@extends('layouts.app')

@section('title', 'My Wishlist')

@section('content')
<div class="page-container py-12">

    <div class="mb-8 pb-6 border-b border-walnut/20">
        <p class="section-label mb-2">Account</p>
        <h1 class="font-heading text-3xl font-light">My Wishlist</h1>
    </div>

    <div class="flex flex-col lg:flex-row gap-10">

        @include('account.partials.sidebar')

        <div class="flex-1 min-w-0">

            @if($items->isEmpty())
                <div class="card p-12 text-center">
                    <p class="font-heading text-xl font-light mb-3">Your wishlist is empty</p>
                    <p class="text-sm text-walnut mb-6">Save pieces you love and come back to them anytime.</p>
                    <a href="{{ route('shop') }}" class="btn-primary">
                        Browse the Shop
                    </a>
                </div>
            @else
                <p class="text-sm text-walnut mb-6">{{ $items->count() }} {{ $items->count() === 1 ? 'item' : 'items' }} saved</p>

                <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-5">
                    @foreach($items as $item)
                        @php
                            $variant  = $item->variant;
                            $product  = $variant?->product;
                            $image    = $product?->primary_image_url ?? null;
                            $imageWebp = $image ? preg_replace('/\.(png|jpe?g)$/i', '.webp', $image) : null;
                            $name     = $product?->name ?? 'Product';
                            $slug     = $product?->slug ?? '#';
                            $price    = $variant?->price ?? $product?->price ?? null;
                        @endphp
                        <article class="group">

                            {{-- Image --}}
                            <div class="relative overflow-hidden bg-surface aspect-square mb-3">
                                <a href="{{ url('/product/' . $slug) }}" aria-label="{{ $name }}">
                                    @if($image)
                                        <picture>
                                            @if($imageWebp)
                                                <source srcset="{{ $imageWebp }}" type="image/webp">
                                            @endif
                                            <img
                                                src="{{ $image }}"
                                                alt="{{ $name }}"
                                                class="w-full h-full object-cover transition-opacity duration-300 group-hover:opacity-90"
                                                loading="lazy"
                                            >
                                        </picture>
                                    @else
                                        <div class="w-full h-full flex items-center justify-center" style="background-color: #EDE8DF;">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor" class="w-12 h-12" style="color: #C4B9AA;">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                                            </svg>
                                        </div>
                                    @endif
                                </a>

                                {{-- Remove button overlay --}}
                                <form
                                    method="POST"
                                    action="{{ route('account.wishlist.remove', $item->product_variant_id) }}"
                                    class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity"
                                    onsubmit="return confirm('Remove from wishlist?')"
                                >
                                    @csrf
                                    @method('DELETE')
                                    <button
                                        type="submit"
                                        title="Remove from wishlist"
                                        class="w-7 h-7 flex items-center justify-center transition-opacity hover:opacity-75"
                                        style="background-color: rgba(255,255,255,0.9);"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4" style="color: #333333;">
                                            <path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" />
                                        </svg>
                                    </button>
                                </form>
                            </div>

                            {{-- Info --}}
                            <div>
                                <a href="{{ url('/product/' . $slug) }}" class="block">
                                    @if($product?->category)
                                        <p class="section-label mb-1" style="font-size: 0.625rem;">{{ $product->category->name }}</p>
                                    @endif
                                    <h3 class="font-heading font-light leading-snug mb-1.5 transition-colors duration-200 group-hover:text-forest-green" style="font-size: 1rem;">
                                        {{ $name }}
                                    </h3>
                                    @if($price !== null)
                                        <p style="font-size: 0.9375rem; font-weight: 500; color: #333333; font-family: var(--font-body);">
                                            ${{ number_format($price, 2) }}
                                        </p>
                                    @endif
                                </a>

                                {{-- Remove link (visible always on mobile) --}}
                                <form
                                    method="POST"
                                    action="{{ route('account.wishlist.remove', $item->product_variant_id) }}"
                                    class="mt-2 lg:hidden"
                                >
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-xs font-semibold uppercase tracking-widest text-walnut hover:text-charcoal transition-colors">
                                        Remove
                                    </button>
                                </form>
                            </div>

                        </article>
                    @endforeach
                </div>
            @endif

        </div>
    </div>
</div>
@endsection
