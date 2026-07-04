{{-- Public site footer --}}
<footer style="background-color: #2C4C3B; color: #F4F1EA;">

    {{-- Main footer grid --}}
    <div class="page-container py-14 md:py-16">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-10 md:gap-12">

            {{-- Col 1: Brand --}}
            <div>
                <p style="font-family: var(--font-heading); font-weight: 400; font-size: 1.375rem; letter-spacing: 0.01em; color: #F4F1EA; margin-bottom: 0.75rem;">
                    {{ $siteName }}
                </p>
                <p style="font-size: 0.8125rem; line-height: 1.7; color: rgba(244, 241, 234, 0.65); max-width: 32ch;">
                    {{ $siteTagline }}
                </p>
            </div>

            {{-- Col 2: Links --}}
            <div class="grid grid-cols-2 gap-6">
                <div>
                    <p style="font-size: 0.6875rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.14em; color: rgba(244, 241, 234, 0.45); margin-bottom: 0.875rem;">Shop</p>
                    <ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 0.5rem;">
                        <li><a href="{{ route('shop') }}?category=jewelry" class="footer-link">Jewelry</a></li>
                        <li><a href="{{ route('shop') }}?category=boxes" class="footer-link">Boxes</a></li>
                        <li><a href="{{ route('shop') }}?category=tumblers" class="footer-link">Tumblers</a></li>
                        <li><a href="{{ route('shop') }}" class="footer-link">All Products</a></li>
                    </ul>
                </div>
                <div>
                    <p style="font-size: 0.6875rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.14em; color: rgba(244, 241, 234, 0.45); margin-bottom: 0.875rem;">Info</p>
                    <ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 0.5rem;">
                        <li><a href="{{ route('page.show', 'about-us') }}" class="footer-link">About</a></li>
                        <li><a href="{{ route('gallery.index') }}" class="footer-link">Gallery</a></li>
                        <li><a href="{{ route('journal.index') }}" class="footer-link">Journal</a></li>
                        <li><a href="{{ route('contact.index') }}" class="footer-link">Contact</a></li>
                        <li><a href="{{ route('page.show', 'faq') }}" class="footer-link">FAQ</a></li>
                        <li><a href="{{ route('page.show', 'care-guide') }}" class="footer-link">Care Guide</a></li>
                    </ul>
                </div>
            </div>

            {{-- Col 3: Social + Newsletter --}}
            <div>
                <p style="font-size: 0.6875rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.14em; color: rgba(244, 241, 234, 0.45); margin-bottom: 0.875rem;">Follow Along</p>

                {{-- Social links --}}
                <div style="display: flex; gap: 1rem; margin-bottom: 1.5rem;">
                    <a href="{{ $socialInstagram ?: 'https://instagram.com' }}"
                       target="_blank" rel="noopener noreferrer" aria-label="Instagram"
                       class="footer-social-link">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M8 0C5.829 0 5.556.01 4.703.048 3.85.088 3.269.222 2.76.42a3.917 3.917 0 0 0-1.417.923A3.927 3.927 0 0 0 .42 2.76C.222 3.268.087 3.85.048 4.7.01 5.555 0 5.827 0 8.001c0 2.172.01 2.444.048 3.297.04.852.174 1.433.372 1.942.205.526.478.972.923 1.417.444.445.89.719 1.416.923.51.198 1.09.333 1.942.372C5.555 15.99 5.827 16 8 16s2.444-.01 3.298-.048c.851-.04 1.434-.174 1.943-.372a3.916 3.916 0 0 0 1.416-.923c.445-.445.718-.891.923-1.417.197-.509.332-1.09.372-1.942C15.99 10.445 16 10.173 16 8s-.01-2.445-.048-3.299c-.04-.851-.175-1.433-.372-1.941a3.926 3.926 0 0 0-.923-1.417A3.911 3.911 0 0 0 13.24.42c-.51-.198-1.092-.333-1.943-.372C10.443.01 10.172 0 7.998 0h.003zm-.717 1.442h.718c2.136 0 2.389.007 3.232.046.78.035 1.204.166 1.486.275.373.145.64.319.92.599.28.28.453.546.598.92.11.281.24.705.275 1.485.039.843.047 1.096.047 3.231s-.008 2.389-.047 3.232c-.035.78-.166 1.203-.275 1.485a2.47 2.47 0 0 1-.599.919c-.28.28-.546.453-.92.598-.28.11-.704.24-1.485.276-.843.038-1.096.047-3.232.047s-2.39-.009-3.233-.047c-.78-.036-1.203-.166-1.485-.276a2.478 2.478 0 0 1-.92-.598 2.48 2.48 0 0 1-.6-.92c-.109-.281-.24-.705-.275-1.485-.038-.843-.046-1.096-.046-3.233 0-2.136.008-2.388.046-3.231.036-.78.166-1.204.276-1.486.145-.373.319-.64.599-.92.28-.28.546-.453.92-.598.282-.11.705-.24 1.485-.276.738-.034 1.024-.044 2.515-.045v.002zm4.988 1.328a.96.96 0 1 0 0 1.92.96.96 0 0 0 0-1.92zm-4.27 1.122a4.109 4.109 0 1 0 0 8.217 4.109 4.109 0 0 0 0-8.217zm0 1.441a2.667 2.667 0 1 1 0 5.334 2.667 2.667 0 0 1 0-5.334z"/>
                        </svg>
                    </a>
                    <a href="{{ $socialFacebook ?: 'https://facebook.com' }}"
                       target="_blank" rel="noopener noreferrer" aria-label="Facebook"
                       class="footer-social-link">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M16 8.049c0-4.446-3.582-8.05-8-8.05C3.58 0-.002 3.603-.002 8.05c0 4.017 2.926 7.347 6.75 7.951v-5.625h-2.03V8.05H6.75V6.275c0-2.017 1.195-3.131 3.022-3.131.876 0 1.791.157 1.791.157v1.98h-1.009c-.993 0-1.303.621-1.303 1.258v1.51h2.218l-.354 2.326H9.25V16c3.824-.604 6.75-3.934 6.75-7.951z"/>
                        </svg>
                    </a>
                    <a href="{{ $socialPinterest ?: 'https://pinterest.com' }}"
                       target="_blank" rel="noopener noreferrer" aria-label="Pinterest"
                       class="footer-social-link">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M8 0a8 8 0 0 0-2.915 15.452c-.07-.633-.134-1.606.027-2.297.146-.625.984-4.171.984-4.171s-.252-.503-.252-1.248c0-1.169.68-2.042 1.526-2.042.719 0 1.068.54 1.068 1.187 0 .723-.461 1.807-.699 2.814-.2.84.421 1.524 1.247 1.524 1.494 0 2.643-1.575 2.643-3.845 0-2.010-1.445-3.415-3.506-3.415-2.389 0-3.79 1.792-3.79 3.645 0 .721.278 1.494.625 1.916a.25.25 0 0 1 .057.243c-.064.262-.206.840-.234.958-.037.155-.123.188-.284.113-1.059-.494-1.720-2.044-1.720-3.290 0-2.674 1.944-5.132 5.608-5.132 2.942 0 5.231 2.097 5.231 4.897 0 2.923-1.842 5.275-4.398 5.275-.860 0-1.667-.447-1.943-.975l-.528 1.972c-.191.736-.708 1.658-1.054 2.219A8 8 0 1 0 8 0z"/>
                        </svg>
                    </a>
                </div>

                {{-- Newsletter signup --}}
                <p style="font-size: 0.6875rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.14em; color: rgba(244, 241, 234, 0.45); margin-bottom: 0.75rem;">Newsletter</p>
                <p style="font-size: 0.8125rem; color: rgba(244,241,234,0.6); margin-bottom: 0.75rem; line-height: 1.6;">New pieces, studio updates, and quiet inspiration.</p>
                <form action="{{ route('newsletter.store') }}" method="POST" class="flex gap-0">
                    @csrf
                    <input
                        type="email"
                        name="email"
                        placeholder="your@email.com"
												autocomplete="email"
                        required
                        style="flex: 1; min-width: 0; padding: 0.5rem 0.75rem; font-size: 0.8125rem; background-color: rgba(255,255,255,0.1); border: 1px solid rgba(244,241,234,0.25); color: #F4F1EA; outline: none; font-family: var(--font-body);"
                    >
                    <button
                        type="submit"
                        style="padding: 0.5rem 1rem; background-color: #F4F1EA; color: #2C4C3B; font-size: 0.6875rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; border: none; cursor: pointer; white-space: nowrap; font-family: var(--font-body); flex-shrink: 0;"
                    >Join</button>
                </form>

            </div>

        </div>
    </div>

    {{-- Bottom bar --}}
    <div style="border-top: 1px solid rgba(244,241,234,0.12);">
        <div class="page-container py-4 flex flex-col sm:flex-row items-center justify-between gap-3">
            <p style="font-size: 0.6875rem; color: rgba(244,241,234,0.4);">
                &copy; {{ date('Y') }} {{ $siteName }}. All rights reserved.
            </p>
            <div style="display: flex; gap: 1.25rem; flex-wrap: wrap; justify-content: center;">
                <a href="{{ route('page.show', 'privacy-policy') }}" class="footer-legal-link">Privacy Policy</a>
                <a href="{{ route('page.show', 'terms-and-conditions') }}" class="footer-legal-link">Terms &amp; Conditions</a>
                <a href="{{ route('page.show', 'return-policy') }}" class="footer-legal-link">Return Policy</a>
                <a href="{{ route('page.show', 'shipping-policy') }}" class="footer-legal-link">Shipping Policy</a>
            </div>
        </div>
    </div>

</footer>
