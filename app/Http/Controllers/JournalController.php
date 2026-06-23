<?php

namespace App\Http\Controllers;

use App\Models\JournalPost;
use Illuminate\View\View;

class JournalController extends Controller
{
    public function index(): View
    {
        $posts = JournalPost::where('status', 'published')
            ->orderByDesc('published_at')
            ->paginate(12);

        return view('journal.index', compact('posts'));
    }

    public function show(string $slug): View
    {
        $post = JournalPost::where('slug', $slug)
            ->where('status', 'published')
            ->firstOrFail();

        return view('journal.show', compact('post'));
    }
}
