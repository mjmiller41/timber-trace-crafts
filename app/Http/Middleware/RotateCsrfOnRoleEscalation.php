<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RotateCsrfOnRoleEscalation
{
    public function handle(Request $request, Closure $next): mixed
    {
        if ($user = $request->user()) {
            $sessionRole = $request->session()->get('auth.role');

            if ($sessionRole !== null && $sessionRole !== $user->role) {
                $request->session()->regenerateToken();
            }

            $request->session()->put('auth.role', $user->role);
        }

        return $next($request);
    }
}
