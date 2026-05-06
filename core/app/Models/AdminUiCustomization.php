<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminUiCustomization extends Model
{
    protected $fillable = [
        'type', 'content', 'is_active', 'description', 'created_by',
    ];

    protected $casts = ['is_active' => 'boolean'];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Get active CSS content */
    public static function activeCSS(): string
    {
        try {
            return static::where('type', 'css')->where('is_active', true)->value('content') ?? '';
        } catch (\Throwable) {
            return '';
        }
    }

    /** Get active JS content */
    public static function activeJS(): string
    {
        try {
            return static::where('type', 'js')->where('is_active', true)->value('content') ?? '';
        } catch (\Throwable) {
            return '';
        }
    }
}
