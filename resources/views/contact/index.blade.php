@extends('layouts.app')

@section('title', 'Contact Us')
@section('meta_description', "Get in touch with Timber Trace Crafts. We're here for questions about orders, custom requests, and everything in between.")

@section('content')

{{-- ============================================================ --}}
{{-- PAGE HEADER --}}
{{-- ============================================================ --}}
<div class="border-b border-walnut/20 py-10 md:py-14">
    <div class="page-container">
        <p class="section-label mb-4">Get in Touch</p>
        <h1 class="font-heading text-4xl md:text-5xl font-light text-charcoal">Contact Us</h1>
    </div>
</div>

{{-- ============================================================ --}}
{{-- TWO-COLUMN LAYOUT --}}
{{-- ============================================================ --}}
<div class="page-container py-14 md:py-20">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-14 lg:gap-20">

        {{-- ====================================================== --}}
        {{-- LEFT: CONTACT FORM --}}
        {{-- ====================================================== --}}
        <div class="lg:col-span-2">
            <p class="font-body text-sm text-walnut leading-relaxed mb-8">
                Whether you have a question about an order, want to discuss a custom piece, or just want to say hello — we'd love to hear from you.
            </p>

            @if(session('success'))
                <div class="border border-forest-green/30 bg-forest-green/5 px-6 py-5 mb-8">
                    <p class="font-body text-sm text-forest-green font-600">{{ session('success') }}</p>
                </div>
            @endif

            <form method="POST" action="{{ route('contact.submit') }}" class="space-y-6">
                @csrf
                @include('components.honeypot')

                {{-- Name + Email --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label for="name" class="section-label block mb-2">Your Name</label>
                        <input type="text"
                               id="name"
                               name="name"
                               value="{{ old('name', auth()->user()?->name) }}"
                               required
                               class="form-field {{ $errors->has('name') ? 'border-error' : '' }}">
                        @error('name')
                            <p class="font-body text-xs text-error mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="email" class="section-label block mb-2">Email Address</label>
                        <input type="email"
                               id="email"
                               name="email"
                               value="{{ old('email', auth()->user()?->email) }}"
                               required
                               class="form-field {{ $errors->has('email') ? 'border-error' : '' }}">
                        @error('email')
                            <p class="font-body text-xs text-error mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Subject --}}
                <div>
                    <label for="subject" class="section-label block mb-2">Subject</label>
                    <select id="subject"
                            name="subject"
                            required
                            class="form-field {{ $errors->has('subject') ? 'border-error' : '' }}">
                        <option value="">— Select a topic —</option>
                        <option value="General Inquiry" {{ old('subject') === 'General Inquiry' ? 'selected' : '' }}>General Inquiry</option>
                        <option value="Order Question" {{ old('subject') === 'Order Question' ? 'selected' : '' }}>Order Question</option>
                        <option value="Custom Order" {{ old('subject') === 'Custom Order' ? 'selected' : '' }}>Custom Order</option>
                        <option value="Return/Exchange" {{ old('subject') === 'Return/Exchange' ? 'selected' : '' }}>Return / Exchange</option>
                        <option value="Other" {{ old('subject') === 'Other' ? 'selected' : '' }}>Other</option>
                    </select>
                    @error('subject')
                        <p class="font-body text-xs text-error mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Message --}}
                <div>
                    <label for="message" class="section-label block mb-2">Message</label>
                    <textarea id="message"
                              name="message"
                              rows="7"
                              required
                              placeholder="Tell us how we can help..."
                              class="form-field resize-none {{ $errors->has('message') ? 'border-error' : '' }}">{{ old('message') }}</textarea>
                    @error('message')
                        <p class="font-body text-xs text-error mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit" class="btn-primary px-10 py-4">Send Message</button>
            </form>
        </div>

        {{-- ====================================================== --}}
        {{-- RIGHT: CONTACT INFO --}}
        {{-- ====================================================== --}}
        <div class="lg:col-span-1 space-y-10">

            {{-- Email --}}
            <div>
                <p class="section-label mb-3">Email</p>
                <a href="mailto:hello@timbertracecrafts.com"
                   class="font-heading text-xl font-light text-charcoal hover:text-forest-green transition-colors break-all">
                    hello@timbertracecrafts.com
                </a>
            </div>

            {{-- Response time --}}
            <div>
                <p class="section-label mb-3">Response Time</p>
                <p class="font-body text-sm text-charcoal/80 leading-relaxed">
                    We aim to respond to all inquiries within 1–2 business days. For urgent order questions, please include your order number in the subject line.
                </p>
            </div>

            {{-- Business hours --}}
            <div>
                <p class="section-label mb-3">Studio Hours</p>
                <div class="space-y-1">
                    <div class="flex justify-between font-body text-sm">
                        <span class="text-walnut">Monday – Friday</span>
                        <span class="text-charcoal">9 AM – 5 PM CT</span>
                    </div>
                    <div class="flex justify-between font-body text-sm">
                        <span class="text-walnut">Saturday</span>
                        <span class="text-charcoal">10 AM – 3 PM CT</span>
                    </div>
                    <div class="flex justify-between font-body text-sm">
                        <span class="text-walnut">Sunday</span>
                        <span class="text-charcoal">Closed</span>
                    </div>
                </div>
            </div>

            {{-- Social links --}}
            <div>
                <p class="section-label mb-4">Follow Along</p>
                <div class="flex items-center gap-5">
                    {{-- Instagram --}}
                    <a href="https://instagram.com/timbertracecrafts"
                       target="_blank" rel="noopener"
                       aria-label="Follow us on Instagram"
                       class="text-walnut hover:text-charcoal transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6">
                            <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                        </svg>
                    </a>

                    {{-- Facebook --}}
                    <a href="https://facebook.com/timbertracecrafts"
                       target="_blank" rel="noopener"
                       aria-label="Follow us on Facebook"
                       class="text-walnut hover:text-charcoal transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6">
                            <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                        </svg>
                    </a>

                    {{-- Pinterest --}}
                    <a href="https://pinterest.com/timbertracecrafts"
                       target="_blank" rel="noopener"
                       aria-label="Follow us on Pinterest"
                       class="text-walnut hover:text-charcoal transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6">
                            <path d="M12 0c-6.627 0-12 5.372-12 12 0 5.084 3.163 9.426 7.627 11.174-.105-.949-.2-2.405.042-3.441.218-.937 1.407-5.965 1.407-5.965s-.359-.719-.359-1.782c0-1.668.967-2.914 2.171-2.914 1.023 0 1.518.769 1.518 1.69 0 1.029-.655 2.568-.994 3.995-.283 1.194.599 2.169 1.777 2.169 2.133 0 3.772-2.249 3.772-5.495 0-2.873-2.064-4.882-5.012-4.882-3.414 0-5.418 2.561-5.418 5.207 0 1.031.397 2.138.893 2.738a.36.36 0 0 1 .083.345l-.333 1.36c-.053.22-.174.267-.402.161-1.499-.698-2.436-2.889-2.436-4.649 0-3.785 2.75-7.262 7.929-7.262 4.163 0 7.398 2.967 7.398 6.931 0 4.136-2.607 7.464-6.227 7.464-1.216 0-2.359-.632-2.75-1.378l-.748 2.853c-.271 1.043-1.002 2.35-1.492 3.146C9.57 23.812 10.763 24 12 24c6.627 0 12-5.373 12-12 0-6.628-5.373-12-12-12z"/>
                        </svg>
                    </a>
                </div>
            </div>

        </div>
    </div>
</div>

@endsection
