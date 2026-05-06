<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminMenuItem extends Model
{
    protected $fillable = [
        'label', 'icon', 'route', 'roles',
        'parent_id', 'order', 'is_active', 'section',
    ];

    protected $casts = [
        'roles'     => 'array',
        'is_active' => 'boolean',
    ];

    public function children(): HasMany
    {
        return $this->hasMany(static::class, 'parent_id')->orderBy('order');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(static::class, 'parent_id');
    }

    /** Check if this item is visible for a given role */
    public function visibleFor(string $role): bool
    {
        if (! $this->is_active) return false;
        if (empty($this->roles)) return true;
        return in_array($role, $this->roles, true);
    }

    /** Resolve the URL — supports named routes and full URLs */
    public function resolveUrl(): string
    {
        if (! $this->route) return '#';
        if (str_starts_with($this->route, 'http')) return $this->route;
        try {
            return route($this->route);
        } catch (\Throwable) {
            return '#';
        }
    }

    /** Get all top-level items grouped by section */
    public static function forSidebar(string $role): array
    {
        $items = static::whereNull('parent_id')
            ->where('is_active', true)
            ->orderBy('order')
            ->get();

        $sections = [];
        foreach ($items as $item) {
            if (! $item->visibleFor($role)) continue;
            $sections[$item->section][] = $item;
        }

        return $sections;
    }
}
