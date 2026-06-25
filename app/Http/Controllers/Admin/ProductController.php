<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\Tag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(): View
    {
        $products = Product::with('category')
            ->orderByDesc('created_at')
            ->paginate(25);

        $categories = Category::orderBy('name')->get();

        return view('admin.products.index', compact('products', 'categories'));
    }

    public function create(): View
    {
        $categories = Category::orderBy('name')->get();
        $tags = Tag::orderBy('name')->get();

        return view('admin.products.create', compact('categories', 'tags'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:products,slug'],
            'sku_base' => ['required', 'string', 'max:100', 'unique:products,sku_base'],
            'price' => ['required', 'numeric', 'min:0'],
            'sale_price' => ['nullable', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
            'short_description' => ['nullable', 'string', 'max:500'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'status' => ['required', 'string', 'in:draft,active,archived'],
            'featured' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'personalization_type' => ['nullable', 'string', 'in:none,included,addon'],
            'personalization_price' => ['nullable', 'numeric', 'min:0'],
            'personalization_prompt' => ['nullable', 'string', 'max:255'],
            'personalization_max_chars' => ['nullable', 'integer', 'min:1'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['integer', 'exists:tags,id'],
        ]);

        $tags = $validated['tags'] ?? [];
        unset($validated['tags']);

        $product = Product::create($validated);
        $product->tags()->sync($tags);

        return redirect()->route('admin.products.edit', $product)->with('success', 'Product created.');
    }

    public function edit(Product $product): View
    {
        $product->load(['variants', 'tags', 'media']);
        $categories = Category::orderBy('name')->get();
        $tags = Tag::orderBy('name')->get();

        return view('admin.products.edit', compact('product', 'categories', 'tags'));
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', "unique:products,slug,{$product->id}"],
            'sku_base' => ['required', 'string', 'max:100', "unique:products,sku_base,{$product->id}"],
            'price' => ['required', 'numeric', 'min:0'],
            'sale_price' => ['nullable', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
            'short_description' => ['nullable', 'string', 'max:500'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'status' => ['required', 'string', 'in:draft,active,archived'],
            'featured' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'personalization_type' => ['nullable', 'string', 'in:none,included,addon'],
            'personalization_price' => ['nullable', 'numeric', 'min:0'],
            'personalization_prompt' => ['nullable', 'string', 'max:255'],
            'personalization_max_chars' => ['nullable', 'integer', 'min:1'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['integer', 'exists:tags,id'],
        ]);

        $tags = $validated['tags'] ?? [];
        unset($validated['tags']);

        $product->update($validated);
        $product->tags()->sync($tags);

        return redirect()->route('admin.products.edit', $product)->with('success', 'Product updated.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $product->delete();

        return redirect()->route('admin.products.index')->with('success', 'Product deleted.');
    }
}
