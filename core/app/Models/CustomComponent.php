<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomComponent extends Model
{
    protected $table = 'custom_components';

    protected $fillable = [
        'name', 'slug', 'category', 'html_template', 'css', 'js',
        'schema_json', 'icon', 'is_active', 'created_by',
    ];

    protected $casts = [
        'schema_json' => 'array',
        'is_active'   => 'boolean',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Render the template with given field values.
     * Replaces {{key}} with escaped values.
     */
    public function render(array $values = []): string
    {
        $html = $this->html_template;
        foreach ($values as $key => $val) {
            $html = str_replace('{{' . $key . '}}', htmlspecialchars((string)$val, ENT_QUOTES), $html);
        }
        // Clear any unreplaced placeholders
        $html = preg_replace('/\{\{[a-z_]+\}\}/i', '', $html);
        return $html;
    }

    /**
     * Default field values from schema_json.
     */
    public function defaultValues(): array
    {
        $defaults = [];
        foreach (($this->schema_json['fields'] ?? []) as $field) {
            $defaults[$field['key']] = $field['default'] ?? '';
        }
        return $defaults;
    }
}
