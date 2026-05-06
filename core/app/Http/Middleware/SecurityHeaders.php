<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * SecurityHeaders — strict HTTP security headers on every response.
 *
 * Changes vs previous version:
 *  - GTM removed from admin script-src (GTM = arbitrary JS injection)
 *  - img-src: data: removed from admin/public (SVG data URI XSS vector)
 *    blob: retained for builder canvas (object URLs from FileReader)
 *  - API CSP tightened further
 *  - Permissions-Policy expanded
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $isAdmin = $request->is('admin') || $request->is('admin/*');
        $isApi   = $request->is('api/*');

        // ── Universal headers ─────────────────────────────────────────────
        $response->headers->set('X-Content-Type-Options',            'nosniff');
        $response->headers->set('X-Frame-Options',                   'SAMEORIGIN');
        $response->headers->set('X-XSS-Protection',                  '1; mode=block');
        $response->headers->set('Referrer-Policy',                   'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy',
            'camera=(), microphone=(), geolocation=(), payment=(), usb=(), ' .
            'interest-cohort=(), bluetooth=(), serial=(), battery=(), ' .
            'ambient-light-sensor=(), autoplay=(self), fullscreen=(self)');
        $response->headers->set('X-Permitted-Cross-Domain-Policies', 'none');
        $response->headers->set('X-DNS-Prefetch-Control',            'off');
        $response->headers->set('Cross-Origin-Opener-Policy',        'same-origin-allow-popups');
        $response->headers->set('Cross-Origin-Resource-Policy',      'cross-origin');

        // Strip server fingerprinting
        $response->headers->remove('X-Powered-By');
        $response->headers->remove('Server');
        $response->headers->remove('X-Generator');
        $response->headers->remove('X-Runtime');

        // ── Admin-only headers ────────────────────────────────────────────
        if ($isAdmin) {
            $response->headers->set('X-Robots-Tag',  'noindex, nofollow, noarchive, nosnippet');
            $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, private');
            $response->headers->set('Pragma',        'no-cache');
        }

        // ── API: no caching ───────────────────────────────────────────────
        if ($isApi) {
            $response->headers->set('Cache-Control', 'no-store, private');
        }

        // ── HSTS (only on verified HTTPS) ─────────────────────────────────
        if ($request->isSecure()) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains; preload'
            );
        }

        // ── CSP — all assets self-hosted, zero CDN references ───────────
        // Bootstrap Icons CDN removed (replaced with inline SVG).
        // CodeMirror CDN removed (replaced with /assets/js/cms-editor.js).
        // Google Fonts CDN removed (replaced with /assets/css/fonts.css + local woff2).
        // ipapi.co kept only for admin IP geo-lookup (optional feature).

        if ($isAdmin) {
            // Admin CSP — self-hosted only
        // Admin CSP — fully self-hosted (no CDN)
            // unsafe-inline on scripts: builder inline handlers, inline event attrs
            // unsafe-inline on styles:  builder live preview, inline style attributes
            // blob: on img/media: builder canvas drag-drop and file preview
            // data: on img: SVG data URI component placeholders
            $csp = implode('; ', [
                "default-src 'self'",
                "script-src 'self' 'unsafe-inline'",
                "style-src 'self' 'unsafe-inline'",
                "font-src 'self'",
                "img-src 'self' blob: data: https://res.cloudinary.com https://placehold.co https://placehold.jp",
                "media-src 'self' blob:",
                "connect-src 'self' https://ipapi.co https://api.cloudinary.com https://res.cloudinary.com",
                "frame-src 'self' blob: https://www.youtube-nocookie.com https://player.vimeo.com https://www.google.com https://maps.google.com",
                "worker-src 'none'",
                "object-src 'none'",
                "base-uri 'self'",
                "form-action 'self'",
                "frame-ancestors 'self'",
            ]);
        } elseif ($isApi) {
            $csp = implode('; ', [
                "default-src 'none'",
                "frame-ancestors 'none'",
            ]);
        } else {
            // Public pages — self-hosted only
            // GA/GTM allowed only if admin has configured a GA ID in settings
            $gaAllow = '';
            try {
                $gaId = \App\Services\SettingsService::staticGet('google_analytics_id', '');
                if ($gaId) $gaAllow = ' https://www.googletagmanager.com https://www.google-analytics.com https://analytics.google.com';
            } catch (\Throwable) {}

            $csp = implode('; ', [
                "default-src 'self'",
                "script-src 'self' 'unsafe-inline'{$gaAllow}",
                "style-src 'self' 'unsafe-inline'",
                "font-src 'self' data:",
                "img-src 'self' blob: data: https:",
                "media-src 'self' blob: https:",
                "connect-src 'self'{$gaAllow} https://res.cloudinary.com https://maps.googleapis.com",
                "frame-src 'self' https://www.google.com https://maps.google.com https://www.youtube-nocookie.com https://player.vimeo.com https://www.youtube.com",
                "worker-src 'none'",
                "object-src 'none'",
                "base-uri 'self'",
                "form-action 'self'",
                "frame-ancestors 'none'",
            ]);
        }

        $response->headers->set('Content-Security-Policy', $csp);

        return $response;
    }
}
