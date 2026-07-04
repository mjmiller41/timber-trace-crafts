@extends('layouts.app')

@section('title', 'Unsubscribed')
@section('robots', 'noindex, nofollow')

@section('content')

<div class="page-container py-16 md:py-24">
    <div class="max-w-xl mx-auto text-center">
        @if($found)
            <h1 class="font-serif text-3xl md:text-4xl mb-4">You're unsubscribed</h1>
            <p class="text-stone-600 leading-relaxed">
                You won't receive any more cart reminder emails from Timber Trace Crafts.
                Order confirmations and shipping updates for purchases you make are not affected.
            </p>
        @else
            <h1 class="font-serif text-3xl md:text-4xl mb-4">Link not found</h1>
            <p class="text-stone-600 leading-relaxed">
                This unsubscribe link is invalid or has expired. If you keep receiving
                emails you'd rather not, please <a href="{{ url('/contact') }}" class="underline">contact us</a>.
            </p>
        @endif

        <p class="mt-8">
            <a href="{{ url('/') }}" class="inline-block bg-[#2C4C3B] text-white uppercase tracking-wider text-xs font-bold px-7 py-3.5">
                Back to shop
            </a>
        </p>
    </div>
</div>

@endsection
