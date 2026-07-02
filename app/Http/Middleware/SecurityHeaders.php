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
        $response->headers->set('Content-Security-Policy-Report-Only', $this->buildCsp());
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

    /**
     * Report-only for now — flips to enforcing once a reporting period
     * confirms no legitimate asset/script source is missed.
     */
    private function buildCsp(): string
    {
        $r2Host = parse_url((string) config('filesystems.disks.r2.url'), PHP_URL_HOST);

        $imgSrc = array_filter(["'self'", 'data:', $r2Host ? "https://{$r2Host}" : null]);

        $directives = [
            "default-src 'self'",
            "script-src 'self' https://js.stripe.com",
            "frame-src 'self' https://js.stripe.com",
            "connect-src 'self' https://api.stripe.com",
            'img-src '.implode(' ', $imgSrc),
            "style-src 'self' 'unsafe-inline'",
            "font-src 'self'",
            "object-src 'none'",
            "base-uri 'self'",
        ];

        return implode('; ', $directives);
    }
}
