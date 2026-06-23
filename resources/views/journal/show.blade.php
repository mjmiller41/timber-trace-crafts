@extends('layouts.app')

@section('title', $post->title)
@section('meta_description', $post->excerpt ? Str::limit(strip_tags($post->excerpt), 155) : Str::limit(strip_tags($post->body), 155))

@section('content')

{{-- ============================================================ --}}
{{-- BREADCRUMB --}}
{{-- ============================================================ --}}
<div class="border-b border-walnut/20 py-5">
    <div class="page-container">
        <nav class="section-label" aria-label="Breadcrumb">
            <a href="{{ route('home') }}" class="hover:text-forest-green transition-colors">Home</a>
            <span class="mx-2 text-walnut/40">/</span>
            <a href="{{ route('journal.index') }}" class="hover:text-forest-green transition-colors">Journal</a>
            <span class="mx-2 text-walnut/40">/</span>
            <span class="text-charcoal">{{ Str::limit($post->title, 50) }}</span>
        </nav>
    </div>
</div>

{{-- ============================================================ --}}
{{-- ARTICLE HEADER --}}
{{-- ============================================================ --}}
<div class="page-container py-12 md:py-16">
    <div class="max-w-3xl mx-auto">

        <header class="mb-10">
            <p class="section-label mb-4">
                {{ \Carbon\Carbon::parse($post->published_at)->format('F j, Y') }}
            </p>
            <h1 class="font-heading text-4xl md:text-5xl lg:text-6xl font-light text-charcoal leading-tight mb-6">
                {{ $post->title }}
            </h1>
            @if($post->excerpt)
                <p class="font-body text-lg text-walnut leading-relaxed">
                    {{ $post->excerpt }}
                </p>
            @endif
        </header>

        {{-- Featured image --}}
        @if($post->featured_image)
            <div class="mb-12 -mx-4 sm:mx-0">
                <img src="{{ $post->featuredImage?->url() }}"
                     alt="{{ $post->title }}"
                     class="w-full aspect-video object-cover">
            </div>
        @endif

        {{-- ============================================================ --}}
        {{-- BODY CONTENT --}}
        {{-- ============================================================ --}}
        <div class="prose prose-lg max-w-none
                    prose-headings:font-heading prose-headings:font-light prose-headings:text-charcoal
                    prose-p:font-body prose-p:text-charcoal/85 prose-p:leading-relaxed
                    prose-a:text-forest-green prose-a:underline hover:prose-a:opacity-75
                    prose-img:w-full prose-blockquote:border-l-forest-green
                    prose-blockquote:font-heading prose-blockquote:font-light prose-blockquote:text-charcoal">
            {!! $post->body !!}
        </div>

        {{-- Tags --}}
        @if(isset($post->tags) && $post->tags->isNotEmpty())
            <div class="mt-12 pt-8 border-t border-walnut/20">
                <p class="section-label mb-3">Tags</p>
                <div class="flex flex-wrap gap-2">
                    @foreach($post->tags as $tag)
                        <span class="font-body text-xs tracking-widest uppercase px-3 py-1.5 border border-walnut/30 text-walnut">
                            {{ $tag->name }}
                        </span>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Back link --}}
        <div class="mt-12 pt-8 border-t border-walnut/20">
            <a href="{{ route('journal.index') }}"
               class="font-body text-xs tracking-widest uppercase border-b border-charcoal pb-0.5 hover:text-forest-green hover:border-forest-green transition-colors">
                ← Back to Journal
            </a>
        </div>

    </div>
</div>

@endsection
