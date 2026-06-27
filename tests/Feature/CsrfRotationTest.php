<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CsrfRotationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function csrf_token_is_rotated_when_user_role_is_elevated(): void
    {
        $user = User::factory()->create(['role' => 'customer']);
        $this->actingAs($user);

        // Prime session — middleware stamps auth.role = customer, no rotation yet
        $this->get(route('account.dashboard'));
        $tokenBefore = session()->token();

        // Simulate an admin elevating this user in the DB
        $user->update(['role' => 'admin']);

        // Next request: middleware detects role drift and rotates token
        $this->get(route('account.dashboard'));
        $tokenAfter = session()->token();

        $this->assertNotEquals($tokenBefore, $tokenAfter, 'CSRF token must rotate on role escalation');
    }

    #[Test]
    public function csrf_token_is_not_rotated_when_role_is_unchanged(): void
    {
        $user = User::factory()->create(['role' => 'customer']);
        $this->actingAs($user);

        // Prime session
        $this->get(route('account.dashboard'));
        $tokenBefore = session()->token();

        // Second request with no role change
        $this->get(route('account.dashboard'));
        $tokenAfter = session()->token();

        $this->assertEquals($tokenBefore, $tokenAfter, 'CSRF token must not rotate when role is stable');
    }
}
