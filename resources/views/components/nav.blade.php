{{-- Public site navigation --}}
<header
    class="sticky top-0 z-50 bg-oak-sand border-b"
    style="border-color: rgb(140 123 108 / 0.2);"
    x-data="mobileNav()"
>

    {{-- Desktop nav --}}
    <div class="page-container hidden md:flex items-center justify-between h-16">

        {{-- Logo --}}
        <a href="{{ url('/') }}" class="flex-shrink-0 flex items-center gap-2.5">
            <img src="{{ $siteLogoUrl }}" alt="{{ $siteName }}" width="200" height="40" class="h-10 w-auto">
            <span style="font-family: 'Playfair Display', serif; font-weight: 300; font-size: 1.375rem; letter-spacing: 0.01em; color: #333333;">{{ $siteName }}</span>
        </a>

        {{-- Center nav links --}}
        <nav class="flex items-center gap-8">
            <a
                href="{{ url('/shop') }}"
                class="section-label transition-colors hover:text-charcoal {{ request()->is('shop*') ? 'text-charcoal' : '' }}"
            >Shop</a>
            <a
                href="{{ url('/journal') }}"
                class="section-label transition-colors hover:text-charcoal {{ request()->is('journal*') ? 'text-charcoal' : '' }}"
            >Journal</a>
            <a
                href="{{ url('/about-us') }}"
                class="section-label transition-colors hover:text-charcoal {{ request()->is('about-us') ? 'text-charcoal' : '' }}"
            >About</a>
            <a
                href="{{ url('/contact') }}"
                class="section-label transition-colors hover:text-charcoal {{ request()->is('contact') ? 'text-charcoal' : '' }}"
            >Contact</a>
        </nav>

        {{-- Right icons --}}
        <div class="flex items-center gap-5">

            {{-- Account --}}
            @auth
                <a href="{{ url('/account') }}" class="flex items-center gap-1.5 section-label hover:text-charcoal transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                    </svg>
                </a>
            @else
                <a href="{{ url('/login') }}" class="section-label hover:text-charcoal transition-colors">Sign in</a>
            @endauth

            {{-- Cart --}}
            <a href="{{ url('/cart') }}" class="relative flex items-center section-label hover:text-charcoal transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007zM8.625 10.5a.375.375 0 11-.75 0 .375.375 0 01.75 0zm5.625 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                </svg>
                @if(session('cart_count', 0) > 0)
                    <span
                        class="absolute -top-2 -right-2 flex items-center justify-center w-4 h-4 rounded-full text-white"
                        style="background-color: #2C4C3B; font-size: 0.625rem; font-weight: 700; font-family: 'Montserrat', sans-serif;"
                    >{{ session('cart_count', 0) }}</span>
                @endif
            </a>

        </div>

    </div>

    {{-- Mobile top bar --}}
    <div class="md:hidden flex items-center justify-between px-4 h-14">

        {{-- Hamburger --}}
        <button
            type="button"
            class="flex items-center justify-center w-8 h-8 text-charcoal"
            x-on:click="open = true"
            aria-label="Open navigation"
        >
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
            </svg>
        </button>

        {{-- Brand --}}
        <a href="{{ url('/') }}" class="flex items-center gap-2">
            <img src="{{ $siteLogoUrl }}" alt="{{ $siteName }}" width="160" height="32" class="h-8 w-auto">
            <span style="font-family: 'Playfair Display', serif; font-weight: 300; font-size: 1.125rem; color: #333333;">{{ $siteName }}</span>
        </a>

        {{-- Cart --}}
        <a href="{{ url('/cart') }}" class="relative flex items-center justify-center w-8 h-8 text-charcoal">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007zM8.625 10.5a.375.375 0 11-.75 0 .375.375 0 01.75 0zm5.625 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
            </svg>
            @if(session('cart_count', 0) > 0)
                <span
                    class="absolute -top-1 -right-1 flex items-center justify-center w-4 h-4 rounded-full text-white"
                    style="background-color: #2C4C3B; font-size: 0.625rem; font-weight: 700;"
                >{{ session('cart_count', 0) }}</span>
            @endif
        </a>

    </div>

    {{-- Mobile drawer overlay --}}
    <div
        x-show="open"
        x-transition:enter="transition-opacity ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 bg-black/40 md:hidden"
        style="display: none;"
        x-on:click="open = false"
    >
        {{-- Drawer panel --}}
        <div
            x-show="open"
            x-transition:enter="transition ease-out duration-250"
            x-transition:enter-start="-translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="-translate-x-full"
            class="absolute left-0 top-0 bottom-0 w-72 flex flex-col"
            style="background-color: #F4F1EA;"
            x-on:click.stop
        >
            {{-- Drawer header --}}
            <div class="flex items-center justify-between px-5 h-14 border-b" style="border-color: rgb(140 123 108 / 0.2);">
                <img src="{{ $siteLogoUrl }}" alt="{{ $siteName }}" width="160" height="32" class="h-8 w-auto">
                <span style="font-family: 'Playfair Display', serif; font-weight: 300; font-size: 1.125rem; color: #333333;">{{ $siteName }}</span>
                <button
                    type="button"
                    class="text-charcoal"
                    x-on:click="open = false"
                    aria-label="Close navigation"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            {{-- Drawer nav --}}
            <nav class="flex-1 px-5 py-6 flex flex-col gap-1">
                <a
                    href="{{ url('/shop') }}"
                    class="flex items-center py-3 section-label border-b {{ request()->is('shop*') ? 'text-charcoal' : '' }}"
                    style="border-color: rgb(140 123 108 / 0.15);"
                    x-on:click="open = false"
                >Shop</a>
                <a
                    href="{{ url('/journal') }}"
                    class="flex items-center py-3 section-label border-b {{ request()->is('journal*') ? 'text-charcoal' : '' }}"
                    style="border-color: rgb(140 123 108 / 0.15);"
                    x-on:click="open = false"
                >Journal</a>
                <a
                    href="{{ url('/about-us') }}"
                    class="flex items-center py-3 section-label border-b {{ request()->is('about-us') ? 'text-charcoal' : '' }}"
                    style="border-color: rgb(140 123 108 / 0.15);"
                    x-on:click="open = false"
                >About</a>
                <a
                    href="{{ url('/contact') }}"
                    class="flex items-center py-3 section-label {{ request()->is('contact') ? 'text-charcoal' : '' }}"
                    x-on:click="open = false"
                >Contact</a>
            </nav>

            {{-- Drawer footer --}}
            <div class="px-5 py-5 border-t" style="border-color: rgb(140 123 108 / 0.2);">
                @auth
                    <a href="{{ url('/account') }}" class="section-label hover:text-charcoal transition-colors">My Account</a>
                @else
                    <div class="flex gap-4">
                        <a href="{{ url('/login') }}" class="section-label hover:text-charcoal transition-colors">Sign In</a>
                        <span class="text-walnut">·</span>
                        <a href="{{ url('/register') }}" class="section-label hover:text-charcoal transition-colors">Register</a>
                    </div>
                @endauth
            </div>

        </div>
    </div>

</header>
