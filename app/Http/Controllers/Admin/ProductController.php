<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\Tag;
use App\Services\Etsy\EtsyClient;
use App\Services\Etsy\EtsyOAuthService;
use App\Services\Etsy\EtsyReturnPolicyService;
use App\Services\Etsy\EtsyShippingProfileService;
use App\Services\Etsy\EtsyShopSectionService;
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
        $categoryTaxonomyMap = $categories->pluck('etsy_taxonomy_id', 'id')->filter()->toArray();
        $etsyTaxonomySuggestions = self::etsyTaxonomySuggestions();
        $etsyShopSections = $this->fetchEtsySections();
        $etsyShippingProfiles = $this->fetchEtsyShippingProfiles();
        $etsyReturnPolicies = $this->fetchEtsyReturnPolicies();

        return view('admin.products.create', compact('categories', 'tags', 'categoryTaxonomyMap', 'etsyTaxonomySuggestions', 'etsyShopSections', 'etsyShippingProfiles', 'etsyReturnPolicies'));
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
            'sold_on_etsy' => ['boolean'],
            'etsy_listing_type' => ['nullable', 'string', 'in:physical,digital,download'],
            'etsy_is_taxable' => ['boolean'],
            'etsy_is_customizable' => ['boolean'],
            'etsy_is_private' => ['boolean'],
            'etsy_should_auto_renew' => ['boolean'],
            'etsy_who_made' => ['nullable', 'string', 'in:i_did,collective,someone_else'],
            'etsy_when_made' => ['nullable', 'string'],
            'etsy_is_supply' => ['boolean'],
            'etsy_processing_min' => ['nullable', 'integer', 'min:0'],
            'etsy_processing_max' => ['nullable', 'integer', 'min:0'],
            'etsy_taxonomy_id' => ['nullable', 'integer'],
            'etsy_shop_section_id' => ['nullable', 'integer'],
            'etsy_shipping_profile_id' => ['nullable', 'integer'],
            'etsy_return_policy_id' => ['nullable', 'integer'],
            'etsy_featured_rank' => ['nullable', 'integer'],
            'etsy_tags_raw' => ['nullable', 'string'],
            'etsy_materials_raw' => ['nullable', 'string'],
            'etsy_style_raw' => ['nullable', 'string'],
            'etsy_item_weight' => ['nullable', 'numeric', 'min:0'],
            'etsy_item_weight_unit' => ['nullable', 'string', 'in:oz,g,lbs,kg'],
            'etsy_item_length' => ['nullable', 'numeric', 'min:0'],
            'etsy_item_width' => ['nullable', 'numeric', 'min:0'],
            'etsy_item_height' => ['nullable', 'numeric', 'min:0'],
            'etsy_item_dimensions_unit' => ['nullable', 'string', 'in:in,cm'],
        ]);

        $tags = $validated['tags'] ?? [];
        unset($validated['tags']);

        $validated = $this->processEtsyFields($validated);

        $product = Product::create($validated);
        $product->tags()->sync($tags);

        return redirect()->route('admin.products.edit', $product)->with('success', 'Product created.');
    }

    public function edit(Product $product): View
    {
        $product->load(['variants', 'tags', 'media']);
        $categories = Category::orderBy('name')->get();
        $tags = Tag::orderBy('name')->get();
        $categoryTaxonomyMap = $categories->pluck('etsy_taxonomy_id', 'id')->filter()->toArray();
        $etsyTaxonomySuggestions = self::etsyTaxonomySuggestions();
        $etsyShopSections = $this->fetchEtsySections();
        $etsyShippingProfiles = $this->fetchEtsyShippingProfiles();
        $etsyReturnPolicies = $this->fetchEtsyReturnPolicies();

        return view('admin.products.edit', compact('product', 'categories', 'tags', 'categoryTaxonomyMap', 'etsyTaxonomySuggestions', 'etsyShopSections', 'etsyShippingProfiles', 'etsyReturnPolicies'));
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
            'sold_on_etsy' => ['boolean'],
            'etsy_listing_type' => ['nullable', 'string', 'in:physical,digital,download'],
            'etsy_is_taxable' => ['boolean'],
            'etsy_is_customizable' => ['boolean'],
            'etsy_is_private' => ['boolean'],
            'etsy_should_auto_renew' => ['boolean'],
            'etsy_who_made' => ['nullable', 'string', 'in:i_did,collective,someone_else'],
            'etsy_when_made' => ['nullable', 'string'],
            'etsy_is_supply' => ['boolean'],
            'etsy_processing_min' => ['nullable', 'integer', 'min:0'],
            'etsy_processing_max' => ['nullable', 'integer', 'min:0'],
            'etsy_taxonomy_id' => ['nullable', 'integer'],
            'etsy_shop_section_id' => ['nullable', 'integer'],
            'etsy_shipping_profile_id' => ['nullable', 'integer'],
            'etsy_return_policy_id' => ['nullable', 'integer'],
            'etsy_featured_rank' => ['nullable', 'integer'],
            'etsy_tags_raw' => ['nullable', 'string'],
            'etsy_materials_raw' => ['nullable', 'string'],
            'etsy_style_raw' => ['nullable', 'string'],
            'etsy_item_weight' => ['nullable', 'numeric', 'min:0'],
            'etsy_item_weight_unit' => ['nullable', 'string', 'in:oz,g,lbs,kg'],
            'etsy_item_length' => ['nullable', 'numeric', 'min:0'],
            'etsy_item_width' => ['nullable', 'numeric', 'min:0'],
            'etsy_item_height' => ['nullable', 'numeric', 'min:0'],
            'etsy_item_dimensions_unit' => ['nullable', 'string', 'in:in,cm'],
        ]);

        $tags = $validated['tags'] ?? [];
        unset($validated['tags']);

        $validated = $this->processEtsyFields($validated);

        $product->update($validated);
        $product->tags()->sync($tags);

        return redirect()->route('admin.products.edit', $product)->with('success', 'Product updated.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $product->delete();

        return redirect()->route('admin.products.index')->with('success', 'Product deleted.');
    }

    /**
     * Curated Etsy taxonomy suggestions per product category.
     * Keys are our internal category IDs; values are ordered from most-specific to broadest.
     * Sourced from .claude/etsy_data/seller_taxonomy.json — update there when adding categories.
     *
     * @return array<int, array<int, array{id: int, label: string}>>
     */
    /**
     * Fetch live shop sections from Etsy (cached). Returns empty array if Etsy is not connected.
     *
     * @return array<int, array{id: int, title: string}>
     */
    private function fetchEtsySections(): array
    {
        try {
            $service = new EtsyShopSectionService(new EtsyClient(app(EtsyOAuthService::class)));

            return $service->getSections();
        } catch (\Throwable) {
            return [];
        }
    }

    private function fetchEtsyShippingProfiles(): array
    {
        try {
            $service = new EtsyShippingProfileService(new EtsyClient(app(EtsyOAuthService::class)));

            return $service->getProfiles();
        } catch (\Throwable) {
            return [];
        }
    }

    private function fetchEtsyReturnPolicies(): array
    {
        try {
            $service = new EtsyReturnPolicyService(new EtsyClient(app(EtsyOAuthService::class)));

            return $service->getPolicies();
        } catch (\Throwable) {
            return [];
        }
    }

    private static function etsyTaxonomySuggestions(): array
    {
        return [
            3 => [ // Jewelry
                ['id' => 1208, 'label' => 'Jewelry › Earrings › Dangle & Drop Earrings'],
                ['id' => 1214, 'label' => 'Jewelry › Earrings › Stud Earrings'],
                ['id' => 1212, 'label' => 'Jewelry › Earrings › Hoop Earrings'],
                ['id' => 1207, 'label' => 'Jewelry › Earrings › Cuff & Wrap Earrings'],
                ['id' => 1206, 'label' => 'Jewelry › Earrings › Cluster Earrings'],
                ['id' => 1204, 'label' => 'Jewelry › Earrings › Chandelier Earrings'],
                ['id' => 1205, 'label' => 'Jewelry › Earrings › Clip-On Earrings'],
                ['id' => 1215, 'label' => 'Jewelry › Earrings › Threader Earrings'],
                ['id' => 1211, 'label' => 'Jewelry › Earrings › Gauge & Plug Earrings'],
                ['id' => 1213, 'label' => 'Jewelry › Earrings › Screw Back Earrings'],
                ['id' => 1684, 'label' => 'Weddings › Jewelry › Earrings'],
                ['id' => 1203, 'label' => 'Jewelry › Earrings'],
                ['id' => 1179, 'label' => 'Jewelry'],
            ],
            2 => [ // Jewelry Boxes
                ['id' => 6102, 'label' => 'Jewelry › Jewelry Storage › Jewelry Boxes'],
                ['id' => 1216, 'label' => 'Jewelry › Jewelry Storage'],
                ['id' => 6674, 'label' => 'Craft Supplies & Tools › Storage & Organization › Jewelry Displays'],
            ],
            1 => [ // Tumblers
                ['id' => 1071, 'label' => 'Home & Living › Kitchen & Dining › Drink & Barware › Drinkware › Tumblers & Water Glasses'],
                ['id' => 1862, 'label' => 'Home & Living › Kitchen & Dining › Drink & Barware › Drinkware'],
                ['id' => 1917, 'label' => 'Home & Living › Bathroom › Bathroom Decor › Bathroom Tumblers'],
            ],
        ];
    }

    /**
     * Convert comma-separated raw Etsy tag/material/style fields to arrays
     * and normalise boolean checkboxes that aren't submitted when unchecked.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function processEtsyFields(array $data): array
    {
        $csvToArray = static function (?string $raw): ?array {
            if ($raw === null || trim($raw) === '') {
                return null;
            }

            return array_values(array_filter(array_map('trim', explode(',', $raw))));
        };

        $data['etsy_tags'] = $csvToArray($data['etsy_tags_raw'] ?? null);
        $data['etsy_materials'] = $csvToArray($data['etsy_materials_raw'] ?? null);
        $data['etsy_style'] = $csvToArray($data['etsy_style_raw'] ?? null);

        unset($data['etsy_tags_raw'], $data['etsy_materials_raw'], $data['etsy_style_raw']);

        // Checkboxes are absent from POST when unchecked — normalise to false
        foreach (['sold_on_etsy', 'etsy_is_taxable', 'etsy_is_customizable', 'etsy_is_private', 'etsy_should_auto_renew', 'etsy_is_supply'] as $bool) {
            $data[$bool] = (bool) ($data[$bool] ?? false);
        }

        return $data;
    }
}
