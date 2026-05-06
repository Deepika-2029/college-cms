<?php

namespace App\Services;

use App\Models\HtmlPage;
use App\Models\HtmlTemplate;

class PageCompilerService
{
    /**
     * Publish page: saves index.html + separate style.css + script.js files.
     * All CSS/JS is extracted from inline blocks and written to separate files.
     * The HTML only references them via <link> and <script src>.
     */
    public function publishPage(HtmlPage $page): bool
    {
        $tpl  = $page->template;
        $html = $page->base_html ?: ($tpl?->html ?? '');
        $css  = $page->base_css  ?: ($tpl?->css  ?? '');
        $js   = $page->base_js   ?: ($tpl?->js   ?? '');

        $slug = $page->slug;

        // All page files (index.html, style.css, script.js, page.json)
        // live together in a single folder: frontend_public_pages/{slug}/
        $pageDir = public_path("frontend_public_pages/{$slug}");
        if (!is_dir($pageDir)) mkdir($pageDir, 0755, true);

        foreach ($page->overrides ?? [] as $cmsId => $entry) {
            if (is_array($entry)) {
                $rawVal = $entry['value'] ?? '';
                $type   = $entry['type'] ?? $entry['field'] ?? 'text';
            } else {
                $rawVal = (string) $entry;
                $type   = 'text';
            }
            $value = htmlspecialchars($rawVal, ENT_QUOTES);

            if ($type === 'image' || $type === 'src') {
                $html = preg_replace_callback(
                    '/(<img[^>]*data-cms-el="' . preg_quote($cmsId, '/') . '"[^>]*>)/i',
                    function ($m) use ($value) {
                        return preg_match('/\bsrc="[^"]*"/', $m[1])
                            ? preg_replace('/\bsrc="[^"]*"/', 'src="' . $value . '"', $m[1])
                            : rtrim($m[1], '>') . ' src="' . $value . '">';
                    },
                    $html
                );
            } elseif ($type === 'href') {
                $html = preg_replace_callback(
                    '/(<a[^>]*data-cms-el="' . preg_quote($cmsId, '/') . '"[^>]*>)/i',
                    function ($m) use ($value) {
                        return preg_match('/\bhref="[^"]*"/', $m[1])
                            ? preg_replace('/\bhref="[^"]*"/', 'href="' . $value . '"', $m[1])
                            : rtrim($m[1], '>') . ' href="' . $value . '">';
                    },
                    $html
                );
            } else {
                $html = preg_replace_callback(
                    '/(<[a-z][a-z0-9]*[^>]*data-cms-el="' . preg_quote($cmsId, '/') . '"[^>]*>)(.*?)(<\/[a-z][a-z0-9]*>)/is',
                    fn($m) => $m[1] . $value . $m[3],
                    $html
                );
            }
        }

        $styleMapData  = $page->style_map ?? [];
        $devices = [
            ['key' => 'desktop', 'mq' => ''],
            ['key' => 'tablet',  'mq' => '@media (max-width: 820px)'],
            ['key' => 'mobile',  'mq' => '@media (max-width: 480px)'],
        ];

        $dynamicCss = "";
        foreach ($devices as $deviceConfig) {
            $deviceKey = $deviceConfig['key'];
            $mq        = $deviceConfig['mq'];
            $deviceMap = $styleMapData[$deviceKey] ?? [];

            if (empty($deviceMap)) continue;

            $rulesCss = "";
            foreach ($deviceMap as $elId => $styles) {
                if (!is_array($styles) || empty($styles)) continue;

                $baseStyles = [];
                $pseudoRules = "";

                foreach ($styles as $prop => $val) {
                    if (str_starts_with($prop, ':')) {
                        if (is_array($val)) {
                            $pRules = "";
                            foreach ($val as $p => $v) {
                                $cssProp = strtolower(preg_replace('/[A-Z]/', '-$0', $p));
                                $pRules .= "{$cssProp}: {$v} !important; ";
                            }
                            if ($pRules) {
                                $pseudoRules .= "[data-cms-el='{$elId}']{$prop} { {$pRules} }\n";
                            }
                        }
                        continue;
                    }

                    if ($val === '' || $val === null) continue;
                    $cssProp = strtolower(preg_replace('/[A-Z]/', '-$0', $prop));
                    $baseStyles[] = "{$cssProp}: {$val} !important;";
                }

                if (!empty($baseStyles)) {
                    $rulesCss .= "[data-cms-el='{$elId}'] { " . implode(' ', $baseStyles) . " }\n";
                }
                $rulesCss .= $pseudoRules;
            }

            if (!empty($rulesCss)) {
                if ($mq) {
                    $dynamicCss .= "{$mq} {\n{$rulesCss}}\n";
                } else {
                    $dynamicCss .= "{$rulesCss}\n";
                }
            }
        }

        if (!empty($dynamicCss)) {
            $css .= "\n/* --- Visual Builder Dynamic Styles --- */\n" . $dynamicCss;
        }

        $html = preg_replace('/\s*data-cms-(?!el=)[a-z0-9\-]+(?:="[^"]*")?/i', '', $html);
        $html = self::postProcessHtml($html);

        $isFullDoc = stripos($html, '<!DOCTYPE') !== false || stripos($html, '<html') !== false;

        $extractedCss = '';
        $extractedJs  = '';
        if ($isFullDoc) {
            preg_match_all('/<style[^>]*>([\s\S]*?)<\/style>/i', $html, $sm);
            foreach ($sm[1] as $blk) { $extractedCss .= "\n" . $blk; }
            $html = preg_replace('/<style[^>]*>[\s\S]*?<\/style>/i', '', $html);

            preg_match_all('/<script(?![^>]*\bsrc=)[^>]*>([\s\S]*?)<\/script>/i', $html, $jm);
            foreach ($jm[1] as $blk) { $extractedJs .= "\n" . $blk; }
            $html = preg_replace('/<script(?![^>]*\bsrc=)[^>]*>[\s\S]*?<\/script>/i', '', $html);
        }

        $rawCss = trim($extractedCss . "\n" . $css);
        if (!$isFullDoc) {
            $rawCss = "*, *::before, *::after { box-sizing: border-box; }\nbody { margin: 0; font-family: -apple-system, BlinkMacSystemFont, 'Inter', 'Segoe UI', sans-serif; }\n" . $rawCss;
        }
        $rawJs  = trim($extractedJs  . "\n" . $js);

        $finalCss = self::enforceIsolationCss($rawCss);
        $finalJs  = self::enforceIsolationJs($rawJs);

        // Bundle legacy home assets natively into the page for self-containment (V1 compatibility)
        $homeCssPath = public_path('assets/css/home.css');
        if (file_exists($homeCssPath)) {
            $homeCss = trim(file_get_contents($homeCssPath));
            if ($homeCss && strpos($finalCss, 'PREMIUM NAVBAR') === false) {
                $finalCss = "/* ── Legacy V1 Styles (Bundled) ── */\n" . $homeCss . "\n\n" . $finalCss;
            }
        }

        $homeJsPath = public_path('assets/js/home.js');
        if (file_exists($homeJsPath)) {
            $homeJs = trim(file_get_contents($homeJsPath));
            if ($homeJs && strpos($finalJs, '/* navbar */') === false) {
                $finalJs = "/* ── Legacy V1 Scripts (Bundled) ── */\n" . $homeJs . "\n\n" . $finalJs;
            }
        }

        file_put_contents("{$pageDir}/style.css", $finalCss);
        file_put_contents("{$pageDir}/script.js",  $finalJs);

        $useBootstrap = (bool) $page->use_bootstrap;
        if (!$useBootstrap && stripos($page->base_html ?? '', 'bootstrap') !== false) {
            $useBootstrap = true;
            $page->use_bootstrap = true;
            $page->saveQuietly();
        }

        $bsCssLink = $useBootstrap
            ? "    <link rel=\"stylesheet\" href=\"/assets/bootstrap/bootstrap.min.css\">\n" .
              "    <link rel=\"stylesheet\" href=\"/assets/bootstrap-icons/bootstrap-icons.min.css\">"
            : '';
        $bsJsTag = $useBootstrap
            ? "    <script src=\"/assets/bootstrap/bootstrap.bundle.min.js\"></script>" : '';

        // Only load page-specific styles + fonts. Do NOT inject home.css —
        // that is the navbar component's stylesheet and is already bundled into
        // the page's own style.css via the page-css textarea on save.
        $cssLink = "    <link rel=\"stylesheet\" href=\"/assets/css/fonts.css\">\n    <link rel=\"stylesheet\" href=\"/frontend_public_pages/{$slug}/style.css?v=" . time() . "\">";
        $jsTag   = "    <script src=\"/frontend_public_pages/{$slug}/script.js?v=" . time() . "\"></script>";
        $esc     = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES);

        if ($isFullDoc) {
            // Strip old V1 root-level style/script refs
            $html = preg_replace('/<link[^>]+href="\/style\.css"\s*\/?>/i', '', $html);
            $html = preg_replace('/<script[^>]+src="\/script\.js"\s*><\/script>/i', '', $html);
            // Strip any already-injected V3 refs (idempotent re-publish)
            $html = preg_replace('/<link[^>]+href="\/pages\/[^"]+\/style\.css"\s*\/?>\n?/i', '', $html);
            $html = preg_replace('/<script[^>]+src="\/pages\/[^"]+\/script\.js"\s*><\/script>\n?/i', '', $html);
            // Strip any old home.css references (legacy, no longer injected)
            $html = preg_replace('/<link[^>]+href="[^"]*home\.css"\s*\/?>\n?/i', '', $html);
            $html = preg_replace('/<link[^>]+href="\/assets\/css\/fonts\.css"\s*\/?>\n?/i', '', $html);
            $html = preg_replace('/<link[^>]+href="\/assets\/bootstrap[^"]+"\s*\/?>\n?/i', '', $html);
            $html = preg_replace('/<script[^>]+src="\/assets\/bootstrap[^"]+"\s*><\/script>\n?/i', '', $html);

            if ($bsCssLink) {
                $html = preg_replace('/<\/head>/i', $bsCssLink . "\n</head>", $html, 1);
            }
            $html = preg_replace('/<\/head>/i', "{$cssLink}\n</head>", $html, 1);

            $foot = ($bsJsTag ? $bsJsTag . "\n" : '') . $jsTag;
            $html = preg_replace('/<\/body>/i', "{$foot}\n</body>", $html, 1);
        } else {
            $t = $esc($page->title);
            
            $innerHtml = preg_replace('/<meta[^>]+>/i', '', $html);
            $innerHtml = preg_replace('/<link[^>]+(?:stylesheet|icon)[^>]+>/i', '', $innerHtml);
            $innerHtml = preg_replace('/<title[^>]*>.*?<\/title>/i', '', $innerHtml);
            
            $html = <<<DOC
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{$t}</title>
{$bsCssLink}
{$cssLink}
</head>
<body>
{$innerHtml}
{$bsJsTag}
{$jsTag}
</body>
</html>
DOC;
        }

        $html = self::postProcessHtml($html);

        // Auto-Optimize Images to WebP (Native GD)
        $html = preg_replace_callback('/(<img[^>]*\bsrc=")([^"]+)("[^>]*>)/i', function($m) {
            $src = $m[2];
            if (str_starts_with($src, 'data:') || str_ends_with(strtolower($src), '.svg') || str_ends_with(strtolower($src), '.webp')) {
                return $m[0];
            }

            try {
                if (!function_exists('imagecreatefromstring')) {
                    return $m[0];
                }

                if (str_starts_with($src, 'http://') || str_starts_with($src, 'https://')) {
                    $imgData = @file_get_contents($src);
                } else {
                    $localPath = public_path(ltrim($src, '/'));
                    $imgData = @file_exists($localPath) ? @file_get_contents($localPath) : false;
                }

                if ($imgData) {
                    $img = @imagecreatefromstring($imgData);
                    if ($img !== false) {
                        $hash = md5($imgData) . '.webp';
                        $webpPath = public_path("assets/img/webp/{$hash}");
                        $webpUrl = "/assets/img/webp/{$hash}";
                        
                        if (!is_dir(public_path('assets/img/webp'))) {
                            @mkdir(public_path('assets/img/webp'), 0755, true);
                        }

                        if (!file_exists($webpPath)) {
                            imagepalettetotruecolor($img);
                            imagealphablending($img, false);
                            imagesavealpha($img, true);
                            @imagewebp($img, $webpPath, 85);
                            imagedestroy($img);
                        }
                        return $m[1] . $webpUrl . $m[3];
                    }
                }
            } catch (\Throwable $e) {}
            return $m[0];
        }, $html);

        $htmlFile = "{$pageDir}/index.html";

        if ($page->is_home) {
            file_put_contents(public_path('frontend_public_pages/.home-slug'), $slug);
        }

        if (file_exists(public_path("{$slug}.html"))) @unlink(public_path("{$slug}.html"));
        if (file_exists(public_path('index.html'))) @unlink(public_path('index.html'));
        if (file_exists(public_path('home.html'))) @unlink(public_path('home.html'));

        file_put_contents($htmlFile, $html);

        // Write page.json metadata alongside index.html — one folder, all files
        file_put_contents("{$pageDir}/page.json", json_encode([
            'id'      => $page->id,
            'title'   => $page->title,
            'slug'    => $slug,
            'status'  => $page->status,
            'is_home' => (bool) $page->is_home,
            'updated' => now()->toISOString(),
        ], JSON_PRETTY_PRINT));

        // Clean Unused WebP Images
        try {
            $webpDir = public_path('assets/img/webp');
            if (is_dir($webpDir)) {
                $allWebpFiles = glob("{$webpDir}/*.webp");
                if (!empty($allWebpFiles)) {
                    $usedWebpUrls = [];
                    $pagesDir = public_path('pages');
                    if (is_dir($pagesDir)) {
                        $iter = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($pagesDir));
                        foreach ($iter as $file) {
                            if ($file->isFile() && $file->getExtension() === 'html') {
                                $content = @file_get_contents($file->getPathname());
                                if (preg_match_all('/src="(\/assets\/img\/webp\/[^"]+\.webp)"/i', $content, $matches)) {
                                    foreach ($matches[1] as $url) {
                                        $usedWebpUrls[$url] = true;
                                    }
                                }
                            }
                        }
                    }

                    foreach ($allWebpFiles as $webpFile) {
                        $url = '/assets/img/webp/' . basename($webpFile);
                        if (!isset($usedWebpUrls[$url])) {
                            @unlink($webpFile);
                        }
                    }
                }
            }
        } catch (\Exception $e) {}

        return true;
    }

    public static function postProcessHtml(string $html): string
    {
        if (!trim($html)) return $html;

        $hasNavbar = stripos($html, 'site-header') !== false
                  || stripos($html, 'class="navbar')  !== false
                  || stripos($html, "class='navbar")  !== false;

        if ($hasNavbar) {
            if (stripos($html, 'has-fixed-nav') === false) {
                if (preg_match('/<body[^>]+class="/i', $html)) {
                    $html = preg_replace('/(<body[^>]+class=")/i', '$1has-fixed-nav ', $html, 1);
                } else {
                    $html = preg_replace('/(<body\b)/i', '$1 class="has-fixed-nav"', $html, 1);
                }
            }
        }

        if (stripos($html, '<body') === false || stripos($html, 'modal') === false) {
            return $html;
        }
        try {
            $dom = new \DOMDocument('1.0', 'UTF-8');
            libxml_use_internal_errors(true);
            $dom->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
            libxml_clear_errors();

            $xpath = new \DOMXPath($dom);
            $body  = $xpath->query('//body')->item(0);
            if (!$body) return $html;

            $candidates = $xpath->query(
                '//body//*[contains(concat(" ",normalize-space(@class)," ")," modal ")]'
            );

            $toHoist = [];
            foreach ($candidates as $el) {
                if ($el->parentNode === $body) continue;

                $classAttr = $el->getAttribute('class');
                $isTopModal = preg_match('/\bmodal\b/', $classAttr) && preg_match('/\bfade\b/', $classAttr);
                if (!$isTopModal) continue;

                $toHoist[] = $el;
            }

            if (empty($toHoist)) return $html;

            foreach ($toHoist as $modal) {
                $wrapperClass = null;
                $parent = $modal->parentNode;
                while ($parent && $parent !== $body) {
                    $parentClass = (string) $parent->getAttribute('class');
                    if (preg_match('/\b(cms-[cst]-[a-z0-9\-]+)\b/i', $parentClass, $m)) {
                        $wrapperClass = $m[1];
                        break;
                    }
                    $parent = $parent->parentNode;
                }

                $modal->parentNode->removeChild($modal);
                
                if ($wrapperClass && $modal->parentNode !== $body) {
                    $wrapper = $dom->createElement('div');
                    $wrapper->setAttribute('class', $wrapperClass);
                    $wrapper->setAttribute('style', 'display: contents;');
                    $wrapper->appendChild($modal);
                    $body->appendChild($wrapper);
                } else {
                    $body->appendChild($modal);
                }
            }

            $out = $dom->saveHTML();
            $out = preg_replace('/^<\?xml[^?]*\?>\s*/i', '', $out);
            return $out ?: $html;

        } catch (\Throwable $e) {
            return $html;
        }
    }

    public static function enforceIsolationCss(string $css): string
    {
        if (!trim($css)) return '';

        // Split on either component [CSS] markers OR template [TPL] markers
        $blocks = preg_split('/(?=\/\* \[(?:CSS|TPL)\])/u', $css);
        $output = [];

        foreach ($blocks as $block) {
            $block = rtrim($block);
            if (!$block) continue;

            if (preg_match('/\/\* \[CSS\] .+? \[(cms-[cstp]-[a-z0-9]+)\] \*\//i', $block, $m)) {
                // Scoped component block — re-map :root to component scope
                $uid = $m[1];
                $block = preg_replace('/(?<![\w\-.])(?::root\b)/i', '.' . $uid, $block);

                $output[] = "/* ── isolated: {$uid} ── */";
                $output[] = $block;
            } elseif (preg_match('/\/\* \[TPL\] .+? \*\//i', $block)) {
                // Template block — pass through as page-level (no scoping needed)
                $output[] = "/* ── template styles ── */";
                $output[] = $block;
            } else {
                $output[] = "/* ── page-level styles ── */";
                $output[] = $block;
            }
        }

        return implode("\n\n", $output);
    }

    public static function enforceIsolationJs(string $js): string
    {
        if (!trim($js)) return '';

        $blocks = preg_split('/(?=\/\* \[CMS\])/u', $js);
        $output = [];

        foreach ($blocks as $block) {
            $block = rtrim($block);
            if (!$block) continue;

            if (!str_starts_with(ltrim($block), '/* [CMS]')) {
                $output[] = $block;
                continue;
            }

            $isIIFE = (bool) preg_match('/^\s*\/\* \[CMS\].*?\*\/\s*\(function\(\)/s', $block);

            if ($isIIFE) {
                $output[] = $block;
            } else {
                preg_match('/\/\* \[CMS\] (.+?) \*\//', $block, $cm);
                $label = $cm[1] ?? 'unknown';
                $output[] = "/* [CMS] {$label} */\n(function() {\n  'use strict';\n  /* fallback isolation */\n{$block}\n})();";
            }
        }

        return implode("\n\n", $output);
    }

    public function buildPageHtml(
        string $title,
        string $baseHtml,
        string $baseCss,
        string $baseJs,
        array  $overrides,
        array  $components,
        bool   $useBootstrap = false
    ): string {
        $bsCssLink = $useBootstrap
            ? '<link rel="stylesheet" href="/assets/bootstrap/bootstrap.min.css">' . "\n" .
              '    <link rel="stylesheet" href="/assets/bootstrap-icons/bootstrap-icons.min.css">'
            : '';
        $bsJsTag = $useBootstrap
            ? '<script src="/assets/bootstrap/bootstrap.bundle.min.js"></script>'
            : '';

        $html = $baseHtml;
        foreach ($overrides as $cmsId => $entry) {
            if (is_array($entry)) {
                $value = htmlspecialchars($entry['value'] ?? '', ENT_QUOTES);
                $type  = $entry['type'] ?? $entry['field'] ?? 'text';
            } else {
                $value = htmlspecialchars((string)$entry, ENT_QUOTES);
                $type  = 'text';
            }

            if ($type === 'image' || $type === 'src') {
                $html = preg_replace_callback(
                    '/(<img[^>]*data-cms-el="' . preg_quote($cmsId, '/') . '"[^>]*>)/i',
                    function ($m) use ($value) {
                        return preg_match('/\bsrc="[^"]*"/', $m[1])
                            ? preg_replace('/\bsrc="[^"]*"/', 'src="' . $value . '"', $m[1])
                            : rtrim($m[1], '>') . ' src="' . $value . '">';
                    },
                    $html
                );
            } elseif ($type === 'href') {
                $html = preg_replace_callback(
                    '/(<a[^>]*data-cms-el="' . preg_quote($cmsId, '/') . '"[^>]*>)/i',
                    function ($m) use ($value) {
                        return preg_match('/\bhref="[^"]*"/', $m[1])
                            ? preg_replace('/\bhref="[^"]*"/', 'href="' . $value . '"', $m[1])
                            : rtrim($m[1], '>') . ' href="' . $value . '">';
                    },
                    $html
                );
            } else {
                $html = preg_replace_callback(
                    '/(<[a-z][a-z0-9]*[^>]*data-cms-el="' . preg_quote($cmsId, '/') . '"[^>]*>)(.*?)(<\/[a-z][a-z0-9]*>)/is',
                    fn($m) => $m[1] . $value . $m[3],
                    $html
                );
            }
        }

        $html = preg_replace('/\s*data-cms-[a-z0-9\-]+(?:="[^"]*")?/i', '', $html);
        $html = self::postProcessHtml($html);

        $isFullDoc = stripos($html, '<!DOCTYPE') !== false || stripos($html, '<html') !== false;
        $esc = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES);

        if ($isFullDoc) {
            if ($bsCssLink) {
                $html = preg_replace('/<\/head>/i', $bsCssLink . "\n</head>", $html, 1);
            }
            if ($baseCss) {
                $html = preg_replace('/<\/head>/i', "<style>\n{$baseCss}\n</style>\n</head>", $html, 1);
            }
            if ($baseJs) {
                $html = preg_replace('/<\/body>/i', "<script>\n{$baseJs}\n</script>\n</body>", $html, 1);
            }
            if ($bsJsTag) {
                $html = preg_replace('/<\/body>/i', $bsJsTag . "\n</body>", $html, 1);
            }
            return self::postProcessHtml($html);
        }

        return self::postProcessHtml(<<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{$esc($title)}</title>
{$bsCssLink}
<style>
*, *::before, *::after { box-sizing: border-box; }
body { margin: 0; font-family: -apple-system, BlinkMacSystemFont, 'Inter', 'Segoe UI', sans-serif; }
{$baseCss}
</style>
</head>
<body>
{$html}
{$bsJsTag}
<script>
{$baseJs}
</script>
</body>
</html>
HTML);
    }
}
