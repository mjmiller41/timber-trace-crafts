<?php

namespace App\Http\Controllers;

use App\Models\JournalPost;
use App\Models\Tag;
use Illuminate\Http\Response;
use Illuminate\View\View;

class JournalController extends Controller
{
    public function index(): View
    {
        $posts = JournalPost::with('featuredImage')
            ->where('status', 'published')
            ->orderByDesc('published_at')
            ->paginate(12);

        return view('journal.index', compact('posts'));
    }

    public function show(string $slug): View
    {
        $post = JournalPost::with(['featuredImage', 'tags', 'author'])
            ->where('slug', $slug)
            ->where('status', 'published')
            ->firstOrFail();

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

    public function tag(Tag $tag): View
    {
        $posts = JournalPost::with('featuredImage')
            ->where('status', 'published')
            ->whereHas('tags', fn ($q) => $q->where('tags.id', $tag->id))
            ->orderByDesc('published_at')
            ->paginate(12);

        return view('journal.tag', compact('tag', 'posts'));
    }

    public function feed(): Response
    {
        $posts = JournalPost::with('author')
            ->where('status', 'published')
            ->orderByDesc('published_at')
            ->limit(20)
            ->get();

        $content = view('journal.feed', compact('posts'))->render();

        return response($content, 200, [
            'Content-Type' => 'application/rss+xml; charset=UTF-8',
        ]);
    }
}
