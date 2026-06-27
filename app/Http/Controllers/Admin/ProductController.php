<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Tag;
use App\Services\Etsy\EtsyClient;
use App\Services\Etsy\EtsyOAuthService;
use App\Services\Etsy\EtsyProductSync;
use App\Services\Etsy\EtsyReadinessStateService;
use App\Services\Etsy\EtsyReturnPolicyService;
use App\Services\Etsy\EtsyShippingProfileService;
use App\Services\Etsy\EtsyShopSectionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(): View
    {
        $products = Product::with('category')
            ->withCount('variants')
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
        $etsyReadinessStates = $this->fetchEtsyReadinessStates();
        $etsyDefaultReadinessStateId = Setting::get('etsy.readiness_state_id');

        return view('admin.products.create', compact('categories', 'tags', 'categoryTaxonomyMap', 'etsyTaxonomySuggestions', 'etsyShopSections', 'etsyShippingProfiles', 'etsyReturnPolicies', 'etsyReadinessStates', 'etsyDefaultReadinessStateId'));
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
            'etsy_is_personalizable' => ['boolean'],
            'etsy_is_private' => ['boolean'],
            'etsy_should_auto_renew' => ['boolean'],
            'etsy_has_variations' => ['boolean'],
            'etsy_who_made' => ['nullable', 'string', 'in:i_did,collective,someone_else'],
            'etsy_when_made' => ['nullable', 'string'],
            'etsy_is_supply' => ['boolean'],
            'etsy_language' => ['nullable', 'string', 'max:10'],
            'etsy_processing_min' => ['nullable', 'integer', 'min:0'],
            'etsy_processing_max' => ['nullable', 'integer', 'min:0'],
            'etsy_taxonomy_id' => ['nullable', 'integer'],
            'etsy_shop_section_id' => ['nullable', 'integer'],
            'etsy_shipping_profile_id' => ['nullable', 'integer'],
            'etsy_return_policy_id' => ['nullable', 'integer'],
            'etsy_readiness_state_id' => ['nullable', 'integer'],
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
            'variation_types' => ['nullable', 'array'],
            'variation_types.*.name' => ['nullable', 'string', 'max:50'],
            'variation_types.*.options' => ['nullable', 'array'],
            'variation_types.*.options.*.price' => ['nullable', 'numeric', 'min:0'],
        ]);

        $tags = $validated['tags'] ?? [];
        $variationTypes = $request->input('variation_types', []);
        unset($validated['tags'], $validated['variation_types']);

        $validated = $this->processEtsyFields($validated);

        $product = (new Product)->fill($validated);
        $product->saveQuietly();
        $product->tags()->sync($tags);
        $this->syncVariationTypes($product, $variationTypes);

        $redirect = redirect()->route('admin.products.edit', $product)->with('success', 'Product created.');

        if ($product->sold_on_etsy && $product->status === 'active') {
            try {
                $sync = new EtsyProductSync(new EtsyClient(app(EtsyOAuthService::class)));
                $sync->syncProduct($product);
                $redirect->with('success_etsy', 'Etsy listing created.');
            } catch (\Throwable $e) {
                $redirect->with('error_etsy', 'Etsy sync failed: '.$e->getMessage());
            }
        }

        return $redirect;
    }

    public function edit(Product $product): View
    {
        $product->load(['variationTypes.variants', 'variants', 'tags', 'media']);
        $categories = Category::orderBy('name')->get();
        $tags = Tag::orderBy('name')->get();
        $categoryTaxonomyMap = $categories->pluck('etsy_taxonomy_id', 'id')->filter()->toArray();
        $etsyTaxonomySuggestions = self::etsyTaxonomySuggestions();
        $etsyShopSections = $this->fetchEtsySections();
        $etsyShippingProfiles = $this->fetchEtsyShippingProfiles();
        $etsyReturnPolicies = $this->fetchEtsyReturnPolicies();
        $etsyReadinessStates = $this->fetchEtsyReadinessStates();
        $etsyDefaultReadinessStateId = Setting::get('etsy.readiness_state_id');

        return view('admin.products.edit', compact('product', 'categories', 'tags', 'categoryTaxonomyMap', 'etsyTaxonomySuggestions', 'etsyShopSections', 'etsyShippingProfiles', 'etsyReturnPolicies', 'etsyReadinessStates', 'etsyDefaultReadinessStateId'));
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
            'etsy_is_personalizable' => ['boolean'],
            'etsy_is_private' => ['boolean'],
            'etsy_should_auto_renew' => ['boolean'],
            'etsy_has_variations' => ['boolean'],
            'etsy_who_made' => ['nullable', 'string', 'in:i_did,collective,someone_else'],
            'etsy_when_made' => ['nullable', 'string'],
            'etsy_is_supply' => ['boolean'],
            'etsy_language' => ['nullable', 'string', 'max:10'],
            'etsy_processing_min' => ['nullable', 'integer', 'min:0'],
            'etsy_processing_max' => ['nullable', 'integer', 'min:0'],
            'etsy_taxonomy_id' => ['nullable', 'integer'],
            'etsy_shop_section_id' => ['nullable', 'integer'],
            'etsy_shipping_profile_id' => ['nullable', 'integer'],
            'etsy_return_policy_id' => ['nullable', 'integer'],
            'etsy_readiness_state_id' => ['nullable', 'integer'],
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
            'variation_types' => ['nullable', 'array'],
            'variation_types.*.name' => ['nullable', 'string', 'max:50'],
            'variation_types.*.options' => ['nullable', 'array'],
            'variation_types.*.options.*.price' => ['nullable', 'numeric', 'min:0'],
        ]);

        $tags = $validated['tags'] ?? [];
        $variationTypes = $request->input('variation_types', []);
        unset($validated['tags'], $validated['variation_types']);

        $validated = $this->processEtsyFields($validated);

        // saveQuietly suppresses model events so the observer doesn't also queue a background job
        $product->fill($validated)->saveQuietly();
        $product->tags()->sync($tags);
        $this->syncVariationTypes($product, $variationTypes);

        $redirect = redirect()->route('admin.products.edit', $product)->with('success', 'Product updated.');

        if ($product->sold_on_etsy && ($product->etsy_listing_id || $product->status === 'active')) {
            try {
                $sync = new EtsyProductSync(new EtsyClient(app(EtsyOAuthService::class)));
                $sync->syncProduct($product);
                $redirect->with('success_etsy', 'Etsy listing updated.');
            } catch (\Throwable $e) {
                $redirect->with('error_etsy', 'Etsy sync failed: '.$e->getMessage());
            }
        }

        return $redirect;
    }

    public function destroy(Product $product): RedirectResponse
    {
        if ($product->sold_on_etsy && $product->etsy_listing_id) {
            try {
                $sync = new EtsyProductSync(new EtsyClient(app(EtsyOAuthService::class)));
                $sync->deleteProduct($product);
            } catch (\Throwable $e) {
                return redirect()->route('admin.products.index')
                    ->with('error', "Etsy deletion failed: {$e->getMessage()} — product was NOT deleted.");
            }
        }

        $product->delete();

        return redirect()->route('admin.products.index')->with('success', 'Product deleted.');
    }

    /**
     * @param  array<int, array<string, mixed>>  $types
     */
    private function syncVariationTypes(Product $product, array $types): void
    {
        $submittedTypeIds = [];
        $submittedVariantIds = [];

        foreach ($types as $sortOrder => $typeData) {
            $typeName = $typeData['name'] ?? 'Style';
            $typeId = ! empty($typeData['id']) ? (int) $typeData['id'] : null;

            if ($typeId) {
                $type = $product->variationTypes()->find($typeId);
                if ($type) {
                    $type->update(['name' => $typeName, 'sort_order' => $sortOrder]);
                } else {
                    $type = $product->variationTypes()->create(['name' => $typeName, 'sort_order' => $sortOrder]);
                }
            } else {
                $type = $product->variationTypes()->create(['name' => $typeName, 'sort_order' => $sortOrder]);
            }

            $submittedTypeIds[] = $type->id;

            foreach ($typeData['options'] ?? [] as $optIndex => $optData) {
                $price = $optData['price'] ?? null;
                $variantData = [
                    'variation_type_id' => $type->id,
                    'label' => $optData['label'] ?? '',
                    'sku' => $optData['sku'] ?? null,
                    'price' => ($price !== null && $price !== '') ? (float) $price : null,
                    'is_enabled' => ($optData['is_enabled'] ?? '1') !== '0',
                    'material_code' => $optData['material_code'] ?? null,
                    'stock_qty' => (int) ($optData['stock_qty'] ?? 0),
                    'low_stock_threshold' => (int) ($optData['low_stock_threshold'] ?? 5),
                    'sort_order' => $optIndex,
                ];

                $variantId = ! empty($optData['id']) ? (int) $optData['id'] : null;
                if ($variantId) {
                    $variant = $product->variants()->find($variantId);
                    if ($variant) {
                        $variant->update($variantData);
                        $submittedVariantIds[] = $variant->id;

                        continue;
                    }
                }

                $variant = $product->variants()->create($variantData);
                $submittedVariantIds[] = $variant->id;
            }
        }

        // Delete types and variants not present in the submission
        $product->variationTypes()->whereNotIn('id', $submittedTypeIds)->delete();
        $product->variants()->whereNotIn('id', $submittedVariantIds)->delete();
    }

    private function fetchEtsySections(): array
    {
        return Cache::remember('etsy.admin.sections', 3600, function () {
            try {
                return (new EtsyShopSectionService(new EtsyClient(app(EtsyOAuthService::class))))->getSections();
            } catch (\Throwable) {
                return [];
            }
        });
    }

    private function fetchEtsyReadinessStates(): array
    {
        return Cache::remember('etsy.admin.readiness_states', 3600, function () {
            try {
                return (new EtsyReadinessStateService(new EtsyClient(app(EtsyOAuthService::class))))->getStates();
            } catch (\Throwable) {
                return [];
            }
        });
    }

    private function fetchEtsyShippingProfiles(): array
    {
        return Cache::remember('etsy.admin.shipping_profiles', 3600, function () {
            try {
                return (new EtsyShippingProfileService(new EtsyClient(app(EtsyOAuthService::class))))->getProfiles();
            } catch (\Throwable) {
                return [];
            }
        });
    }

    private function fetchEtsyReturnPolicies(): array
    {
        return Cache::remember('etsy.admin.return_policies', 3600, function () {
            try {
                return (new EtsyReturnPolicyService(new EtsyClient(app(EtsyOAuthService::class))))->getPolicies();
            } catch (\Throwable) {
                return [];
            }
        });
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
        foreach (['sold_on_etsy', 'etsy_is_taxable', 'etsy_is_customizable', 'etsy_is_personalizable', 'etsy_is_private', 'etsy_should_auto_renew', 'etsy_is_supply', 'etsy_has_variations'] as $bool) {
            $data[$bool] = (bool) ($data[$bool] ?? false);
        }

        return $data;
    }
}
