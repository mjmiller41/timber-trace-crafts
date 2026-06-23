@extends('layouts.app')

@section('title', 'About Us')
@section('meta_description', 'Timber Trace Crafts is a veteran-owned laser engraving studio in Avon Park, Florida, founded by Michael J. Miller. Handcrafted wooden jewelry, boxes, and gifts made with precision and care.')

@section('content')

{{-- ============================================================ --}}
{{-- HERO --}}
{{-- ============================================================ --}}
<div class="relative overflow-hidden bg-charcoal text-oak-sand">
    <div class="page-container py-24 md:py-36 relative z-10">
        <p class="section-label text-walnut mb-6">Our Story</p>
        <h1 class="font-heading text-5xl md:text-6xl lg:text-7xl font-light leading-tight max-w-3xl">
            Where Tradition<br>Meets Precision
        </h1>
        <p class="font-body text-oak-sand/70 text-lg mt-6 max-w-xl leading-relaxed">
            A one-person woodworking &amp; engraving studio rooted in craftsmanship, discipline, and a passion for the grain beneath the saw.
        </p>
    </div>
    {{-- Background image --}}
    <div class="absolute inset-0 opacity-20">
        <img src="{{ asset('storage/products/workshop-1.jpg') }}"
             alt="Timber Trace Crafts workshop"
             class="w-full h-full object-cover object-center">
    </div>
</div>

{{-- ============================================================ --}}
{{-- FOUNDER INTRO --}}
{{-- ============================================================ --}}
<div class="page-container py-20 md:py-28">
    <div class="grid md:grid-cols-2 gap-16 items-center">
        <div>
            <p class="section-label text-forest-green mb-4">About Timber Trace Crafts</p>
            <h2 class="font-heading text-3xl md:text-4xl font-light text-charcoal mb-6 leading-snug">
                Built on Dedication, Precision, and Genuine Artistry
            </h2>
            <div class="space-y-4 font-body text-charcoal/80 leading-relaxed">
                <p>
                    Located in the heart of Avon Park, Florida, Timber Trace Crafts is a dedicated, one-person woodworking and engraving studio. Founded and operated by Michael J. Miller, a disabled veteran who served during Desert Storm, the shop is built on the enduring principles of dedication, meticulous precision, and genuine artistry.
                </p>
            </div>
        </div>
        <div>
            <img src="{{ asset('storage/products/workshop-2.jpg') }}"
                 alt="Michael J. Miller at work in his studio"
                 class="w-full aspect-square object-cover object-center">
        </div>
    </div>
</div>

{{-- ============================================================ --}}
{{-- DIVIDER IMAGE --}}
{{-- ============================================================ --}}
<div class="w-full h-72 md:h-96 overflow-hidden">
    <img src="{{ asset('storage/products/lifestyle-1.png') }}"
         alt="Finished pieces from Timber Trace Crafts"
         class="w-full h-full object-cover object-center">
</div>

{{-- ============================================================ --}}
{{-- WHERE PRECISION MEETS TRADITION --}}
{{-- ============================================================ --}}
<div class="bg-charcoal text-oak-sand">
    <div class="page-container py-20 md:py-28">
        <div class="grid md:grid-cols-2 gap-16 items-center">
            <div class="order-2 md:order-1">
                <img src="{{ asset('storage/products/workshop-3.jpg') }}"
                     alt="Laser engraver in action at Timber Trace Crafts"
                     class="w-full aspect-square object-cover object-center">
            </div>
            <div class="order-1 md:order-2">
                <p class="section-label text-walnut mb-4">Philosophy</p>
                <h2 class="font-heading text-3xl md:text-4xl font-light text-oak-sand mb-6 leading-snug">
                    Where Precision Meets Tradition
                </h2>
                <div class="space-y-4 font-body text-oak-sand/75 leading-relaxed">
                    <p>
                        At Timber Trace Crafts, the philosophy is simple: technology should enhance, not replace, the artisan's touch. The shop's unique process seamlessly blends modern diode laser precision with time-honored, hand-crafted quality.
                    </p>
                    <p>
                        Whether it is intricate marquetry, detailed inlay work, delicate wooden earrings, or custom decorative boxes, every single piece is designed, engraved, and hand-finished in-house. This careful balance ensures that the exacting detail of a laser is matched perfectly with the warmth and character of traditional woodworking.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ============================================================ --}}
{{-- MATERIALS --}}
{{-- ============================================================ --}}
<div class="page-container py-20 md:py-28">
    <div class="grid md:grid-cols-2 gap-16 items-center">
        <div>
            <p class="section-label text-forest-green mb-4">Materials</p>
            <h2 class="font-heading text-3xl md:text-4xl font-light text-charcoal mb-6 leading-snug">
                Commitment to Quality Materials
            </h2>
            <div class="space-y-4 font-body text-charcoal/80 leading-relaxed">
                <p>
                    Great craftsmanship is only as good as the canvas it is built upon. Timber Trace Crafts places a heavy emphasis on rigorous material selection, working exclusively with premium, hand-selected woods.
                </p>
                <p>
                    By utilizing high-quality Maple, durable Baltic Birch plywood, and fine Basswood, the natural grain and texture of the wood are allowed to shine, ensuring that every finished product is as durable as it is beautiful.
                </p>
            </div>
            <div class="mt-8 grid grid-cols-3 gap-4">
                @foreach(['Maple', 'Baltic Birch', 'Basswood'] as $wood)
                <div class="border border-walnut/20 p-4 text-center">
                    <div class="font-heading text-sm font-light text-charcoal/60 uppercase tracking-widest">{{ $wood }}</div>
                </div>
                @endforeach
            </div>
        </div>
        <div class="relative">
            <img src="{{ asset('storage/products/lifestyle-2.png') }}"
                 alt="Premium hand-selected wood materials"
                 class="w-full aspect-square object-cover object-center">
        </div>
    </div>
</div>

{{-- ============================================================ --}}
{{-- VETERAN OWNED --}}
{{-- ============================================================ --}}
<div class="border-t border-walnut/20">
    <div class="page-container py-20 md:py-28">
        <div class="grid md:grid-cols-2 gap-16 items-center">
            <div>
                <img src="{{ asset('storage/products/workshop-4.jpg') }}"
                     alt="Timber Trace Crafts finished products"
                     class="w-full aspect-square object-cover object-center">
            </div>
            <div>
                <p class="section-label text-forest-green mb-4">Veteran Owned &amp; Operated</p>
                <h2 class="font-heading text-3xl md:text-4xl font-light text-charcoal mb-6 leading-snug">
                    Proudly Serving You, Just as We Once Served Our Country
                </h2>
                <div class="space-y-4 font-body text-charcoal/80 leading-relaxed">
                    <p>
                        As a disabled veteran-owned business, Timber Trace Crafts is deeply rooted in a commitment to excellence and service. When you support Timber Trace Crafts, you are directly supporting a local, independent artisan who takes immense pride in bringing heirloom-quality craftsmanship to the community.
                    </p>
                </div>
                <div class="mt-8 inline-flex items-center gap-3 border border-forest-green/30 px-5 py-3">
                    <svg class="w-5 h-5 text-forest-green flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.562.562 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z" />
                    </svg>
                    <span class="font-body text-sm text-charcoal/70 tracking-wide">Disabled Veteran Owned Business — Desert Storm</span>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ============================================================ --}}
{{-- LOCAL CONNECTION --}}
{{-- ============================================================ --}}
<div class="bg-charcoal/5 border-t border-walnut/15">
    <div class="page-container py-16 md:py-20">
        <div class="max-w-2xl mx-auto text-center">
            <p class="section-label text-forest-green mb-4">Avon Park, Florida</p>
            <h2 class="font-heading text-2xl md:text-3xl font-light text-charcoal mb-4">
                Rooted in Community
            </h2>
            <p class="font-body text-charcoal/75 leading-relaxed">
                Timber Trace Crafts is more than a business — it is a passion for local history and heritage. From commemorative design work for local museums to pieces that celebrate Florida's natural beauty, the studio is proud to be woven into the fabric of Avon Park and the surrounding Highlands County community.
            </p>
        </div>
    </div>
</div>

{{-- ============================================================ --}}
{{-- CTA --}}
{{-- ============================================================ --}}
<div class="page-container py-16 md:py-20">
    <div class="text-center">
        <h2 class="font-heading text-2xl md:text-3xl font-light text-charcoal mb-4">
            Ready to find your perfect piece?
        </h2>
        <p class="font-body text-charcoal/65 mb-8">
            Browse the full collection or get in touch for a custom order.
        </p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="{{ route('shop') }}" class="btn-primary">Shop the Collection</a>
            <a href="{{ route('contact.index') }}" class="btn-outline">Get in Touch</a>
        </div>
    </div>
</div>

@endsection
