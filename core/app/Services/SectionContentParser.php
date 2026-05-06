<?php

namespace App\Services;

/**
 * SectionContentParser
 *
 * Parses the global sections (navbar / footer) HTML into structured,
 * editable JSON and writes the data back to the template files.
 */
class SectionContentParser
{
    // ── NAVBAR ──────────────────────────────────────────────────────────────

    public function parseNavbar(string $html): array
    {
        $dom   = $this->dom($html);
        $xpath = new \DOMXPath($dom);

        $data = [
            'brand_name'    => '',
            'brand_tagline' => '',
            'brand_url'     => '/',
            'affil_name'    => '',
            'affil_sub'     => '',
            'affil_url'     => '#',
            'apply_url'     => '#',
            'apply_label'   => 'Apply Now',
            'socials'       => [],
            'topbar_left'   => [],
            'topbar_right'  => [],
            'nav_links'     => [],
        ];

        // Brand name & tagline
        $brandName    = $xpath->query('//*[@class="brand-name"]')->item(0);
        $brandTagline = $xpath->query('//*[@class="brand-tagline"]')->item(0);
        $brandLink    = $xpath->query('//*[contains(@class,"brand-main")]')->item(0);
        if ($brandName)    $data['brand_name']    = trim($brandName->nodeValue);
        if ($brandTagline) $data['brand_tagline'] = trim($brandTagline->nodeValue);
        if ($brandLink)    $data['brand_url']     = $brandLink->getAttribute('href');

        // Affiliation badge
        $affilName = $xpath->query('//*[@class="affil-name"]')->item(0);
        $affilSub  = $xpath->query('//*[@class="affil-sub"]')->item(0);
        $affilLink = $xpath->query('//*[contains(@class,"affil-badge")]')->item(0);
        if ($affilName) $data['affil_name'] = trim($affilName->nodeValue);
        if ($affilSub)  $data['affil_sub']  = trim($affilSub->nodeValue);
        if ($affilLink) $data['affil_url']  = $affilLink->getAttribute('href');

        // Apply Now button
        $applyBtn = $xpath->query('//*[contains(@class,"tb-apply")]')->item(0);
        if ($applyBtn) {
            $data['apply_url']   = $applyBtn->getAttribute('href');
            $data['apply_label'] = trim(preg_replace('/\s+/', ' ', strip_tags($applyBtn->nodeValue)));
        }

        // Social links (top bar socials)
        $socLinks = $xpath->query('//*[contains(@class,"tb-soc")]');
        foreach ($socLinks as $a) {
            $data['socials'][] = [
                'platform' => $a->getAttribute('title'),
                'url'      => $a->getAttribute('href'),
            ];
        }

        // Top-bar LEFT links
        $tbLeft = $xpath->query('//*[contains(@class,"tb-left")]//a[contains(@class,"tb-a")]');
        foreach ($tbLeft as $a) {
            $data['topbar_left'][] = [
                'label' => trim(preg_replace('/\s+/', ' ', strip_tags($a->nodeValue))),
                'url'   => $a->getAttribute('href'),
            ];
        }

        // Top-bar RIGHT links (tb-a only, skip admin & socials)
        $tbRight = $xpath->query('//*[contains(@class,"tb-right")]//a[contains(@class,"tb-a")]');
        foreach ($tbRight as $a) {
            $data['topbar_right'][] = [
                'label' => trim(preg_replace('/\s+/', ' ', strip_tags($a->nodeValue))),
                'url'   => $a->getAttribute('href'),
            ];
        }

        // Main nav links (skip mobile-only items)
        $navList = $xpath->query('//ul[@id="navList"]/li[not(contains(@class,"mobile-only"))]');
        foreach ($navList as $li) {
            $topLink = $xpath->query('.//a[contains(@class,"nav-link")]', $li)->item(0);
            if (!$topLink) continue;

            $topIconNode = $xpath->query('.//*[contains(concat(" ", normalize-space(@class), " "), " ico ") and not(contains(concat(" ", normalize-space(@class), " "), " caret-ico "))]', $topLink)->item(0);
            $topIconHtml = $topIconNode ? $dom->saveHTML($topIconNode) : '';

            $item = [
                'label'     => trim(preg_replace('/\s+/', ' ', strip_tags($topLink->nodeValue))),
                'url'       => $topLink->getAttribute('href'),
                'icon_html' => $topIconHtml,
                'children'  => [],
            ];

            $dropdown = $xpath->query('.//ul[contains(@class,"dropdown")]', $li)->item(0);
            if ($dropdown) {
                foreach ($dropdown->childNodes as $child) {
                    if ($child->nodeType !== XML_ELEMENT_NODE) continue;
                    if ($child->nodeName === 'li') {
                        $ca = $xpath->query('.//a', $child)->item(0);
                        if ($ca) {
                            $childIconNode = $xpath->query('.//*[contains(concat(" ", normalize-space(@class), " "), " ico ")]', $ca)->item(0);
                            $childIconHtml = $childIconNode ? $dom->saveHTML($childIconNode) : '';

                            $item['children'][] = [
                                'label'     => trim(preg_replace('/\s+/', ' ', strip_tags($ca->nodeValue))),
                                'url'       => $ca->getAttribute('href'),
                                'icon_html' => $childIconHtml,
                            ];
                        }
                    } elseif (str_contains($child->getAttribute('class') ?? '', 'drop-hr')) {
                        $item['children'][] = ['label' => '---', 'url' => '#'];
                    }
                }
            }

            if (empty($item['children'])) unset($item['children']);
            $data['nav_links'][] = $item;
        }

        return $data;
    }

    public function parseFooter(string $html): array
    {
        $dom   = $this->dom($html);
        $xpath = new \DOMXPath($dom);

        $data = [
            'brand_name'      => '',
            'brand_sub'       => '',
            'brand_url'       => '#',
            'about_text'      => '',
            'affil_name'      => '',
            'affil_sub'       => '',
            'affil_url'       => '#',
            'socials'         => [],
            'contact_address' => '',
            'contact_email'   => '',
            'contact_phone'   => '',
            'office_hours'    => '',
            'map_embed'       => '',
            'important_links' => [],
            'quick_links'     => [],
            'resource_links'  => [],
            'copyright'       => '',
            'developer'       => '',
        ];

        // Brand
        $brandName = $xpath->query('//*[@class="f-brand-name"]')->item(0);
        $brandSub  = $xpath->query('//*[@class="f-brand-sub"]')->item(0);
        $brandLink = $xpath->query('//*[@class="f-brand-logo"]')->item(0);
        if ($brandName) $data['brand_name'] = trim($brandName->nodeValue);
        if ($brandSub)  $data['brand_sub']  = trim($brandSub->nodeValue);
        if ($brandLink) $data['brand_url']  = $brandLink->getAttribute('href');

        // About paragraph
        $about = $xpath->query('//*[@class="f-about"]')->item(0);
        if ($about) $data['about_text'] = trim(preg_replace('/\s+/', ' ', $about->nodeValue));

        // Affiliation
        $affilName = $xpath->query('//*[@class="f-affil-name"]')->item(0);
        $affilSub  = $xpath->query('//*[@class="f-affil-sub"]')->item(0);
        $affilLink = $xpath->query('//*[@class="f-affil"]')->item(0);
        if ($affilName) $data['affil_name'] = trim($affilName->nodeValue);
        if ($affilSub)  $data['affil_sub']  = trim($affilSub->nodeValue);
        if ($affilLink) $data['affil_url']  = $affilLink->getAttribute('href');

        // Socials
        $socs = $xpath->query('//*[contains(@class,"f-soc")]');
        foreach ($socs as $a) {
            $data['socials'][] = [
                'platform' => $a->getAttribute('title'),
                'url'      => $a->getAttribute('href'),
            ];
        }

        // Contact items
        $contactItems = $xpath->query('//*[@class="f-ci"]');
        foreach ($contactItems as $li) {
            $label = trim($xpath->query('.//*[@class="f-ci-label"]', $li)->item(0)?->nodeValue ?? '');
            $val   = trim(preg_replace('/\s+/', ' ', $xpath->query('.//*[@class="f-ci-val"]', $li)->item(0)?->nodeValue ?? ''));
            if (stripos($label, 'address') !== false) $data['contact_address'] = $val;
            elseif (stripos($label, 'email') !== false) $data['contact_email'] = $val;
            elseif (stripos($label, 'phone') !== false) $data['contact_phone'] = $val;
        }

        // Office hours
        $hoursChip = $xpath->query('//*[@class="hours-chip"]')->item(0);
        if ($hoursChip) {
            $data['office_hours'] = trim(preg_replace('/\s+/', ' ', strip_tags($hoursChip->nodeValue)));
        }

        // Map embed src
        $mapIframe = $xpath->query('//iframe[not(ancestor::noscript)]')->item(0);
        if ($mapIframe) $data['map_embed'] = $mapIframe->getAttribute('src');

        // Link columns: important_links, quick_links, resource_links
        $columns = $xpath->query('//div[@class="f-grid"]/div');
        $colIdx  = 0;
        foreach ($columns as $col) {
            $links = $xpath->query('.//ul[@class="f-links"]/li/a', $col);
            $list  = [];
            foreach ($links as $a) {
                $list[] = [
                    'label' => trim(preg_replace('/\s+/', ' ', strip_tags($a->nodeValue))),
                    'url'   => $a->getAttribute('href'),
                ];
            }
            if (!empty($list)) {
                if ($colIdx === 0) $data['important_links'] = $list;
                elseif ($colIdx === 1) $data['quick_links'] = $list;
                elseif ($colIdx === 2) $data['resource_links'] = $list;
                $colIdx++;
            }
        }

        // Copyright & developer
        $ftBottom = $xpath->query('//*[@class="footer-bottom"]//p')->item(0);
        if ($ftBottom) {
            $data['copyright'] = trim(preg_replace('/\s+/', ' ', strip_tags($ftBottom->nodeValue)));
        }
        $devEl = $xpath->query('//*[@class="footer-bottom"]//*[contains(@class,"footer-bottom-inner")]//p[2]')->item(0);
        if (!$devEl) {
            $allP = $xpath->query('//*[@class="footer-bottom"]//p');
            if ($allP->length > 1) $devEl = $allP->item(1);
        }
        if ($devEl) $data['developer'] = trim(preg_replace('/\s+/', ' ', strip_tags($devEl->nodeValue)));

        return $data;
    }

    // ── WRITE-BACK ───────────────────────────────────────────────────────────

    /**
     * Write changed navbar content back to the template.html file.
     * Only touches the specific elements; preserves all SVG/markup.
     */
    public function writeNavbar(string $html, array $data): string
    {
        $html = $this->replaceText($html, 'class="brand-name"',    $data['brand_name']    ?? '');
        $html = $this->replaceText($html, 'class="brand-tagline"', $data['brand_tagline'] ?? '');
        $html = $this->replaceAttr($html, 'class="brand-main"',    'href', $data['brand_url'] ?? '/');
        $html = $this->replaceText($html, 'class="affil-name"',    $data['affil_name']    ?? '');
        $html = $this->replaceText($html, 'class="affil-sub"',     $data['affil_sub']     ?? '');
        $html = $this->replaceAttr($html, 'class="affil-badge"',   'href', $data['affil_url']  ?? '#');

        // Apply button label + url
        if (isset($data['apply_label'])) {
            // Replace href
            $html = preg_replace_callback(
                '/(<a[^>]+class="[^"]*tb-apply[^"]*"[^>]*href=")[^"]*("/[^>]*>)([\s\S]*?)(<\/a>)/U',
                function ($m) use ($data) {
                    $innerHtml = preg_replace('/>[^<]+Apply[^<]*</', '>' . htmlspecialchars($data['apply_label'] ?? 'Apply Now') . '<', $m[3]);
                    return $m[1] . htmlspecialchars($data['apply_url'] ?? '#') . $m[2] . $innerHtml . $m[4];
                },
                $html
            ) ?? $html;
        }

        // Social links
        if (!empty($data['socials'])) {
            $html = $this->updateSocialLinks($html, 'tb-soc', $data['socials']);
        }

        // Top-bar left links
        if (!empty($data['topbar_left'])) {
            $html = $this->updateSimpleLinks($html, 'tb-left', 'tb-a', $data['topbar_left']);
        }

        // Nav links
        if (!empty($data['nav_links'])) {
            $html = $this->rewriteNavLinks($html, $data['nav_links']);
        }

        return $html;
    }

    public function writeFooter(string $html, array $data): string
    {
        $html = $this->replaceText($html, 'class="f-brand-name"', $data['brand_name'] ?? '');
        $html = $this->replaceText($html, 'class="f-brand-sub"',  $data['brand_sub']  ?? '');
        $html = $this->replaceAttr($html, 'class="f-brand-logo"', 'href', $data['brand_url'] ?? '#');

        if (isset($data['about_text'])) {
            $html = preg_replace('/(<p\s+class="f-about">)([\s\S]*?)(<\/p>)/U',
                '$1' . htmlspecialchars($data['about_text']) . '$3', $html) ?? $html;
        }

        $html = $this->replaceText($html, 'class="f-affil-name"', $data['affil_name'] ?? '');
        $html = $this->replaceText($html, 'class="f-affil-sub"',  $data['affil_sub']  ?? '');
        $html = $this->replaceAttr($html, 'class="f-affil"',      'href', $data['affil_url'] ?? '#');

        if (!empty($data['socials'])) {
            $html = $this->updateSocialLinks($html, 'f-soc', $data['socials']);
        }

        // Contact items
        if (!empty($data['contact_address'])) $html = $this->updateContactItem($html, 'Address', $data['contact_address']);
        if (!empty($data['contact_email']))   $html = $this->updateContactItem($html, 'Email',   $data['contact_email']);
        if (!empty($data['contact_phone']))   $html = $this->updateContactItem($html, 'Phone',   $data['contact_phone']);

        // Office hours
        if (isset($data['office_hours'])) {
            $html = preg_replace(
                '/(<div\s+class="hours-chip">)([\s\S]*?)(<\/div>)/U',
                function () use ($data, $html) {}, // fallback below
                $html
            );
            // Simple replacement of text inside hours-chip
            $html = preg_replace_callback(
                '/(<div\s+class="hours-chip">)([\s\S]*?)(<\/div>)/U',
                function ($m) use ($data) {
                    // Keep icon SVG
                    $icon = '';
                    if (preg_match('/(<span[^>]*class="ico"[^>]*>[\s\S]*?<\/span>)/U', $m[2], $im)) {
                        $icon = $im[1];
                    }
                    return $m[1] . "\n            " . $icon . "\n            " . htmlspecialchars($data['office_hours']) . "\n          " . $m[3];
                },
                $html
            ) ?? $html;
        }

        // Copyright
        if (!empty($data['copyright'])) {
            $html = preg_replace_callback(
                '/(<p>©[\s\S]*?(?:<span[^>]+>[^<]*<\/span>[\s\S]*?)?)(All rights reserved\.?<\/p>|<\/p>)/U',
                function ($m) use ($data) {
                    // Preserve the currentYear span
                    $yearSpan = '';
                    if (preg_match('/(<span[^>]+id="currentYear"[^>]*>[^<]*<\/span>)/U', $m[0], $ym)) {
                        $yearSpan = $ym[1];
                    }
                    $text = htmlspecialchars($data['copyright']);
                    // Re-inject span if it was there
                    if ($yearSpan) {
                        $year = date('Y');
                        $text = preg_replace('/©\s*\d{4}/', '© ' . $yearSpan, $text);
                    }
                    return '<p>' . $text . '</p>';
                },
                $html
            ) ?? $html;
        }

        // Link columns: rewrite f-links lists in order (important, quick, resource)
        $columnLinks = array_filter([
            $data['important_links'] ?? null,
            $data['quick_links']     ?? null,
            $data['resource_links']  ?? null,
        ]);
        if (!empty($columnLinks)) {
            $html = $this->rewriteFooterLinkColumns($html, array_values($columnLinks));
        }

        return $html;
    }

    // ── PRIVATE HELPERS ──────────────────────────────────────────────────────

    private function dom(string $html): \DOMDocument
    {
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        return $dom;
    }

    private function replaceText(string $html, string $attrSnippet, string $newText): string
    {
        $safe = htmlspecialchars($newText);
        return preg_replace_callback(
            '/(<(?:span|div|a)\s[^>]*?' . preg_quote($attrSnippet, '/') . '[^>]*>)([\s\S]*?)(<\/(?:span|div|a)>)/U',
            fn($m) => $m[1] . $safe . $m[3],
            $html
        ) ?? $html;
    }

    private function replaceAttr(string $html, string $attrSnippet, string $attr, string $newVal): string
    {
        return preg_replace_callback(
            '/(<[a-z]+\s[^>]*?' . preg_quote($attrSnippet, '/') . '[^>]*?' . $attr . '=")[^"]*(")/U',
            fn($m) => $m[1] . htmlspecialchars($newVal) . $m[2],
            $html
        ) ?? $html;
    }

    private function updateSocialLinks(string $html, string $cssClass, array $socials): string
    {
        $idx = 0;
        return preg_replace_callback(
            '/(<a[^>]+class="[^"]*' . preg_quote($cssClass, '/') . '[^"]*"[^>]*href=")[^"]*(")/U',
            function ($m) use ($socials, &$idx) {
                $url = $socials[$idx]['url'] ?? '#';
                $idx++;
                return $m[1] . htmlspecialchars($url) . $m[2];
            },
            $html
        ) ?? $html;
    }

    private function updateSimpleLinks(string $html, string $containerClass, string $linkClass, array $links): string
    {
        // Only update href & text for links inside the matching container
        if (preg_match('/(<div[^>]+class="[^"]*' . preg_quote($containerClass, '/') . '[^"]*"[^>]*>)([\s\S]*?)(<\/div>)/U', $html, $m)) {
            $inner = $m[2];
            $idx   = 0;
            $inner = preg_replace_callback(
                '/(<a[^>]+class="[^"]*' . preg_quote($linkClass, '/') . '[^"]*"[^>]*href=")[^"]*("[^>]*>)([\s\S]*?)(<\/a>)/U',
                function ($am) use ($links, &$idx) {
                    if (!isset($links[$idx])) return $am[0];
                    $item     = $links[$idx++];
                    $newLabel = htmlspecialchars($item['label'] ?? '');
                    // Keep icon span
                    $icon = '';
                    if (preg_match('/(<span[^>]*class="ico"[^>]*>[\s\S]*?<\/span>)/U', $am[3], $im)) {
                        $icon = $im[1] . ' ';
                    }
                    return $am[1] . htmlspecialchars($item['url'] ?? '#') . $am[2] . $icon . $newLabel . $am[4];
                },
                $inner
            ) ?? $inner;
            $html = $m[1] . $inner . $m[3];  // This is naive but safe for simple containers
        }
        return $html;
    }

    private function rewriteNavLinks(string $html, array $links): string
    {
        // Build new nav-items HTML preserving mobile-only items
        $items = '';
        foreach ($links as $item) {
            $url   = htmlspecialchars($item['url'] ?? '#');
            $label = htmlspecialchars($item['label'] ?? '');
            $icon  = $item['icon_html'] ?? '';
            $children = $item['children'] ?? [];

            if (!empty($children)) {
                $dropItems = '';
                foreach ($children as $child) {
                    if (($child['label'] ?? '') === '---') {
                        $dropItems .= "\n            <div class=\"drop-hr\"></div>";
                    } else {
                        $childIcon = $child['icon_html'] ?? '';
                        $childFormatIcon = $childIcon ? $childIcon . ' ' : '';
                        $dropItems .= "\n            <li><a href=\"" . htmlspecialchars($child['url'] ?? '#') . "\" data-cms-type=\"link\">{$childFormatIcon}" . htmlspecialchars($child['label'] ?? '') . "</a></li>";
                    }
                }
                $formatIcon = $icon ? "\n            " . $icon : '';
                $items .= "\n        <li class=\"nav-item drop-wrap\">\n          <a class=\"nav-link has-drop\" href=\"{$url}\" aria-haspopup=\"true\" aria-expanded=\"false\" data-cms-type=\"link\">{$formatIcon}\n            <span class=\"ico-label\">{$label}</span>\n            <span class=\"ico caret-ico\"><svg viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2.5\" stroke-linecap=\"round\" stroke-linejoin=\"round\"><polyline points=\"6 9 12 15 18 9\"/></svg></span>\n          </a>\n          <ul class=\"dropdown\">{$dropItems}\n          </ul>\n        </li>";
            } else {
                $formatIcon = $icon ? "\n            " . $icon : '';
                $items .= "\n        <li class=\"nav-item\">\n          <a class=\"nav-link\" href=\"{$url}\" data-cms-type=\"link\">{$formatIcon}\n            <span class=\"ico-label\">{$label}</span>\n          </a>\n        </li>";
            }
        }

        // Replace the navList content between mobile-only drawer and mobile-only footer items
        return preg_replace_callback(
            '/(id="navList"[^>]*>)([\s\S]*?)(<li[^>]*class="[^"]*mobile-only[^"]*"[^>]*>\s*<div class="m-apply)/U',
            fn($m) => $m[1] . "\n        <!-- Mobile drawer header (auto-preserved) -->\n" . $this->extractMobileDrawer($m[2]) . $items . "\n\n        " . $m[3],
            $html
        ) ?? $html;
    }

    private function extractMobileDrawer(string $inner): string
    {
        if (preg_match('/(<li[^>]*class="[^"]*mobile-only[^"]*"[^>]*>[\s\S]*?<\/li>)/U', $inner, $m)) {
            return $m[1] . "\n";
        }
        return '';
    }

    private function updateContactItem(string $html, string $label, string $value): string
    {
        return preg_replace_callback(
            '/(<span[^>]+class="f-ci-label"[^>]*>' . preg_quote($label, '/') . '<\/span>\s*<span[^>]+class="f-ci-val"[^>]*>)([\s\S]*?)(<\/span>)/U',
            fn($m) => $m[1] . htmlspecialchars($value) . $m[3],
            $html
        ) ?? $html;
    }

    private function rewriteFooterLinkColumns(string $html, array $columns): string
    {
        $colIdx = 0;
        return preg_replace_callback(
            '/(<ul\s+class="f-links">)([\s\S]*?)(<\/ul>)/U',
            function ($m) use ($columns, &$colIdx) {
                if (!isset($columns[$colIdx])) { $colIdx++; return $m[0]; }
                $links = $columns[$colIdx++];
                $items = '';
                foreach ($links as $link) {
                    $url   = htmlspecialchars($link['url'] ?? '#');
                    $label = htmlspecialchars($link['label'] ?? '');
                    // Keep icon SVGs from first <li> (extract from original)
                    $items .= "\n            <li><a href=\"{$url}\" data-cms-type=\"link\">{$label}</a></li>";
                }
                return $m[1] . $items . "\n          " . $m[3];
            },
            $html
        ) ?? $html;
    }
}
