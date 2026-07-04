<?php

namespace App\Http\Controllers;

use App\Services\TwoFactorAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

/**
 * Self-service TOTP enrollment / disable for the authenticated account. Lives
 * under /account/security. The pending (unconfirmed) secret is held in the
 * session until the user proves possession with a valid code, so an abandoned
 * enrollment never leaves a half-configured secret in the database.
 */
class TwoFactorSettingsController extends Controller
{
    private const PENDING_SECRET_KEY = 'two_factor.pending_secret';

    public function show(Request $request, TwoFactorAuthService $totp): View
    {
        $user = $request->user();
        $pendingSecret = $request->session()->get(self::PENDING_SECRET_KEY);

        $setup = null;
        if (! $user->hasTwoFactorEnabled() && $pendingSecret) {
            $setup = [
                'secret' => $totp->formatSecretForDisplay($pendingSecret),
                'uri' => $totp->otpauthUri(
                    $pendingSecret,
                    $user->email,
                    config('app.name', 'Timber Trace Crafts'),
                ),
            ];
        }

        return view('account.security', [
            'setup' => $setup,
            // Recovery codes are flashed once, immediately after confirmation.
            'recoveryCodes' => $request->session()->get('two_factor.recovery_codes'),
        ]);
    }

    /**
     * Begin enrollment: generate a fresh secret and stash it in the session,
     * then show the setup screen where the user scans it and enters a code.
     */
    public function enable(Request $request, TwoFactorAuthService $totp): RedirectResponse
    {
        if ($request->user()->hasTwoFactorEnabled()) {
            return redirect()->route('account.security');
        }

        $request->session()->put(self::PENDING_SECRET_KEY, $totp->generateSecret());

        return redirect()->route('account.security');
    }

    /**
     * Confirm enrollment with a valid TOTP code, then persist the secret and
     * reveal one-time recovery codes.
     */
    public function confirm(Request $request, TwoFactorAuthService $totp): RedirectResponse
    {
        $user = $request->user();

        if ($user->hasTwoFactorEnabled()) {
            return redirect()->route('account.security');
        }

        $pendingSecret = $request->session()->get(self::PENDING_SECRET_KEY);

        if (! $pendingSecret) {
            return redirect()->route('account.security')
                ->withErrors(['code' => 'Your setup session expired. Please start again.']);
        }

        $request->validate(['code' => ['required', 'string']]);

        if (! $totp->verify($pendingSecret, $request->input('code'))) {
            return back()->withErrors(['code' => 'That code was incorrect. Check your app and try again.']);
        }

        $recoveryCodes = $totp->generateRecoveryCodes();

        $user->two_factor_secret = $pendingSecret;
        $user->two_factor_recovery_codes = $recoveryCodes;
        $user->two_factor_confirmed_at = now();
        $user->save();

        $request->session()->forget(self::PENDING_SECRET_KEY);
        // The current session just proved possession of a code, so treat it as
        // having passed the 2FA gate.
        $request->session()->put('auth.2fa_passed', true);

        return redirect()->route('account.security')
            ->with('success', 'Two-factor authentication is now enabled. Save your recovery codes.')
            ->with('two_factor.recovery_codes', $recoveryCodes);
    }

    /**
     * Disable 2FA. Requires re-entering the account password to prevent a
     * hijacked session from silently removing the protection.
     */
    public function disable(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (! $user->hasTwoFactorEnabled()) {
            return redirect()->route('account.security');
        }

        $request->validate(['password' => ['required', 'string']]);

        if (! Hash::check($request->input('password'), $user->password)) {
            return back()->withErrors(['password' => 'Your password was incorrect.']);
        }

        $user->two_factor_secret = null;
        $user->two_factor_recovery_codes = null;
        $user->two_factor_confirmed_at = null;
        $user->save();

        $request->session()->forget(['auth.2fa_passed', self::PENDING_SECRET_KEY]);

        return redirect()->route('account.security')
            ->with('success', 'Two-factor authentication has been disabled.');
    }
}
