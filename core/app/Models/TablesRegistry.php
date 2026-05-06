<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TablesRegistry extends Model
{
    public $timestamps = false;

    protected $table = 'tables_registry';

    protected $fillable = ['table_name', 'ui_type'];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function ($model) {
            $model->created_at = now();
        });
    }
}
