<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Media;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class MediaController extends Controller
{
    public function index(): View
    {
        $mediaItems = Media::orderByDesc('created_at')->paginate(40);

        return view('admin.media.index', compact('mediaItems'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:jpg,jpeg,png,gif,webp,svg,pdf', 'max:10240'],
            'alt' => ['nullable', 'string', 'max:255'],
        ]);

        $file = $request->file('file');
        $original = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension();
        $filename = Str::slug(pathinfo($original, PATHINFO_FILENAME)).'-'.uniqid().'.'.$extension;
        $path = $file->storeAs('media', $filename, 'public');

        Media::create([
            'filename' => $filename,
            'path' => $path,
            'disk' => 'public',
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'alt' => $request->input('alt'),
            'url' => Storage::disk('public')->url($path),
        ]);

        return redirect()->route('admin.media.index')->with('success', 'File uploaded.');
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
