<?php

use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        // TD-23: routes/api.php must be named explicitly here or it is
        // never loaded (see tests/Feature/Api/RoutingSmokeTest.php).
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // spatie/laravel-permission v8 expects its aliases registered here.
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
        ]);

        // Payment gateways POST server-to-server notifications; they're
        // authenticated by signature / server-side confirmation inside
        // each gateway driver instead of a CSRF token.
        $middleware->validateCsrfTokens(except: ['payments/webhook/*']);

        $middleware->web(append: [SecurityHeaders::class]);

        // Behind a load balancer (Forge / Cloudflare) so request()->isSecure()
        // and client IPs (rate limiting) are correct.
        $middleware->trustProxies(at: env('TRUSTED_PROXIES', '127.0.0.1'));
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
