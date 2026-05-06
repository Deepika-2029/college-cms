<?php

namespace App\Services;

/**
 * TemplateVariableValidator
 * ─────────────────────────
 * Handles {{ variable }} extraction, validation, and safe substitution
 * for plugin HTML templates and AI-imported HTML.
 *
 * Security rules:
 *  - Variables must match /^[a-zA-Z_][a-zA-Z0-9_.]{0,99}$/
 *  - Blocked names: prototype pollution + dangerous browser globals
 *  - All substituted values are HTML-escaped
 *  - Unknown variables render as empty string (never echo variable names)
 *  - Dot notation supported: {{ user.name }} → $data['user']['name']
 */
class TemplateVariableValidator
{
    private const SAFE_VARIABLE = '/^[a-zA-Z_][a-zA-Z0-9_.]{0,99}$/';

    private const BLOCKED = [
        '__proto__', 'constructor', 'prototype', 'eval',
        'window', 'document', 'globalThis', 'self', 'top', 'parent',
        'location', 'navigator', 'fetch', 'XMLHttpRequest',
    ];

    /**
     * Extract all {{ variable }} references from a template string.
     * Returns array of raw variable names found (may include unsafe names).
     */
    public static function extract(string $template): array
    {
        preg_match_all('/\{\{\s*([^}]+?)\s*\}\}/', $template, $m);
        return array_unique(array_map('trim', $m[1]));
    }

    /**
     * Validate a single variable name is safe to use.
     */
    public static function validate(string $varName): bool
    {
        if (!preg_match(self::SAFE_VARIABLE, $varName)) return false;
        foreach (self::BLOCKED as $blocked) {
            if (stripos($varName, $blocked) !== false) return false;
        }
        return true;
    }

    /**
     * Validate and return only safe variable names from a list.
     */
    public static function filterSafe(array $vars): array
    {
        return array_values(array_filter($vars, [self::class, 'validate']));
    }

    /**
     * Substitute {{ variables }} in a template with values from $data.
     * - Unknown variables → empty string
     * - Unsafe variable names → empty string (never rendered)
     * - All values HTML-escaped before output
     */
    public static function render(string $template, array $data): string
    {
        return preg_replace_callback(
            '/\{\{\s*([^}]+?)\s*\}\}/',
            function ($m) use ($data) {
                $key = trim($m[1]);
                if (!self::validate($key)) return '';
                $val = self::getValue($data, $key);
                return htmlspecialchars((string) $val, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            },
            $template
        );
    }

    /**
     * Extract variables from a template and return metadata for the builder UI.
     * Returns: [['name' => 'site_name', 'safe' => true, 'dotted' => false], ...]
     */
    public static function analyze(string $template): array
    {
        $vars = self::extract($template);
        $result = [];
        foreach ($vars as $var) {
            $result[] = [
                'name'   => $var,
                'safe'   => self::validate($var),
                'dotted' => str_contains($var, '.'),
                'parts'  => explode('.', $var),
            ];
        }
        return $result;
    }

    // ── Private ───────────────────────────────────────────────────────────

    private static function getValue(array $data, string $key): mixed
    {
        $parts = explode('.', $key);
        $val   = $data;
        foreach ($parts as $part) {
            if (!is_array($val) || !array_key_exists($part, $val)) return '';
            $val = $val[$part];
        }
        return is_scalar($val) ? $val : '';
    }
}
