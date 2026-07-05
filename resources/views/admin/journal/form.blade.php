{{--
    Shared journal post form partial.
    Variables: $post (optional, for edit), $tags (Collection of Tag models)
    Usage: @include('admin.journal.form')
--}}

<div x-data="{
    seoOpen: false,
    autoSlug(title) {
        return title.toLowerCase()
            .replace(/[^a-z0-9\s-]/g, '')
            .trim()
            .replace(/[\s]+/g, '-');
    }
}">

{{-- Title --}}
<div style="margin-bottom: 1.25rem;">
    <label class="admin-label" for="title">Title <span style="color: #ba1a1a;">*</span></label>
    <input
        type="text"
        id="title"
        name="title"
        class="admin-input"
        value="{{ old('title', $post->title ?? '') }}"
        placeholder="Post title&hellip;"
        @input="
            let slugField = $el.closest('form').querySelector('#slug');
            if (!slugField.dataset.manually_edited) {
                slugField.value = autoSlug($el.value);
            }
        "
        required
    >
    @error('title') <p class="admin-error-text">{{ $message }}</p> @enderror
</div>

{{-- Slug --}}
<div style="margin-bottom: 1.25rem;">
    <label class="admin-label" for="slug">Slug <span style="color: #ba1a1a;">*</span></label>
    <input
        type="text"
        id="slug"
        name="slug"
        class="admin-input"
        value="{{ old('slug', $post->slug ?? '') }}"
        placeholder="post-url-slug"
        @input="$el.dataset.manually_edited = true;"
        required
    >
    <p class="admin-hint">URL: /journal/<strong x-text="$el.closest('form').querySelector('#slug')?.value || 'slug'"></strong></p>
    @error('slug') <p class="admin-error-text">{{ $message }}</p> @enderror
</div>

{{-- Excerpt --}}
<div style="margin-bottom: 1.25rem;" x-data="{ charCount: {{ strlen(old('excerpt', $post->excerpt ?? '')) }} }">
    <label class="admin-label" for="excerpt">
        Excerpt
        <span style="font-weight: 400; color: #6b7280; margin-left: 0.25rem;">(<span x-text="charCount"></span>/300 chars)</span>
    </label>
    <textarea
        id="excerpt"
        name="excerpt"
        class="admin-input"
        rows="3"
        maxlength="300"
        placeholder="Brief summary of the post&hellip;"
        @input="charCount = $el.value.length"
    >{{ old('excerpt', $post->excerpt ?? '') }}</textarea>
    @error('excerpt') <p class="admin-error-text">{{ $message }}</p> @enderror
</div>

{{-- Body --}}
<div style="margin-bottom: 1.25rem;">
    <label class="admin-label" for="body">Body <span style="color: #ba1a1a;">*</span></label>
    <textarea
        id="body"
        name="body"
        rows="20"
        required
    >{{ old('body', $post->body ?? '') }}</textarea>
    @error('body') <p class="admin-error-text">{{ $message }}</p> @enderror
</div>
@include('components.admin.zencomposer', ['targetId' => 'body'])

{{-- Featured Image (shared media picker: upload OR choose from library) --}}
<div style="margin-bottom: 1.25rem;"
    x-data="{
        preview: '{{ isset($post) && $post->featuredImage ? $post->featuredImage->url() : '' }}',
        mediaId: '{{ old('featured_image_id', isset($post) ? $post->featured_image_id : '') }}',
    }"
    @media-picker:picked:journal-featured.window="
        const item = $event.detail.items[0];
        if (item) { mediaId = item.id; preview = item.url; }
    "
>
    <label class="admin-label">Featured Image</label>

    {{-- Current selection preview --}}
    <template x-if="preview">
        <div style="margin-bottom: 0.75rem;">
            <img :src="preview" alt="Featured image" style="max-height: 180px; max-width: 100%; object-fit: cover; border: 1px solid #e5e7eb; display: block;">
        </div>
    </template>

    {{-- Submitted value (id of the chosen / uploaded media) --}}
    <input type="hidden" name="featured_image_id" :value="mediaId">

    <div style="display: flex; gap: 0.5rem;">
        <button type="button" class="admin-btn admin-btn-outline" style="font-size: 0.8125rem;"
            @click="$dispatch('media-picker:open:journal-featured')">
            <span x-text="preview ? 'Change image' : 'Choose image'"></span>
        </button>
        <button type="button" x-show="preview" x-cloak class="admin-btn admin-btn-outline" style="font-size: 0.8125rem;"
            @click="mediaId = ''; preview = ''">
            Remove
        </button>
    </div>
    @error('featured_image_id') <p class="admin-error-text">{{ $message }}</p> @enderror
</div>

<x-admin.media-picker channel="journal-featured" />

{{-- Status + Published At --}}
<div style="display: grid; grid-template-columns: 200px 1fr; gap: 1rem; margin-bottom: 1.25rem; max-width: 480px;">
    <div>
        <label class="admin-label" for="status">Status</label>
        <select id="status" name="status" class="admin-input">
            <option value="draft" {{ old('status', $post->status ?? 'draft') === 'draft' ? 'selected' : '' }}>Draft</option>
            <option value="published" {{ old('status', $post->status ?? '') === 'published' ? 'selected' : '' }}>Published</option>
        </select>
        @error('status') <p class="admin-error-text">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="admin-label" for="published_at">Published At</label>
        <input
            type="datetime-local"
            id="published_at"
            name="published_at"
            class="admin-input"
            value="{{ old('published_at', isset($post) && $post->published_at ? $post->published_at->format('Y-m-d\TH:i') : '') }}"
        >
        <p class="admin-hint">Leave blank to use the current time when publishing. Set status to Published with a future date/time to schedule the post — it stays hidden until then.</p>
        @error('published_at') <p class="admin-error-text">{{ $message }}</p> @enderror
    </div>
</div>

{{-- Tags --}}
@if($tags->isNotEmpty())
<div style="margin-bottom: 1.5rem;">
    <label class="admin-label">Tags</label>
    <div style="display: flex; flex-wrap: wrap; gap: 0.5rem; margin-top: 0.375rem;">
        @foreach($tags as $tag)
        <label style="display: flex; align-items: center; gap: 0.375rem; cursor: pointer; font-size: 0.8125rem; padding: 0.25rem 0.625rem; border: 1px solid #d1d5db; border-radius: 9999px; transition: border-color 0.15s;">
            <input
                type="checkbox"
                name="tags[]"
                value="{{ $tag->id }}"
                {{ in_array($tag->id, old('tags', isset($post) ? $post->tags->pluck('id')->toArray() : [])) ? 'checked' : '' }}
                style="cursor: pointer;"
            >
            {{ $tag->name }}
        </label>
        @endforeach
    </div>
    @error('tags') <p class="admin-error-text">{{ $message }}</p> @enderror
</div>
@endif

{{-- SEO (collapsible) --}}
<div style="margin-bottom: 1.25rem; border: 1px solid #e5e7eb; border-radius: 0.25rem; overflow: hidden;">
    <button
        type="button"
        @click="seoOpen = !seoOpen"
        style="width: 100%; display: flex; align-items: center; justify-content: space-between; padding: 0.875rem 1rem; background: #f9fafb; border: none; cursor: pointer; font-size: 0.875rem; font-weight: 600; color: #333; text-align: left;"
    >
        <span>SEO / Meta</span>
        <span x-text="seoOpen ? '▲' : '▼'" style="font-size: 0.75rem; color: #6b7280;"></span>
    </button>

    <div x-show="seoOpen" style="display: none; padding: 1rem; border-top: 1px solid #e5e7eb;">
        <div style="margin-bottom: 1rem;">
            <label class="admin-label" for="meta_title">Meta Title</label>
            <input
                type="text"
                id="meta_title"
                name="meta_title"
                class="admin-input"
                value="{{ old('meta_title', $post->meta_title ?? '') }}"
                placeholder="Defaults to post title if blank"
                maxlength="255"
            >
            @error('meta_title') <p class="admin-error-text">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="admin-label" for="meta_description">Meta Description</label>
            <textarea
                id="meta_description"
                name="meta_description"
                class="admin-input"
                rows="3"
                placeholder="155–160 characters recommended"
                maxlength="500"
            >{{ old('meta_description', $post->meta_description ?? '') }}</textarea>
            @error('meta_description') <p class="admin-error-text">{{ $message }}</p> @enderror
        </div>
    </div>
</div>

</div>{{-- end x-data --}}
