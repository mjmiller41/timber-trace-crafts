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
    public function second_user_login_does_not_become_admin(): void
    {
        User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create([
            'role' => 'customer',
            'password' => Hash::make('Password1!'),
        ]);

        $this->post('/login', ['email' => $customer->email, 'password' => 'Password1!']);

        $this->assertEquals('customer', $customer->fresh()->role);
    }
}
