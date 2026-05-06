<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HtmlPage;
use App\Services\SettingsService;
use App\Services\GlobalStringService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;

/**
 * Visual Builder V3 — Raw HTML Page Editor
 *
 * Shared nav/footer assets live in pages/global/ and are linked by all pages.
 * Page-specific CSS/JS go in pages/{slug}/style.css + script.js.
 * Bootstrap 5 is global. No components library.
 */
class VisualBuilderV3Controller extends Controller
{
    public function __construct(protected SettingsService $settings) {}

    // ─── PAGES LIST ────────────────────────────────────────────────────────────
    public function pages()
    {
        $pages = HtmlPage::orderByDesc('updated_at')->get();
        $components = \App\Models\V3Component::orderBy('category')->orderBy('name')->get();
        return view('admin.visual-builder-v3.pages', compact('pages', 'components'));
    }

    // ─── EDITOR UI ──────────────────────────────────────────────────────────────
    public function editPage(?string $slug = null)
    {
        $page = null;
        if ($slug) {
            $page = HtmlPage::where('slug', $slug)->first();
            if (!$page) abort(404);
        }

        $settings = $this->settings->all();
        $globalStrings = $settings['global_strings'] ?? [];
        if (is_string($globalStrings)) {
            $globalStrings = json_decode($globalStrings, true) ?? [];
        }
        
        $navHtml = $this->settings->get('v3_nav_html', '');
        $footerHtml = $this->settings->get('v3_footer_html', '');
        
        $globalCss = trim($this->settings->get('v3_global_css', ''));
        // Migrate legacy split CSS if global is empty
        if (empty($globalCss)) {
            $legacyNavCss = trim($this->settings->get('v3_nav_css', ''));
            $legacyFootCss = trim($this->settings->get('v3_footer_css', ''));
            $globalCss = trim($legacyNavCss . "\n\n" . $legacyFootCss);
        }

        if ($page) {
            $dirtyBodyHtml = $page->base_html ?? '';
            if (!empty($navHtml)) {
                $dirtyBodyHtml = preg_replace('#<header\b[^>]*class="[^"]*site-header[^"]*"[^>]*>[\s\S]*?</header>#i', '', $dirtyBodyHtml) ?? $dirtyBodyHtml;
            }
            if (!empty($footerHtml)) {
                $dirtyBodyHtml = preg_replace('#<footer\b[^>]*class="[^"]*site-footer[^"]*"[^>]*>[\s\S]*?</footer>#i', '', $dirtyBodyHtml) ?? $dirtyBodyHtml;
            }
            $page->base_html = trim($dirtyBodyHtml);
        }

        return view('admin.visual-builder-v3.editor', compact('page', 'globalStrings', 'navHtml', 'footerHtml', 'globalCss'));
    }

    // ─── SAVE PAGE ──────────────────────────────────────────────────────────────
    public function savePage(Request $request): JsonResponse
    {
        try {
            $data = $request->validate([
                'page_id'          => 'nullable|integer',
                'title'            => 'required|string|max:255',
                'slug'             => 'nullable|string|max:200',
                'meta_title'       => 'nullable|string|max:255',
                'meta_description' => 'nullable|string|max:500',
                'meta_keywords'    => 'nullable|string|max:500',
                'canonical_url'    => 'nullable|string|max:500',
                'og_title'         => 'nullable|string|max:255',
                'og_description'   => 'nullable|string|max:500',
                'og_image'         => 'nullable|string|max:500',
                'base_html'        => 'nullable|string|max:20000000',
                'nav_html'         => 'nullable|string|max:5000000',
                'footer_html'      => 'nullable|string|max:5000000',
                'base_css'         => 'nullable|string|max:5000000',
                'global_css'       => 'nullable|string|max:5000000',
                'base_js'          => 'nullable|string|max:5000000',
                'head_code'        => 'nullable|string|max:5000000',
                'end_code'         => 'nullable|string|max:5000000',
                'global_js'        => 'nullable|string|max:5000000',
                'use_bootstrap'    => 'nullable|integer',
                'publish'          => 'nullable|boolean',
            ]);

            $slug = $data['slug']
                ? Str::slug($data['slug'])
                : Str::slug($data['title'] ?? 'page-' . time());

            $existingId = $data['page_id'] ?? null;

            $page = null;
            if ($existingId) {
                $page = HtmlPage::find($existingId);
            }
            
            // If the user specifies a slug that already exists, 
            // merge/overwrite it rather than creating "slug-1", "slug-2", etc.
            if (!$page) {
                $page = HtmlPage::where('slug', $slug)->first();
            }
            
            if (!$page) {
                $page = new HtmlPage();
            }

            // ── Strip builder-only attributes before DB write ────────────────
            $rawHtml = $data['base_html'] ?? $page->base_html ?? '';
            $rawHtml = preg_replace('/ data-sel="[^"]*"/', '', $rawHtml);
            $rawHtml = preg_replace('/ data-hov="[^"]*"/', '', $rawHtml);
            $rawHtml = preg_replace('/ data-edit="[^"]*"/', '', $rawHtml);
            $rawHtml = preg_replace('/ contenteditable="(?:true|false)"/', '', $rawHtml);

            $page->title            = $data['title'] ?? $page->title;
            $page->slug             = $slug;
            $page->meta_title       = $data['meta_title'] ?? $page->meta_title;
            $page->meta_description = $data['meta_description'] ?? $page->meta_description;
            $page->meta_keywords    = $data['meta_keywords'] ?? $page->meta_keywords ?? null;
            $page->canonical_url    = $data['canonical_url'] ?? $page->canonical_url ?? null;
            $page->og_title         = $data['og_title'] ?? $page->og_title ?? null;
            $page->og_description   = $data['og_description'] ?? $page->og_description ?? null;
            $page->og_image         = $data['og_image'] ?? $page->og_image ?? null;
            $page->base_html        = $rawHtml;
            $page->base_css         = $data['base_css'] ?? '';
            $page->base_js          = $data['base_js'] ?? '';
            $page->head_code        = $data['head_code'] ?? $page->head_code;
            $page->end_code         = $data['end_code'] ?? $page->end_code;
            $page->use_bootstrap    = $data['use_bootstrap'] ?? 1;

            // If publish flag is set, mark page as published
            if (!empty($data['publish'])) {
                $page->status = 'published';
            }

            $page->save();

            $globalChanged = false;
            if (isset($data['nav_html']) && $data['nav_html'] !== $this->settings->get('v3_nav_html')) {
                $this->settings->set('v3_nav_html', $data['nav_html']);
                $globalChanged = true;
            }
            if (isset($data['footer_html']) && $data['footer_html'] !== $this->settings->get('v3_footer_html')) {
                $this->settings->set('v3_footer_html', $data['footer_html']);
                $globalChanged = true;
            }
            if (isset($data['global_css']) && $data['global_css'] !== $this->settings->get('v3_global_css')) {
                $this->settings->set('v3_global_css', $data['global_css']);
                $globalChanged = true;
            }
            if (isset($data['global_js']) && $data['global_js'] !== $this->settings->get('v3_global_js')) {
                $this->settings->set('v3_global_js', $data['global_js']);
                $globalChanged = true;
            }

            // Always regenerate static file so page content is up-to-date on the frontend
            $this->writeStaticFile($page);

            if ($globalChanged) {
                // If global header/footer changed, we must rebuild ALL other published pages!
                $allPages = HtmlPage::where('id', '!=', $page->id)->where('status', 'published')->get();
                foreach ($allPages as $otherPage) {
                    $this->writeStaticFile($otherPage);
                }
            }

            $published = $page->status === 'published';
            if ($published) {
                try { \App\Models\HtmlPage::updateSitemap(); } catch (\Throwable $e) {}
            }

            return response()->json([
                'success'   => true,
                'id'        => $page->id,
                'slug'      => $page->slug,
                'published' => $published,
                'message'   => $published ? 'Page saved & published to frontend!' : 'Page saved (draft)!',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'message' => collect($e->errors())->first()[0]]);
        } catch (\Throwable $e) {
            $msg = $e->getMessage();
            if (($pos = strpos($msg, '(SQL:')) !== false) {
                $msg = trim(substr($msg, 0, $pos));
            }
            return response()->json(['success' => false, 'message' => 'SQL Error: ' . $msg]);
        }
    }

    // ─── DELETE PAGE ────────────────────────────────────────────────────────────
    public function destroyPage(int $id): JsonResponse
    {
        $page = HtmlPage::findOrFail($id);
        $slug = $page->slug;

        // Delete compiled page directory (contains index.html, style.css, script.js, page.json)
        $pageDir = public_path("frontend_public_pages/{$slug}");
        if (File::isDirectory($pageDir)) {
            File::deleteDirectory($pageDir);
        }

        // Also clean up legacy path if it still exists
        $legacyDir = public_path("pages/templates/{$slug}");
        if (File::isDirectory($legacyDir)) {
            File::deleteDirectory($legacyDir);
        }

        $page->delete();
        try { \App\Models\HtmlPage::updateSitemap(); } catch (\Throwable $e) {}

        return response()->json(['success' => true]);
    }

    // ─── SET HOME PAGE ──────────────────────────────────────────────────────────
    public function setHome(int $id)
    {
        HtmlPage::query()->update(['is_home' => false]);
        $page = HtmlPage::findOrFail($id);
        $page->update(['is_home' => true]);

        $this->writeStaticFile($page);

        // Write .home-slug file so index.php knows which page is home
        try {
            $publicBase = public_path();
            File::put($publicBase . '/frontend_public_pages/.home-slug', $page->slug);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('[VB3] setHome slug file failed: ' . $e->getMessage());
        }

        try { \App\Models\HtmlPage::updateSitemap(); } catch (\Throwable $e) {}

        return response()->json(['success' => true]);
    }

    public function syncAll()
    {
        try {
            $pages = HtmlPage::all();
            foreach ($pages as $page) {
                // Call the actual write method that properly bundles nav/footer
                $this->writeStaticFile($page);
            }
            
            // Rebuild V3 components into the new frontend_templates logic
            $components = \App\Models\V3Component::all();
            $compController = app(\App\Http\Controllers\Admin\V3ComponentController::class);
            foreach ($components as $component) {
                // By updating, it will hit the observer/save logic
                $component->save(); 
                // There's a template Dir writer we can call if needed
            }
            // Let's trigger a manual rewrite of all template json files
            foreach ($components as $component) {
                $dir = public_path('frontend_templates/' . $component->id . '_' . \Illuminate\Support\Str::slug($component->name));
                File::ensureDirectoryExists($dir);
                File::put($dir . '/template.json', json_encode($component->toArray(), JSON_PRETTY_PRINT));
            }

            return response()->json(['success' => true, 'message' => count($pages) . ' pages and ' . count($components) . ' templates synced globally.']);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('[VB3] Sync All failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    // ─── WRITE GLOBAL ASSETS ────────────────────────────────────────────────────
    /**
     * Writes shared nav/footer CSS+JS into pages/global/.
     * Called by NavigationController::saveLayout() whenever nav/footer is saved.
     * All pages then link to these shared files — no more per-page bundling.
     *
     *   pages/global/nav.css
     *   pages/global/nav.js
     *   pages/global/footer.css
     *   pages/global/footer.js
     */
    public function writeGlobalAssets(): void
    {
        try {
            $globalDir = public_path('frontend_public_pages/global');
            File::ensureDirectoryExists($globalDir);

            // Read raw values from settings
            $navCss    = trim($this->settings->get('v3_nav_css',    ''));
            $navJs     = trim($this->settings->get('v3_nav_js',     ''));
            $footerCss = trim($this->settings->get('v3_footer_css', ''));
            $footerJs  = trim($this->settings->get('v3_footer_js',  ''));

            // Strip any accidental <style>/<script> wrappers
            $cleanNavCss    = ltrim(preg_replace('#</?style[^>]*>#i',  '', $navCss));
            $cleanFooterCss = ltrim(preg_replace('#</?style[^>]*>#i',  '', $footerCss));
            $cleanNavJs     = ltrim(preg_replace('#</?script[^>]*>#i', '', preg_replace('/<!--[\s\S]*?-->/', '', $navJs)));
            $cleanFooterJs  = ltrim(preg_replace('#</?script[^>]*>#i', '', preg_replace('/<!--[\s\S]*?-->/', '', $footerJs)));

            File::put($globalDir . '/nav.css',    $cleanNavCss);
            File::put($globalDir . '/nav.js',     $cleanNavJs);
            File::put($globalDir . '/footer.css', $cleanFooterCss);
            File::put($globalDir . '/footer.js',  $cleanFooterJs);

            \Illuminate\Support\Facades\Log::info('[VB3] Global assets written to pages/global/');
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('[VB3] writeGlobalAssets failed: ' . $e->getMessage());
        }
    }

    // ─── WRITE STATIC FILE ──────────────────────────────────────────────────────
    /**
     * Builds a complete standalone HTML file for a page.
     *
     * ┌───────────────────────────────────────────────────────────────┐
     * │  GLOBAL ASSETS (shared, cached by browser)                    │
     * │    pages/global/nav.css         ← nav CSS (linked in <head>)  │
     * │    pages/global/footer.css      ← footer CSS (linked)         │
     * │    pages/global/nav.js          ← nav JS (linked at </body>)  │
     * │    pages/global/footer.js       ← footer JS (linked)          │
     * │                                                                │
     * │  PER-PAGE ASSETS (all inside pages/templates/)                │
     * │    pages/templates/{slug}/style.css  ← global + page CSS      │
     * │    pages/templates/{slug}/script.js  ← global + page JS       │
     * │    pages/templates/{slug}/index.html ← full HTML output       │
     * └───────────────────────────────────────────────────────────────┘
     *
     * Nav/footer HTML is still injected inline so the DOM structure
     * is self-contained in index.html (no JS-render dependency).
     */
    public function writeStaticFile(HtmlPage $page): void
    {
        try {
            $publicBase = public_path();
            // All page files go into frontend_public_pages/{slug}/ to keep the root clean
            $pageDir    = $publicBase . '/frontend_public_pages/' . $page->slug;
            File::ensureDirectoryExists($pageDir);

            // ── Bootstrap tags ──────────────────────────────────────────────
            $bsCss = $page->use_bootstrap
                ? '<link rel="stylesheet" href="/assets/bootstrap/bootstrap.min.css">'
                : '';
            $bsJs = $page->use_bootstrap
                ? '<script src="/assets/bootstrap/bootstrap.bundle.min.js"></script>'
                : '';

            $headCode = $page->head_code ?? '';
            $endCode  = $page->end_code  ?? '';
            $slug     = $page->slug;
            $bodyHtml = $page->base_html ?? '';

            // ── Strip builder-only attributes (server-side safety net) ───────
            // JS does this before save, but this ensures the static file is
            // always clean even if the client missed something.
            $bodyHtml = preg_replace('/ data-sel="[^"]*"/', '', $bodyHtml);
            $bodyHtml = preg_replace('/ data-hov="[^"]*"/', '', $bodyHtml);
            $bodyHtml = preg_replace('/ data-edit="[^"]*"/', '', $bodyHtml);
            $bodyHtml = preg_replace('/ contenteditable="(?:true|false)"/', '', $bodyHtml);

            // ── Page Title ──────────────────────────────────────────────────
            $siteBrowserTitle = trim($this->settings->get('site_browser_title', ''));
            $rawPageTitle     = $page->meta_title ?: $page->title;
            $fullTitle        = $siteBrowserTitle
                ? e($rawPageTitle) . ' — ' . e($siteBrowserTitle)
                : e($rawPageTitle);

            // ── SEO & Open Graph Tags ───────────────────────────────────────
            $siteMetaDesc = trim($this->settings->get('site_meta_description', ''));
            $desc = e($page->meta_description ?: $siteMetaDesc);

            $keywordsTag = $page->meta_keywords 
                ? '<meta name="keywords" content="' . e($page->meta_keywords) . '">' . "\n" 
                : '';
            $canonicalTag = $page->canonical_url 
                ? '<link rel="canonical" href="' . e($page->canonical_url) . '">' . "\n" 
                : '';

            $ogTitle = $page->og_title ?: $rawPageTitle;
            $ogDesc  = $page->og_description ?: $page->meta_description ?: $siteMetaDesc;
            $ogImage = $page->og_image 
                ? '<meta property="og:image" content="' . e($page->og_image) . '">' . "\n" 
                : '';

            $ogTags  = '<meta property="og:title" content="' . e($ogTitle) . '">' . "\n";
            $ogTags .= '<meta property="og:description" content="' . e($ogDesc) . '">' . "\n";
            $ogTags .= '<meta property="og:type" content="website">' . "\n";
            if ($slug === 'home') {
                $ogTags .= '<meta property="og:url" content="' . e(url('/')) . '">' . "\n";
            } else {
                $ogTags .= '<meta property="og:url" content="' . e(url('/' . $slug)) . '">' . "\n";
            }
            $ogTags .= $ogImage;

            // ── Favicon ─────────────────────────────────────────────────────
            $faviconUrl = trim($this->settings->get('site_favicon', ''));
            $faviconTag = $faviconUrl
                ? '<link rel="icon" href="' . e($faviconUrl) . '">'
                : '';

            // ── Read nav/footer HTML ─────────────────────────────────────────
            $navHtml    = trim($this->settings->get('v3_nav_html',    ''));
            $footerHtml = trim($this->settings->get('v3_footer_html', ''));

            // ── Global CSS — check if global files already exist ─────────────
            // If global assets have been written, link to them; otherwise fall
            // back to the old bundled approach so we don't break during migration.
            $globalDir         = public_path('frontend_public_pages/global');
            $globalAssetsExist = File::exists($globalDir . '/nav.css')
                              && File::exists($globalDir . '/nav.js');

            // ── Per-page CSS: global CSS + page-specific CSS ─────────────────
            // (Nav/footer CSS are now in pages/global/ — not bundled here)
            $globalCss  = trim($this->settings->get('v3_global_css', ''));
            $rawPageCss = implode("\n", array_filter([
                $globalCss, $page->base_css ?? ''
            ]));
            $pageCss = ltrim(preg_replace('#</?style[^>]*>#i', '', $rawPageCss));

            // ── Per-page JS: global JS + page-specific JS ────────────────────
            // (Nav/footer JS are now in pages/global/ — not bundled here)
            $globalJs  = trim($this->settings->get('v3_global_js', ''));
            $rawPageJs = implode("\n", array_filter([
                $globalJs, $page->base_js ?? ''
            ]));
            $pageJs = preg_replace('/<!--[\s\S]*?-->/', '', $rawPageJs);
            $pageJs = ltrim(preg_replace('#</?script[^>]*>#i', '', $pageJs));

            // ── Fallback: if global assets not yet written, write them now ────
            // This auto-heals a fresh install where the nav was never explicitly saved.
            if (!$globalAssetsExist) {
                // Write the global files inline so they exist for future page loads
                try {
                    app(\App\Http\Controllers\Admin\VisualBuilderV3Controller::class)->writeGlobalAssets();
                    // Re-check after writing
                    $globalAssetsExist = File::exists($globalDir . '/nav.css')
                                      && File::exists($globalDir . '/nav.js');
                } catch (\Throwable) {}

                // If STILL missing (empty nav/footer settings), bundle inline as last resort
                if (!$globalAssetsExist) {
                    $navCss    = trim($this->settings->get('v3_nav_css',    ''));
                    $navJs     = trim($this->settings->get('v3_nav_js',     ''));
                    $footerCss = trim($this->settings->get('v3_footer_css', ''));
                    $footerJs  = trim($this->settings->get('v3_footer_js',  ''));
                    $rawFull   = implode("\n", array_filter([$navCss, $footerCss, $pageCss]));
                    $pageCss   = ltrim(preg_replace('#</?style[^>]*>#i', '', $rawFull));
                    $rawFullJs = implode("\n", array_filter([$navJs, $footerJs, $pageJs]));
                    $pageJs    = preg_replace('/<!--[\s\S]*?-->/', '', $rawFullJs);
                    $pageJs    = ltrim(preg_replace('#</?script[^>]*>#i', '', $pageJs));
                }
            }

            // ── Build Complete HTML Document ────────────────────────────────
            $vGlobal = $this->settings->get('v3_global_version', 1); // bumped on nav save
            $vPage   = time();

            $html  = '<!DOCTYPE html>' . "\n";
            $html .= '<html lang="en">' . "\n";
            $html .= '<head>' . "\n";
            $html .= '<meta charset="UTF-8">' . "\n";
            $html .= '<meta name="viewport" content="width=device-width, initial-scale=1.0">' . "\n";
            $html .= "<title>{$fullTitle}</title>" . "\n";
            $html .= '<meta name="description" content="' . $desc . '">' . "\n";
            $html .= $keywordsTag;
            $html .= $canonicalTag;
            $html .= $ogTags;
            if ($faviconTag) $html .= $faviconTag . "\n";
            $html .= $bsCss . "\n";
            if ($page->use_bootstrap) {
                $html .= '<link rel="stylesheet" href="/assets/bootstrap-icons/bootstrap-icons.min.css">' . "\n";
            }
            $html .= $headCode . "\n";

            // ── Link global nav/footer CSS (shared across all pages) ─────────
            if ($globalAssetsExist) {
                $html .= '<link rel="stylesheet" href="/frontend_public_pages/global/nav.css?v=' . $vGlobal . '">' . "\n";
                $html .= '<link rel="stylesheet" href="/frontend_public_pages/global/footer.css?v=' . $vGlobal . '">' . "\n";
            }
            // Page-specific CSS (global CSS + page CSS only — no nav/footer CSS)
            $html .= '<link rel="stylesheet" href="/frontend_public_pages/' . $slug . '/style.css?v=' . $vPage . '">' . "\n";
            $html .= '</head>' . "\n";
            $html .= '<body>' . "\n";

            // ── Inject SVGs ──────────────────────────────────────────────────
            $svgPath = base_path('core/resources/views/partials/svg-sprites.blade.php');
            if (File::exists($svgPath)) {
                $html .= File::get($svgPath) . "\n";
            }

            // ── Strip nav/footer already baked into bodyHtml ─────────────────
            // Prevents duplicate IDs (menuBtn, navList, backdrop) from breaking JS.
            $cleanBodyHtml = $bodyHtml;
            if (!empty($navHtml)) {
                $cleanBodyHtml = preg_replace(
                    '#<header\b[^>]*class="[^"]*site-header[^"]*"[^>]*>[\s\S]*?</header>#i',
                    '', $cleanBodyHtml
                ) ?? $cleanBodyHtml;
            }
            if (!empty($footerHtml)) {
                $cleanBodyHtml = preg_replace(
                    '#<footer\b[^>]*class="[^"]*site-footer[^"]*"[^>]*>[\s\S]*?</footer>#i',
                    '', $cleanBodyHtml
                ) ?? $cleanBodyHtml;
                $cleanBodyHtml = preg_replace(
                    '#<button\b[^>]*id="btt"[^>]*>[\s\S]*?</button>#i',
                    '', $cleanBodyHtml
                ) ?? $cleanBodyHtml;
            }

            // ── Inject nav + page body + footer ─────────────────────────────
            if (!empty($navHtml))    $html .= $navHtml . "\n";
            $html .= $cleanBodyHtml . "\n";
            if (!empty($footerHtml)) $html .= $footerHtml . "\n";

            // ── Scripts at bottom ────────────────────────────────────────────
            $html .= $bsJs . "\n";
            $html .= $endCode . "\n";

            // Global nav/footer JS (separate files, browser-cached)
            if ($globalAssetsExist) {
                $html .= '<script src="/frontend_public_pages/global/nav.js?v=' . $vGlobal . '"></script>' . "\n";
                $html .= '<script src="/frontend_public_pages/global/footer.js?v=' . $vGlobal . '"></script>' . "\n";
            }

            // Page-specific JS
            if (!empty($pageJs)) {
                $html .= '<script src="/frontend_public_pages/' . $slug . '/script.js?v=' . $vPage . '"></script>' . "\n";
            }

            $html .= '</body>' . "\n";
            $html .= '</html>';

            // ── Resolve [[global_tokens]] before writing ─────────────────────
            GlobalStringService::flushCache();
            $html = app(GlobalStringService::class)->replace($html);

            // ── Write page files ─────────────────────────────────────────────
            File::put($pageDir . '/index.html', $html);
            File::put($pageDir . '/style.css',  $pageCss);
            if (!empty($pageJs)) {
                File::put($pageDir . '/script.js', $pageJs);
            }

            // ── Write page.json metadata alongside compiled files ────────────
            File::put($pageDir . '/page.json', json_encode([
                'id'      => $page->id,
                'title'   => $page->title,
                'slug'    => $page->slug,
                'status'  => $page->status,
                'is_home' => (bool) $page->is_home,
                'updated' => now()->toISOString(),
            ], JSON_PRETTY_PRINT));

        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('[VB3] Static write failed: ' . $e->getMessage());
        }
    }
}
