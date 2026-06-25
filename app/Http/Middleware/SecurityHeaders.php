<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->remove('X-Powered-By');
        $response->headers->remove('Server');

        if ($request->secure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        // Only cache routes that are truly public and stateless (no CSRF tokens or session data).
        $publicCacheableRoutes = ['home', 'shop.index', 'shop.product', 'about', 'journal.index', 'journal.show', 'journal.tag', 'journal.feed', 'sitemap'];

        if (! $request->user() && $response->isSuccessful() && in_array($request->route()?->getName(), $publicCacheableRoutes, true)) {
            $response->headers->set('Cache-Control', 'public, s-maxage=60, stale-while-revalidate=300');
            $response->headers->set('Vary', 'Cookie, Accept-Encoding');
        } else {
            $response->headers->set('Cache-Control', 'private, no-store');
        }

        return $response;
    }
}
