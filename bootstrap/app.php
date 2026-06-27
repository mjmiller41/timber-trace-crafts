<?php

use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\HoneypotCheck;
use App\Http\Middleware\RotateCsrfOnRoleEscalation;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Wildcard only in local dev (ngrok). Production trusts only loopback — the real
        // web server (nginx/Apache) is the only proxy and runs on the same host.
        // Use env() directly — app()->environment() is not yet bound at this stage.
        $middleware->trustProxies(at: env('APP_ENV') === 'local' ? '*' : ['127.0.0.1', '::1']);
        $middleware->append(SecurityHeaders::class);
        $middleware->alias([
            'admin' => AdminMiddleware::class,
            'honeypot' => HoneypotCheck::class,
        ]);
        $middleware->web(append: [RotateCsrfOnRoleEscalation::class]);
        // Etsy calls webhooks directly; no browser session or CSRF token
        $middleware->validateCsrfTokens(except: ['webhooks/etsy']);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
