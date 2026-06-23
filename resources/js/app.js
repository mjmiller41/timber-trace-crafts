import Alpine from 'alpinejs'

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
Alpine.data('variantSelector', (variants = []) => ({
    variants,
    selectedId: variants.find(v => v.stock_qty > 0)?.id ?? variants[0]?.id ?? null,
    get selected() { return this.variants.find(v => v.id === this.selectedId) ?? null },
    get selectedLabel() { return this.selected?.label ?? '' },
    get inStock() { return (this.selected?.stock_qty ?? 0) > 0 },
    get lowStock() { return this.inStock && (this.selected?.stock_qty ?? 0) <= (this.selected?.low_stock_threshold ?? 5) },
    selectVariant(variant) { this.selectedId = variant.id }
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

window.Alpine = Alpine
Alpine.start()
