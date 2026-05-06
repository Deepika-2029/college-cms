# College CMS v3

A secure, modular, AI-compatible Content Management System built with Laravel 11.

## Directory Structure

```
project-root/
├── public_html/          ← WEB ROOT (domain points here)
│   ├── index.php         ← 5-line entry point
│   ├── .htaccess         ← security + rewrite rules
│   ├── admin/.htaccess   ← LiteSpeed shim (no PHP)
│   ├── media/            ← uploaded files (PHP blocked)
│   └── data/             ← static page cache (PHP blocked)
│       ├── pages/        ← SSR page JSON + HTML
│       └── api-cache/    ← API response cache
│
├── core/                 ← PRIVATE: Laravel app (never web-accessible)
│   ├── app/
│   │   ├── Http/Controllers/Admin/
│   │   │   └── AIController.php     ← NEW: AI export/import
│   │   ├── Services/
│   │   │   ├── AIExportService.php         ← NEW
│   │   │   ├── AIImportService.php         ← NEW
│   │   │   ├── ApiCacheService.php         ← NEW
│   │   │   ├── TemplateVariableValidator.php ← NEW
│   │   │   └── PluginScannerService.php    ← MODIFIED (syncToPublic removed)
│   │   └── ...
│   ├── bootstrap/
│   │   └── entry.php     ← MODIFIED: install guard, SCRIPT_NAME fix, Termux
│   ├── plugins/          ← SINGLE canonical plugin directory (private)
│   ├── .env              ← PRIVATE (above public_html/)
│   └── composer.json
│
└── system/               ← PRIVATE: installer + flags
    ├── setup.php         ← CLI-only installer
    ├── install.lock      ← created on install, checked on boot
    ├── termux.dev        ← touch to enable Termux/FAT32 mode
    └── start-termux.sh   ← Termux dev server
```

## Installation

### cPanel / Shared Hosting

1. Upload entire project above `public_html/`
2. Set domain root to `public_html/`
3. Run via SSH: `php system/setup.php`
4. Visit your domain

### Termux (Android)

```bash
cd /storage/emulated/0/college-cms
bash system/start-termux.sh
```

## What's New in v3

### Security
- `core/` moved above `public_html/` — `.env` is fully private
- `setup.php` moved to `system/` — not web-accessible, CLI-only
- `install.lock` checked on every boot — app shows error if not installed
- Plugin JS no longer synced to `public_html/plugins/` — served auth-gated
- `public_html/data/.htaccess` blocks PHP execution in static cache

### New Systems
- **AI Export/Import** (`AIController`, `AIExportService`, `AIImportService`)
  - Export any page or section as HTML + prompt for AI enhancement
  - Import AI-returned HTML with full security pipeline (sanitize → extract JS/CSS → validate variables)
  - Save imported HTML as a new plugin
- **Template Variable Validator** — `{{ variable }}` extraction, validation, dot notation
- **API Cache** (`ApiCacheService`) — file-based cache for public API, 10-minute TTL

### Architecture
- `syncToPublic()` removed from `PluginScannerService` — single plugin source of truth
- Plugin assets served via `/admin/plugin-asset/{plugin}/{file}` (auth-gated)
- Public pages: plugin CSS/JS inlined server-side by `PageRendererService` (unchanged)
- New route: `POST /admin/ai/export`, `POST /admin/ai/import`, `GET /admin/ai/rules`
- New migration: `media_folders`, `ai_import_log`, `detected_variables` columns

## AI Workflow

1. Open any page in the Builder
2. Click **Export for AI** — copies HTML + prompt to clipboard
3. Paste into ChatGPT or Claude
4. Paste AI response into **Import from AI** in the builder
5. Preview in sandboxed iframe
6. Save as a section or as a reusable plugin

## Plugin System

Plugins live in `core/plugins/{name}/`:
- `plugin.json` — manifest
- `fields.json` — admin form fields
- `component.html` — HTML template (supports `{{ variables }}`)
- `component.css` — styles (inlined by SSR renderer)
- `component.js` — interactivity (inlined by SSR renderer on public pages)

Plugin JS is **never** publicly accessible as a static file.
On public pages: inlined server-side by `PageRendererService`.
In admin builder: served via `/admin/plugin-asset/` (authenticated).

## Default Credentials

Set during `php system/setup.php` — you choose email and password.

**Change your password immediately after first login.**
