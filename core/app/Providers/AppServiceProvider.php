<?php

namespace App\Providers;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use App\Services\CloudinaryService;
use App\Services\CrudUiGeneratorService;
use App\Services\JsonGeneratorService;
use App\Services\PageRendererService;
use App\Services\SettingsService;
use App\Services\TemplateVariableValidator;
use App\Services\ApiCacheService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(\App\Services\StaticPageBuilder::class);
        $this->app->singleton(CloudinaryService::class, function ($app) {
            return new CloudinaryService($app->make(SettingsService::class));
        });

        $this->app->singleton(JsonGeneratorService::class);
        $this->app->singleton(CrudUiGeneratorService::class);
        $this->app->singleton(SettingsService::class);
        $this->app->singleton(\App\Services\ComponentRenderer::class);
        $this->app->singleton(TemplateVariableValidator::class);
        $this->app->singleton(ApiCacheService::class);
    }

    public function boot(): void
    {
        @ini_set('upload_max_filesize', '32M');
        @ini_set('post_max_size',       '34M');
        @ini_set('memory_limit',        '256M');

        $tmpDir = storage_path('tmp');
        if (!is_dir($tmpDir)) @mkdir($tmpDir, 0755, true);
        @ini_set('upload_tmp_dir', $tmpDir);

        // Auto-create required directories
        foreach ([
            storage_path('framework/views'),
            storage_path('framework/cache/data'),
            storage_path('framework/sessions'),
            storage_path('logs'),
            storage_path('tmp'),
            storage_path('app'),
            base_path('bootstrap/cache'),
            public_path('data/pages'),
            public_path('data/api-cache'),
            public_path('data'),
            public_path('media'),
            public_path('media/avatars'),
            resource_path('crud-ui'),
        ] as $dir) {
            if (!is_dir($dir)) @mkdir($dir, 0755, true);
        }



        // Auto setup (runs migrations/seed if needed)
        if (!$this->app->runningInConsole()) {
            $this->autoSetupIfNeeded();
        }

        // Timezone from settings
        try {
            $tz = app(SettingsService::class)->get('timezone', 'UTC');
            if ($tz && in_array($tz, \DateTimeZone::listIdentifiers(), true)) {
                config(['app.timezone' => $tz]);
                date_default_timezone_set($tz);
            }
        } catch (\Throwable) {}

        // Global admin view composers
        View::composer('admin.*', function ($view) {
            try {
                $user     = auth()->user();
                $settings = app(SettingsService::class);
                $view->with([
                    'cmsMediaDriver'     => $settings->mediaDriver(),
                    'cmsCloudinaryReady' => $settings->cloudinaryConfigured(),
                    'cmsSiteName'        => $settings->get('site_name', 'College CMS'),
                    'cmsAdminTheme'      => $settings->get('admin_theme', 'default-dark'),
                    'cmsSiteLogoUrl'     => $settings->url('site_logo', ''),
                    'cmsCustomCss'       => \App\Models\AdminUiCustomization::activeCSS(),
                    'cmsCustomJs'        => \App\Models\AdminUiCustomization::activeJS(),
                    'isSuperAdmin'       => $user?->isSuperAdmin() ?? false,
                    'isAdmin'            => $user?->isAdmin() ?? false,
                    'registeredTables'   => (function() use ($user) {
                        $tables = \App\Models\TablesRegistry::orderBy('table_name')->get();
                        if ($user && $user->role === 'admin') {
                            $allowed = \App\Models\TablePermission::allowedTablesForUser($user->id);
                            return $tables->filter(fn($t) => in_array($t->table_name, $allowed));
                        }
                        return $tables;
                    })(),
                ]);
            } catch (\Throwable) {
                $view->with([
                    'cmsMediaDriver'     => 'local',
                    'cmsCloudinaryReady' => false,
                    'cmsSiteName'        => 'College CMS',
                    'cmsAdminTheme'      => 'default-dark',
                    'cmsSiteLogoUrl'     => '',
                    'cmsCustomCss'       => '',
                    'cmsCustomJs'        => '',
                    'isSuperAdmin'       => false,
                    'isAdmin'            => false,
                    'registeredTables'   => collect(),
                ]);
            }
        });
    }

    private function autoSetupIfNeeded(): void
    {
        if (file_exists(storage_path('app/.setup_done'))) return;
        try {
            $connected   = $this->canConnectToDatabase();
            $tablesExist = $connected && Schema::hasTable('users');
            if (!$connected) { $this->showDbErrorPage(); return; }
            if ($tablesExist) {
                @file_put_contents(storage_path('app/.setup_done'), now()->toISOString());
                return;
            }
            $this->runAutoSetup();
        } catch (\Throwable $e) {
            $this->showDbErrorPage($e->getMessage());
        }
    }

    private function canConnectToDatabase(): bool
    {
        try {
            $dbName = config('database.connections.mysql.database', '');
            $dbUser = config('database.connections.mysql.username', '');
            if (empty($dbName) || empty($dbUser)) return false;
            DB::connection()->getPdo();
            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    private function runAutoSetup(): void
    {
        set_time_limit(120);
        $key = config('app.key');
        if (empty($key) || str_contains($key, 'SomeRandom') || strlen($key) < 10) {
            Artisan::call('key:generate', ['--force' => true]);
        }
        Artisan::call('migrate', ['--force' => true]);
        Artisan::call('db:seed', ['--force' => true]);
        try {
            Artisan::call('config:clear');
            Artisan::call('cache:clear');
        } catch (\Throwable) {}
        @file_put_contents(storage_path('app/.setup_done'), now()->toISOString());
        header('Location: /admin/login?setup=done');
        exit;
    }

    private function showDbErrorPage(string $detail = ''): void
    {
        if (headers_sent()) return;
        http_response_code(503);
        header('Content-Type: text/html; charset=UTF-8');
        $isProduction = app()->environment('production');
        if ($isProduction) {
            echo '<!DOCTYPE html><html><head><title>Service Unavailable</title></head>'
               . '<body style="font-family:sans-serif;background:#0f172a;color:#e2e8f0;display:flex;align-items:center;justify-content:center;min-height:100vh;">'
               . '<div style="text-align:center;padding:2rem"><h1>⚙️</h1><h2>Service Temporarily Unavailable</h2>'
               . '<p style="color:#94a3b8">The site is undergoing maintenance.</p></div></body></html>';
        } else {
            echo '<!DOCTYPE html><html><head><title>DB Setup Required</title></head>'
               . '<body style="font-family:sans-serif;background:#0f172a;color:#e2e8f0;display:flex;align-items:center;justify-content:center;min-height:100vh;">'
               . '<div style="background:#1e293b;border-radius:12px;padding:2rem;max-width:500px;width:100%">'
               . '<h2>🗄️ Database Setup Required</h2>'
               . '<p style="color:#94a3b8">Edit <code>core/.env</code> — fill in DB_DATABASE, DB_USERNAME, DB_PASSWORD.</p>'
               . ($detail ? '<pre style="background:#0f172a;padding:1rem;border-radius:6px;font-size:.75rem;color:#94a3b8;overflow:auto">' . htmlspecialchars($detail) . '</pre>' : '')
               . '<p style="margin-top:1rem">Then run: <code>php system/setup.php</code> from the command line.</p>'
               . '</div></body></html>';
        }
        exit;
    }
}
