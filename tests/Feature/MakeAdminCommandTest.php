<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MakeAdminCommandTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function first_registered_user_is_a_customer_not_an_admin(): void
    {
        $this->assertSame(0, User::count());

        $this->post(route('register'), [
            'name' => 'First User',
            'email' => 'first@example.com',
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
        ]);

        $this->assertSame('customer', User::where('email', 'first@example.com')->value('role'));
    }

    #[Test]
    public function make_admin_promotes_an_existing_user(): void
    {
        $user = User::factory()->create(['email' => 'owner@example.com', 'role' => 'customer']);

        $this->artisan('app:make-admin', ['email' => 'owner@example.com'])
            ->expectsOutputToContain('is now an admin')
            ->assertExitCode(0);

        $this->assertSame('admin', $user->fresh()->role);
    }

    #[Test]
    public function make_admin_fails_for_unknown_email(): void
    {
        $this->artisan('app:make-admin', ['email' => 'nobody@example.com'])
            ->assertExitCode(1);

        $this->assertDatabaseMissing('users', ['role' => 'admin']);
    }
}
