@extends('layouts.admin')

@section('page-title', 'Media Library')

@section('content')

{{-- Upload Form --}}
<div class="admin-card" style="margin-bottom: 1.5rem;">
    <div class="admin-card-header">
        <span class="admin-card-title">Upload Media</span>
    </div>
    <form method="POST" action="{{ route('admin.media.store') }}" enctype="multipart/form-data"
          style="display: flex; gap: 0.875rem; align-items: flex-end; flex-wrap: wrap;">
        @csrf

        <div style="flex: 2; min-width: 240px;">
            <label class="admin-label" for="files">Files</label>
            <input
                type="file"
                id="files"
                name="files[]"
                class="admin-input"
                multiple
                accept="image/*,video/mp4"
                style="padding: 0.375rem 0.75rem; cursor: pointer;"
                required
            >
            <p class="admin-hint">Images (JPG, PNG, WebP, GIF) and MP4 videos. Multiple allowed.</p>
        </div>

        <div style="flex: 1; min-width: 200px;">
            <label class="admin-label" for="alt_text">Alt Text</label>
            <input
                type="text"
                id="alt_text"
                name="alt_text"
                class="admin-input"
                placeholder="Describe the image for accessibility…"
            >
            <p class="admin-hint">Applied to all uploaded files.</p>
        </div>

        <div style="padding-bottom: 1.375rem;">
            <button type="submit" class="admin-btn" style="background: #2C4C3B; color: #fff;">
                &#x2B06; Upload
            </button>
        </div>

    </form>
</div>

{{-- Media Stats --}}
<div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
    <p style="font-size: 0.875rem; color: #6b7280;">
        {{ $mediaItems->total() }} {{ Str::plural('file', $mediaItems->total()) }}
        &nbsp;&middot;&nbsp; Page {{ $mediaItems->currentPage() }} of {{ $mediaItems->lastPage() }}
    </p>
</div>

{{-- Media Grid --}}
@if($mediaItems->isEmpty())
<div class="admin-card" style="text-align: center; padding: 4rem 2rem; color: #9ca3af;">
    <div style="font-size: 3rem; margin-bottom: 1rem;">&#x1F4F7;</div>
    <p style="font-size: 1rem; margin-bottom: 0.5rem;">No media uploaded yet.</p>
    <p style="font-size: 0.875rem;">Use the upload form above to add images and videos.</p>
</div>
@else

<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 0.875rem; margin-bottom: 1.5rem;">
    @foreach($mediaItems as $media)
    <div
        class="admin-card"
        style="padding: 0; overflow: hidden; position: relative; cursor: pointer;"
        x-data="{ showDetails: false }"
        @click="showDetails = !showDetails"
        title="{{ $media->original_name }}"
    >
        {{-- Thumbnail --}}
        <div style="position: relative; aspect-ratio: 1; background: #f3f4f6; overflow: hidden;">
            @if(Str::startsWith($media->mime_type, 'image/'))
                <img
                    src="{{ asset('storage/' . $media->path) }}"
                    alt="{{ $media->alt_text ?? $media->original_name }}"
                    style="width: 100%; height: 100%; object-fit: cover; display: block;"
                    loading="lazy"
                >
            @else
                {{-- Video placeholder --}}
                <div style="width: 100%; height: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 0.375rem; color: #9ca3af;">
                    <div style="font-size: 2.5rem;">&#x1F3AC;</div>
                    <div style="font-size: 0.6875rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.08em;">Video</div>
                </div>
            @endif
        </div>

        {{-- File Info --}}
        <div style="padding: 0.5rem 0.625rem; border-top: 1px solid #f3f4f6;">
            <div style="font-size: 0.6875rem; font-weight: 500; color: #333; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="{{ $media->original_name }}">
                {{ $media->original_name }}
            </div>
            <div style="font-size: 0.625rem; color: #9ca3af; margin-top: 0.125rem;">
                {{ number_format($media->size_bytes / 1024, 0) }} KB
            </div>
        </div>

        {{-- Details Overlay --}}
        <div
            x-show="showDetails"
            @click.stop
            style="position: absolute; inset: 0; background: rgba(30, 53, 41, 0.93); display: flex; flex-direction: column; justify-content: space-between; padding: 0.875rem 0.75rem;"
        >
            <div>
                <p style="font-size: 0.6875rem; color: rgba(255,255,255,0.8); word-break: break-all; margin-bottom: 0.5rem;">{{ $media->original_name }}</p>
                @if($media->alt_text)
                    <p style="font-size: 0.6875rem; color: rgba(255,255,255,0.6); font-style: italic; margin-bottom: 0.5rem;">{{ $media->alt_text }}</p>
                @endif
                <div style="background: rgba(255,255,255,0.15); border-radius: 0.125rem; padding: 0.25rem 0.375rem; cursor: text;">
                    <p style="font-size: 0.625rem; color: rgba(255,255,255,0.9); word-break: break-all; font-family: monospace;">
                        {{ asset('storage/' . $media->path) }}
                    </p>
                </div>
            </div>
            <div style="display: flex; gap: 0.375rem; justify-content: space-between; align-items: center;">
                <button
                    type="button"
                    onclick="navigator.clipboard.writeText('{{ asset('storage/' . $media->path) }}')"
                    style="font-size: 0.625rem; background: rgba(255,255,255,0.15); color: #fff; border: none; padding: 0.25rem 0.5rem; border-radius: 0.125rem; cursor: pointer; font-family: 'Montserrat', sans-serif;"
                    @click.stop
                >
                    Copy URL
                </button>
                <form method="POST" action="{{ route('admin.media.destroy', $media) }}"
                      @submit.prevent="$dispatch('confirm-delete', {form: $el})"
                      @click.stop>
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="admin-btn admin-btn-danger" style="font-size: 0.625rem; padding: 0.25rem 0.5rem;">Delete</button>
                </form>
            </div>
        </div>

    </div>
    @endforeach
</div>

{{-- Pagination --}}
@if($mediaItems->hasPages())
<div class="admin-card" style="padding: 1rem 1.5rem;">
    {{ $mediaItems->links() }}
</div>
@endif

@endif

@endsection
