<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Template extends Model
{
    protected $fillable = [
        'name', 'slug', 'description', 'category',
        'sections', 'rows', 'thumbnail', 'is_active',
        'created_by', 'updated_by',
    ];

    protected $casts = [
        'sections'  => 'array',
        'rows'      => 'array',
        'is_active' => 'boolean',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function getThumbnailUrlAttribute(): ?string
    {
        if (! $this->thumbnail) return null;
        if (str_starts_with($this->thumbnail, 'http')) return $this->thumbnail;
        return asset(ltrim($this->thumbnail, '/'));
    }

    /** Number of rows (new format) or sections (legacy) */
    public function getSectionCountAttribute(): int
    {
        if ($this->rows) return count($this->rows);
        return count($this->sections ?? []);
    }

    public function getUsedPluginsAttribute(): array
    {
        if ($this->rows) {
            $types = [];
            foreach ($this->rows as $row) {
                foreach ($row['cells'] ?? [] as $cell) {
                    if ($c = $cell['component'] ?? null) $types[] = $c;
                }
            }
            return array_unique($types);
        }
        return array_unique(array_column($this->sections ?? [], 'plugin'));
    }

    public static function allCategories(): array
    {
        return static::distinct()->pluck('category')->sort()->values()->toArray();
    }
}
