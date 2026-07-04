@extends('layouts.app')

@section('title', 'Security')

@section('content')
<div class="page-container py-12">

    <div class="mb-8 pb-6 border-b border-walnut/20">
        <p class="section-label mb-2">Account</p>
        <h1 class="font-heading text-3xl font-light">Security</h1>
    </div>

    <div class="flex flex-col lg:flex-row gap-10">

        @include('account.partials.sidebar')

        <div class="flex-1 min-w-0 space-y-10">

            @if(session('success'))
                <div class="border border-forest-green p-4" style="background-color: #dcfce7;">
                    <p class="text-sm" style="color: #166534;">{{ session('success') }}</p>
                </div>
            @endif

            {{-- One-time recovery codes, shown immediately after enabling --}}
            @if(!empty($recoveryCodes))
                <section class="card p-6 md:p-8 border-forest-green">
                    <h2 class="font-heading text-xl font-light mb-3">Save Your Recovery Codes</h2>
                    <p class="text-sm text-walnut mb-5">
                        Store these somewhere safe. Each code can be used once to sign in if you
                        lose access to your authenticator app. <strong>They will not be shown again.</strong>
                    </p>
                    <ul class="grid grid-cols-2 gap-2 max-w-sm font-mono text-sm">
                        @foreach($recoveryCodes as $code)
                            <li class="border border-walnut/20 px-3 py-2 tracking-wide">{{ $code }}</li>
                        @endforeach
                    </ul>
                </section>
            @endif

            <section class="card p-6 md:p-8">
                <h2 class="font-heading text-xl font-light mb-2">Two-Factor Authentication</h2>
                <p class="text-sm text-walnut mb-6">
                    Add a second step to sign-in using a time-based code from an authenticator
                    app such as Google Authenticator, Authy, or 1Password.
                </p>

                @if($errors->any())
                    <div class="mb-6 border border-error p-4" style="background-color: #fee2e2;">
                        <ul class="space-y-1">
                            @foreach($errors->all() as $error)
                                <li class="text-sm" style="color: #991b1b;">{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if(auth()->user()->hasTwoFactorEnabled())
                    {{-- Enabled: show status + disable flow --}}
                    <div class="mb-6 flex items-center gap-2">
                        <span class="inline-block w-2.5 h-2.5 rounded-full bg-forest-green"></span>
                        <span class="text-sm font-semibold text-charcoal">Two-factor authentication is enabled.</span>
                    </div>

                    <form method="POST" action="{{ route('account.security.2fa.disable') }}">
                        @csrf
                        @method('DELETE')
                        <div class="space-y-4 max-w-md">
                            <p class="text-sm text-walnut">
                                To disable, confirm your account password.
                            </p>
                            <div>
                                <label for="password" class="block text-xs font-semibold uppercase tracking-widest text-walnut mb-1.5">
                                    Password
                                </label>
                                <input type="password" id="password" name="password" class="form-field" autocomplete="current-password">
                            </div>
                            <button type="submit" class="btn-outline">Disable Two-Factor</button>
                        </div>
                    </form>
                @elseif($setup)
                    {{-- Enrollment in progress: show QR + secret + confirm form --}}
                    <div class="space-y-6 max-w-md">
                        <div>
                            <p class="text-sm text-walnut mb-3">
                                1. Scan this QR code with your authenticator app (Google Authenticator, Authy, 1Password, etc.):
                            </p>
                            <div x-data="totpQr({{ json_encode($setup['uri']) }})"
                                 x-init="init()"
                                 class="mb-4 border border-walnut/20 inline-block p-2">
                                <canvas x-ref="qr"></canvas>
                            </div>
                            <p class="text-sm text-walnut mb-2">Or enter this key manually:</p>
                            <p class="font-mono text-lg tracking-widest border border-walnut/20 px-4 py-3 break-all">
                                {{ $setup['secret'] }}
                            </p>
                        </div>

                        <form method="POST" action="{{ route('account.security.2fa.confirm') }}">
                            @csrf
                            <p class="text-sm text-walnut mb-3">
                                2. Enter the 6-digit code your app now shows to confirm:
                            </p>
                            <label for="code" class="block text-xs font-semibold uppercase tracking-widest text-walnut mb-1.5">
                                Verification Code
                            </label>
                            <input type="text" id="code" name="code" class="form-field" inputmode="numeric" autocomplete="one-time-code" autofocus>
                            <button type="submit" class="btn-primary mt-4">Confirm &amp; Enable</button>
                        </form>
                    </div>
                @else
                    {{-- Not enabled: start enrollment --}}
                    <div class="mb-6 flex items-center gap-2">
                        <span class="inline-block w-2.5 h-2.5 rounded-full bg-walnut/40"></span>
                        <span class="text-sm text-walnut">Two-factor authentication is not enabled.</span>
                    </div>
                    <form method="POST" action="{{ route('account.security.2fa.enable') }}">
                        @csrf
                        <button type="submit" class="btn-primary">Enable Two-Factor</button>
                    </form>
                @endif
            </section>

        </div>
    </div>
</div>
@endsection
