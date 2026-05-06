<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class HtmlComponent extends Model
{
    protected $table = 'html_components';
    protected $fillable = ['name','slug','category','html','css','js','icon','thumbnail','description','is_active','created_by'];
    protected $casts = ['is_active' => 'boolean'];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($m) {
            if (empty($m->slug)) {
                $m->slug = Str::slug($m->name) ?: 'comp-' . Str::random(6);
            }
        });
    }
}
