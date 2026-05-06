<?php

namespace App\Services;

/**
 * StaticPageBuilder
 * ─────────────────────────────────────────────────────────────────────
 * Generates static files for published pages into deploy/data/pages/{slug}/
 *
 * FILE LAYOUT PER PAGE:
 *   data/pages/{slug}/
 *     page.json      ← full content + layout (always written on any save)
 *     index.html     ← static HTML shell (only rebuilt when layout changes)
 *     style.css      ← page-specific CSS (only rebuilt when layout changes)
 *
 * SMART REBUILD LOGIC:
 *   - Every save  → writes page.json (content always fresh)
 *   - On publish  → compares layout_hash; if changed, rebuilds HTML + CSS
 *   - On publish  → if layout same, skips HTML/CSS rebuild (JSON-only update)
 *
 * FRONTEND BEHAVIOUR:
 *   - index.html contains SSR content for SEO + first paint
 *   - page.json contains live content the frontend JS can read to update
 *     the page without a full reload (e.g. after admin content-only change)
 */
class StaticPageBuilder
{
    private string $outputDir;  // deploy/data/pages/
    private string $publicBase; // deploy/ (web root)

    public function __construct()
    {
        $this->publicBase = dirname(public_path());
        $this->outputDir  = public_path('data/pages');
    }

    // ─────────────────────────────────────────────────────────────────
    // Public API
    // ─────────────────────────────────────────────────────────────────

    /**
     * Write page.json and (if layout changed or forced) rebuild HTML+CSS.
     *
     * @param  array  $pageData  Normalised page data from builder
     * @param  bool   $publish   True = publish; False = draft (JSON only)
     * @return array  ['json'=>bool, 'html'=>bool, 'css'=>bool, 'reason'=>string]
     */
    public function build(array $pageData, bool $publish = false): array
    {
        $slug    = $pageData['slug'] ?? 'page';
        $dir     = "{$this->outputDir}/{$slug}";
        $result  = ['json' => false, 'html' => false, 'css' => false, 'reason' => ''];

        $this->ensureDir($dir);

        $sections     = $pageData['sections'] ?? [];
        $layoutHash   = $this->hashLayout($sections);

        // ── Compare layout BEFORE overwriting page.json ───────────────
        $existingHtml = "{$dir}/index.html";
        $forceRebuild = ! file_exists($existingHtml);

        if (! $forceRebuild) {
            $oldPayloadFile = "{$dir}/page.json";
            if (file_exists($oldPayloadFile)) {
                $oldPayload = @json_decode(@file_get_contents($oldPayloadFile), true);
                $forceRebuild = ($oldPayload['layout_hash'] ?? '') !== $layoutHash;
            } else { $forceRebuild = true; }
        }

        // ── Now write the fresh page.json ─────────────────────────────
        $jsonPayload  = $this->buildJsonPayload($pageData, $layoutHash);
        $jsonString   = json_encode($jsonPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        
        file_put_contents("{$dir}/page.json", $jsonString);
        $result['json'] = true;

        // Keep legacy flat file for backward compat
        file_put_contents("{$this->outputDir}/{$slug}.json", $jsonString);

        if (! $publish) {
            $result['reason'] = 'draft';
            return $result;
        }

        if ($forceRebuild) {
            // Full rebuild
            $css = $this->buildCss($pageData, $sections);
            $html = $this->buildHtml($pageData, $sections, $css);

            file_put_contents("{$dir}/style.css", $css);
            file_put_contents("{$dir}/index.html", $html);

            $result['html']   = true;
            $result['css']    = true;
            $result['reason'] = 'layout_changed';
        } else {
            // Layout unchanged — patch existing HTML with new content only
            $this->patchHtmlContent($dir, $sections);
            $result['reason'] = 'content_only';
        }

        // Write the runtime JS (always, it's tiny and the same for all pages)
        $this->writeRuntimeJs($dir, $slug);

        return $result;
    }

    /**
     * Rebuild HTML + CSS from scratch (force).
     * Call this when admin explicitly triggers a full republish.
     */
    public function rebuild(string $slug): bool
    {
        $jsonFile = "{$this->outputDir}/{$slug}/page.json";
        if (! file_exists($jsonFile)) {
            // Try legacy location
            $jsonFile = "{$this->outputDir}/{$slug}.json";
        }
        if (! file_exists($jsonFile)) return false;

        $data = json_decode(file_get_contents($jsonFile), true);
        if (! $data) return false;

        $data['slug'] = $slug;
        $this->build($data, true);
        return true;
    }

    /**
     * Delete all static files for a page.
     */
    public function remove(string $slug): void
    {
        // Remove subdir format: data/pages/{slug}/
        $dir = "{$this->outputDir}/{$slug}";
        if (is_dir($dir)) {
            foreach (glob("{$dir}/*") ?: [] as $f) @unlink($f);
            @rmdir($dir);
        }
        // Remove flat format: data/pages/{slug}.json
        @unlink("{$this->outputDir}/{$slug}.json");
    }

    // ─────────────────────────────────────────────────────────────────
    // JSON payload builder
    // ─────────────────────────────────────────────────────────────────

    private function buildJsonPayload(array $pageData, string $layoutHash): array
    {
        $sections = $pageData['sections'] ?? [];

        return [
            'title'       => $pageData['title']            ?? '',
            'slug'        => $pageData['slug']             ?? '',
            'status'      => $pageData['status']           ?? 'draft',
            'layout_hash' => $layoutHash,
            'meta'        => [
                'description' => $pageData['meta_description'] ?? '',
                'og_image'    => $pageData['og_image']         ?? '',
            ],
            'sections'    => array_map([$this, 'normaliseSection'], $sections),
            'updated'     => now()->toISOString(),
        ];
    }

    private function normaliseSection(array $sec): array
    {
        // Handle both builder format (sections array) and renderer format (rows/cells)
        $type = $sec['type'] ?? $sec['component'] ?? ($sec['cells'][0]['component'] ?? 'text');
        $data = $sec['data'] ?? $sec['content'] ?? ($sec['cells'][0]['content'] ?? []);
        if (!is_array($data)) $data = [];

        // CRITICAL: preserve _pid and _col so buildHtml() tree builder
        // can correctly place nested sections inside layout containers.
        // These may live at the top level of $sec (builder state format).
        if (!isset($data['_pid']) && isset($sec['data']['_pid'])) {
            $data['_pid'] = $sec['data']['_pid'];
        }
        if (!isset($data['_col']) && isset($sec['data']['_col'])) {
            $data['_col'] = $sec['data']['_col'];
        }

        return [
            'id'   => $sec['id']  ?? ('s' . substr(md5(json_encode($sec)), 0, 6)),
            'type' => $type,
            'data' => $data,
            'anim' => $sec['anim']    ?? 'none',
            'dur'  => $sec['animDur'] ?? '0.6',
            'del'  => $sec['animDel'] ?? '0',
        ];
    }

    // ─────────────────────────────────────────────────────────────────
    // HTML builder — full page static shell
    // ─────────────────────────────────────────────────────────────────

    private function buildHtml(array $pageData, array $sections, string $css): string
    {
        $slug        = $pageData['slug']            ?? 'page';
        $title       = htmlspecialchars($pageData['title'] ?? '', ENT_QUOTES);
        $metaDesc    = htmlspecialchars($pageData['meta_description'] ?? '', ENT_QUOTES);
        $ogImage     = htmlspecialchars($pageData['og_image'] ?? '', ENT_QUOTES);
        $siteUrl     = rtrim(config('app.url', ''), '/');
        $canonical   = "{$siteUrl}/{$slug}";

        // Build tree
        $tree = ['root' => []];
        foreach ($sections as $sec) {
            $norm = $this->normaliseSection($sec);
            $pid = $norm['data']['_pid'] ?? 'root';
            if ($pid === '') $pid = 'root';
            $col = $norm['data']['_col'] ?? 'main';
            $tree[$pid][$col][] = $norm;
        }

        $sectionHtml = $this->renderTreeList($tree, 'root', 'main');

        // Load active theme colors for inline :root fallback
        $themeBlock = '';
        try {
            $settings  = app(\App\Services\SettingsService::class);
            $themes    = $settings->get('site_themes', []);
            $activeId  = $settings->get('active_theme_id', '');
            if ($activeId && isset($themes[$activeId]['colors'])) {
                $lines = [':root {'];
                foreach ($themes[$activeId]['colors'] as $tok => $val) {
                    $lines[] = "  {$tok}: {$val};";
                }
                $lines[] = '}';
                $themeBlock = '<style>' . implode("\n", $lines) . '</style>';
            }
        } catch (\Throwable) {}

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="description" content="{$metaDesc}">
<meta property="og:title" content="{$title}">
<meta property="og:description" content="{$metaDesc}">
<meta property="og:image" content="{$ogImage}">
<meta property="og:url" content="{$canonical}">
<link rel="canonical" href="{$canonical}">
<title>{$title}</title>
{$themeBlock}
<link rel="stylesheet" href="/data/cms-theme.css">
<link rel="stylesheet" href="/data/pages/{$slug}/style.css">
<script>window.__CMS_SLUG="{$slug}";</script>
</head>
<body>
{$sectionHtml}
<script src="/data/pages/{$slug}/runtime.js" defer></script>
</body>
</html>
HTML;
    }

    /**
     * Render the static SSR HTML for a section (used inside index.html).
     * Each section gets a stable data-cms attribute so runtime.js can target it.
     */
    private function renderTreeList(array $tree, string $pid, string $col): string 
    {
        $list = $tree[$pid][$col] ?? [];
        $html = '';
        foreach ($list as $norm) {
            $animClass = $norm['anim'] !== 'none' ? " cms-anim cms-anim--{$norm['anim']}" : '';
            $animStyle = $norm['anim'] !== 'none' ? " style=\"--anim-dur:{$norm['dur']}s;--anim-del:{$norm['del']}s\"" : '';
            $html .= $this->renderSectionShell($norm, $animClass, $animStyle, $tree);
        }
        return $html;
    }

    private function renderSectionShell(array $sec, string $animClass, string $animStyle, array $tree): string
    {
        $id      = htmlspecialchars($sec['id']);
        $type    = htmlspecialchars($sec['type']);
        $data    = $sec['data'];

        $inner = $this->renderSectionContent($sec['id'], $sec['type'], $data, $tree);

        return "<section id=\"cms-{$id}\" data-cms=\"{$id}\" data-type=\"{$type}\" class=\"cms-section{$animClass}\"{$animStyle}>\n{$inner}\n</section>\n";
    }

    /**
     * Server-side render a section's inner HTML.
     */
    private function renderSectionContent(string $id, string $type, array $d, array $tree): string
    {
        $e = fn($v) => htmlspecialchars((string)($v ?? ''), ENT_QUOTES);

        return match($type) {
            'navbar' => sprintf(
                '<nav class="cms-navbar" style="background:%s"><div class="cms-nav-brand">%s</div><div class="cms-nav-links"></div></nav>',
                $e($d['bg'] ?? '#0f172a'), $e($d['site_name'] ?? '')
            ),
            'hero' => sprintf(
                '<div class="cms-hero" style="background:%s;text-align:%s"><h1 class="cms-hero-headline">%s</h1><p class="cms-hero-sub">%s</p><div class="cms-hero-btns">%s</div></div>',
                $e($d['bg'] ?? '#1e293b'),
                $e($d['align'] ?? 'center'),
                $e($d['headline'] ?? ''),
                $e($d['subheadline'] ?? ''),
                ($d['cta_text'] ?? '') ? sprintf('<a href="%s" class="cms-btn cms-btn-primary">%s</a>', $e($d['cta_url'] ?? '#'), $e($d['cta_text'])) : ''
            ),
            'heading' => sprintf(
                '<%s class="cms-heading" style="text-align:%s;color:%s">%s</%s>',
                $e($d['level'] ?? 'h2'), $e($d['align'] ?? 'left'),
                $e($d['color'] ?? '#111827'), $e($d['text'] ?? ''), $e($d['level'] ?? 'h2')
            ),
            'text' => sprintf(
                '<div class="cms-text" style="text-align:%s;color:%s">%s</div>',
                $e($d['align'] ?? 'left'), $e($d['color'] ?? '#374151'),
                nl2br($e($d['content'] ?? ''))
            ),
            'cards' => $this->renderCardsHtml($d),
            'stats' => $this->renderStatsHtml($d),
            'cta'   => sprintf(
                '<div class="cms-cta" style="background:%s"><h2 class="cms-cta-heading">%s</h2><p class="cms-cta-sub">%s</p><div class="cms-cta-btns">%s%s</div></div>',
                $e($d['bg'] ?? '#6366f1'), $e($d['heading'] ?? ''), $e($d['sub'] ?? ''),
                ($d['btn'] ?? '') ? sprintf('<a href="%s" class="cms-btn cms-btn-white">%s</a>', $e($d['btnurl'] ?? '#'), $e($d['btn'])) : '',
                ($d['btn2'] ?? '') ? sprintf('<a href="%s" class="cms-btn cms-btn-outline">%s</a>', $e($d['btn2url'] ?? '#'), $e($d['btn2'])) : ''
            ),
            'footer' => sprintf(
                '<footer class="cms-footer" style="background:%s"><div class="cms-footer-brand">%s</div><p class="cms-footer-tagline">%s</p></footer>',
                $e($d['bg'] ?? '#0f172a'), $e($d['site_name'] ?? ''), $e($d['tagline'] ?? '')
            ),
            'richtext' => sprintf('<div class="cms-richtext" style="padding:%spx 2rem">%s</div>', $e($d['pad'] ?? '28'), $d['content'] ?? ''),
            'pricing' => $this->renderPricingHtml($d),
            'contact-form' => $this->renderContactFormHtml($d),
            'gallery' => $this->renderGalleryHtml($d),
            'notices' => $this->renderNoticesHtml($d),
            'video' => sprintf('<div class="cms-video" style="padding:2rem"><div style="aspect-ratio:%s;background:#000;border-radius:.5rem;display:flex;align-items:center;justify-content:center;color:#fff;font-size:.9rem">Video Player: %s</div></div>', $e($d['aspect'] ?? '16/9'), $e($d['url'] ?? '')),
            'accordion' => $this->renderAccordionHtml($d),
            'image' => ($d['src'] ?? null)
                ? sprintf('<div class="cms-image" style="padding:1rem 2rem"><img src="%s" alt="%s" style="border-radius:%spx;object-fit:%s;height:%s" loading="lazy"></div>', $e($d['src']), $e($d['alt'] ?? ''), $e($d['radius'] ?? '8'), $e($d['fit'] ?? 'cover'), $e($d['height'] ? $d['height'].'px' : 'auto'))
                : '<div class="cms-image cms-image-placeholder" style="margin:1rem 2rem"></div>',
            'button' => sprintf('<div style="padding:1rem 2rem;text-align:%s"><a href="%s" class="cms-btn" style="background:%s;color:%s;border-radius:.5rem">%s</a></div>', $e($d['align'] ?? 'center'), $e($d['url'] ?? '#'), $e($d['color'] ?? '#6366f1'), $e($d['tc'] ?? '#ffffff'), $e($d['text'] ?? 'Get Started')),
            'divider' => sprintf('<div class="cms-divider"><hr style="border-top:%spx %s %s;margin:0 2rem"></div>', $e($d['thick'] ?? '1'), $e($d['style'] ?? 'solid'), $e($d['color'] ?? '#e5e7eb')),
            'spacer'  => sprintf('<div class="cms-spacer" style="height:%spx"></div>', $e($d['height'] ?? '40')),
            'section' => sprintf('<div style="background:%s;padding:%spx 0">%s</div>', $e($d['bg'] ?? '#fff'), $e($d['pad'] ?? '48'), $this->renderTreeList($tree, $id, 'main')),
            'cols2' => sprintf('<div style="background:%s;padding:%spx 2rem"><div style="display:grid;grid-template-columns:%sfr %sfr;gap:%spx">%s%s</div></div>', $e($d['bg'] ?? '#fff'), $e($d['pad'] ?? '32'), $e($d['lw'] ?? 50), 100 - (int)($d['lw'] ?? 50), $e($d['gap'] ?? 24), "<div>{$this->renderTreeList($tree, $id, 'col1')}</div>", "<div>{$this->renderTreeList($tree, $id, 'col2')}</div>"),
            'cols3' => sprintf('<div style="background:%s;padding:%spx 2rem"><div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:%spx">%s%s%s</div></div>', $e($d['bg'] ?? '#fff'), $e($d['pad'] ?? '28'), $e($d['gap'] ?? 20), "<div>{$this->renderTreeList($tree, $id, 'col1')}</div>", "<div>{$this->renderTreeList($tree, $id, 'col2')}</div>", "<div>{$this->renderTreeList($tree, $id, 'col3')}</div>"),
            'aside-left' => sprintf('<div style="background:%s;display:grid;grid-template-columns:%sfr %sfr"><div style="background:%s;border-right:1px solid #e5e7eb;padding:2rem">%s</div><div style="padding:2rem">%s</div></div>', $e($d['bg'] ?? '#fff'), $e($d['asideWidth'] ?? 25), 100 - (int)($d['asideWidth'] ?? 25), $e($d['asideBg'] ?? '#f8fafc'), $this->renderTreeList($tree, $id, 'aside'), $this->renderTreeList($tree, $id, 'main')),
            'aside-right' => sprintf('<div style="background:%s;display:grid;grid-template-columns:%sfr %sfr"><div style="padding:2rem">%s</div><div style="background:%s;border-left:1px solid #e5e7eb;padding:2rem">%s</div></div>', $e($d['bg'] ?? '#fff'), 100 - (int)($d['asideWidth'] ?? 25), $e($d['asideWidth'] ?? 25), $this->renderTreeList($tree, $id, 'main'), $e($d['asideBg'] ?? '#f8fafc'), $this->renderTreeList($tree, $id, 'aside')),
            default   => sprintf('<div class="cms-section-wrap" data-component="%s" style="padding:2rem;text-align:center;color:#999;font-size:.8rem">Component: %s</div>', $e($type), $e($type)),
        };
    }

    private function renderPricingHtml(array $d): string
    {
        $e = fn($v) => htmlspecialchars((string)($v ?? ''), ENT_QUOTES);
        $items = '';
        foreach ($d['items'] ?? [] as $item) {
            $hilite = ($item['highlighted'] ?? false) ? ' style="border-color:#6366f1;box-shadow:0 8px 30px rgba(99,102,241,0.15)"' : '';
            $features = '';
            foreach($item['features'] ?? [] as $f) $features .= '<li>'.$e($f).'</li>';
            $items .= sprintf(
                '<div class="cms-card"%s><h3 class="cms-card-title">%s</h3><div style="font-size:2rem;font-weight:800;margin:.5rem 0">%s</div><ul style="list-style:none;padding:0;margin:1.5rem 0;font-size:.875rem">%s</ul><a class="cms-btn cms-btn-primary" style="width:100%%;justify-content:center">%s</a></div>',
                $hilite, $e($item['name'] ?? ''), $e($item['price'] ?? ''), $features, $e($item['cta'] ?? 'Get Started')
            );
        }
        return sprintf(
            '<div class="cms-pricing" style="padding:4rem 2rem"><h2 class="cms-section-title" style="text-align:center">%s</h2><div class="cms-cards-grid">%s</div></div>',
            $e($d['heading'] ?? 'Pricing'), $items
        );
    }

    private function renderContactFormHtml(array $d): string
    {
        $e = fn($v) => htmlspecialchars((string)($v ?? ''), ENT_QUOTES);
        return sprintf(
            '<div class="cms-contact" style="background:%s;padding:4rem 2rem"><div style="max-width:600px;margin:0 auto"><h2 style="font-size:1.75rem;margin-bottom:.5rem">%s</h2><p style="color:#64748b;margin-bottom:2rem">%s</p><div style="display:grid;gap:1rem"><input type="text" placeholder="Name" style="padding:.75rem;border:1px solid #e2e8f0;border-radius:.375rem"><input type="email" placeholder="Email" style="padding:.75rem;border:1px solid #e2e8f0;border-radius:.375rem"><textarea placeholder="Message" style="padding:.75rem;border:1px solid #e2e8f0;border-radius:.375rem;min-height:120px"></textarea><button class="cms-btn cms-btn-primary" style="justify-content:center">%s</button></div></div></div>',
            $e($d['bg'] ?? '#fff'), $e($d['heading'] ?? 'Contact'), $e($d['subtext'] ?? ''), $e($d['btn_lbl'] ?? 'Send Message')
        );
    }

    private function renderAccordionHtml(array $d): string
    {
        $e = fn($v) => htmlspecialchars((string)($v ?? ''), ENT_QUOTES);
        $items = '';
        foreach ($d['items'] ?? [] as $item) {
            $items .= sprintf(
                '<div style="border-bottom:1px solid #e2e8f0;padding:1rem 0"><div style="font-weight:600;cursor:pointer">%s</div><div style="margin-top:.5rem;color:#64748b;font-size:.9rem">%s</div></div>',
                $e($item['q'] ?? ''), $e($item['a'] ?? '')
            );
        }
        return sprintf(
            '<div class="cms-accordion" style="padding:3rem 2rem;max-width:800px;margin:0 auto"><h2 class="cms-section-title">%s</h2><div>%s</div></div>',
            $e($d['heading'] ?? 'FAQ'), $items
        );
    }

    private function renderCardsHtml(array $d): string
    {
        $heading = htmlspecialchars($d['heading'] ?? '', ENT_QUOTES);
        $cols    = (int)($d['cols'] ?? 3);
        $bg      = htmlspecialchars($d['bg'] ?? '#f9fafb', ENT_QUOTES);
        $items   = '';
        foreach ($d['items'] ?? [] as $item) {
            $items .= sprintf(
                '<div class="cms-card"><div class="cms-card-icon">%s</div><h3 class="cms-card-title">%s</h3><p class="cms-card-desc">%s</p></div>',
                htmlspecialchars($item['icon'] ?? '', ENT_QUOTES),
                htmlspecialchars($item['title'] ?? '', ENT_QUOTES),
                htmlspecialchars($item['desc'] ?? '', ENT_QUOTES)
            );
        }
        return sprintf(
            '<div class="cms-cards" style="background:%s"><h2 class="cms-section-title">%s</h2><div class="cms-cards-grid" style="--cols:%d">%s</div></div>',
            $bg, $heading, $cols, $items
        );
    }

    private function renderGalleryHtml(array $d): string
    {
        $e = fn($v) => htmlspecialchars((string)($v ?? ''), ENT_QUOTES);
        $heading = $e($d['heading'] ?? 'Gallery');
        $cols = (int)($d['cols'] ?? 3);
        $images = '';
        foreach ($d['images'] ?? [] as $src) {
            $img = $src ? sprintf('<img src="%s" style="width:100%%;aspect-ratio:1/1;object-fit:cover;border-radius:.5rem">', $e($src)) : '<div style="aspect-ratio:1/1;background:#f3f4f6;border-radius:.5rem;display:flex;align-items:center;justify-content:center;color:#999">📷</div>';
            $images .= '<div>'.$img.'</div>';
        }
        return sprintf(
            '<div class="cms-gallery" style="padding:3rem 2rem"><h2 class="cms-section-title">%s</h2><div style="display:grid;grid-template-columns:repeat(%d,1fr);gap:1rem">%s</div></div>',
            $heading, $cols, $images
        );
    }

    private function renderNoticesHtml(array $d): string
    {
        $e = fn($v) => htmlspecialchars((string)($v ?? ''), ENT_QUOTES);
        $heading = $e($d['heading'] ?? 'Notices');
        $items = '';
        foreach ($d['items'] ?? [] as $item) {
            $items .= sprintf(
                '<div style="margin-bottom:1.5rem"><div style="display:flex;gap:.75rem;align-items:baseline;margin-bottom:.25rem"><span style="font-size:.7rem;font-weight:700;color:#64748b;text-transform:uppercase">%s</span><span style="font-size:.7rem;padding:.125rem .375rem;background:#ede9fe;color:#6366f1;border-radius:4px;font-weight:600">%s</span></div><h4 style="font-size:.95rem;font-weight:700;margin-bottom:.25rem">%s</h4><p style="font-size:.85rem;color:#475569;line-height:1.4">%s</p></div>',
                $e($item['date'] ?? ''), $e($item['cat'] ?? 'General'), $e($item['title'] ?? ''), $e($item['body'] ?? '')
            );
        }
        return sprintf(
            '<div class="cms-notices" style="padding:2rem"><h2 class="cms-section-title" style="font-size:1.25rem">%s</h2><div>%s</div></div>',
            $heading, $items
        );
    }

    private function renderStatsHtml(array $d): string
    {
        $heading = htmlspecialchars($d['heading'] ?? '', ENT_QUOTES);
        $bg      = htmlspecialchars($d['bg'] ?? '#f9fafb', ENT_QUOTES);
        $items   = '';
        foreach ($d['items'] ?? [] as $item) {
            $items .= sprintf(
                '<div class="cms-stat"><div class="cms-stat-value">%s</div><div class="cms-stat-label">%s</div></div>',
                htmlspecialchars($item['value'] ?? $item['number'] ?? '0', ENT_QUOTES),
                htmlspecialchars($item['label'] ?? '', ENT_QUOTES)
            );
        }
        return sprintf(
            '<div class="cms-stats" style="background:%s"><h2 class="cms-section-title">%s</h2><div class="cms-stats-grid">%s</div></div>',
            $bg, $heading, $items
        );
    }

    // ─────────────────────────────────────────────────────────────────
    // CSS builder
    // ─────────────────────────────────────────────────────────────────

    private function buildCss(array $pageData, array $sections): string
    {
        $parts = [];

        // Base reset + typography
        $parts[] = $this->baseCss();

        // Animation keyframes if any section has animations
        $hasAnim = collect($sections)->contains(fn($s) => ($s['anim'] ?? 'none') !== 'none');
        if ($hasAnim) {
            $parts[] = $this->animationCss();
        }

        // Section-specific inline CSS (background colors etc.)
        foreach ($sections as $sec) {
            $norm = $this->normaliseSection($sec);
            $sectionCss = $this->sectionCss($norm);
            if ($sectionCss) $parts[] = $sectionCss;
        }

        // Global CSS from admin (custom overrides)
        if (!empty($pageData['global_css'])) {
            $parts[] = "/* Page Custom CSS */\n" . $pageData['global_css'];
        }

        return implode("\n\n", array_filter($parts));
    }

    private function baseCss(): string
    {
        return <<<'CSS'
/* ── CMS Page Base ─────────────────────────────────────────────────── */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
html { -webkit-text-size-adjust: 100%; scroll-behavior: smooth; }
body {
  font-family: -apple-system, BlinkMacSystemFont, 'Inter', 'Segoe UI', sans-serif;
  line-height: 1.6; color: #1e293b;
  -webkit-font-smoothing: antialiased;
}
img { max-width: 100%; display: block; }
a { color: inherit; }

/* ── Layout wrappers ──────────────────────────────────────────────── */
.cms-section { position: relative; }
.cms-inner { max-width: 1200px; margin: 0 auto; padding: 0 1.5rem; }
.cms-section-title { font-size: clamp(1.25rem,3vw,2rem); font-weight: 700; margin-bottom: 1.5rem; }

/* ── Buttons ──────────────────────────────────────────────────────── */
.cms-btn { display: inline-flex; align-items: center; gap: .375rem; padding: .625rem 1.375rem; border-radius: .5rem; font-weight: 600; font-size: .9rem; text-decoration: none; transition: all .15s; cursor: pointer; border: 2px solid transparent; }
.cms-btn-primary { background: #5b6ef5; color: #fff; }
.cms-btn-primary:hover { background: #4355e0; transform: translateY(-1px); box-shadow: 0 4px 14px rgba(91,110,245,.4); }
.cms-btn-white { background: #fff; color: #4355e0; }
.cms-btn-white:hover { background: #f0f2ff; transform: translateY(-1px); }
.cms-btn-outline { background: transparent; color: #fff; border-color: rgba(255,255,255,.5); }
.cms-btn-outline:hover { background: rgba(255,255,255,.1); }

/* ── Navbar ───────────────────────────────────────────────────────── */
.cms-navbar { display: flex; align-items: center; justify-content: space-between; padding: 0 2rem; height: 60px; position: sticky; top: 0; z-index: 100; }
.cms-nav-brand { font-size: 1.125rem; font-weight: 800; color: #fff; letter-spacing: -.01em; }
.cms-nav-links { display: flex; gap: 1.5rem; }
.cms-nav-links a { color: rgba(255,255,255,.75); font-size: .875rem; font-weight: 500; text-decoration: none; transition: color .12s; }
.cms-nav-links a:hover { color: #fff; }

/* ── Hero ─────────────────────────────────────────────────────────── */
.cms-hero { padding: 5rem 2rem; position: relative; overflow: hidden; }
.cms-hero::after { content:''; position:absolute; inset:0; background:radial-gradient(ellipse at 65% 40%, rgba(99,102,241,.18), transparent 60%); pointer-events:none; }
.cms-hero-headline { font-size: clamp(2rem,5vw,3.5rem); font-weight: 800; color: #fff; line-height: 1.1; letter-spacing: -.025em; margin-bottom: .75rem; position:relative;z-index:1; }
.cms-hero-sub { font-size: clamp(.9rem,2vw,1.125rem); color: rgba(255,255,255,.75); margin-bottom: 2rem; max-width: 560px; position:relative;z-index:1; }
.cms-hero-btns { display: flex; gap: .75rem; flex-wrap: wrap; position:relative;z-index:1; }
@media(max-width:600px) { .cms-hero { padding: 3rem 1.25rem; } }

/* ── Heading ──────────────────────────────────────────────────────── */
.cms-heading { padding: 1rem 2rem; letter-spacing: -.02em; }

/* ── Text ─────────────────────────────────────────────────────────── */
.cms-text { padding: 1rem 2rem; font-size: 1rem; line-height: 1.75; max-width: 760px; }

/* ── Cards ────────────────────────────────────────────────────────── */
.cms-cards { padding: 3rem 2rem; }
.cms-cards-grid { display: grid; grid-template-columns: repeat(var(--cols,3),1fr); gap: 1.25rem; }
@media(max-width:900px) { .cms-cards-grid { grid-template-columns: repeat(2,1fr); } }
@media(max-width:600px) { .cms-cards-grid { grid-template-columns: 1fr; } }
.cms-card { background: #fff; border: 1px solid #e5e7eb; border-radius: .75rem; padding: 1.25rem; }
.cms-card-icon { font-size: 1.5rem; width: 44px; height: 44px; border-radius: .5rem; background: #ede9fe; display: flex; align-items: center; justify-content: center; margin-bottom: .75rem; }
.cms-card-title { font-size: 1rem; font-weight: 700; color: #111827; margin-bottom: .375rem; }
.cms-card-desc { font-size: .875rem; color: #6b7280; line-height: 1.6; }

/* ── Stats ────────────────────────────────────────────────────────── */
.cms-stats { padding: 3rem 2rem; }
.cms-stats-grid { display: grid; grid-template-columns: repeat(auto-fit,minmax(140px,1fr)); gap: 1.5rem; }
.cms-stat { text-align: center; }
.cms-stat-value { font-size: 2.25rem; font-weight: 800; color: #5b6ef5; letter-spacing: -.025em; line-height: 1; }
.cms-stat-label { font-size: .75rem; color: #6b7280; font-weight: 600; text-transform: uppercase; letter-spacing: .06em; margin-top: .375rem; }

/* ── CTA ──────────────────────────────────────────────────────────── */
.cms-cta { padding: 4rem 2rem; text-align: center; }
.cms-cta-heading { font-size: clamp(1.5rem,3.5vw,2.25rem); font-weight: 800; color: #fff; margin-bottom: .625rem; }
.cms-cta-sub { font-size: 1rem; color: rgba(255,255,255,.8); margin-bottom: 1.75rem; max-width:520px; margin-left:auto; margin-right:auto; }
.cms-cta-btns { display: flex; gap: .75rem; justify-content: center; flex-wrap: wrap; }

/* ── Footer ───────────────────────────────────────────────────────── */
.cms-footer { padding: 3rem 2rem 2rem; }
.cms-footer-brand { font-size: 1.125rem; font-weight: 800; color: #fff; margin-bottom: .375rem; }
.cms-footer-tagline { font-size: .875rem; color: rgba(255,255,255,.55); }

/* ── Image ────────────────────────────────────────────────────────── */
.cms-image img { width: 100%; height: auto; object-fit: cover; border-radius: .5rem; }
.cms-image-placeholder { height: 260px; background: #f3f4f6; border-radius: .5rem; }

/* ── Divider / Spacer ─────────────────────────────────────────────── */
.cms-divider { padding: .375rem 2rem; }
.cms-divider hr { border: none; }
.cms-spacer {}
CSS;
    }

    private function animationCss(): string
    {
        return <<<'CSS'
/* ── Scroll animations ────────────────────────────────────────────── */
.cms-anim { opacity: 0; }
.cms-anim.cms-visible { animation: var(--anim-name) var(--anim-dur,0.6s) ease var(--anim-del,0s) both; }

.cms-anim--fadeIn    { --anim-name: cmsAnimFadeIn; }
.cms-anim--slideUp   { --anim-name: cmsAnimSlideUp; }
.cms-anim--slideLeft { --anim-name: cmsAnimSlideLeft; }
.cms-anim--slideRight{ --anim-name: cmsAnimSlideRight; }
.cms-anim--zoomIn    { --anim-name: cmsAnimZoomIn; }
.cms-anim--bounce    { --anim-name: cmsAnimBounce; }
.cms-anim--flip      { --anim-name: cmsAnimFlip; }

@keyframes cmsAnimFadeIn    { from{opacity:0}              to{opacity:1} }
@keyframes cmsAnimSlideUp   { from{opacity:0;transform:translateY(28px)}  to{opacity:1;transform:none} }
@keyframes cmsAnimSlideLeft { from{opacity:0;transform:translateX(-28px)} to{opacity:1;transform:none} }
@keyframes cmsAnimSlideRight{ from{opacity:0;transform:translateX(28px)}  to{opacity:1;transform:none} }
@keyframes cmsAnimZoomIn    { from{opacity:0;transform:scale(.88)}  to{opacity:1;transform:none} }
@keyframes cmsAnimBounce    { 0%{opacity:0;transform:scale(.4)} 55%{transform:scale(1.1)} 75%{transform:scale(.95)} 100%{opacity:1;transform:none} }
@keyframes cmsAnimFlip      { from{opacity:0;transform:rotateX(-30deg)} to{opacity:1;transform:none} }
CSS;
    }

    private function sectionCss(array $sec): string
    {
        $id   = $sec['id'];
        $type = $sec['type'];
        $data = $sec['data'];

        // Only generate section-specific overrides
        $overrides = [];

        if (isset($data['bg']) && in_array($type, ['hero','cta','cards','stats','footer','navbar'])) {
            $bg = htmlspecialchars($data['bg'], ENT_QUOTES);
            $overrides[] = "#cms-{$id} { background: {$bg}; }";
        }

        return implode("\n", $overrides);
    }

    // ─────────────────────────────────────────────────────────────────
    // Runtime JS (same for every page, reads page.json + patches DOM)
    // ─────────────────────────────────────────────────────────────────

    private function writeRuntimeJs(string $dir, string $slug): void
    {
        $js = <<<JSEOF
/* CMS Page Runtime — reads page.json, patches content, runs animations */
(function(){
'use strict';

var slug = window.__CMS_SLUG || '{$slug}';

/* ── Scroll animation observer ─────────────────────────────────── */
function initAnims(){
  var els = document.querySelectorAll('.cms-anim');
  if(!els.length) return;
  var obs = new IntersectionObserver(function(entries){
    entries.forEach(function(e){
      if(e.isIntersecting){ e.target.classList.add('cms-visible'); obs.unobserve(e.target); }
    });
  },{ threshold:0.1 });
  els.forEach(function(el){ obs.observe(el); });
}

/* ── Content patcher: updates DOM from page.json ────────────────── */
function patchContent(sections){
  sections.forEach(function(sec){
    var el = document.querySelector('[data-cms="'+sec.id+'"]');
    if(!el) return;
    var d = sec.data || {};
    switch(sec.type){
      case 'hero':
        patch(el,'.cms-hero-headline', d.headline);
        patch(el,'.cms-hero-sub',      d.subheadline);
        patchLink(el, '.cms-hero-btns .cms-btn-primary', d.cta_text, d.cta_url);
        if(d.bg) el.querySelector('.cms-hero').style.background = d.bg;
        break;
      case 'heading':
        patch(el, '.cms-heading', d.text);
        if(d.color) el.querySelector('.cms-heading').style.color = d.color;
        if(d.align) el.querySelector('.cms-heading').style.textAlign = d.align;
        break;
      case 'text':
        patch(el, '.cms-text', d.content);
        break;
      case 'navbar':
        patch(el, '.cms-nav-brand', d.site_name);
        if(d.bg) el.querySelector('.cms-navbar').style.background = d.bg;
        break;
      case 'cta':
        patch(el, '.cms-cta-heading', d.heading);
        patch(el, '.cms-cta-sub',     d.sub);
        patchLink(el, '.cms-btn-white',   d.btn,  d.btnurl);
        patchLink(el, '.cms-btn-outline', d.btn2, d.btn2url);
        if(d.bg) el.querySelector('.cms-cta').style.background = d.bg;
        break;
      case 'cards':
        patchCards(el, d);
        break;
      case 'stats':
        patchStats(el, d);
        break;
      case 'footer':
        patch(el, '.cms-footer-brand',   d.site_name);
        patch(el, '.cms-footer-tagline', d.tagline);
        if(d.bg) el.querySelector('.cms-footer').style.background = d.bg;
        break;
      case 'pricing':
        // pricing uses the same cards logic essentially or we just let it fetch once.
        break;
      case 'gallery':
        // runtime patch for gallery if needed
        break;
      case 'notices':
        // runtime patch for notices if needed
        break;
    }
  });
}

function patch(root, sel, val){
  if(val == null) return;
  var el = root.querySelector(sel);
  if(el) el.textContent = val;
}
function patchLink(root, sel, text, href){
  if(!text) return;
  var el = root.querySelector(sel);
  if(el){ el.textContent = text; if(href) el.href = href; }
}
function patchCards(root, d){
  if(!d.heading && !d.items) return;
  if(d.heading) patch(root, '.cms-section-title', d.heading);
  if(!d.items) return;
  var grid = root.querySelector('.cms-cards-grid');
  if(!grid) return;
  grid.innerHTML = d.items.map(function(item){
    return '<div class="cms-card"><div class="cms-card-icon">'+esc(item.icon||'')+'</div>'
         + '<h3 class="cms-card-title">'+esc(item.title||'')+'</h3>'
         + '<p class="cms-card-desc">'+esc(item.desc||'')+'</p></div>';
  }).join('');
}
function patchStats(root, d){
  if(!d.items) return;
  var grid = root.querySelector('.cms-stats-grid');
  if(!grid) return;
  grid.innerHTML = d.items.map(function(item){
    return '<div class="cms-stat"><div class="cms-stat-value">'+esc(item.value||item.number||'0')+'</div>'
         + '<div class="cms-stat-label">'+esc(item.label||'')+'</div></div>';
  }).join('');
}
function esc(v){ var d=document.createElement('div'); d.textContent=String(v); return d.innerHTML; }

/* ── Boot ──────────────────────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', function(){
  initAnims();
  // Optionally refresh content from JSON (enables live content without full rebuild)
  fetch('/data/pages/'+slug+'/page.json?v='+Date.now())
    .then(function(r){ return r.ok ? r.json() : null; })
    .then(function(data){
      if(data && data.sections) patchContent(data.sections);
    })
    .catch(function(){});
});

})();
JSEOF;

        file_put_contents("{$dir}/runtime.js", $js);
    }

    // ─────────────────────────────────────────────────────────────────
    // Content-only patch (when layout is unchanged)
    // ─────────────────────────────────────────────────────────────────

    /**
     * When layout hasn't changed, update section data attributes in existing HTML
     * so the runtime.js gets fresh content immediately on next load.
     * (The runtime.js fetches page.json anyway, this is belt-and-suspenders.)
     */
    private function patchHtmlContent(string $dir, array $sections): void
    {
        // In content-only mode, page.json is already updated.
        // The runtime.js fetches it on load. No HTML surgery needed.
        // This is a no-op stub that could be extended later.
    }

    // ─────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────

    private function hashLayout(array $sections): string
    {
        // Hash based on section IDs + types + order + hierarchy (NOT content/data)
        $structure = array_map(fn($s) => [
            'id'   => $s['id']   ?? $s['cells'][0]['id']        ?? '',
            'type' => $s['type'] ?? $s['cells'][0]['component']  ?? '',
            'anim' => $s['anim'] ?? 'none',
            'pid'  => $s['data']['_pid'] ?? '',
            'col'  => $s['data']['_col'] ?? '',
        ], $sections);

        return substr(md5(json_encode($structure)), 0, 12);
    }

    private function ensureDir(string $dir): void
    {
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }
}
