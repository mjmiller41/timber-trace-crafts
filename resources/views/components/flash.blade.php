{{-- Flash messages — auto-dismiss after 4s via Alpine flash() component --}}

@if(session('success') || session('error') || session('info') || session('warning'))
    <div
        x-data="flash()"
        x-show="show"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-2"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 translate-y-2"
        class="fixed top-4 right-4 z-[200] flex flex-col gap-2 max-w-sm w-full"
        style="display: none;"
    >
        @if(session('success'))
            <div
                class="flex items-start gap-3 px-4 py-3"
                style="background-color: #1a3a27; color: #d1fae5; font-family: 'Montserrat', sans-serif; font-size: 0.875rem; font-weight: 500;"
            >
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5 flex-shrink-0 mt-0.5" style="color: #6ee7b7;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div class="flex-1 min-w-0">
                    <p style="margin: 0; line-height: 1.5;">{{ session('success') }}</p>
                </div>
                <button
                    type="button"
                    x-on:click="show = false"
                    class="flex-shrink-0 ml-1"
                    style="color: rgba(209,250,229,0.6); background: none; border: none; cursor: pointer; padding: 0; line-height: 1;"
                    aria-label="Dismiss"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        @endif

        @if(session('error'))
            <div
                class="flex items-start gap-3 px-4 py-3"
                style="background-color: #450a0a; color: #fecaca; font-family: 'Montserrat', sans-serif; font-size: 0.875rem; font-weight: 500;"
            >
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5 flex-shrink-0 mt-0.5" style="color: #f87171;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                </svg>
                <div class="flex-1 min-w-0">
                    <p style="margin: 0; line-height: 1.5;">{{ session('error') }}</p>
                </div>
                <button
                    type="button"
                    x-on:click="show = false"
                    class="flex-shrink-0 ml-1"
                    style="color: rgba(254,202,202,0.6); background: none; border: none; cursor: pointer; padding: 0; line-height: 1;"
                    aria-label="Dismiss"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        @endif

        @if(session('info'))
            <div
                class="flex items-start gap-3 px-4 py-3"
                style="background-color: #1e3a5f; color: #bfdbfe; font-family: 'Montserrat', sans-serif; font-size: 0.875rem; font-weight: 500;"
            >
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5 flex-shrink-0 mt-0.5" style="color: #60a5fa;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                </svg>
                <div class="flex-1 min-w-0">
                    <p style="margin: 0; line-height: 1.5;">{{ session('info') }}</p>
                </div>
                <button
                    type="button"
                    x-on:click="show = false"
                    class="flex-shrink-0 ml-1"
                    style="color: rgba(191,219,254,0.6); background: none; border: none; cursor: pointer; padding: 0; line-height: 1;"
                    aria-label="Dismiss"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        @endif

        @if(session('warning'))
            <div
                class="flex items-start gap-3 px-4 py-3"
                style="background-color: #451a03; color: #fde68a; font-family: 'Montserrat', sans-serif; font-size: 0.875rem; font-weight: 500;"
            >
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5 flex-shrink-0 mt-0.5" style="color: #fbbf24;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                </svg>
                <div class="flex-1 min-w-0">
                    <p style="margin: 0; line-height: 1.5;">{{ session('warning') }}</p>
                </div>
                <button
                    type="button"
                    x-on:click="show = false"
                    class="flex-shrink-0 ml-1"
                    style="color: rgba(253,230,138,0.6); background: none; border: none; cursor: pointer; padding: 0; line-height: 1;"
                    aria-label="Dismiss"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        @endif

    </div>
@endif
