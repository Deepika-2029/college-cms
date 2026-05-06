<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ComponentLoader;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ComponentFileController extends Controller
{
    public function __construct(private ComponentLoader $loader) {}

    // ── GET /admin/components/files ───────────────────────────────────────
    // Returns the full list of file-based components (for builder left panel)
    public function index(): JsonResponse
    {
        return response()->json($this->loader->getAllComponents());
    }

    // ── GET /admin/components/files/{slug} ────────────────────────────────
    // Returns a single component with file contents (for editor and renderer)
    public function show(string $slug): JsonResponse
    {
        $comp = $this->loader->getComponent($slug);
        if (!$comp) abort(404, "Component '{$slug}' not found.");
        return response()->json($comp);
    }

    // ── POST /admin/components/files ──────────────────────────────────────
    // Create a new component folder with skeleton files
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'slug' => ['nullable', 'string', 'max:80', 'regex:/^[a-z0-9\-]+$/'],
        ]);

        $name = trim($data['name']);
        $slug = !empty($data['slug']) ? $data['slug'] : Str::slug($name);

        try {
            $comp = $this->loader->createComponent($name, $slug);
            return response()->json(['success' => true, 'component' => $comp], 201);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    // ── PUT /admin/components/files/{slug} ────────────────────────────────
    // Save all files for a component
    public function update(Request $request, string $slug): JsonResponse
    {
        $data = $request->validate([
            'template'   => ['nullable', 'string', 'max:500000'],
            'css'        => ['nullable', 'string', 'max:100000'],
            'js'         => ['nullable', 'string', 'max:100000'],
            'schema_raw' => ['nullable', 'string', 'max:50000'],
        ]);

        // Prevent PHP code injection in template/css/js
        foreach (['template', 'css', 'js'] as $field) {
            if (!empty($data[$field]) && preg_match('/<\?(?:php|=)?/i', $data[$field])) {
                return response()->json(['error' => "PHP code is not allowed in {$field}"], 422);
            }
        }

        try {
            $this->loader->saveComponent($slug, array_filter($data, fn($v) => $v !== null));
            $comp = $this->loader->getComponent($slug);
            return response()->json(['success' => true, 'component' => $comp]);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    // ── DELETE /admin/components/files/{slug} ─────────────────────────────
    public function destroy(string $slug): JsonResponse
    {
        try {
            $this->loader->deleteComponent($slug);
            return response()->json(['success' => true]);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    // ── GET /admin/components/editor ──────────────────────────────────────
    // Admin Component Editor page
    public function editor(Request $request)
    {
        $slug       = $request->get('slug');
        $components = $this->loader->getAllComponents();
        $current    = $slug ? $this->loader->getComponent($slug) : null;

        return view('admin.components.editor', compact('components', 'current', 'slug'));
    }

    // ── GET /admin/components/files/{slug}/asset ──────────────────────────
    // Serve a component file asset (template/style/script) with caching headers
    public function serveAsset(string $slug, string $asset): \Illuminate\Http\Response
    {
        if (!in_array($asset, ['template.html', 'style.css', 'script.js'])) abort(404);

        $comp = $this->loader->getComponent($slug);
        if (!$comp) abort(404);

        $mimeMap = ['template.html' => 'text/html', 'style.css' => 'text/css', 'script.js' => 'application/javascript'];
        $content = match($asset) {
            'template.html' => $comp['template'],
            'style.css'     => $comp['css'],
            'script.js'     => $comp['js'],
            default         => '',
        };

        return response($content, 200)
            ->header('Content-Type', $mimeMap[$asset] . '; charset=UTF-8')
            ->header('Cache-Control', 'private, max-age=30');
    }
}
