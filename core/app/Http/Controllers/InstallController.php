<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Exception;

class InstallController extends Controller
{
    private function abortIfInstalled()
    {
        $installLock = defined('CMS_SYSTEM_ROOT') 
            ? CMS_SYSTEM_ROOT . '/install.lock' 
            : base_path('install.lock');
            
        if (file_exists($installLock) || file_exists(dirname(base_path()) . '/system/install.lock')) {
            abort(403, 'System has already been installed. To reinstall, please delete the install.lock file.');
        }
    }

    public function index()
    {
        $this->abortIfInstalled();

        $requirements = [
            'php' => version_compare(PHP_VERSION, '8.2.0', '>='),
            'pdo' => extension_loaded('pdo_mysql'),
            'mbstring' => extension_loaded('mbstring'),
            'fileinfo' => extension_loaded('fileinfo'),
            'openssl' => extension_loaded('openssl'),
            'tokenizer' => extension_loaded('tokenizer'),
            'xml' => extension_loaded('xml'),
            'ctype' => extension_loaded('ctype'),
            'json' => extension_loaded('json'),
            'curl' => extension_loaded('curl'),
        ];
        
        $envWritable = is_writable(base_path('.env'));
        $storageWritable = is_writable(storage_path()) && is_writable(storage_path('framework')) && is_writable(storage_path('logs'));

        $ready = !in_array(false, $requirements) && $envWritable && $storageWritable;

        return view('install', compact('requirements', 'envWritable', 'storageWritable', 'ready'));
    }

    public function process(Request $request)
    {
        $this->abortIfInstalled();

        $request->validate([
            'db_host' => 'required',
            'db_port' => 'required',
            'db_name' => 'required',
            'db_user' => 'required',
            'admin_name' => 'required',
            'admin_email' => 'required|email',
            'admin_password' => 'required|min:8',
        ]);

        try {
            // Test DB Connection
            config([
                'database.connections.mysql.host' => $request->db_host,
                'database.connections.mysql.port' => $request->db_port,
                'database.connections.mysql.database' => $request->db_name,
                'database.connections.mysql.username' => $request->db_user,
                'database.connections.mysql.password' => $request->db_pass ?? '',
            ]);
            DB::purge('mysql');
            DB::reconnect('mysql');
            DB::connection()->getPdo();
        } catch (Exception $e) {
            return back()->withInput()->with('error', 'Database Connection Failed: ' . $e->getMessage());
        }

        try {
            // Write to .env
            $this->updateEnv([
                'DB_HOST' => $request->db_host,
                'DB_PORT' => $request->db_port,
                'DB_DATABASE' => $request->db_name,
                'DB_USERNAME' => $request->db_user,
                'DB_PASSWORD' => $request->db_pass ?? '',
                'APP_URL' => url('/'),
                'APP_ENV' => 'production',
                'APP_DEBUG' => 'false',
            ]);

            // Run Migrations
            Artisan::call('migrate:fresh', ['--force' => true]);
            
            // Reconnect again just in case model uses stale connection
            DB::purge('mysql');
            DB::reconnect('mysql');

            // Create Admin User
            User::create([
                'name' => $request->admin_name,
                'email' => $request->admin_email,
                'password' => Hash::make($request->admin_password),
                'role' => 'super_admin',
                'status' => true,
                'system_permissions' => ['database_builder', 'tools', 'audit_logs', 'settings', 'api_keys', 'advanced_users']
            ]);

            // Create Installation Lock
            $installLock = defined('CMS_SYSTEM_ROOT') 
                ? CMS_SYSTEM_ROOT . '/install.lock' 
                : base_path('install.lock');
            file_put_contents($installLock, 'installed on ' . date('Y-m-d H:i:s'));

            // Clear Caches
            Artisan::call('optimize:clear');

            return redirect('/admin/login')->with('success', 'Installation completed successfully! You can now log in.');

        } catch (Exception $e) {
            return back()->withInput()->with('error', 'Installation Failed: ' . $e->getMessage());
        }
    }

    private function updateEnv($data)
    {
        $envPath = base_path('.env');
        $envContent = file_get_contents($envPath);

        foreach ($data as $key => $value) {
            $value = preg_replace('/\s+/', '', $value); // remove spaces
            $keyRegex = '/^' . $key . '=(.*)$/m';
            if (preg_match($keyRegex, $envContent)) {
                $envContent = preg_replace($keyRegex, $key . '=' . $value, $envContent);
            } else {
                $envContent .= "\n" . $key . '=' . $value;
            }
        }

        file_put_contents($envPath, $envContent);
    }
}
