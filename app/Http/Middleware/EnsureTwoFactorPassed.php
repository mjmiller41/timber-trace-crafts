<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Defence-in-depth gate for the /admin surface: an authenticated user with
 * confirmed 2FA cannot reach admin pages unless this session has passed the
 * two-factor challenge (login) or just confirmed a code (enrollment). The
 * normal login flow already enforces this, but the gate protects against any
 * other code path that authenticates a user without going through it.
 */
class EnsureTwoFactorPassed
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->hasTwoFactorEnabled() && ! $request->session()->get('auth.2fa_passed')) {
            $request->session()->put('login.2fa.id', $user->id);
            auth()->logout();

            return redirect()->route('two-factor.login');
        }

        return $next($request);
    }
}
