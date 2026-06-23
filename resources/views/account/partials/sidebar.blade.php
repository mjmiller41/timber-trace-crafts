@php
    $navLinks = [
        ['label' => 'Dashboard',   'route' => 'account.dashboard'],
        ['label' => 'My Orders',   'route' => 'account.orders'],
        ['label' => 'Wishlist',    'route' => 'account.wishlist'],
        ['label' => 'Addresses',   'route' => 'account.addresses'],
        ['label' => 'Profile',     'route' => 'account.profile'],
    ];
@endphp

<aside class="w-full lg:w-64 shrink-0">
    <div class="mb-4 lg:mb-0">
        <p class="section-label mb-4 hidden lg:block">My Account</p>
        <nav>
            <ul class="flex flex-row lg:flex-col gap-1 overflow-x-auto lg:overflow-visible pb-2 lg:pb-0">
                @foreach($navLinks as $link)
                    @php
                        $isActive = request()->routeIs($link['route']);
                    @endphp
                    <li class="shrink-0">
                        <a
                            href="{{ route($link['route']) }}"
                            class="block py-2 px-3 lg:px-4 text-sm font-medium transition-colors whitespace-nowrap
                                {{ $isActive
                                    ? 'text-forest-green font-semibold border-b-2 lg:border-b-0 lg:border-l-2 border-forest-green'
                                    : 'text-charcoal hover:text-forest-green border-b-2 lg:border-b-0 lg:border-l-2 border-transparent' }}"
                        >
                            {{ $link['label'] }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </nav>
        <div class="mt-4 lg:mt-8 hidden lg:block">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="text-xs font-semibold uppercase tracking-widest text-walnut hover:text-charcoal transition-colors">
                    Sign Out
                </button>
            </form>
        </div>
    </div>
</aside>
