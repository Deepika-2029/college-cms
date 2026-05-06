<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\HtmlPage;
use App\Models\MediaFile;
use App\Services\AuditLogger;
use App\Services\ImageOptimizerService;
use App\Services\PluginScannerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ToolsController extends Controller
{
    public function __construct(
        private AuditLogger $audit,
        private ImageOptimizerService $imageOptimizer,
    ) {}

    // ── Main tools page ───────────────────────────────────────────────────

    public function index()
    {
        $stats = $this->gatherStats();
        $logs  = $this->readRecentLogs(100);

        return view('admin.tools.index', compact('stats', 'logs'));
    }

    // ── Cache clear ───────────────────────────────────────────────────────

    public function clearCache()
    {
        try {
            Cache::flush();
            $this->audit->log('tools.cache_cleared', 'system');
            return back()->with('success', 'Application cache cleared successfully.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Cache clear failed: ' . $e->getMessage());
        }
    }

    // ── Clear Laravel log file ────────────────────────────────────────────

    public function clearLog()
    {
        $logFile = storage_path('logs/laravel.log');
        if (file_exists($logFile)) {
            file_put_contents($logFile, '');
        }
        $this->audit->log('tools.log_cleared', 'system');
        return back()->with('success', 'Log file cleared.');
    }



    public function generateSitemap(Request $request)
    {
        try {
            $count = \App\Models\HtmlPage::updateSitemap();
            $this->audit->log('tools.sitemap_generated', 'system', null, $count . ' URLs');
            return back()->with('success', 'Sitemap generated at /sitemap.xml with ' . $count . ' URLs.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Sitemap generation failed: ' . $e->getMessage());
        }
    }

    // ── Delete orphaned media files ───────────────────────────────────────

    public function cleanOrphanedMedia()
    {
        $deleted = 0;
        $errors  = [];

        // Collect all DB-tracked local file paths (exclude cloud URLs)
        $dbPaths = MediaFile::where('file_path', 'not like', 'http%')
            ->pluck('file_path')
            ->map(fn ($p) => public_path(ltrim($p, '/')))
            ->toArray();

        // Scan both possible local media directories
        $scanDirs = array_filter([
            public_path('media'),
            storage_path('app/public'),
        ], 'is_dir');

        foreach ($scanDirs as $dir) {
            try {
                foreach (File::files($dir) as $file) {
                    if (! in_array($file->getPathname(), $dbPaths, true)) {
                        File::delete($file->getPathname());
                        $deleted++;
                    }
                }
            } catch (\Throwable $e) {
                $errors[] = $e->getMessage();
            }
        }

        $this->audit->log('tools.orphans_cleaned', 'system', null, "{$deleted} files");

        if ($errors) {
            return back()->with('warning', "Cleaned {$deleted} file(s). Some errors: " . implode('; ', $errors));
        }

        return back()->with('success', "Removed {$deleted} orphaned file(s) from disk.");
    }

    // ── Optimize Media ───────────────────────────────────────

    public function optimizeMedia(Request $request)
    {
        $media = MediaFile::where('driver', 'local')
            ->where(function ($q) {
                $q->whereNull('variants')->orWhere('variants', 'LIKE', '[]');
            })
            ->whereIn('mime_type', ['image/jpeg', 'image/png', 'image/webp'])
            ->limit(10)
            ->get();
            
        if ($media->isEmpty()) {
            return response()->json(['processed' => 0, 'remaining' => 0, 'failed' => 0]);
        }

        $processed = 0;
        $failed = 0;

        foreach ($media as $item) {
            $localPath = public_path(ltrim($item->file_path, '/'));
            if (!file_exists($localPath)) {
                $failed++;
                // Skip it so it doesn't block the queue forever, mark it as failed by setting empty array
                $item->update(['variants' => []]);
                continue;
            }

            $basename = pathinfo($item->file_path, PATHINFO_FILENAME);
            $basename = preg_replace('/^(thumb|medium|large|original)_/', '', $basename);
            $ext = pathinfo($item->file_path, PATHINFO_EXTENSION);

            $variants = $this->imageOptimizer->process($localPath, $basename, $ext);

            if (empty($variants)) {
                $failed++;
                // Fallback mark as processed so it's not infinite loop
                $item->update(['variants' => []]);
                continue;
            }

            $item->update([
                'variants' => $variants,
                'file_path' => $variants['original'] ?? $item->file_path,
                'size' => filesize(public_path(ltrim($variants['original'] ?? $item->file_path, '/'))),
            ]);

            $processed++;
        }

        $remaining = MediaFile::where('driver', 'local')
            ->whereNull('variants')
            ->whereIn('mime_type', ['image/jpeg', 'image/png', 'image/webp'])
            ->count();

        if ($processed > 0) {
            $this->audit->log('tools.media_optimized', 'system', null, "Batch of {$processed} optimization");
        }

        return response()->json(['processed' => $processed, 'remaining' => $remaining, 'failed' => $failed]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    private function gatherStats(): array
    {
        $mediaDir  = public_path('media');
        $mediaSize = 0;
        if (is_dir($mediaDir)) {
            foreach (File::allFiles($mediaDir) as $f) {
                $mediaSize += $f->getSize();
            }
        }

        $logFile = storage_path('logs/laravel.log');
        $logSize = file_exists($logFile) ? filesize($logFile) : 0;

        // DB size (SQLite: file size; MySQL: schema query)
        $dbSize = 0;
        try {
            $connection = config('database.default');
            if ($connection === 'sqlite') {
                $dbPath = config('database.connections.sqlite.database');
                $dbSize = file_exists($dbPath) ? filesize($dbPath) : 0;
            } else {
                $result = DB::select('SELECT SUM(data_length + index_length) AS size FROM information_schema.tables WHERE table_schema = DATABASE()');
                $dbSize = $result[0]->size ?? 0;
            }
        } catch (\Throwable) {}

        $pagesDir  = public_path('data/pages');
        $pageCount = is_dir($pagesDir) ? count(glob("{$pagesDir}/*.json")) : 0;

        return [
            'php_version'   => PHP_VERSION,
            'laravel_ver'   => app()->version(),
            'db_driver'     => config('database.default'),
            'db_size'       => $this->humanSize($dbSize),
            'cache_driver'  => config('cache.default'),
            'media_count'   => MediaFile::count(),
            'unoptimized'   => MediaFile::where('driver', 'local')->whereNull('variants')->whereIn('mime_type', ['image/jpeg', 'image/png', 'image/webp'])->count(),
            'media_size'    => $this->humanSize($mediaSize),
            'page_count'    => $pageCount,
            'audit_count'   => AuditLog::count(),
            'log_size'      => $this->humanSize($logSize),
            'log_file'      => $logFile,
            'storage_ok'    => is_writable(storage_path('logs')),
            'media_dir_ok'  => is_writable(public_path('media')),
            'sitemap_exists'=> file_exists(public_path('sitemap.xml')),
            'sitemap_url'   => url('sitemap.xml'),
        ];
    }

    private function readRecentLogs(int $lines = 100): array
    {
        $logFile = storage_path('logs/laravel.log');
        if (! file_exists($logFile) || filesize($logFile) === 0) return [];

        // Read last N lines efficiently
        $fp    = fopen($logFile, 'rb');
        $size  = filesize($logFile);
        $chunk = min($size, 32768); // read 32KB max
        fseek($fp, -$chunk, SEEK_END);
        $content = fread($fp, $chunk);
        fclose($fp);

        $allLines  = explode("\n", $content);
        $recent    = array_slice(array_filter($allLines), -$lines);
        $entries   = [];
        $current   = '';

        foreach ($recent as $line) {
            if (preg_match('/^\[\d{4}-\d{2}-\d{2}/', $line)) {
                if ($current) $entries[] = $current;
                $current = $line;
            } else {
                $current .= "\n" . $line;
            }
        }
        if ($current) $entries[] = $current;

        return array_reverse(array_slice($entries, -50));
    }

    private function humanSize(int $bytes): string
    {
        if ($bytes === 0) return '0 B';
        $units = ['B', 'KB', 'MB', 'GB'];
        $i     = 0;
        while ($bytes >= 1024 && $i < 3) { $bytes /= 1024; $i++; }
        return round($bytes, 1) . ' ' . $units[$i];
    }
    // ── Export DB as SQL dump ────────────────────────────────────────────
    public function exportDb()
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403, 'Only Super Admins can export the database.');
        try {
            $db     = config('database.connections.mysql.database');
            $user   = config('database.connections.mysql.username');
            $pass   = config('database.connections.mysql.password');
            $host   = config('database.connections.mysql.host', 'localhost');
            $tables = \Illuminate\Support\Facades\DB::select('SHOW TABLES');
            $col    = 'Tables_in_' . $db;

            $sql  = "-- College CMS Database Export\n";
            $sql .= "-- Generated: " . now()->toISOString() . "\n";
            $sql .= "-- Database:  {$db}\n\n";
            $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

            foreach ($tables as $tableObj) {
                $table = $tableObj->$col;

                // Table structure
                $create = \Illuminate\Support\Facades\DB::select("SHOW CREATE TABLE `{$table}`");
                $sql   .= "-- Table: {$table}\n";
                $sql   .= "DROP TABLE IF EXISTS `{$table}`;\n";
                $sql   .= $create[0]->{"Create Table"} . ";\n\n";

                // Table data
                $rows = \Illuminate\Support\Facades\DB::table($table)->get();
                if ($rows->isEmpty()) continue;

                $cols = array_keys((array) $rows->first());
                $colList = implode(', ', array_map(fn($c) => "`{$c}`", $cols));

                // Scrub sensitive columns — never export passwords, tokens, or key hashes
                $sensitiveColumns = ['password', 'remember_token', 'key_hash', 'secret', 'token', 'api_secret'];

                foreach ($rows->chunk(100) as $chunk) {
                    $values = [];
                    foreach ($chunk as $row) {
                        $rowArr = (array) $row;
                        // Replace sensitive column values with a placeholder
                        foreach ($sensitiveColumns as $s) {
                            if (array_key_exists($s, $rowArr) && $rowArr[$s] !== null) {
                                $rowArr[$s] = '[REDACTED]';
                            }
                        }
                        $vals = array_map(function ($v) {
                            if ($v === null) return 'NULL';
                            if (is_int($v) || is_float($v)) return $v;
                            return "'" . addslashes((string)$v) . "'";
                        }, $rowArr);
                        $values[] = '(' . implode(', ', $vals) . ')';
                    }
                    $sql .= "INSERT INTO `{$table}` ({$colList}) VALUES\n" . implode(",\n", $values) . ";\n";
                }
                $sql .= "\n";
            }

            $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";

            $filename = 'cms_backup_' . now()->format('Ymd_His') . '.sql';

            $this->audit->log('tools.db_exported', 'system', null, $filename);

            return response($sql, 200, [
                'Content-Type'        => 'application/sql',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]);
        } catch (\Throwable $e) {
            return back()->with('error', 'DB export failed: ' . $e->getMessage());
        }
    }

    // ── Sync (Republish) All Pages from Database ────────────────────────────

    /**
     * POST /tools/sync-from-db
     * Re-publishes ALL pages stored in the database back to static HTML files.
     * This is the recovery tool after an FTP wipe or accidental file deletion.
     * Returns JSON progress for the AJAX progress bar on the Tools page.
     */
    public function syncFromDb(Request $request)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403, 'Only Super Admins can run this operation.');

        $pages = HtmlPage::whereNotNull('base_html')->get();
        $total     = $pages->count();
        $published = 0;
        $failed    = [];

        // Locate the VisualBuilderV3Controller's publishPage logic
        // We call HtmlPage::publish() which handles writing the static file
        foreach ($pages as $page) {
            try {
                $this->republishPage($page);
                $published++;
            } catch (\Throwable $e) {
                $failed[] = $page->slug . ': ' . $e->getMessage();
            }
        }

        // Also regenerate the sitemap
        try {
            HtmlPage::updateSitemap();
        } catch (\Throwable) {}

        $this->audit->log('tools.sync_from_db', 'system', null, "{$published}/{$total} pages republished");

        return response()->json([
            'success'   => true,
            'total'     => $total,
            'published' => $published,
            'failed'    => $failed,
            'message'   => "Republished {$published} of {$total} pages from database.",
        ]);
    }

    /**
     * Rebuilds the static HTML file for a single HtmlPage record, applying
     * the same Nav/Footer/Global-CSS injection the Visual Builder uses on Publish.
     */
    private function republishPage(HtmlPage $page): void
    {
        // Fetch global nav & footer
        $nav    = $page->nav_html    ?? '';
        $footer = $page->footer_html ?? '';
        $globalCss = $page->global_css ?? '';
        $pageCss   = $page->base_css  ?? '';
        $pageJs    = $page->base_js   ?? '';
        $headCode  = $page->head_code ?? '';
        $endCode   = $page->end_code  ?? '';
        $body      = $page->base_html ?? '';
        $useBootstrap = (bool) ($page->use_bootstrap ?? false);

        $bsCss = $useBootstrap ? '<link rel="stylesheet" href="/assets/bootstrap/bootstrap.min.css">' : '';
        $bsJs  = $useBootstrap ? '<script src="/assets/bootstrap/bootstrap.bundle.min.js"></script>' : '';
        $allCss = implode("\n", array_filter([$globalCss, $pageCss]));

        $html = "<!DOCTYPE html><html lang=\"en\"><head>";
        $html .= "<meta charset=\"UTF-8\">";
        $html .= "<meta name=\"viewport\" content=\"width=device-width,initial-scale=1\">";
        $html .= $bsCss;
        $html .= $headCode;
        if ($allCss) {
            $html .= "<style>{$allCss}</style>";
        }
        $html .= "</head><body>";
        $html .= $nav;
        $html .= $body;
        $html .= $footer;
        $html .= $bsJs;
        $html .= $endCode;
        if ($pageJs) {
            $html .= "<script>{$pageJs}</script>";
        }
        $html .= "</body></html>";

        // Determine output path
        $slug = $page->slug;
        $isHome = (bool) ($page->is_home ?? false);

        if ($isHome) {
            // Home page: also write a copy directly at index.html for Apache
            $outPath = public_path('index.html');
        } else {
            // Non-home pages always go in pages/templates/{slug}/ — never the document root
            $dir = public_path('pages/templates/' . $slug);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            $outPath = $dir . '/index.html';
        }

        file_put_contents($outPath, $html);
    }

}
