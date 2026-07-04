@extends('layouts.app')

@section('title', 'Two-Factor Verification')
@section('robots', 'noindex, follow')

@section('content')
<div class="page-container py-16">
    <div class="max-w-md mx-auto">

        <div class="card p-8 md:p-10">

            <div class="mb-8 text-center">
                <p class="section-label mb-3">Security Check</p>
                <h1 class="font-heading text-3xl font-light">Two-Factor Verification</h1>
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

            {{-- Authenticator code --}}
            <form method="POST" action="{{ route('two-factor.login.store') }}" novalidate>
                @csrf
                <p class="text-sm text-walnut mb-4">
                    Enter the 6-digit code from your authenticator app.
                </p>
                <div>
                    <label for="code" class="block text-xs font-semibold uppercase tracking-widest text-walnut mb-1.5">
                        Authentication Code
                    </label>
                    <input
                        type="text"
                        id="code"
                        name="code"
                        class="form-field"
                        inputmode="numeric"
                        autocomplete="one-time-code"
                        autofocus
                    >
                </div>
                <button type="submit" class="btn-primary w-full mt-5">
                    Verify
                </button>
            </form>

            {{-- Recovery code fallback --}}
            <div class="mt-8 pt-6 border-t border-walnut/20">
                <details>
                    <summary class="text-sm font-semibold text-charcoal cursor-pointer">
                        Lost your device? Use a recovery code
                    </summary>
                    <form method="POST" action="{{ route('two-factor.login.store') }}" class="mt-4" novalidate>
                        @csrf
                        <label for="recovery_code" class="block text-xs font-semibold uppercase tracking-widest text-walnut mb-1.5">
                            Recovery Code
                        </label>
                        <input
                            type="text"
                            id="recovery_code"
                            name="recovery_code"
                            class="form-field"
                            autocomplete="off"
                        >
                        <button type="submit" class="btn-outline w-full mt-4">
                            Use Recovery Code
                        </button>
                    </form>
                </details>
            </div>

        </div>

    </div>
</div>
@endsection
