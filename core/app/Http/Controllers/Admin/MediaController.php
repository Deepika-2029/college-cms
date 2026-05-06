<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MediaFile;
use App\Services\CloudinaryService;
use App\Services\SettingsService;
use App\Services\ImageOptimizerService;
use App\Services\MediaUsageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MediaController extends Controller
{
    // ── Allowed extensions → MIME map ─────────────────────────────────────
    private array $allowedExtensions = [
        'jpg'  => 'image/jpeg',   'jpeg' => 'image/jpeg',
        'png'  => 'image/png',    'gif'  => 'image/gif',
        'webp' => 'image/webp',   'svg'  => 'image/svg+xml',
        'pdf'  => 'application/pdf',
        'doc'  => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xls'  => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'ppt'  => 'application/vnd.ms-powerpoint',
        'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'zip'  => 'application/zip',
        'txt'  => 'text/plain',
        'mp4'  => 'video/mp4',
        'mov'  => 'video/quicktime',
    ];

    public function __construct(
        private SettingsService    $settings,
        private CloudinaryService  $cloudinary,
        private ImageOptimizerService $imageOptimizer,
    ) {}

    // ── MIME helpers ───────────────────────────────────────────────────────

    private function resolveMime(\Illuminate\Http\UploadedFile $file): string
    {
        $detected = $file->getMimeType();
        if (!$detected || $detected === 'application/octet-stream') {
            $ext      = strtolower($file->getClientOriginalExtension());
            $detected = $this->allowedExtensions[$ext] ?? 'application/octet-stream';
        }
        return $detected;
    }

    private function isMimeAllowed(string $mime, string $ext): bool
    {
        if (in_array($mime, array_values($this->allowedExtensions), true)) return true;
        if (array_key_exists(strtolower($ext), $this->allowedExtensions))  return true;
        return false;
    }

    // ── Driver resolution ──────────────────────────────────────────────────

    private function resolveDriver(?string $requested): string
    {
        if ($requested === 'local')      return 'local';
        if ($requested === 'cloudinary') return $this->cloudinary->isConfigured() ? 'cloudinary' : 'local';
        return $this->settings->mediaDriver();
    }

    // ── Local upload ───────────────────────────────────────────────────────

    private function storeLocally(\Illuminate\Http\UploadedFile $file, string $filename): string
    {
        $dest = public_path('media');

        if (!is_dir($dest)) {
            if (!mkdir($dest, 0755, true) && !is_dir($dest)) {
                throw new \RuntimeException("Cannot create media directory: {$dest}");
            }
        }

        if (!is_writable($dest)) {
            @chmod($dest, 0755);
            if (!is_writable($dest)) {
                throw new \RuntimeException("Media directory is not writable: {$dest}");
            }
        }

        $ext = strtolower($file->getClientOriginalExtension());
        
        // Fallback or non-image files
        $file->move($dest, $filename);

        if (!file_exists("{$dest}/{$filename}")) {
            throw new \RuntimeException("File move failed — check storage permissions.");
        }

        return '/media/' . $filename;
    }

    // ── SVG sanitizer ──────────────────────────────────────────────────────

    private function sanitizeSvg(string $filepath): void
    {
        if (!file_exists($filepath)) return;
        $content = file_get_contents($filepath);

        if (!preg_match('/<svg[\s>]/i', $content)) {
            unlink($filepath);
            throw new \RuntimeException('Invalid SVG: no <svg> element.');
        }
        if (preg_match('/<\?(?:php|=)?/i', $content)) {
            unlink($filepath);
            throw new \RuntimeException('SVG contains PHP code — rejected.');
        }

        if (!extension_loaded('dom')) {
            $patterns = [
                '/<script[^>]*>.*?<\/script>/is',
                '/\son\w+\s*=\s*["\'][^"\']*["\']?/i',
                '/javascript\s*:/i',
                '/<iframe[^>]*>.*?<\/iframe>/is',
                '/<foreignObject[^>]*>.*?<\/foreignObject>/is',
            ];
            foreach ($patterns as $p) $content = preg_replace($p, '', $content);
            file_put_contents($filepath, $content);
            return;
        }

        $dom = new \DOMDocument();
        $dom->recover = true;
        libxml_use_internal_errors(true);

        if (!@$dom->loadXML($content, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING)) {
            libxml_clear_errors();
            unlink($filepath);
            throw new \RuntimeException('SVG could not be parsed as valid XML.');
        }
        libxml_clear_errors();

        foreach (['script','iframe','object','embed','applet','foreignObject','meta','link'] as $tag) {
            foreach (iterator_to_array($dom->getElementsByTagName($tag)) as $el) {
                $el->parentNode?->removeChild($el);
            }
        }

        $xpath = new \DOMXPath($dom);
        $nodes = $xpath->query('//*');
        if ($nodes) {
            foreach ($nodes as $element) {
                if (!$element instanceof \DOMElement) continue;
                $toRemove = [];
                foreach ($element->attributes as $attr) {
                    $name  = strtolower($attr->name);
                    $value = strtolower(preg_replace('/\s+/', '', $attr->value));
                    if (str_starts_with($name, 'on')) { $toRemove[] = $attr->name; continue; }
                    if (in_array($name, ['href','xlink:href']) && !str_starts_with(ltrim($value), '#')) {
                        if (preg_match('/^(javascript|vbscript|data):/i', $value)) $toRemove[] = $attr->name;
                    }
                }
                foreach ($toRemove as $a) $element->removeAttribute($a);
            }
        }

        $clean = $dom->saveXML();
        if ($clean) file_put_contents($filepath, $clean);
    }

    // ── Main upload dispatcher ─────────────────────────────────────────────

    private function handleUpload(\Illuminate\Http\UploadedFile $file, ?string $requestedDriver): array
    {
        $original = preg_replace('/[^a-zA-Z0-9._-]/', '_', $file->getClientOriginalName());
        $ext      = strtolower($file->getClientOriginalExtension());
        $filename = time() . '_' . $original;
        $driver   = $this->resolveDriver($requestedDriver);
        $mime     = $this->resolveMime($file);

        $cloudPublicId = null;
        $width = $height = null;
        $variants = null;

        if ($driver === 'cloudinary') {
            // Upload via CloudinaryService — gets dimensions from API response
            $result       = $this->cloudinary->upload($file->getRealPath(), $filename, $mime);
            $path         = $result['url'];
            $cloudPublicId = $result['public_id'];
            $width        = $result['width'];
            $height       = $result['height'];

            $size = $result['size'] ?: $this->resolveFileSize($file, $path);
        } else {
            // Local storage
            $generatedVariants = [];
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                $basename = pathinfo($filename, PATHINFO_FILENAME);
                $generatedVariants = $this->imageOptimizer->process($file->getRealPath(), $basename, $ext);
            }

            if (!empty($generatedVariants)) {
                $variants = $generatedVariants;
                $path = $variants['original'] ?? $generatedVariants[array_key_first($generatedVariants)];
                $finalExt = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                $mime = $finalExt === 'jpg' || $finalExt === 'jpeg' ? 'image/jpeg' : ($finalExt === 'png' ? 'image/png' : 'image/webp');
            } else {
                $path = $this->storeLocally($file, $filename);
            }

            if ($ext === 'svg') {
                $this->sanitizeSvg(public_path(ltrim($path, '/')));
            }

            // Get dimensions for local images
            if (str_starts_with($mime, 'image/') && function_exists('getimagesize')) {
                try {
                    [$width, $height] = @getimagesize(public_path(ltrim($path, '/'))) ?: [null, null];
                } catch (\Throwable) {}
            }

            $size = $this->resolveFileSize($file, $path);
        }

        return [
            'file_path'             => $path,
            'cloudinary_public_id'  => $cloudPublicId,
            'driver'                => $driver,
            'mime_type'             => $mime,
            'size'                  => $size,
            'width'                 => $width,
            'height'                => $height,
            'variants'              => $variants,
        ];
    }

    private function resolveFileSize(\Illuminate\Http\UploadedFile $file, string $savedPath): int
    {
        // Try the moved local file first, as WebP compression changes the filesize from the uploaded temp
        if (str_starts_with($savedPath, '/media/')) {
            $local = public_path(ltrim($savedPath, '/'));
            if (file_exists($local)) return (int) filesize($local);
        }

        try {
            $s = $file->getSize();
            if ($s > 0) return $s;
        } catch (\Throwable) {}

        return 0;
    }

    // ── Routes ─────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $query = MediaFile::with('uploader')->latest();
        $uploaders = [];

        if (Auth::user()->role !== 'super_admin') {
            $query->where('uploaded_by', Auth::id());
        } else {
            $uploaders = \App\Models\User::whereHas('media')->get(['id', 'name']);
            if ($uploaderId = $request->get('uploader')) {
                $query->where('uploaded_by', $uploaderId);
            }
        }

        if ($search = $request->get('search')) $query->search($search);

        if ($type = $request->get('type')) {
            match ($type) {
                'image'          => $query->where('mime_type', 'like', 'image/%'),
                'document'       => $query->where(function ($q) {
                    $q->where('mime_type','like','application/%')->orWhere('mime_type','text/plain');
                }),
                'video'          => $query->where('mime_type', 'like', 'video/%'),
                'cloud'          => $query->where('driver', 'cloudinary'),
                'visual-builder' => $query->where('tags', 'like', '%source:visual-builder%'),
                default          => null,
            };
        }

        $files           = $query->paginate(30)->withQueryString();
        $cloudinaryReady = $this->cloudinary->isConfigured();
        $defaultDriver   = $this->settings->mediaDriver();
        $phpUploadLimit  = $this->getPhpUploadLimit();
        $cloudFolder     = $this->cloudinary->getFolder();

        return view('admin.media.index', compact(
            'files', 'cloudinaryReady', 'defaultDriver',
            'phpUploadLimit', 'cloudFolder', 'uploaders'
        ));
    }

    public function upload(Request $request)
    {
        $maxKb = min(32768, (int) ($this->getPhpUploadLimitBytes() / 1024));

        $request->validate([
            'file'    => ['required', 'file', "max:{$maxKb}"],
            'title'   => ['nullable', 'string', 'max:255'],
            'alt'     => ['nullable', 'string', 'max:255'],
            'caption' => ['nullable', 'string', 'max:500'],
            'tags'    => ['nullable', 'string', 'max:500'],
            'driver'  => ['nullable', 'string', 'in:local,cloudinary'],
        ]);

        $file = $request->file('file');
        $ext  = strtolower($file->getClientOriginalExtension());
        $mime = $this->resolveMime($file);

        if (!$this->isMimeAllowed($mime, $ext)) {
            return back()->withErrors(['file' => "File type not allowed: {$mime} (.{$ext})"]);
        }

        try {
            $meta = $this->handleUpload($file, $request->input('driver'));
            MediaFile::create(array_merge($meta, [
                'title'       => $request->input('title') ?: $file->getClientOriginalName(),
                'alt'         => $request->input('alt', ''),
                'caption'     => $request->input('caption', ''),
                'tags'        => $request->input('tags', ''),
                'uploaded_by' => Auth::id(),
            ]));

            if ($request->expectsJson()) {
                return response()->json(['success' => true, 'message' => 'File uploaded successfully.']);
            }
            return back()->with('success', 'File uploaded successfully.');
        } catch (\Throwable $e) {
            if ($request->expectsJson()) return response()->json(['error' => $e->getMessage()], 500);
            return back()->withErrors(['file' => 'Upload failed: ' . $e->getMessage()]);
        }
    }

    public function quickUpload(Request $request)
    {
        $maxKb = min(32768, (int) ($this->getPhpUploadLimitBytes() / 1024));
        $request->validate(['file' => ['required', 'file', "max:{$maxKb}"]]);

        $file = $request->file('file');
        $ext  = strtolower($file->getClientOriginalExtension());
        $mime = $this->resolveMime($file);

        if (!$this->isMimeAllowed($mime, $ext)) {
            return response()->json(['error' => "File type not allowed: .{$ext}"], 422);
        }

        try {
            $meta  = $this->handleUpload($file, $request->input('driver'));

            // Tag uploads coming from the Visual Builder for easy filtering later
            $sourceTags = $request->input('tags', '');
            if ($request->input('source') === 'visual-builder') {
                $sourceTags = trim(($sourceTags ? $sourceTags . ', ' : '') . 'source:visual-builder', ', ');
            }

            $media = MediaFile::create(array_merge($meta, [
                'title'       => $file->getClientOriginalName(),
                'tags'        => $sourceTags,
                'uploaded_by' => Auth::id(),
            ]));
            return response()->json([
                'id'         => $media->id,
                'url'        => '/c-asset/' . $media->id,
                'raw_url'    => $media->url,
                'title'      => $media->display_name,
                'driver'     => $media->driver,
                'mime'       => $media->mime_type,
                'is_image'   => $media->is_image,
                'is_cloud'   => $media->is_cloud,
                'public_id'  => $media->cloudinary_public_id,
                // Transformation URLs (Cloudinary only)
                'thumb_url'  => $media->is_cloud ? $media->thumbnailUrl(400) : $media->url,
                'web_url'    => '/c-asset/' . $media->id,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function serve(Request $request, string $filename)
    {
        $filename = basename($filename);
        $path     = public_path('media/' . $filename);

        if (!file_exists($path)) abort(404, 'File not found.');

        $ext  = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $mime = $this->allowedExtensions[$ext]
             ?? (function_exists('mime_content_type') ? mime_content_type($path) : null)
             ?: 'application/octet-stream';

        $forceDownload = $request->boolean('dl');
        $inline        = !$forceDownload && in_array($ext, ['pdf','jpg','jpeg','png','gif','webp','svg','mp4','mov','txt']);
        $disposition   = $inline ? 'inline' : 'attachment';

        return response()->file($path, [
            'Content-Type'           => $mime,
            'Content-Disposition'    => "{$disposition}; filename=\"{$filename}\"",
            'Cache-Control'          => 'public, max-age=86400',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function update(Request $request, int $id)
    {
        $media = MediaFile::findOrFail($id);
        if (auth()->user()->role !== 'super_admin' && $media->uploaded_by !== auth()->id()) {
            abort(403, 'Unauthorized to edit this media.');
        }
        $data  = $request->validate([
            'title'   => ['nullable', 'string', 'max:255'],
            'alt'     => ['nullable', 'string', 'max:255'],
            'caption' => ['nullable', 'string', 'max:500'],
            'tags'    => ['nullable', 'string', 'max:500'],
        ]);
        $media->update($data);

        if ($request->expectsJson()) return response()->json(['success' => true, 'media' => $media]);
        return back()->with('success', 'Media updated.');
    }

    public function destroy(int $id)
    {
        abort_unless(auth()->user()->isAdmin(), 403, 'Only Admins can delete media.');

        $media = MediaFile::findOrFail($id);
        if (auth()->user()->role !== 'super_admin' && $media->uploaded_by !== auth()->id()) {
            abort(403, 'Unauthorized to delete this media.');
        }

        // Delete from Cloudinary if stored there
        if ($media->is_cloud && $media->cloudinary_public_id) {
            $this->cloudinary->deleteAny($media->cloudinary_public_id);
        } elseif (!$media->is_cloud) {
            // Delete local file
            $local = public_path(ltrim($media->file_path, '/'));
            if (file_exists($local)) @unlink($local);
            
            // Delete variants
            if (!empty($media->variants)) {
                foreach ($media->variants as $variantPath) {
                    $vp = public_path(ltrim($variantPath, '/'));
                    if (file_exists($vp)) @unlink($vp);
                }
            }
        }

        $media->delete();

        if (request()->expectsJson()) return response()->json(['success' => true]);
        return back()->with('success', 'File deleted.');
    }

    public function optimize(Request $request, int $id)
    {
        if (!extension_loaded('gd')) {
            return response()->json(['error' => 'Image optimization requires the PHP GD extension, which is not currently installed or enabled on your server. Please enable extension=gd in your php.ini file.'], 500);
        }

        $media = MediaFile::findOrFail($id);
        if (auth()->user()->role !== 'super_admin' && $media->uploaded_by !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        if ($media->is_cloud || !in_array(strtolower(pathinfo($media->file_path, PATHINFO_EXTENSION)), ['jpg','jpeg','png','webp'])) {
            return response()->json(['error' => 'File not eligible for optimization'], 400);
        }

        $localPath = public_path(ltrim($media->file_path, '/'));
        if (!file_exists($localPath)) {
            return response()->json(['error' => 'File not found on disk'], 404);
        }

        $basename = pathinfo($media->file_path, PATHINFO_FILENAME);
        // Strip out prefixed size keywords if this was generated before
        $basename = preg_replace('/^(thumb|medium|large|original)_/', '', $basename);
        
        $ext = pathinfo($media->file_path, PATHINFO_EXTENSION);
        
        $variants = $this->imageOptimizer->process($localPath, $basename, $ext);
        
        if (empty($variants)) {
            return response()->json(['error' => 'Optimization failed (perhaps GD is missing or format is unsupported)'], 500);
        }

        $newPath = $variants['original'] ?? $media->file_path;
        $finalExt = strtolower(pathinfo($newPath, PATHINFO_EXTENSION));
        $newMime = $finalExt === 'jpg' || $finalExt === 'jpeg' ? 'image/jpeg' : ($finalExt === 'png' ? 'image/png' : 'image/webp');

        $media->update([
            'variants' => $variants,
            'file_path' => $newPath,
            'mime_type' => $newMime,
            'size' => filesize(public_path(ltrim($newPath, '/'))),
        ]);

        return response()->json(['success' => true, 'variants' => $variants]);
    }

    public function replace(Request $request, int $id)
    {
        $maxKb = min(32768, (int) ($this->getPhpUploadLimitBytes() / 1024));
        $request->validate(['file' => ['required', 'file', "max:{$maxKb}"]]);

        $media = MediaFile::findOrFail($id);
        if (auth()->user()->role !== 'super_admin' && $media->uploaded_by !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        $oldUrl = $media->url;
        $oldPath = ltrim($media->file_path, '/');

        $file = $request->file('file');
        $ext  = strtolower($file->getClientOriginalExtension());
        $mime = $this->resolveMime($file);

        if (!$this->isMimeAllowed($mime, $ext)) {
            return response()->json(['error' => "File type not allowed: .{$ext}"], 422);
        }

        try {
            // Drop old file from storage
            if ($media->is_cloud && $media->cloudinary_public_id) {
                $this->cloudinary->deleteAny($media->cloudinary_public_id);
            } elseif (!$media->is_cloud) {
                $local = public_path($oldPath);
                if (file_exists($local)) @unlink($local);
                
                // Delete variants
                if (!empty($media->variants)) {
                    foreach ($media->variants as $variantPath) {
                        $vp = public_path(ltrim($variantPath, '/'));
                        if (file_exists($vp)) @unlink($vp);
                    }
                }
            }

            // Upload new file
            $meta = $this->handleUpload($file, $media->driver);

            // Update record
            $media->update(array_merge($meta, [
                'title'       => $file->getClientOriginalName(),
                'uploaded_by' => Auth::id(),
            ]));

            $newUrl = $media->url;
            $newPath = ltrim($media->file_path, '/');

            // Find and Replace throughout the CMS if path/url changed
            $this->replaceReferences($oldUrl, $oldPath, $newUrl, $newPath);

            return response()->json(['success' => true, 'url' => $newUrl]);
            
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function json(Request $request)
    {
        $query = MediaFile::latest();

        if (Auth::user()->role !== 'super_admin') {
            $query->where('uploaded_by', Auth::id());
        }

        if ($search = $request->get('search'))    $query->search($search);
        if ($request->get('images_only'))          $query->where('mime_type', 'like', 'image/%');

        $media = $query->limit(200)->get()->map(fn($m) => [
            'id'         => $m->id,
            'url'        => '/c-asset/' . $m->id,
            'raw_url'    => $m->url,
            'thumb_url'  => $m->is_cloud ? $m->thumbnailUrl(400) : $m->url,
            'web_url'    => '/c-asset/' . $m->id,
            'title'      => $m->display_name,
            'alt'        => $m->alt,
            'caption'    => $m->caption,
            'mime'       => $m->mime_type,
            'is_image'   => $m->is_image,
            'is_cloud'   => $m->is_cloud,
            'driver'     => $m->driver,
            'public_id'  => $m->cloudinary_public_id,
            'human_size' => $m->human_size,
            'icon'       => $m->icon,
            'is_optimized' => $m->isOptimized(),
            'variants'   => $m->variants,
            'uploader'   => $m->uploader ? current(explode(' ', $m->uploader->name)) : 'System',
        ]);

        return response()->json($media);
    }

    public function usage(int $id)
    {
        try {
            $result = app(MediaUsageService::class)->findUsage($id);
            return response()->json($result);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // ── PHP limit helpers ──────────────────────────────────────────────────

    private function getPhpUploadLimitBytes(): int
    {
        $toBytes = function (string $val): int {
            $val  = trim($val);
            $last = strtolower($val[strlen($val) - 1]);
            $num  = (int) $val;
            return match ($last) {
                'g' => $num * 1024 ** 3,
                'm' => $num * 1024 ** 2,
                'k' => $num * 1024,
                default => $num,
            };
        };
        $upload = $toBytes(ini_get('upload_max_filesize') ?: '8M');
        $post   = $toBytes(ini_get('post_max_size')        ?: '8M');
        return min($upload, $post);
    }

    public function getPhpUploadLimit(): string
    {
        $bytes = $this->getPhpUploadLimitBytes();
        if ($bytes >= 1024 ** 3) return round($bytes / 1024 ** 3, 1) . ' GB';
        if ($bytes >= 1024 ** 2) return round($bytes / 1024 ** 2, 1) . ' MB';
        return round($bytes / 1024, 1) . ' KB';
    }

    private function replaceReferences(string $oldUrl, string $oldPath, string $newUrl, string $newPath): void
    {
        if ($oldUrl === $newUrl && $oldPath === $newPath) return;

        // 1. Storage JSON Files (Pages, Templates, Components)
        $directories = ['data/pages', 'data/templates', 'data/components'];
        foreach ($directories as $dir) {
            $absDir = public_path($dir);
            if (is_dir($absDir)) {
                foreach (glob($absDir . '/*.json') as $f) {
                    $content = file_get_contents($f);
                    $mod = str_replace([$oldUrl, '/' . $oldPath], [$newUrl, '/' . $newPath], $content);
                    if ($mod !== $content) file_put_contents($f, $mod);
                }
            }
        }

        // 2. DB Web pages
        try {
            foreach (\App\Models\HtmlPage::all() as $page) {
                $changed = false;
                if (str_contains($page->html ?? '', $oldUrl) || str_contains($page->html ?? '', '/' . $oldPath)) {
                    $page->html = str_replace([$oldUrl, '/' . $oldPath], [$newUrl, '/' . $newPath], $page->html);
                    $changed = true;
                }
                if (is_array($page->meta)) {
                    $metaStr = json_encode($page->meta);
                    if (str_contains($metaStr, $oldUrl) || str_contains($metaStr, '/' . $oldPath)) {
                        $page->meta = json_decode(str_replace([$oldUrl, '/' . $oldPath], [$newUrl, '/' . $newPath], $metaStr), true);
                        $changed = true;
                    }
                }
                if ($changed) $page->save();
            }
        } catch (\Throwable $e) {}

        // 3. DB Templates
        try {
            foreach (\App\Models\HtmlTemplate::all() as $tpl) {
                if (str_contains($tpl->html ?? '', $oldUrl) || str_contains($tpl->html ?? '', '/' . $oldPath)) {
                    $tpl->html = str_replace([$oldUrl, '/' . $oldPath], [$newUrl, '/' . $newPath], $tpl->html);
                    $tpl->save();
                }
            }
        } catch (\Throwable $e) {}

        // 4. DB Identity Settings
        try {
            $settings = \App\Models\Setting::whereIn('key', ['site_logo', 'site_favicon', 'site_og_image'])->get();
            foreach ($settings as $setting) {
                if ($setting->value === $oldUrl || $setting->value === '/' . $oldPath) {
                    $setting->value = $newUrl;
                    $setting->save();
                }
            }
        } catch (\Throwable $e) {}

        // 5. Dynamic CRUD Tables (Text/String fields)
        try {
            $tables = \App\Models\TablesRegistry::all();
            foreach ($tables as $t) {
                $tableName = $t->table_name;
                $cols = \Illuminate\Support\Facades\Schema::getColumnListing($tableName);
                foreach ($cols as $col) {
                    \Illuminate\Support\Facades\DB::update(
                        "UPDATE {$tableName} SET {$col} = REPLACE({$col}, ?, ?) WHERE {$col} LIKE ?",
                        [$oldUrl, $newUrl, '%' . $oldUrl . '%']
                    );
                    \Illuminate\Support\Facades\DB::update(
                        "UPDATE {$tableName} SET {$col} = REPLACE({$col}, ?, ?) WHERE {$col} LIKE ?",
                        ['/' . $oldPath, '/' . $newPath, '%/' . $oldPath . '%']
                    );
                }
            }
        } catch (\Throwable $e) {}
    }

    public function merge(Request $request)
    {
        $request->validate([
            'master_id' => 'required|integer|exists:media_files,id',
            'duplicate_ids' => 'required|array',
            'duplicate_ids.*' => 'integer|exists:media_files,id'
        ]);

        if (Auth::user()->role !== 'super_admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            $master = MediaFile::findOrFail($request->master_id);
            $duplicates = MediaFile::whereIn('id', $request->duplicate_ids)->get();

            $masterUrl = $master->url;
            $masterPath = ltrim($master->file_path, '/');

            foreach ($duplicates as $dup) {
                if ($dup->id === $master->id) continue;

                $oldUrl = $dup->url;
                $oldPath = ltrim($dup->file_path, '/');

                $this->replaceReferences($oldUrl, $oldPath, $masterUrl, $masterPath);

                // Delete Duplicate File
                if ($dup->is_cloud && $dup->cloudinary_public_id) {
                    $this->cloudinary->deleteAny($dup->cloudinary_public_id);
                } elseif (!$dup->is_cloud) {
                    @unlink(public_path($oldPath));
                    if (!empty($dup->variants)) {
                        foreach ($dup->variants as $vp) @unlink(public_path(ltrim($vp, '/')));
                    }
                }
                $dup->delete();
            }

            return response()->json(['success' => true]);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
