@extends('layouts.app')

@section('title', $page->title)
@section('meta_description', Str::limit(strip_tags($page->body), 155))

@section('content')

{{-- ============================================================ --}}
{{-- PAGE HEADER --}}
{{-- ============================================================ --}}
<div class="border-b border-walnut/20 py-10 md:py-14">
    <div class="page-container">
        <nav class="section-label mb-4" aria-label="Breadcrumb">
            <a href="{{ route('home') }}" class="hover:text-forest-green transition-colors">Home</a>
            <span class="mx-2 text-walnut/40">/</span>
            <span class="text-charcoal">{{ $page->title }}</span>
        </nav>
        <h1 class="font-heading text-4xl md:text-5xl lg:text-6xl font-light text-charcoal">
            {{ $page->title }}
        </h1>
    </div>
</div>

{{-- ============================================================ --}}
{{-- PAGE BODY --}}
{{-- ============================================================ --}}
<div class="page-container py-14 md:py-20">
    <div class="content-container">
        <div class="prose prose-lg max-w-none
                    prose-headings:font-heading prose-headings:font-light prose-headings:text-charcoal
                    prose-h2:text-3xl prose-h3:text-2xl
                    prose-p:font-body prose-p:text-charcoal/85 prose-p:leading-relaxed
                    prose-a:text-forest-green prose-a:underline hover:prose-a:opacity-75
                    prose-ul:font-body prose-ol:font-body
                    prose-strong:font-600 prose-strong:text-charcoal
                    prose-blockquote:border-l-forest-green prose-blockquote:font-heading prose-blockquote:font-light
                    prose-hr:border-walnut/20">
            {!! clean($page->body, 'body_content') !!}
        </div>
    </div>
</div>

@endsection
