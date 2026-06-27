@extends('layouts.app')

@section('title', $post->title)
@section('meta_description', $post->excerpt ? Str::limit(strip_tags($post->excerpt), 155) : Str::limit(strip_tags($post->body), 155))
@section('og_type', 'article')
@section('og_image', $post->featured_image ? $post->featuredImage?->url() : asset('images/og-default.jpg'))

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
                @if($post->user)
                    &middot; By {{ $post->user->name }}
                @endif
                &middot; {{ $post->reading_time }} min read
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
            {!! clean($post->body, 'body_content') !!}
        </div>

        {{-- Tags --}}
        @if($post->tags->isNotEmpty())
            <div class="mt-12 pt-8 border-t border-walnut/20">
                <p class="section-label mb-3">Tags</p>
                <div class="flex flex-wrap gap-2">
                    @foreach($post->tags as $tag)
                        <a href="{{ route('journal.tag', $tag->slug) }}"
                           class="font-body text-xs tracking-widest uppercase px-3 py-1.5 border border-walnut/30 text-walnut hover:border-forest-green hover:text-forest-green transition-colors">
                            {{ $tag->name }}
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Related posts --}}
        @if(isset($relatedPosts) && $relatedPosts->isNotEmpty())
            <div class="mt-12 pt-8 border-t border-walnut/20">
                <p class="section-label mb-6">Related Posts</p>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                    @foreach($relatedPosts as $related)
                    <article>
                        <a href="{{ route('journal.show', $related->slug) }}" class="block mb-3">
                            @if($related->featured_image_id && $related->featuredImage)
                                <img src="{{ $related->featuredImage->url() }}"
                                     alt="{{ $related->title }}"
                                     class="w-full aspect-video object-cover">
                            @else
                                <div class="w-full aspect-video bg-walnut/10"></div>
                            @endif
                        </a>
                        <p class="section-label mb-1">{{ \Carbon\Carbon::parse($related->published_at)->format('M j, Y') }}</p>
                        <h3 class="font-heading text-base font-light leading-snug">
                            <a href="{{ route('journal.show', $related->slug) }}"
                               class="hover:text-forest-green transition-colors">
                                {{ $related->title }}
                            </a>
                        </h3>
                    </article>
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

@push('schema')
@php
    $blogSchemaData = [
        '@context'        => 'https://schema.org',
        '@type'           => 'BlogPosting',
        'headline'        => $post->title,
        'description'     => Str::limit(strip_tags($post->excerpt ?? $post->body ?? ''), 155),
        'url'             => url()->current(),
        'inLanguage'      => 'en-US',
        'datePublished'   => \Carbon\Carbon::parse($post->published_at)->toAtomString(),
        'dateModified'    => $post->updated_at->toAtomString(),
        'author'          => ['@type' => 'Person', '@id' => url('/').'#author'],
        'publisher'       => ['@id' => url('/').'#organization'],
        'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => url()->current()],
    ];
    if ($post->featuredImage) {
        $blogSchemaData['image'] = $post->featuredImage->url();
    }
    $blogSchema = json_encode($blogSchemaData);
@endphp
<script type="application/ld+json">{!! $blogSchema !!}</script>
@endpush

@endsection

