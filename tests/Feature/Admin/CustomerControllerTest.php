<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CustomerControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function admin_can_view_a_customer_detail_page(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer', 'name' => 'Skylar Will']);

        $response = $this->actingAs($admin)
            ->get(route('admin.customers.show', $customer));

        $response->assertOk();
        $response->assertSee('Skylar Will');
    }

    #[Test]
    public function non_admin_cannot_view_a_customer_detail_page(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $other = User::factory()->create(['role' => 'customer']);

        $response = $this->actingAs($other)
            ->get(route('admin.customers.show', $customer));

        $response->assertForbidden();
    }
}
