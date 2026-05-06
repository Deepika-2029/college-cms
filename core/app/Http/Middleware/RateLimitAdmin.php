<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * RateLimitAdmin — throttles all admin write requests.
 *
 * Prevents:
 *  - Automated form submission bots
 *  - Mass delete/create loops if account is compromised
 *
 * Limits: 120 writes per minute per user ID.
 * (Normal human use never approaches this; a bot would.)
 */
class RateLimitAdmin
{
    // Global write limit: 120/min per user (protects against automated mass operations)
    private const MAX_WRITES    = 120;
    private const DECAY_SECONDS = 60;

    // Stricter limits for high-risk route groups (per user per minute)
    private const SENSITIVE_ROUTES = [
        'admin.users.*'    => 20,  // user management
        'admin.database.*' => 15,  // DB schema changes
        'admin.plugins.*'  => 20,  // plugin file writes
        'admin.tools.*'    => 10,  // system tools
        'admin.widgets.*'  => 30,
        'admin.settings.*' => 15,
    ];

    public function __construct(private RateLimiter $limiter) {}

    public function handle(Request $request, Closure $next): Response
    {
        // Only throttle state-changing methods
        if (! in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return $next($request);
        }

        // Skip logout (don't lock someone out of logging out)
        if ($request->routeIs('admin.logout')) {
            return $next($request);
        }

        $userId = auth()->id() ?? $request->ip();

        // Check sensitive route limits first
        foreach (self::SENSITIVE_ROUTES as $pattern => $limit) {
            if ($request->routeIs($pattern)) {
                $sensitiveKey = 'admin-sensitive|' . $pattern . '|' . $userId;
                if ($this->limiter->tooManyAttempts($sensitiveKey, $limit)) {
                    $msg = "Too many requests for this action. Limit: {$limit}/minute.";
                    if ($request->expectsJson()) {
                        return response()->json(['error' => $msg], 429);
                    }
                    return back()->with('error', $msg);
                }
                $this->limiter->hit($sensitiveKey, self::DECAY_SECONDS);
                break;
            }
        }

        // Global write limit
        $key = 'admin-writes|' . $userId;
        if ($this->limiter->tooManyAttempts($key, self::MAX_WRITES)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Too many requests. Please slow down.',
                ], 429);
            }
            return back()->with('error', 'Too many requests. Please wait a moment before trying again.');
        }
        $this->limiter->hit($key, self::DECAY_SECONDS);

        return $next($request);
    }
}
