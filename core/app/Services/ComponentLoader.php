<?php

namespace App\Services;

use Illuminate\Support\Facades\File;

/**
 * ComponentLoader — scans /visual-builder/components/ and /visual-builder/sections/ and returns file-based component definitions.
 *
 * Directory structure expected:
 *   <base>/
 *     {slug}/
 *       component.json  ← required
 *       template.html   ← required
 *       style.css       ← optional
 *       script.js       ← optional
 */
class ComponentLoader
{
    private array $basePaths;

    public function __construct()
    {
        // Scan both components and sections
        $this->basePaths = [
            resource_path('views/admin/visual-builder/components'),
            resource_path('views/admin/visual-builder/sections')
        ];
    }

    // ── Public API ────────────────────────────────────────────────────────

    /**
     * Return valid elements as an array.
     * @param string $type 'all', 'components', or 'sections'
     */
    public function getAllComponents(string $type = 'all'): array
    {
        $components = [];

        foreach ($this->basePaths as $basePath) {
            if ($type === 'components' && str_ends_with($basePath, 'sections')) continue;
            if ($type === 'sections' && str_ends_with($basePath, 'components')) continue;
            
            if (!is_dir($basePath)) continue;

            $dirs = array_filter(glob($basePath . DIRECTORY_SEPARATOR . '*'), 'is_dir');

            foreach ($dirs as $dir) {
                $jsonPath = $dir . DIRECTORY_SEPARATOR . 'component.json';
                if (!file_exists($jsonPath)) continue;

                $meta = $this->readJson($jsonPath);
                if (!$meta || empty($meta['slug'])) continue;

                $slug = $meta['slug'];

                $components[] = [
                    'name'        => $meta['name']        ?? $slug,
                    'slug'        => $slug,
                    'category'    => $meta['category']    ?? 'custom',
                    'icon'        => $meta['icon']        ?? '🧩',
                    'description' => $meta['description'] ?? '',
                    'fields'      => $meta['fields']      ?? [],
                    'hasTemplate' => file_exists($dir . DIRECTORY_SEPARATOR . 'template.html'),
                    'hasCss'      => file_exists($dir . DIRECTORY_SEPARATOR . 'style.css'),
                    'hasJs'       => file_exists($dir . DIRECTORY_SEPARATOR . 'script.js'),
                ];
            }
        }

        usort($components, fn($a, $b) => strcmp($a['category'] . $a['name'], $b['category'] . $b['name']));
        return $components;
    }

    /**
     * Return all valid components with their full file contents attached.
     */
    public function getFullComponents(string $type = 'all'): array
    {
        $comps = $this->getAllComponents($type);
        foreach ($comps as &$c) {
            $full = $this->getComponent($c['slug']);
            $c['template'] = $full['template'] ?? '';
            $c['css']      = $full['css'] ?? '';
            $c['js']       = $full['js'] ?? '';
        }
        return $comps;
    }

    /**
     * Return a single component with full file contents.
     */
    public function getComponent(string $slug): ?array
    {
        $dir = $this->componentDir($slug);
        if (!$dir) return null;

        $jsonPath = $dir . DIRECTORY_SEPARATOR . 'component.json';
        $meta = $this->readJson($jsonPath);
        if (!$meta) return null;

        return [
            'name'         => $meta['name']         ?? $slug,
            'slug'         => $slug,
            'category'     => $meta['category']     ?? 'custom',
            'icon'         => $meta['icon']          ?? '🧩',
            'description'  => $meta['description']  ?? '',
            'fields'       => $meta['fields']        ?? [],
            'builder_tree' => $meta['builder_tree']  ?? null,
            'template'     => $this->readFile($dir, 'template.html'),
            'css'          => $this->readFile($dir, 'style.css'),
            'js'           => $this->readFile($dir, 'script.js'),
            'schema_raw'   => file_get_contents($jsonPath),
        ];
    }

    /**
     * Create a new component or section folder with skeleton files.
     */
    public function createComponent(string $name, string $slug, string $type = 'components'): array
    {
        // Choose base path based on type
        $baseIdx = $type === 'sections' ? 1 : 0;
        $dir = $this->basePaths[$baseIdx] . DIRECTORY_SEPARATOR . $slug;

        if (is_dir($dir) || $this->componentDir($slug) !== null) {
            throw new \RuntimeException("Component '{$slug}' already exists.");
        }

        if (!mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new \RuntimeException("Failed to create component directory.");
        }

        $json = json_encode([
            'name'        => $name,
            'slug'        => $slug,
            'category'    => 'custom',
            'icon'        => '🧩',
            'description' => '',
            'fields'      => [
                ['type' => 'text', 'key' => 'title', 'label' => 'Title', 'default' => $name],
            ],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        file_put_contents($dir . DIRECTORY_SEPARATOR . 'component.json', $json);
        file_put_contents($dir . DIRECTORY_SEPARATOR . 'template.html', "<div class=\"fc-{$slug}\">\n  <h2>{{title}}</h2>\n</div>\n");
        file_put_contents($dir . DIRECTORY_SEPARATOR . 'style.css',     ".fc-{$slug} { padding: 32px 40px; }\n");
        file_put_contents($dir . DIRECTORY_SEPARATOR . 'script.js',     "// {$name} component script\n");

        return $this->getComponent($slug) ?? [];
    }

    /**
     * Save files for an existing component.
     */
    public function saveComponent(string $slug, array $files): void
    {
        $dir = $this->componentDir($slug);
        if (!$dir) throw new \RuntimeException("Component '{$slug}' not found.");

        if (isset($files['schema_raw'])) {
            $decoded = json_decode($files['schema_raw'], true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \InvalidArgumentException('Invalid component.json: ' . json_last_error_msg());
            }
            file_put_contents($dir . DIRECTORY_SEPARATOR . 'component.json', $files['schema_raw']);
        }

        if (isset($files['template'])) {
            file_put_contents($dir . DIRECTORY_SEPARATOR . 'template.html', $files['template']);
        }

        if (isset($files['css'])) {
            file_put_contents($dir . DIRECTORY_SEPARATOR . 'style.css', $files['css']);
        }

        if (isset($files['js'])) {
            file_put_contents($dir . DIRECTORY_SEPARATOR . 'script.js', $files['js']);
        }
    }

    /**
     * Delete a component folder.
     */
    public function deleteComponent(string $slug): void
    {
        $dir = $this->componentDir($slug);
        if (!$dir) throw new \RuntimeException("Component '{$slug}' not found.");

        // Safety: only delete known files, not arbitrary paths
        $files = ['component.json', 'template.html', 'style.css', 'script.js'];
        foreach ($files as $f) {
            $fp = $dir . DIRECTORY_SEPARATOR . $f;
            if (file_exists($fp)) unlink($fp);
        }
        @rmdir($dir);
    }

    // ── Internals ─────────────────────────────────────────────────────────

    private function componentDir(string $slug): ?string
    {
        if (!preg_match('/^[a-z0-9][a-z0-9\-]{0,60}$/', $slug)) return null;

        foreach ($this->basePaths as $basePath) {
            $dir      = $basePath . DIRECTORY_SEPARATOR . $slug;
            $realBase = realpath($basePath);
            if (!$realBase) continue;

            $realDir  = realpath($dir);
            
            if ($realDir && str_starts_with($realDir, $realBase . DIRECTORY_SEPARATOR) && is_dir($realDir)) {
                return $realDir;
            }
        }
        return null;
    }

    private function readJson(string $path): ?array
    {
        $content = @file_get_contents($path);
        if ($content === false) return null;
        $decoded = json_decode($content, true);
        return json_last_error() === JSON_ERROR_NONE ? $decoded : null;
    }

    private function readFile(string $dir, string $name): string
    {
        $path = $dir . DIRECTORY_SEPARATOR . $name;
        return file_exists($path) ? (file_get_contents($path) ?: '') : '';
    }
}
