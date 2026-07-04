<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminIdleTimeout
{
    private const SESSION_KEY = 'admin_last_activity';

    /**
     * Log an admin out of the whole session after a configurable window of
     * inactivity. Activity is refreshed on every admin request; once the gap
     * since the last request exceeds the timeout the session is invalidated
     * and the user is bounced to the login screen.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $timeout = (int) config('admin.idle_timeout', 30);

        // A non-positive timeout disables the feature entirely.
        if ($timeout <= 0 || ! Auth::check()) {
            return $next($request);
        }

        $now = now()->getTimestamp();
        $last = $request->session()->get(self::SESSION_KEY);

        if ($last !== null && ($now - (int) $last) > $timeout * 60) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->with('status', 'You were signed out after a period of inactivity.');
        }

        $request->session()->put(self::SESSION_KEY, $now);

        return $next($request);
    }
}
