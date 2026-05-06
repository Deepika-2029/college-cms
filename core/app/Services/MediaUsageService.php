<?php

namespace App\Services;

use App\Models\MediaFile;
use App\Models\TablesRegistry;
use App\Models\HtmlPage;
use App\Models\HtmlTemplate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MediaUsageService
{
    /**
     * Find everywhere a media file is referenced across the CMS.
     * Returns an array of usage items grouped by source type.
     */
    public function findUsage(int $mediaId): array
    {
        $media = MediaFile::findOrFail($mediaId);

        // Build all search needles: asset URL, raw path, filename
        $needles = $this->buildNeedles($media);

        $usages = [];

        // 1 ── CRUD database tables ────────────────────────────────────────
        $usages = array_merge($usages, $this->scanCrudTables($needles, $mediaId));

        // 2 ── Visual Builder pages ────────────────────────────────────────
        $usages = array_merge($usages, $this->scanVisualBuilderPages($needles));

        // 3 ── Site Settings (logos, favicons, etc.) ───────────────────────
        $usages = array_merge($usages, $this->scanSettings($needles, $media));

        return [
            'media_id'   => $mediaId,
            'file_name'  => $media->display_name,
            'total'      => count($usages),
            'usages'     => $usages,
        ];
    }

    // ── Needles ────────────────────────────────────────────────────────────

    private function buildNeedles(MediaFile $media): array
    {
        $needles = [];

        // /c-asset/{id} – the canonical internal URL used everywhere in CMS
        $needles[] = '/c-asset/' . $media->id;

        // Raw stored file_path
        if ($media->file_path) {
            $needles[] = $media->file_path;
            $needles[] = ltrim($media->file_path, '/');
        }

        // Cloudinary public ID
        if ($media->cloudinary_public_id) {
            $needles[] = $media->cloudinary_public_id;
        }

        // Just the filename as fallback
        $basename = basename(parse_url($media->file_path, PHP_URL_PATH) ?? $media->file_path);
        if ($basename) {
            $needles[] = $basename;
        }

        return array_unique(array_filter($needles));
    }

    // ── CRUD Tables ────────────────────────────────────────────────────────

    private function scanCrudTables(array $needles, int $mediaId): array
    {
        $usages = [];
        $tables = TablesRegistry::pluck('table_name')->toArray();

        foreach ($tables as $tableName) {
            if (!Schema::hasTable($tableName)) continue;

            $columns = Schema::getColumnListing($tableName);
            $textCols = [];
            foreach ($columns as $col) {
                $type = Schema::getColumnType($tableName, $col);
                // Laravel returns 'varchar'/'char' for string columns, not 'string'
                if (in_array($type, [
                    'string', 'varchar', 'char', 'tinytext',
                    'text', 'mediumtext', 'longtext', 'json',
                ])) {
                    $textCols[] = $col;
                }
            }

            if (empty($textCols)) continue;

            // Build OR-WHERE for each needle × each column
            $rows = DB::table($tableName)->where(function ($q) use ($textCols, $needles) {
                foreach ($textCols as $col) {
                    foreach ($needles as $needle) {
                        $q->orWhere($col, 'LIKE', '%' . $needle . '%');
                    }
                }
            })->select(array_merge(['id'], $textCols))->get();

            foreach ($rows as $row) {
                $rowArr   = (array) $row;
                $rowId    = $rowArr['id'] ?? null;
                $adminPrefix = env('ADMIN_PREFIX', 'admin');
                $usages[] = [
                    'type'      => 'crud',
                    'icon'      => '🗄️',
                    'label'     => 'Database → ' . ucfirst(str_replace('_', ' ', $tableName)),
                    'sub'       => 'Row #' . $rowId,
                    'link'      => $rowId
                        ? "/{$adminPrefix}/crud/{$tableName}/{$rowId}/edit"
                        : "/{$adminPrefix}/crud/{$tableName}",
                    'table'     => $tableName,
                    'row_id'    => $rowId,
                ];
            }
        }

        return $usages;
    }

    // ── Visual Builder ─────────────────────────────────────────────────────

    private function scanVisualBuilderPages(array $needles): array
    {
        $usages = [];
        $adminPrefix = env('ADMIN_PREFIX', 'admin');

        // Scan HTML pages (all text columns that could hold media references)
        $pages = HtmlPage::all(['id', 'title', 'slug', 'base_html', 'base_css', 'base_js',
                                 'components', 'overrides', 'tree', 'style_map', 'og_image']);

        foreach ($pages as $page) {
            $haystack = implode(' ', array_filter([
                $page->base_html ?? '',
                $page->base_css  ?? '',
                $page->base_js   ?? '',
                $page->og_image  ?? '',
                is_array($page->components) ? json_encode($page->components) : ($page->components ?? ''),
                is_array($page->overrides)  ? json_encode($page->overrides)  : ($page->overrides  ?? ''),
                is_array($page->tree)       ? json_encode($page->tree)       : ($page->tree       ?? ''),
                is_array($page->style_map)  ? json_encode($page->style_map)  : ($page->style_map  ?? ''),
            ]));

            foreach ($needles as $needle) {
                if (str_contains($haystack, $needle)) {
                    $usages[] = [
                        'type'  => 'page',
                        'icon'  => '📄',
                        'label' => 'Visual Builder → ' . ($page->title ?: $page->slug),
                        'sub'   => 'Slug: ' . $page->slug,
                        'link'  => "/{$adminPrefix}/visual-builder/pages/{$page->slug}/edit",
                    ];
                    break; // Don't double-count per page
                }
            }
        }

        // Scan file-based pages (public/pages/*/index.html)
        $pagesDir = public_path('pages');
        if (is_dir($pagesDir)) {
            foreach (glob($pagesDir . '/*/index.html') as $file) {
                $content = file_get_contents($file);
                $slug    = basename(dirname($file));
                foreach ($needles as $needle) {
                    if (str_contains($content, $needle)) {
                        // Check we haven't already listed this slug from DB scan
                        $already = array_filter($usages, fn($u) => $u['type'] === 'page'
                            && isset($u['sub']) && str_contains($u['sub'], $slug));
                        if (empty($already)) {
                            $usages[] = [
                                'type'  => 'page',
                                'icon'  => '📄',
                                'label' => 'Visual Builder → ' . ucfirst($slug),
                                'sub'   => 'Slug: ' . $slug . ' (published)',
                                'link'  => "/{$adminPrefix}/visual-builder/pages/{$slug}/edit",
                            ];
                        }
                        break;
                    }
                }
            }
        }

        // Also scan templates — uses html, css, js columns
        foreach (HtmlTemplate::all(['id', 'name', 'slug', 'html', 'css', 'js', 'thumbnail']) as $tpl) {
            $haystack = implode(' ', array_filter([
                $tpl->html      ?? '',
                $tpl->css       ?? '',
                $tpl->js        ?? '',
                $tpl->thumbnail ?? '',
            ]));
            foreach ($needles as $needle) {
                if (str_contains($haystack, $needle)) {
                    $usages[] = [
                        'type'  => 'template',
                        'icon'  => '🖼️',
                        'label' => 'Builder Template → ' . ($tpl->name ?: 'Template #' . $tpl->id),
                        'sub'   => 'Slug: ' . ($tpl->slug ?: $tpl->id),
                        'link'  => "/{$adminPrefix}/visual-builder/templates/{$tpl->id}/edit",
                    ];
                    break;
                }
            }
        }

        return $usages;
    }

    // ── Site Settings ──────────────────────────────────────────────────────

    private function scanSettings(array $needles, MediaFile $media): array
    {
        $usages      = [];
        $adminPrefix = env('ADMIN_PREFIX', 'admin');
        $settingsPath = storage_path('app/cms_settings.json');

        if (!file_exists($settingsPath)) return [];

        $settings = json_decode(file_get_contents($settingsPath), true) ?? [];

        $labelMap = [
            'site_logo'        => 'Site Logo',
            'university_logo'  => 'University Logo',
            'site_favicon'     => 'Site Favicon',
            'og_image'         => 'OG / Social Image',
        ];

        foreach ($settings as $key => $value) {
            if (!is_string($value) || empty($value)) continue;
            foreach ($needles as $needle) {
                if (str_contains($value, $needle)) {
                    $usages[] = [
                        'type'  => 'settings',
                        'icon'  => '⚙️',
                        'label' => 'Site Settings → ' . ($labelMap[$key] ?? ucfirst(str_replace('_', ' ', $key))),
                        'sub'   => 'Key: ' . $key,
                        'link'  => "/{$adminPrefix}/site-identity",
                    ];
                    break;
                }
            }
        }

        return $usages;
    }
}
