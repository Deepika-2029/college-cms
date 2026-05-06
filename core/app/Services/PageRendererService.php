<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

/**
 * PageRendererService — v3.0 (Stripped of Plugins, Analytics, and Nav Builder)
 */
class PageRendererService
{
    private string $pagesDir;
    private string $dataDir;
    private array $mediaCache = [];

    public function __construct()
    {
        $this->pagesDir   = public_path('data/pages');
        $this->dataDir    = public_path('data');
    }

    /**
     * Render a page by slug.
     */
    public function render(string $slug, int $pageNum = 1): ?string
    {
        $page = $this->readPageJson($slug);
        if (!$page) return null;

        return $this->assemble($page, $slug, false, $pageNum);
    }

    /**
     * Render preview for admin.
     */
    public function renderPreview(string $title, array $sections): string
    {
        return $this->assemble([
            'title'    => $title,
            'slug'     => 'preview',
            'sections' => $sections,
        ], 'preview', true, 1);
    }

    private function assemble(array $page, string $slug, bool $isPreview = false, int $pageNum = 1): string
    {
        $title      = htmlspecialchars($page['title'] ?? 'Page', ENT_QUOTES);
        $globalCss  = $page['global_css'] ?? '';

        if (!empty($page['rows'])) {
            return $this->assembleGrid($page, $slug, $isPreview, $pageNum);
        }

        $sections    = $page['sections'] ?? [];
        $dataSources = $this->collectDataSources($sections);
        $renderer    = new ComponentRenderer();
        
        $tree = ['root' => []];
        foreach ($sections as $sec) {
            $pid = $sec['data']['_pid'] ?? 'root';
            if ($pid === '') $pid = 'root';
            $col = $sec['data']['_col'] ?? 'main';
            $tree[$pid][$col][] = $sec;
        }

        $bodyHtml     = $this->renderTreeList($tree, 'root', 'main', $renderer);

        $dataScript = $this->buildDataScript($dataSources);
        $appUrl     = rtrim(config('app.url', '/'), '/');
        $baseTag    = $isPreview ? "<base href=\"{$appUrl}/\">" : '';

        $siteSettings  = app(SettingsService::class);
        $siteName      = htmlspecialchars($siteSettings->get('site_name', 'College CMS'), ENT_QUOTES);
        $metaDesc      = htmlspecialchars($page['meta_description'] ?? $siteSettings->get('meta_description', ''), ENT_QUOTES);
        $favicon       = $siteSettings->url('site_favicon', '');
        $fullTitle     = $title . ($siteName ? ' — ' . $siteName : '');
        $canonicalUrl  = rtrim(config('app.url', '/'), '/') . '/' . ltrim($slug === 'home' ? '' : $slug, '/');

        $rendered = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    {$baseTag}
    <title>{$fullTitle}</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: sans-serif; background: #fff; color: #1e293b; min-height: 100vh; }
        .cs { position: relative; }
        {$globalCss}
    </style>
</head>
<body>
{$bodyHtml}
{$dataScript}
</body>
</html>
HTML;
        return $this->postProcessHtml($rendered);
    }

    private function assembleGrid(array $page, string $slug, bool $isPreview, int $pageNum = 1): string
    {
        $rows     = $page['rows'] ?? [];
        $renderer = new ComponentRenderer();
        $bodyHtml = '';

        foreach ($rows as $rowIdx => $row) {
            $rowId      = $row['id'] ?? "row_{$rowIdx}";
            $rowClasses = ComponentRenderer::rowClasses($row);
            $rowBg      = (!empty($row['bg']) && is_string($row['bg'])) ? " style=\"background:{$row['bg']};\"" : '';
            $cellsHtml  = '';

            foreach ($row['cells'] ?? [] as $cellIdx => $cell) {
                $cellId      = $cell['id'] ?? "{$rowId}_cell_{$cellIdx}";
                $cellClasses = ComponentRenderer::cellClasses($cell);
                $type        = $cell['component'] ?? 'text';
                $content     = $cell['content'] ?? [];

                $cellsHtml .= "<div class=\"{$cellClasses}\">" . $renderer->render($type, $content, $cellId) . "</div>";
            }
            $bodyHtml .= "<div class=\"{$rowClasses}\" id=\"{$rowId}\"{$rowBg}>{$cellsHtml}</div>";
        }

        $siteSettings = app(SettingsService::class);
        $siteName     = htmlspecialchars($siteSettings->get('site_name', 'College CMS'), ENT_QUOTES);
        $fullTitle    = htmlspecialchars($page['title'] ?? 'Page') . ' — ' . $siteName;

        $rendered = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" /><title>{$fullTitle}</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: sans-serif; background:#fff; }
        .cms-row { display: flex; flex-wrap: wrap; }
        .cms-cell { flex: 1; }
    </style>
</head>
<body class="cms-page">
    <div class="cms-canvas">{$bodyHtml}</div>
</body>
</html>
HTML;
        return $this->postProcessHtml($rendered);
    }

    public function renderTreeList(array $tree, string $pid, string $col, ComponentRenderer $renderer): string
    {
        $list = $tree[$pid][$col] ?? [];
        $html = '';
        foreach ($list as $idx => $section) {
            $type = $section['type'] ?? $section['plugin'] ?? $section['component'] ?? '';
            if (!$type) continue;
            
            $settings = $section['data'] ?? $section['settings'] ?? $section['content'] ?? [];
            $secId    = $section['id'] ?? "s{$idx}";

            // Scroll animations
            $anim = $section['anim'] ?? 'none';
            $animClass = ($anim && $anim !== 'none') ? " cms-anim cms-anim--{$anim}" : '';
            $dur  = isset($section['animDur'])   ? round((float)$section['animDur']   / 1000, 2) : 0.6;
            $del  = isset($section['animDelay']) ? round((float)$section['animDelay'] / 1000, 2) : 0;
            $animStyle = $animClass ? " style=\"--anim-dur:{$dur}s;--anim-del:{$del}s\"" : '';

            // Inject the tree so ComponentRenderer can render children
            $compHtml = $renderer->render($type, $settings, $secId, $tree, fn($t, $p, $c) => $this->renderTreeList($t, $p, $c, $renderer));
            if (!empty($settings['_css'])) {
                $compHtml .= "<style>{$settings['_css']}</style>";
            }
            if (!empty($settings['_js'])) {
                $compHtml .= "<script>{$settings['_js']}</script>";
            }

            $html .= "<div class=\"cms-section{$animClass}\" id=\"{$secId}\"{$animStyle}>"
                   . $compHtml
                   . "</div>\n";
        }
        return $html;
    }

    private function collectDataSources(array $sections): array
    {
        // Placeholder for data source collection if needed
        return [];
    }

    private function buildDataScript(array $dataSources): string
    {
        if (empty($dataSources)) return '';
        $json = json_encode($dataSources, JSON_HEX_TAG | JSON_UNESCAPED_UNICODE);
        return "<script>window.__d={$json};</script>";
    }

    public function readPageJson(string $slug): ?array
    {
        $path = "{$this->pagesDir}/{$slug}.json";
        if (!file_exists($path)) {
            $path = "{$this->pagesDir}/{$slug}/page.json";
        }
        if (!file_exists($path)) return null;
        return json_decode(file_get_contents($path), true);
    }

    private function postProcessHtml(string $html): string
    {
        $hasLcp = false;
        
        return preg_replace_callback('/<img([^>]*)src=["\']((?:\/media\/|https?:\/\/)[^"\']+)["\']([^>]*)>/i', function($matches) use (&$hasLcp) {
            $preAttrs = $matches[1];
            $src = $matches[2];
            $postAttrs = $matches[3];
            
            $isLocalMedia = str_starts_with($src, '/media/');
            $imgTag = "<img{$preAttrs}src=\"{$src}\"{$postAttrs}>";
            
            $isFirstImage = !$hasLcp;
            if ($isFirstImage) {
                $hasLcp = true;
                $imgTag = str_replace('loading="lazy"', '', $imgTag); // Remove lazy load from LCP
                if (!str_contains($imgTag, 'fetchpriority="high"')) {
                    $imgTag = str_replace('<img ', '<img fetchpriority="high" ', $imgTag);
                }
            }
            
            if ($isLocalMedia) {
                if (!array_key_exists($src, $this->mediaCache)) {
                    $this->mediaCache[$src] = \App\Models\MediaFile::where('file_path', $src)
                          ->orWhere('variants->original', $src)
                          ->orWhere('variants->large', $src)
                          ->first();
                }
                
                $media = $this->mediaCache[$src];
                
                if ($media && $media->isOptimized()) {
                    $attrPattern = '/(class|alt|width|height)="([^"]*)"/i';
                    preg_match_all($attrPattern, "{$preAttrs} {$postAttrs}", $attrMatches);
                    
                    $attrs = [];
                    foreach ($attrMatches[1] as $idx => $name) {
                        $attrs[$name] = $attrMatches[2][$idx];
                    }
                    if ($isFirstImage) {
                         $attrs['fetchpriority'] = 'high';
                         unset($attrs['loading']);
                    }
                    
                    return $media->pictureTag($attrs);
                }
            }
            
            return $imgTag;
        }, $html);
    }
}