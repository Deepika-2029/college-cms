<?php
/**
 * College CMS — PHP Front Controller  v4.0
 *
 * ┌─────────────────────────────────────────────────────────┐
 * │  PUBLIC VISITORS: Apache serves pages directly via      │
 * │  mod_rewrite — this file is NEVER called for them.      │
 * │                                                         │
 * │  This file is ONLY reached for:                         │
 * │    /admin/*   → Laravel auth + admin panel              │
 * │    /api/*     → Laravel API endpoints                   │
 * │    Unknown    → Laravel 404 handler                     │
 * └─────────────────────────────────────────────────────────┘
 *
 * Security: Do NOT add public page serving logic here.
 * Static pages are compiled by the CMS and served zero-PHP by Apache.
 */

// ── Hard security check ────────────────────────────────────────────────────
// If somehow a direct /frontend_public_pages/ URL sneaks past .htaccess, kill it here.
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/';
if (preg_match('#^/frontend_public_pages(/|$)#', $uri)) {
    http_response_code(403);
    exit('Forbidden');
}

// ── Block direct path traversal attempts ──────────────────────────────────
if (preg_match('#\.\.|%2e%2e|%252e|/\.|\./#i', $uri)) {
    http_response_code(400);
    exit('Bad Request');
}

// ── Boot Laravel ───────────────────────────────────────────────────────────
define('CMS_PUBLIC_ROOT', __DIR__);

// Support both: local dev (core/ inside project) and cPanel (core/ above web root)
$corePath = file_exists(__DIR__ . '/core/bootstrap/entry.php')
    ? __DIR__ . '/core/bootstrap/entry.php'
    : dirname(__DIR__) . '/core/bootstrap/entry.php';

if (!file_exists($corePath)) {
    http_response_code(503);
    exit('CMS core not found. Please check installation.');
}

require $corePath;
