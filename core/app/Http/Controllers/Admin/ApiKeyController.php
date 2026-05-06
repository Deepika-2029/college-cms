<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use App\Models\TablesRegistry;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ApiKeyController extends Controller
{
    public function __construct(private AuditLogger $audit) {}

    public function index()
    {
        $keys   = ApiKey::with('createdBy')->latest()->get();
        $tables = TablesRegistry::pluck('table_name');
        return view('admin.api-keys.index', compact('keys', 'tables'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'       => ['required', 'string', 'max:100'],
            'table_name' => ['required', 'string', 'exists:tables_registry,table_name'],
            'expires_at' => ['nullable', 'date', 'after:now'],
            'data_limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'data_sort'  => ['nullable', 'string', 'in:latest,oldest'],
        ]);

        $generated = ApiKey::generate($data['name'], $data['table_name'], Auth::id(), [
            'data_limit' => $data['data_limit'] ?? null,
            'data_sort'  => $data['data_sort'] ?? null,
        ]);
        $keyModel  = $generated['model'];
        $rawKey    = $generated['key'];
        
        if (isset($data['expires_at'])) {
            $keyModel->update(['expires_at' => $data['expires_at']]);
        }

        $this->audit->log('api_key.created', 'api_key', (string) $keyModel->id, $keyModel->name);

        // Show the raw key once — it won't be shown again in full
        // User must copy it now before navigating away
        return back()->with('new_key', $rawKey)->with('success', "API key created. Copy it now — it won't be shown again.");
    }

    public function toggle(ApiKey $apiKey)
    {
        $apiKey->update(['is_active' => ! $apiKey->is_active]);
        $this->audit->log(
            $apiKey->is_active ? 'api_key.enabled' : 'api_key.disabled',
            'api_key', (string) $apiKey->id, $apiKey->name
        );
        return back()->with('success', "API key {$apiKey->name} " . ($apiKey->is_active ? 'enabled' : 'disabled') . '.');
    }

    public function destroy(ApiKey $apiKey)
    {
        $name = $apiKey->name;
        $apiKey->delete();
        $this->audit->log('api_key.deleted', 'api_key', null, $name);
        return back()->with('success', "API key '{$name}' deleted.");
    }
}
