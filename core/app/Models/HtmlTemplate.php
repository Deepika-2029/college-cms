<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class HtmlTemplate extends Model
{
    protected $table = 'html_templates';
    protected $fillable = ['name','slug','category','html','css','js','thumbnail','description','is_active','use_bootstrap','created_by'];
    protected $casts = ['is_active' => 'boolean', 'use_bootstrap' => 'boolean'];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($m) {
            if (empty($m->slug)) {
                $m->slug = Str::slug($m->name) ?: 'template-' . Str::random(6);
            }
        });
    }

    /** Inject data-cms-el IDs into all editable elements and return tagged HTML */
    public function getTaggedHtml(): string
    {
        return self::tagHtml($this->html ?? '');
    }

    /** Static: scan HTML and add data-cms-el="uuid" to editable elements */
    public static function tagHtml(string $html): string
    {
        if (empty(trim($html))) return $html;
        // Use regex to tag common editable elements that don't already have data-cms-el
        $tags = ['h1','h2','h3','h4','h5','h6','p','span','a','button','li','td','th','label','strong','em','small','blockquote'];
        $pattern = '/<(' . implode('|', $tags) . ')(\s[^>]*)?(?!.*data-cms-el)>/i';
        $html = preg_replace_callback($pattern, function ($m) {
            $id = 'cms-' . substr(md5($m[0] . microtime()), 0, 8) . rand(100,999);
            $attrs = $m[2] ?? '';
            return "<{$m[1]}{$attrs} data-cms-el=\"{$id}\">";
        }, $html);
        // Tag images
        $html = preg_replace_callback('/<img(\s[^>]*)(?!.*data-cms-el)>/i', function ($m) {
            $id = 'cms-img-' . substr(md5($m[0] . microtime()), 0, 8) . rand(100,999);
            return "<img{$m[1]} data-cms-el=\"{$id}\" data-cms-type=\"image\">";
        }, $html);
        return $html;
    }
}
