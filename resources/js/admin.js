import Alpine from 'alpinejs'

// Sidebar state (collapse/expand)
Alpine.data('sidebar', () => ({
    collapsed: localStorage.getItem('sidebarCollapsed') === 'true',
    toggle() {
        this.collapsed = !this.collapsed
        localStorage.setItem('sidebarCollapsed', this.collapsed)
    }
}))

// Confirm delete dialog
Alpine.data('confirmDelete', (message = 'Are you sure?') => ({
    message,
    show: false,
    pendingForm: null,
    open(form) { this.pendingForm = form; this.show = true },
    confirm() { this.pendingForm?.submit(); this.show = false },
    cancel() { this.show = false; this.pendingForm = null }
}))

// Media library picker for admin forms
Alpine.data('mediaPicker', (selected = []) => ({
    selected,
    toggle(id, url) {
        const idx = this.selected.findIndex(m => m.id === id)
        if (idx > -1) this.selected.splice(idx, 1)
        else this.selected.push({ id, url })
    },
    isSelected(id) { return this.selected.some(m => m.id === id) },
    remove(id) { this.selected = this.selected.filter(m => m.id !== id) }
}))

// Image reorder for product media (drag hint only — actual DnD is a server round-trip)
Alpine.data('mediaOrderer', (items = []) => ({
    items,
    moveUp(index) { if (index > 0) { [this.items[index-1], this.items[index]] = [this.items[index], this.items[index-1]] } },
    moveDown(index) { if (index < this.items.length - 1) { [this.items[index], this.items[index+1]] = [this.items[index+1], this.items[index]] } }
}))

window.Alpine = Alpine
Alpine.start()
