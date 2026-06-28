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

// Shared media-library picker modal (used by <x-admin.media-picker>).
// Opens on window event `media-picker:open:{channel}`, and on confirm emits
// `media-picker:picked:{channel}` with { detail: { items: [...] } }.
Alpine.data("mediaPickerModal", (config = {}) => ({
    channel: config.channel,
    multiple: config.multiple ?? false,
    libraryUrl: config.libraryUrl,
    uploadUrl: config.uploadUrl,
    csrf: config.csrf,

    open: false,
    tab: "library",
    items: [],
    selected: [],
    search: "",
    page: 1,
    lastPage: 1,
    loading: false,
    searchTimer: null,

    queue: [],
    uploading: false,
    dragging: false,
    dragCount: 0,

    init() {
        window.addEventListener(`media-picker:open:${this.channel}`, () => {
            this.openModal();
        });
    },

    openModal() {
        this.open = true;
        this.selected = [];
        this.tab = "library";
        if (this.items.length === 0) this.fetchLibrary(true);
    },

    close() {
        this.open = false;
        this.queue = [];
    },

    debouncedSearch() {
        clearTimeout(this.searchTimer);
        this.searchTimer = setTimeout(() => this.fetchLibrary(true), 300);
    },

    async fetchLibrary(reset = false) {
        if (this.loading) return;
        this.loading = true;
        if (reset) {
            this.page = 1;
            this.items = [];
        }
        const url = new URL(this.libraryUrl, window.location.origin);
        if (this.search) url.searchParams.set("search", this.search);
        url.searchParams.set("page", this.page);

        try {
            const res = await fetch(url, {
                headers: { Accept: "application/json" },
            });
            if (res.ok) {
                const data = await res.json();
                this.items.push(...data.data);
                this.lastPage = data.last_page;
            }
        } catch {}
        this.loading = false;
    },

    loadMore() {
        if (this.page >= this.lastPage || this.loading) return;
        this.page++;
        this.fetchLibrary(false);
    },

    toggle(item) {
        const idx = this.selected.findIndex((m) => m.id === item.id);
        if (idx > -1) {
            this.selected.splice(idx, 1);
        } else if (this.multiple) {
            this.selected.push(item);
        } else {
            this.selected = [item];
        }
    },

    isSelected(id) {
        return this.selected.some((m) => m.id === id);
    },

    confirm() {
        window.dispatchEvent(
            new CustomEvent(`media-picker:picked:${this.channel}`, {
                detail: { items: this.selected },
            }),
        );
        this.close();
    },

    // --- Upload tab ---
    filesToQueue(files) {
        const existing = new Set(this.queue.map((i) => i.name));
        this.queue.push(
            ...Array.from(files)
                .filter((f) => !existing.has(f.name))
                .map((f) => ({ file: f, name: f.name, status: "pending", error: "" })),
        );
    },
    selectFiles(event) {
        this.filesToQueue(event.target.files);
    },
    dropFiles(event) {
        this.filesToQueue(event.dataTransfer.files);
    },
    async startUpload() {
        if (this.uploading || this.queue.length === 0) return;
        this.uploading = true;
        const uploaded = [];

        for (const item of this.queue) {
            if (item.status === "done") continue;
            item.status = "loading";
            const body = new FormData();
            body.append("file", item.file);
            body.append("_token", this.csrf);
            try {
                const res = await fetch(this.uploadUrl, {
                    method: "POST",
                    headers: { Accept: "application/json" },
                    body,
                });
                if (res.ok) {
                    const data = await res.json();
                    item.status = "done";
                    uploaded.push({
                        id: data.id,
                        url: data.url,
                        name: data.name,
                        alt: null,
                        is_image: true,
                    });
                } else {
                    const data = await res.json().catch(() => ({}));
                    item.status = "error";
                    item.error = data.message ?? data.error ?? `HTTP ${res.status}`;
                }
            } catch {
                item.status = "error";
                item.error = "Network error";
            }
        }

        this.uploading = false;
        // Surface freshly-uploaded files at the top of the library, pre-selected.
        this.items = [...uploaded, ...this.items];
        uploaded.forEach((u) => this.toggle(u));
        this.queue = this.queue.filter((i) => i.status === "error");
        if (uploaded.length > 0) this.tab = "library";
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
