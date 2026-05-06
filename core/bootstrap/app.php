<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

/*
|--------------------------------------------------------------------------
| College CMS — Laravel Application Bootstrap
|--------------------------------------------------------------------------
|
| Structure (v3):
|   basePath   = core/         (Laravel app root — PRIVATE)
|   publicPath = public_html/  (web root — one level up from core/)
|
*/

$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web:      __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health:   '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Trust all proxies (required for LiteSpeed + cPanel HTTPS detection)
        $middleware->trustProxies(at: '*');

        // Global middleware stack
        $middleware->prepend(\App\Http\Middleware\CheckInstallation::class);
        $middleware->prepend(\App\Http\Middleware\ForceHttps::class);
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);
        $middleware->append(\App\Http\Middleware\SanitizeInput::class);

        // Named middleware aliases
        $middleware->alias([
            'admin.auth'      => \App\Http\Middleware\AdminAuthenticated::class,
            'role'            => \App\Http\Middleware\RequireRole::class,
            'permission'      => \App\Http\Middleware\RequirePermission::class,
            'login.throttle'  => \App\Http\Middleware\LoginThrottle::class,
            'admin.ratelimit' => \App\Http\Middleware\RateLimitAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();

// core/bootstrap/app.php: dirname(__DIR__) = core/
// Project root = dirname(dirname(__DIR__))

$app->usePublicPath(
    defined('CMS_PUBLIC_ROOT')
        ? CMS_PUBLIC_ROOT
        : dirname(dirname(__DIR__))
);

return $app;
