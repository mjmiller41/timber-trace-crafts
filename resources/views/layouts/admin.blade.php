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
        x-data="confirmDelete()"
        x-on:confirm-delete.window="open($event.detail.form)"
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
