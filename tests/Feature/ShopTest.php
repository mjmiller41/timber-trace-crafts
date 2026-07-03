<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ShopTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function search_filters_products_by_name(): void
    {
        Product::factory()->create(['name' => 'Walnut Hoop Earrings', 'status' => 'active']);
        Product::factory()->create(['name' => 'Maple Keepsake Box', 'status' => 'active']);

        $response = $this->get(route('shop', ['search' => 'Walnut']));

        $response->assertOk();
        $response->assertSee('Walnut Hoop Earrings');
        $response->assertDontSee('Maple Keepsake Box');
    }

    #[Test]
    public function search_box_reflects_the_active_search_term(): void
    {
        $response = $this->get(route('shop', ['search' => 'engraved']));

        $response->assertOk();
        $response->assertSee('value="engraved"', false);
    }

    #[Test]
    public function it_filters_products_by_tag(): void
    {
        $tag = Tag::create(['name' => 'Minimalist', 'slug' => 'minimalist', 'type' => 'style']);

        $tagged = Product::factory()->create(['name' => 'Minimal Studs', 'status' => 'active']);
        $tagged->tags()->attach($tag);

        Product::factory()->create(['name' => 'Ornate Pendant', 'status' => 'active']);

        $response = $this->get(route('shop', ['tag' => 'minimalist']));

        $response->assertOk();
        $response->assertSee('Minimal Studs');
        $response->assertDontSee('Ornate Pendant');
    }

    #[Test]
    public function style_tags_render_in_the_filter_sidebar(): void
    {
        Tag::create(['name' => 'Rustic', 'slug' => 'rustic', 'type' => 'style']);

        $response = $this->get(route('shop'));

        $response->assertOk();
        $response->assertSee('Style');
        $response->assertSee('Rustic');
    }
}
