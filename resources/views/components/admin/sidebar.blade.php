{{-- Admin sidebar --}}
<aside
    class="admin-sidebar"
    x-data="sidebar()"
    :class="{ 'collapsed': collapsed }"
    id="admin-sidebar"
>
    {{-- Logo --}}
    <div class="admin-sidebar-logo">
        <a href="{{ url('/admin') }}" style="color: inherit; text-decoration: none;">
            <span x-show="!collapsed" x-cloak style="font-family: 'Playfair Display', serif; font-weight: 300; font-size: 1.125rem; color: #F4F1EA;">TTC Admin</span>
            <span x-show="collapsed" x-cloak style="font-family: 'Playfair Display', serif; font-weight: 300; font-size: 1.125rem; color: #F4F1EA;">TTC</span>
        </a>
    </div>

    {{-- Collapse toggle --}}
    <button
        type="button"
        x-on:click="toggle()"
        style="display: flex; align-items: center; justify-content: center; width: 100%; padding: 0.5rem; background: transparent; border: none; border-bottom: 1px solid rgba(255,255,255,0.08); color: rgba(255,255,255,0.4); cursor: pointer; transition: color 0.15s;"
        onmouseover="this.style.color='rgba(255,255,255,0.7)'"
        onmouseout="this.style.color='rgba(255,255,255,0.4)'"
        :aria-label="collapsed ? 'Expand sidebar' : 'Collapse sidebar'"
    >
        <template x-if="!collapsed">
            <span style="display: flex; align-items: center; gap: 0.375rem; font-size: 0.625rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.1em; font-family: 'Montserrat', sans-serif;">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 14px; height: 14px;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                </svg>
                Collapse
            </span>
        </template>
        <template x-if="collapsed">
            <span style="display: flex; align-items: center; justify-content: center;">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 14px; height: 14px;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                </svg>
            </span>
        </template>
    </button>

    {{-- Navigation --}}
    <nav class="admin-sidebar-nav">

        {{-- Main --}}
        <div class="admin-sidebar-section">
            <div class="admin-sidebar-section-label" x-show="!collapsed" x-cloak>Main</div>

            <a
                href="{{ url('/admin') }}"
                class="admin-sidebar-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}"
                title="Dashboard"
            >
                <span class="nav-icon">&#x2302;</span>
                <span x-show="!collapsed" x-cloak>Dashboard</span>
            </a>

            <a
                href="{{ url('/admin/orders') }}"
                class="admin-sidebar-link {{ request()->is('admin/orders*') ? 'active' : '' }}"
                title="Orders"
                style="position: relative;"
            >
                <span class="nav-icon" style="position: relative; display: inline-block;">
                    &#x1F4E6;
                    <span
                        x-show="count > 0" x-cloak
                        x-cloak
                        x-text="count"
                        style="position: absolute; top: -6px; right: -8px; background: #ef4444; color: #fff; font-size: 0.625rem; font-weight: 700; line-height: 1; padding: 2px 4px; border-radius: 9999px; min-width: 14px; text-align: center;"
                    ></span>
                </span>
                <span x-show="!collapsed" x-cloak>
                    Orders
                    <span x-show="count > 0" x-cloak x-text="'(' + count + ')'" style="font-size: 0.75rem; color: #fca5a5; margin-left: 0.25rem;"></span>
                </span>
            </a>
        </div>

        {{-- Catalog --}}
        <div class="admin-sidebar-section" style="margin-top: 0.5rem;">
            <div class="admin-sidebar-section-label" x-show="!collapsed" x-cloak>Catalog</div>

            <a
                href="{{ url('/admin/products') }}"
                class="admin-sidebar-link {{ request()->is('admin/products*') ? 'active' : '' }}"
                title="Products"
            >
                <span class="nav-icon">&#x2B1C;</span>
                <span x-show="!collapsed" x-cloak>Products</span>
            </a>

            <a
                href="{{ url('/admin/categories') }}"
                class="admin-sidebar-link {{ request()->is('admin/categories*') ? 'active' : '' }}"
                title="Categories & Tags"
            >
                <span class="nav-icon">&#x1F3F7;</span>
                <span x-show="!collapsed" x-cloak>Categories &amp; Tags</span>
            </a>

            <a
                href="{{ url('/admin/media') }}"
                class="admin-sidebar-link {{ request()->is('admin/media*') ? 'active' : '' }}"
                title="Media"
            >
                <span class="nav-icon">&#x1F4F7;</span>
                <span x-show="!collapsed" x-cloak>Media</span>
            </a>
        </div>

        {{-- Commerce --}}
        <div class="admin-sidebar-section" style="margin-top: 0.5rem;">
            <div class="admin-sidebar-section-label" x-show="!collapsed" x-cloak>Commerce</div>

            <a
                href="{{ url('/admin/coupons') }}"
                class="admin-sidebar-link {{ request()->is('admin/coupons*') ? 'active' : '' }}"
                title="Coupons"
            >
                <span class="nav-icon">&#x1F3F7;</span>
                <span x-show="!collapsed" x-cloak>Coupons</span>
            </a>

            <a
                href="{{ url('/admin/customers') }}"
                class="admin-sidebar-link {{ request()->is('admin/customers*') ? 'active' : '' }}"
                title="Customers"
            >
                <span class="nav-icon">&#x1F465;</span>
                <span x-show="!collapsed" x-cloak>Customers</span>
            </a>

            <a
                href="{{ url('/admin/reviews') }}"
                class="admin-sidebar-link {{ request()->is('admin/reviews*') ? 'active' : '' }}"
                title="Reviews"
            >
                <span class="nav-icon">&#x2605;</span>
                <span x-show="!collapsed" x-cloak>Reviews</span>
            </a>
        </div>

        {{-- Integrations --}}
        <div class="admin-sidebar-section" style="margin-top: 0.5rem;">
            <div class="admin-sidebar-section-label" x-show="!collapsed" x-cloak>Integrations</div>

            <a
                href="{{ route('admin.etsy.index') }}"
                class="admin-sidebar-link {{ request()->is('admin/etsy*') ? 'active' : '' }}"
                title="Etsy"
            >
                <span class="nav-icon">&#x1F6D2;</span>
                <span x-show="!collapsed" x-cloak>Etsy</span>
            </a>
        </div>

        {{-- Content --}}
        <div class="admin-sidebar-section" style="margin-top: 0.5rem;">
            <div class="admin-sidebar-section-label" x-show="!collapsed" x-cloak>Content</div>

            <a
                href="{{ url('/admin/journal') }}"
                class="admin-sidebar-link {{ request()->is('admin/journal*') ? 'active' : '' }}"
                title="Journal"
            >
                <span class="nav-icon">&#x1F4DD;</span>
                <span x-show="!collapsed" x-cloak>Journal</span>
            </a>

            <a
                href="{{ url('/admin/messages') }}"
                class="admin-sidebar-link {{ request()->is('admin/messages*') ? 'active' : '' }}"
                title="Messages"
            >
                <span class="nav-icon">&#x2709;</span>
                <span x-show="!collapsed" x-cloak>Messages</span>
            </a>

            <a
                href="{{ route('admin.inbox.index') }}"
                class="admin-sidebar-link {{ request()->is('admin/inbox*') ? 'active' : '' }}"
                title="Inbox"
            >
                <span class="nav-icon">&#x1F4E5;</span>
                <span x-show="!collapsed" x-cloak>Inbox</span>
            </a>

            <a
                href="{{ url('/admin/pages') }}"
                class="admin-sidebar-link {{ request()->is('admin/pages*') ? 'active' : '' }}"
                title="Pages"
            >
                <span class="nav-icon">&#x1F4C4;</span>
                <span x-show="!collapsed" x-cloak>Pages</span>
            </a>
        </div>

        {{-- System --}}
        <div class="admin-sidebar-section" style="margin-top: 0.5rem;">
            <div class="admin-sidebar-section-label" x-show="!collapsed" x-cloak>System</div>

            <a
                href="{{ url('/admin/reports') }}"
                class="admin-sidebar-link {{ request()->is('admin/reports*') ? 'active' : '' }}"
                title="Reports"
            >
                <span class="nav-icon">&#x1F4CA;</span>
                <span x-show="!collapsed" x-cloak>Reports</span>
            </a>

            <a
                href="{{ url('/admin/settings') }}"
                class="admin-sidebar-link {{ request()->is('admin/settings*') ? 'active' : '' }}"
                title="Settings"
            >
                <span class="nav-icon">&#x2699;</span>
                <span x-show="!collapsed" x-cloak>Settings</span>
            </a>

            <a
                href="{{ route('admin.audit.index') }}"
                class="admin-sidebar-link {{ request()->is('admin/audit*') ? 'active' : '' }}"
                title="Audit Log"
            >
                <span class="nav-icon">&#x1F4DC;</span>
                <span x-show="!collapsed" x-cloak>Audit Log</span>
            </a>

            <a
                href="{{ route('admin.errors.index') }}"
                class="admin-sidebar-link {{ request()->is('admin/errors*') ? 'active' : '' }}"
                title="Error Log"
            >
                <span class="nav-icon">&#x1F41E;</span>
                <span x-show="!collapsed" x-cloak>Error Log</span>
            </a>
        </div>

    </nav>

    {{-- Footer: logout --}}
    <div class="admin-sidebar-footer">
        <form method="POST" action="{{ url('/logout') }}">
            @csrf
            <button
                type="submit"
                class="admin-sidebar-link w-full text-left"
                style="background: none; border: none; cursor: pointer; width: 100%;"
                title="Log out"
            >
                <span class="nav-icon">&#x2192;</span>
                <span x-show="!collapsed" x-cloak style="font-size: 0.8125rem; font-weight: 500;">Log out</span>
            </button>
        </form>
    </div>

</aside>

{{-- Push main content when sidebar is open --}}
<script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
    (function () {
        // Sync admin-main margin with sidebar collapsed state on load
        const collapsed = localStorage.getItem('sidebarCollapsed') === 'true'
        const main = document.getElementById('admin-main')
        if (main && collapsed) main.classList.add('sidebar-collapsed')

        // Watch for Alpine toggling
        document.addEventListener('alpine:initialized', () => {
            const sidebar = document.getElementById('admin-sidebar')
            if (!sidebar) return
            const observer = new MutationObserver(() => {
                const isCollapsed = sidebar.classList.contains('collapsed')
                if (main) main.classList.toggle('sidebar-collapsed', isCollapsed)
            })
            observer.observe(sidebar, { attributes: true, attributeFilter: ['class'] })
        })
    })()
</script>
