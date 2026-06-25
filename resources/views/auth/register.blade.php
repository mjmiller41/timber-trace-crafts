@extends('layouts.app')

@section('title', 'Create Account')
@section('robots', 'noindex, follow')

@section('content')
<div class="page-container py-16">
    <div class="max-w-md mx-auto">

        <div class="card p-8 md:p-10">

            <div class="mb-8 text-center">
                <p class="section-label mb-3">Join Us</p>
                <h1 class="font-heading text-3xl font-light">Create Account</h1>
            </div>

            @if($errors->any())
                <div class="mb-6 border border-error p-4" style="background-color: #fee2e2;">
                    <ul class="space-y-1">
                        @foreach($errors->all() as $error)
                            <li class="text-sm" style="color: #991b1b;">{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('register') }}" novalidate>
                @csrf
                @include('components.honeypot')

                <div class="space-y-5">

                    <div>
                        <label for="name" class="block text-xs font-semibold uppercase tracking-widest text-walnut mb-1.5">
                            Full Name
                        </label>
                        <input
                            type="text"
                            id="name"
                            name="name"
                            class="form-field"
                            value="{{ old('name') }}"
                            autocomplete="name"
                            required
                        >
                    </div>

                    <div>
                        <label for="email" class="block text-xs font-semibold uppercase tracking-widest text-walnut mb-1.5">
                            Email Address
                        </label>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            class="form-field"
                            value="{{ old('email') }}"
                            autocomplete="email"
                            required
                        >
                    </div>

                    <div>
                        <label for="password" class="block text-xs font-semibold uppercase tracking-widest text-walnut mb-1.5">
                            Password
                        </label>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-field"
                            autocomplete="new-password"
                            required
                        >
                        <p class="mt-1.5 text-xs text-walnut/70">At least 8 characters with uppercase, lowercase, and a number.</p>
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-xs font-semibold uppercase tracking-widest text-walnut mb-1.5">
                            Confirm Password
                        </label>
                        <input
                            type="password"
                            id="password_confirmation"
                            name="password_confirmation"
                            class="form-field"
                            autocomplete="new-password"
                            required
                        >
                    </div>

                    <button type="submit" class="btn-primary w-full mt-2">
                        Create Account
                    </button>

                </div>
            </form>

            <div class="mt-8 pt-6 border-t border-walnut/20 text-center">
                <p class="text-sm text-walnut">
                    Already have an account?
                    <a href="{{ route('login') }}" class="font-semibold text-charcoal hover:text-forest-green transition-colors ml-1">
                        Sign In
                    </a>
                </p>
            </div>

        </div>

    </div>
</div>
@endsection
