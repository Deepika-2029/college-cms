<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * ForceHttps — redirects HTTP → HTTPS in production.
 * Skipped on localhost, 0.0.0.0, and 127.x.x.x so Termux `php artisan serve` works.
 */
class ForceHttps
{
    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost();

        // Never redirect on local/dev environments
        $isLocal = in_array($host, ['localhost', '0.0.0.0', '127.0.0.1', '::1'], true)
                || str_starts_with($host, '192.168.')
                || str_starts_with($host, '10.')
                || str_ends_with($host, '.local')
                || str_ends_with($host, '.test')
                || app()->environment('local', 'testing');

        if (! $isLocal && ! $request->isSecure()) {
            return redirect()->secure($request->getRequestUri(), 301);
        }

        return $next($request);
    }
}
