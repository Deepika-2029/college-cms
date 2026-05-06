<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\CustomComponent;

return new class extends Migration
{
    public function up(): void
    {
        if (CustomComponent::where('slug', 'custom-card')->exists()) return;

        CustomComponent::create([
            'name'     => 'Custom Card',
            'slug'     => 'custom-card',
            'category' => 'custom',
            'icon'     => '🃏',
            'html_template' => '<div style="background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:24px;text-align:center">
  <div style="font-size:36px;margin-bottom:12px">{{icon}}</div>
  <h3 style="font-size:18px;font-weight:700;color:#111827;margin:0 0 8px">{{title}}</h3>
  <p style="font-size:14px;color:#6b7280;line-height:1.6;margin:0 0 16px">{{description}}</p>
  <a href="{{link_url}}" style="display:inline-block;padding:9px 20px;background:#6366f1;color:#fff;border-radius:7px;font-weight:600;font-size:13px;text-decoration:none">{{link_label}}</a>
</div>',
            'css' => '',
            'js'  => '',
            'schema_json' => [
                'fields' => [
                    ['type' => 'text',     'key' => 'icon',        'label' => 'Icon (emoji)',  'default' => '⭐'],
                    ['type' => 'text',     'key' => 'title',       'label' => 'Title',         'default' => 'Card Title'],
                    ['type' => 'textarea', 'key' => 'description', 'label' => 'Description',   'default' => 'Your description here.'],
                    ['type' => 'url',      'key' => 'link_url',    'label' => 'Button URL',    'default' => '#'],
                    ['type' => 'text',     'key' => 'link_label',  'label' => 'Button Label',  'default' => 'Learn More'],
                ],
            ],
            'is_active'  => true,
            'created_by' => null,
        ]);
    }

    public function down(): void
    {
        CustomComponent::where('slug', 'custom-card')->delete();
    }
};
