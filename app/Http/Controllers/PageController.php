<?php

namespace App\Http\Controllers;

use App\Models\Page;
use Illuminate\View\View;

class PageController extends Controller
{
    public function show(string $slug): View
    {
        $page = Page::where('slug', $slug)->firstOrFail();

        $viewName = "pages.{$slug}";

        return view(view()->exists($viewName) ? $viewName : 'pages.show', compact('page'));
    }
}
