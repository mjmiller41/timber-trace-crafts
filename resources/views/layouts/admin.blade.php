<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@stack('title', 'Dashboard') | TTC Admin</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@300;400;500;600&family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/admin.css', 'resources/js/admin.js'])
    @stack('head')
</head>
<body class="bg-gray-50 antialiased">

    <div
        class="flex min-h-screen"
        x-data="{ ...confirmDelete(), ...orderNotifier() }"
        x-init="init()"
        x-on:confirm-delete.window="open($event.detail.form, $event.detail.message)"
    >
        {{-- Sidebar --}}
        @include('components.admin.sidebar')

        {{-- Main content --}}
        <div class="admin-main flex-1 flex flex-col" id="admin-main">

            {{-- Topbar --}}
            @include('components.admin.topbar')

            {{-- Flash messages --}}
            @if(session('success'))
            <div style="margin: 1rem 1.5rem 0; padding: 0.75rem 1rem; background: #dcfce7; border: 1px solid #86efac; border-radius: 0.375rem; color: #166534; font-size: 0.875rem;">
                ✓ {{ session('success') }}
            </div>
            @endif
            @if(session('success_etsy'))
            <div style="margin: 0.5rem 1.5rem 0; padding: 0.75rem 1rem; background: #eff6ff; border: 1px solid #93c5fd; border-radius: 0.375rem; color: #1e40af; font-size: 0.875rem;">
                ↑ {{ session('success_etsy') }}
            </div>
            @endif
            @if(session('error_etsy'))
            <div style="margin: 0.5rem 1.5rem 0; padding: 0.75rem 1rem; background: #fee2e2; border: 1px solid #fca5a5; border-radius: 0.375rem; color: #991b1b; font-size: 0.875rem;">
                ✕ {{ session('error_etsy') }}
            </div>
            @endif
            @if(session('error'))
            <div style="margin: 1rem 1.5rem 0; padding: 0.75rem 1rem; background: #fee2e2; border: 1px solid #fca5a5; border-radius: 0.375rem; color: #991b1b; font-size: 0.875rem;">
                ✕ {{ session('error') }}
            </div>
            @endif

            {{-- Page content --}}
            <div class="p-6 flex-1">
                @yield('content')
            </div>

        </div>

        {{-- Etsy new order toast --}}
        <div
            x-show="toast"
            x-cloak
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-2"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 translate-y-2"
            style="position: fixed; bottom: 1.5rem; right: 1.5rem; z-index: 9999; display: flex; align-items: center; gap: 0.75rem; background: #2C4C3B; color: #fff; padding: 0.875rem 1.125rem; border-radius: 0.5rem; box-shadow: 0 4px 16px rgba(0,0,0,0.2); font-size: 0.875rem; max-width: 320px;"
        >
            <span style="font-size: 1.25rem;">🛒</span>
            <span style="flex: 1;">
                <strong x-text="count"></strong> new Etsy order<span x-show="count !== 1">s</span> —
                <a href="{{ route('admin.orders.index') }}" style="color: #fde68a; text-decoration: underline;">View orders</a>
            </span>
            <button type="button" x-on:click="dismiss()" style="background: none; border: none; color: rgba(255,255,255,0.6); cursor: pointer; font-size: 1rem; line-height: 1; padding: 0;">&times;</button>
        </div>

        {{-- Confirm Delete Modal --}}
        <div
            x-show="show"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="admin-modal-overlay"
            style="display: none;"
            x-on:keydown.escape.window="cancel()"
        >
            <div
                x-show="show"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="admin-modal"
                @click.stop
            >
                <h3 class="text-xl font-heading font-light text-charcoal mb-2">Confirm Delete</h3>
                <p class="text-sm text-gray-600 mb-6" x-text="message"></p>
                <div class="flex gap-3 justify-end">
                    <button
                        type="button"
                        class="admin-btn admin-btn-outline"
                        x-on:click="cancel()"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        class="admin-btn admin-btn-danger"
                        x-on:click="confirm()"
                    >
                        Delete
                    </button>
                </div>
            </div>
        </div>

    </div>

    @stack('scripts')

</body>
</html>
