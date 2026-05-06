<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TablesRegistry;
use App\Models\TablePermission;
use App\Services\JsonGeneratorService;
use App\Services\ApiCacheService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CrudController extends Controller
{
    public function __construct(
        private JsonGeneratorService $jsonGenerator,
        private ApiCacheService $apiCache,
    ) {}

    // ---------------------------------------------------------------
    // Guard: ensure table is registered before any action
    // ---------------------------------------------------------------

    private function guardTable(string $table): void
    {
        abort_unless(
            TablesRegistry::where('table_name', $table)->exists(),
            404,
            "Table '{$table}' is not registered."
        );
    }

    private function guardPermission(string $table, string $ability): void
    {
        $user = auth()->user();
        abort_unless(
            $user->isSuperAdmin() || TablePermission::userCan($user->id, $table, $ability),
            403,
            "You do not have permission to {$ability} in table '{$table}'."
        );
    }

    /**
     * Confirm the table has an 'id' column before edit/delete actions.
     * Without this, ->where('id', $id) silently matches nothing or crashes.
     */
    private function guardId(string $table): void
    {
        abort_unless(
            in_array('id', Schema::getColumnListing($table), true),
            422,
            "Table '{$table}' has no 'id' column. Edit/delete requires an integer primary key named 'id'."
        );
    }

    private function columns(string $table): array
    {
        return Schema::getColumnListing($table);
    }

    // ---------------------------------------------------------------
    // List all rows
    // ---------------------------------------------------------------

    public function index(Request $request, string $table)
    {
        $this->guardTable($table);
        $this->guardPermission($table, 'view');

        $columns = $this->columns($table);
        $query = DB::table($table);

        // Apply Search Filter
        if ($request->has('search') && $request->input('search') !== '') {
            $search = $request->input('search');
            $query->where(function ($q) use ($columns, $search) {
                foreach ($columns as $col) {
                    $q->orWhere($col, 'LIKE', "%{$search}%");
                }
            });
        }

        $rows    = $query->paginate(20)->appends($request->all());
        $uiDir   = resource_path("crud-ui/{$table}");

        $registry = TablesRegistry::where('table_name', $table)->first();
        $uiType   = $registry->ui_type ?? 'list';
        $viewName = $uiType === 'grid' ? 'admin.crud.grid' : 'admin.crud.list';

        return view($viewName, compact('table', 'rows', 'columns', 'uiDir'));
    }

    // ---------------------------------------------------------------
    // Show create form
    // ---------------------------------------------------------------

    public function create(string $table)
    {
        $this->guardTable($table);
        $this->guardPermission($table, 'create');

        $columns = array_filter(
            $this->columns($table),
            fn($col) => $col !== 'id' && $col !== 'created_at' && $col !== 'updated_at'
        );

        return view('admin.crud.form', [
            'table'  => $table,
            'row'    => null,
            'columns'=> array_values($columns),
            'action' => route('admin.crud.store', $table),
            'method' => 'POST',
        ]);
    }

    // ---------------------------------------------------------------
    // Store new row
    // ---------------------------------------------------------------

    public function store(Request $request, string $table)
    {
        $this->guardTable($table);
        $this->guardPermission($table, 'create');

        $data = $this->filterInput($request, $table);
        DB::table($table)->insert($data);

        $this->jsonGenerator->generate($table);
        $this->apiCache->invalidate($table);

        return redirect()->route('admin.crud.index', $table)
            ->with('success', 'Record created successfully.');
    }

    // ---------------------------------------------------------------
    // Show edit form
    // ---------------------------------------------------------------

    public function edit(string $table, int $id)
    {
        $this->guardTable($table);
        $this->guardPermission($table, 'edit');
        $this->guardId($table);

        $row = DB::table($table)->where('id', $id)->firstOrFail();
        $columns = array_filter(
            $this->columns($table),
            fn($col) => $col !== 'id' && $col !== 'created_at' && $col !== 'updated_at'
        );

        return view('admin.crud.form', [
            'table'  => $table,
            'row'    => $row,
            'columns'=> array_values($columns),
            'action' => route('admin.crud.update', [$table, $id]),
            'method' => 'PUT',
        ]);
    }

    // ---------------------------------------------------------------
    // Update row
    // ---------------------------------------------------------------

    public function update(Request $request, string $table, int $id)
    {
        $this->guardTable($table);
        $this->guardPermission($table, 'edit');
        $this->guardId($table);

        $data = $this->filterInput($request, $table);
        DB::table($table)->where('id', $id)->update($data);

        $this->jsonGenerator->generate($table);
        $this->apiCache->invalidate($table);

        return redirect()->route('admin.crud.index', $table)
            ->with('success', 'Record updated successfully.');
    }

    // ---------------------------------------------------------------
    // Delete row
    // ---------------------------------------------------------------

    public function destroy(string $table, int $id)
    {
        $this->guardTable($table);
        $this->guardPermission($table, 'delete');
        $this->guardId($table);

        DB::table($table)->where('id', $id)->delete();
        $this->jsonGenerator->generate($table);
        $this->apiCache->invalidate($table);

        return redirect()->route('admin.crud.index', $table)
            ->with('success', 'Record deleted.');
    }

    // ---------------------------------------------------------------
    // Edit UI templates in code editor
    // ---------------------------------------------------------------

    public function editUi(string $table)
    {
        // Customizing table UI is admin+ only
        abort_unless(auth()->user()->isAdmin(), 403, 'Only Admins can customise the CRUD UI.');

        // UI editing unified into Page Builder — redirect to plugin creator
        return redirect()->route('admin.plugins.creator.index')
            ->with('info', 'Display data on pages using the Page Builder — add notices, gallery or counter components.');
    }

    public function saveUi(Request $request, string $table)
    {
        abort_unless(auth()->user()->isAdmin(), 403, 'Only Admins can save the CRUD UI.');

        $this->guardTable($table);

        $request->validate([
            'files'   => ['required', 'array'],
            'files.*' => ['string'],
        ]);

        $uiDir = resource_path("crud-ui/{$table}");
        if (! is_dir($uiDir)) mkdir($uiDir, 0755, true);

        $allowed = ['list.html', 'form.html', 'style.css', 'script.js'];
        foreach ($request->input('files') as $filename => $content) {
            if (in_array($filename, $allowed, true)) {
                file_put_contents("{$uiDir}/{$filename}", $content);
            }
        }

        return back()->with('success', 'UI templates saved.');
    }

    // ---------------------------------------------------------------
    // Helper: filter request data to only table columns
    // ---------------------------------------------------------------

    private function filterInput(Request $request, string $table): array
    {
        // Columns that should never be written via the CRUD UI regardless of schema
        $blocked = ['id', 'password', 'remember_token', 'key_hash', 'api_key',
                    'secret', 'token', 'email_verified_at'];

        $columns = array_filter(
            $this->columns($table),
            fn($col) => ! in_array(strtolower($col), $blocked, true)
        );

        return $request->only(array_values($columns));
    }
}
