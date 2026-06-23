<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\JournalPost;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        return view('admin.journal.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:journal_posts,slug'],
            'body' => ['required', 'string'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'published' => ['boolean'],
            'published_at' => ['nullable', 'date'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
        ]);

        JournalPost::create($validated);

        return redirect()->route('admin.journal.index')->with('success', 'Post created.');
    }

    public function edit(JournalPost $journal): View
    {
        return view('admin.journal.edit', compact('journal'));
    }

    public function update(Request $request, JournalPost $journal): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', "unique:journal_posts,slug,{$journal->id}"],
            'body' => ['required', 'string'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'published' => ['boolean'],
            'published_at' => ['nullable', 'date'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
        ]);

        $journal->update($validated);

        return redirect()->route('admin.journal.index')->with('success', 'Post updated.');
    }

    public function destroy(JournalPost $journal): RedirectResponse
    {
        $journal->delete();

        return redirect()->route('admin.journal.index')->with('success', 'Post deleted.');
    }
}
