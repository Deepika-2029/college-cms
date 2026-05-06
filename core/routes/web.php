<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Public\PageController;
use App\Http\Controllers\Public\ApiController;
use App\Http\Controllers\Public\ApiTokenController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DatabaseBuilderController;
use App\Http\Controllers\Admin\CrudController;

use App\Http\Controllers\Admin\MediaController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\ToolsController;

use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\ApiKeyController;
use App\Http\Controllers\Admin\WidgetController;
use App\Http\Controllers\Admin\TablePermissionController;
use App\Http\Controllers\Admin\ComponentFileController;
use App\Http\Controllers\Admin\CustomComponentController;
use App\Http\Controllers\Admin\VisualBuilderV3Controller;
use App\Http\Controllers\Admin\V3ComponentController;
use App\Http\Controllers\Admin\SiteIdentityController;
use App\Http\Controllers\Admin\NavigationController;


/*
|--------------------------------------------------------------------------
| Public API (read-only, key-authenticated)
|--------------------------------------------------------------------------
*/

Route::middleware('throttle:60,1')->group(function () {
    Route::get('/api/data/{table}', [ApiController::class, 'tableData'])
        ->where('table', '[a-zA-Z_][a-zA-Z0-9_]*')
        ->name('api.table');

    // Token vending — static pages call this to get a short-lived HMAC token.
    // No secret required; the registered table name IS the auth factor.
    Route::get('/api/token/{table}', [ApiTokenController::class, 'issue'])
        ->where('table', '[a-zA-Z_][a-zA-Z0-9_]*')
        ->name('api.token');
});

// Immutable Asset Redirect Route
Route::get('/c-asset/{id}', [ApiController::class, 'serveMediaAsset'])
    ->where('id', '[0-9]+')
    ->name('c-asset');

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/
$adminPrefix = env('ADMIN_PREFIX', 'admin');

Route::prefix($adminPrefix)->name('admin.')->group(function () {

    // Auth
    Route::get('login',  [AuthController::class, 'showLogin'])->name('login');
    Route::post('login', [AuthController::class, 'login'])->middleware('login.throttle');
    Route::post('logout',[AuthController::class, 'logout'])->name('logout');

    Route::get('/', function () {
        return \Illuminate\Support\Facades\Auth::check()
            ? redirect()->route('admin.dashboard')
            : redirect()->route('admin.login');
    })->name('root');

    // Admin static assets — public (the controller whitelists allowed files)
    Route::get('/assets/{file}',             [\App\Http\Controllers\Admin\AdminAssetController::class, 'serve'])
        ->where('file', '[a-zA-Z0-9._-]+')
        ->name('serve');
    Route::get('/page-asset/{path}',         [\App\Http\Controllers\Admin\AdminAssetController::class, 'pageAsset'])
        ->where('path', '.+')
        ->name('page-asset');
    Route::get('/crud-asset/{table}/{file}', [\App\Http\Controllers\Admin\AdminAssetController::class, 'crudAsset'])
        ->where(['table' => '[a-z0-9_]+', 'file' => '[a-zA-Z0-9._-]+'])
        ->name('crud-asset');

    // CSRF token refresh — called by JS auto-refresh to silently fix 419 Page Expired errors
    Route::get('/csrf-token', function () {
        return response()->json(['token' => csrf_token()]);
    })->name('csrf-token');

    Route::middleware(['admin.auth', 'admin.ratelimit'])->group(function () {





        // Dashboard
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');



        // Profile
        Route::prefix('profile')->name('profile.')->group(function () {
            Route::get('/',         [ProfileController::class, 'show'])->name('show');
            Route::put('/info',     [ProfileController::class, 'updateInfo'])->name('update-info');
            Route::put('/email',    [ProfileController::class, 'updateEmail'])->name('update-email');
            Route::put('/password', [ProfileController::class, 'updatePassword'])->name('update-password');
        });

        Route::get('/my-logs', [UserController::class, 'myLogs'])->name('my-logs');

        // ── SYSTEM FEATURES (Requires specific granular permissions) ──────────────
            // Database Builder
            Route::prefix('database')->name('database.')->middleware('permission:database_builder')->group(function () {
                Route::get('/',           [DatabaseBuilderController::class, 'index'])  ->name('index');
                Route::get('/builder',    [DatabaseBuilderController::class, 'builder'])->name('builder');
                Route::post('/run',       [DatabaseBuilderController::class, 'run'])    ->name('run');
                Route::post('/run-raw',   [DatabaseBuilderController::class, 'runRaw'])->name('run-raw');
                Route::delete('/{table}', [DatabaseBuilderController::class, 'drop'])  ->name('drop');

                // ── Alter Table & Stats (SuperAdmin only, extra throttle) ────────────
                Route::middleware(['role:super_admin', 'throttle:30,1'])->group(function () {
                    Route::get('/{table}/schema',             [DatabaseBuilderController::class, 'getSchema'])    ->name('schema');
                    Route::get('/{table}/stats',              [DatabaseBuilderController::class, 'getTableStats'])->name('stats');
                    Route::post('/{table}/regenerate-ui',     [DatabaseBuilderController::class, 'regenerateUi']) ->name('regenerate-ui');

                    Route::post('/{table}/link-page',         [DatabaseBuilderController::class, 'linkPage'])     ->name('link-page');
                    Route::delete('/{table}/link-page',       [DatabaseBuilderController::class, 'unlinkPage'])   ->name('unlink-page');
                });
            });



            // Tools
            Route::prefix('tools')->name('tools.')->middleware('permission:tools')->group(function () {
                Route::get('/',             [ToolsController::class, 'index'])->name('index');
                Route::post('/clear-cache', [ToolsController::class, 'clearCache'])->name('clear-cache');
                Route::post('/clear-log',   [ToolsController::class, 'clearLog'])->name('clear-log');
                Route::post('/sitemap',     [ToolsController::class, 'generateSitemap'])->name('sitemap');
                Route::post('/clean-media', [ToolsController::class, 'cleanOrphanedMedia'])->name('clean-media');
                Route::post('/optimize-media',[ToolsController::class, 'optimizeMedia'])->name('optimize-media');
                Route::get('/export-db',    [ToolsController::class, 'exportDb'])->name('export-db');
                Route::post('/sync-from-db',[ToolsController::class, 'syncFromDb'])->name('sync-from-db');
                Route::get('/migrate',      function() {
                    try {
                        \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
                        return "Migrations executed successfully! Please go back to the admin panel.";
                    } catch (\Exception $e) {
                        return "Migration failed: " . $e->getMessage();
                    }
                })->name('migrate');
            });



            // Audit Logs
            Route::prefix('audit')->name('audit.')->middleware('permission:audit_logs')->group(function () {
                Route::get('/',                           [AuditLogController::class, 'index'])->name('index');
                Route::delete('/clear',                   [AuditLogController::class, 'clear'])->name('clear');
                Route::post('/block-ip',                  [AuditLogController::class, 'blockIp'])->name('block-ip');
                Route::delete('/blocked-ips/{blockedIp}', [AuditLogController::class, 'unblockIp'])->name('unblock-ip');
            });



            // Settings
            Route::prefix('settings')->name('settings.')->middleware('permission:settings')->group(function () {
                Route::get('/',                 [SettingsController::class, 'index'])->name('index');
                Route::post('/save',            [SettingsController::class, 'save'])->name('save');
                Route::post('/test-cloudinary', [SettingsController::class, 'testCloudinary'])->name('test-cloudinary');
                Route::post('/theme',           [SettingsController::class, 'switchTheme'])->name('theme');
            });
            Route::middleware('permission:settings')->get('media-config', [SettingsController::class, 'mediaConfig'])->name('media.config');

            // API Keys
            Route::prefix('api-keys')->name('api-keys.')->middleware('permission:api_keys')->group(function () {
                Route::get('/',                [ApiKeyController::class, 'index'])->name('index');
                Route::post('/',               [ApiKeyController::class, 'store'])->name('store');
                Route::post('/{apiKey}/toggle',[ApiKeyController::class, 'toggle'])->name('toggle');
                Route::delete('/{apiKey}',     [ApiKeyController::class, 'destroy'])->name('destroy');
            });

            // User management (super_admin level)
            Route::middleware('permission:advanced_users')->group(function () {
                Route::post('/users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');
                Route::post('/users/{user}/ip',             [UserController::class, 'addIp'])->name('users.add-ip');
                Route::delete('/users/{user}/ip/{ipEntry}', [UserController::class, 'removeIp'])->name('users.remove-ip');
            });

            // Table Permissions
            Route::prefix('permissions')->name('permissions.')->middleware('permission:advanced_users')->group(function () {
                Route::get('/', [TablePermissionController::class, 'index'])->name('index');
                Route::post('/toggle', [TablePermissionController::class, 'toggle'])->name('toggle');
                Route::get('/{user}', [TablePermissionController::class, 'show'])->name('user');
                Route::post('/{user}/save', [TablePermissionController::class, 'save'])->name('save');
                Route::post('/{user}/grant-all', [TablePermissionController::class, 'grantAll'])->name('grant-all');
                Route::post('/{user}/revoke-all', [TablePermissionController::class, 'revokeAll'])->name('revoke-all');
            });

            Route::prefix('site-identity')->name('site-identity.')->middleware('permission:settings')->group(function () {
                Route::get('/',                    [SiteIdentityController::class, 'index'])->name('index');
                Route::post('/save',               [SiteIdentityController::class, 'save'])->name('save');
                // Theme Palette API
                Route::get('/themes',              [SiteIdentityController::class, 'getThemes'])->name('themes');
                Route::post('/themes/save',        [SiteIdentityController::class, 'saveTheme'])->name('theme.save');
                Route::post('/themes/activate',    [SiteIdentityController::class, 'activateTheme'])->name('theme.activate');
                Route::delete('/themes/{id}',      [SiteIdentityController::class, 'deleteTheme'])->name('theme.delete');
            });

            // Navigation
            Route::prefix('navigation')->name('navigation.')->middleware('permission:settings')->group(function () {
                Route::get('/',               [NavigationController::class, 'index'])->name('index');
                Route::post('/save-layout',   [NavigationController::class, 'saveLayout'])->name('save-layout');
                Route::post('/save',          [NavigationController::class, 'save'])->name('save');
                Route::post('/save-content',  [NavigationController::class, 'saveContent'])->name('save-content');
                Route::get('/json',           [NavigationController::class, 'getJson'])->name('json');
            });

        // ── SUPER ADMIN + ADMIN (with granular permission gates) ──────────────
        Route::middleware('role:super_admin,admin')->group(function () {



            // ── Visual Builder V3 (Live HTML DOM Editor) ───────────────────────
            Route::prefix('visual-builder-v3')->name('vbuilder3.')->middleware('permission:page_builder')->group(function () {
                Route::get('/pages',             [VisualBuilderV3Controller::class, 'pages'])->name('pages');
                Route::get('/pages/new',         [VisualBuilderV3Controller::class, 'editPage'])->name('page.new');
                Route::get('/pages/{slug}/edit', [VisualBuilderV3Controller::class, 'editPage'])->name('page.edit');
                Route::post('/pages/save',       [VisualBuilderV3Controller::class, 'savePage'])->name('page.save');
                Route::post('/pages/sync-all',   [VisualBuilderV3Controller::class, 'syncAll'])->name('page.sync-all');
                Route::delete('/pages/{id}',     [VisualBuilderV3Controller::class, 'destroyPage'])->name('page.destroy');
                Route::post('/pages/{id}/set-home', [VisualBuilderV3Controller::class, 'setHome'])->name('page.set-home');

                // V3 Component Library

                Route::get('/components/list', [V3ComponentController::class, 'list'])->name('components.list');
                Route::post('/components', [V3ComponentController::class, 'store'])->name('components.store');
                Route::post('/components/{id}', [V3ComponentController::class, 'update'])->name('components.update');
                Route::delete('/components/{id}', [V3ComponentController::class, 'destroy'])->name('components.destroy');
            });

            // ── Custom Components ─────────────────────────────────────────────
            Route::prefix('custom-components')->name('custom-components.')->middleware('permission:page_builder')->group(function () {
                Route::get('/',         [CustomComponentController::class, 'index'])->name('index');
                Route::post('/',        [CustomComponentController::class, 'store'])->name('store');
                Route::get('/{id}',     [CustomComponentController::class, 'show'])->name('show')->where('id','[0-9]+');
                Route::put('/{id}',     [CustomComponentController::class, 'update'])->name('update')->where('id','[0-9]+');
                Route::delete('/{id}',  [CustomComponentController::class, 'destroy'])->name('destroy')->where('id','[0-9]+');
                Route::post('/{id}/toggle', [CustomComponentController::class, 'toggle'])->name('toggle')->where('id','[0-9]+');
                Route::post('/seed',    [CustomComponentController::class, 'seed'])->name('seed');
            });

            // ── File-Based Components ─────────────────────────────────────────
            Route::prefix('components')->name('components.')->middleware('permission:page_builder')->group(function () {
                Route::get('/editor',             [ComponentFileController::class, 'editor'])->name('editor');
                Route::get('/files',              [ComponentFileController::class, 'index'])->name('files.index');
                Route::post('/files',             [ComponentFileController::class, 'store'])->name('files.store');
                Route::get('/files/{slug}',       [ComponentFileController::class, 'show'])->name('files.show')->where('slug','[a-z0-9\-]+');
                Route::put('/files/{slug}',       [ComponentFileController::class, 'update'])->name('files.update')->where('slug','[a-z0-9\-]+');
                Route::delete('/files/{slug}',    [ComponentFileController::class, 'destroy'])->name('files.destroy')->where('slug','[a-z0-9\-]+');
                Route::get('/files/{slug}/asset/{asset}', [ComponentFileController::class, 'serveAsset'])->name('files.asset')->where('slug','[a-z0-9\-]+')->where('asset','[a-z0-9\-\.]+');
            });

            // ── Users ─────────────────────────────────────────────────────────
            Route::prefix('users')->name('users.')->middleware('permission:user_management')->group(function () {
                Route::get('/',              [UserController::class, 'index'])->name('index');
                Route::get('/create',        [UserController::class, 'create'])->name('create');
                Route::post('/store',        [UserController::class, 'store'])->name('store');
                Route::get('/{user}/edit',   [UserController::class, 'edit'])->name('edit');
                Route::put('/{user}/update', [UserController::class, 'update'])->name('update');
                Route::delete('/{user}',     [UserController::class, 'destroy'])->name('destroy');
                Route::post('/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('toggle-status');
            });
        });

        // ── ALL AUTHENTICATED ─────────────────────────────────────────────

        // ── Media (requires media permission) ─────────────────────────────
        Route::prefix('media')->name('media.')->middleware('permission:media')->group(function () {
            Route::get('/',              [MediaController::class, 'index'])->name('index');
            Route::get('/json',          [MediaController::class, 'json'])->name('json');
            Route::get('/usage/{id}',    [MediaController::class, 'usage'])->name('usage');
            Route::post('/upload',       [MediaController::class, 'upload'])->name('upload');
            Route::post('/quick-upload', [MediaController::class, 'quickUpload'])->name('quick-upload');
            Route::post('/merge',        [MediaController::class, 'merge'])->name('merge');
            Route::post('/{id}/replace', [MediaController::class, 'replace'])->name('replace');
            Route::post('/{id}/optimize',[MediaController::class, 'optimize'])->name('optimize');
            Route::put('/{id}',          [MediaController::class, 'update'])->name('update');
            Route::delete('/{id}',       [MediaController::class, 'destroy'])->name('destroy');
        });

        // CRUD
        Route::prefix('crud/{table}')->name('crud.')->group(function () {
            Route::get('/',               [CrudController::class, 'index'])->name('index');
            Route::get('/create',         [CrudController::class, 'create'])->name('create');
            Route::post('/store',         [CrudController::class, 'store'])->name('store');
            Route::get('/ui/edit',        [CrudController::class, 'editUi'])->name('ui.edit');
            Route::post('/ui/save',       [CrudController::class, 'saveUi'])->name('ui.save');
            Route::get('/{id}/edit',      [CrudController::class, 'edit'])->name('edit')->where('id','[0-9]+');
            Route::put('/{id}/update',    [CrudController::class, 'update'])->name('update')->where('id','[0-9]+');
            Route::delete('/{id}/delete', [CrudController::class, 'destroy'])->name('destroy')->where('id','[0-9]+');
        });
    });
});

/*
|--------------------------------------------------------------------------
| CSP Violation Report Endpoint
|--------------------------------------------------------------------------
*/
Route::post("/{$adminPrefix}/csp-report", function (\Illuminate\Http\Request $request) {
    $report = $request->input('csp-report', $request->all());
    if (!empty($report)) {
        \Illuminate\Support\Facades\Log::channel('single')->warning('CSP Violation', [
            'blocked'  => $report['blocked-uri']          ?? $report['blockedURI']         ?? '?',
            'violated' => $report['violated-directive']   ?? $report['violatedDirective']  ?? '?',
            'document' => $report['document-uri']         ?? $report['documentURI']        ?? '?',
            'source'   => $report['source-file']          ?? $report['sourceFile']         ?? '?',
            'ip'       => $request->ip(),
        ]);
    }
    return response()->noContent();
})->name('csp.report');

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::get('/', [PageController::class, 'home'])->name('home');

// Setup / Installation
Route::get('/install', [\App\Http\Controllers\InstallController::class, 'index'])->name('install.index');
Route::post('/install', [\App\Http\Controllers\InstallController::class, 'process'])->name('install.process');

Route::get('/media/{filename}', [MediaController::class, 'serve'])
    ->where('filename', '[^/]+')
    ->name('media.serve');

// Health check
Route::get('/up', function () {
    $installed = file_exists(defined('CMS_SYSTEM_ROOT') ? CMS_SYSTEM_ROOT . '/install.lock' : '');
    return response()->json([
        'status'    => 'ok',
        'installed' => $installed,
        'timestamp' => now()->toIso8601String(),
    ]);
})->name('health');

// Catch-all — must be last
Route::get('/{slug}', [PageController::class, 'show'])
    ->where('slug', '^(?!' . preg_quote($adminPrefix, '/') . '|media|storage|up|api)([a-z0-9][a-z0-9\-]*)$')
    ->name('page');
