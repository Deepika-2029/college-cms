<?php
/**
 * College CMS — Asset Downloader
 * Run once during setup to pull all external resources locally.
 * After this, the CMS serves everything self-hosted (zero external CDN requests).
 *
 * Usage (CLI):  php download-assets.php
 * Usage (web):  Only callable from setup.php — protected by install state check
 */

define('ASSET_BASE', dirname(__DIR__) . '/assets');

$downloads = [

    // ── CodeMirror 5.65.16 ──────────────────────────────────────────
    'js/codemirror/codemirror.min.js' =>
        'https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/codemirror.min.js',
    'css/codemirror.min.css' =>
        'https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/codemirror.min.css',
    'js/codemirror/mode/htmlmixed/htmlmixed.min.js' =>
        'https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/htmlmixed/htmlmixed.min.js',
    'js/codemirror/mode/xml/xml.min.js' =>
        'https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/xml/xml.min.js',
    'js/codemirror/mode/css/css.min.js' =>
        'https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/css/css.min.js',
    'js/codemirror/mode/javascript/javascript.min.js' =>
        'https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/javascript/javascript.min.js',
    'css/codemirror-monokai.min.css' =>
        'https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/theme/monokai.min.css',

    // ── Inter Font (latin subset woff2) ─────────────────────────────
    // These are the direct woff2 files; we also write the @font-face CSS manually
    'fonts/inter-400.woff2' =>
        'https://fonts.gstatic.com/s/inter/v13/UcCO3FwrK3iLTeHuS_fvQtMwCp50KnMw2boKoduKmMEVuLyfAZ9hiJ-Ek-_EeA.woff2',
    'fonts/inter-500.woff2' =>
        'https://fonts.gstatic.com/s/inter/v13/UcCO3FwrK3iLTeHuS_fvQtMwCp50KnMw2boKoduKmMEVuI6fAZ9hiJ-Ek-_EeA.woff2',
    'fonts/inter-600.woff2' =>
        'https://fonts.gstatic.com/s/inter/v13/UcCO3FwrK3iLTeHuS_fvQtMwCp50KnMw2boKoduKmMEVuGKYAZ9hiJ-Ek-_EeA.woff2',
    'fonts/inter-700.woff2' =>
        'https://fonts.gstatic.com/s/inter/v13/UcCO3FwrK3iLTeHuS_fvQtMwCp50KnMw2boKoduKmMEVuFuYAZ9hiJ-Ek-_EeA.woff2',
    'fonts/inter-800.woff2' =>
        'https://fonts.gstatic.com/s/inter/v13/UcCO3FwrK3iLTeHuS_fvQtMwCp50KnMw2boKoduKmMEVuDyZAZ9hiJ-Ek-_EeA.woff2',

    // ── Plus Jakarta Sans (login page) ──────────────────────────────
    'fonts/plus-jakarta-400.woff2' =>
        'https://fonts.gstatic.com/s/plusjakartasans/v8/LDIbaomQNQcsA88c7O9yZ4KMCoOg4KozySKCF7s_8Q.woff2',
    'fonts/plus-jakarta-600.woff2' =>
        'https://fonts.gstatic.com/s/plusjakartasans/v8/LDIbaomQNQcsA88c7O9yZ4KMCoOg4KozySKCp7s_8Q.woff2',
    'fonts/plus-jakarta-700.woff2' =>
        'https://fonts.gstatic.com/s/plusjakartasans/v8/LDIbaomQNQcsA88c7O9yZ4KMCoOg4KozySKCkrs_8Q.woff2',
    'fonts/plus-jakarta-800.woff2' =>
        'https://fonts.gstatic.com/s/plusjakartasans/v8/LDIbaomQNQcsA88c7O9yZ4KMCoOg4KozySKCh7s_8Q.woff2',

    // ── JetBrains Mono (code editor font) ───────────────────────────
    'fonts/jetbrains-mono-400.woff2' =>
        'https://fonts.gstatic.com/s/jetbrainsmono/v18/tDbY2o-flEEny0FZhsfKu5WU4zr3E_BX0PnT8RD8yKxjOlB1sUs.woff2',
    'fonts/jetbrains-mono-500.woff2' =>
        'https://fonts.gstatic.com/s/jetbrainsmono/v18/tDbY2o-flEEny0FZhsfKu5WU4zr3E_BX0PnT8RD8PLxjOlB1sUs.woff2',
];

$results = [];
$allOk   = true;

foreach ($downloads as $dest => $url) {
    $path = ASSET_BASE . '/' . $dest;
    $dir  = dirname($path);

    if (!is_dir($dir)) mkdir($dir, 0755, true);

    // Skip if already downloaded
    if (file_exists($path) && filesize($path) > 0) {
        $results[$dest] = ['status' => 'skipped', 'size' => filesize($path)];
        continue;
    }

    $ctx = stream_context_create(['http' => [
        'timeout' => 30,
        'user_agent' => 'Mozilla/5.0 (CMS Asset Downloader/1.0)',
        'follow_location' => true,
    ]]);

    $data = @file_get_contents($url, false, $ctx);
    if ($data === false || strlen($data) < 100) {
        $results[$dest] = ['status' => 'failed', 'url' => $url];
        $allOk = false;
        continue;
    }

    file_put_contents($path, $data);
    $results[$dest] = ['status' => 'ok', 'size' => strlen($data)];
}

// Always write the font-face CSS (self-hosted)
writeFontCss();
writePlaceholderSvg();

if (php_sapi_name() === 'cli') {
    foreach ($results as $dest => $r) {
        $icon = $r['status'] === 'ok' ? '✓' : ($r['status'] === 'skipped' ? '→' : '✗');
        $note = $r['status'] === 'ok'
            ? number_format($r['size']) . ' bytes'
            : ($r['status'] === 'skipped' ? 'already exists' : 'FAILED: ' . ($r['url'] ?? ''));
        echo "{$icon} {$dest} — {$note}\n";
    }
    echo $allOk ? "\n✅ All assets ready.\n" : "\n⚠ Some downloads failed — check network.\n";
}

return ['ok' => $allOk, 'results' => $results];

// ─────────────────────────────────────────────────────────────────────
function writeFontCss(): void
{
    $css = <<<'CSS'
/* ── Self-hosted Inter ───────────────────────────────────────────── */
@font-face {
  font-family: 'Inter';
  font-style:  normal;
  font-weight: 400;
  font-display: swap;
  src: url('/assets/fonts/inter-400.woff2') format('woff2');
}
@font-face {
  font-family: 'Inter';
  font-style:  normal;
  font-weight: 500;
  font-display: swap;
  src: url('/assets/fonts/inter-500.woff2') format('woff2');
}
@font-face {
  font-family: 'Inter';
  font-style:  normal;
  font-weight: 600;
  font-display: swap;
  src: url('/assets/fonts/inter-600.woff2') format('woff2');
}
@font-face {
  font-family: 'Inter';
  font-style:  normal;
  font-weight: 700;
  font-display: swap;
  src: url('/assets/fonts/inter-700.woff2') format('woff2');
}
@font-face {
  font-family: 'Inter';
  font-style:  normal;
  font-weight: 800;
  font-display: swap;
  src: url('/assets/fonts/inter-800.woff2') format('woff2');
}

/* ── Self-hosted Plus Jakarta Sans ──────────────────────────────── */
@font-face {
  font-family: 'Plus Jakarta Sans';
  font-style:  normal;
  font-weight: 400;
  font-display: swap;
  src: url('/assets/fonts/plus-jakarta-400.woff2') format('woff2');
}
@font-face {
  font-family: 'Plus Jakarta Sans';
  font-style:  normal;
  font-weight: 600;
  font-display: swap;
  src: url('/assets/fonts/plus-jakarta-600.woff2') format('woff2');
}
@font-face {
  font-family: 'Plus Jakarta Sans';
  font-style:  normal;
  font-weight: 700;
  font-display: swap;
  src: url('/assets/fonts/plus-jakarta-700.woff2') format('woff2');
}
@font-face {
  font-family: 'Plus Jakarta Sans';
  font-style:  normal;
  font-weight: 800;
  font-display: swap;
  src: url('/assets/fonts/plus-jakarta-800.woff2') format('woff2');
}

/* ── Self-hosted JetBrains Mono ──────────────────────────────────── */
@font-face {
  font-family: 'JetBrains Mono';
  font-style:  normal;
  font-weight: 400;
  font-display: swap;
  src: url('/assets/fonts/jetbrains-mono-400.woff2') format('woff2');
}
@font-face {
  font-family: 'JetBrains Mono';
  font-style:  normal;
  font-weight: 500;
  font-display: swap;
  src: url('/assets/fonts/jetbrains-mono-500.woff2') format('woff2');
}
CSS;

    file_put_contents(ASSET_BASE . '/css/fonts.css', $css);
}

function writePlaceholderSvg(): void
{
    // Inline SVG placeholder — replaces placehold.co/external image services
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="400" height="200" viewBox="0 0 400 200">'
         . '<rect width="400" height="200" fill="#e2e8f0"/>'
         . '<text x="200" y="105" text-anchor="middle" font-family="sans-serif" font-size="14" fill="#94a3b8">Image</text>'
         . '</svg>';
    file_put_contents(ASSET_BASE . '/img/placeholder.svg', $svg);

    $svg2 = '<svg xmlns="http://www.w3.org/2000/svg" width="200" height="100" viewBox="0 0 200 100">'
          . '<rect width="200" height="100" fill="#f1f5f9"/>'
          . '<text x="100" y="55" text-anchor="middle" font-family="sans-serif" font-size="12" fill="#94a3b8">Image</text>'
          . '</svg>';
    file_put_contents(ASSET_BASE . '/img/placeholder-sm.svg', $svg2);
}
