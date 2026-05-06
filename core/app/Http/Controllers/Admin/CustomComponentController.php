<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CustomComponent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CustomComponentController extends Controller
{
    // ── List all active components (for builder left panel) ───────────────
    public function index(Request $request): JsonResponse
    {
        $query = CustomComponent::where('is_active', true)->orderBy('category')->orderBy('name');
        if ($search = $request->get('search')) {
            $query->where('name', 'like', "%{$search}%");
        }
        return response()->json($query->get([
            'id','name','slug','category','icon','schema_json','html_template','css','js'
        ]));
    }

    // ── Create a new component ────────────────────────────────────────────
    public function store(Request $request): JsonResponse
    {
        $data = $this->validateComponent($request);
        $data['created_by'] = auth()->id();
        $data['slug'] = $this->uniqueSlug($data['name']);

        $component = CustomComponent::create($data);
        return response()->json(['success' => true, 'component' => $component], 201);
    }

    // ── Show single component ─────────────────────────────────────────────
    public function show(int $id): JsonResponse
    {
        return response()->json(CustomComponent::findOrFail($id));
    }

    // ── Update component ──────────────────────────────────────────────────
    public function update(Request $request, int $id): JsonResponse
    {
        $component = CustomComponent::findOrFail($id);
        $data = $this->validateComponent($request, $id);
        $component->update($data);
        return response()->json(['success' => true, 'component' => $component->fresh()]);
    }

    // ── Toggle active/inactive ────────────────────────────────────────────
    public function toggle(int $id): JsonResponse
    {
        $component = CustomComponent::findOrFail($id);
        $component->update(['is_active' => !$component->is_active]);
        return response()->json(['success' => true, 'is_active' => $component->is_active]);
    }

    // ── Delete component ───────────────────────────────────────────────────
    public function destroy(int $id): JsonResponse
    {
        CustomComponent::findOrFail($id)->delete();
        return response()->json(['success' => true]);
    }

    // ── Seed a sample card component ───────────────────────────────────────
    public function seed(): JsonResponse
    {
        if (CustomComponent::where('slug', 'custom-card')->exists()) {
            return response()->json(['message' => 'Sample already exists']);
        }

        $sample = CustomComponent::create([
            'name'          => 'Custom Card',
            'slug'          => 'custom-card',
            'category'      => 'custom',
            'icon'          => '🃏',
            'html_template' => '<div style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:24px;text-align:center">
  <div style="font-size:36px;margin-bottom:12px">{{icon}}</div>
  <h3 style="font-size:18px;font-weight:700;color:#111827;margin:0 0 8px">{{title}}</h3>
  <p style="font-size:14px;color:#6b7280;line-height:1.6;margin:0 0 16px">{{description}}</p>
  <a href="{{link_url}}" style="display:inline-block;padding:9px 20px;background:#6366f1;color:#fff;border-radius:7px;font-weight:600;font-size:13px;text-decoration:none">{{link_label}}</a>
</div>',
            'css'           => '.custom-card-wrap { padding: 32px 40px; }',
            'js'            => '',
            'schema_json'   => [
                'fields' => [
                    ['type' => 'text',  'key' => 'icon',        'label' => 'Icon (emoji)',    'default' => '⭐'],
                    ['type' => 'text',  'key' => 'title',       'label' => 'Title',           'default' => 'Card Title'],
                    ['type' => 'textarea','key'=> 'description', 'label' => 'Description',    'default' => 'Your description here.'],
                    ['type' => 'url',   'key' => 'link_url',    'label' => 'Button URL',      'default' => '#'],
                    ['type' => 'text',  'key' => 'link_label',  'label' => 'Button Label',    'default' => 'Learn More'],
                ],
            ],
            'created_by'    => auth()->id(),
        ]);

        return response()->json(['success' => true, 'component' => $sample], 201);
    }

    // ── Helpers ────────────────────────────────────────────────────────────
    private function validateComponent(Request $request, ?int $excludeId = null): array
    {
        return $request->validate([
            'name'          => ['required', 'string', 'max:120'],
            'category'      => ['nullable', 'string', 'max:60'],
            'icon'          => ['nullable', 'string', 'max:20'],
            'html_template' => ['required', 'string', 'max:200000'],
            'css'           => ['nullable', 'string', 'max:50000'],
            'js'            => ['nullable', 'string', 'max:50000'],
            'schema_json'   => ['required', 'array'],
            'schema_json.fields' => ['required', 'array', 'min:1'],
            'schema_json.fields.*.type'  => ['required', 'string', 'in:text,textarea,image,url,number,color,select'],
            'schema_json.fields.*.key'   => ['required', 'string', 'regex:/^[a-z_][a-z0-9_]*$/'],
            'schema_json.fields.*.label' => ['required', 'string', 'max:80'],
        ]);
    }

    private function uniqueSlug(string $name, int $attempt = 0): string
    {
        $base = Str::slug($name);
        $slug = $attempt ? "{$base}-{$attempt}" : $base;
        return CustomComponent::where('slug', $slug)->exists()
            ? $this->uniqueSlug($name, $attempt + 1)
            : $slug;
    }
}
