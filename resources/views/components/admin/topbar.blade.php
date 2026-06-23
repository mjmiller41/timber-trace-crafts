{{-- Admin top bar --}}
<div class="admin-topbar">

    {{-- Left: Page title --}}
    <h1 class="admin-topbar-title">
        @yield('page-title', 'Dashboard')
    </h1>

    {{-- Right: Admin info + actions --}}
    <div class="admin-topbar-actions">

        {{-- View site --}}
        <a
            href="{{ url('/') }}"
            target="_blank"
            rel="noopener noreferrer"
            style="display: flex; align-items: center; gap: 0.375rem; font-size: 0.8125rem; color: #8C7B6C; transition: color 0.15s;"
            onmouseover="this.style.color='#333333'"
            onmouseout="this.style.color='#8C7B6C'"
        >
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 14px; height: 14px;">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
            </svg>
            View site
        </a>

        {{-- Divider --}}
        <span style="color: #e5e7eb;">|</span>

        {{-- Admin name --}}
        @auth
            <div style="display: flex; align-items: center; gap: 0.5rem;">
                <div
                    style="width: 28px; height: 28px; border-radius: 50%; background-color: #2C4C3B; display: flex; align-items: center; justify-content: center; color: #F4F1EA; font-size: 0.6875rem; font-weight: 700; font-family: 'Montserrat', sans-serif; flex-shrink: 0;"
                >
                    {{ strtoupper(substr(Auth::user()->name ?? 'A', 0, 1)) }}
                </div>
                <span style="font-size: 0.8125rem; color: #333333; font-weight: 500;">{{ Auth::user()->name ?? 'Admin' }}</span>
            </div>

            <form method="POST" action="{{ url('/logout') }}" style="display: inline;">
                @csrf
                <button
                    type="submit"
                    style="background: none; border: none; cursor: pointer; font-size: 0.8125rem; color: #8C7B6C; font-family: 'Montserrat', sans-serif; padding: 0; transition: color 0.15s;"
                    onmouseover="this.style.color='#ba1a1a'"
                    onmouseout="this.style.color='#8C7B6C'"
                >
                    Sign out
                </button>
            </form>
        @endauth

    </div>

</div>
