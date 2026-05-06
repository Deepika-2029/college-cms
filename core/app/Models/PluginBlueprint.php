<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PluginBlueprint extends Model
{
    protected $fillable = [
        'name', 'slug', 'description', 'category',
        'fields', 'rows', 'html_template', 'custom_css', 'custom_js',
        'published', 'created_by',
    ];

    protected $casts = [
        'fields'    => 'array',
        'rows'      => 'array',
        'published' => 'boolean',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
