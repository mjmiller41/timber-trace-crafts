@extends('layouts.app')

@section('title', 'The Collection')
@section('meta_description', 'Browse our full collection of handcrafted laser-cut wooden jewelry, boxes, and gifts.')

@section('content')

{{-- ============================================================ --}}
{{-- PAGE HEADER --}}
{{-- ============================================================ --}}
<div class="border-b border-walnut/20 py-8">
    <div class="page-container">
        {{-- Breadcrumb --}}
        <nav class="section-label mb-3" aria-label="Breadcrumb">
            <a href="{{ route('home') }}" class="hover:text-forest-green transition-colors">Home</a>
            <span class="mx-2 text-walnut/40">/</span>
            <span class="text-charcoal">Shop</span>
        </nav>
        <div class="flex items-end justify-between">
            <h1 class="font-heading text-4xl md:text-5xl font-light text-charcoal">The Collection</h1>
            <p class="section-label hidden md:block">
                {{ $products->total() }} {{ Str::plural('product', $products->total()) }}
            </p>
        </div>
    </div>
</div>

{{-- ============================================================ --}}
{{-- MAIN LAYOUT: SIDEBAR + GRID --}}
{{-- ============================================================ --}}
<div class="page-container py-12">

    {{-- Mobile filter toggle (Alpine) --}}
    <div class="lg:hidden mb-6" x-data="{ open: false }">
        <button @click="open = !open"
                class="btn-outline w-full flex items-center justify-between">
            <span>Filter & Sort</span>
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                 class="w-4 h-4 transition-transform duration-200" :class="open ? 'rotate-180' : ''">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
            </svg>
        </button>

        {{-- Mobile sidebar (collapsed) --}}
        <div x-show="open" x-cloak x-transition class="mt-4 border border-walnut/20 p-6 bg-surface">
            @include('shop._sidebar', ['mobile' => true])
        </div>
    </div>

    <div class="flex gap-12 lg:gap-16">

        {{-- ====================================================== --}}
        {{-- SIDEBAR (desktop) --}}
        {{-- ====================================================== --}}
        <aside class="hidden lg:block w-56 flex-shrink-0">
            @include('shop._sidebar', ['mobile' => false])
        </aside>

        {{-- ====================================================== --}}
        {{-- PRODUCT GRID --}}
        {{-- ====================================================== --}}
        <div class="flex-1 min-w-0">

            {{-- Sort bar --}}
            <div class="flex items-center justify-between mb-8 pb-4 border-b border-walnut/20">
                <p class="section-label md:hidden">{{ $products->total() }} products</p>
                <div class="flex items-center gap-2 ml-auto">
                    <span class="section-label mr-3 hidden sm:inline">Sort</span>

                    @php
                        $sortOptions = [
                            'newest'     => 'Newest',
                            'featured'   => 'Featured',
                            'price_asc'  => 'Price: Low to High',
                            'price_desc' => 'Price: High to Low',
                            'name_asc'   => 'Name A–Z',
                        ];
                    @endphp

                    <div class="flex flex-wrap gap-2">
                        @foreach($sortOptions as $value => $label)
                            <a href="{{ route('shop') }}?{{ http_build_query(array_merge(request()->except('sort', 'page'), ['sort' => $value])) }}"
                               class="font-body text-xs tracking-wider uppercase px-3 py-1.5 border transition-colors duration-150
                                      {{ $activeSort === $value
                                          ? 'bg-charcoal text-oak-sand border-charcoal'
                                          : 'border-walnut/30 text-walnut hover:border-charcoal hover:text-charcoal' }}">
                                {{ $label }}
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Products --}}
            @if($products->isEmpty())
                <div class="py-24 text-center">
                    <p class="font-heading text-3xl font-light text-charcoal mb-4">Nothing found here.</p>
                    <p class="font-body text-sm text-walnut mb-8">Try adjusting your filters or browse the full collection.</p>
                    <a href="{{ route('shop') }}" class="btn-outline">Clear Filters</a>
                </div>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 md:gap-8">
                    @foreach($products as $product)
                        @include('components.product-card', ['product' => $product])
                    @endforeach
                </div>

                {{-- Pagination --}}
                @if($products->hasPages())
                    <div class="mt-14 pt-8 border-t border-walnut/20">
                        {{ $products->appends(request()->except('page'))->links() }}
                    </div>
                @endif
            @endif

        </div>
    </div>
</div>

@endsection
