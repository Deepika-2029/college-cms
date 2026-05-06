<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * ForceHttps — redirects HTTP → HTTPS in production.
 * Skipped on localhost and when behind a trusted proxy (e.g. Render.com)
 * that terminates SSL and forwards X-Forwarded-Proto: https.
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

        // Check X-Forwarded-Proto for proxy environments like Render.com
        // Render terminates HTTPS at its load balancer and forwards HTTP internally
        $isHttpsViaProxy = $request->header('X-Forwarded-Proto') === 'https';

        if (! $isLocal && ! $isHttpsViaProxy && ! $request->isSecure()) {
            return redirect()->secure($request->getRequestUri(), 301);
        }

        return $next($request);
    }
}
