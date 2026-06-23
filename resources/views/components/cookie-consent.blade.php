<div
    x-data="{
        show: false,
        init() {
            if (!localStorage.getItem('cookie_consent')) {
                this.show = true
            }
        },
        accept() {
            localStorage.setItem('cookie_consent', 'accepted')
            this.show = false
        },
        decline() {
            localStorage.setItem('cookie_consent', 'declined')
            this.show = false
        }
    }"
    x-show="show"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 translate-y-4"
    x-transition:enter-end="opacity-100 translate-y-0"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100 translate-y-0"
    x-transition:leave-end="opacity-0 translate-y-4"
    class="fixed bottom-0 left-0 right-0 z-50 bg-charcoal text-oak-sand border-t border-walnut/30"
    role="dialog"
    aria-live="polite"
    aria-label="Cookie consent"
    style="display: none"
>
    <div class="page-container py-4">
        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4 justify-between">
            <p class="font-body text-sm text-oak-sand/80 max-w-2xl">
                We use essential cookies to keep the shop working (cart, sessions). We do not use advertising trackers.
                <a href="{{ route('page.show', 'privacy-policy') }}" class="underline text-oak-sand/60 hover:text-oak-sand transition-colors ml-1">Privacy Policy</a>
            </p>
            <div class="flex items-center gap-3 flex-shrink-0">
                <button
                    @click="decline"
                    class="font-body text-sm text-oak-sand/50 hover:text-oak-sand transition-colors underline whitespace-nowrap"
                >
                    Decline non-essential
                </button>
                <button
                    @click="accept"
                    class="font-body text-sm bg-forest-green text-white px-5 py-2 hover:bg-forest-green/90 transition-colors whitespace-nowrap"
                >
                    Accept
                </button>
            </div>
        </div>
    </div>
</div>
