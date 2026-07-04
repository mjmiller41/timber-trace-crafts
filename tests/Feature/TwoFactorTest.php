<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\TwoFactorAuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TwoFactorTest extends TestCase
{
    use RefreshDatabase;

    private TwoFactorAuthService $totp;

    protected function setUp(): void
    {
        parent::setUp();
        $this->totp = app(TwoFactorAuthService::class);
    }

    /** Compute the current valid 6-digit code for a secret. */
    private function currentCode(string $secret): string
    {
        $method = new \ReflectionMethod($this->totp, 'codeForCounter');
        $method->setAccessible(true);

        return $method->invoke($this->totp, $secret, (int) floor(time() / 30));
    }

    /** Create a user with confirmed 2FA and a known secret + recovery code. */
    private function userWithTwoFactor(array $attributes = [], array $recoveryCodes = ['AAAAA-BBBBB']): User
    {
        $secret = $this->totp->generateSecret();

        $user = User::factory()->create(array_merge([
            'password' => Hash::make('Password1!'),
        ], $attributes));

        $user->two_factor_secret = $secret;
        $user->two_factor_recovery_codes = $recoveryCodes;
        $user->two_factor_confirmed_at = now();
        $user->save();

        return $user;
    }

    // ---- Enrollment -------------------------------------------------------

    #[Test]
    public function a_user_can_enroll_and_confirm_two_factor(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('account.security.2fa.enable'))
            ->assertRedirect(route('account.security'));

        $secret = session('two_factor.pending_secret');
        $this->assertNotNull($secret);

        $response = $this->actingAs($user)->post(route('account.security.2fa.confirm'), [
            'code' => $this->currentCode($secret),
        ]);

        $response->assertRedirect(route('account.security'));
        $user->refresh();
        $this->assertTrue($user->hasTwoFactorEnabled());
        $this->assertNotEmpty($user->two_factor_recovery_codes);
        $this->assertNull(session('two_factor.pending_secret'));
    }

    #[Test]
    public function confirmation_rejects_an_invalid_code(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('account.security.2fa.enable'));

        $this->actingAs($user)->post(route('account.security.2fa.confirm'), [
            'code' => '000000',
        ])->assertSessionHasErrors('code');

        $this->assertFalse($user->refresh()->hasTwoFactorEnabled());
    }

    // ---- Login challenge --------------------------------------------------

    #[Test]
    public function login_holds_a_two_factor_user_at_the_challenge(): void
    {
        $user = $this->userWithTwoFactor();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'Password1!',
        ]);

        $response->assertRedirect(route('two-factor.login'));
        $this->assertGuest();
        $this->assertEquals($user->id, session('login.2fa.id'));
    }

    #[Test]
    public function a_valid_code_completes_the_challenge(): void
    {
        $user = $this->userWithTwoFactor();

        $this->post('/login', ['email' => $user->email, 'password' => 'Password1!']);

        $response = $this->post(route('two-factor.login.store'), [
            'code' => $this->currentCode($user->two_factor_secret),
        ]);

        $response->assertRedirect(route('account.dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    #[Test]
    public function the_challenge_rejects_an_invalid_code(): void
    {
        $user = $this->userWithTwoFactor();

        $this->post('/login', ['email' => $user->email, 'password' => 'Password1!']);

        $this->post(route('two-factor.login.store'), ['code' => '000000'])
            ->assertSessionHasErrors('code');

        $this->assertGuest();
    }

    #[Test]
    public function a_recovery_code_is_single_use(): void
    {
        $user = $this->userWithTwoFactor(recoveryCodes: ['AAAAA-BBBBB', 'CCCCC-DDDDD']);

        // First use logs in.
        $this->post('/login', ['email' => $user->email, 'password' => 'Password1!']);
        $this->post(route('two-factor.login.store'), ['recovery_code' => 'AAAAA-BBBBB'])
            ->assertRedirect(route('account.dashboard'));
        $this->assertAuthenticatedAs($user);

        $this->assertNotContains('AAAAA-BBBBB', $user->refresh()->two_factor_recovery_codes);

        // Log out and try the same code again — must fail.
        $this->post('/logout');
        $this->post('/login', ['email' => $user->email, 'password' => 'Password1!']);
        $this->post(route('two-factor.login.store'), ['recovery_code' => 'AAAAA-BBBBB'])
            ->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    // ---- Disable ----------------------------------------------------------

    #[Test]
    public function disabling_requires_the_correct_password(): void
    {
        $user = $this->userWithTwoFactor();

        $this->actingAs($user)->withSession(['auth.2fa_passed' => true])
            ->delete(route('account.security.2fa.disable'), ['password' => 'wrong'])
            ->assertSessionHasErrors('password');
        $this->assertTrue($user->refresh()->hasTwoFactorEnabled());

        $this->actingAs($user)->withSession(['auth.2fa_passed' => true])
            ->delete(route('account.security.2fa.disable'), ['password' => 'Password1!'])
            ->assertRedirect(route('account.security'));
        $this->assertFalse($user->refresh()->hasTwoFactorEnabled());
    }

    // ---- Admin gate & non-2FA unaffected ----------------------------------

    #[Test]
    public function a_non_two_factor_admin_reaches_admin_normally(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'password' => Hash::make('Password1!')]);

        // Full login (no challenge) and admin dashboard reachable.
        $this->post('/login', ['email' => $admin->email, 'password' => 'Password1!'])
            ->assertRedirect(route('account.dashboard'));
        $this->assertAuthenticatedAs($admin);

        $this->get(route('admin.dashboard'))->assertOk();
    }

    #[Test]
    public function admin_gate_blocks_a_two_factor_admin_that_has_not_passed_the_challenge(): void
    {
        $admin = $this->userWithTwoFactor(['role' => 'admin']);

        // actingAs skips the login flow, so the 2fa_passed flag is absent.
        $this->actingAs($admin)->get(route('admin.dashboard'))
            ->assertRedirect(route('two-factor.login'));

        // With the flag set (as the real challenge/enrollment would), access is allowed.
        $this->actingAs($admin)->withSession(['auth.2fa_passed' => true])
            ->get(route('admin.dashboard'))->assertOk();
    }
}
