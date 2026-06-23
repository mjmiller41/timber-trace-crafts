@extends('layouts.app')

@section('title', 'Sign In')

@section('content')
<div class="page-container py-16">
    <div class="max-w-md mx-auto">

        <div class="card p-8 md:p-10">

            <div class="mb-8 text-center">
                <p class="section-label mb-3">Your Account</p>
                <h1 class="font-heading text-3xl font-light">Welcome Back</h1>
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

            <form method="POST" action="{{ route('login') }}" novalidate>
                @csrf

                <div class="space-y-5">

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
                            autocomplete="current-password"
                            required
                        >
                    </div>

                    <div class="flex items-center justify-between">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input
                                type="checkbox"
                                name="remember"
                                id="remember"
                                class="border-charcoal"
                                style="accent-color: #333333; width: 1rem; height: 1rem; border-radius: 0;"
                                {{ old('remember') ? 'checked' : '' }}
                            >
                            <span class="text-sm text-walnut">Remember me</span>
                        </label>
                        <a href="{{ route('password.request') }}" class="text-xs font-semibold uppercase tracking-widest text-walnut hover:text-charcoal transition-colors">
                            Forgot password?
                        </a>
                    </div>

                    <button type="submit" class="btn-primary w-full mt-2">
                        Sign In
                    </button>

                </div>
            </form>

            <div class="mt-8 pt-6 border-t border-walnut/20 text-center">
                <p class="text-sm text-walnut">
                    Don't have an account?
                    <a href="{{ route('register') }}" class="font-semibold text-charcoal hover:text-forest-green transition-colors ml-1">
                        Create one
                    </a>
                </p>
            </div>

        </div>

    </div>
</div>
@endsection
