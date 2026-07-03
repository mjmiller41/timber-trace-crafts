{{-- Sidebar filters partial. Included by shop/index.blade.php for both desktop and mobile. --}}

@php
    $hasFilters = $activeCategory || $activeTag || request('sale') || $activeSearch;
@endphp

<div class="space-y-8">

    {{-- ── Search ── --}}
    <form method="GET" action="{{ route('shop') }}" role="search" class="relative">
        {{-- Preserve active filters/sort when searching --}}
        @foreach(request()->except(['search', 'page']) as $key => $value)
            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
        @endforeach
        @php($searchId = 'shop-search-'.(($mobile ?? false) ? 'mobile' : 'desktop'))
        <label for="{{ $searchId }}" class="sr-only">Search products</label>
        <input type="search"
               id="{{ $searchId }}"
               name="search"
               value="{{ $activeSearch }}"
               placeholder="Search products…"
               class="form-field text-sm w-full pr-10">
        <button type="submit" aria-label="Search"
                class="absolute right-0 top-0 h-full px-3 text-walnut hover:text-charcoal transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
            </svg>
        </button>
    </form>

    {{-- Section heading --}}
    <div class="flex items-center justify-between">
        <p class="section-label">Filter</p>
        @if($hasFilters)
            <a href="{{ route('shop') }}" class="font-body text-xs text-walnut hover:text-charcoal transition-colors tracking-wider uppercase underline underline-offset-2">
                Clear All
            </a>
        @endif
    </div>

    {{-- ── Categories ── --}}
    <div>
        <p class="font-body text-xs font-600 tracking-widest uppercase text-charcoal mb-3">Category</p>
        <ul class="space-y-1.5">
            <li>
                <a href="{{ route('shop') }}?{{ http_build_query(array_merge(request()->except('category', 'page'), [])) }}"
                   class="font-body text-sm transition-colors
                          {{ !$activeCategory ? 'text-forest-green font-600' : 'text-walnut hover:text-charcoal' }}">
                    All Products
                </a>
            </li>
            @foreach($categories as $cat)
                <li>
                    <a href="{{ route('shop') }}?{{ http_build_query(array_merge(request()->except('category', 'page'), ['category' => $cat->slug])) }}"
                       class="font-body text-sm transition-colors
                              {{ $activeCategory === $cat->slug ? 'text-forest-green font-600' : 'text-walnut hover:text-charcoal' }}">
                        {{ $cat->name }}
                        @if(isset($cat->products_count))
                            <span class="text-walnut/50 font-400">({{ $cat->products_count }})</span>
                        @endif
                    </a>
                </li>
            @endforeach
        </ul>
    </div>

    {{-- ── Wood Species / Tags ── --}}
    @if($woodTags->isNotEmpty())
        <div>
            <p class="font-body text-xs font-600 tracking-widest uppercase text-charcoal mb-3">Wood Species</p>
            <ul class="space-y-1.5">
                @foreach($woodTags as $tag)
                    <li>
                        <a href="{{ route('shop') }}?{{ http_build_query(array_merge(request()->except('tag', 'page'), ['tag' => $tag->slug])) }}"
                           class="font-body text-sm transition-colors
                                  {{ $activeTag === $tag->slug ? 'text-forest-green font-600' : 'text-walnut hover:text-charcoal' }}">
                            {{ $tag->name }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- ── Style / Tags ── --}}
    @if($styleTags->isNotEmpty())
        <div>
            <p class="font-body text-xs font-600 tracking-widest uppercase text-charcoal mb-3">Style</p>
            <ul class="space-y-1.5">
                @foreach($styleTags as $tag)
                    <li>
                        <a href="{{ route('shop') }}?{{ http_build_query(array_merge(request()->except('tag', 'page'), ['tag' => $tag->slug])) }}"
                           class="font-body text-sm transition-colors
                                  {{ $activeTag === $tag->slug ? 'text-forest-green font-600' : 'text-walnut hover:text-charcoal' }}">
                            {{ $tag->name }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- ── On Sale ── --}}
    <div>
        <p class="font-body text-xs font-600 tracking-widest uppercase text-charcoal mb-3">Offers</p>
        <a href="{{ route('shop') }}?{{ http_build_query(array_merge(request()->except('sale', 'page'), request('sale') ? [] : ['sale' => 1])) }}"
           class="flex items-center gap-2 font-body text-sm transition-colors
                  {{ request('sale') ? 'text-forest-green font-600' : 'text-walnut hover:text-charcoal' }}">
            <span class="w-4 h-4 border {{ request('sale') ? 'border-forest-green bg-forest-green' : 'border-walnut' }} flex items-center justify-center flex-shrink-0">
                @if(request('sale'))
                    <svg class="w-2.5 h-2.5 text-white" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                    </svg>
                @endif
            </span>
            On Sale
        </a>
    </div>

</div>
