<?php

namespace App\Services;

class SettingsService
{
    private string $path;
    private array  $data;

    private array $defaults = [
        // Site identity
        'site_name'     => 'College CMS',
        'site_tagline'  => '',
        'site_email'    => '',
        'site_phone'    => '',
        'site_address'  => '',

        // Homepage — which page slug loads at /
        'homepage_slug' => 'home',

        // Branding (store media file_path or URL)
        'site_logo'         => '',   // file_path of college logo image
        'university_logo'   => '',   // file_path of affiliated university logo
        'site_favicon'      => '',   // file_path of favicon

        // Global token replacement map — auto-rebuilt by SiteIdentityController on save
        'global_strings'    => [],

        // Social
        'social_facebook'  => '',
        'social_twitter'   => '',
        'social_instagram' => '',
        'social_youtube'   => '',
        'social_linkedin'  => '',

        // Media
        'media_driver'             => 'local',
        'cloudinary_cloud_name'    => '',
        'cloudinary_api_key'       => '',
        'cloudinary_api_secret'    => '',
        'cloudinary_upload_preset' => '',
        'cloudinary_folder'        => 'college-cms',

        // SEO
        'meta_description'     => '',
        'meta_keywords'        => '',

        // Navigation
        'navbar_menu'          => [],
        'footer_menu'          => [],

        // V3 Builder layout (raw HTML/CSS/JS + link tree)
        'v3_nav_links'         => [],
        'v3_nav_html'          => '',
        'v3_nav_css'           => '',
        'v3_nav_js'            => '',
        'v3_footer_html'       => '',
        'v3_footer_css'        => '',
        'v3_footer_js'         => '',

        // Misc
        'items_per_page'   => 10,
        'timezone'         => 'Asia/Kolkata',
        'footer_text'      => '',
        'maintenance_mode' => false,
    ];

    public function __construct()
    {
        $this->load();
    }

    // ── Read ──────────────────────────────────────────────────────────────

    public function all(): array
    {
        return array_merge($this->defaults, $this->data);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $this->defaults[$key] ?? $default;
    }

    /**
     * Static helper for contexts where DI is not available (e.g. middleware).
     * Reads directly from the DB.
     */
    public static function staticGet(string $key, mixed $default = null): mixed
    {
        try {
            $setting = \App\Models\Setting::where('key', $key)->first();
            if ($setting) {
                $decoded = json_decode($setting->value, true);
                return (json_last_error() === JSON_ERROR_NONE && !is_numeric($setting->value)) ? $decoded : $setting->value;
            }
        } catch (\Exception $e) {
            // DB not ready or missing table
        }
        
        $instance = new self();
        return $instance->defaults[$key] ?? $default;
    }

    /** Resolve a setting that may be a media path to a full URL */
    public function url(string $key, string $default = ''): string
    {
        $value = $this->get($key, '');
        if (! $value) return $default;
        if (str_starts_with($value, 'http')) return $value;
        return asset(ltrim($value, '/'));
    }

    // ── Write ─────────────────────────────────────────────────────────────

    public function set(string $key, mixed $value): void
    {
        $this->data[$key] = $value;
        
        $valToSave = is_array($value) || is_object($value) ? json_encode($value) : $value;
        try {
            \App\Models\Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $valToSave]
            );
        } catch (\Exception $e) {
            // Fails gracefully if DB isn't connected
        }
    }

    public function fill(array $values): void
    {
        foreach ($values as $key => $value) {
            $this->set($key, $value);
        }
    }

    // ── Media driver helpers ──────────────────────────────────────────────

    public function mediaDriver(): string
    {
        $envDriver = env('MEDIA_DRIVER', '');
        return $envDriver ?: $this->get('media_driver', 'local');
    }

    public function cloudinaryConfigured(): bool
    {
        $name   = $this->get('cloudinary_cloud_name') ?: env('CLOUDINARY_CLOUD_NAME', '');
        $key    = $this->get('cloudinary_api_key')    ?: env('CLOUDINARY_API_KEY', '');
        $secret = $this->get('cloudinary_api_secret') ?: env('CLOUDINARY_API_SECRET', '');
        return !empty($name) && !empty($key) && !empty($secret);
    }

    public function cloudinaryCredentials(): array
    {
        return [
            'cloud_name'    => $this->get('cloudinary_cloud_name') ?: env('CLOUDINARY_CLOUD_NAME', ''),
            'api_key'       => $this->get('cloudinary_api_key')    ?: env('CLOUDINARY_API_KEY', ''),
            'api_secret'    => $this->get('cloudinary_api_secret') ?: env('CLOUDINARY_API_SECRET', ''),
            'upload_preset' => $this->get('cloudinary_upload_preset') ?: env('CLOUDINARY_UPLOAD_PRESET', ''),
        ];
    }

    // ── Internal ──────────────────────────────────────────────────────────

    private function load(): void
    {
        $this->data = [];
        try {
            $dbSettings = \App\Models\Setting::all()->pluck('value', 'key')->toArray();
            foreach ($dbSettings as $k => $v) {
                // Determine if it was saved as JSON array/object or pure string
                $decoded = json_decode($v, true);
                $this->data[$k] = (json_last_error() === JSON_ERROR_NONE && !is_numeric($v)) ? $decoded : $v;
            }
        } catch (\Exception $e) {
            // db might be down
        }
    }

    private function save(): void
    {
        // Handled directly loop by loop in set() now.
    }
}
