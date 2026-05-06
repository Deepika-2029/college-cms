<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HtmlPage;
use App\Models\V3Component;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class V3ComponentController extends Controller
{
    // ── Template folder path helper ─────────────────────────────────────────────
    private function templateDir(string $slug): string
    {
        // All template metadata files live under: frontend_templates/{slug}/
        return public_path("frontend_templates/{$slug}");
    }

    // ── LIST VIEW ───────────────────────────────────────────────────────────────
    public function index()
    {
        $components = V3Component::orderBy('category')->orderBy('name')->get();
        return view('admin.visual-builder-v3.components', compact('components'));
    }

    // ── CREATE ──────────────────────────────────────────────────────────────────
    public function store(Request $request)
    {
        try {
            $request->validate([
                'name'     => 'required|string|max:255',
                'category' => 'required|string|max:255',
            ]);

            $component = V3Component::create([
                'name'      => $request->name,
                'category'  => $request->category,
                'base_html' => $request->base_html ?? '',
                'base_css'  => $request->base_css  ?? '',
                'base_js'   => $request->base_js   ?? '',
            ]);

            // Write template metadata file to data/templates/{slug}/
            $this->writeTemplateMeta($component);

            return response()->json(['success' => true, 'component' => $component]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'message' => collect($e->errors())->first()[0]]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine()]);
        }
    }

    // ── UPDATE ──────────────────────────────────────────────────────────────────
    public function update(Request $request, $id)
    {
        try {
            $component = V3Component::findOrFail($id);

            $oldHtml = $component->base_html;
            $newHtml = $request->input('base_html', $oldHtml);
            $newCss  = $request->input('base_css',  $component->base_css);
            $newJs   = $request->input('base_js',   $component->base_js);
            $newName = $request->input('name',      $component->name);
            $newCat  = $request->input('category',  $component->category);

            $component->update([
                'name'      => $newName,
                'category'  => $newCat,
                'base_html' => $newHtml,
                'base_css'  => $newCss,
                'base_js'   => $newJs,
            ]);

            // Update template metadata file
            $this->writeTemplateMeta($component);

            // ── Propagate HTML change to all pages that use this template ──────
            $diag = ['pages_updated' => 0, 'pages_errors' => []];

            if (trim($newHtml) !== trim($oldHtml)) {
                $marker   = 'data-tpl-id="' . $id . '"';
                $affected = HtmlPage::where('base_html', 'like', '%' . $marker . '%')->get();

                if ($affected->isNotEmpty()) {
                    $vb3 = app(VisualBuilderV3Controller::class);

                    foreach ($affected as $page) {
                        try {
                            $pageHtml = $page->base_html ?? '';
                            $updated  = self::replaceTplBlock($pageHtml, (string) $id, $newHtml);

                            if ($updated && $updated !== $pageHtml) {
                                $page->base_html = $updated;
                                $page->save();
                                $vb3->writeStaticFile($page);
                                $diag['pages_updated']++;
                            }
                        } catch (\Throwable $e) {
                            $diag['pages_errors'][] = "{$page->slug}: " . $e->getMessage();
                        }
                    }
                }
            }

            return response()->json([
                'success'    => true,
                'component'  => $component,
                'propagated' => $diag,
                'message'    => $diag['pages_updated'] > 0
                    ? "Template updated and propagated to {$diag['pages_updated']} page(s)!"
                    : 'Template saved! (No pages currently use this template)',
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    // ── DELETE ──────────────────────────────────────────────────────────────────
    public function destroy($id)
    {
        $component = V3Component::findOrFail($id);

        // Delete the metadata folder: data/templates/{slug}/
        $slug = Str::slug($component->name . '-' . $component->id);
        $dir  = $this->templateDir($slug);
        if (File::isDirectory($dir)) {
            File::deleteDirectory($dir);
        }

        $component->delete();
        return response()->json(['success' => true]);
    }

    // ── API LIST (for editor dropdown) ──────────────────────────────────────────
    public function list()
    {
        $components = V3Component::orderBy('category')->get()->groupBy('category');
        return response()->json($components);
    }

    // ── WRITE METADATA FILE ─────────────────────────────────────────────────────
    /**
     * Writes a template.json metadata file to data/templates/{slug}/.
     * Non-fatal: if the write fails it logs a warning but doesn't break the save.
     */
    private function writeTemplateMeta(V3Component $component): void
    {
        try {
            $slug = Str::slug($component->name . '-' . $component->id);
            $dir  = $this->templateDir($slug);
            File::ensureDirectoryExists($dir);
            File::put("{$dir}/template.json", json_encode([
                'id'       => $component->id,
                'name'     => $component->name,
                'category' => $component->category,
                'slug'     => $slug,
                'updated'  => now()->toISOString(),
            ], JSON_PRETTY_PRINT));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('[V3Component] writeTemplateMeta failed: ' . $e->getMessage());
        }
    }

    // ── TEMPLATE BLOCK REPLACEMENT ──────────────────────────────────────────────
    /**
     * Replace the inner content of every <div data-tpl-id="X"> ... </div>
     * wrapper in $pageHtml with $newInner. Uses a simple stack-based parser
     * instead of a non-greedy regex so nested divs inside templates are handled
     * correctly.
     */
    private static function replaceTplBlock(string $pageHtml, string $tplId, string $newInner): string
    {
        $marker = 'data-tpl-id="' . $tplId . '"';

        if (!str_contains($pageHtml, $marker)) {
            return $pageHtml;
        }

        $result   = '';
        $pos      = 0;
        $len      = strlen($pageHtml);
        $replaced = false;

        while ($pos < $len) {
            $divStart = stripos($pageHtml, '<div', $pos);
            if ($divStart === false) {
                $result .= substr($pageHtml, $pos);
                break;
            }

            if ($divStart > $pos) {
                $result .= substr($pageHtml, $pos, $divStart - $pos);
            }

            $tagEnd = strpos($pageHtml, '>', $divStart);
            if ($tagEnd === false) {
                $result .= substr($pageHtml, $divStart);
                break;
            }
            $openTag = substr($pageHtml, $divStart, $tagEnd - $divStart + 1);

            // Is this OUR template wrapper?
            if (stripos($openTag, $marker) !== false) {
                $replaced = true;
                $depth  = 1;
                $cursor = $tagEnd + 1;
                while ($cursor < $len && $depth > 0) {
                    $nextOpen  = stripos($pageHtml, '<div',  $cursor);
                    $nextClose = stripos($pageHtml, '</div', $cursor);

                    if ($nextClose === false) break; // malformed HTML

                    if ($nextOpen !== false && $nextOpen < $nextClose) {
                        $depth++;
                        $cursor = $nextOpen + 4;
                    } else {
                        $depth--;
                        if ($depth === 0) {
                            $closeEnd = strpos($pageHtml, '>', $nextClose);
                            if ($closeEnd === false) break;
                            $closeTag = substr($pageHtml, $nextClose, $closeEnd - $nextClose + 1);

                            $result .= $openTag . "\n" . $newInner . "\n" . $closeTag;
                            $pos     = $closeEnd + 1;
                            break;
                        }
                        $cursor = $nextClose + 6;
                    }
                }
                if ($depth !== 0) {
                    $result .= substr($pageHtml, $pos);
                    break;
                }
            } else {
                // Not our template wrapper — copy open tag and advance
                $result .= $openTag;
                $pos     = $tagEnd + 1;
            }
        }

        return $replaced ? $result : $pageHtml;
    }
}
