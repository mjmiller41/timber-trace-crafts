@extends('layouts.app')

@section('title', 'Home')
@section('meta_description', 'Handcrafted laser-cut wooden jewelry, boxes, and gifts. Made with precision and love in every piece.')

@section('content')

{{-- ============================================================ --}}
{{-- HERO --}}
{{-- ============================================================ --}}
<section class="bg-forest-green text-white py-24 md:py-36">
    <div class="page-container">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">

            {{-- Copy --}}
            <div>
                <p class="section-label text-white/60 mb-6">Timber Trace Crafts</p>
                <h1 class="font-heading text-5xl md:text-6xl lg:text-7xl font-light leading-tight mb-6">
                    Handcrafted<br>in Hardwood.<br>Worn with Pride.
                </h1>
                <p class="font-body text-white/75 text-lg font-light leading-relaxed mb-10 max-w-md">
                    Every piece begins as raw timber and becomes something to treasure. Laser-cut with precision, finished by hand, made to last a lifetime.
                </p>
                <div class="flex flex-wrap gap-4">
                    <a href="{{ route('shop') }}"
                       class="inline-block bg-white text-charcoal px-8 py-4 font-body font-600 text-sm tracking-widest uppercase transition-opacity hover:opacity-85">
                        Shop Now
                    </a>
                    <a href="{{ route('page.show', 'about-us') }}"
                       class="inline-block border border-white/60 text-white px-8 py-4 font-body font-600 text-sm tracking-widest uppercase transition-all hover:bg-white/10">
                        Our Story
                    </a>
                </div>
            </div>

            {{-- Hero image --}}
            <div class="relative">
                <div class="aspect-square overflow-hidden">
                    <img src="{{ \Illuminate\Support\Facades\Storage::disk('r2')->url('products/lifestyle-1.png') }}"
                         alt="Handcrafted wooden box at a craft market"
                         class="w-full h-full object-cover">
                </div>
                {{-- Decorative offset border --}}
                <div class="absolute -bottom-4 -right-4 w-full h-full border border-white/20 -z-10"></div>
            </div>

        </div>
    </div>
</section>

{{-- ============================================================ --}}
{{-- FEATURED PRODUCTS --}}
{{-- ============================================================ --}}
<section class="py-20 md:py-28">
    <div class="page-container">
        <div class="mb-12">
            <p class="section-label mb-3">Featured Collection</p>
            <h2 class="font-heading text-4xl md:text-5xl font-light text-charcoal">Crafted with Precision</h2>
        </div>

        @if($featuredProducts->isNotEmpty())
            <div class="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-4 gap-6 md:gap-8">
                @foreach($featuredProducts as $product)
                    @include('components.product-card', ['product' => $product])
                @endforeach
            </div>
        @endif

        <div class="mt-12 text-center">
            <a href="{{ route('shop') }}" class="btn-outline">View All Products</a>
        </div>
    </div>
</section>

{{-- ============================================================ --}}
{{-- SHOP BY CATEGORY --}}
{{-- ============================================================ --}}
<section class="py-20 md:py-28 bg-surface">
    <div class="page-container">
        <p class="section-label mb-10">Browse by Category</p>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-px bg-walnut/20">
            @foreach($categories as $category)
                <a href="{{ route('shop') }}?category={{ $category->slug }}"
                   class="group relative bg-oak-sand py-16 px-10 flex flex-col justify-between min-h-48 hover:bg-mahogany hover:text-white transition-colors duration-300">
                    <div>
                        <p class="section-label group-hover:text-white/50 transition-colors mb-4">
                            {{ $category->products_count ?? '' }}{{ isset($category->products_count) ? ' pieces' : 'Explore' }}
                        </p>
                        <h3 class="font-heading text-3xl md:text-4xl font-light leading-tight">
                            {{ $category->name }}
                        </h3>
                    </div>
                    <div class="mt-8">
                        <span class="font-body text-xs tracking-widest uppercase border-b border-current pb-0.5 inline-block">
                            Shop {{ $category->name }}
                        </span>
                    </div>
                </a>
            @endforeach
        </div>
    </div>
</section>

{{-- ============================================================ --}}
{{-- NEW ARRIVALS --}}
{{-- ============================================================ --}}
<section class="py-20 md:py-28">
    <div class="page-container">
        <div class="flex items-end justify-between mb-12">
            <div>
                <p class="section-label mb-3">New Arrivals</p>
                <h2 class="font-heading text-4xl md:text-5xl font-light text-charcoal">Fresh from the Studio</h2>
            </div>
            <a href="{{ route('shop') }}?sort=newest" class="hidden md:inline font-body text-xs tracking-widest uppercase border-b border-charcoal pb-0.5 hover:text-forest-green hover:border-forest-green transition-colors">
                See All New →
            </a>
        </div>

        @if($newArrivals->isNotEmpty())
            <div class="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-4 gap-6 md:gap-8">
                @foreach($newArrivals as $product)
                    @include('components.product-card', ['product' => $product])
                @endforeach
            </div>
        @endif
    </div>
</section>

{{-- ============================================================ --}}
{{-- BRAND STORY CALLOUT --}}
{{-- ============================================================ --}}
<section class="bg-forest-green py-24 md:py-32">
    <div class="page-container">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">

            {{-- Image --}}
            <div class="relative">
                <div class="aspect-square overflow-hidden">
                    <img src="{{ \Illuminate\Support\Facades\Storage::disk('r2')->url('products/BOX-HRT-BBPLY3-01-IMG16.jpeg') }}"
                         alt="Handcrafted laser-cut heart box with floral design"
                         class="w-full h-full object-cover">
                </div>
                <div class="absolute -bottom-4 -left-4 w-full h-full border border-white/20 -z-10"></div>
            </div>

            {{-- Copy --}}
            <div>
                <p class="section-label text-white/50 mb-8">Our Philosophy</p>
                <blockquote class="font-heading text-3xl md:text-5xl font-light text-white leading-tight mb-10">
                    "Every piece tells the story of the wood it came from."
                </blockquote>
                <p class="font-body text-white/65 text-base leading-relaxed mb-10">
                    We work with sustainably sourced hardwoods — oak, walnut, cherry, and maple — letting the grain and natural character of each board shape the final piece.
                </p>
                <a href="{{ route('page.show', 'about-us') }}" class="inline-block border border-white/50 text-white px-8 py-4 font-body font-600 text-sm tracking-widest uppercase transition-all hover:bg-white/10">
                    Read Our Story
                </a>
            </div>

        </div>
    </div>
</section>

{{-- ============================================================ --}}
{{-- JOURNAL PREVIEW --}}
{{-- ============================================================ --}}
<section class="py-20 md:py-28">
    <div class="page-container">
        <div class="flex items-end justify-between mb-12">
            <div>
                <p class="section-label mb-3">From the Workshop</p>
                <h2 class="font-heading text-4xl md:text-5xl font-light text-charcoal">Journal</h2>
            </div>
            <a href="{{ route('journal.index') }}" class="hidden md:inline font-body text-xs tracking-widest uppercase border-b border-charcoal pb-0.5 hover:text-forest-green hover:border-forest-green transition-colors">
                All Posts →
            </a>
        </div>

        @if($journalPosts->isNotEmpty())
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 md:gap-12">
                @foreach($journalPosts as $post)
                    <article>
                        {{-- Image --}}
                        <a href="{{ route('journal.show', $post->slug) }}" class="block mb-5">
                            @if(isset($post->featured_image) && $post->featured_image)
                                <img src="{{ $post->featuredImage?->url() }}"
                                     alt="{{ $post->title }}"
                                     class="w-full aspect-video object-cover">
                            @else
                                <div class="w-full aspect-video bg-walnut/10 flex items-center justify-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="0.75" stroke="currentColor" class="w-10 h-10 text-walnut/30">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5z" />
                                    </svg>
                                </div>
                            @endif
                        </a>

                        {{-- Meta --}}
                        <p class="section-label mb-2">
                            {{ \Carbon\Carbon::parse($post->published_at)->format('F j, Y') }}
                        </p>

                        {{-- Title --}}
                        <h3 class="font-heading text-xl font-light mb-3 leading-snug">
                            <a href="{{ route('journal.show', $post->slug) }}" class="hover:text-forest-green transition-colors">
                                {{ $post->title }}
                            </a>
                        </h3>

                        {{-- Excerpt --}}
                        @if($post->excerpt)
                            <p class="font-body text-sm text-walnut leading-relaxed mb-4">
                                {{ Str::limit($post->excerpt, 120) }}
                            </p>
                        @endif

                        <a href="{{ route('journal.show', $post->slug) }}"
                           class="font-body text-xs tracking-widest uppercase border-b border-charcoal pb-0.5 hover:text-forest-green hover:border-forest-green transition-colors">
                            Read More
                        </a>
                    </article>
                @endforeach
            </div>
        @endif
    </div>
</section>

{{-- ============================================================ --}}
{{-- NEWSLETTER CTA --}}
{{-- ============================================================ --}}
<section class="py-20 md:py-24 bg-surface border-t border-walnut/20">
    <div class="page-container">
        <div class="max-w-lg mx-auto text-center">
            <p class="section-label mb-4">Stay Connected</p>
            <h2 class="font-heading text-3xl md:text-4xl font-light text-charcoal mb-4">Join Our Community</h2>
            <p class="font-body text-sm text-walnut leading-relaxed mb-8">
                New collections, behind-the-scenes stories from the studio, and occasional offers — straight to your inbox. Unsubscribe any time.
            </p>
            <form action="#" method="POST" class="flex flex-col sm:flex-row gap-0">
                @csrf
                <input type="email"
                       name="email"
                       placeholder="your@email.com"
                       required
                       class="form-field flex-1 text-sm py-4 px-5 border-r-0 sm:border-r-0">
                <button type="submit" class="btn-forest px-8 py-4 whitespace-nowrap">
                    Subscribe
                </button>
            </form>
            <p class="font-body text-xs text-walnut/70 mt-4">
                No spam. Unsubscribe any time.
            </p>
        </div>
    </div>
</section>

@endsection
