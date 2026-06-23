@extends('layouts.app')

@section('title', '404 — Page Not Found')

@section('content')

<div class="page-container py-24 md:py-36">
    <div class="max-w-lg mx-auto text-center">

        <p class="font-heading text-9xl md:text-[12rem] font-light text-charcoal/10 leading-none select-none mb-0">
            404
        </p>

        <div class="-mt-6 md:-mt-10">
            <p class="section-label mb-4">Page Not Found</p>
            <h1 class="font-heading text-3xl md:text-4xl font-light text-charcoal mb-5">
                This page has wandered off.
            </h1>
            <p class="font-body text-sm text-walnut leading-relaxed mb-10">
                The page you're looking for may have moved, been renamed, or never existed. Let's get you back on track.
            </p>
        </div>

        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="{{ route('home') }}" class="btn-primary px-10 py-4">Go Home</a>
            <a href="{{ route('shop') }}" class="btn-outline px-10 py-4">Browse the Shop</a>
        </div>

    </div>
</div>

@endsection
