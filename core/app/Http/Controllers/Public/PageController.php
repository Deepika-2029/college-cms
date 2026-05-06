<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Services\PageRendererService;
use App\Services\GlobalStringService;
use Illuminate\Http\Response;

class PageController extends Controller
{
    public function __construct(
        private PageRendererService $renderer,
        private GlobalStringService $globalStrings,
    ) {}

    public function home(): Response
    {
        // Allow any page to be set as homepage via admin settings
        $homeSlug = 'home';
        try {
            $homeSlug = app(\App\Services\SettingsService::class)->get('homepage_slug', 'home') ?: 'home';
        } catch (\Throwable) {}
        return $this->servePage($homeSlug);
    }

    public function show(string $slug): Response
    {
        return $this->servePage($slug);
    }

    private function servePage(string $slug): Response
    {
        $slug = preg_replace('/[^a-z0-9\-]/', '', strtolower($slug));

        // ── Detect paginated slug: e.g. "notices-2", "news-3" ────────────
        // Pattern: {base-slug}-{integer}  where integer >= 2
        $pageNum  = 1;
        $baseSlug = $slug;
        if (preg_match('/^(.+)-(\d+)$/', $slug, $m) && (int)$m[2] >= 2) {
            $candidate = $m[1];
            // Only treat as paginated if the base page JSON file exists
            // and the numeric-suffix page file does NOT exist as its own page
            $baseFile  = public_path("frontend_public_pages/{$candidate}/page.json");
            $ownFile   = public_path("frontend_public_pages/{$slug}/page.json");
            if (file_exists($baseFile) && ! file_exists($ownFile)) {
                $baseSlug = $candidate;
                $pageNum  = (int) $m[2];
            }
        }

        // Check published status — draft pages return 404 to public
        $dbPage = \App\Models\HtmlPage::where('slug', $baseSlug)->first();
        if ($dbPage && $dbPage->status === 'draft') {
            return $this->draftPage($baseSlug);
        }

        // V2/V3 Native Compiled Support
        $dbPageC = \App\Models\HtmlPage::where('slug', $baseSlug)->first();
        $isHome = $dbPageC ? $dbPageC->is_home : false;
        
        $compiledFile = public_path("frontend_public_pages/{$baseSlug}/index.html");

        if (file_exists($compiledFile)) {
            $html = file_get_contents($compiledFile);
        } else {
            // Static file missing — try to regenerate it on-the-fly
            $regenerated = false;
            if ($dbPageC && $dbPageC->base_html) {
                try {
                    $v3Compiler = app(\App\Http\Controllers\Admin\VisualBuilderV3Controller::class);
                    $v3Compiler->writeStaticFile($dbPageC);
                    if (file_exists($compiledFile)) {
                        $html = file_get_contents($compiledFile);
                        $regenerated = true;
                    }
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning("PageController: failed to auto-regenerate {$baseSlug}: " . $e->getMessage());
                }
            }
            if (!$regenerated) {
                // Final fallback to old renderer
                $html = $this->renderer->render($baseSlug, $pageNum);
            }
        }

        if ($html === null) {
            return $this->notFound($slug);
        }

        // ── Global token replacement — [[key]] → live value ──────────────
        // Runs in-memory on the already-read HTML string.
        // Static files on disk are never modified (SEO unaffected).
        $html = $this->globalStrings->replace($html);

        $lastMod = file_exists($compiledFile) ? filemtime($compiledFile) : time();
        return response($html, 200)
            ->header('Content-Type', 'text/html; charset=UTF-8')
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0')
            ->header('Last-Modified', gmdate('D, d M Y H:i:s', $lastMod) . ' GMT')
            ->header('X-Content-Type-Options', 'nosniff')
            ->header('X-Frame-Options', 'SAMEORIGIN');
    }

    private function draftPage(string $slug): Response
    {
        $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Draft — Not Published</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
               display: flex; align-items: center; justify-content: center;
               min-height: 100vh; background: #fffbeb; color: #1e293b; }
        .wrap { text-align: center; padding: 2rem; }
        .badge { display: inline-block; background: #f59e0b; color: white;
                 padding: .35rem .9rem; border-radius: 999px; font-size: .75rem;
                 font-weight: 700; letter-spacing: .06em; text-transform: uppercase; margin-bottom: 1rem; }
        h1 { font-size: 1.5rem; font-weight: 700; margin-bottom: .5rem; }
        p  { color: #64748b; font-size: .9rem; margin-bottom: 1.5rem; }
        a  { display: inline-block; background: #6366f1; color: white; text-decoration: none;
             padding: .65rem 1.5rem; border-radius: 8px; font-weight: 500; font-size: .9rem; }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="badge">Draft</div>
        <h1>This page isn't published yet</h1>
        <p>The page <code>/{$slug}</code> is saved as a draft and not visible to the public.</p>
        <a href="/">← Go home</a>
    </div>
</body>
</html>
HTML;
        return response($html, 404)
            ->header('Content-Type', 'text/html; charset=UTF-8')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, private')
            ->header('X-Robots-Tag', 'noindex, nofollow');
    }

    private function notFound(string $slug): Response
    {
        // Serve a custom 404 page if built in Visual Builder
        $compiled404 = public_path("frontend_public_pages/404/index.html");
        if (file_exists($compiled404)) {
            $html = file_get_contents($compiled404);
            $html = $this->globalStrings->replace($html);
            return response($html, 404)->header('Content-Type', 'text/html; charset=UTF-8');
        }

        $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Page not found</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
               display: flex; align-items: center; justify-content: center;
               min-height: 100vh; background: #f8fafc; color: #1e293b; }
        .wrap { text-align: center; padding: 2rem; }
        h1 { font-size: 4rem; font-weight: 800; color: #6366f1; line-height: 1; }
        h2 { font-size: 1.25rem; font-weight: 600; margin: .75rem 0 .5rem; }
        p  { color: #64748b; font-size: .9rem; margin-bottom: 1.5rem; }
        a  { display: inline-block; background: #6366f1; color: white; text-decoration: none;
             padding: .65rem 1.5rem; border-radius: 8px; font-weight: 500; font-size: .9rem; }
    </style>
</head>
<body>
    <div class="wrap">
        <h1>404</h1>
        <h2>Page not found</h2>
        <p>The page <code>/{$slug}</code> doesn't exist.</p>
        <a href="/">← Go home</a>
    </div>
</body>
</html>
HTML;
        return response($html, 404)->header('Content-Type', 'text/html; charset=UTF-8');
    }
}
