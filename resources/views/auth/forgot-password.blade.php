@extends('layouts.app')

@section('title', 'Forgot Password')

@section('content')
<div class="page-container py-16">
    <div class="max-w-md mx-auto">

        <div class="card p-8 md:p-10">

            <div class="mb-8 text-center">
                <p class="section-label mb-3">Account Recovery</p>
                <h1 class="font-heading text-3xl font-light">Reset Password</h1>
                <p class="mt-4 text-sm text-walnut leading-relaxed">
                    Enter your email address and we'll send you a link to reset your password.
                </p>
            </div>

            @if(session('status'))
                <div class="mb-6 border border-forest-green p-4" style="background-color: #dcfce7;">
                    <p class="text-sm" style="color: #166534;">{{ session('status') }}</p>
                </div>
            @endif

            @if($errors->any())
                <div class="mb-6 border border-error p-4" style="background-color: #fee2e2;">
                    <ul class="space-y-1">
                        @foreach($errors->all() as $error)
                            <li class="text-sm" style="color: #991b1b;">{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('password.email') }}" novalidate>
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

                    <button type="submit" class="btn-primary w-full mt-2">
                        Send Reset Link
                    </button>

                </div>
            </form>

            <div class="mt-8 pt-6 border-t border-walnut/20 text-center">
                <a href="{{ route('login') }}" class="text-xs font-semibold uppercase tracking-widest text-walnut hover:text-charcoal transition-colors">
                    ← Back to Sign In
                </a>
            </div>

        </div>

    </div>
</div>
@endsection
