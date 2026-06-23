<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ShopController extends Controller
{
    public function index(Request $request): View
    {
        $query = Product::with(['media.media', 'category', 'variants'])
            ->where('status', 'active');

        // Category filter
        if ($categorySlug = $request->query('category')) {
            $category = Category::where('slug', $categorySlug)->first();
            if ($category) {
                $query->where('category_id', $category->id);
            }
        }

        // Tag filter (wood species, style, etc.)
        if ($tagSlug = $request->query('tag')) {
            $tag = Tag::where('slug', $tagSlug)->first();
            if ($tag) {
                $query->whereHas('tags', fn ($q) => $q->where('tags.id', $tag->id));
            }
        }

        // Search
        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('short_description', 'like', "%{$search}%");
            });
        }

        // Sale filter
        if ($request->query('sale')) {
            $query->whereNotNull('sale_price');
        }

        // Sort
        match ($request->query('sort', 'newest')) {
            'price_asc' => $query->orderBy('price'),
            'price_desc' => $query->orderByDesc('price'),
            'name_asc' => $query->orderBy('name'),
            'featured' => $query->orderByDesc('featured')->orderBy('sort_order'),
            default => $query->latest(),
        };

        $products = $query->paginate(16)->withQueryString();
        $categories = Category::whereNull('parent_id')->orderBy('sort_order')->get();
        $woodTags = Tag::where('type', 'wood_species')->orderBy('name')->get();

        $activeCategory = $request->query('category');
        $activeTag = $request->query('tag');
        $activeSort = $request->query('sort', 'newest');

        return view('shop.index', compact(
            'products',
            'categories',
            'woodTags',
            'activeCategory',
            'activeTag',
            'activeSort'
        ));
    }
}
