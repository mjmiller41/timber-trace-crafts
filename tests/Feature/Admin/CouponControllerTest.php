<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CouponControllerTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    #[Test]
    public function create_page_loads_with_category_and_product_options(): void
    {
        Category::create(['name' => 'Earrings', 'slug' => 'earrings']);
        Product::factory()->create(['name' => 'Cherry Butterfly Earrings']);

        $response = $this->actingAs($this->admin())->get(route('admin.coupons.create'));

        $response->assertOk();
        $response->assertSee('Earrings');
        $response->assertSee('Cherry Butterfly Earrings');
    }

    #[Test]
    public function edit_page_loads_with_category_and_product_options(): void
    {
        $coupon = Coupon::factory()->create();
        Category::create(['name' => 'Boxes', 'slug' => 'boxes']);
        Product::factory()->create(['name' => 'Heart Keepsake Box']);

        $response = $this->actingAs($this->admin())->get(route('admin.coupons.edit', $coupon));

        $response->assertOk();
        $response->assertSee('Boxes');
        $response->assertSee('Heart Keepsake Box');
    }

    #[Test]
    public function percent_coupon_over_100_is_rejected(): void
    {
        $response = $this->actingAs($this->admin())->post(route('admin.coupons.store'), [
            'code' => 'BIGSAVE',
            'type' => 'percent',
            'value' => 150,
            'active' => true,
        ]);

        $response->assertSessionHasErrors('value');
        $this->assertDatabaseCount('coupons', 0);
    }

    #[Test]
    public function fixed_coupon_over_100_is_allowed(): void
    {
        $response = $this->actingAs($this->admin())->post(route('admin.coupons.store'), [
            'code' => 'BIGFIXED',
            'type' => 'fixed',
            'value' => 150,
            'active' => true,
        ]);

        $response->assertSessionDoesntHaveErrors('value');
        $this->assertDatabaseHas('coupons', ['code' => 'BIGFIXED', 'value' => 150]);
    }

    #[Test]
    public function coupon_code_is_uppercased_on_create(): void
    {
        $this->actingAs($this->admin())->post(route('admin.coupons.store'), [
            'code' => 'save10now',
            'type' => 'percent',
            'value' => 10,
            'active' => true,
        ]);

        $this->assertDatabaseHas('coupons', ['code' => 'SAVE10NOW']);
        $this->assertDatabaseMissing('coupons', ['code' => 'save10now']);
    }

    #[Test]
    public function coupon_code_is_uppercased_on_update(): void
    {
        $coupon = Coupon::factory()->create(['code' => 'OLDCODE']);

        $this->actingAs($this->admin())->put(route('admin.coupons.update', $coupon), [
            'code' => 'newcode',
            'type' => 'percent',
            'value' => 10,
            'active' => true,
        ]);

        $this->assertEquals('NEWCODE', $coupon->fresh()->code);
    }

    #[Test]
    public function percent_coupon_over_100_is_rejected_on_update(): void
    {
        $coupon = Coupon::factory()->create(['type' => 'percent', 'value' => 10]);

        $response = $this->actingAs($this->admin())->put(route('admin.coupons.update', $coupon), [
            'code' => $coupon->code,
            'type' => 'percent',
            'value' => 200,
            'active' => true,
        ]);

        $response->assertSessionHasErrors('value');
        $this->assertEquals(10, $coupon->fresh()->value);
    }
}
