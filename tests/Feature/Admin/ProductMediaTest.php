<?php

namespace Tests\Feature\Admin;

use App\Models\Media;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProductMediaTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    /**
     * Minimal valid product payload for the update route.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(Product $product, array $overrides = []): array
    {
        return array_merge([
            'name' => $product->name,
            'slug' => $product->slug,
            'sku_base' => $product->sku_base,
            'price' => $product->price,
            'status' => $product->status,
            'personalization_type' => 'none',
        ], $overrides);
    }

    #[Test]
    public function admin_can_attach_media_to_a_product(): void
    {
        $product = Product::factory()->create();
        $media = Media::factory()->create();

        $this->actingAs($this->admin())
            ->put(route('admin.products.update', $product), $this->payload($product, [
                'media' => [
                    ['media_id' => $media->id, 'is_primary' => '1', 'alt_text' => 'Walnut studs'],
                ],
            ]))
            ->assertRedirect();

        $this->assertDatabaseHas('product_media', [
            'product_id' => $product->id,
            'media_id' => $media->id,
            'variant_id' => null,
            'is_primary' => true,
            'alt_text' => 'Walnut studs',
            'sort_order' => 0,
        ]);
    }

    #[Test]
    public function media_can_be_assigned_to_a_specific_variant(): void
    {
        $product = Product::factory()->create();
        $type = $product->variationTypes()->create(['name' => 'Wood', 'sort_order' => 0]);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'variation_type_id' => $type->id,
            'label' => 'Walnut',
        ]);
        $media = Media::factory()->create();

        // Mirror the real form: variation_types are always resubmitted so the
        // variation manager keeps existing variants alive across the save.
        $this->actingAs($this->admin())
            ->put(route('admin.products.update', $product), $this->payload($product, [
                'variation_types' => [
                    [
                        'id' => $type->id,
                        'name' => 'Wood',
                        'options' => [
                            [
                                'id' => $variant->id,
                                'label' => 'Walnut',
                                'sku' => $variant->sku,
                                'stock_qty' => 5,
                                'low_stock_threshold' => 5,
                                'is_enabled' => '1',
                                'sort_order' => 0,
                            ],
                        ],
                    ],
                ],
                'media' => [
                    ['media_id' => $media->id, 'variant_id' => $variant->id, 'is_primary' => '1'],
                ],
            ]))
            ->assertRedirect();

        $this->assertDatabaseHas('product_media', [
            'product_id' => $product->id,
            'media_id' => $media->id,
            'variant_id' => $variant->id,
        ]);
    }

    #[Test]
    public function submission_order_sets_sort_order_and_primary_is_honored(): void
    {
        $product = Product::factory()->create();
        [$first, $second] = Media::factory()->count(2)->create();

        $this->actingAs($this->admin())
            ->put(route('admin.products.update', $product), $this->payload($product, [
                'media' => [
                    ['media_id' => $second->id, 'is_primary' => '0'],
                    ['media_id' => $first->id, 'is_primary' => '1'],
                ],
            ]))
            ->assertRedirect();

        // $second was submitted first → sort_order 0; $first is the flagged primary.
        $this->assertDatabaseHas('product_media', ['media_id' => $second->id, 'sort_order' => 0, 'is_primary' => false]);
        $this->assertDatabaseHas('product_media', ['media_id' => $first->id, 'sort_order' => 1, 'is_primary' => true]);
    }

    #[Test]
    public function a_primary_is_auto_assigned_when_none_is_flagged(): void
    {
        $product = Product::factory()->create();
        $media = Media::factory()->create();

        $this->actingAs($this->admin())
            ->put(route('admin.products.update', $product), $this->payload($product, [
                'media' => [
                    ['media_id' => $media->id, 'is_primary' => '0'],
                ],
            ]))
            ->assertRedirect();

        $this->assertDatabaseHas('product_media', ['media_id' => $media->id, 'is_primary' => true]);
    }

    #[Test]
    public function media_rows_not_resubmitted_are_detached(): void
    {
        $product = Product::factory()->create();
        $keep = Media::factory()->create();
        $drop = Media::factory()->create();

        $product->media()->create(['media_id' => $keep->id, 'sort_order' => 0, 'is_primary' => true]);
        $product->media()->create(['media_id' => $drop->id, 'sort_order' => 1, 'is_primary' => false]);

        $this->actingAs($this->admin())
            ->put(route('admin.products.update', $product), $this->payload($product, [
                'media' => [
                    ['media_id' => $keep->id, 'is_primary' => '1'],
                ],
            ]))
            ->assertRedirect();

        $this->assertDatabaseHas('product_media', ['product_id' => $product->id, 'media_id' => $keep->id]);
        $this->assertDatabaseMissing('product_media', ['product_id' => $product->id, 'media_id' => $drop->id]);
        $this->assertSame(1, $product->media()->count());
    }

    #[Test]
    public function media_attachment_drives_the_primary_image_url_accessor(): void
    {
        $product = Product::factory()->create();
        $media = Media::factory()->create(['path' => 'media/hero.png', 'disk' => 'public']);

        $this->actingAs($this->admin())
            ->put(route('admin.products.update', $product), $this->payload($product, [
                'media' => [['media_id' => $media->id, 'is_primary' => '1']],
            ]))
            ->assertRedirect();

        $this->assertNotNull($product->fresh()->primary_image_url);
    }
}
