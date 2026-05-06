<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use App\Models\TablesRegistry;
use App\Services\ApiCacheService;
use App\Services\ApiTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * ApiController — v3.0
 *
 * CHANGED: Checks ApiCacheService before hitting MySQL.
 * All other logic (auth, rate limit, sanitization) unchanged.
 */
class ApiController extends Controller
{
    public function __construct(
        private ApiCacheService  $cache,
        private ApiTokenService  $tokens,
    ) {}

    public function tableData(Request $request, string $table): JsonResponse
    {
        // Rate limit: 60 requests/minute per IP
        $ip      = $request->ip();
        $limiter = app(\Illuminate\Cache\RateLimiter::class);
        $rlKey   = 'public-api|' . md5($ip);

        if ($limiter->tooManyAttempts($rlKey, 60)) {
            $retryAfter = $limiter->availableIn($rlKey);
            return response()->json([
                'error'       => 'Rate limit exceeded. Max 60 requests per minute.',
                'retry_after' => $retryAfter,
            ], 429)->header('Retry-After', $retryAfter);
        }
        $limiter->hit($rlKey, 60);

        // ── Auth: Token (new, preferred) OR API Key (legacy) ────────────────
        $authToken = $request->query('token');
        $apiKeyStr = $request->query('key') ?? $request->header('X-API-Key');

        // Determine origin for both auth paths
        $originHeader  = $request->header('Origin');
        $refererHeader = $request->header('Referer');
        $requestHost   = null;
        if ($originHeader) {
            $requestHost = strtolower(parse_url($originHeader, PHP_URL_HOST));
        } elseif ($refererHeader) {
            $requestHost = strtolower(parse_url($refererHeader, PHP_URL_HOST));
        }
        $appHost = strtolower($request->getHost());

        if ($authToken) {
            // ── Token path ───────────────────────────────────────────────────
            $origin = $requestHost ?: $appHost;
            if (!$this->tokens->verify($authToken, $table, $origin)) {
                $this->recordAuthFailure($ip);
                return response()->json(['error' => 'Invalid or expired token.'], 403);
            }
            // Token is valid — no further key checks needed
        } elseif ($apiKeyStr) {
            // ── Legacy API key path (backward-compat) ─────────────────────────
            // Strict same-domain check for legacy keys
            if (!$requestHost || $requestHost !== $appHost) {
                return response()->json([
                    'error' => 'Access Denied: cross-domain requests are prohibited.',
                ], 403);
            }

            $apiKey = ApiKey::verify($apiKeyStr);
            if (!$apiKey || !$apiKey->isValid()) {
                $this->recordAuthFailure($ip);
                return response()->json(['error' => 'Invalid or expired API key.'], 403);
            }
            if ($apiKey->table_name !== $table) {
                return response()->json(['error' => 'This key does not have access to that table.'], 403);
            }
        } else {
            return response()->json(['error' => 'Authentication required. Use ?token= or X-API-Key header.'], 401);
        }

        if (!TablesRegistry::where('table_name', $table)->exists()) {
            return response()->json(['error' => 'Table not found.'], 404);
        }

        $page = max(1, (int) $request->query('page', 1));
        $requestedLimit = $request->query('limit');

        // If no limit is provided, default to a high number (2000) so all records load automatically
        // Ignoring old API key limits as per user request
        $apiLimit = 2000;
        
        if ($requestedLimit === 'all') {
            $perPage = 2000; // Hard max
        } else {
            $perPage = (int)$requestedLimit > 0 ? min((int)$requestedLimit, 2000) : $apiLimit;
        }

        $hasCustomRules = false; // Ignore old API key custom rules for caching

        $allowedCorsOrigin = $request->getSchemeAndHttpHost();

        // Only use cache if they aren't passing a custom limit
        if (!$hasCustomRules && empty($requestedLimit)) {
            // ── Try file cache first ──────────────────────────────────────────
            $cached = $this->cache->read($table, $page);
            if ($cached !== null) {
                if (isset($apiKey)) $apiKey->recordUsage();
                return response()->json($cached)
                    ->header('X-Cache',                       'HIT')
                    ->header('Cache-Control',                 'no-store, private')
                    ->header('X-Content-Type-Options',        'nosniff')
                    ->header('Access-Control-Allow-Origin',   $allowedCorsOrigin)
                    ->header('Access-Control-Allow-Methods',  'GET')
                    ->header('Access-Control-Allow-Headers',  'X-API-Key, Authorization');
            }
        }

        // ── Cache miss: query MySQL ───────────────────────────────────────
        $queryBuilder = DB::table($table);
        $total = $queryBuilder->count();

        // Apply sort
        $requestedSort = strtolower($request->query('sort', ''));
        
        if (in_array($requestedSort, ['latest', 'oldest'])) {
            $sortRule = $requestedSort;
        } else {
            $sortRule = 'latest'; // Default to latest as per user request
        }

        if ($sortRule === 'latest') {
            $queryBuilder->orderByDesc('id'); // ID is standard primary key
        } elseif ($sortRule === 'oldest') {
            $queryBuilder->orderBy('id');
        } else {
            $queryBuilder->orderByDesc('id');
        }

        $rows = $queryBuilder
            ->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->get()
            ->map(fn($r) => $this->sanitizeRow((array) $r))
            ->toArray();

        $payload = [
            'table'       => $table,
            'page'        => $page,
            'per_page'    => $perPage,
            'total'       => $total,
            'total_pages' => max(1, (int) ceil($total / $perPage)),
            'data'        => $rows,
        ];

        if (isset($apiKey)) {
            $apiKey->recordUsage();
        }

        if (!$hasCustomRules && empty($requestedLimit)) {
            // Rebuild only the missing page asynchronously-safe
            try { $this->cache->rebuildPage($table, $page, $perPage); } catch (\Throwable) {}
        }

        return response()->json($payload)
            ->header('X-Cache',                       'MISS')
            ->header('Cache-Control',                 'no-store, private')
            ->header('X-Content-Type-Options',        'nosniff')
            ->header('Access-Control-Allow-Origin',   $allowedCorsOrigin)
            ->header('Access-Control-Allow-Methods',  'GET')
            ->header('Access-Control-Allow-Headers',  'X-API-Key, Authorization');
    }

    private function sanitizeRow(array $row): array
    {
        foreach (['password','token','secret','api_key','api_secret','remember_token'] as $col) {
            unset($row[$col]);
        }
        foreach ($row as $k => $v) {
            // Unpack single-file JSON arrays stored by the CRUD uploader ["url"] → "url"
            if (is_string($v) && str_starts_with($v, '[') && str_ends_with($v, ']')) {
                $decoded = json_decode($v, true);
                if (is_array($decoded)) {
                    if (count($decoded) === 0)      $row[$k] = null;
                    elseif (count($decoded) === 1)  $row[$k] = $decoded[0];
                    else                            $row[$k] = $decoded;
                }
            }
            // Normalise the value (may have changed above)
            $val = $row[$k];

            if (is_string($val)) {
                // Convert relative media paths → /media/filename
                if (str_starts_with($val, 'media/')) {
                    $row[$k] = '/' . $val;
                    $val = $row[$k];
                }
                // Convert full Laravel dev-server media URLs → /media/filename
                // e.g. http://127.0.0.1:8000/media/file.pdf  →  /media/file.pdf
                //      http://127.0.0.1:8000/media-serve/file.pdf  →  /media/file.pdf
                if (preg_match('~^https?://[^/]+?/((?:media|media-serve)/[^?#]+)~i', $val, $m)) {
                    $row[$k] = '/' . $m[1];
                }
            } elseif (is_array($val)) {
                foreach ($val as $arrK => $arrV) {
                    if (!is_string($arrV)) continue;
                    if (str_starts_with($arrV, 'media/')) {
                        $row[$k][$arrK] = '/' . $arrV;
                    } elseif (preg_match('~^https?://[^/]+?/((?:media|media-serve)/[^?#]+)~i', $arrV, $m)) {
                        $row[$k][$arrK] = '/' . $m[1];
                    }
                }
            }
        }
        return $row;
    }

    public function serveMediaAsset(int $id)
    {
        $media = \App\Models\MediaFile::find($id);

        if (!$media) {
            abort(404, 'Media not found');
        }

        // Cloud assets — redirect directly
        if ($media->is_cloud) {
            return redirect($media->file_path);
        }

        $fp = $media->file_path;

        // Try all possible paths in order:
        // 1. storage/app/public (symlinked as public/storage)
        // 2. public_path directly
        // 3. storage_path('app/public/...')
        $candidates = [
            storage_path('app/public/' . ltrim($fp, '/')),
            public_path(ltrim($fp, '/')),
            storage_path('app/' . ltrim($fp, '/')),
        ];

        $resolvedPath = null;
        foreach ($candidates as $candidate) {
            if (file_exists($candidate)) {
                $resolvedPath = $candidate;
                break;
            }
        }

        if (!$resolvedPath) {
            abort(404, 'Asset file not found on disk');
        }

        $ext  = strtolower(pathinfo($resolvedPath, PATHINFO_EXTENSION));
        $mime = $media->mime_type
            ?: ((new \finfo(FILEINFO_MIME_TYPE))->file($resolvedPath) ?: 'application/octet-stream');

        $headers = [
            'Content-Type'            => $mime,
            'Cache-Control'           => 'public, max-age=86400',
            'X-Content-Type-Options'  => 'nosniff',
        ];

        // SVG hardening: even if sanitizer passes, restrict active content via CSP
        if ($ext === 'svg') {
            $headers['Content-Security-Policy'] = "default-src 'none'; style-src 'unsafe-inline'; img-src data:;";
        }

        return response()->file($resolvedPath, $headers);
    }

    /**
     * Exponential backoff on auth failures.
     * 1st fail → 0.2s delay. 5th fail → 6.4s. 8th+ → 64s max.
     * Counter resets after 10 minutes of no failures.
     */
    private function recordAuthFailure(string $ip): void
    {
        $key   = 'api-auth-fail|' . md5($ip);
        $cache = app(\Illuminate\Cache\Repository::class);
        $count = (int) $cache->get($key, 0) + 1;
        $cache->put($key, $count, 600); // reset after 10 min clean

        $delay = min(64, (int) round(0.1 * pow(2, $count))); // 0.2s, 0.4s, 0.8s … 64s
        usleep($delay * 1_000_000);
    }
}

