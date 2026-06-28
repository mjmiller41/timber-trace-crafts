import Alpine from "alpinejs";
// tui-image-editor is heavy (~700 kB) and only needed when the media editor
// opens, so it is dynamically imported in initEditor() to keep it out of the
// main admin bundle.

// Sidebar state (collapse/expand)
Alpine.data("sidebar", () => ({
    collapsed: localStorage.getItem("sidebarCollapsed") === "true",
    toggle() {
        this.collapsed = !this.collapsed;
        localStorage.setItem("sidebarCollapsed", this.collapsed);
    },
}));

// Confirm delete dialog
Alpine.data("confirmDelete", () => ({
    message: "Are you sure? This action cannot be undone.",
    show: false,
    pendingForm: null,
    open(form, message) {
        this.pendingForm = form;
        this.message = message || "Are you sure? This action cannot be undone.";
        this.show = true;
    },
    confirm() {
        this.pendingForm?.submit();
        this.show = false;
    },
    cancel() {
        this.show = false;
        this.pendingForm = null;
    },
}));

// Media library picker for admin forms
Alpine.data("mediaPicker", (selected = []) => ({
    selected,
    toggle(id, url) {
        const idx = this.selected.findIndex((m) => m.id === id);
        if (idx > -1) this.selected.splice(idx, 1);
        else this.selected.push({ id, url });
    },
    isSelected(id) {
        return this.selected.some((m) => m.id === id);
    },
    remove(id) {
        this.selected = this.selected.filter((m) => m.id !== id);
    },
}));

// Image reorder for product media (drag hint only — actual DnD is a server round-trip)
Alpine.data("mediaOrderer", (items = []) => ({
    items,
    moveUp(index) {
        if (index > 0) {
            [this.items[index - 1], this.items[index]] = [
                this.items[index],
                this.items[index - 1],
            ];
        }
    },
    moveDown(index) {
        if (index < this.items.length - 1) {
            [this.items[index], this.items[index + 1]] = [
                this.items[index + 1],
                this.items[index],
            ];
        }
    },
}));

// Product variation manager — supports up to 3 named variation types, each with multiple options
Alpine.data("variationManager", (initialTypes = []) => {
    let _nextKey = 0;

    function makeOption(data = {}) {
        return {
            _key: _nextKey++,
            id: data.id ?? null,
            label: data.label ?? "",
            sku: data.sku ?? "",
            price: data.price ?? null,
            is_enabled: data.is_enabled ?? true,
            material_code: data.material_code ?? "",
            stock_qty: data.stock_qty ?? 0,
            low_stock_threshold: data.low_stock_threshold ?? 5,
            sort_order: data.sort_order ?? 0,
        };
    }

    function makeType(data = {}) {
        return {
            _key: _nextKey++,
            id: data.id ?? null,
            name: data.name ?? "",
            options: (data.options ?? [{}]).map((o) => makeOption(o)),
        };
    }

    return {
        variationTypes: initialTypes.map((t) => makeType(t)),

        addType() {
            if (this.variationTypes.length >= 3) return;
            this.variationTypes.push(makeType());
        },

        removeType(typeIndex) {
            this.variationTypes.splice(typeIndex, 1);
        },

        addOption(typeIndex) {
            this.variationTypes[typeIndex].options.push(makeOption());
        },

        removeOption(typeIndex, optionIndex) {
            this.variationTypes[typeIndex].options.splice(optionIndex, 1);
        },
    };
});

// Etsy new-order badge — polls every 60s, shows sidebar badge + toast
Alpine.data("orderNotifier", () => ({
    count: 0,
    toast: false,
    toastTimer: null,
    init() {
        this.poll();
        setInterval(() => this.poll(), 60000);
    },
    async poll() {
        try {
            const res = await fetch("/admin/etsy/orders/badge", {
                headers: { "X-Requested-With": "XMLHttpRequest" },
            });
            if (!res.ok) return;
            const data = await res.json();
            const prev = this.count;
            this.count = data.count ?? 0;
            if (this.count > 0 && this.count > prev) this.showToast();
        } catch {}
    },
    showToast() {
        this.toast = true;
        clearTimeout(this.toastTimer);
        this.toastTimer = setTimeout(() => {
            this.toast = false;
        }, 6000);
    },
    dismiss() {
        this.toast = false;
        clearTimeout(this.toastTimer);
    },
}));

// Media image editor (TUI Image Editor / ZenImages)
Alpine.data("mediaEditor", () => ({
    open: false,
    mediaId: null,
    mediaUrl: null,
    mediaName: null,
    editor: null,
    saving: false,
    error: "",

    init() {
        window.addEventListener("open-media-editor", (e) => {
            this.mediaId = e.detail.id;
            this.mediaUrl = e.detail.url;
            this.mediaName = e.detail.name;
            this.error = "";
            this.open = true;
            this.$nextTick(() =>
                requestAnimationFrame(() => this.initEditor()),
            );
        });
    },

    async initEditor() {
        if (this.editor) {
            this.editor.destroy();
            this.editor = null;
        }
        const [{ default: ImageEditor }] = await Promise.all([
            import("tui-image-editor"),
            import("tui-image-editor/dist/tui-image-editor.css"),
        ]);
        this.editor = new ImageEditor(this.$refs.editorContainer, {
            includeUI: {
                loadImage: { path: this.mediaUrl, name: this.mediaName },
                menu: ["crop", "flip", "rotate", "draw", "shape", "text", "filter"],
                initMenu: "",
                menuBarPosition: "left",
            },
            usageStatistics: false,
        });
    },

    close() {
        this.open = false;
        if (this.editor) {
            this.editor.destroy();
            this.editor = null;
        }
    },

    async save(mode) {
        if (!this.editor || this.saving) return;
        this.saving = true;
        this.error = "";

        try {
            const dataURL = this.editor.toDataURL();
            const blob = await fetch(dataURL).then((r) => r.blob());
            const ext = blob.type.split("/")[1] ?? "jpg";
            const formData = new FormData();
            formData.append("_method", "PATCH");
            formData.append(
                "_token",
                document.querySelector('meta[name="csrf-token"]').content,
            );
            formData.append(
                "image",
                blob,
                this.mediaName.replace(/\.[^.]+$/, "") + "." + ext,
            );
            formData.append("mode", mode);

            const res = await fetch(`/admin/media/${this.mediaId}`, {
                method: "POST",
                headers: { Accept: "application/json" },
                body: formData,
            });

            if (res.ok) {
                this.close();
                window.location.reload();
            } else {
                const data = await res.json().catch(() => ({}));
                this.error =
                    data.message ??
                    data.error ??
                    `Save failed (HTTP ${res.status})`;
            }
        } catch (e) {
            this.error = "Network error — please try again.";
        }

        this.saving = false;
    },
}));

window.Alpine = Alpine;
Alpine.start();
