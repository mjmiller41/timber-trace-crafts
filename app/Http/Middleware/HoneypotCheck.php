<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rejects submissions where the honeypot field is filled (bots fill hidden fields).
 * Apply to any public-facing POST form: contact, login, register, checkout.
 */
class HoneypotCheck
{
    public function handle(Request $request, Closure $next): Response
    {
        // Bots fill every field; real users leave this hidden field blank
        if ($request->filled('_honey')) {
            return back()->with('status', 'Your message has been sent.');
        }

        return $next($request);
    }
}
