<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * SanitizeInput — strips null bytes and trims all string inputs.
 * Protects against null-byte injection and whitespace-based bypasses.
 */
class SanitizeInput
{
    private array $except = [
        // Passwords: must not be trimmed (leading/trailing spaces are valid password chars)
        'password', 'password_confirmation', 'current_password', 'new_password', 'new_password_confirmation',
        // Rich content: trimming/stripping breaks JSON, HTML, CSS
        'content', 'html', 'global_css', 'rows', 'sections',
        // Meta fields with intentional whitespace
        'meta_description', 'og_image', 'custom_html', 'body', 'description',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $input = $request->all();
        array_walk_recursive($input, function (&$value, $key) {
            if (is_string($value) && ! in_array($key, $this->except, true)) {
                // Remove null bytes (path traversal prevention)
                $value = str_replace("\0", '', $value);
                // Remove control characters except newline/tab
                $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $value);
                $value = trim($value);
            }
        });
        $request->merge($input);

        return $next($request);
    }
}
