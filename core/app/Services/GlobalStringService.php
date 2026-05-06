<?php

namespace App\Services;

/**
 * GlobalStringService
 *
 * Replaces [[token]] placeholders in compiled page HTML with live values
 * from the global_strings settings map. Called server-side per public page
 * request — no static file is ever modified, so SEO is unaffected.
 *
 * Usage in PageController:
 *   $html = app(GlobalStringService::class)->replace($html);
 */
class GlobalStringService
{
    /** Cached token map for the duration of this request. */
    private static ?array $map = null;

    public function __construct(private SettingsService $settings) {}

    /**
     * Replace all [[key]] tokens in $html with their current live values.
     * Returns the transformed string (original is never mutated on disk).
     */
    public function replace(string $html): string
    {
        $map = $this->getMap();

        if (empty($map)) {
            return $html;
        }

        $searchRaw = array_map(fn($k) => '[[' . $k . ']]', array_keys($map));
        $searchUrl = array_map(fn($k) => '%5B%5B' . $k . '%5D%5D', array_keys($map));

        $search  = array_merge($searchRaw, $searchUrl);
        $replace = array_merge(array_values($map), array_values($map));

        $html = str_replace($search, $replace, $html);

        return $html;
    }

    /**
     * Build (or return cached) token => value map.
     * Uses static cache so the JSON file is only read once per request,
     * even if replace() is called multiple times.
     */
    private function getMap(): array
    {
        if (self::$map !== null) {
            return self::$map;
        }

        $raw = $this->settings->get('global_strings', []);

        // Handle both array (new) and legacy JSON string
        if (is_string($raw)) {
            $raw = json_decode($raw, true) ?? [];
        }

        self::$map = is_array($raw) ? $raw : [];

        return self::$map;
    }

    /**
     * Flush the in-request cache (useful after saving new settings in tests).
     */
    public static function flushCache(): void
    {
        self::$map = null;
    }
}
