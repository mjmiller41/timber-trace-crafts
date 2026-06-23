@extends('layouts.app')

@section('title', 'Verify Your Email')

@section('content')
<div class="page-container py-16">
    <div class="max-w-md mx-auto">

        <div class="card p-8 md:p-10">

            <div class="mb-8 text-center">
                <p class="section-label mb-3">Almost There</p>
                <h1 class="font-heading text-3xl font-light">Verify Your Email</h1>
            </div>

            @if(session('status') === 'verification-link-sent')
                <div class="mb-6 border border-forest-green/30 bg-forest-green/5 p-4">
                    <p class="text-sm text-forest-green">A new verification link has been sent to your email address.</p>
                </div>
            @endif

            <p class="font-body text-charcoal/70 text-sm leading-relaxed mb-6">
                Thanks for signing up! Before getting started, please verify your email address by clicking the link we just emailed you. If you didn't receive it, we'll send another.
            </p>

            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <button type="submit" class="btn-primary w-full">
                    Resend Verification Email
                </button>
            </form>

            <div class="mt-6 text-center">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-xs font-semibold uppercase tracking-widest text-walnut hover:text-charcoal transition-colors">
                        Sign Out
                    </button>
                </form>
            </div>

        </div>

    </div>
</div>
@endsection
