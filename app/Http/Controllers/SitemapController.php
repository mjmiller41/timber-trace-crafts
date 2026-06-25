<?php

namespace App\Http\Controllers;

use App\Models\JournalPost;
use App\Models\Product;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function index(): Response
    {
        $products = Product::where('status', 'active')
            ->select('slug', 'updated_at')
            ->orderByDesc('updated_at')
            ->get();

        $posts = JournalPost::where('status', 'published')
            ->select('slug', 'updated_at')
            ->orderByDesc('updated_at')
            ->get();

        $content = view('sitemap', compact('products', 'posts'))->render();

        return response($content, 200, [
            'Content-Type' => 'application/xml',
        ]);
    }
}
