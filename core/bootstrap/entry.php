<?php

/**
 * College CMS — Single Entry Point v3.1
 * ─────────────────────────────────────────────────────────────────────
 *
 * FIX v3.1: "headers already sent" on Termux / php -S
 *   Root cause: error_reporting(E_ALL) + display_errors=On (default in
 *   php -S) caused PHP warnings from mkdir/flock to be printed BEFORE
 *   http_response_code() in the install guard, corrupting output.
 *
 * Fixes applied:
 *   1. ob_start() at the very top — buffers ALL output before headers
 *   2. error_reporting changed to suppress notices/warnings in web mode
 *      (errors still logged; display_errors forced Off immediately)
 *   3. ob_end_clean() on the install-guard exit path so no garbage
 *      precedes the 503 HTML
 *   4. All mkdir/flock calls already use @ — kept as-is
 */

// ── CRITICAL: Start output buffer FIRST, before anything can print ────
// This prevents any stray warning from reaching the browser before headers.
if (PHP_SAPI !== 'cli') {
    ob_start();
}

// ── Silence display of errors — they go to log, not browser ──────────
// Must be done before any code that might warn (mkdir, flock, etc.)
if (PHP_SAPI !== 'cli') {
    @ini_set('display_errors', '0');
    @ini_set('display_startup_errors', '0');
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED & ~E_NOTICE & ~E_WARNING);
} else {
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);
}

// ── Path constants ────────────────────────────────────────────────────
define('LARAVEL_START',    microtime(true));
define('CMS_APP_ROOT',     dirname(__DIR__));                         // core/
define('CMS_PROJECT_ROOT', dirname(__DIR__, 2));                      // project root
define('CMS_PUBLIC_ROOT',  defined('CMS_PUBLIC_ROOT')
    ? CMS_PUBLIC_ROOT
    : CMS_PROJECT_ROOT);                             // project root
define('CMS_SYSTEM_ROOT',  CMS_PROJECT_ROOT . '/system');            // system/

// ── Install guard ─────────────────────────────────────────────────────
// Show a clean "Not Installed" page if install.lock is missing.
// Skip during artisan CLI (migrate, key:generate, etc.)


if (PHP_SAPI !== 'cli' && !file_exists(CMS_SYSTEM_ROOT . '/install.lock')) {
    if (!file_exists(CMS_APP_ROOT . '/.env') || !file_exists(CMS_APP_ROOT . '/storage/app/.setup_done')) {
        if (ob_get_level() > 0) ob_end_clean();
        header('HTTP/1.1 503 Service Unavailable');
        die("<!DOCTYPE html><html><head><title>System Not Installed</title><style>body{font-family:sans-serif;background:#07070f;color:#ededf8;display:flex;align-items:center;justify-content:center;height:100vh} .box{background:#0e0e1a;padding:2rem;border-radius:12px;text-align:center}</style></head><body><div class='box'><h1 style='color:#f87171'>Installation Required</h1><p>The system is not installed. Please navigate to <code>/setup.php</code> to run the installer.</p></div></body></html>");
    }
}

// ── CLI-server SCRIPT_NAME fix (Termux / php -S) ──────────────────────
// Fixes SCRIPT_NAME so Laravel doesn't strip /admin from REQUEST_URI.
if (PHP_SAPI === 'cli-server') {
    $_SERVER['SCRIPT_NAME']     = '/index.php';
    $_SERVER['SCRIPT_FILENAME'] = CMS_PUBLIC_ROOT . '/index.php';
    $_SERVER['PHP_SELF']        = '/index.php';

    // Serve static files directly (CSS, JS, images, fonts)
    $uri  = urldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/');
    $file = CMS_PUBLIC_ROOT . $uri;
    if ($uri !== '/' && file_exists($file) && !is_dir($file) && pathinfo($file, PATHINFO_EXTENSION) !== 'php') {
        // Discard buffer so built-in server returns the raw file
        if (ob_get_level() > 0) ob_end_clean();
        return false;
    }
}



// ── Directory Readiness ───────────────────────────────────────────────
(function () {
    // Ensure storage dirs always exist
    foreach ([
        CMS_APP_ROOT . '/storage/framework/sessions',
        CMS_APP_ROOT . '/storage/framework/cache/data',
        CMS_APP_ROOT . '/storage/framework/views',
        CMS_APP_ROOT . '/storage/logs',
        CMS_APP_ROOT . '/storage/app',
        CMS_APP_ROOT . '/storage/app/rate_limits',
        CMS_APP_ROOT . '/storage/tmp',
        CMS_APP_ROOT . '/bootstrap/cache',
    ] as $d) {
        @mkdir($d, 0755, true);
    }
})();

// ── Maintenance mode ──────────────────────────────────────────────────
if (file_exists($m = CMS_APP_ROOT . '/storage/framework/maintenance.php')) {
    require $m;
}

// ── Pre-Laravel rate limiting ─────────────────────────────────────────
// File-based, runs before Laravel boots — stops flood attacks early.
(function () {
    $ip     = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['HTTP_X_REAL_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $ip     = preg_replace('/[^0-9a-f.:,]/', '', strtolower(explode(',', $ip)[0]));
    $uri    = $_SERVER['REQUEST_URI'] ?? '';
    $method = $_SERVER['REQUEST_METHOD'] ?? '';

    $isLoginPost = $method === 'POST' && str_contains($uri, '/admin/login');
    $isAdminReq  = str_starts_with(ltrim(parse_url($uri, PHP_URL_PATH) ?? '', '/'), 'admin');

    $rlDir = CMS_APP_ROOT . '/storage/app/rate_limits';
    if (!is_dir($rlDir)) @mkdir($rlDir, 0750, true);

    $limit = $isLoginPost ? 30 : ($isAdminReq ? 200 : 0);
    if (!$limit) return;

    $key  = $isLoginPost ? 'login' : 'admin';
    $file = $rlDir . '/' . md5("{$key}|{$ip}") . '.txt';
    $now  = time();
    $data = @file_get_contents($file) ?: '';
    $hits = array_values(array_filter(explode(',', $data), fn($t) => $t && ($now - (int)$t) < 60));

    if (count($hits) >= $limit) {
        if (ob_get_level() > 0) ob_end_clean();
        http_response_code(429);
        header('Retry-After: 60');
        header('Content-Type: text/html; charset=UTF-8');
        echo '<!DOCTYPE html><html><head><title>429 Too Many Requests</title>'
           . '<style>body{font-family:system-ui,sans-serif;background:#0f172a;color:#e2e8f0;'
           . 'display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0}'
           . '.c{text-align:center}.n{font-size:4rem;color:#6366f1;font-weight:700}'
           . '</style></head><body><div class="c">'
           . '<div class="n">429</div>'
           . '<p>' . ($isLoginPost ? 'Too many login attempts.' : 'Too many requests.') . '</p>'
           . '<p style="color:#64748b;font-size:.85rem">Please wait 60 seconds and try again.</p>'
           . '</div></body></html>';
        exit;
    }
    $hits[] = $now;
    @file_put_contents($file, implode(',', array_slice($hits, -$limit)));
})();

// ── Flush output buffer into Laravel's response pipeline ─────────────
// Laravel will handle output — discard anything buffered so far
// (should be empty if no errors, but safety net for stray warnings)
if (PHP_SAPI !== 'cli' && ob_get_level() > 0) {
    $buffered = ob_get_clean();
    // If anything was captured (stray warning), log it but don't send it
    if ($buffered !== '' && $buffered !== false) {
        @error_log('[CMS entry.php] Buffered output discarded: ' . substr(strip_tags($buffered), 0, 300));
    }
}

// ── Boot Laravel ──────────────────────────────────────────────────────
require CMS_APP_ROOT . '/vendor/autoload.php';

use Illuminate\Http\Request;

$app = require_once CMS_APP_ROOT . '/bootstrap/app.php';
$app->handleRequest(Request::capture());
