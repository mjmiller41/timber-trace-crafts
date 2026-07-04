<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\TwoFactorAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Second step of login for accounts with confirmed 2FA. The user is not yet
 * authenticated here — their id is held in the session by AuthController@login
 * and they are only logged in once a valid TOTP or recovery code is supplied.
 */
class TwoFactorChallengeController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has('login.2fa.id')) {
            return redirect()->route('login');
        }

        return view('auth.two-factor-challenge');
    }

    public function store(Request $request, TwoFactorAuthService $totp): RedirectResponse
    {
        $userId = $request->session()->get('login.2fa.id');

        if (! $userId) {
            return redirect()->route('login');
        }

        $user = User::find($userId);

        if (! $user || ! $user->hasTwoFactorEnabled()) {
            $request->session()->forget(['login.2fa.id', 'login.2fa.remember']);

            return redirect()->route('login');
        }

        $request->validate([
            'code' => ['nullable', 'string'],
            'recovery_code' => ['nullable', 'string'],
        ]);

        $passed = false;

        if ($code = $request->input('code')) {
            $passed = $totp->verify($user->two_factor_secret, $code);
        } elseif ($recovery = $request->input('recovery_code')) {
            $passed = $user->useRecoveryCode($recovery);
        }

        if (! $passed) {
            return back()->withErrors([
                'code' => 'The provided two-factor code was invalid.',
            ]);
        }

        $remember = (bool) $request->session()->pull('login.2fa.remember', false);
        $request->session()->forget('login.2fa.id');

        Auth::loginUsingId($user->id, $remember);
        $request->session()->regenerate();
        $request->session()->put('auth.role', $user->role);
        $request->session()->put('auth.2fa_passed', true);

        return redirect()->intended(route('account.dashboard'));
    }
}
