<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Media;
use App\Models\Product;
use App\Models\ProductMedia;
use App\Models\ProductVariant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class ProductImportSeeder extends Seeder
{
    private string $sourceDir;

    private int $adminId = 1;

    public function run(): void
    {
        $this->sourceDir = base_path('.agents/product-images');
        Storage::disk('public')->makeDirectory('products');

        // ===== CATEGORIES =====
        $tumblers = Category::create([
            'name' => 'Tumblers',
            'slug' => 'tumblers',
            'description' => 'Laser-etched stainless steel insulated tumblers and travel mugs.',
            'sort_order' => 1,
        ]);

        $boxes = Category::create([
            'name' => 'Jewelry Boxes',
            'slug' => 'boxes',
            'description' => 'Handcrafted laser-cut wooden jewelry boxes and keepsake boxes.',
            'sort_order' => 2,
        ]);

        $jewelry = Category::create([
            'name' => 'Jewelry',
            'slug' => 'jewelry',
            'description' => 'Laser-cut solid hardwood earrings and jewelry.',
            'sort_order' => 3,
        ]);

        // ===== PRODUCTS =====
        $this->importTumbler($tumblers);
        $this->importJewelryBox($boxes);
        $this->importButterflyEarrings1($jewelry);
        $this->importButterflyEarrings2($jewelry);
        $this->importButterflyEarrings3($jewelry);
        $this->importTeardropEarrings($jewelry);
    }

    private function importTumbler(Category $category): void
    {
        $description = "🇺🇸 Celebrate America's 250th Anniversary in Style! 🇺🇸\n\n"
            ."Commemorate the historic semiquincentennial with the Timber Trace Crafts 250 Years of Freedom Stainless Steel Insulated Tumbler. Whether you're heading to a 4th of July parade, commuting to work, or relaxing at home, this premium travel mug is the perfect way to show your American pride while keeping your favorite beverages at the perfect temperature.\n\n"
            ."The front of this tumbler features a breathtaking, laser-etched circular emblem that will never fade or peel. At the center sits a majestic American bald eagle with wide-spread wings, set against a stunning background of the American flag's stars and stripes. The top border proudly displays the years '1776' and '2026' separated by a solid five-pointed star, while the lower border declares '250 YEARS OF FREEDOM' in a clean, bold sans-serif font.\n\n"
            ."Key Features:\n"
            ."- Double-wall vacuum insulation keeps drinks hot or cold\n"
            ."- Premium brushed stainless steel construction\n"
            ."- Three horizontal ridges for a secure, comfortable grip\n"
            ."- Tapered base fits standard vehicle cup holders\n"
            ."- Clear press-on lid with black rubber gasket for a secure, leak-free seal\n"
            ."- High-detail laser etching that remains crisp and permanent\n\n"
            ."The Perfect Gift: Looking for a meaningful gift for a veteran, history buff, or proud patriot? This America 250 tumbler makes an unforgettable gift for Father's Day, birthdays, Christmas, or the 2026 Fourth of July celebrations!\n\n"
            .'Please note: Hand-washing is recommended to preserve the integrity of the vacuum seal and lid gasket.';

        $product = Product::create([
            'name' => 'America 250 Tumbler',
            'slug' => 'america-250-tumbler',
            'sku_base' => 'TMBLR-AM250-STNLS20',
            'description' => $description,
            'short_description' => "Laser-etched 20oz stainless steel insulated tumbler commemorating America's 250th anniversary (1776–2026). Features a bald eagle emblem, double-wall vacuum insulation, and a spill-resistant lid.",
            'category_id' => $category->id,
            'price' => '20.00',
            'status' => 'active',
            'featured' => true,
            'sort_order' => 1,
            'meta_title' => 'America 250 Tumbler | Laser-Etched Patriotic Stainless Steel Travel Mug',
            'meta_description' => "Celebrate America's 250th anniversary with this laser-etched stainless steel tumbler. Bald eagle emblem, double-wall insulation, and spill-resistant lid.",
        ]);

        ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'TMBLR-AM250-STNLS20-01',
            'label' => 'Brushed Stainless Steel',
            'material_code' => 'STNLS',
            'stock_qty' => 9,
            'low_stock_threshold' => 3,
            'sort_order' => 0,
        ]);

        $this->importProductImages($product, 'TMBLR-AM250-STNLS20-01', 'America 250 Tumbler — Laser-Etched Patriotic Travel Mug');
    }

    private function importJewelryBox(Category $category): void
    {
        $description = "Store your most treasured keepsakes in this beautifully intricate, personalized heart-shaped jewelry box. The lid features a stunning, multi-layered laser-cut floral design, complete with a custom engraved banner to commemorate a special name, date, or meaningful phrase.\n\n"
            .'Crafted from premium 3mm Baltic Birch plywood, the box showcases a rich, dark-stained base and a flexible "living hinge" curved side that contrasts perfectly with the natural, light wood tones of the detailed lid. Inside, a felt lining provides a soft, scratch-free resting place for rings, necklaces, and delicate items.'
            ." Whether you are looking for a unique anniversary gift, a wedding keepsake, or a special place for your own jewelry, this box blends classic romance with precise modern craftsmanship.\n\n"
            ."Item Details:\n"
            ."- Material: 3mm Baltic Birch Plywood\n"
            ."- Lining: Soft felt interior to protect jewelry from scratches\n"
            .'- Design: Intricate laser-cut floral lid with a contrasting dark-stained body and living-hinge curved sides'."\n"
            .'- Personalization: Custom text engraved on the center banner (perfect for names, dates, or short quotes like "Forever Infinity")'."\n\n"
            .'Designed and crafted with care at TimberTraceCrafts in Avon Park, Florida.';

        $product = Product::create([
            'name' => 'Personalized Heart Jewelry Box',
            'slug' => 'personalized-heart-jewelry-box',
            'sku_base' => 'BOX-HRT-BBPLY3',
            'description' => $description,
            'short_description' => 'Personalized laser-cut heart-shaped jewelry box in 3mm Baltic Birch plywood. Intricate floral lid, living-hinge sides, felt interior, and your custom text engraved on the center banner.',
            'category_id' => $category->id,
            'price' => '40.00',
            'personalization_type' => 'included',
            'personalization_prompt' => 'Enter your custom text for the banner (e.g., a name, date, or short quote). Max 30 characters.',
            'personalization_max_chars' => 30,
            'status' => 'active',
            'featured' => true,
            'sort_order' => 2,
            'meta_title' => 'Personalized Heart Jewelry Box | Laser-Cut Wooden Keepsake Box',
            'meta_description' => "Beautiful personalized heart-shaped jewelry box in 3mm Baltic Birch. Intricate laser-cut floral lid, felt lining, custom engraved banner. Perfect wedding, anniversary, or Valentine's gift.",
        ]);

        ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'BOX-HRT-BBPLY3-01',
            'label' => 'Red Felt Lining',
            'material_code' => 'BBPLY3',
            'stock_qty' => 1,
            'low_stock_threshold' => 1,
            'sort_order' => 0,
        ]);

        $this->importProductImages($product, 'BOX-HRT-BBPLY3-01', 'Personalized Heart Jewelry Box — Laser-Cut Baltic Birch');
    }

    private function importButterflyEarrings1(Category $category): void
    {
        $product = $this->createEarringProduct(
            name: 'Butterfly Earrings — Design 1',
            slug: 'butterfly-earrings-design-1',
            skuBase: 'EAR-BFLY-3-01',
            description: $this->butterflyDescription(),
            shortDescription: 'Laser-cut butterfly earrings in solid 3mm hardwood. Hand-finished with natural Danish Oil. Ultra-lightweight with gold-colored ear wires. Available in 6 wood species.',
            category: $category,
            sortOrder: 3,
        );

        // 18 qty ÷ 6 species = 3 each
        $this->createWoodVariants($product, $this->woodSpecies(3), 'EAR-BFLY', '3', '01');

        $this->importProductImages($product, 'EAR-BFLY-CHY3-01', 'Butterfly Earrings Design 1 — Cherry');
        $this->attachVariantImage($product, 'EAR-BFLY-MGY3-01', 'EAR-BFLY-MGY3-01-IMG1.jpg', 'Mahogany');
        $this->attachVariantImage($product, 'EAR-BFLY-MPL3-01', 'EAR-BFLY-MPL3-01-IMG1.jpg', 'Maple');
        $this->attachVariantImage($product, 'EAR-BFLY-PDK3-01', 'EAR-BFLY-PDK3-01-IMG1.jpg', 'Padauk');
        $this->attachVariantImage($product, 'EAR-BFLY-ROK3-01', 'EAR-BFLY-ROK3-01-IMG1.jpg', 'Red Oak');
        $this->attachVariantImage($product, 'EAR-BFLY-WNT3-01', 'EAR-BFLY-WNT3-01-IMG1.jpg', 'Walnut');
    }

    private function importButterflyEarrings2(Category $category): void
    {
        $product = $this->createEarringProduct(
            name: 'Butterfly Earrings — Design 2',
            slug: 'butterfly-earrings-design-2',
            skuBase: 'EAR-BFLY-3-02',
            description: $this->butterflyDescription(),
            shortDescription: 'Laser-cut butterfly earrings in solid 3mm hardwood. Hand-finished with natural Danish Oil. Ultra-lightweight with gold-colored ear wires. Available in 6 wood species.',
            category: $category,
            sortOrder: 4,
        );

        $this->createWoodVariants($product, $this->woodSpecies(3), 'EAR-BFLY', '3', '02');

        $this->importProductImages($product, 'EAR-BFLY-CHY3-02', 'Butterfly Earrings Design 2 — Cherry');
        $this->attachVariantImage($product, 'EAR-BFLY-MGY3-02', 'EAR-BFLY-MGY3-02-IMG5.jpg', 'Mahogany');
        $this->attachVariantImage($product, 'EAR-BFLY-MPL3-02', 'EAR-BFLY-MPL3-02-IMG5.jpg', 'Maple');
        $this->attachVariantImage($product, 'EAR-BFLY-PDK3-02', 'EAR-BFLY-PDK3-02-IMG5.jpg', 'Padauk');
        $this->attachVariantImage($product, 'EAR-BFLY-ROK3-02', 'EAR-BFLY-ROK3-02-IMG5.jpg', 'Red Oak');
        $this->attachVariantImage($product, 'EAR-BFLY-WNT3-02', 'EAR-BFLY-WNT3-02-IMG5.jpg', 'Walnut');
    }

    private function importButterflyEarrings3(Category $category): void
    {
        $product = $this->createEarringProduct(
            name: 'Butterfly Earrings — Design 3',
            slug: 'butterfly-earrings-design-3',
            skuBase: 'EAR-BFLY-3-03',
            description: $this->butterflyDescription(),
            shortDescription: 'Laser-cut butterfly earrings in solid 3mm hardwood. Hand-finished with natural Danish Oil. Ultra-lightweight with gold-colored ear wires. Available in 6 wood species.',
            category: $category,
            sortOrder: 5,
        );

        // 26 qty across 6 species (4,4,4,5,5,4)
        $species = [
            ['wood' => 'Cherry',   'code' => 'CHY', 'stock' => 4],
            ['wood' => 'Mahogany', 'code' => 'MGY', 'stock' => 4],
            ['wood' => 'Maple',    'code' => 'MPL', 'stock' => 4],
            ['wood' => 'Padauk',   'code' => 'PDK', 'stock' => 5],
            ['wood' => 'Red Oak',  'code' => 'ROK', 'stock' => 5],
            ['wood' => 'Walnut',   'code' => 'WNT', 'stock' => 4],
        ];
        $this->createWoodVariants($product, $species, 'EAR-BFLY', '3', '03');

        $this->importProductImages($product, 'EAR-BFLY-CHY3-03', 'Butterfly Earrings Design 3 — Cherry');
        $this->attachVariantImage($product, 'EAR-BFLY-MGY3-03', 'EAR-BFLY-MGY3-03-IMG5.jpg', 'Mahogany');
        $this->attachVariantImage($product, 'EAR-BFLY-MPL3-03', 'EAR-BFLY-MPL3-03-IMG5.jpg', 'Maple');
        $this->attachVariantImage($product, 'EAR-BFLY-PDK3-03', 'EAR-BFLY-PDK3-03-IMG5.jpg', 'Padauk');
        $this->attachVariantImage($product, 'EAR-BFLY-ROK3-03', 'EAR-BFLY-ROK3-03-IMG5.jpg', 'Red Oak');
        $this->attachVariantImage($product, 'EAR-BFLY-WNT3-03', 'EAR-BFLY-WNT3-03-IMG5.jpg', 'Walnut');
    }

    private function importTeardropEarrings(Category $category): void
    {
        $description = "Elevate your everyday style with these elegant, laser-cut teardrop earrings, meticulously crafted from solid 3mm hardwood. The intricate, airy design brings a touch of modern boho charm to any outfit without weighing you down.\n\n"
            ."Each pair is hand-finished with Tried & True Danish Oil—a premium, 100% natural, and zero-VOC finish that highlights the rich, warm grain of the wood while ensuring your jewelry is entirely non-toxic and skin-safe. Paired with classic 18mm gold-colored ear wires, these lightweight earrings perfectly balance rustic warmth with delicate precision.\n\n"
            ."Item Details:\n"
            ."- Material: 3mm Solid Hardwood (Cherry, Mahogany, Maple, Red Oak, Padauk, Walnut)\n"
            ."- Finish: Hand-rubbed with 100% natural, zero-VOC Tried & True Danish Oil\n"
            ."- Hardware: Classic 18mm x 18mm Gold-colored ear wires\n"
            ."- Design: Intricate laser-cut teardrop silhouette\n"
            ."- Feel: Ultra-lightweight for comfortable, all-day wear\n\n"
            .'Designed and crafted with care at TimberTraceCrafts in Avon Park, Florida.';

        $product = $this->createEarringProduct(
            name: 'Teardrop Earrings',
            slug: 'teardrop-earrings',
            skuBase: 'EAR-TDRP-3',
            description: $description,
            shortDescription: 'Laser-cut teardrop earrings in solid 3mm hardwood. Hand-finished with natural Danish Oil. Ultra-lightweight with gold-colored ear wires. Available in 6 wood species.',
            category: $category,
            sortOrder: 6,
        );

        // 41 qty across 6 species (7,7,7,7,6,7)
        $species = [
            ['wood' => 'Cherry',   'code' => 'CHY', 'stock' => 7],
            ['wood' => 'Mahogany', 'code' => 'MGY', 'stock' => 7],
            ['wood' => 'Maple',    'code' => 'MPL', 'stock' => 7],
            ['wood' => 'Padauk',   'code' => 'PDK', 'stock' => 7],
            ['wood' => 'Red Oak',  'code' => 'ROK', 'stock' => 6],
            ['wood' => 'Walnut',   'code' => 'WNT', 'stock' => 7],
        ];
        $this->createWoodVariants($product, $species, 'EAR-TDRP', '3', '01');

        // CHY has 9 images — use as product-level images
        $this->importProductImages($product, 'EAR-TDRP-CHY3-01', 'Teardrop Earrings — Cherry');

        // Each non-CHY variant has IMG1–5; attach all to their variant
        $nonCherry = [
            'MGY' => 'Mahogany',
            'MPL' => 'Maple',
            'PDK' => 'Padauk',
            'ROK' => 'Red Oak',
            'WNT' => 'Walnut',
        ];

        foreach ($nonCherry as $code => $label) {
            $sku = "EAR-TDRP-{$code}3-01";
            $variant = $product->variants()->where('sku', $sku)->first();
            if (! $variant) {
                continue;
            }

            for ($i = 1; $i <= 5; $i++) {
                $filename = "{$sku}-IMG{$i}.jpg";
                $media = $this->copyAndCreateMedia($filename, "Teardrop Earrings — {$label}");
                if ($media) {
                    ProductMedia::create([
                        'product_id' => $product->id,
                        'variant_id' => $variant->id,
                        'media_id' => $media->id,
                        'sort_order' => $i,
                        'is_primary' => $i === 1,
                    ]);
                }
            }
        }
    }

    // ===== HELPERS =====

    private function createEarringProduct(
        string $name,
        string $slug,
        string $skuBase,
        string $description,
        string $shortDescription,
        Category $category,
        int $sortOrder,
    ): Product {
        return Product::create([
            'name' => $name,
            'slug' => $slug,
            'sku_base' => $skuBase,
            'description' => $description,
            'short_description' => $shortDescription,
            'category_id' => $category->id,
            'price' => '15.00',
            'status' => 'active',
            'featured' => $sortOrder <= 4,
            'sort_order' => $sortOrder,
            'meta_title' => "{$name} | Laser-Cut Solid Hardwood | Boho Eco-Friendly",
            'meta_description' => "Handcrafted {$name} in solid 3mm hardwood. Available in Cherry, Mahogany, Maple, Padauk, Red Oak, and Walnut. Natural Danish Oil finish. Made in Florida.",
        ]);
    }

    /**
     * @return array<int, array{wood: string, code: string, stock: int}>
     */
    private function woodSpecies(int $stockEach): array
    {
        return [
            ['wood' => 'Cherry',   'code' => 'CHY', 'stock' => $stockEach],
            ['wood' => 'Mahogany', 'code' => 'MGY', 'stock' => $stockEach],
            ['wood' => 'Maple',    'code' => 'MPL', 'stock' => $stockEach],
            ['wood' => 'Padauk',   'code' => 'PDK', 'stock' => $stockEach],
            ['wood' => 'Red Oak',  'code' => 'ROK', 'stock' => $stockEach],
            ['wood' => 'Walnut',   'code' => 'WNT', 'stock' => $stockEach],
        ];
    }

    /**
     * @param  array<int, array{wood: string, code: string, stock: int}>  $species
     */
    private function createWoodVariants(Product $product, array $species, string $skuPrefix, string $thickness, string $style): void
    {
        foreach ($species as $i => $s) {
            ProductVariant::create([
                'product_id' => $product->id,
                'sku' => "{$skuPrefix}-{$s['code']}{$thickness}-{$style}",
                'label' => $s['wood'],
                'material_code' => $s['code'],
                'stock_qty' => $s['stock'],
                'low_stock_threshold' => 2,
                'sort_order' => $i,
            ]);
        }
    }

    /**
     * Import all images matching a SKU prefix as product-level media (no variant).
     */
    private function importProductImages(Product $product, string $skuPrefix, string $altText): void
    {
        $files = $this->findImages($skuPrefix);

        foreach ($files as $i => $filename) {
            $media = $this->copyAndCreateMedia($filename, $altText);
            if ($media) {
                ProductMedia::create([
                    'product_id' => $product->id,
                    'variant_id' => null,
                    'media_id' => $media->id,
                    'sort_order' => $i + 1,
                    'is_primary' => $i === 0,
                ]);
            }
        }
    }

    /**
     * Attach a single image to a specific product variant.
     */
    private function attachVariantImage(Product $product, string $sku, string $filename, string $woodLabel): void
    {
        $variant = $product->variants()->where('sku', $sku)->first();
        if (! $variant) {
            return;
        }

        $media = $this->copyAndCreateMedia($filename, "Earrings — {$woodLabel}");
        if ($media) {
            ProductMedia::create([
                'product_id' => $product->id,
                'variant_id' => $variant->id,
                'media_id' => $media->id,
                'sort_order' => 1,
                'is_primary' => true,
            ]);
        }
    }

    /**
     * @return array<int, string>
     */
    private function findImages(string $prefix): array
    {
        $files = glob("{$this->sourceDir}/{$prefix}-IMG*.{jpg,jpeg,png,gif,webp}", GLOB_BRACE);
        if (! $files) {
            return [];
        }

        usort($files, function (string $a, string $b): int {
            preg_match('/IMG(\d+)\./i', $a, $matchA);
            preg_match('/IMG(\d+)\./i', $b, $matchB);

            return (int) ($matchA[1] ?? 0) <=> (int) ($matchB[1] ?? 0);
        });

        return array_map('basename', $files);
    }

    private function copyAndCreateMedia(string $filename, string $altText): ?Media
    {
        $sourcePath = "{$this->sourceDir}/{$filename}";
        if (! file_exists($sourcePath)) {
            return null;
        }

        $destPath = "products/{$filename}";
        Storage::disk('public')->put($destPath, file_get_contents($sourcePath));

        $mimeType = match (strtolower(pathinfo($filename, PATHINFO_EXTENSION))) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            default => 'image/jpeg',
        };

        return Media::create([
            'filename' => $filename,
            'original_name' => $filename,
            'disk' => 'public',
            'path' => $destPath,
            'mime_type' => $mimeType,
            'size_bytes' => filesize($sourcePath),
            'alt_text' => $altText,
            'uploaded_by' => $this->adminId,
        ]);
    }

    private function butterflyDescription(): string
    {
        return "Elevate your everyday style with these elegant, laser-cut butterfly earrings, meticulously crafted from solid 3mm hardwood. The intricate, airy design brings a touch of modern boho charm to any outfit without weighing you down.\n\n"
            ."Each pair is hand-finished with Tried & True Danish Oil—a premium, 100% natural, and zero-VOC finish that highlights the rich, warm grain of the wood while ensuring your jewelry is entirely non-toxic and skin-safe. Paired with classic 18mm gold-colored ear wires, these lightweight earrings perfectly balance rustic warmth with delicate precision.\n\n"
            ."Item Details:\n"
            ."- Material: 3mm Solid Hardwood (Cherry, Mahogany, Maple, Red Oak, Padauk, Walnut)\n"
            ."- Finish: Hand-rubbed with 100% natural, zero-VOC Tried & True Danish Oil\n"
            ."- Hardware: Classic 18mm x 18mm Gold-colored ear wires\n"
            ."- Design: Intricate laser-cut butterfly silhouette\n"
            ."- Feel: Ultra-lightweight for comfortable, all-day wear\n\n"
            .'Designed and crafted with care at TimberTraceCrafts in Avon Park, Florida.';
    }
}
