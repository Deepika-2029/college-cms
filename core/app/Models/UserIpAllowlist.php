<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserIpAllowlist extends Model
{
    protected $fillable = ['user_id', 'ip_address', 'label', 'added_by'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    /**
     * Check if a user has any IP restrictions.
     * If yes, the given IP must be in their allowlist.
     */
    public static function isAllowed(int $userId, string $ip): bool
    {
        $count = static::where('user_id', $userId)->count();
        if ($count === 0) return true; // no restrictions
        return static::where('user_id', $userId)->where('ip_address', $ip)->exists();
    }
}
