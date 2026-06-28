@extends('layouts.app')

@section('title', 'Tagged: '.$tag->name.' — Journal')
@section('meta_description', 'Journal posts tagged with "'.$tag->name.'" from the Timber Trace Crafts studio.')
@section('robots', 'noindex, follow')

@section('content')

{{-- ============================================================ --}}
{{-- HEADER --}}
{{-- ============================================================ --}}
<div class="border-b border-walnut/20 py-12 md:py-16">
    <div class="page-container">
        <nav class="section-label mb-4">
            <a href="{{ route('journal.index') }}" class="hover:text-forest-green transition-colors">Journal</a>
            <span class="mx-2 text-walnut/40">/</span>
            <span>{{ $tag->name }}</span>
        </nav>
        <h1 class="font-heading text-5xl md:text-6xl font-light text-charcoal">{{ $tag->name }}</h1>
        <p class="font-body text-sm text-walnut mt-3">{{ $posts->total() }} {{ Str::plural('post', $posts->total()) }}</p>
    </div>
</div>

{{-- ============================================================ --}}
{{-- POSTS GRID --}}
{{-- ============================================================ --}}
<div class="page-container py-14 md:py-20">

    @if($posts->isEmpty())
        <div class="py-24 text-center">
            <p class="font-heading text-3xl font-light text-charcoal mb-4">No posts with this tag yet.</p>
            <a href="{{ route('journal.index') }}"
               class="font-body text-xs tracking-widest uppercase border-b border-charcoal pb-0.5 hover:text-forest-green hover:border-forest-green transition-colors">
                ← Back to Journal
            </a>
        </div>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-8 md:gap-12">
            @foreach($posts as $post)
            <article>
                <a href="{{ route('journal.show', $post->slug) }}" class="block mb-5">
                    @if($post->featured_image_id && $post->featuredImage)
                        <picture>
                            <source srcset="{{ preg_replace('/\.(png|jpe?g)$/i', '.webp', $post->featuredImage->url()) }}" type="image/webp">
                            <img src="{{ $post->featuredImage->url() }}"
                                 alt="{{ $post->title }}"
                                 class="w-full aspect-video object-cover">
                        </picture>
                    @else
                        <div class="w-full aspect-video bg-walnut/10 flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="0.75" stroke="currentColor" class="w-10 h-10 text-walnut/30">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5z" />
                            </svg>
                        </div>
                    @endif
                </a>

                <p class="section-label mb-2">
                    {{ \Carbon\Carbon::parse($post->published_at)->format('F j, Y') }}
                    &middot; {{ $post->reading_time }} min read
                </p>

                <h2 class="font-heading text-xl font-light mb-3 leading-snug">
                    <a href="{{ route('journal.show', $post->slug) }}" class="hover:text-forest-green transition-colors">
                        {{ $post->title }}
                    </a>
                </h2>

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

        <div class="mt-16">
            {{ $posts->links() }}
        </div>
    @endif
</div>

@endsection
