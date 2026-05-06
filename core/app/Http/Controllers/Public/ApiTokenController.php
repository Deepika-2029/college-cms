<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\TablesRegistry;
use App\Services\ApiTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * ApiTokenController — Token Vending Machine
 *
 * Public endpoint that issues short-lived HMAC tokens so that
 * frontend static pages never need to embed permanent API keys.
 *
 * Flow:
 *   1. Static page JS calls  GET /api/token/{table}
 *   2. Server checks: is this table registered and public?
 *   3. Server issues a 15-min HMAC token scoped to that table + origin
 *   4. JS uses the token for data requests (expires automatically)
 */
class ApiTokenController extends Controller
{
    public function __construct(private ApiTokenService $tokens) {}

    public function issue(Request $request, string $table): JsonResponse
    {
        // ── Rate limit: 30 token requests/min per IP ─────────────────────────
        $ip      = $request->ip();
        $limiter = app(\Illuminate\Cache\RateLimiter::class);
        $rlKey   = 'token-vend|' . md5($ip);

        if ($limiter->tooManyAttempts($rlKey, 30)) {
            $retryAfter = $limiter->availableIn($rlKey);
            return response()->json([
                'error'       => 'Too many token requests. Slow down.',
                'retry_after' => $retryAfter,
            ], 429)->header('Retry-After', $retryAfter);
        }
        $limiter->hit($rlKey, 60);

        // ── Sanitize table name ───────────────────────────────────────────────
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]{0,63}$/', $table)) {
            return response()->json(['error' => 'Invalid table name.'], 400);
        }
        $table = strtolower($table);

        // ── Check table is registered (only managed tables are accessible) ───
        if (!TablesRegistry::where('table_name', $table)->exists()) {
            // Generic error — don't reveal table existence to probers
            return response()->json(['error' => 'Access denied.'], 403);
        }

        // ── Determine origin & slug from Referer ──────────────────────────────
        $originHeader  = $request->header('Origin');
        $refererHeader = $request->header('Referer');
        
        $origin = strtolower(
            $originHeader
                ? parse_url($originHeader, PHP_URL_HOST)
                : $request->getHost()
        );

        // Extract the page slug from the referer (e.g., https://domain.com/notices -> notices)
        $refererPath = $refererHeader ? parse_url($refererHeader, PHP_URL_PATH) : '';
        $slug = trim($refererPath, '/');

        // Check if this specific page is authorized to access this table
        if (!$this->tokens->pageCanAccessTable($slug, $table)) {
            return response()->json(['error' => 'Access denied. This page is not linked to this table.'], 403);
        }

        // ── Issue token ───────────────────────────────────────────────────────
        $result = $this->tokens->issue($table, $origin);

        return response()->json($result)
            ->header('Cache-Control', 'no-store, private')
            ->header('X-Content-Type-Options', 'nosniff')
            ->header('Access-Control-Allow-Origin', $request->getSchemeAndHttpHost())
            ->header('Access-Control-Allow-Methods', 'GET');
    }
}
