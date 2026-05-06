<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class HtmlPage extends Model
{
    protected $table = 'html_pages';
    protected $fillable = [
        'title','slug','template_id','base_html','base_css','base_js','head_code','end_code',
        'overrides','components','style_map','tree','meta_title','meta_description','meta_keywords','og_image',
        'status','use_bootstrap','created_by','is_home',
        // ── V2 Canvas Builder fields ───────────────────────────────────
        'scene_json','builder_version',
    ];
    protected $casts = [
        'overrides' => 'array', 'components' => 'array', 'style_map' => 'array', 'tree' => 'array',
        'use_bootstrap' => 'boolean',
        // V2
        'builder_version' => 'integer',
        // scene_json stored as raw string so Fabric.js can loadFromJSON() directly
    ];

    public function template()
    {
        return $this->belongsTo(HtmlTemplate::class, 'template_id');
    }

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($m) {
            if (empty($m->slug)) {
                $m->slug = Str::slug($m->title) ?: 'page-' . Str::random(6);
            }
        });
    }

    /**
     * Rebuilds the sitemap.xml dynamically from published pages.
     */
    public static function updateSitemap(): int
    {
        $baseUrl = rtrim(config('app.url'), '/');
        $pages = self::where('status', 'published')->get();

        $urls = [];
        // Homepage
        $urls[] = ['loc' => $baseUrl . '/', 'priority' => '1.0', 'changefreq' => 'weekly'];

        foreach ($pages as $page) {
            $slug = $page->slug;
            if ($slug === 'home' || $page->is_home) continue; // Already root

            $urls[] = [
                'loc'        => $baseUrl . '/' . $slug,
                'priority'   => '0.8',
                'changefreq' => 'weekly',
                'lastmod'    => $page->updated_at ? $page->updated_at->format('Y-m-d') : date('Y-m-d'),
            ];
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($urls as $url) {
            $xml .= "  <url>\n";
            foreach ($url as $k => $v) {
                $xml .= "    <{$k}>{$v}</{$k}>\n";
            }
            $xml .= "  </url>\n";
        }
        $xml .= '</urlset>';

        file_put_contents(public_path('sitemap.xml'), $xml);
        return count($urls);
    }
}
