<?php

namespace App\Services;

use Illuminate\Support\Facades\Schema;

class CrudUiGeneratorService
{
    // ─── Column Classification ───────────────────────────────────────────────

    private function classify(string $col): string
    {
        $c = strtolower($col);
        if (in_array($c, ['id', 'created_at', 'updated_at'], true)) return 'system';
        if (str_starts_with($c, 'is_') || str_starts_with($c, 'has_') || in_array($c, ['active', 'published', 'enabled', 'featured'], true)) return 'boolean';
        if (preg_match('/(image|photo|banner|thumbnail|gallery|avatar|picture|cover|logo|icon|screenshot|poster)/', $c)) return 'image';
        if (preg_match('/(document|file|attachment|resume|pdf|report|certificate|brochure|slides|ppt|video|presentation|download)/', $c) && !preg_match('/(name|type|title)/', $c)) return 'document';
        if (preg_match('/(description|body|content|bio|text|summary|detail|note|remark|about|overview)/', $c)) return 'textarea';
        if (preg_match('/(email|mail)/', $c)) return 'email';
        if (preg_match('/(url|link|website|href)/', $c) && !preg_match('/(description|body)/', $c)) return 'url';
        if (preg_match('/(date|_at|_on)/', $c)) return 'date';
        if (preg_match('/(price|amount|cost|salary|fee|rate|discount)/', $c)) return 'number';
        if (preg_match('/(phone|mobile|contact|tel)/', $c)) return 'tel';
        if (preg_match('/(color|colour)/', $c)) return 'color';
        if (preg_match('/(order|sort|position|rank|priority)/', $c)) return 'number';
        return 'text';
    }

    // ─── Main Entry Point ────────────────────────────────────────────────────

    public function generate(string $table): void
    {
        $dir = resource_path("crud-ui/{$table}");
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        $columns = Schema::getColumnListing($table);
        $meta    = $this->buildMeta($table, $columns);

        file_put_contents("{$dir}/config.json", json_encode($meta, JSON_PRETTY_PRINT));
        file_put_contents("{$dir}/list.html",   $this->buildList($table, $meta));
        file_put_contents("{$dir}/form.html",   $this->buildForm($table, $meta));
        file_put_contents("{$dir}/style.css",   $this->buildCss($table));
        file_put_contents("{$dir}/script.js",   $this->buildJs($table));
    }

    // ─── Metadata Builder ────────────────────────────────────────────────────

    private function buildMeta(string $table, array $columns): array
    {
        $cols = [];
        foreach ($columns as $col) {
            $type = $this->classify($col);
            $cols[] = [
                'name'         => $col,
                'label'        => ucwords(str_replace('_', ' ', $col)),
                'field_type'   => $type,
                'show_in_list' => !in_array($type, ['textarea', 'document', 'system'], true),
                'searchable'   => in_array($type, ['text', 'email', 'url', 'tel'], true),
                'editable'     => $type !== 'system',
            ];
        }
        return [
            'table'      => $table,
            'label'      => ucwords(str_replace('_', ' ', $table)),
            'ui_type'    => 'list',
            'drag_order' => $columns,
            'columns'    => $cols,
            'created_at' => now()->toDateTimeString(),
        ];
    }

    // ─── List Template ───────────────────────────────────────────────────────

    private function buildList(string $table, array $meta): string
    {
        $title   = $meta['label'];
        $listCols = array_filter($meta['columns'], fn($c) => $c['show_in_list']);

        $headers = '';
        foreach ($listCols as $col) {
            $headers .= "                        <th>{$col['label']}</th>\n";
        }

        $cellLogic = '';
        foreach ($listCols as $col) {
            $name = $col['name'];
            $type = $col['field_type'];
            $cellLogic .= match ($type) {
                'image'   => "                        <td data-col=\"{$name}\"><img src=\"{{row.{$name}}}\" alt=\"\" style=\"width:48px;height:48px;object-fit:cover;border-radius:6px;\" loading=\"lazy\"></td>\n",
                'boolean' => "                        <td data-col=\"{$name}\"><span class=\"cms-badge {{row.{$name} ? 'cms-badge-green' : 'cms-badge-grey'}}\">{{row.{$name} ? '✓ Yes' : '✗ No'}}</span></td>\n",
                'url'     => "                        <td data-col=\"{$name}\"><a href=\"{{row.{$name}}}\" target=\"_blank\" class=\"cms-link\">Open ↗</a></td>\n",
                default   => "                        <td data-col=\"{$name}\">{{row.{$name}}}</td>\n",
            };
        }

        return <<<HTML
<!-- Auto-generated List Template for: {$table} -->
<div class="cms-ui-wrapper cms-fade-in" id="cms-{$table}-list">
    <div class="cms-ui-header">
        <div class="cms-ui-title-group">
            <h2>{$title}</h2>
            <p>Manage all records in your {$title} database.</p>
        </div>
        <button class="cms-btn cms-btn-primary" onclick="openCreateForm()">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Add New Record
        </button>
    </div>
    <div class="cms-glass-card">
        <div class="table-responsive">
            <table class="cms-modern-table">
                <thead>
                    <tr>
{$headers}                        <th class="actions-cell">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Backend populates rows — each row uses the cell logic above -->
                    <!-- cell-logic-hint:
{$cellLogic}                    -->
                </tbody>
            </table>
        </div>
    </div>
</div>
HTML;
    }

    // ─── Form Template ───────────────────────────────────────────────────────

    private function buildForm(string $table, array $meta): string
    {
        $title       = rtrim($meta['label'], 's');
        $fieldsHtml  = '';

        foreach ($meta['columns'] as $col) {
            if (!$col['editable']) continue;

            $name  = $col['name'];
            $label = $col['label'];
            $type  = $col['field_type'];

            $input = match ($type) {
                'image'    => <<<HTML
                    <div class="cms-media-field" data-col="{$name}" data-accept="image/*">
                        <div class="cms-media-preview" id="prev_{$name}"></div>
                        <div class="cms-media-actions">
                            <button type="button" class="cms-btn cms-btn-sm" onclick="openMediaPicker('{$name}','image')">📷 Upload / Pick</button>
                            <button type="button" class="cms-btn cms-btn-sm cms-btn-ghost" onclick="addMediaUrl('{$name}')">🔗 Add URL</button>
                        </div>
                        <input type="hidden" name="{$name}" id="f_{$name}">
                    </div>
HTML,
                'document' => <<<HTML
                    <div class="cms-media-field" data-col="{$name}" data-accept="*">
                        <div class="cms-media-preview" id="prev_{$name}"></div>
                        <div class="cms-media-actions">
                            <button type="button" class="cms-btn cms-btn-sm" onclick="openMediaPicker('{$name}','all')">📎 Upload / Pick</button>
                            <button type="button" class="cms-btn cms-btn-sm cms-btn-ghost" onclick="addMediaUrl('{$name}')">🔗 Add URL</button>
                        </div>
                        <input type="hidden" name="{$name}" id="f_{$name}">
                    </div>
HTML,
                'boolean'  => <<<HTML
                    <div class="cms-toggle-wrap">
                        <input type="hidden" name="{$name}" value="0">
                        <label class="cms-toggle">
                            <input type="checkbox" name="{$name}" id="f_{$name}" value="1">
                            <span class="cms-toggle-slider"></span>
                        </label>
                        <span class="cms-toggle-label" id="lbl_{$name}">Off</span>
                    </div>
HTML,
                'textarea' => <<<HTML
                    <textarea name="{$name}" id="f_{$name}" class="cms-form-control" rows="4" placeholder="Enter {$label}..."></textarea>
HTML,
                'email'    => <<<HTML
                    <input type="email" name="{$name}" id="f_{$name}" class="cms-form-control" placeholder="name@example.com">
HTML,
                'url'      => <<<HTML
                    <input type="url" name="{$name}" id="f_{$name}" class="cms-form-control" placeholder="https://">
HTML,
                'date'     => <<<HTML
                    <input type="datetime-local" name="{$name}" id="f_{$name}" class="cms-form-control">
HTML,
                'number'   => <<<HTML
                    <input type="number" name="{$name}" id="f_{$name}" class="cms-form-control" placeholder="0" step="any">
HTML,
                'tel'      => <<<HTML
                    <input type="tel" name="{$name}" id="f_{$name}" class="cms-form-control" placeholder="+91 00000 00000">
HTML,
                'color'    => <<<HTML
                    <input type="color" name="{$name}" id="f_{$name}" class="cms-form-control" style="height:48px;padding:4px;">
HTML,
                default    => <<<HTML
                    <input type="text" name="{$name}" id="f_{$name}" class="cms-form-control" placeholder="Enter {$label}...">
HTML,
            };

            $fieldsHtml .= <<<HTML

                <div class="cms-form-group">
                    <label for="f_{$name}">{$label}</label>
                    <div class="cms-input-wrapper">
{$input}
                    </div>
                </div>

HTML;
        }

        return <<<HTML
<!-- Auto-generated Form Template for: {$table} -->
<div class="cms-ui-wrapper cms-fade-in" id="cms-{$table}-form" style="display:none;">
    <div class="cms-ui-header">
        <div class="cms-ui-title-group">
            <h2 id="form-title">Create New {$title}</h2>
            <p>Fill out the details below to add or update a record.</p>
        </div>
        <button class="cms-btn cms-btn-ghost" onclick="closeForm()">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
            Back to List
        </button>
    </div>
    <div class="cms-glass-card">
        <form class="cms-modern-form" onsubmit="handleFormSubmit(event)">
            <div class="cms-form-grid">
{$fieldsHtml}
            </div>
            <div class="cms-form-actions">
                <button type="button" class="cms-btn cms-btn-light" onclick="closeForm()">Cancel</button>
                <button type="submit" class="cms-btn cms-btn-primary">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                    Save Record
                </button>
            </div>
        </form>
    </div>
</div>
HTML;
    }

    // ─── CSS ─────────────────────────────────────────────────────────────────

    private function buildCss(string $table): string
    {
        return <<<CSS
/* Auto-generated CRUD UI Styles for: {$table} */
:root {
    --cms-primary: #6366f1; --cms-primary-h: #4f46e5;
    --cms-text: #0f172a; --cms-muted: #64748b;
    --cms-border: #e2e8f0; --cms-surface: rgba(255,255,255,0.85);
    --cms-shadow: 0 10px 40px rgba(15,23,42,0.06);
    --cms-green: #22c55e; --cms-grey: #94a3b8; --cms-red: #ef4444;
}
.cms-ui-wrapper { font-family:'Inter',system-ui,sans-serif; color:var(--cms-text); padding:2rem 1rem; max-width:1200px; margin:0 auto; }
.cms-ui-header { display:flex; justify-content:space-between; align-items:flex-end; margin-bottom:2rem; flex-wrap:wrap; gap:1rem; }
.cms-ui-title-group h2 { font-size:2rem; font-weight:800; margin:0 0 .5rem; background:linear-gradient(135deg,#4f46e5,#ec4899); -webkit-background-clip:text; -webkit-text-fill-color:transparent; letter-spacing:-.02em; }
.cms-ui-title-group p  { font-size:1rem; color:var(--cms-muted); margin:0; }
.cms-glass-card { background:var(--cms-surface); backdrop-filter:blur(16px); border:1px solid rgba(255,255,255,0.6); box-shadow:var(--cms-shadow); border-radius:16px; overflow:hidden; }
.table-responsive { overflow-x:auto; width:100%; }
.cms-modern-table { width:100%; border-collapse:separate; border-spacing:0; }
.cms-modern-table th { background:rgba(248,250,252,0.7); padding:1.25rem 1.5rem; font-size:.8rem; font-weight:700; text-transform:uppercase; letter-spacing:.08em; color:var(--cms-muted); border-bottom:2px solid var(--cms-border); text-align:left; white-space:nowrap; }
.cms-modern-table td { padding:1rem 1.5rem; border-bottom:1px solid var(--cms-border); font-size:.95rem; vertical-align:middle; }
.cms-modern-table tbody tr:hover { background:rgba(99,102,241,0.04); }
.cms-modern-table tbody tr:last-child td { border-bottom:none; }
.actions-cell { text-align:right !important; }
.cms-link { color:var(--cms-primary); text-decoration:none; font-weight:500; }
.cms-link:hover { text-decoration:underline; }
/* Badges */
.cms-badge { display:inline-flex; align-items:center; padding:.25rem .65rem; border-radius:999px; font-size:.75rem; font-weight:700; }
.cms-badge-green { background:rgba(34,197,94,.15); color:#16a34a; }
.cms-badge-grey  { background:rgba(148,163,184,.15); color:#64748b; }
/* Form */
.cms-modern-form { padding:2.5rem; }
.cms-form-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(300px,1fr)); gap:1.75rem; }
.cms-form-group { display:flex; flex-direction:column; }
.cms-form-group label { font-weight:600; font-size:.9rem; color:var(--cms-text); margin-bottom:.5rem; }
.cms-input-wrapper { position:relative; }
.cms-form-control { width:100%; padding:.85rem 1.25rem; background:rgba(255,255,255,.9); border:1px solid var(--cms-border); border-radius:10px; font-size:1rem; color:var(--cms-text); transition:all .3s; box-sizing:border-box; }
.cms-form-control:focus { outline:none; border-color:var(--cms-primary); box-shadow:0 0 0 4px rgba(99,102,241,.15); }
textarea.cms-form-control { resize:vertical; min-height:100px; font-family:inherit; }
.cms-form-actions { display:flex; justify-content:flex-end; gap:1rem; margin-top:2rem; padding-top:1.5rem; border-top:1px solid rgba(0,0,0,.06); }
/* Toggle */
.cms-toggle-wrap { display:flex; align-items:center; gap:.75rem; margin-top:.25rem; }
.cms-toggle { position:relative; display:inline-block; width:44px; height:24px; }
.cms-toggle input { opacity:0; width:0; height:0; }
.cms-toggle-slider { position:absolute; cursor:pointer; inset:0; background:var(--cms-border); border-radius:24px; transition:.3s; }
.cms-toggle-slider:before { content:""; position:absolute; height:18px; width:18px; left:3px; bottom:3px; background:white; border-radius:50%; transition:.3s; box-shadow:0 2px 4px rgba(0,0,0,.2); }
.cms-toggle input:checked + .cms-toggle-slider { background:var(--cms-primary); }
.cms-toggle input:checked + .cms-toggle-slider:before { transform:translateX(20px); }
.cms-toggle-label { font-size:.85rem; color:var(--cms-muted); font-weight:500; }
/* Media field */
.cms-media-field { background:rgba(248,250,252,.8); border:1.5px dashed var(--cms-border); border-radius:10px; padding:1rem; }
.cms-media-preview { display:flex; flex-wrap:wrap; gap:.5rem; margin-bottom:.75rem; min-height:10px; }
.cms-media-preview:empty { display:none; }
.cms-media-actions { display:flex; gap:.5rem; flex-wrap:wrap; }
/* Buttons */
.cms-btn { display:inline-flex; align-items:center; justify-content:center; gap:.5rem; padding:.75rem 1.5rem; border-radius:10px; font-size:.95rem; font-weight:600; cursor:pointer; transition:all .25s; border:none; text-decoration:none; }
.cms-btn-primary { background:var(--cms-primary); color:white; box-shadow:0 4px 14px rgba(99,102,241,.3); }
.cms-btn-primary:hover { background:var(--cms-primary-h); transform:translateY(-2px); }
.cms-btn-ghost { background:transparent; color:var(--cms-muted); }
.cms-btn-ghost:hover { background:rgba(0,0,0,.05); color:var(--cms-text); }
.cms-btn-light { background:white; color:var(--cms-text); border:1px solid var(--cms-border); }
.cms-btn-light:hover { background:#f8fafc; }
.cms-btn-sm { padding:.45rem 1rem; font-size:.82rem; border-radius:7px; }
.cms-btn-icon { padding:.5rem; border-radius:8px; background:transparent; border:none; cursor:pointer; display:inline-flex; transition:all .2s; }
.cms-btn-icon:hover { background:rgba(99,102,241,.1); color:var(--cms-primary); }
.cms-btn-icon.danger:hover { color:var(--cms-red); background:rgba(239,68,68,.1); }
@keyframes cmsFadeIn { from{opacity:0;transform:translateY(15px)} to{opacity:1;transform:translateY(0)} }
.cms-fade-in { animation:cmsFadeIn .45s cubic-bezier(.16,1,.3,1); }
CSS;
    }

    // ─── JavaScript ──────────────────────────────────────────────────────────

    private function buildJs(string $table): string
    {
        return <<<JS
// Auto-generated UI Script for: {$table}
document.addEventListener('DOMContentLoaded', function () {
    // Stagger-animate table rows on load
    document.querySelectorAll('.cms-modern-table tbody tr').forEach((row, i) => {
        row.style.opacity = '0';
        row.style.animation = `cmsFadeIn 0.35s ease-out \${i * 0.04}s forwards`;
    });

    // Toggle label update for boolean fields
    document.querySelectorAll('.cms-toggle input[type="checkbox"]').forEach(cb => {
        const lbl = document.getElementById('lbl_' + cb.name);
        if (lbl) {
            const update = () => { lbl.textContent = cb.checked ? 'On' : 'Off'; };
            cb.addEventListener('change', update);
            update();
        }
    });
});

function openCreateForm() {
    const list = document.getElementById('cms-{$table}-list');
    const form = document.getElementById('cms-{$table}-form');
    if (list && form) {
        list.style.display = 'none';
        form.style.display = 'block';
        const t = document.getElementById('form-title');
        if (t) t.textContent = 'Create New Record';
        form.querySelector('form').reset();
    }
}

function closeForm() {
    const list = document.getElementById('cms-{$table}-list');
    const form = document.getElementById('cms-{$table}-form');
    if (list && form) { form.style.display = 'none'; list.style.display = 'block'; }
}

function handleFormSubmit(e) {
    const btn = e.target.querySelector('button[type="submit"]');
    if (btn) {
        btn.innerHTML = '<span style="opacity:.7">Saving…</span>';
        btn.disabled = true;
    }
}

// Stub — connect to your media picker
function openMediaPicker(col, type) {
    if (window.cmsMediaPicker?.open) {
        window.cmsMediaPicker.open({ imagesOnly: type === 'image', onSelect: m => {
            const hidden = document.getElementById('f_' + col);
            if (hidden) { hidden.value = m.url; }
        }});
    }
}

// Add URL inline for media fields
function addMediaUrl(col) {
    const url = prompt('Paste a URL:');
    if (!url) return;
    const hidden = document.getElementById('f_' + col);
    if (hidden) { hidden.value = url; }
}
JS;
    }
}
