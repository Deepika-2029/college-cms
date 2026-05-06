<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * ApiCacheService
 * ───────────────
 * Writes JSON snapshots to data/api-cache/{table}/page{N}.json
 * so ApiController can serve from file instead of hitting MySQL on every request.
 *
 * Cache invalidation:
 *   - Call invalidate($table) after any CRUD write (insert/update/delete)
 *   - rebuild($table) regenerates all pages from current DB state
 *   - TTL: 10 minutes (600s) — checked via filemtime in ApiController
 */
class ApiCacheService
{
    private string $cacheDir;

    /** Columns that must never appear in public API output */
    private const BLOCKED_COLS = [
        'password', 'token', 'secret', 'api_key',
        'api_secret', 'remember_token', 'key_hash',
    ];

    public function __construct()
    {
        $this->cacheDir = public_path('data/api-cache');
    }

    /**
     * Delete all cached pages for a table.
     * Call after any CRUD write so stale data is never served.
     */
    public function invalidate(string $table): void
    {
        if (!$this->isSafeTableName($table)) return;
        $dir = "{$this->cacheDir}/{$table}";
        if (!is_dir($dir)) return;
        foreach (glob("{$dir}/page*.json") ?: [] as $file) {
            @unlink($file);
        }
    }

    /**
     * Rebuild a specific page for a table from current DB state.
     * Called from ApiController on cache miss to avoid blocking the server with a full rebuild.
     */
    public function rebuildPage(string $table, int $page, int $perPage = 10): void
    {
        if (!$this->isSafeTableName($table)) return;

        $dir = "{$this->cacheDir}/{$table}";
        if (!is_dir($dir)) @mkdir($dir, 0755, true);

        try {
            $total = DB::table($table)->count();
            $pages = max(1, (int) ceil($total / $perPage));

            if ($page > $pages) return;

            $rows = DB::table($table)
                ->orderBy('id')
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
                'total_pages' => $pages,
                'data'        => $rows,
                'cached_at'   => now()->toIso8601String(),
            ];

            file_put_contents(
                "{$dir}/page{$page}.json",
                json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            );
        } catch (\Throwable) {
            // Silence missing table exceptions
        }
    }

    /**
     * Rebuild all pages for a table from current DB state.
     * Only call via queued jobs or scheduled tasks.
     */
    public function rebuild(string $table, int $perPage = 10): void
    {
        if (!$this->isSafeTableName($table)) return;

        $this->invalidate($table);
        
        try {
            $total = DB::table($table)->count();
            $pages = max(1, (int) ceil($total / $perPage));
            for ($p = 1; $p <= min(5, $pages); $p++) { // Fallback, aggressively only sync top 5 immediately
                $this->rebuildPage($table, $p, $perPage);
            }
        } catch (\Throwable) {}
    }

    /**
     * Rebuild caches for all registered tables.
     * Called from Tools > Sync Pages.
     */
    public function rebuildAll(): void
    {
        try {
            $tables = DB::table('tables_registry')->pluck('table_name');
            foreach ($tables as $table) {
                $this->rebuild($table);
            }
        } catch (\Throwable) {}
    }

    /**
     * Read a cached page if fresh (< 10 minutes old).
     * Returns null on cache miss so caller falls through to MySQL.
     */
    public function read(string $table, int $page): ?array
    {
        if (!$this->isSafeTableName($table)) return null;
        $file = "{$this->cacheDir}/{$table}/page{$page}.json";
        if (!file_exists($file)) return null;
        if ((time() - filemtime($file)) > 600) return null; // stale
        $data = json_decode(file_get_contents($file), true);
        return is_array($data) ? $data : null;
    }

    // ── Private ───────────────────────────────────────────────────────────

    private function sanitizeRow(array $row): array
    {
        foreach (self::BLOCKED_COLS as $col) {
            unset($row[$col]);
        }
        foreach ($row as $k => $v) {
            // Unpack single-file JSON arrays ["url"] → "url", [] → null
            if (is_string($v) && str_starts_with($v, '[') && str_ends_with($v, ']')) {
                $decoded = json_decode($v, true);
                if (is_array($decoded)) {
                    // Always return as array — empty → null, otherwise keep the full array
                    // (single-item arrays are NOT collapsed to strings so frontend can
                    //  always treat multi-file fields consistently as arrays)
                    $row[$k] = count($decoded) === 0 ? null : $decoded;
                }
            }
            $val = $row[$k];
            if (is_string($val)) {
                // relative media path → /media/filename
                if (str_starts_with($val, 'media/')) {
                    $row[$k] = '/' . $val;
                    $val = $row[$k];
                }
                // full localhost/dev URL → /media/filename
                if (preg_match('#^https?://[^/]+?/((?:media|media-serve)/[^?#]+)#i', $val, $m)) {
                    $row[$k] = '/' . $m[1];
                }
            } elseif (is_array($val)) {
                foreach ($val as $arrK => $arrV) {
                    if (!is_string($arrV)) continue;
                    if (str_starts_with($arrV, 'media/')) {
                        $row[$k][$arrK] = '/' . $arrV;
                    } elseif (preg_match('#^https?://[^/]+?/((?:media|media-serve)/[^?#]+)#i', $arrV, $m)) {
                        $row[$k][$arrK] = '/' . $m[1];
                    }
                }
            }
        }
        return $row;
    }

    private function isSafeTableName(string $table): bool
    {
        return (bool) preg_match('/^[a-zA-Z_][a-zA-Z0-9_]{0,63}$/', $table);
    }
}
