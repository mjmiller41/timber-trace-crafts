@extends('layouts.app')

@section('title', 'Gallery — Finished Pieces')
@section('meta_description', 'A gallery of finished laser-cut and engraved pieces from the Timber Trace Crafts workshop — wooden jewelry, boxes, tumblers, and custom gifts.')

@push('schema')
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'CollectionPage',
    'name' => 'Gallery — Finished Pieces',
    'description' => 'A gallery of finished laser-cut and engraved pieces from the Timber Trace Crafts workshop.',
    'url' => route('gallery.index'),
    'isPartOf' => ['@id' => url('/').'#website'],
    'about' => 'Handcrafted laser-cut wooden jewelry, boxes, and engraved gifts.',
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
</script>
@endpush

@section('content')

{{-- ============================================================ --}}
{{-- PAGE HEADER --}}
{{-- ============================================================ --}}
<div class="border-b border-walnut/20 py-12 md:py-16">
    <div class="page-container">
        <p class="section-label mb-4">The Workshop</p>
        <h1 class="font-heading text-5xl md:text-6xl font-light text-charcoal">Gallery</h1>
        <p class="font-body text-sm text-walnut mt-4 max-w-xl">
            A look at finished pieces from the studio — every one cut, engraved, and
            hand-finished. Tap any piece to view it in the shop.
        </p>
    </div>
</div>

{{-- ============================================================ --}}
{{-- GALLERY GRID (masonry via CSS columns) --}}
{{-- ============================================================ --}}
<div class="page-container py-14 md:py-20">
    @if($items->isEmpty())
        <div class="py-24 text-center">
            <p class="font-heading text-3xl font-light text-charcoal mb-4">Nothing to show yet.</p>
            <p class="font-body text-sm text-walnut">
                Finished pieces will appear here as the catalog grows.
                <a href="{{ route('shop') }}" class="underline hover:text-charcoal">Browse the shop →</a>
            </p>
        </div>
    @else
        <div class="[column-gap:1rem] md:[column-gap:1.5rem] columns-2 md:columns-3 lg:columns-4">
            @foreach($items as $item)
                @php $webp = preg_replace('/\.(png|jpe?g)$/i', '.webp', $item['url']); @endphp
                <a
                    href="{{ route('product.show', $item['product_slug']) }}"
                    class="group relative block mb-4 md:mb-6 break-inside-avoid overflow-hidden kerf-frame bg-surface"
                    aria-label="{{ $item['product_name'] }}"
                >
                    <picture>
                        @if($webp !== $item['url'])
                            <source srcset="{{ $webp }}" type="image/webp">
                        @endif
                        <img
                            src="{{ $item['url'] }}"
                            alt="{{ $item['alt'] }}"
                            loading="lazy"
                            class="w-full h-auto object-cover transition-transform duration-700 ease-out group-hover:scale-[1.04]"
                        >
                    </picture>
                    <span class="engrave-reveal" aria-hidden="true"></span>

                    {{-- Overlay caption on hover --}}
                    <div
                        class="absolute inset-x-0 bottom-0 p-3 opacity-0 group-hover:opacity-100 transition-opacity duration-300"
                        style="background: linear-gradient(to top, rgba(27,23,18,0.85), rgba(27,23,18,0));"
                    >
                        <p class="font-body text-xs font-600 text-white leading-snug">{{ $item['product_name'] }}</p>
                        @if($item['kind'] === 'customer')
                            <p class="font-body text-white/70" style="font-size: 0.625rem;">Customer photo</p>
                        @endif
                    </div>
                </a>
            @endforeach
        </div>

        <div class="mt-14 text-center">
            <a href="{{ route('shop') }}" class="btn-outline">Shop All Pieces</a>
        </div>
    @endif
</div>

@endsection
