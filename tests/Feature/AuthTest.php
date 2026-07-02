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

    #[Test]
    public function registration_regenerates_the_session_id(): void
    {
        $this->get('/register');
        $originalSessionId = session()->getId();

        $this->post('/register', [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
        ]);

        $this->assertNotEquals($originalSessionId, session()->getId());
    }

    #[Test]
    public function register_is_rate_limited_after_five_attempts(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/register', ['name' => 'Jane', 'email' => "jane{$i}@example.com", 'password' => 'short', 'password_confirmation' => 'short']);
        }

        $response = $this->post('/register', ['name' => 'Jane', 'email' => 'final@example.com', 'password' => 'short', 'password_confirmation' => 'short']);

        $response->assertStatus(429);
    }

    #[Test]
    public function forgot_password_is_rate_limited_after_six_attempts(): void
    {
        for ($i = 0; $i < 6; $i++) {
            $this->post('/forgot-password', ['email' => 'nobody@example.com']);
        }

        $response = $this->post('/forgot-password', ['email' => 'nobody@example.com']);

        $response->assertStatus(429);
    }

    #[Test]
    public function reset_password_is_rate_limited_after_six_attempts(): void
    {
        for ($i = 0; $i < 6; $i++) {
            $this->post('/reset-password', ['token' => 'bad', 'email' => 'nobody@example.com', 'password' => 'Password1!', 'password_confirmation' => 'Password1!']);
        }

        $response = $this->post('/reset-password', ['token' => 'bad', 'email' => 'nobody@example.com', 'password' => 'Password1!', 'password_confirmation' => 'Password1!']);

        $response->assertStatus(429);
    }
}
