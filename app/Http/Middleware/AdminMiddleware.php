<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    public function handle($request, Closure $next)
{
    if (auth()->check() && auth()->user()->userLevel == 5) {
        return $next($request);
    }

    abort(403, 'Unauthorized');
}
}
