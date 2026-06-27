<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function login_is_rate_limited_after_five_attempts(): void
    {
        User::factory()->create(['email' => 'user@example.com', 'password' => Hash::make('Password1!')]);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => 'user@example.com', 'password' => 'wrong']);
        }

        $response = $this->post('/login', ['email' => 'user@example.com', 'password' => 'wrong']);

        $response->assertStatus(429);
    }

    #[Test]
    public function sole_remaining_customer_does_not_become_admin_on_login(): void
    {
        // Bug scenario: admin account was deleted, leaving only one customer.
        // Without the fix, that customer would be silently elevated to admin on next login.
        $customer = User::factory()->create([
            'role' => 'customer',
            'password' => Hash::make('Password1!'),
        ]);

        $this->post('/login', ['email' => $customer->email, 'password' => 'Password1!']);

        $this->assertEquals('customer', $customer->fresh()->role);
    }
}
