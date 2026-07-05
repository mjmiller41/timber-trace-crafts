import Alpine from 'alpinejs'
import QRCode from 'qrcode'

// Mobile nav component
Alpine.data('mobileNav', () => ({ open: false }))

// Image gallery component for product pages
Alpine.data('gallery', (allMedia = []) => ({
    items: allMedia,
    currentIndex: 0,
    get currentItem() { return this.items[this.currentIndex] ?? null },
    setIndex(index) { this.currentIndex = index },
    prev() { this.currentIndex = this.currentIndex > 0 ? this.currentIndex - 1 : this.items.length - 1 },
    next() { this.currentIndex = this.currentIndex < this.items.length - 1 ? this.currentIndex + 1 : 0 }
}))

// Variant selector for product pages
Alpine.data('variantSelector', (variants = [], pricing = {}) => ({
    variants,
    pricing,
    selectedId: variants.find(v => v.stock_qty > 0)?.id ?? variants[0]?.id ?? null,
    get selected() { return this.variants.find(v => v.id === this.selectedId) ?? null },
    get selectedLabel() { return this.selected?.label ?? '' },
    get inStock() { return (this.selected?.stock_qty ?? 0) > 0 },
    get lowStock() { return this.inStock && (this.selected?.stock_qty ?? 0) <= (this.selected?.low_stock_threshold ?? 5) },
    // A variant price is an absolute override; otherwise fall back to the
    // product's current (sale-aware) price.
    get displayPrice() {
        const override = this.selected?.price
        return override != null ? override : this.pricing.currentPrice
    },
    // Only the product-level sale shows the struck-through compare-at price;
    // an explicit variant override has no sale framing.
    get onSale() {
        if (this.selected?.price != null) { return false }
        return this.pricing.salePrice != null && this.pricing.salePrice < this.pricing.price
    },
    get compareAtPrice() { return this.onSale ? this.pricing.price : null },
    formatPrice(value) { return value == null ? '' : '$' + Number(value).toFixed(2) },
    selectVariant(variant) { this.selectedId = variant.id }
}))

// Recently viewed products — persisted in localStorage, rendered client-side.
// `current` is the card data for the product being viewed (null off a product page).
Alpine.data('recentlyViewed', (current = null) => ({
    storageKey: 'ttc_recently_viewed',
    max: 12,
    items: [],
    init() {
        let stored = [];
        try {
            stored = JSON.parse(localStorage.getItem(this.storageKey)) || [];
        } catch (e) {
            stored = [];
        }
        if (!Array.isArray(stored)) { stored = []; }

        if (current && current.slug) {
            // Move the current product to the front, deduped, capped.
            stored = stored.filter(p => p && p.slug !== current.slug);
            stored.unshift(current);
            stored = stored.slice(0, this.max);
            try {
                localStorage.setItem(this.storageKey, JSON.stringify(stored));
            } catch (e) { /* private mode / quota — non-fatal */ }
        }

        // Never show the product currently being viewed in its own strip.
        this.items = stored.filter(p => p && p.slug && (!current || p.slug !== current.slug));
    },
    formatPrice(value) { return value == null ? '' : '$' + Number(value).toFixed(2); },
    onSale(p) { return p.sale_price != null && p.sale_price < p.price; },
}))

// Social share buttons. Facebook/Pinterest are plain links; Instagram has no
// web share intent, so its button copies the product URL to the clipboard.
Alpine.data('shareButtons', (url = '') => ({
    url,
    copied: false,
    copyTimer: null,
    async copyLink() {
        let ok = false;
        try {
            await navigator.clipboard.writeText(this.url);
            ok = true;
        } catch (e) {
            // Fallback for older browsers / insecure contexts.
            try {
                const el = document.createElement('textarea');
                el.value = this.url;
                el.setAttribute('readonly', '');
                el.style.position = 'absolute';
                el.style.left = '-9999px';
                document.body.appendChild(el);
                el.select();
                ok = document.execCommand('copy');
                document.body.removeChild(el);
            } catch (e2) { ok = false; }
        }
        if (ok) {
            this.copied = true;
            clearTimeout(this.copyTimer);
            this.copyTimer = setTimeout(() => { this.copied = false; }, 2500);
        }
    },
}))

// Cart quantity control
Alpine.data('qtyControl', (initial = 1, max = 99) => ({
    qty: initial,
    max,
    increment() { if (this.qty < this.max) this.qty++ },
    decrement() { if (this.qty > 1) this.qty-- }
}))

// Flash message auto-dismiss
Alpine.data('flash', () => ({
    show: true,
    init() { setTimeout(() => this.show = false, 4000) }
}))

// Coupon code toggle
Alpine.data('couponForm', () => ({
    open: false
}))

// TOTP enrollment QR code — renders the otpauth:// URI into a canvas element.
Alpine.data('totpQr', (uri) => ({
    async init() {
        if (!uri) { return }
        try {
            await QRCode.toCanvas(this.$refs.qr, uri, { width: 200, margin: 2 })
        } catch (e) {
            // QR render failure is non-fatal; the text key + URI remain visible.
        }
    }
}))

window.Alpine = Alpine
Alpine.start()
