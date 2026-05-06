<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PageVersion extends Model
{
    protected $fillable = [
        'slug', 'title', 'rows', 'sections', 'global_css', 'status', 'saved_by', 'version_note',
    ];

    protected $casts = [
        'rows'     => 'array',
        'sections' => 'array',
    ];

    public function savedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'saved_by');
    }

    /** Create a version snapshot from page data array */
    public static function snapshot(string $slug, array $pageData, int $userId = null, string $note = null): static
    {
        return static::create([
            'slug'         => $slug,
            'title'        => $pageData['title'] ?? 'Untitled',
            'rows'         => $pageData['rows'] ?? null,
            'sections'     => $pageData['sections'] ?? null,
            'global_css'   => $pageData['global_css'] ?? null,
            'status'       => $pageData['status'] ?? 'published',
            'saved_by'     => $userId,
            'version_note' => $note,
        ]);
    }
}
