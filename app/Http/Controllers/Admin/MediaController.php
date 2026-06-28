<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Services\Media\MediaUploader;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use League\Flysystem\FileAttributes;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MediaController extends Controller
{
    public function __construct(private MediaUploader $uploader) {}

    public function index(Request $request): View
    {
        $query = Media::query();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('original_name', 'like', "%{$search}%")
                    ->orWhere('alt_text', 'like', "%{$search}%");
            });
        }

        $sort = $request->input('sort', 'newest');
        match ($sort) {
            'oldest' => $query->orderBy('created_at'),
            'name' => $query->orderBy('original_name'),
            'size' => $query->orderByDesc('size_bytes'),
            default => $query->orderByDesc('created_at'),
        };

        $mediaItems = $query->paginate(40)->withQueryString();

        return view('admin.media.index', compact('mediaItems', 'search', 'sort'));
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:jpg,jpeg,png,gif,webp,pdf', 'max:10240'],
            'alt_text' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $media = $this->uploader->store($request->file('file'), $request->input('alt_text'));
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        return response()->json(['id' => $media->id, 'url' => $media->url(), 'name' => $media->original_name]);
    }

    public function sync(): RedirectResponse
    {
        $mimeMap = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'pdf' => 'application/pdf',
        ];

        $existingPaths = Media::pluck('path')->flip();
        $disk = config('filesystems.default');
        $created = 0;

        foreach (Storage::disk($disk)->listContents('', true) as $item) {
            if (! $item instanceof FileAttributes) {
                continue;
            }

            $path = $item->path();

            if ($existingPaths->has($path)) {
                continue;
            }

            $filename = basename($path);
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            $mime = $mimeMap[$ext] ?? 'application/octet-stream';

            Media::create([
                'filename' => $filename,
                'original_name' => $filename,
                'path' => $path,
                'disk' => $disk,
                'mime_type' => $mime,
                'size_bytes' => $item->fileSize() ?? 0,
                'alt_text' => null,
                'uploaded_by' => auth()->id(),
            ]);

            $created++;
        }

        $label = match ($created) {
            0 => 'Already in sync — no new files found in R2.',
            1 => '1 new file imported from R2.',
            default => "{$created} new files imported from R2.",
        };

        return redirect()->route('admin.media.index')->with('success', $label);
    }

    public function proxy(Media $media): StreamedResponse
    {
        $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/avif'];
        $safe = in_array($media->mime_type, $allowed, true);
        $contentType = $safe ? $media->mime_type : 'application/octet-stream';
        $disposition = ($safe ? 'inline' : 'attachment').'; filename="'.rawurlencode($media->original_name).'"';

        $stream = Storage::disk($media->disk)->readStream($media->path);

        return response()->stream(function () use ($stream) {
            fpassthru($stream);
        }, 200, [
            'Content-Type' => $contentType,
            'Content-Disposition' => $disposition,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, max-age=300',
        ]);
    }

    public function update(Request $request, Media $media): JsonResponse
    {
        $request->validate([
            'image' => ['required', 'file', 'mimes:jpg,jpeg,png,gif,webp', 'max:20480'],
            'mode' => ['required', 'in:new,overwrite'],
        ]);

        $file = $request->file('image');
        $disk = config('filesystems.default');

        $extMap = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
        ];
        $ext = $extMap[$file->getMimeType()] ?? pathinfo($media->filename, PATHINFO_EXTENSION);

        if ($request->input('mode') === 'overwrite') {
            Storage::disk($disk)->delete($media->path);
            $file->storeAs('media', $media->filename, $disk);
            $this->uploader->generateWebpVariant($disk, $media->path, file_get_contents($file->getRealPath()));

            $media->update([
                'size_bytes' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
            ]);

            return response()->json(['id' => $media->id, 'url' => $media->url()]);
        }

        $filename = Str::slug(pathinfo($media->filename, PATHINFO_FILENAME)).'-edited-'.uniqid().'.'.$ext;
        $file->storeAs('media', $filename, $disk);
        $this->uploader->generateWebpVariant($disk, 'media/'.$filename, file_get_contents($file->getRealPath()));

        $newMedia = Media::create([
            'filename' => $filename,
            'original_name' => $filename,
            'path' => 'media/'.$filename,
            'disk' => $disk,
            'mime_type' => $file->getMimeType(),
            'size_bytes' => $file->getSize(),
            'alt_text' => null,
            'uploaded_by' => auth()->id(),
        ]);

        return response()->json(['id' => $newMedia->id, 'url' => $newMedia->url()]);
    }

    public function show(Media $media): View
    {
        return view('admin.media.show', compact('media'));
    }

    public function destroy(Media $media): RedirectResponse
    {
        $this->uploader->deleteWithVariants($media);
        $media->delete();

        return redirect()->route('admin.media.index')->with('success', 'Media deleted.');
    }
}
