<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\JournalPost;
use App\Models\Product;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        $featuredProducts = Product::with(['media.media', 'category'])
            ->where('status', 'active')
            ->where('featured', true)
            ->orderBy('sort_order')
            ->limit(8)
            ->get();

        $newArrivals = Product::with(['media.media', 'category'])
            ->where('status', 'active')
            ->latest()
            ->limit(4)
            ->get();

        $categories = Category::whereNull('parent_id')
            ->with('image')
            ->orderBy('sort_order')
            ->limit(6)
            ->get();

        $journalPosts = JournalPost::live()
            ->with('featuredImage')
            ->latest('published_at')
            ->limit(3)
            ->get();

        return view('home.index', compact(
            'featuredProducts',
            'newArrivals',
            'categories',
            'journalPosts'
        ));
    }
}
