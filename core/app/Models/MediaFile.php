<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MediaFile extends Model
{
    protected $table = 'media';

    protected $fillable = [
        'file_path', 'cloudinary_public_id', 'driver',
        'title', 'alt', 'caption', 'tags',
        'mime_type', 'size', 'width', 'height', 'uploaded_by', 'variants',
    ];

    protected $casts = [
        'size' => 'integer', 'width' => 'integer', 'height' => 'integer', 'variants' => 'array',
    ];

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    // ── URL resolution ─────────────────────────────────────────────────────

    public function getUrlAttribute(): string
    {
        $fp = $this->file_path;
        // Cloudinary and other external URLs pass through directly
        if (str_starts_with($fp, 'http')) return $fp;
        // Use authenticated serve route so Content-Type headers are set correctly
        try {
            return route('media.serve', ['filename' => basename($fp)]);
        } catch (\Throwable) {
            return asset(ltrim($fp, '/'));
        }
    }

    /**
     * Get the URL for a specific local variant (thumb, medium, large, original).
     * Falls back to the original URL if the variant doesn't exist.
     */
    public function localVariantUrl(string $size = 'large'): string
    {
        if ($this->is_cloud || empty($this->variants) || empty($this->variants[$size])) {
            return $this->url;
        }

        $fp = $this->variants[$size];
        
        try {
            return route('media.serve', ['filename' => basename($fp)]);
        } catch (\Throwable) {
            return asset(ltrim($fp, '/'));
        }
    }

    /**
     * Generate a srcset attribute string for local variants.
     */
    public function srcset(): string
    {
        if ($this->is_cloud || empty($this->variants)) {
            return '';
        }

        $sets = [];
        $sizes = ['thumb' => 400, 'medium' => 800, 'large' => 1600];
        
        foreach ($sizes as $name => $width) {
            if (!empty($this->variants[$name])) {
                $url = $this->localVariantUrl($name);
                $sets[] = "{$url} {$width}w";
            }
        }
        
        return implode(', ', $sets);
    }

    /**
     * Generate a full <picture> element.
     */
    public function pictureTag(array $attrs = []): string
    {
        $src = $this->url;
        $alt = htmlspecialchars($this->alt ?: $this->display_name);
        $w = $this->width ? ' width="'.$this->width.'"' : '';
        $h = $this->height ? ' height="'.$this->height.'"' : '';
        
        $attrStr = '';
        foreach ($attrs as $k => $v) {
            if ($k === 'class' || $k === 'loading' || $k === 'fetchpriority' || $k === 'decoding') {
                $attrStr .= ' '.htmlspecialchars($k).'="'.htmlspecialchars($v).'"';
            }
        }

        // Add defaults if they correspond to optimization best practices
        if (!isset($attrs['loading']) && !isset($attrs['fetchpriority'])) {
            $attrStr .= ' loading="lazy" decoding="async"';
        }

        if ($this->is_cloud || empty($this->variants)) {
            return "<img src=\"{$src}\" alt=\"{$alt}\"{$w}{$h}{$attrStr} />";
        }

        $srcset = $this->srcset();
        $largeUrl = $this->localVariantUrl('large'); // default src logic

        $sizesAttr = '(max-width: 600px) 400px, (max-width: 1200px) 800px, 1600px';

        return 
        "<picture>
            <source srcset=\"{$srcset}\" type=\"image/webp\" sizes=\"{$sizesAttr}\">
            <img src=\"{$src}\" alt=\"{$alt}\"{$w}{$h}{$attrStr} />
        </picture>";
    }

    // ── Cloudinary transformation URLs ─────────────────────────────────────

    /**
     * Return a Cloudinary transformation URL.
     *
     * @param  array  $transforms  e.g. ['w' => 800, 'h' => 600, 'c' => 'fill', 'q' => 'auto', 'f' => 'auto']
     */
    public function cloudinaryUrl(array $transforms = []): string
    {
        $url = $this->url;

        if (empty($transforms) || !$this->is_cloud) {
            return $url;
        }

        // Build transformation string: w_800,h_600,c_fill,q_auto,f_auto
        $parts = [];
        $keys  = ['w','h','c','g','q','f','r','e','o','a','dpr'];
        foreach ($keys as $k) {
            if (isset($transforms[$k])) $parts[] = "{$k}_{$transforms[$k]}";
        }
        $tStr = implode(',', $parts);

        // Inject after /upload/
        return preg_replace('#(/upload/)(?!v\d)#', "/upload/{$tStr}/", $url, 1) ?: $url;
    }

    /** Thumbnail: square crop, auto-gravity, 300×300 */
    public function thumbnailUrl(int $size = 300): string
    {
        if (!$this->is_cloud) return $this->url;
        return $this->cloudinaryUrl(['w' => $size, 'h' => $size, 'c' => 'fill', 'g' => 'auto', 'q' => 'auto', 'f' => 'auto']);
    }

    /** Web-optimised: limit to max width, auto quality + format */
    public function webUrl(int $maxWidth = 1200): string
    {
        if (!$this->is_cloud) return $this->url;
        return $this->cloudinaryUrl(['w' => $maxWidth, 'c' => 'limit', 'q' => 'auto', 'f' => 'auto']);
    }

    // ── Boolean helpers ────────────────────────────────────────────────────

    public function getIsCloudAttribute(): bool
    {
        return ($this->driver === 'cloudinary')
            || str_starts_with($this->file_path, 'http');
    }

    public function getIsImageAttribute(): bool
    {
        if ($this->mime_type) return str_starts_with($this->mime_type, 'image/');
        return (bool) preg_match('/\.(jpg|jpeg|png|gif|webp|svg)(\?.*)?$/i', $this->file_path);
    }

    public function getIsVideoAttribute(): bool
    {
        if ($this->mime_type) return str_starts_with($this->mime_type, 'video/');
        return (bool) preg_match('/\.(mp4|mov|webm|avi)(\?.*)?$/i', $this->file_path);
    }

    // ── Display helpers ────────────────────────────────────────────────────

    public function getHumanSizeAttribute(): string
    {
        if (! $this->size) return '—';
        $units = ['B', 'KB', 'MB', 'GB'];
        $size  = $this->size;
        $i     = 0;
        while ($size >= 1024 && $i < 3) { $size /= 1024; $i++; }
        return round($size, 1) . ' ' . $units[$i];
    }

    public function isOptimized(): bool
    {
        return !empty($this->variants);
    }

    public function getTagListAttribute(): array
    {
        if (! $this->tags) return [];
        return array_map('trim', explode(',', $this->tags));
    }

    public function getBasenameAttribute(): string
    {
        return basename(parse_url($this->file_path, PHP_URL_PATH) ?? $this->file_path);
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->title ?: $this->basename;
    }

    public function getIconAttribute(): string
    {
        $fp = strtolower($this->file_path);
        if ($this->is_image)                       return '🖼️';
        if (str_ends_with($fp, '.pdf'))             return '📄';
        if (preg_match('/\.(mp4|mov|avi)$/', $fp)) return '🎬';
        if (preg_match('/\.(doc|docx)$/', $fp))    return '📝';
        if (preg_match('/\.(xls|xlsx)$/', $fp))    return '📊';
        return '📁';
    }

    // ── Scopes ─────────────────────────────────────────────────────────────

    public function scopeImages($query)
    {
        return $query->where(function ($q) {
            $q->where('mime_type', 'like', 'image/%')
              ->orWhere('file_path', 'regexp', '\.(jpg|jpeg|png|gif|webp|svg)(\?|$)');
        });
    }

    public function scopeSearch($query, string $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('title',     'like', "%{$term}%")
              ->orWhere('alt',      'like', "%{$term}%")
              ->orWhere('tags',     'like', "%{$term}%")
              ->orWhere('file_path','like', "%{$term}%");
        });
    }
}
