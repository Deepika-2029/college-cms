<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PageVersion;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PageVersionController extends Controller
{
    public function __construct(private AuditLogger $audit) {}

    public function index(string $slug)
    {
        $versions = PageVersion::where('slug', $slug)
            ->with('savedBy')
            ->latest()
            ->paginate(20);

        return view('admin.pages.versions', compact('slug', 'versions'));
    }

    public function restore(Request $request, int $versionId)
    {
        $version = PageVersion::findOrFail($versionId);

        $pageFile = public_path("data/pages/{$version->slug}.json");

        $existing = file_exists($pageFile)
            ? json_decode(file_get_contents($pageFile), true)
            : [];

        // Snapshot current state before restoring
        if ($existing) {
            PageVersion::snapshot($version->slug, $existing, Auth::id(), 'Auto-snapshot before restore');
        }

        // Restore to version
        $restored = array_merge($existing, [
            'title'      => $version->title,
            'rows'       => $version->rows,
            'sections'   => $version->sections,
            'global_css' => $version->global_css,
            'status'     => $version->status,
        ]);

        @mkdir(dirname($pageFile), 0755, true);
        file_put_contents($pageFile, json_encode($restored, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $this->audit->log('page.restored', 'page', (string) $versionId,
            $version->slug . ' — restored to version from ' . $version->created_at->format('Y-m-d H:i'));

        return redirect()->route('admin.builder.edit', [$version->slug, 'mode' => 'page'])
            ->with('success', "Page restored to version from {$version->created_at->format('M j, Y H:i')}.");
    }

    public function destroy(PageVersion $version)
    {
        $slug = $version->slug;
        $version->delete();
        return back()->with('success', 'Version deleted.');
    }
}
