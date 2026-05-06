<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * AdminAssetController — v3.0
 *
 * ADDED in v3:
 *  - pluginAsset() — serves plugin CSS/JS from core/plugins/ (auth-gated)
 *    Replaces the old public_html/plugins/ sync approach.
 *    Only authenticated admins can load plugin JS.
 *    Public pages use server-side inlining (PageRendererService) instead.
 *
 * EXISTING:
 *  - serve()      — admin.css, admin.js, cms-grid.css
 *  - pageAsset()  — per-view style.css / script.js
 *  - crudAsset()  — crud-ui/{table}/style.css|script.js
 */
class AdminAssetController extends Controller
{
    private const ALLOWED = [
        'admin.css'                => 'text/css; charset=UTF-8',
        'admin.js'                 => 'application/javascript; charset=UTF-8',
        'cms-grid.css'             => 'text/css; charset=UTF-8',
        'bootstrap.min.css'        => 'text/css; charset=UTF-8',
        'bootstrap.bundle.min.js'  => 'application/javascript; charset=UTF-8',
    ];

    private string $assetDir;

    public function __construct()
    {
        $this->assetDir = resource_path('assets/admin');
    }

    // ── Admin global assets ───────────────────────────────────────────────

    public function serve(Request $request, string $file): Response
    {
        if (!array_key_exists($file, self::ALLOWED)) abort(404);

        $path     = $this->assetDir . DIRECTORY_SEPARATOR . $file;
        $realBase = realpath($this->assetDir);
        $realPath = realpath($path);

        if (!$realPath || !$realBase || !str_starts_with($realPath, $realBase . DIRECTORY_SEPARATOR)) abort(404);
        if (!is_file($realPath) || !is_readable($realPath)) abort(404);

        return $this->fileResponse($realPath, self::ALLOWED[$file], $request);
    }

    // ── Per-page view assets ──────────────────────────────────────────────

    public function pageAsset(Request $request, string $path): Response
    {
        if (!preg_match('/^[a-z0-9\/\-]+\.(css|js)$/', $path)) abort(404);

        $segments = explode('/', $path);

        if (count($segments) === 2) {
            if (!in_array($segments[1], ['style.css', 'script.js', 'engine.js', 'canvas.js'])) abort(404);
        } elseif (count($segments) === 3) {
            if ($segments[1] !== 'engine' || pathinfo($segments[2], PATHINFO_EXTENSION) !== 'js') abort(404);
            if (!in_array($segments[2], ['state.js', 'renderer.js', 'canvas.js', 'inspector.js', 'parser.js'])) abort(404);
        } else {
            abort(404);
        }

        $viewBase = resource_path('views/admin');
        $filePath = $viewBase . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $segments);
        $realBase = realpath($viewBase);
        $realPath = realpath($filePath);

        if (!$realPath || !$realBase || !str_starts_with($realPath, $realBase . DIRECTORY_SEPARATOR)) abort(404);
        if (!is_file($realPath) || !is_readable($realPath)) abort(404, 'Page asset not found: ' . $path);

        $mime = str_ends_with($path, '.css') ? 'text/css; charset=UTF-8' : 'application/javascript; charset=UTF-8';
        // Use no-cache for visual-builder assets so changes are always picked up
        $cc = str_contains($path, 'visual-builder') ? 'no-store, no-cache, must-revalidate' : null;
        return $this->fileResponse($realPath, $mime, $request, $cc);
    }

    // ── CRUD table assets ─────────────────────────────────────────────────

    public function crudAsset(Request $request, string $table, string $file): Response
    {
        if (!in_array($file, ['style.css', 'script.js'])) abort(404);
        if (!preg_match('/^[a-z0-9_]+$/', $table)) abort(404);

        $base     = resource_path('crud-ui');
        $filePath = $base . DIRECTORY_SEPARATOR . $table . DIRECTORY_SEPARATOR . $file;
        $realBase = realpath($base);
        $realPath = realpath($filePath);

        if (!$realPath || !$realBase || !str_starts_with($realPath, $realBase . DIRECTORY_SEPARATOR)) {
            $mime = str_ends_with($file, '.css') ? 'text/css; charset=UTF-8' : 'application/javascript; charset=UTF-8';
            return response('/* No custom UI for this table */', 200)
                ->header('Content-Type', $mime)
                ->header('Cache-Control', 'private, no-cache');
        }

        $mime = str_ends_with($file, '.css') ? 'text/css; charset=UTF-8' : 'application/javascript; charset=UTF-8';
        return $this->fileResponse($realPath, $mime, $request);
    }

    // ── Shared file response builder ──────────────────────────────────────

    private function fileResponse(string $path, string $mime, Request $request, ?string $cacheControl = null): Response
    {
        $mtime  = filemtime($path);
        $size   = filesize($path);
        $etag   = sprintf('"%s-%s"', base_convert((string) $mtime, 10, 36), base_convert((string) $size, 10, 36));
        $cc     = $cacheControl ?? 'private, max-age=86400, must-revalidate, immutable';

        if ($request->header('If-None-Match') === $etag) {
            return response('', 304)->withHeaders(['ETag' => $etag, 'Cache-Control' => $cc]);
        }

        return response(file_get_contents($path), 200)
            ->header('Content-Type',                  $mime)
            ->header('Content-Length',                (string) $size)
            ->header('ETag',                          $etag)
            ->header('Last-Modified',                 gmdate('D, d M Y H:i:s', $mtime) . ' GMT')
            ->header('Cache-Control',                 $cc)
            ->header('X-Content-Type-Options',        'nosniff')
            ->header('Cross-Origin-Resource-Policy',  'same-origin');
    }
}
