<?php

namespace App\Services;

/**
 * HtmlSanitizer — Cleans HTML to prevent XSS attacks
 *
 * Strategy:
 * 1. Whitelist-only tags (no forms — phishing vector)
 * 2. Whitelist-only attributes per tag
 * 3. Strip ALL event handlers (on*)
 * 4. Strip ALL data-* / x-* attributes (Alpine.js injection vectors)
 * 5. Block ALL data: URIs including data:image/svg+xml (SVG XSS)
 * 6. Block javascript:, vbscript:, livescript: protocols
 */
class HtmlSanitizer
{
    private static array $allowedTags = [
        'div', 'section', 'article', 'header', 'footer', 'nav', 'main', 'aside',
        'p', 'span', 'strong', 'em', 'b', 'i', 'u', 'code', 'pre',
        'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
        'blockquote', 'ul', 'ol', 'li', 'dl', 'dt', 'dd',
        'a', 'img', 'picture', 'source',
        'table', 'thead', 'tbody', 'tfoot', 'tr', 'th', 'td',
        'br', 'hr', 'figure', 'figcaption', 'time', 'address',
    ];

    private static array $allowedAttributes = [
        '*'       => ['class', 'id', 'title', 'style'],
        'a'       => ['href', 'title', 'rel', 'target'],
        'img'     => ['src', 'alt', 'loading', 'width', 'height'],
        'picture' => ['src', 'alt'],
        'source'  => ['src', 'srcset', 'type', 'media'],
        'th'      => ['scope', 'colspan', 'rowspan'],
        'td'      => ['colspan', 'rowspan'],
        'time'    => ['datetime'],
    ];

    public static function sanitize(string $html): string
    {
        if (empty($html)) return '';
        return extension_loaded('dom')
            ? self::sanitizeWithDom($html)
            : self::sanitizeWithRegex($html);
    }

    private static function sanitizeWithDom(string $html): string
    {
        $dom = new \DOMDocument();
        $dom->recover = true;
        libxml_use_internal_errors(true);

        if (!@$dom->loadHTML(
            '<?xml version="1.0" encoding="UTF-8"?><root>' . $html . '</root>',
            LIBXML_HTML_NOINPUT | LIBXML_NOERROR | LIBXML_NOWARNING
        )) {
            libxml_clear_errors();
            return htmlspecialchars($html, ENT_QUOTES, 'UTF-8');
        }
        libxml_clear_errors();

        $root = $dom->documentElement;
        if (!$root) return '';

        self::cleanElement($root);

        $out = '';
        foreach ($root->childNodes as $node) {
            if ($node->nodeType === XML_ELEMENT_NODE) {
                $out .= $dom->saveHTML($node);
            } elseif ($node->nodeType === XML_TEXT_NODE) {
                $out .= htmlspecialchars($node->nodeValue, ENT_QUOTES, 'UTF-8');
            }
        }
        return trim($out);
    }

    private static function cleanElement(\DOMElement $el): void
    {
        $tag = strtolower($el->nodeName);

        if (!in_array($tag, self::$allowedTags, true)) {
            while ($el->firstChild) {
                $el->parentNode?->insertBefore($el->firstChild, $el);
            }
            $el->parentNode?->removeChild($el);
            return;
        }

        $remove = [];
        foreach ($el->attributes as $attr) {
            $name = strtolower($attr->name);
            // Strip event handlers
            if (str_starts_with($name, 'on')) { $remove[] = $name; continue; }
            // Strip data-* and framework binding attrs (Alpine x-*, Vue :*, Svelte)
            if (str_starts_with($name, 'data-') || str_starts_with($name, 'x-') || str_starts_with($name, ':')) {
                $remove[] = $name; continue;
            }
            if (!self::isAttrAllowed($tag, $name)) { $remove[] = $name; continue; }
            if (!self::isValueSafe($name, $attr->value)) { $remove[] = $name; }
        }
        foreach ($remove as $a) $el->removeAttribute($a);

        foreach (iterator_to_array($el->childNodes) as $child) {
            if ($child->nodeType === XML_ELEMENT_NODE) self::cleanElement($child);
        }
    }

    private static function isAttrAllowed(string $tag, string $attr): bool
    {
        if (in_array($attr, self::$allowedAttributes['*'], true)) return true;
        return isset(self::$allowedAttributes[$tag]) && in_array($attr, self::$allowedAttributes[$tag], true);
    }

    private static function isValueSafe(string $attr, string $value): bool
    {
        // Normalise: strip whitespace and null bytes used to bypass filters
        $n = strtolower(preg_replace('/[\x00-\x20\s]+/', '', $value));

        // Block ALL data: URIs — this covers data:image/svg+xml XSS
        foreach (['javascript:', 'vbscript:', 'data:', 'livescript:', 'mocha:'] as $proto) {
            if (str_starts_with($n, $proto)) return false;
        }

        // URL attributes: only allow safe absolute or relative paths
        if (in_array($attr, ['href', 'src', 'action', 'formaction'], true)) {
            if ($value === '' || $value[0] === '#' || $value[0] === '/') return true;
            $l = strtolower(ltrim($value));
            return str_starts_with($l, 'http://') || str_starts_with($l, 'https://');
        }

        return true;
    }

    private static function sanitizeWithRegex(string $html): string
    {
        // Remove dangerous tags entirely
        $html = preg_replace(
            '/<(script|style|iframe|embed|object|form|input|select|textarea|button)[^>]*>.*?<\/\1>/is',
            '', $html
        );
        $html = preg_replace('/<(script|style|iframe|embed|object|form|input|select|textarea|button)[^>]*\/?>/i', '', $html);
        // Strip event handlers
        $html = preg_replace('/\s+on\w+\s*=\s*["\']?[^"\'>\s]*["\']?/i', '', $html);
        // Strip ALL data: URIs (covers SVG XSS)
        $html = preg_replace('/(href|src|action|formaction)\s*=\s*["\']?\s*(?:javascript|vbscript|data|livescript):[^"\'>\s]*["\']?/i', '', $html);
        // Strip data-* and framework binding attributes
        $html = preg_replace('/\s+(?:data-|x-|:)\S+\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s>]*)/i', '', $html);
        return $html;
    }
}
