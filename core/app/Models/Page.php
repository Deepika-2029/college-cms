<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Page extends Model
{
    protected $fillable = [
        'title', 'slug', 'status', 'created_by', 'updated_by', 'published_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    // ── Relationships ─────────────────────────────────────────────────────

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // ── Accessors ─────────────────────────────────────────────────────────

    public function getIsPublishedAttribute(): bool
    {
        return $this->status === 'published';
    }

    public function getStatusBadgeAttribute(): string
    {
        return $this->status === 'published' ? 'badge-green' : 'badge-yellow';
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    /** Path to the page's JSON file */
    public function jsonPath(): string
    {
        return public_path("data/pages/{$this->slug}.json");
    }

    /** Read the full page JSON (sections + title) */
    public function readJson(): ?array
    {
        $path = $this->jsonPath();
        if (! file_exists($path)) return null;
        return json_decode(file_get_contents($path), true);
    }

    /** Sync a DB record from a JSON file (upsert) */
    public static function syncFromJson(array $pageData, ?int $userId = null): self
    {
        return static::updateOrCreate(
            ['slug' => $pageData['slug']],
            [
                'title'      => $pageData['title'] ?? 'Untitled',
                'status'     => $pageData['status'] ?? 'draft',
                'updated_by' => $userId,
            ]
        );
    }

    // ── Scopes ────────────────────────────────────────────────────────────

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }
}
