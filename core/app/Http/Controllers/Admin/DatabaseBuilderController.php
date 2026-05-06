<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PageTableLink;
use App\Models\TablesRegistry;
use App\Services\CrudUiGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DatabaseBuilderController extends Controller
{
    public function __construct(
        private CrudUiGeneratorService $uiGenerator
    ) {}

    public function index()
    {
        $tables = TablesRegistry::latest()->get();
        return view('admin.database.index', compact('tables'));
    }

    public function builder()
    {
        return view('admin.database.builder');
    }

    public function run(Request $request)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403, 'Only Super Admins can create tables.');
        $request->validate([
            'table_name' => ['required', 'string', 'regex:/^[a-zA-Z_][a-zA-Z0-9_]{0,63}$/'],
            'ui_type'    => ['nullable', 'string', 'in:list,grid'],
            'columns' => ['required', 'array', 'min:1'],
            'columns.*.name' => ['required', 'string', 'regex:/^[a-zA-Z_][a-zA-Z0-9_]{0,63}$/'],
            'columns.*.type' => ['required', 'string', 'in:string,text,integer,bigInteger,decimal,date,dateTime,boolean'],
            'columns.*.nullable' => ['nullable', 'boolean']
        ]);

        $tableName = strtolower($request->input('table_name'));

        // Reserved tables check
        $reserved = ['tables_registry','users','media','media_files','sessions','cache',
                     'cache_locks','jobs','failed_jobs','audit_logs','blocked_ips',
                     'admin_menu_items','admin_ui_customizations','plugin_blueprints',
                     'templates','pages','migrations','password_resets', 'api_keys', 'table_permissions'];
        if (in_array($tableName, $reserved, true) || \Illuminate\Support\Facades\Schema::hasTable($tableName)) {
            return back()->with('error', "Table '{$tableName}' is reserved or already exists.");
        }

        try {
            Schema::create($tableName, function (\Illuminate\Database\Schema\Blueprint $table) use ($request) {
                $table->id();
                foreach ($request->input('columns') as $col) {
                    $type = $col['type'];
                    $name = strtolower($col['name']);
                    
                    // Prevent duplicate IDs or timestamps
                    if (in_array($name, ['id', 'created_at', 'updated_at'])) continue;

                    if ($type === 'decimal') {
                        $colDef = $table->decimal($name, 10, 2);
                    } else {
                        $colDef = $table->$type($name);
                    }

                    if (!empty($col['nullable'])) {
                        $colDef->nullable();
                    }
                }
                $table->timestamps();
            });

            // Register the table
            TablesRegistry::updateOrCreate(
                ['table_name' => $tableName],
                ['ui_type' => $request->input('ui_type', 'list')]
            );

            // Auto-provision table permissions for admin users
            \App\Models\TablePermission::provisionForTable($tableName);

            // Generate CRUD UI templates
            $this->uiGenerator->generate($tableName);

            // Automatically generate a securely hashed read-only API Key for frontend requests
            $keyData = \App\Models\ApiKey::generate("Auto-Generated Read Access: {$tableName}", $tableName, auth()->id());

            // Flash the key directly to the user (it's only shown once)
            return back()->with('success', "Table '{$tableName}' created successfully! \nYour API Key (SAVE THIS NOW!): " . $keyData['key']);
        } catch (\Exception $e) {
            // If schema creation failed but was partially created, attempting to drop it might be safe, but usually it rolls back.
            Schema::dropIfExists($tableName);
            return back()->with('error', 'Schema Error: ' . $e->getMessage());
        }
    }

    public function runRaw(Request $request)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403, 'Only Super Admins can execute raw SQL.');
        $request->validate([
            'raw_sql' => ['required', 'string'],
            'ui_type' => ['nullable', 'string', 'in:list,grid'],
        ]);

        $sql = $request->input('raw_sql');
        
        // Extract table name from CREATE TABLE IF NOT EXISTS `table_name`
        if (preg_match('/CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?(?:[`"\']?)([a-zA-Z_][a-zA-Z0-9_]{0,63})(?:[`"\']?)/i', $sql, $matches)) {
            $tableName = strtolower($matches[1]);
        } else {
            return back()->with('error', 'Could not detect table name from CREATE TABLE statement. Please ensure your query begins with CREATE TABLE.');
        }

        $reserved = ['tables_registry','users','media','media_files','sessions','cache',
                     'cache_locks','jobs','failed_jobs','audit_logs','blocked_ips',
                     'admin_menu_items','admin_ui_customizations','plugin_blueprints',
                     'templates','pages','migrations','password_resets', 'api_keys', 'table_permissions'];
                     
        if (in_array($tableName, $reserved, true) || \Illuminate\Support\Facades\Schema::hasTable($tableName)) {
            return back()->with('error', "Table '{$tableName}' is reserved or already exists.");
        }

        try {
            // ── Ensure utf8mb4 so emoji default values are valid ─────────────────────
            // MySQL's legacy utf8 charset only supports 3-byte chars; emojis need 4 bytes.
            // We automatically append (or replace) the table charset to utf8mb4.
            $sql = $this->normaliseCharset($sql);

            DB::unprepared($sql);

            // Register the table
            TablesRegistry::updateOrCreate(
                ['table_name' => $tableName],
                ['ui_type' => $request->input('ui_type', 'list')]
            );

            // Auto-provision table permissions for admin users
            \App\Models\TablePermission::provisionForTable($tableName);

            // Generate CRUD UI templates
            $this->uiGenerator->generate($tableName);

            // Automatically generate a securely hashed read-only API Key for frontend requests
            $keyData = \App\Models\ApiKey::generate("Auto-Generated Read Access: {$tableName}", $tableName, auth()->id());

            // Flash the key directly to the user (it's only shown once)
            return back()->with('success', "Table '{$tableName}' created successfully! \nYour API Key (SAVE THIS NOW!): " . $keyData['key']);
        } catch (\Exception $e) {
            return back()->with('error', 'Raw SQL Execution Error: ' . $e->getMessage());
        }
    }

    public function drop(string $table)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403, 'Only Super Admins can drop tables.');
        // Validate table is registered
        $registry = TablesRegistry::where('table_name', $table)->firstOrFail();

        try {
            Schema::dropIfExists($table);
            $registry->delete();

            // Delete associated API Keys
            \App\Models\ApiKey::where('table_name', $table)->delete();

            // Clean up CRUD UI templates
            $uiDir = resource_path("crud-ui/{$table}");
            if (is_dir($uiDir)) {
                array_map('unlink', glob("{$uiDir}/*"));
                rmdir($uiDir);
            }

            // Clean up JSON data
            $dataDir = public_path("data/{$table}");
            if (is_dir($dataDir)) {
                array_map('unlink', glob("{$dataDir}/*"));
                rmdir($dataDir);
            }

            return back()->with('success', "Table '{$table}' dropped and unregistered.");
        } catch (\Exception $e) {
            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    /**
     * Ensure CREATE TABLE SQL uses utf8mb4 so 4-byte emoji characters are valid
     * as column DEFAULT values (MySQL's old "utf8" only supports 3-byte chars).
     *
     * Strategy:
     *   1. Strip any existing table-level CHARSET / CHARACTER SET / COLLATE declarations.
     *   2. Append   ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
     *      before the trailing semicolon so the table is always created with full
     *      Unicode support.
     */
    private function normaliseCharset(string $sql): string
    {
        // Remove existing table-level charset/collate tokens
        $sql = preg_replace('/\b(DEFAULT\s+)?CHARSET\s*=\s*\S+/i', '', $sql);
        $sql = preg_replace('/\bCHARACTER\s+SET\s*=?\s*\S+/i', '', $sql);
        $sql = preg_replace('/\bCOLLATE\s*=?\s*\S+/i', '', $sql);

        // Collapse extra whitespace left by removals
        $sql = preg_replace('/[ \t]+/', ' ', trim($sql));

        // Append utf8mb4 table options, preserving the trailing semicolon
        $trimmed = rtrim($sql, " \t\n\r");
        if (str_ends_with($trimmed, ';')) {
            $sql = rtrim($trimmed, ';')
                . ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;';
        } else {
            $sql = $trimmed
                . ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';
        }

        return $sql;
    }

    // ─── SCHEMA INTROSPECTION ────────────────────────────────────────────────

    /** Return column listing + types for the Alter Table panel (JSON). */
    public function getSchema(string $table)
    {
        $this->guardRegistered($table);
        $columns = DB::select("SHOW COLUMNS FROM `{$table}`");
        return response()->json([
            'table'   => $table,
            'columns' => array_map(fn($c) => [
                'name'     => $c->Field,
                'type'     => $c->Type,
                'nullable' => $c->Null === 'YES',
                'default'  => $c->Default,
                'key'      => $c->Key,
            ], $columns),
        ]);
    }

    public function getTableStats(string $table)
    {
        $this->guardRegistered($table);
        $count = 0;
        try {
            $count = DB::table($table)->count();
        } catch (\Exception $e) {
            $count = '?';
        }
        $registry = TablesRegistry::where('table_name', $table)->first();
        return response()->json([
            'table'      => $table,
            'rows'       => $count,
            'ui_type'    => $registry?->ui_type ?? 'list',
            'created_at' => $registry?->created_at?->toDateTimeString(),
        ]);
    }

    /** Re-run CrudUiGeneratorService for an existing table. */
    public function regenerateUi(string $table)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);
        $this->guardRegistered($table);
        $this->uiGenerator->generate($table);
        $this->auditLog("Regenerated UI for table: {$table}");
        return response()->json(['success' => true, 'message' => "UI regenerated for '{$table}'."]);
    }


    // ─── PAGE–TABLE LINKS (Token Vending Source of Truth) ──────────────────

    public function linkPage(Request $request, string $table)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);
        $this->guardRegistered($table);
        $request->validate(['page_slug' => ['required', 'regex:/^[a-z0-9\-]{1,120}$/']]);

        try {
            PageTableLink::firstOrCreate([
                'page_slug'  => $request->input('page_slug'),
                'table_name' => $table,
            ]);
        } catch (\Exception $e) {
            // Check if the error is a missing table
            if (str_contains($e->getMessage(), "Base table or view not found")) {
                return response()->json(['success' => false, 'error' => 'Database migration missing. Please visit /tools/migrate to create the page_table_links table.']);
            }
            return response()->json(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
        }

        return response()->json(['success' => true, 'message' => 'Page linked. Token access granted.']);
    }

    public function unlinkPage(Request $request, string $table)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);
        $this->guardRegistered($table);
        $request->validate(['page_slug' => ['required', 'regex:/^[a-z0-9\-]{1,120}$/']]);

        try {
            PageTableLink::where('page_slug', $request->input('page_slug'))
                ->where('table_name', $table)
                ->delete();
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
        }

        return response()->json(['success' => true, 'message' => 'Page unlinked. Token access revoked.']);
    }

    // ─── HELPERS ────────────────────────────────────────────────────────────

    private function guardRegistered(string $table): void
    {
        if (!TablesRegistry::where('table_name', $table)->exists()) {
            abort(404, "Table '{$table}' is not a managed table.");
        }
    }

    private function auditLog(string $message): void
    {
        try {
            \App\Models\AuditLog::create([
                'user_id'    => auth()->id(),
                'action'     => 'database_builder',
                'description'=> $message,
                'ip_address' => request()->ip(),
            ]);
        } catch (\Throwable) {}
    }
}
