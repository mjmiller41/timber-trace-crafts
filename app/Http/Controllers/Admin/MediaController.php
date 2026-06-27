<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Media;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use League\Flysystem\FileAttributes;

class MediaController extends Controller
{
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

        $extMap = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'application/pdf' => 'pdf',
        ];

        $file = $request->file('file');
        $original = $file->getClientOriginalName();
        $extension = $extMap[$file->getMimeType()] ?? null;

        if (! $extension) {
            return response()->json(['error' => 'Unsupported file type.'], 422);
        }

        $filename = Str::slug(pathinfo($original, PATHINFO_FILENAME)).'-'.uniqid().'.'.$extension;
        $path = $file->storeAs('media', $filename, config('filesystems.default'));

        $media = Media::create([
            'filename' => $filename,
            'original_name' => $original,
            'path' => $path,
            'disk' => config('filesystems.default'),
            'mime_type' => $file->getMimeType(),
            'size_bytes' => $file->getSize(),
            'alt_text' => $request->input('alt_text'),
            'uploaded_by' => auth()->id(),
        ]);

        return response()->json(['id' => $media->id, 'url' => $media->url(), 'name' => $original]);
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

    public function show(Media $media): View
    {
        return view('admin.media.show', compact('media'));
    }

    public function destroy(Media $media): RedirectResponse
    {
        Storage::disk($media->disk)->delete($media->path);
        $media->delete();

        return redirect()->route('admin.media.index')->with('success', 'Media deleted.');
    }
}
