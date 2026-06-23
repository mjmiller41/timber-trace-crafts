@extends('layouts.app')

@section('title', '500 — Server Error')

@section('content')

<div class="page-container py-24 md:py-36">
    <div class="max-w-lg mx-auto text-center">

        <p class="font-heading text-9xl md:text-[12rem] font-light text-charcoal/10 leading-none select-none mb-0">
            500
        </p>

        <div class="-mt-6 md:-mt-10">
            <p class="section-label mb-4">Server Error</p>
            <h1 class="font-heading text-3xl md:text-4xl font-light text-charcoal mb-5">
                Something went wrong.
            </h1>
            <p class="font-body text-sm text-walnut leading-relaxed mb-10">
                We encountered an unexpected error. Our team has been notified. Please try again in a few moments, or head back home.
            </p>
        </div>

        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="{{ route('home') }}" class="btn-primary px-10 py-4">Go Home</a>
            <a href="{{ route('contact.index') }}" class="btn-outline px-10 py-4">Contact Support</a>
        </div>

    </div>
</div>

@endsection
