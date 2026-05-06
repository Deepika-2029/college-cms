<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SettingsService;
use App\Services\GlobalStringService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class SiteIdentityController extends Controller
{
    public function __construct(private SettingsService $settings) {}

    // ─── Default brand color tokens ───────────────────────────────────
    private const DEFAULT_COLORS = [
        '--primary'    => '#1a5c3a',
        '--primary-2'  => '#174f33',
        '--primary-d'  => '#0f3a24',
        '--primary-l'  => '#e8f5ed',
        '--primary-xl' => '#f2fbf5',
        '--gold'       => '#e07b00',
        '--gold-d'     => '#c06a00',
        '--gold-l'     => '#fff3e0',
        '--success'    => '#16a34a',
        '--warning'    => '#d97706',
        '--error'      => '#dc2626',
        '--info'       => '#0284c7',
        '--text'       => '#0d1f18',
        '--text-s'     => '#3d5a4a',
        '--text-m'     => '#7a9585',
        '--text-inv'   => '#ffffff',
        '--sur'        => '#ffffff',
        '--sur-2'      => '#f7faf8',
        '--sur-3'      => '#eef5f1',
        '--bdr'        => '#d4e4da',
        '--bdr-s'      => '#eaf1ec',
        '--sh-xs'      => '0 1px 3px rgba(0,0,0,.07)',
        '--sh-sm'      => '0 2px 8px rgba(0,0,0,.09)',
        '--sh-md'      => '0 6px 24px rgba(0,0,0,.11)',
        '--sh-lg'      => '0 16px 48px rgba(0,0,0,.15)',
        '--sh-b'       => '0 8px 24px rgba(26,92,58,.3)',
    ];

    /**
     * Show the Site Identity management page.
     */
    public function index()
    {
        $settings = $this->settings->all();

        $globalStrings = $settings['global_strings'] ?? [];
        if (is_string($globalStrings)) {
            $globalStrings = json_decode($globalStrings, true) ?? [];
        }

        $customTokens = $settings['custom_tokens'] ?? [];
        if (is_string($customTokens)) {
            $customTokens = json_decode($customTokens, true) ?? [];
        }

        $themes = $this->settings->get('site_themes', []);
        if (!is_array($themes)) $themes = [];

        $activeThemeId = $this->settings->get('active_theme_id', '');

        return view('admin.site-identity.index', [
            'settings'       => $settings,
            'globalStrings'  => $globalStrings,
            'customTokens'   => $customTokens,
            'themes'         => $themes,
            'activeThemeId'  => $activeThemeId,
            'defaultColors'  => self::DEFAULT_COLORS,
        ]);
    }

    /**
     * Save site identity settings.
     */
    public function save(Request $request)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403, 'Only Super Admins can change site identity settings.');

        $request->validate([
            'site_name'             => ['nullable', 'string', 'max:100'],
            'college_name'          => ['nullable', 'string', 'max:100'],
            'site_browser_title'    => ['nullable', 'string', 'max:200'],
            'site_meta_description' => ['nullable', 'string', 'max:500'],
            'site_tagline'          => ['nullable', 'string', 'max:200'],
            'site_email'            => ['nullable', 'email', 'max:200'],
            'site_phone'            => ['nullable', 'string', 'max:50'],
            'site_address'          => ['nullable', 'string', 'max:500'],
            'homepage_slug'         => ['nullable', 'string', 'max:100', 'regex:/^[a-z0-9\-]+$/'],
            'site_logo'             => ['nullable', 'string', 'max:500'],
            'university_logo'       => ['nullable', 'string', 'max:500'],
            'site_favicon'      => ['nullable', 'string', 'max:500'],
            'social_facebook'   => ['nullable', 'url', 'max:300'],
            'social_twitter'    => ['nullable', 'url', 'max:300'],
            'social_instagram'  => ['nullable', 'url', 'max:300'],
            'social_youtube'    => ['nullable', 'url', 'max:300'],
            'social_linkedin'   => ['nullable', 'url', 'max:300'],
            'social_whatsapp'   => ['nullable', 'string', 'max:100'],
            'social_telegram'   => ['nullable', 'url', 'max:300'],
            'custom_token_keys'     => ['nullable', 'array'],
            'custom_token_keys.*'   => ['nullable', 'string', 'max:50', 'regex:/^[a-z0-9_]+$/'],
            'custom_token_values'   => ['nullable', 'array'],
            'custom_token_values.*' => ['nullable', 'string', 'max:1000'],
        ]);

        $fields = $request->only([
            'site_name', 'college_name', 'site_browser_title', 'site_meta_description',
            'site_tagline', 'site_email', 'site_phone',
            'site_address', 'homepage_slug',
            'site_logo', 'university_logo', 'site_favicon',
            'social_facebook', 'social_twitter', 'social_instagram',
            'social_youtube', 'social_linkedin', 'social_whatsapp', 'social_telegram',
        ]);

        $this->settings->fill($fields);

        // Handle Custom Tokens
        $customTokens = [];
        $keys = $request->input('custom_token_keys', []);
        $vals = $request->input('custom_token_values', []);
        foreach ($keys as $idx => $key) {
            $key = trim($key);
            if (!empty($key)) {
                $customTokens[$key] = $vals[$idx] ?? '';
            }
        }
        $this->settings->set('custom_tokens', $customTokens);

        $globalStrings = $this->buildGlobalStrings($fields, $customTokens);
        $this->settings->set('global_strings', $globalStrings);

        GlobalStringService::flushCache();

        return redirect()
            ->route('admin.site-identity.index')
            ->with('success', 'Site Identity saved. All [[tokens]] on your website have been updated.');
    }

    // ─── THEME PALETTE API ────────────────────────────────────────────

    /** Return all themes as JSON */
    public function getThemes(): JsonResponse
    {
        $themes = $this->settings->get('site_themes', []);
        return response()->json([
            'themes'       => is_array($themes) ? array_values($themes) : [],
            'active_id'    => $this->settings->get('active_theme_id', ''),
            'defaults'     => self::DEFAULT_COLORS,
        ]);
    }

    /** Create or update a named palette */
    public function saveTheme(Request $request): JsonResponse
    {
        $data = $request->validate([
            'id'     => ['nullable', 'string', 'max:64'],
            'name'   => ['required', 'string', 'max:80'],
            'colors' => ['required', 'array'],
        ]);

        $themes = $this->settings->get('site_themes', []);
        if (!is_array($themes)) $themes = [];

        $id = $data['id'] ?? null;
        if (!$id) {
            $id = Str::slug($data['name']) . '-' . substr(md5(uniqid()), 0, 6);
        }

        // Sanitise colors — only allow known token names
        $clean = [];
        foreach (self::DEFAULT_COLORS as $token => $_) {
            if (isset($data['colors'][$token])) {
                $clean[$token] = substr(strip_tags($data['colors'][$token]), 0, 200);
            }
        }

        $themes[$id] = [
            'id'    => $id,
            'name'  => $data['name'],
            'colors'=> $clean,
        ];

        $this->settings->set('site_themes', $themes);

        // If it's the active theme, rewrite the CSS file
        if ($this->settings->get('active_theme_id') === $id) {
            $this->writeThemeCss($clean);
        }

        return response()->json(['saved' => true, 'id' => $id, 'theme' => $themes[$id]]);
    }

    /** Set a palette as the active (site-wide) theme */
    public function activateTheme(Request $request): JsonResponse
    {
        $id = $request->input('id');
        $themes = $this->settings->get('site_themes', []);

        if (!isset($themes[$id])) {
            return response()->json(['error' => 'Theme not found'], 404);
        }

        $this->settings->set('active_theme_id', $id);
        $this->writeThemeCss($themes[$id]['colors']);

        return response()->json(['activated' => true, 'id' => $id]);
    }

    /** Delete a palette */
    public function deleteTheme(string $id): JsonResponse
    {
        $themes = $this->settings->get('site_themes', []);
        if (!is_array($themes)) $themes = [];

        unset($themes[$id]);
        $this->settings->set('site_themes', $themes);

        // If deleted was active, clear active
        if ($this->settings->get('active_theme_id') === $id) {
            $this->settings->set('active_theme_id', '');
        }

        return response()->json(['deleted' => true]);
    }

    // ─── Private helpers ──────────────────────────────────────────────

    /**
     * Write /data/cms-theme.css to the public directory.
     * All published pages link this file — changing it updates colors site-wide instantly.
     */
    private function writeThemeCss(array $colors): void
    {
        $dir = public_path('data');
        if (!is_dir($dir)) @mkdir($dir, 0755, true);

        $lines = [':root {'];
        foreach ($colors as $token => $value) {
            $lines[] = "  {$token}: {$value};";
        }
        $lines[] = '}';
        $css = "/* CMS Site Theme — auto-generated, do not edit manually */\n" . implode("\n", $lines) . "\n";

        file_put_contents(public_path('data/cms-theme.css'), $css);
    }

    /**
     * Build the flat global_strings token map.
     */
    private function buildGlobalStrings(array $fields, array $customTokens = []): array
    {
        $map = [];

        $textKeys = [
            'site_name', 'college_name', 'site_browser_title', 'site_meta_description',
            'site_tagline', 'site_email', 'site_phone', 'site_address', 'homepage_slug',
            'social_facebook', 'social_twitter', 'social_instagram',
            'social_youtube', 'social_linkedin', 'social_whatsapp', 'social_telegram',
        ];
        foreach ($textKeys as $key) {
            $map[$key] = $fields[$key] ?? '';
        }

        $imageKeys = ['site_logo', 'university_logo', 'site_favicon'];
        foreach ($imageKeys as $key) {
            $val = $fields[$key] ?? '';
            if ($val && !str_starts_with($val, 'http')) {
                $val = asset(ltrim($val, '/'));
            }
            $map[$key] = $val;
        }

        foreach ($customTokens as $key => $val) {
            if (!isset($map[$key])) {
                $map[$key] = $val;
            }
        }

        return $map;
    }
}
