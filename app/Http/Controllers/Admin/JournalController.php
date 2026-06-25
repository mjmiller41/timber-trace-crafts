<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\JournalPost;
use App\Models\Media;
use App\Models\Tag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class JournalController extends Controller
{
    public function index(): View
    {
        $posts = JournalPost::orderByDesc('created_at')->paginate(25);

        return view('admin.journal.index', compact('posts'));
    }

    public function create(): View
    {
        $tags = Tag::orderBy('name')->get();

        return view('admin.journal.create', compact('tags'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:journal_posts,slug'],
            'body' => ['required', 'string'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'status' => ['required', 'in:draft,published'],
            'published_at' => ['nullable', 'date'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['integer', 'exists:tags,id'],
            'featured_image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
        ]);

        if ($validated['status'] === 'published' && empty($validated['published_at'])) {
            $validated['published_at'] = now();
        }

        $validated['featured_image_id'] = $this->uploadFeaturedImage($request);

        $post = JournalPost::create($validated);
        $post->tags()->sync($request->input('tags', []));

        return redirect()->route('admin.journal.index')->with('success', 'Post created.');
    }

    public function edit(JournalPost $journal): View
    {
        $tags = Tag::orderBy('name')->get();
        $post = $journal;

        return view('admin.journal.edit', compact('post', 'tags'));
    }

    public function update(Request $request, JournalPost $journal): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', "unique:journal_posts,slug,{$journal->id}"],
            'body' => ['required', 'string'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'status' => ['required', 'in:draft,published'],
            'published_at' => ['nullable', 'date'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['integer', 'exists:tags,id'],
            'featured_image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
        ]);

        if ($validated['status'] === 'published' && empty($validated['published_at']) && ! $journal->published_at) {
            $validated['published_at'] = now();
        }

        $newImageId = $this->uploadFeaturedImage($request);
        if ($newImageId) {
            $validated['featured_image_id'] = $newImageId;
        }

        $journal->update($validated);
        $journal->tags()->sync($request->input('tags', []));

        return redirect()->route('admin.journal.index')->with('success', 'Post updated.');
    }

    private function uploadFeaturedImage(Request $request): ?int
    {
        if (! $request->hasFile('featured_image')) {
            return null;
        }

        $file = $request->file('featured_image');
        $original = $file->getClientOriginalName();
        $extMap = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
        $ext = $extMap[$file->getMimeType()] ?? abort(422, 'Unsupported image type.');
        $filename = Str::slug(pathinfo($original, PATHINFO_FILENAME)).'-'.uniqid().'.'.$ext;
        $path = $file->storeAs('media', $filename, config('filesystems.default'));

        $media = Media::create([
            'filename' => $filename,
            'original_name' => $original,
            'path' => $path,
            'disk' => config('filesystems.default'),
            'mime_type' => $file->getMimeType(),
            'size_bytes' => $file->getSize(),
            'alt_text' => null,
            'uploaded_by' => auth()->id(),
        ]);

        return $media->id;
    }

    public function destroy(JournalPost $journal): RedirectResponse
    {
        $journal->delete();

        return redirect()->route('admin.journal.index')->with('success', 'Post deleted.');
    }
}
