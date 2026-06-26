<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\JournalPost;
use App\Models\Media;
use App\Models\Tag;
use App\Services\BlogDraftParser;
use Carbon\Carbon;
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

    public function preview(JournalPost $journal): View
    {
        $post = $journal->load(['featuredImage', 'tags', 'author']);

        $tagIds = $post->tags->pluck('id');

        $relatedPosts = $tagIds->isNotEmpty()
            ? JournalPost::with('featuredImage')
                ->where('status', 'published')
                ->where('id', '!=', $post->id)
                ->whereHas('tags', fn ($q) => $q->whereIn('tags.id', $tagIds))
                ->orderByDesc('published_at')
                ->limit(3)
                ->get()
            : collect();

        return view('journal.show', compact('post', 'relatedPosts'));
    }

    public function importIndex(): View
    {
        $draftsPath = base_path('.claude/blog/posts');
        $importedSlugs = JournalPost::pluck('slug')->flip();

        $drafts = collect(glob("{$draftsPath}/*.{md,html}", GLOB_BRACE))
            ->map(function (string $path) use ($importedSlugs): array {
                $filename = basename($path);
                $slug = pathinfo($filename, PATHINFO_FILENAME);

                return [
                    'filename' => $filename,
                    'slug' => $slug,
                    'extension' => strtolower(pathinfo($filename, PATHINFO_EXTENSION)),
                    'modified_at' => Carbon::createFromTimestamp(filemtime($path)),
                    'imported' => $importedSlugs->has($slug),
                ];
            })
            ->sortByDesc('modified_at')
            ->values();

        return view('admin.journal.import', compact('drafts'));
    }

    public function import(Request $request, string $filename): RedirectResponse
    {
        $path = base_path('.claude/blog/posts/'.basename($filename));

        abort_unless(file_exists($path), 404);

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        abort_unless(in_array($extension, ['md', 'html']), 422, 'Unsupported file type.');

        $parser = new BlogDraftParser;
        $fields = $parser->parse($path);

        abort_if(empty($fields['title']) || empty($fields['slug']), 422, 'Draft is missing title or slug.');

        $existingPost = JournalPost::where('slug', $fields['slug'])->first();

        if ($existingPost) {
            $existingPost->update([
                'title' => $fields['title'],
                'excerpt' => $fields['excerpt'],
                'body' => $fields['body'],
                'meta_title' => $fields['meta_title'],
                'meta_description' => $fields['meta_description'],
                'status' => $fields['status'],
                'published_at' => $fields['published_at'],
                'user_id' => auth()->id(),
            ]);
            $post = $existingPost;
            $message = "'{$post->title}' updated from draft.";
        } else {
            $post = JournalPost::create([
                'title' => $fields['title'],
                'slug' => $fields['slug'],
                'excerpt' => $fields['excerpt'],
                'body' => $fields['body'],
                'meta_title' => $fields['meta_title'],
                'meta_description' => $fields['meta_description'],
                'status' => $fields['status'],
                'published_at' => $fields['published_at'],
                'user_id' => auth()->id(),
            ]);
            $message = "'{$post->title}' imported successfully.";
        }

        if (! empty($fields['tags'])) {
            $tagIds = collect($fields['tags'])
                ->filter()
                ->map(fn (string $name): int => Tag::firstOrCreate(
                    ['slug' => Str::slug($name)],
                    ['name' => $name]
                )->id)
                ->all();

            $post->tags()->sync($tagIds);
        }

        return redirect()->route('admin.journal.import')->with('success', $message);
    }

    public function destroy(JournalPost $journal): RedirectResponse
    {
        $journal->delete();

        return redirect()->route('admin.journal.index')->with('success', 'Post deleted.');
    }
}
