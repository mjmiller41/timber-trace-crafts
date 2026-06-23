@extends('layouts.app')

@section('title', 'Profile & Password')

@section('content')
<div class="page-container py-12">

    <div class="mb-8 pb-6 border-b border-walnut/20">
        <p class="section-label mb-2">Account</p>
        <h1 class="font-heading text-3xl font-light">Profile & Password</h1>
    </div>

    <div class="flex flex-col lg:flex-row gap-10">

        @include('account.partials.sidebar')

        <div class="flex-1 min-w-0 space-y-10">

            {{-- Update Profile --}}
            <section class="card p-6 md:p-8">
                <h2 class="font-heading text-xl font-light mb-6">Update Profile</h2>

                @if(session('success'))
                    <div class="mb-6 border border-forest-green p-4" style="background-color: #dcfce7;">
                        <p class="text-sm" style="color: #166534;">{{ session('success') }}</p>
                    </div>
                @endif

                @if($errors->has('name') || $errors->has('email'))
                    <div class="mb-6 border border-error p-4" style="background-color: #fee2e2;">
                        <ul class="space-y-1">
                            @foreach($errors->only(['name', 'email']) as $error)
                                <li class="text-sm" style="color: #991b1b;">{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('account.profile.update') }}">
                    @csrf
                    @method('PUT')

                    <div class="space-y-5 max-w-md">

                        <div>
                            <label for="name" class="block text-xs font-semibold uppercase tracking-widest text-walnut mb-1.5">
                                Full Name
                            </label>
                            <input
                                type="text"
                                id="name"
                                name="name"
                                class="form-field"
                                value="{{ old('name', auth()->user()->name) }}"
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
                                value="{{ old('email', auth()->user()->email) }}"
                                autocomplete="email"
                                required
                            >
                        </div>

                        <button type="submit" class="btn-primary">
                            Update Profile
                        </button>

                    </div>
                </form>
            </section>

            {{-- Change Password --}}
            <section class="card p-6 md:p-8">
                <h2 class="font-heading text-xl font-light mb-6">Change Password</h2>

                @if($errors->has('current_password') || $errors->has('password'))
                    <div class="mb-6 border border-error p-4" style="background-color: #fee2e2;">
                        <ul class="space-y-1">
                            @foreach($errors->only(['current_password', 'password']) as $error)
                                <li class="text-sm" style="color: #991b1b;">{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('account.profile.update') }}">
                    @csrf
                    @method('PUT')

                    {{-- Carry over profile fields so the controller doesn't strip them --}}
                    <input type="hidden" name="name" value="{{ auth()->user()->name }}">
                    <input type="hidden" name="email" value="{{ auth()->user()->email }}">

                    <div class="space-y-5 max-w-md">

                        <div>
                            <label for="current_password" class="block text-xs font-semibold uppercase tracking-widest text-walnut mb-1.5">
                                Current Password
                            </label>
                            <input
                                type="password"
                                id="current_password"
                                name="current_password"
                                class="form-field"
                                autocomplete="current-password"
                            >
                        </div>

                        <div>
                            <label for="new_password" class="block text-xs font-semibold uppercase tracking-widest text-walnut mb-1.5">
                                New Password
                            </label>
                            <input
                                type="password"
                                id="new_password"
                                name="password"
                                class="form-field"
                                autocomplete="new-password"
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
                            >
                        </div>

                        <button type="submit" class="btn-outline">
                            Change Password
                        </button>

                    </div>
                </form>
            </section>

        </div>
    </div>
</div>
@endsection
