@props([
    'channel' => 'default',
    'multiple' => false,
])

{{--
    Shared media-library picker modal.

    Open it from the host form:  $dispatch('media-picker:open:{{ $channel }}')  (or a window CustomEvent)
    Receive the selection:       window event `media-picker:picked:{{ $channel }}`
                                 with detail = { items: [{ id, url, name, alt, is_image }, ...] }

    Pass multiple=true to allow selecting more than one file.
--}}
@once
    @push('head')
        <style>
            .mp-tab {
                appearance: none;
                border: none;
                background: none;
                padding: 0.75rem 1.1rem;
                margin-bottom: -1px;
                font-family: inherit;
                font-size: 0.9375rem;
                font-weight: 600;
                color: #6b7280;
                cursor: pointer;
                border-bottom: 3px solid transparent;
                transition: color 0.15s, border-color 0.15s, background 0.15s;
            }
            .mp-tab:hover { color: #2C4C3B; background: #f9fafb; }
            .mp-tab.is-active {
                color: #2C4C3B;
                border-bottom-color: #2C4C3B;
                background: #f6f8f6;
            }
            /* display:flex lives in the class (not inline) because Alpine's
               x-show removes the inline display property when it shows the
               element, which would otherwise fall back to block. */
            .mp-overlay {
                position: fixed;
                inset: 0;
                z-index: 10000;
                background: rgba(17, 24, 39, 0.55);
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 1rem;
            }
            .mp-selected-badge {
                position: absolute;
                top: 0.25rem;
                right: 0.25rem;
                width: 1.25rem;
                height: 1.25rem;
                border-radius: 9999px;
                background: #2C4C3B;
                color: #fff;
                font-size: 0.75rem;
                display: flex;
                align-items: center;
                justify-content: center;
            }
        </style>
    @endpush
@endonce
<div
    x-data="mediaPickerModal({
        channel: @js($channel),
        multiple: @js((bool) $multiple),
        libraryUrl: @js(route('admin.media.index')),
        uploadUrl: @js(route('admin.media.store')),
        csrf: @js(csrf_token()),
    })"
>
{{-- Teleported to <body> so the fixed overlay centers against the viewport,
     not a transformed admin-layout ancestor. --}}
<template x-teleport="body">
    <div
        x-show="open"
        x-cloak
        @keydown.escape.window="open && close()"
        class="mp-overlay"
    >
    <div
        @click.outside="close()"
        style="background: #fff; width: min(920px, 94vw); max-height: 88vh; border-radius: 0.5rem; display: flex; flex-direction: column; overflow: hidden; box-shadow: 0 20px 60px rgba(0,0,0,0.3);"
    >
        {{-- Header + tabs --}}
        <div style="display: flex; align-items: stretch; justify-content: space-between; padding: 0 1.25rem; border-bottom: 1px solid #e5e7eb; flex-shrink: 0;">
            <div style="display: flex; gap: 0.25rem;" role="tablist">
                <button type="button" role="tab" @click="tab = 'library'" class="mp-tab" :class="{ 'is-active': tab === 'library' }">
                    &#x1F5BC;&#xFE0F; Library
                </button>
                <button type="button" role="tab" @click="tab = 'upload'" class="mp-tab" :class="{ 'is-active': tab === 'upload' }">
                    &#x2B06;&#xFE0F; Upload
                </button>
            </div>
            <button type="button" @click="close()" title="Close"
                style="align-self: center; background: none; border: none; font-size: 1.5rem; line-height: 1; color: #6b7280; cursor: pointer;">&times;</button>
        </div>

        {{-- Body --}}
        <div style="flex: 1; overflow-y: auto; padding: 1.25rem;" @scroll="onScroll($event)">

            {{-- Library tab --}}
            <div x-show="tab === 'library'">
                <input type="search" x-model="search" @input="debouncedSearch()"
                    placeholder="Search filename or alt text…" class="admin-input" style="margin-bottom: 1rem;">

                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 0.75rem;">
                    <template x-for="item in items" :key="item.id">
                        <button type="button" @click="toggle(item)"
                            :style="isSelected(item.id) ? 'outline:3px solid #2C4C3B;outline-offset:-3px' : 'outline:1px solid #e5e7eb;outline-offset:-1px'"
                            style="position: relative; padding: 0; background: #f3f4f6; border: none; border-radius: 0.25rem; overflow: hidden; cursor: pointer; aspect-ratio: 1;">
                            <template x-if="item.is_image">
                                <img :src="item.url" :alt="item.alt || item.name" loading="lazy"
                                    style="width: 100%; height: 100%; object-fit: cover; display: block;">
                            </template>
                            <template x-if="!item.is_image">
                                <span style="display: flex; align-items: center; justify-content: center; width: 100%; height: 100%; font-size: 1.75rem; color: #9ca3af;">&#x1F4C4;</span>
                            </template>
                            <span x-show="isSelected(item.id)" class="mp-selected-badge">&#x2713;</span>
                        </button>
                    </template>
                </div>

                <p x-show="!loading && items.length === 0" style="text-align: center; color: #9ca3af; padding: 2rem 0; font-size: 0.875rem;">
                    No media found.
                </p>
                {{-- Infinite scroll: more pages load automatically as you scroll. --}}
                <p x-show="loading" style="text-align: center; color: #6b7280; padding: 1rem 0; font-size: 0.8125rem;">Loading…</p>
            </div>

            {{-- Upload tab --}}
            <div x-show="tab === 'upload'" x-cloak>
                <div
                    @dragenter.prevent="dragCount++; dragging = true"
                    @dragleave.prevent="if (--dragCount === 0) dragging = false"
                    @dragover.prevent
                    @drop.prevent="dragCount = 0; dropFiles($event); dragging = false"
                    @click="$refs.pickerFileInput.click()"
                    :style="{ border: dragging ? '2px dashed #2C4C3B' : '2px dashed #9ca3af', background: dragging ? '#f0f5f2' : '#fafafa' }"
                    style="border-radius: 0.5rem; padding: 2.5rem 1.5rem; min-height: 9rem; display: flex; flex-direction: column; align-items: center; justify-content: center; cursor: pointer; text-align: center;"
                >
                    <div style="font-size: 2rem; margin-bottom: 0.5rem; pointer-events: none;">&#x1F4C2;</div>
                    <p style="font-size: 0.9375rem; color: #4b5563; margin: 0; pointer-events: none;"><strong>Drop files here</strong> or click to browse</p>
                    <p style="font-size: 0.75rem; color: #9ca3af; margin: 0.375rem 0 0; pointer-events: none;">JPG, PNG, WebP, GIF, PDF — up to 10 MB each</p>
                </div>
                <input type="file" x-ref="pickerFileInput" multiple
                    accept="image/jpeg,image/png,image/gif,image/webp,application/pdf"
                    style="display: none;" @change="selectFiles($event)">

                <template x-if="queue.length > 0">
                    <ul style="list-style: none; padding: 0; margin: 0.75rem 0 0; display: flex; flex-direction: column; gap: 0.375rem;">
                        <template x-for="item in queue" :key="item.name">
                            <li style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.8125rem;">
                                <span x-text="item.name" style="flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"></span>
                                <span x-show="item.status === 'error'" x-text="item.error" style="color: #dc2626; font-size: 0.75rem;"></span>
                            </li>
                        </template>
                    </ul>
                </template>

                <div style="margin-top: 0.75rem; text-align: right;">
                    <button type="button" class="admin-btn" style="background: #2C4C3B; color: #fff;"
                        :disabled="uploading || queue.length === 0" @click="startUpload()">
                        <span x-show="!uploading">&#x2B06; Upload <span x-show="queue.length > 0" x-text="'(' + queue.length + ')'"></span></span>
                        <span x-show="uploading">Uploading…</span>
                    </button>
                </div>
            </div>
        </div>

        {{-- Footer --}}
        <div style="display: flex; align-items: center; justify-content: space-between; padding: 0.875rem 1.25rem; border-top: 1px solid #e5e7eb; flex-shrink: 0;">
            <span style="font-size: 0.8125rem; color: #6b7280;" x-text="selected.length + (selected.length === 1 ? ' file selected' : ' files selected')"></span>
            <div style="display: flex; gap: 0.5rem;">
                <button type="button" @click="close()" class="admin-btn admin-btn-outline" style="font-size: 0.8125rem;">Cancel</button>
                <button type="button" @click="confirm()" :disabled="selected.length === 0"
                    class="admin-btn" style="background: #2C4C3B; color: #fff; font-size: 0.8125rem;">
                    Add selected
                </button>
            </div>
        </div>
    </div>
    </div>
</template>
</div>
