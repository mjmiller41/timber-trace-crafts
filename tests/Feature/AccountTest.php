<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AccountTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function email_change_resets_verification_and_resends_link(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'old@example.com',
            'email_verified_at' => now(),
            'password' => Hash::make('Password1!'),
        ]);

        $this->actingAs($user)->put(route('account.profile.update'), [
            'name' => $user->name,
            'email' => 'new@example.com',
        ]);

        $this->assertNull($user->fresh()->email_verified_at);
        Notification::assertSentTo($user->fresh(), VerifyEmail::class);
    }

    #[Test]
    public function email_unchanged_does_not_reset_verification(): void
    {
        $user = User::factory()->create([
            'email' => 'same@example.com',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)->put(route('account.profile.update'), [
            'name' => 'New Name',
            'email' => 'same@example.com',
        ]);

        $this->assertNotNull($user->fresh()->email_verified_at);
    }
}
