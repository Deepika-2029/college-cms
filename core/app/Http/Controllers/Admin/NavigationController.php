<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HtmlPage;
use App\Services\SettingsService;
use Illuminate\Http\Request;

class NavigationController extends Controller
{
    protected SettingsService $settings;

    public function __construct(SettingsService $settings)
    {
        $this->settings = $settings;
    }

    public function index()
    {
        $navLinks   = $this->settings->get('v3_nav_links',   []);
        $navHtml    = $this->settings->get('v3_nav_html',    '');
        $navCss     = $this->settings->get('v3_nav_css',     '');
        $navJs      = $this->settings->get('v3_nav_js',      '');
        $footerHtml = $this->settings->get('v3_footer_html', '');
        $footerCss  = $this->settings->get('v3_footer_css',  '');
        $footerJs   = $this->settings->get('v3_footer_js',   '');

        // Branding (Site Identity strings used in nav/footer)
        $brand = [
            'site_name'       => $this->settings->get('site_name', ''),
            'college_name'    => $this->settings->get('college_name', ''),
            'site_tagline'    => $this->settings->get('site_tagline', ''),
            'site_logo'       => $this->settings->get('site_logo', ''),
            'university_logo' => $this->settings->get('university_logo', ''),
        ];

        return view('admin.navigation.index', compact(
            'navLinks', 'navHtml', 'navCss', 'navJs',
            'footerHtml', 'footerCss', 'footerJs', 'brand'
        ));
    }

    /** Save all navigation data (links + code) */
    public function saveLayout(Request $request)
    {
        $request->validate([
            'nav_links'   => 'nullable|array',
            'nav_html'    => 'nullable|string',
            'nav_css'     => 'nullable|string',
            'nav_js'      => 'nullable|string',
            'footer_html' => 'nullable|string',
            'footer_css'  => 'nullable|string',
            'footer_js'   => 'nullable|string',
            'brand_site_name'       => 'nullable|string|max:100',
            'brand_college_name'    => 'nullable|string|max:100',
            'brand_site_tagline'    => 'nullable|string|max:200',
            'brand_site_logo'       => 'nullable|string|max:500',
            'brand_university_logo' => 'nullable|string|max:500',
        ]);

        $diag = [];

        // ── Step 1: Save to DB / settings ────────────────────────────────────
        try {
            $this->settings->set('v3_nav_links',   $request->input('nav_links',   []));
            $this->settings->set('v3_nav_html',    $request->input('nav_html',    ''));
            $this->settings->set('v3_nav_css',     $request->input('nav_css',     ''));
            $this->settings->set('v3_nav_js',      $request->input('nav_js',      ''));
            $this->settings->set('v3_footer_html', $request->input('footer_html', ''));
            $this->settings->set('v3_footer_css',  $request->input('footer_css',  ''));
            $this->settings->set('v3_footer_js',   $request->input('footer_js',   ''));

            // Save Branding Fields
            $this->settings->set('site_name',       $request->input('brand_site_name', ''));
            $this->settings->set('college_name',    $request->input('brand_college_name', ''));
            $this->settings->set('site_tagline',    $request->input('brand_site_tagline', ''));
            $this->settings->set('site_logo',       $request->input('brand_site_logo', ''));
            $this->settings->set('university_logo', $request->input('brand_university_logo', ''));

            // Rebuild relevant global_strings
            $globalStrs = $this->settings->get('global_strings', []);
            if (is_string($globalStrs)) $globalStrs = json_decode($globalStrs, true) ?? [];
            
            $globalStrs['site_name']    = $request->input('brand_site_name', '');
            $globalStrs['college_name'] = $request->input('brand_college_name', '');
            $globalStrs['site_tagline'] = $request->input('brand_site_tagline', '');
            
            $sl = $request->input('brand_site_logo', '');
            $globalStrs['site_logo'] = ($sl && !str_starts_with($sl, 'http')) ? asset(ltrim($sl, '/')) : $sl;
            
            $ul = $request->input('brand_university_logo', '');
            $globalStrs['university_logo'] = ($ul && !str_starts_with($ul, 'http')) ? asset(ltrim($ul, '/')) : $ul;

            $this->settings->set('global_strings', $globalStrs);
            \App\Services\GlobalStringService::flushCache();

            $nextVersion = (int) $this->settings->get('v3_global_version', 1) + 1;
            $this->settings->set('v3_global_version', $nextVersion);

            // Verify what was actually persisted
            $saved = \App\Models\Setting::where('key', 'v3_nav_html')->first();
            $diag['db_nav_html_saved']   = $saved ? 'yes (len=' . strlen($saved->value) . ')' : 'MISSING';
            $saved2 = \App\Models\Setting::where('key', 'v3_footer_html')->first();
            $diag['db_footer_html_saved'] = $saved2 ? 'yes (len=' . strlen($saved2->value) . ')' : 'MISSING';
        } catch (\Throwable $e) {
            $diag['db_error'] = $e->getMessage();
            return response()->json(['success' => false, 'message' => 'DB save failed: ' . $e->getMessage(), 'diag' => $diag]);
        }

        // ── Step 2: Write global CSS/JS files ────────────────────────────────
        try {
            $vb3 = app(VisualBuilderV3Controller::class);
            $vb3->writeGlobalAssets();
            $diag['global_assets'] = file_exists(public_path('frontend_public_pages/global/nav.css')) ? 'written' : 'MISSING after write';
        } catch (\Throwable $e) {
            $diag['global_assets_error'] = $e->getMessage();
        }

        // ── Step 3: Regenerate ALL pages (published + draft) ─────────────────
        // Drafts are rebuilt too so nav/footer is always up-to-date when
        // an admin publishes them — no stale HTML on first publish.
        $pages = HtmlPage::all();
        $diag['pages_total']    = $pages->count();
        $diag['pages_updated']  = 0;
        $diag['pages_errors']   = [];

        foreach ($pages as $page) {
            try {
                $vb3->writeStaticFile($page);
                $indexPath = public_path("frontend_public_pages/{$page->slug}/index.html");
                if (file_exists($indexPath)) {
                    $diag['pages_updated']++;
                } else {
                    $diag['pages_errors'][] = "{$page->slug}: file not found after write";
                }
            } catch (\Throwable $e) {
                $diag['pages_errors'][] = "{$page->slug}: " . $e->getMessage();
            }
        }

        \Illuminate\Support\Facades\Log::info('[NavigationController] saveLayout diag', $diag);

        return response()->json([
            'success' => true,
            'message' => "Saved! DB: ✅ | Pages regenerated: {$diag['pages_updated']}/{$diag['pages_total']}",
            'diag'    => $diag,
        ]);
    }

    /** JSON endpoint for V3 builder iframe */
    public function getJson()
    {
        return response()->json([
            'nav_links'   => $this->settings->get('v3_nav_links',   []),
            'nav_html'    => $this->settings->get('v3_nav_html',    ''),
            'nav_css'     => $this->settings->get('v3_nav_css',     ''),
            'nav_js'      => $this->settings->get('v3_nav_js',      ''),
            'footer_html' => $this->settings->get('v3_footer_html', ''),
            'footer_css'  => $this->settings->get('v3_footer_css',  ''),
            'footer_js'   => $this->settings->get('v3_footer_js',   ''),
        ]);
    }

    // Legacy stubs
    public function save(Request $request)        { return $this->saveLayout($request); }
    public function saveContent(Request $request) { return response()->json(['success' => true]); }
}
