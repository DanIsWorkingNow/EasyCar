<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        // FIXED (TD-23, Rate Limit Kit): this app: parameter was never
        // named, so routes/api.php was never actually loaded — every
        // endpoint in the API kit and this rate-limiting kit was
        // unreachable via the intended path. See TSD Section 8.11.1.
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // spatie/laravel-permission v8 no longer auto-registers 'role'/
        // 'permission' route-middleware aliases for you — it expects them
        // registered here (this app's actual middleware entry point; the
        // legacy app/Http/Kernel.php that used to alias these was dead
        // code — nothing bound App\Http\Kernel — and was deleted as part
        // of the Rate Limit Kit's TD-24 cleanup).
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
