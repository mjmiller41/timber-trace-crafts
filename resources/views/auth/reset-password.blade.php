@extends('layouts.app')

@section('title', 'Set New Password')

@section('content')
<div class="page-container py-16">
    <div class="max-w-md mx-auto">

        <div class="card p-8 md:p-10">

            <div class="mb-8 text-center">
                <p class="section-label mb-3">Account Recovery</p>
                <h1 class="font-heading text-3xl font-light">Set New Password</h1>
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

            <form method="POST" action="{{ route('password.update') }}" novalidate>
                @csrf

                <input type="hidden" name="token" value="{{ $token }}">
                <input type="hidden" name="email" value="{{ request()->query('email', old('email')) }}">

                <div class="space-y-5">

                    <div>
                        <label for="password" class="block text-xs font-semibold uppercase tracking-widest text-walnut mb-1.5">
                            New Password
                        </label>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-field"
                            autocomplete="new-password"
                            required
                        >
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-xs font-semibold uppercase tracking-widest text-walnut mb-1.5">
                            Confirm New Password
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
                        Set Password
                    </button>

                </div>
            </form>

        </div>

    </div>
</div>
@endsection
