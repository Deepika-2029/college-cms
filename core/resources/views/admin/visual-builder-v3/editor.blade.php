<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $page?->title ?? 'New Page' }} — V3 Builder</title>
</head>
<body>
<div id="vb3-root">

  {{-- ── TOOLBAR ── --}}
  <div id="vb3-toolbar">
    <div class="tb-left">
      <a href="{{ route('admin.vbuilder3.pages') }}" class="tb-back" title="Back to pages">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
      </a>
      <div class="tb-sep"></div>
      <input id="vb3-page-title" class="tb-title-input" type="text" placeholder="Page title…" value="{{ $page?->title ?? '' }}">
      <span class="tb-slash">/</span>
      <input id="vb3-page-slug" class="tb-slug-input" type="text" placeholder="slug" value="{{ $page?->slug ?? '' }}">
      <div id="vb3-status" class="tb-status saved">Saved</div>
    </div>

    <div class="tb-center">
      <button class="vp-btn active" id="vp-desktop" onclick="setViewport('desktop')" title="Desktop (1200px)">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
      </button>
      <button class="vp-btn" id="vp-tablet" onclick="setViewport('tablet')" title="Tablet (768px)">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="4" y="2" width="16" height="20" rx="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></svg>
      </button>
      <button class="vp-btn" id="vp-mobile" onclick="setViewport('mobile')" title="Mobile (390px)">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="5" y="2" width="14" height="20" rx="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></svg>
      </button>
    </div>

    <div class="tb-right">

      <button class="tb-btn" onclick="undoHistory()" title="Undo (Ctrl+Z)">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 14 4 9 9 4"/><path d="M20 20v-7a4 4 0 0 0-4-4H4"/></svg>
        Undo
      </button>
      <button class="tb-btn primary" id="btn-save" onclick="savePage(false)">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13"/><polyline points="7 3 7 8 15 8"/></svg>
        Save Draft
      </button>
      <button class="tb-btn publish" id="btn-publish" onclick="savePage(true)">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
        Publish
      </button>
    </div>
  </div>

  {{-- ── MAIN AREA (canvas + right panel) ── --}}
  <div id="vb3-main">

    {{-- LEFT TEMPLATES PANEL --}}
    <div id="vb3-left-panel">
      <button class="panel-toggle-left" onclick="document.getElementById('vb3-left-panel').classList.toggle('hidden')" title="Toggle Left Panel">
        <span class="toggle-text">Templates</span>
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
      </button>
      <div class="panel-tabs">
        <button class="panel-tab active ltab-btn" data-ltab="templates" onclick="showLeftTab('templates')">Templates</button>
        <button class="panel-tab ltab-btn" data-ltab="tokens" onclick="showLeftTab('tokens')">Tokens</button>
      </div>

      <div id="ltab-templates" class="ltab-content active" style="display:flex;flex-direction:column;flex:1;overflow:hidden;">
        <div id="tpl-list">
           <div style="padding:20px;text-align:center;color:var(--t3);font-size:12px;">Loading...</div>
        </div>
      </div>

      <div id="ltab-tokens" class="ltab-content" style="display:none;flex:1;flex-direction:column;overflow:hidden;">
        <div style="padding:16px;overflow-y:auto;flex:1;">
          <p style="font-size:11px;color:var(--t2);margin:0 0 12px 0;line-height:1.4;">Click any token below to copy it, then paste it directly into your text in the canvas.</p>
          <div style="display:flex;flex-direction:column;gap:6px;">
              @forelse($globalStrings ?? [] as $k => $v)
              <div style="background:var(--surf2);border:1px solid var(--brd);border-radius:6px;padding:8px 10px;display:flex;flex-direction:column;gap:4px;cursor:pointer;transition:.15s;" title="Click to copy" onclick="navigator.clipboard.writeText('[[{{ $k }}]]').then(()=>toast('Copied [[{{ $k }}]]','success'))" onmouseover="this.style.borderColor='var(--acc)'" onmouseout="this.style.borderColor='var(--brd)'">
                  <span style="font-family:monospace;font-size:11px;color:var(--accent);font-weight:600;">[[{{ $k }}]]</span>
                  <span style="font-size:10px;color:var(--t2);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $v ?: '(empty)' }}</span>
              </div>
              @empty
              <div style="text-align:center;color:var(--t3);font-size:11px;padding:20px 0;">No tokens available.<br><a href="{{ route('admin.site-identity.index') }}" style="color:var(--acc);text-decoration:none;display:inline-block;margin-top:6px;">Manage in Site Identity</a></div>
              @endforelse
          </div>
        </div>
      </div>
    </div>

    {{-- CANVAS --}}
    <div id="vb3-canvas-wrap">
      <div id="vb3-frame-shell" class="vp-desktop" data-vp-label="🖥 Desktop — 1280px">
        <iframe id="vb3-iframe" sandbox="allow-scripts allow-same-origin allow-forms" scrolling="auto"></iframe>
      </div>
    </div>

    {{-- RIGHT INSPECTOR PANEL --}}
    <div id="vb3-panel">
      <button class="panel-toggle-right" onclick="document.getElementById('vb3-panel').classList.toggle('hidden')" title="Toggle Right Panel">
        <span class="toggle-text">Settings</span>
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
      </button>
      <div class="panel-tabs">
        <button class="panel-tab active" data-ptab="element" onclick="showPanelTab('element')">Element</button>
        <button class="panel-tab" data-ptab="theme" onclick="showPanelTab('theme')">Theme</button>
        <button class="panel-tab" data-ptab="page" onclick="showPanelTab('page')">Page Settings</button>
      </div>

      {{-- ELEMENT INSPECTOR (shown when something is selected) --}}
      <div id="ptab-element" class="ptab-content active">
        <div id="panel-empty" class="panel-empty">
          <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
          <p>Click any element<br>in the canvas to edit it</p>
        </div>

        <div id="panel-inspector" style="display:none">
          <div class="panel-section-header" id="insp-element-type">div</div>

          {{-- Lock Component --}}
          <div class="insp-group">
            <div class="insp-group-label" style="display:flex;justify-content:space-between"><span>Component Protection</span><span title="Hold Alt and click an element to select it even if it is locked." style="cursor:help;color:var(--t2)">&#9432;</span></div>
            <div style="display:flex;align-items:center;gap:8px">
              <input type="checkbox" id="insp-lock" onchange="applyInspLock()">
              <label for="insp-lock" style="font-size:12px;color:var(--t1);cursor:pointer;font-weight:600">Lock (Protect API Data)</label>
            </div>
            <div style="font-size:10px;color:var(--t3);margin-top:6px;line-height:1.4">When locked, users cannot click or edit this. To unlock it, hold <b>Alt</b> and click it.</div>
          </div>

          {{-- Text Editing --}}
          <div class="insp-group" id="insp-text-group">
            <div class="insp-group-label">Content</div>
            <textarea id="insp-text-val" class="insp-textarea" rows="3" placeholder="Text content…" oninput="applyInspText()"></textarea>
          </div>

          {{-- Image --}}
          <div class="insp-group" id="insp-img-group" style="display:none">
            <div class="insp-group-label">Image</div>
            <div class="insp-img-preview-wrap">
              <img id="insp-img-preview" src="" alt="" style="max-width:100%;border-radius:4px;display:none">
            </div>
            <div style="display:flex;gap:8px;margin-top:8px">
              <input type="url" id="insp-img-src" class="insp-input" placeholder="Image URL…" oninput="applyInspImg()">
              <button class="insp-btn-pick" onclick="pickImage()" title="Browse Media">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
              </button>
            </div>
            <input type="text" id="insp-img-alt" class="insp-input" style="margin-top:6px" placeholder="Alt text…" oninput="applyInspImgAlt()">
          </div>

          {{-- Link --}}
          <div class="insp-group" id="insp-link-group" style="display:none">
            <div class="insp-group-label">Link</div>
            <input type="url" id="insp-link-href" class="insp-input" placeholder="https://…" oninput="applyInspLink()">
            <div style="display:flex;align-items:center;gap:8px;margin-top:8px">
              <input type="checkbox" id="insp-link-blank" onchange="applyInspLink()">
              <label for="insp-link-blank" style="font-size:12px;color:var(--t2,#8b949e);cursor:pointer">Open in new tab</label>
            </div>
          </div>

          {{-- Style --}}
          <div class="insp-group">
            <div class="insp-group-label">Typography</div>
            <div class="insp-row2">
              <div>
                <div class="insp-field-label">Font Size</div>
                <input type="text" id="insp-font-size" class="insp-input" placeholder="16px" oninput="applyInspStyle('fontSize',this.value)">
              </div>
              <div>
                <div class="insp-field-label">Font Weight</div>
                <select id="insp-font-weight" class="insp-select" onchange="applyInspStyle('fontWeight',this.value)">
                  <option value="">Default</option>
                  <option value="400">Normal (400)</option>
                  <option value="500">Medium (500)</option>
                  <option value="600">Semi Bold (600)</option>
                  <option value="700">Bold (700)</option>
                  <option value="800">Extra Bold (800)</option>
                </select>
              </div>
            </div>
            <div class="insp-row2" style="margin-top:8px">
              <div>
                <div class="insp-field-label">Color</div>
                <div style="display:flex;gap:6px;align-items:center">
                  <input type="color" id="insp-color-pick" style="width:32px;height:32px;border:none;border-radius:4px;cursor:pointer;background:none;padding:0" onchange="applyInspStyle('color',this.value);document.getElementById('insp-color-txt').value=this.value">
                  <input type="text" id="insp-color-txt" class="insp-input" placeholder="#000000" oninput="document.getElementById('insp-color-pick').value=this.value;applyInspStyle('color',this.value)">
                </div>
              </div>
              <div>
                <div class="insp-field-label">Text Align</div>
                <div class="insp-btn-group">
                  <button class="insp-align-btn" onclick="applyInspStyle('textAlign','left');setAlignActive(this)" title="Left">&#8801;</button>
                  <button class="insp-align-btn" onclick="applyInspStyle('textAlign','center');setAlignActive(this)" title="Center">&#8803;</button>
                  <button class="insp-align-btn" onclick="applyInspStyle('textAlign','right');setAlignActive(this)" title="Right">&#8800;</button>
                </div>
              </div>
            </div>
          </div>

          <div class="insp-group">
            <div class="insp-group-label">Spacing</div>
            <div class="insp-grid4">
              <div><div class="insp-field-label">Pad T</div><input type="text" id="insp-pt" class="insp-input" placeholder="0" oninput="applyInspStyle('paddingTop',this.value)"></div>
              <div><div class="insp-field-label">Pad R</div><input type="text" id="insp-pr" class="insp-input" placeholder="0" oninput="applyInspStyle('paddingRight',this.value)"></div>
              <div><div class="insp-field-label">Pad B</div><input type="text" id="insp-pb" class="insp-input" placeholder="0" oninput="applyInspStyle('paddingBottom',this.value)"></div>
              <div><div class="insp-field-label">Pad L</div><input type="text" id="insp-pl" class="insp-input" placeholder="0" oninput="applyInspStyle('paddingLeft',this.value)"></div>
            </div>
            <div class="insp-grid4" style="margin-top:6px">
              <div><div class="insp-field-label">Mar T</div><input type="text" id="insp-mt" class="insp-input" placeholder="0" oninput="applyInspStyle('marginTop',this.value)"></div>
              <div><div class="insp-field-label">Mar R</div><input type="text" id="insp-mr" class="insp-input" placeholder="0" oninput="applyInspStyle('marginRight',this.value)"></div>
              <div><div class="insp-field-label">Mar B</div><input type="text" id="insp-mb" class="insp-input" placeholder="0" oninput="applyInspStyle('marginBottom',this.value)"></div>
              <div><div class="insp-field-label">Mar L</div><input type="text" id="insp-ml" class="insp-input" placeholder="0" oninput="applyInspStyle('marginLeft',this.value)"></div>
            </div>
          </div>

          <div class="insp-group">
            <div class="insp-group-label">Size</div>
            <div class="insp-row2">
              <div><div class="insp-field-label">Width</div><input type="text" id="insp-w" class="insp-input" placeholder="auto" oninput="applyInspStyle('width',this.value)"></div>
              <div><div class="insp-field-label">Height</div><input type="text" id="insp-h" class="insp-input" placeholder="auto" oninput="applyInspStyle('height',this.value)"></div>
            </div>
          </div>

          <div class="insp-group">
            <div class="insp-group-label">Background</div>
            <div style="display:flex;gap:6px;align-items:center">
              <input type="color" id="insp-bg-pick" style="width:32px;height:32px;border:none;border-radius:4px;cursor:pointer;background:none;padding:0" onchange="applyInspStyle('backgroundColor',this.value);document.getElementById('insp-bg-txt').value=this.value">
              <input type="text" id="insp-bg-txt" class="insp-input" placeholder="transparent" oninput="document.getElementById('insp-bg-pick').value=this.value;applyInspStyle('backgroundColor',this.value)">
            </div>
          </div>

          <div class="insp-group">
            <div class="insp-group-label">Border</div>
            <div class="insp-row2">
              <div><div class="insp-field-label">Border</div><input type="text" id="insp-border" class="insp-input" placeholder="1px solid #ccc" oninput="applyInspStyle('border',this.value)"></div>
              <div><div class="insp-field-label">Radius</div><input type="text" id="insp-radius" class="insp-input" placeholder="0px" oninput="applyInspStyle('borderRadius',this.value)"></div>
            </div>
          </div>

          <div class="insp-group">
            <div class="insp-group-label">Custom CSS (inline)</div>
            <textarea id="insp-custom-css" class="insp-textarea" rows="4" placeholder="color: red;&#10;font-family: 'Inter';" oninput="applyInspCustomCss()"></textarea>
          </div>

          <div class="insp-actions">
            <button class="insp-btn-del" onclick="deleteSelectedElement()">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
              Delete Element
            </button>
          </div>
        </div>
      </div>

      {{-- THEME SETTINGS --}}
      <div id="ptab-theme" class="ptab-content">
        <div class="insp-group">
          <div class="insp-group-label" title="Applies to the entire website (e.g., Nav and Footer)">Global Site CSS (Theme Variables)</div>
          <p style="font-size:11px;color:var(--t2);line-height:1.4;margin:4px 0 8px 0;">Define your :root variables here (e.g., --primary-color: blue). This CSS applies globally across all templates.</p>
          <textarea id="ps-global-css" class="insp-textarea" rows="12" style="font-family:'JetBrains Mono','Fira Code',monospace;white-space:pre;font-size:11px;background:rgba(0,0,0,0.6);" placeholder=":root { ... }" oninput="globalCssChanged()">{{ $globalCss ?: ":root {
  --primary:      #3b5a8a;
  --primary-2:    #324e7a;
  --primary-d:    #243659;
  --primary-l:    #e8eef6;
  --primary-xl:   #f3f6fb;
  --gold:         #c0714f;
  --gold-d:       #a85e3f;
  --gold-l:       #fdf0eb;
  --success:      #2e7d52;
  --warning:      #b45309;
  --error:        #c0392b;
  --info:         #2563a8;
  --text:         #1a2333;
  --text-s:       #3d4e66;
  --text-m:       #7a8ca3;
  --text-inv:     #ffffff;
  --sur:          #ffffff;
  --sur-2:        #f7f9fc;
  --sur-3:        #edf1f7;
  --bdr:          #d0daeb;
  --bdr-s:        #e8eef6;
  --r-xs:         3px;
  --r-sm:         7px;
  --r-md:         11px;
  --r-lg:         16px;
  --r-xl:         24px;
  --sh-xs:        0 1px 3px rgba(0,0,0,.06);
  --sh-sm:        0 2px 8px rgba(0,0,0,.08);
  --sh-md:        0 6px 24px rgba(0,0,0,.10);
  --sh-lg:        0 16px 48px rgba(0,0,0,.13);
  --sh-b:         0 8px 24px rgba(59,90,138,.28);
  --t:            .2s;
  --ease:         cubic-bezier(.4,0,.2,1);
  --max-w:        1440px;
  --nav-h:        64px;
  --topbar-h:     44px;
}" }}</textarea>
          
          <div class="insp-group-label" style="margin-top:20px" title="Applies to the entire website">Global Site JS (Navigation/Footer Code)</div>
          <p style="font-size:11px;color:var(--t2);line-height:1.4;margin:4px 0 8px 0;">Javascript entered here will be bundled into every page. Use this for global elements like the Navigation Hamburger.</p>
          <textarea id="ps-global-js" class="insp-textarea" rows="10" style="font-family:'JetBrains Mono','Fira Code',monospace;white-space:pre;font-size:11px;background:rgba(0,0,0,0.6);" placeholder="(function(){\n  // global code\n})();" oninput="globalJsChanged()">{{ $globalJs ?? '' }}</textarea>
          
          <button type="button" class="insp-btn-apply" style="margin-top:12px;display:flex;align-items:center;gap:8px" onclick="extractStylesToGlobal()" title="Find inline <style> blocks in the visual canvas and merge only :root variables here">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
            Extract :root Variables
          </button>
        </div>
      </div>

      {{-- PAGE SETTINGS --}}
      <div id="ptab-page" class="ptab-content">

        {{-- ── Basic Info ── --}}
        <div class="insp-group">
          <div class="insp-group-label">Page Identity</div>

          <div class="insp-field-label" style="margin-top:8px">Title</div>
          <input type="text" id="ps-title" class="insp-input" placeholder="Page Title"
                 value="{{ $page?->title ?? '' }}"
                 oninput="syncTitleFromPanel(this.value)">

          <div class="insp-field-label" style="margin-top:8px">Slug</div>
          <input type="text" id="ps-slug" class="insp-input" placeholder="my-page"
                 value="{{ $page?->slug ?? '' }}"
                 oninput="document.getElementById('vb3-page-slug').value=this.value; markDirty()">
          <div style="font-size:10px;color:var(--t3);margin-top:4px">URL: <span id="ps-slug-preview">{{ url('/') }}/{{ $page?->slug ?? 'my-page' }}</span></div>
        </div>

        {{-- ── SEO Meta Tags ── --}}
        <div class="insp-group">
          <div class="insp-group-label">SEO Meta Tags</div>

          <div class="insp-field-label" style="margin-top:8px">Meta Title</div>
          <input type="text" id="ps-meta-title" class="insp-input" placeholder="SEO Title (55–60 chars)"
                 value="{{ $page?->meta_title ?? '' }}" oninput="updateMetaCounter('ps-meta-title','ps-meta-title-ct',60); markDirty()">
          <div style="font-size:10px;color:var(--t3);margin-top:3px"><span id="ps-meta-title-ct">{{ strlen($page?->meta_title ?? '') }}</span>/60 characters</div>

          <div class="insp-field-label" style="margin-top:10px; display:flex; justify-content:space-between; align-items:center;">
            <span>Meta Description</span>
            <button type="button" class="insp-btn-apply" style="padding:3px 8px;font-size:10px;width:auto" onclick="autoGenerateMeta()" title="Auto-generate from page content">Auto-Generate</button>
          </div>
          <textarea id="ps-meta-desc" class="insp-textarea" rows="3" placeholder="SEO description (150–160 chars)…"
                    oninput="updateMetaCounter('ps-meta-desc','ps-meta-desc-ct',160); markDirty()">{{ $page?->meta_description ?? '' }}</textarea>
          <div style="font-size:10px;color:var(--t3);margin-top:3px"><span id="ps-meta-desc-ct">{{ strlen($page?->meta_description ?? '') }}</span>/160 characters</div>

          <div class="insp-field-label" style="margin-top:10px">Keywords <span style="font-weight:400;opacity:.6">(comma separated)</span></div>
          <input type="text" id="ps-meta-keywords" class="insp-input"
                 placeholder="flutter, firebase, iot, college"
                 value="{{ $page?->meta_keywords ?? '' }}" oninput="markDirty()">

          <div class="insp-field-label" style="margin-top:10px">Canonical URL <span style="font-weight:400;opacity:.6">(optional)</span></div>
          <input type="url" id="ps-canonical" class="insp-input"
                 placeholder="https://yourdomain.com/page"
                 value="{{ $page?->canonical_url ?? '' }}" oninput="markDirty()">
        </div>

        {{-- ── Open Graph (Social Sharing) ── --}}
        <div class="insp-group">
          <div class="insp-group-label">Open Graph / Social Sharing</div>
          <div style="font-size:10px;color:var(--t3);margin-bottom:10px;line-height:1.5">Controls how your page appears when shared on WhatsApp, Facebook, LinkedIn, etc.</div>

          <div class="insp-field-label">OG Title</div>
          <input type="text" id="ps-og-title" class="insp-input" placeholder="Leave blank to use Meta Title"
                 value="{{ $page?->og_title ?? '' }}" oninput="markDirty()">

          <div class="insp-field-label" style="margin-top:8px">OG Description</div>
          <textarea id="ps-og-desc" class="insp-textarea" rows="2"
                    placeholder="Leave blank to use Meta Description" oninput="markDirty()">{{ $page?->og_description ?? '' }}</textarea>

          <div class="insp-field-label" style="margin-top:8px">OG Image (1200×630 recommended)</div>
          <div style="display:flex;gap:6px;align-items:center">
            <input type="url" id="ps-og-image" class="insp-input" placeholder="https://…/og-image.jpg"
                   value="{{ $page?->og_image ?? '' }}" oninput="updateOgImagePreview(); markDirty()">
            <button class="insp-btn-pick" onclick="pickOgImage()" title="Browse Media"
                    style="flex-shrink:0;padding:7px 8px">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
            </button>
          </div>
          <div id="ps-og-img-preview" style="margin-top:8px;display:{{ $page?->og_image ? 'block' : 'none' }}">
            <img id="ps-og-img-el" src="{{ $page?->og_image ?? '' }}" alt="OG Preview"
                 style="width:100%;border-radius:6px;border:1px solid var(--brd2);object-fit:cover;max-height:80px">
          </div>
        </div>

        {{-- ── Page Scripts ── --}}
        <div class="insp-group">
          <div class="insp-group-label">Bootstrap 5</div>
          <div style="display:flex;align-items:center;gap:10px;padding:8px 0">
            <input type="checkbox" id="ps-bootstrap" {{ ($page?->use_bootstrap ?? 1) ? 'checked' : '' }}
                   onchange="toggleBootstrap(this.checked)">
            <label for="ps-bootstrap" style="color:var(--t2,#8b949e);font-size:12px;cursor:pointer">Enable Bootstrap 5</label>
          </div>
        </div>
        <div class="insp-group">
          <div class="insp-group-label" title="Applies only to this specific page">Page CSS</div>
          <textarea id="ps-css" class="insp-textarea" rows="8" placeholder="/* Custom CSS for this page */"
                    oninput="pageCssChanged()">{{ $page?->base_css ?? '' }}</textarea>
        </div>
        <div class="insp-group">
          <div class="insp-group-label">Page JS</div>
          <textarea id="ps-js" class="insp-textarea" rows="5" placeholder="// Custom JS"
                    oninput="pageJsChanged()">{{ $page?->base_js ?? '' }}</textarea>
        </div>
      </div>

    </div>{{-- /vb3-panel --}}
  </div>{{-- /vb3-main --}}

  <div id="vb3-toast"></div>
</div>

{{-- ── Media Picker (full CMS library) ── --}}
@php $cloudinaryReady = app(\App\Services\CloudinaryService::class)->isConfigured(); @endphp
@include('admin.partials.media-picker')

{{-- Hidden data bridge --}}
<div id="vb3-data"
  data-save-url="{{ route('admin.vbuilder3.page.save') }}"
></div>

<script>
window.V3_PAGE_DATA = {!! json_encode([
    'page_id' => $page?->id ?? '',
    'title' => htmlspecialchars_decode($page?->title ?? 'New Page'),
    'slug' => $page?->slug ?? '',
    'html' => $page?->base_html ?? '',
    'css' => $page?->base_css ?? '',
    'js' => $page?->base_js ?? '',
    'head' => $page?->head_code ?? '',
    'end' => $page?->end_code ?? '',
    'navHtml' => $navHtml ?? '',
    'footHtml' => $footerHtml ?? '',
    'globalCss' => $globalCss ?? '',
    'globalJs' => $globalJs ?? '',
    'useB5' => ($page?->use_bootstrap ?? 1) ? 1 : 0
], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) !!};
</script>
<style>
/* ── Global Scrollbar Hide ── */
::-webkit-scrollbar { width: 0; height: 0; background: transparent; display: none; }
* { scrollbar-width: none; -ms-overflow-style: none; }

:root {
  --bg: #09090b; /* Zinc 950 */
  --surf: #18181b; /* Zinc 900 */
  --surf2: #27272a; /* Zinc 800 */
  --brd: rgba(255, 255, 255, 0.08);
  --brd2: rgba(255, 255, 255, 0.12);
  --t1: #fafafa; /* Zinc 50 */
  --t2: #a1a1aa; /* Zinc 400 */
  --t3: #71717a; /* Zinc 500 */
  --accent: #6366f1; /* Indigo 500 */
  --accent-l: #818cf8; /* Indigo 400 */
  --accent-rgb: 99, 102, 241;
  --acc:  var(--accent); --acc2: var(--accent-l);
  --red: #ef4444; --red-rgb: 239, 68, 68;
  --green: #10b981; --green-rgb: 16, 185, 129;
  --amber: #f59e0b;
}
/* ── Root ── */
body { margin: 0; padding: 0; overflow: hidden; background: var(--bg); }
#vb3-root { display:flex; flex-direction:column; height: 100vh; background:var(--bg); font-family:'Inter',system-ui,sans-serif; overflow:hidden; }

/* ── Toolbar ── */
#vb3-toolbar { display:flex; align-items:center; justify-content:space-between; gap:10px; padding:0 14px; height:52px; background:rgba(24, 24, 27, 0.75); backdrop-filter:blur(12px); -webkit-backdrop-filter:blur(12px); border-bottom:1px solid var(--brd); flex-shrink:0; z-index:200; }
.tb-left,.tb-center,.tb-right { display:flex; align-items:center; gap:8px; }
.tb-left { flex:1; min-width:0; }
.tb-sep  { width:1px; height:24px; background:var(--brd); flex-shrink:0; }
.tb-icon-btn { display:flex; align-items:center; justify-content:center; width:30px; height:30px; border-radius:6px; background:transparent; border:1px solid transparent; color:var(--t2); cursor:pointer; transition:.15s; flex-shrink:0; padding:0; }
.tb-icon-btn:hover { background:rgba(255,255,255,.08); color:var(--t1); }
.tb-back { display:flex; align-items:center; justify-content:center; width:30px; height:30px; border-radius:6px; background:rgba(255,255,255,.05); color:var(--t2); text-decoration:none; transition:.15s; flex-shrink:0; }
.tb-back:hover { background:rgba(255,255,255,.1); color:var(--t1); }
.tb-title-input { background:transparent; border:none; color:var(--t1); font-size:14px; font-weight:600; width:200px; outline:none; padding:4px 8px; border-radius:6px; min-width:0; font-family:inherit; }
.tb-title-input:focus { background:rgba(255,255,255,.05); }
.tb-slash { color:var(--t3); font-size:16px; flex-shrink:0; }
.tb-slug-input { background:transparent; border:none; color:var(--t2); font-size:12px; font-family:monospace; width:140px; outline:none; padding:4px 6px; border-radius:6px; min-width:0; }
.tb-slug-input:focus { background:rgba(255,255,255,.05); }
.tb-status { font-size:10px; font-weight:700; padding:2px 8px; border-radius:99px; letter-spacing:.3px; text-transform:uppercase; }
.tb-status.saved     { background:rgba(var(--green-rgb),.1);  color:var(--green); }
.tb-status.unsaved   { background:rgba(251,191,36,.1); color:var(--amber); }
.tb-status.published { background:rgba(var(--green-rgb),.2);  color:var(--green); border:1px solid rgba(var(--green-rgb),.4); }
.tb-status.draft     { background:rgba(148,163,184,.1); color:var(--text-3); }
.vp-btn { width:32px; height:32px; border:1px solid var(--brd2); background:transparent; color:var(--t3); border-radius:6px; cursor:pointer; display:flex; align-items:center; justify-content:center; transition:.15s; }
.vp-btn:hover,.vp-btn.active { background:rgba(var(--accent-rgb),.12); border-color:var(--acc); color:var(--accent); }
.tb-btn { display:inline-flex; align-items:center; gap:6px; padding:6px 14px; background:rgba(255,255,255,.03); box-shadow:inset 0 1px 0 rgba(255,255,255,.05); border:1px solid var(--brd2); color:var(--t2); border-radius:6px; font-size:12px; font-weight:600; cursor:pointer; transition:.15s; white-space:nowrap; font-family:inherit; }
.tb-btn:hover  { background:rgba(255,255,255,.08); color:var(--t1); }
.tb-btn.active { background:rgba(var(--accent-rgb),.12); border-color:var(--acc); color:var(--accent); }
.tb-btn.primary { background:linear-gradient(180deg, var(--acc2), var(--acc)); box-shadow:inset 0 1px 0 rgba(255,255,255,.2), 0 2px 8px rgba(var(--accent-rgb),.3); border:none; color:#fff; }
.tb-btn.primary:hover { filter:brightness(1.1); }
.tb-btn.publish { background:linear-gradient(180deg, #10b981, #059669); box-shadow:inset 0 1px 0 rgba(255,255,255,.2), 0 2px 8px rgba(16,185,129,.3); border:none; color:#fff; }
.tb-btn.publish:hover { filter:brightness(1.1); }
.tb-btn:disabled { opacity:.4; cursor:default; filter:none; box-shadow:none; }

/* ── Main layout ── */
#vb3-main { flex:1; display:flex; overflow:hidden; }

/* ── Left Panel ── */
#vb3-left-panel { width:240px; flex-shrink:0; background:var(--surf); border-right:1px solid var(--brd); display:flex; flex-direction:column; transition:margin-left .25s ease; position:relative; z-index:50; }
#vb3-left-panel.hidden { margin-left:-240px; }
#tpl-list { flex:1; overflow-y:auto; padding:10px; }
.tpl-cat-title { font-size:10px; font-weight:800; text-transform:uppercase; color:var(--t3); margin:14px 0 8px 4px; letter-spacing:.5px; }
.tpl-item { padding:10px 12px; background:rgba(255,255,255,.03); border:1px solid var(--brd2); border-radius:6px; color:var(--t1); font-size:12px; font-weight:600; margin-bottom:6px; cursor:pointer; transition:.15s; display:flex; align-items:center; gap:8px;}
.tpl-item:hover { background:rgba(var(--accent-rgb),.1); border-color:var(--acc); color:var(--accent); }

/* ── Canvas ── */
#vb3-canvas-wrap {
  flex: 1;
  overflow: hidden;         /* No scrollbars on wrapper, handled inside iframe */
  background: var(--bg);
  display: flex;
  align-items: center;      /* Center vertically so scaled view is centered */
  justify-content: center;
  padding: 24px;
  background-image: 
    radial-gradient(var(--brd) 1px, transparent 1px),
    radial-gradient(circle at 50% 0%, rgba(var(--accent-rgb), 0.08) 0%, transparent 60%);
  background-size: 24px 24px, 100% 100%;
  min-width: 0;             /* prevent flex overflow clipping */
}

/* Frame shell — the simulated browser window */
#vb3-frame-shell {
  flex-shrink: 0;
  background: #fff;
  border-radius: 10px;
  overflow: hidden;
  transition: width .35s cubic-bezier(.4,0,.2,1);
  box-shadow: 0 0 0 1px rgba(255,255,255,.08), 0 32px 80px rgba(0,0,0,.8), 0 0 40px rgba(var(--accent-rgb), 0.1);
  position: relative;
  transform-origin: center center;
  display: flex;
  flex-direction: column;
}

/* Device width presets */
#vb3-frame-shell.vp-desktop { width: 1280px; }   /* true desktop — canvas scrolls horizontally */
#vb3-frame-shell.vp-tablet  { width: 768px;  }   /* tablet portrait */
#vb3-frame-shell.vp-mobile  { width: 390px;  }   /* iPhone-size */

/* Device label above the frame */
#vb3-frame-shell::before {
  content: attr(data-vp-label);
  display: block;
  background: var(--surf);
  color: var(--t2);
  font-size: 10px;
  font-weight: 700;
  letter-spacing: .5px;
  text-transform: uppercase;
  text-align: center;
  padding: 6px 0;
  border-bottom: 1px solid var(--brd);
  font-family: 'Inter', system-ui, sans-serif;
}

/* Iframe fills shell exactly */
#vb3-iframe {
  flex: 1;
  width: 100%;
  border: none;
  display: block;
}

/* ── Right Panel ── */
#vb3-panel { width:300px; flex-shrink:0; background:var(--surf); border-left:1px solid var(--brd); display:flex; flex-direction:column; transition:margin-right .25s ease; position:relative; z-index:50; }
#vb3-panel.hidden { margin-right:-300px; }

/* ── Panel Edge Toggles ── */
.panel-toggle-left { position:absolute; top:50%; right:-28px; transform:translateY(-50%); width:28px; height:120px; background:rgba(39,39,42,0.85); backdrop-filter:blur(8px); border:1px solid var(--brd); border-left:none; border-radius:0 8px 8px 0; display:flex; flex-direction:column; align-items:center; justify-content:center; gap:8px; cursor:pointer; color:var(--t1); z-index:100; transition:.2s; padding:0; outline:none; box-shadow:2px 0 8px rgba(0,0,0,0.5); }
.panel-toggle-left:hover { background:var(--accent); border-color:var(--accent); color:#fff; }
.panel-toggle-left svg { transition:transform .25s; flex-shrink:0; }
#vb3-left-panel.hidden .panel-toggle-left svg { transform:rotate(180deg); }

.panel-toggle-right { position:absolute; top:50%; left:-28px; transform:translateY(-50%); width:28px; height:120px; background:rgba(39,39,42,0.85); backdrop-filter:blur(8px); border:1px solid var(--brd); border-right:none; border-radius:8px 0 0 8px; display:flex; flex-direction:column; align-items:center; justify-content:center; gap:8px; cursor:pointer; color:var(--t1); z-index:100; transition:.2s; padding:0; outline:none; box-shadow:-2px 0 8px rgba(0,0,0,0.5); }
.panel-toggle-right:hover { background:var(--accent); border-color:var(--accent); color:#fff; }
.panel-toggle-right svg { transition:transform .25s; flex-shrink:0; }
#vb3-panel.hidden .panel-toggle-right svg { transform:rotate(180deg); }

.toggle-text {
  writing-mode: vertical-rl;
  text-orientation: mixed;
  font-size: 9px;
  font-weight: 700;
  letter-spacing: 1px;
  text-transform: uppercase;
  color: inherit;
}
.panel-toggle-left .toggle-text {
  transform: rotate(180deg);
}
.panel-tabs { display:flex; border-bottom:1px solid var(--brd); flex-shrink:0; }
.panel-tab { flex:1; padding:11px 0; font-size:12px; font-weight:600; background:none; border:none; color:var(--t3); cursor:pointer; border-bottom:2px solid transparent; transition:.15s; font-family:inherit; }
.panel-tab:hover { color:var(--t2); }
.panel-tab.active { color:var(--accent); border-bottom-color:var(--acc); }
.ptab-content { display:none; flex:1; overflow-y:auto; padding-bottom:20px; }
.ptab-content.active { display:block; }
.panel-empty { display:flex; flex-direction:column; align-items:center; justify-content:center; gap:14px; min-height:200px; padding:40px 20px; text-align:center; color:var(--t3); }
.panel-empty p { font-size:13px; line-height:1.6; margin:0; }

/* Inspector groups */
.insp-group { padding:14px 16px; border-bottom:1px solid var(--brd); }
.insp-group-label { font-size:10px; font-weight:800; text-transform:uppercase; letter-spacing:.7px; color:var(--t3); margin-bottom:10px; }
.insp-field-label { font-size:10px; color:var(--t3); margin-bottom:4px; font-weight:600; }
.insp-input { width:100%; padding:7px 10px; background:rgba(0,0,0,.3); border:1px solid var(--brd2); border-radius:6px; color:var(--t1); font-size:12px; outline:none; transition:.15s; box-sizing:border-box; font-family:inherit; }
.insp-input:focus { border-color:var(--acc); background:rgba(0,0,0,.5); }
.insp-select { width:100%; padding:7px 10px; background:rgba(0,0,0,.3); border:1px solid var(--brd2); border-radius:6px; color:var(--t1); font-size:12px; outline:none; box-sizing:border-box; font-family:inherit; }
.insp-textarea { width:100%; padding:8px 10px; background:rgba(0,0,0,.3); border:1px solid var(--brd2); border-radius:6px; color:var(--t1); font-size:12px; line-height:1.6; outline:none; resize:vertical; transition:.15s; box-sizing:border-box; font-family:inherit; }
.insp-textarea:focus { border-color:var(--acc); }
.insp-textarea.code-ta { font-family:'JetBrains Mono','Fira Code',monospace; font-size:11px; }
.insp-row2  { display:grid; grid-template-columns:1fr 1fr; gap:8px; }
.insp-grid4 { display:grid; grid-template-columns:1fr 1fr 1fr 1fr; gap:5px; }
.insp-btn-group { display:flex; gap:4px; }
.insp-align-btn { flex:1; padding:6px; background:rgba(255,255,255,.05); border:1px solid var(--brd2); border-radius:5px; color:var(--t2); cursor:pointer; transition:.12s; font-size:14px; }
.insp-align-btn:hover,.insp-align-btn.active { background:rgba(var(--accent-rgb),.15); color:var(--accent); border-color:var(--acc); }
.insp-btn-pick { padding:7px 10px; background:rgba(255,255,255,.05); border:1px solid var(--brd2); border-radius:6px; color:var(--t2); cursor:pointer; transition:.12s; flex-shrink:0; }
.insp-btn-pick:hover { background:rgba(255,255,255,.1); color:var(--t1); }
.insp-actions { padding:10px 16px; }
.insp-btn-del { width:100%; padding:8px; display:flex; align-items:center; justify-content:center; gap:6px; background:rgba(var(--red-rgb),.08); border:1px solid rgba(var(--red-rgb),.2); border-radius:6px; color:var(--red); cursor:pointer; font-size:12px; font-weight:600; transition:.15s; font-family:inherit; }
.insp-btn-del:hover { background:rgba(var(--red-rgb),.15); }
.insp-btn-apply { width:100%; padding:9px; display:flex; align-items:center; justify-content:center; gap:6px; background:linear-gradient(135deg,var(--acc),var(--acc2)); border:none; border-radius:6px; color:#fff; cursor:pointer; font-size:12px; font-weight:700; transition:.15s; font-family:inherit; }
.insp-btn-apply:hover { opacity:.9; }
.panel-section-header { padding:10px 16px; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.5px; color:var(--accent); border-bottom:1px solid var(--brd); display:flex; align-items:center; gap:6px; background:rgba(var(--accent-rgb),.05); }
.panel-section-header::before { content:''; width:8px; height:8px; border-radius:2px; background:var(--acc); display:inline-block; }

/* Toast */
#vb3-toast { position:fixed; bottom:28px; right:318px; padding:12px 22px; border-radius:8px; font-size:13px; font-weight:600; color:#fff; opacity:0; pointer-events:none; transition:opacity .25s; z-index:9999; }
#vb3-toast.show { opacity:1; }
#vb3-toast.success { background:#059669; }
#vb3-toast.error   { background:#dc2626; }
#vb3-toast.info    { background:#0284c7; }
</style>
<script>
'use strict';

/* ── Bootstrap data ── */
const D        = document.getElementById('vb3-data');
const SAVE_URL = D.dataset.saveUrl;
const PAGE_ID  = window.V3_PAGE_DATA.page_id;
let CSRF       = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

let _title     = window.V3_PAGE_DATA.title;
let _slug      = window.V3_PAGE_DATA.slug;
let _html      = window.V3_PAGE_DATA.html;
let _css       = window.V3_PAGE_DATA.css;
let _js        = window.V3_PAGE_DATA.js;
let _head      = window.V3_PAGE_DATA.head;
let _end       = window.V3_PAGE_DATA.end;
let _navHtml   = window.V3_PAGE_DATA.navHtml;   // initial; refreshed from API below
let _footHtml  = window.V3_PAGE_DATA.footHtml;  // initial; refreshed from API below
let _globalCss = window.V3_PAGE_DATA.globalCss;
let _globalJs  = window.V3_PAGE_DATA.globalJs;
let _bootstrap = window.V3_PAGE_DATA.useB5 === 1;

// Always fetch the LIVE nav/footer from DB so the canvas never shows stale data
async function refreshGlobalNav() {
  try {
    const r = await fetch('{{ route('admin.navigation.json') }}', {
      headers: {'Accept':'application/json','X-CSRF-TOKEN': CSRF}
    });
    if (!r.ok) return;
    const d = await r.json();
    const fresh = {
      nav:    (d.nav_html    || '').trim(),
      footer: (d.footer_html || '').trim(),
      css:    (d.nav_css     || '') + '\n' + (d.footer_css || ''),
    };
    let changed = (fresh.nav !== _navHtml.trim() || fresh.footer !== _footHtml.trim());
    _navHtml   = fresh.nav;
    _footHtml  = fresh.footer;
    if (fresh.css.trim()) _globalCss = fresh.css;
    if (changed) renderIframe();
  } catch(e) { /* silent — canvas still shows baked version */ }
}

let _history    = [];
let _dirty      = false;
let _visualMode = true;
let _sel        = null;

const iframe = document.getElementById('vb3-iframe');

/* ═══════════════════════════════════════════════════════════════
   VISUAL EDIT SCRIPT — injected into iframe srcdoc
   ═══════════════════════════════════════════════════════════════ */
const VIS_SCRIPT = `
<style id="__vb_vis">
  #vb-body, [data-vb-fixed] { display:contents; }
  #vb-body *:not([data-vb-fixed]) { cursor:pointer !important; outline:none !important; }
  #vb-body [data-hov]:not([data-sel]):not([data-edit]) { outline:2px dashed rgba(59,130,246,.55) !important; outline-offset:2px; }
  #vb-body [data-sel]  { outline:2px solid #3b82f6 !important; outline-offset:3px; }
  #vb-body [data-edit] { outline:2px solid #2ea043 !important; outline-offset:3px; cursor:text !important; }
  #__vb_chip { position:fixed; background:#3b82f6; color:#fff; font:600 10px/1 system-ui,sans-serif; padding:2px 8px 3px; border-radius:4px 4px 0 0; z-index:99999; pointer-events:none; opacity:0; transition:opacity .1s; white-space:nowrap; }
  #__vb_chip.vis { opacity:1; }
  #__vb_bar { position:fixed; background:#161b22; border:1px solid #30363d; border-radius:8px; padding:4px 6px; display:flex; align-items:center; gap:4px; z-index:99999; box-shadow:0 8px 24px rgba(0,0,0,.6); opacity:0; pointer-events:none; transition:opacity .15s; transform:translateX(-50%); }
  #__vb_bar.vis { opacity:1; pointer-events:all; }
  #__vb_bar button { border:none; border-radius:5px; padding:4px 10px; font:600 11px/1 system-ui; cursor:pointer; transition:.1s; background:rgba(255,255,255,.1); color:#fff; }
  #__vb_bar button:hover { opacity:.8; }
  #__vb_bar .b-edit { background:rgba(59,130,246,.15); color:#3b82f6; }
  #__vb_bar .b-dup  { background:rgba(46,160,67,.15); color:#2ea043; }
  #__vb_bar .b-done { background:#2ea043; color:#fff; display:none; }
  #__vb_bar .b-del  { background:rgba(248,81,73,.15); color:#f85149; }
</style>
<div id="__vb_chip"></div>
<div id="__vb_bar">
  <button class="b-edit" id="__vb_edit">&#9998; Edit</button>
  <button class="b-dup"  id="__vb_dup">&#10697; Clone</button>
  <button class="b-done" id="__vb_done">&#10003; Done</button>
  <button class="b-del"  id="__vb_del">&#10005;</button>
</div>
<script>
(function(){
  window.__vb_action = false;
  const body=document.getElementById('vb-body');
  if(!body) return;
  const chip=document.getElementById('__vb_chip');
  const bar=document.getElementById('__vb_bar');
  const btnEdit=document.getElementById('__vb_edit');
  const btnDup=document.getElementById('__vb_dup');
  const btnDone=document.getElementById('__vb_done');
  const btnDel=document.getElementById('__vb_del');
  const SKIP=new Set(['HTML','BODY','SCRIPT','STYLE','HEAD','META','LINK','BR','HR']);
  let sel=null,editing=false;

  function getStyles(el){
    const cs=window.getComputedStyle(el);
    return { fontSize:el.style.fontSize||'', fontWeight:el.style.fontWeight||'', color:el.style.color||cs.color||'', textAlign:el.style.textAlign||'', paddingTop:el.style.paddingTop||'', paddingRight:el.style.paddingRight||'', paddingBottom:el.style.paddingBottom||'', paddingLeft:el.style.paddingLeft||'', marginTop:el.style.marginTop||'', marginRight:el.style.marginRight||'', marginBottom:el.style.marginBottom||'', marginLeft:el.style.marginLeft||'', width:el.style.width||'', height:el.style.height||'', backgroundColor:el.style.backgroundColor||'', border:el.style.border||'', borderRadius:el.style.borderRadius||'', customCss:el.dataset.customCss||'', backgroundImage:el.style.backgroundImage||cs.backgroundImage||'' };
  }

  function sendSel(el){
    const a=el.closest('a');
    const isImg=el.tagName==='IMG',isLink=!!a,isText=!SKIP.has(el.tagName)&&!isImg;
    const bg = el.style.backgroundImage || window.getComputedStyle(el).backgroundImage || '';
    const hasBgImg = bg && bg !== 'none' && bg.includes('url(');
    let bgUrl = '';
    if(hasBgImg) {
      const m = bg.match(/url\(['"]?(.*?)['"]?\)/);
      if(m) bgUrl = m[1];
    }
    // For locked (API-driven) elements, report pristine text so the inspector shows the editable original
    let displayText = isText ? el.innerText : '';
    if(isText && el.hasAttribute('data-vb-ignore') && window._pristineDom) {
      const id = el.getAttribute('data-vb-id');
      if(id && window._pristineDom.has(id)) {
        const tmp = document.createElement('div');
        tmp.innerHTML = window._pristineDom.get(id);
        displayText = tmp.innerText || el.innerText;
      }
    }
    window.parent.postMessage({type:'vb3-selected',tag:el.tagName.toLowerCase(),text:displayText,src:isImg?el.getAttribute('src'):'',alt:isImg?el.getAttribute('alt'):'',href:a?(a.getAttribute('href')||''):'',blank:a?a.getAttribute('target')==='_blank':false,isImg,isLink,isText,hasBgImg,bgUrl,styles:getStyles(el),isLocked:el.hasAttribute('data-vb-ignore')},'*');
  }

  function showBar(el){
    const r=el.getBoundingClientRect();
    bar.style.left=(r.left+r.width/2)+'px';
    bar.style.top=(r.bottom+8)+'px';
    bar.classList.add('vis');
    chip.textContent=el.tagName.toLowerCase();
    chip.style.left=r.left+'px';
    chip.style.top=(r.top-18)+'px';
    chip.classList.add('vis');
  }
  function hideBar(){bar.classList.remove('vis');chip.classList.remove('vis');}

  function selectEl(el){
    if(!el||el===body||SKIP.has(el.tagName)) return;
    if(editing) stopEdit();
    if(sel) sel.removeAttribute('data-sel');
    sel=el; el.setAttribute('data-sel','1');
    showBar(el); sendSel(el);
  }

  function clearSel(){
    if(editing) stopEdit();
    if(sel) sel.removeAttribute('data-sel');
    sel=null; hideBar();
    window.parent.postMessage({type:'vb3-deselected'},'*');
  }

  function startEdit(){
    if(!sel||sel.tagName==='IMG') return;
    editing=true;
    sel.removeAttribute('data-sel');
    sel.setAttribute('data-edit','1');
    // Always restore pristine HTML before editing so JS-modified content (typewriter etc) doesn't interfere
    if(window._pristineDom) {
      const id = sel.getAttribute('data-vb-id');
      if(id && window._pristineDom.has(id)) {
        sel.innerHTML = window._pristineDom.get(id);
      }
    }
    sel.contentEditable='true';
    sel.focus();
    const r=document.createRange();
    r.selectNodeContents(sel);
    const s=window.getSelection();
    s.removeAllRanges(); s.addRange(r);
    btnEdit.style.display='none'; btnDup.style.display='none'; btnDone.style.display='';
  }

  function stopEdit(){
    if(!sel) return;
    editing=false;
    sel.contentEditable='false';
    sel.removeAttribute('data-edit');
    sel.setAttribute('data-sel','1');
    btnEdit.style.display=''; btnDup.style.display=''; btnDone.style.display='none';
    // Update pristine snapshot with the user's new text so it survives the pristine restore
    if(window._pristineDom) {
      const id = sel.getAttribute('data-vb-id');
      if(id) window._pristineDom.set(id, sel.innerHTML);
    }
    window.parent.postMessage({type:'vb3-update'},'*');
    sendSel(sel);
  }

  function duplicateEl(){
    window.__vb_action = true;
    if(!sel) { window.__vb_action = false; return; }
    const clone = sel.cloneNode(true);
    clone.removeAttribute('data-sel');
    clone.removeAttribute('data-edit');
    clone.removeAttribute('data-hov');
    clone.removeAttribute('data-vb-id');
    clone.querySelectorAll('*').forEach(x=>x.removeAttribute('data-vb-id'));
    sel.parentNode.insertBefore(clone, sel.nextSibling);
    window.parent.postMessage({type:'vb3-update'},'*');
    selectEl(clone); // Auto-select the newly created element
    setTimeout(() => window.__vb_action = false, 10);
  }

  function deleteEl(){
    window.__vb_action = true;
    if(!sel) { window.__vb_action = false; return; }
    if(sel.hasAttribute('data-global')){
      window.parent.toast('You cannot delete the global wrapper directly. Select and delete elements inside it instead.', 'warning');
      window.__vb_action = false; return;
    }
    const el=sel; clearSel();
    el.remove();
    window.parent.postMessage({type:'vb3-update'},'*');
    setTimeout(() => window.__vb_action = false, 10);
  }

  document.addEventListener('mouseover',e=>{
    if(editing) return;
    const el=e.target;
    if(!el||el===document.body||SKIP.has(el.tagName)||el===sel||el.closest('[data-vb-fixed]')) return;
    if(!e.altKey && el.closest('[data-vb-ignore]')) return;
    document.querySelectorAll('[data-hov]').forEach(x=>x.removeAttribute('data-hov'));
    el.setAttribute('data-hov','1');
  });
  document.addEventListener('mouseout',e=>{ if(e.target) e.target.removeAttribute('data-hov'); });
  document.addEventListener('click',e=>{
    if(editing){e.stopPropagation();return;}
    const el=e.target;
    if(!el||el===document.body||SKIP.has(el.tagName)||el.closest('[data-vb-fixed]')) return;
    if(!e.altKey && el.closest('[data-vb-ignore]')) return;
    e.stopPropagation(); e.preventDefault();
    selectEl(el);
  });
  document.addEventListener('dblclick',e=>{
    const el=e.target;
    if(SKIP.has(el.tagName)||el.closest('[data-vb-fixed]')) return;
    if(!e.altKey && el.closest('[data-vb-ignore]')) return;
    e.stopPropagation(); e.preventDefault();
    if(!sel||sel!==el) selectEl(el);
    // Double-click on image or bg-image → open media picker in parent
    if(el.tagName==='IMG'){
      window.parent.postMessage({type:'vb3-open-media-picker'},'*');
      return;
    }
    // Check for background image
    const bg = el.style.backgroundImage || window.getComputedStyle(el).backgroundImage || '';
    const hasBgImg = bg && bg !== 'none' && bg.includes('url(');
    if(hasBgImg){
      window.parent.postMessage({type:'vb3-open-media-picker'},'*');
      return;
    }
    // Otherwise start inline text editing — allow on locked elements too (Alt+dblclick)
    if(el.hasAttribute('data-vb-ignore') && !e.altKey) {
      // Locked but Alt not held — just select, don't enter edit mode
      return;
    }
    startEdit();
  });
  document.addEventListener('click',e=>{
    if(e.target.closest('a')) e.preventDefault();
  }, true);
  
  document.addEventListener('click',e=>{
    if(!e.target.closest('#vb-body')&&!e.target.closest('#__vb_bar')) clearSel();
  });
  document.addEventListener('keydown',e=>{
    if(e.key==='Escape'){editing?stopEdit():clearSel();}
    if(e.key==='Delete'&&sel&&!editing&&!e.target.matches('input,textarea')){e.preventDefault();deleteEl();}
  });

  btnEdit.addEventListener('click',startEdit);
  btnDup.addEventListener('click',duplicateEl);
  btnDone.addEventListener('click',stopEdit);
  btnDel.addEventListener('click',deleteEl);

  window.addEventListener('message',e=>{
    window.__vb_action = true;
    if(!e.data||!sel) { window.__vb_action = false; return; }
    const d=e.data;
    if(d.type==='vb3-set-text'&&d.text!==undefined){
      sel.innerHTML=d.text;
      // Update pristine snapshot so saving preserves user edits on JS-mutated elements
      if(window._pristineDom) {
        const id = sel.getAttribute('data-vb-id');
        if(id) window._pristineDom.set(id, d.text);
      }
      window.parent.postMessage({type:'vb3-update',html:body.innerHTML},'*');
    }
    if(d.type==='vb3-set-style'){ sel.style[d.prop]=d.val; window.parent.postMessage({type:'vb3-update',html:body.innerHTML},'*'); }
    if(d.type==='vb3-set-img'){
      if(sel.tagName==='IMG'){
        if(d.src!==undefined) sel.setAttribute('src',d.src);
        if(d.alt!==undefined) sel.setAttribute('alt',d.alt);
      } else if(d.src!==undefined) {
        // Background-image element — applyInspImg sends vb3-set-style for this,
        // but handle it here too as a fallback so the update is never lost
        sel.style.backgroundImage = \`url('\${d.src}')\`;
      }
      window.parent.postMessage({type:'vb3-update',html:body.innerHTML},'*');
    }
    if(d.type==='vb3-set-link'){
      const a=sel.closest('a');
      if(a){ if(d.href!==undefined) a.setAttribute('href',d.href); a.setAttribute('target',d.blank?'_blank':'_self'); window.parent.postMessage({type:'vb3-update',html:body.innerHTML},'*'); }
    }
    if(d.type==='vb3-set-custom-css'){
      if(sel.dataset.customCss){
        const oldTmp=document.createElement('div');
        oldTmp.setAttribute('style',sel.dataset.customCss);
        Array.from(oldTmp.style).forEach(prop=>sel.style.removeProperty(prop));
      }
      const tmp=document.createElement('div');
      tmp.setAttribute('style',d.css);
      Array.from(tmp.style).forEach(prop=>{
        sel.style.setProperty(prop, tmp.style.getPropertyValue(prop), tmp.style.getPropertyPriority(prop));
      });
      if(d.css){ sel.dataset.customCss=d.css; } else { sel.removeAttribute('data-custom-css'); }
      window.parent.postMessage({type:'vb3-update',html:body.innerHTML},'*');
    }
    if(d.type==='vb3-image-selected'&&sel){
      if(sel.tagName==='IMG'){
        sel.setAttribute('src',d.url);
      } else {
        sel.style.backgroundImage = "url('" + d.url + "')";
      }
      window.parent.postMessage({type:'vb3-update',html:body.innerHTML},'*');
      sendSel(sel);
    }
    if(d.type==='vb3-set-lock'&&sel){
      if(d.locked) sel.setAttribute('data-vb-ignore','true');
      else sel.removeAttribute('data-vb-ignore');
      window.parent.postMessage({type:'vb3-update',html:body.innerHTML},'*');
      sendSel(sel);
    }
    setTimeout(() => window.__vb_action = false, 10);
  });
  
  // Auto-lock dynamically generated API components
  // Tracks any JS mutation (typewriter, API) to prevent it from saving
  const pageRoot = document.getElementById('__vb_page');
  if (pageRoot) {
    const mo = new MutationObserver(mutations => {
      if (editing || window.__vb_action) return; // ignore builder interactions
      mutations.forEach(m => {
        let target = m.target;
        if (target.nodeType === 3) target = target.parentNode;
        if (!target || target === pageRoot || target.tagName === 'BODY' || target.id === '__vb_chip' || target.id === '__vb_bar') return;
        
        if (target.nodeType === 1) {
          // Mark element as having been mutated by JS (this triggers pristine restore on save)
          target.setAttribute('data-vb-js-mutated', '1');
          
          // Lock the element ONLY if real new elements were added (API/fetch)
          if (m.type === 'childList' && m.addedNodes.length > 0) {
            const hasRealElements = Array.from(m.addedNodes).some(n => n.nodeType === 1);
            if (hasRealElements && !target.hasAttribute('data-vb-ignore') && !target.hasAttribute('data-vb-fixed')) {
              target.setAttribute('data-vb-ignore', 'true');
              if (sel === target) sendSel(target);
            }
          }
        }
      });
    });
    mo.observe(pageRoot, { childList: true, characterData: true, subtree: true });
  }
})();
<\/script>`;

/* ── Build iframe document ── */

// CSS injected in visual-edit mode only: stabilizes carousels/sliders so the
// builder always shows the first slide exactly as the published page does.
const VB_FIX_CSS = `<style id="__vb_fix">
/* Force first carousel slide visible, freeze all transitions */
.carousel-item:first-child,
.slide:first-child,
.swiper-slide:first-child,
.hero-slide:first-child,
.slick-slide:first-child { display:block!important; opacity:1!important; visibility:visible!important; transform:none!important; position:relative!important; }
/* Hide non-first slides so builder shows the hero as-is */
.carousel-item:not(:first-child),
.slide:not(:first-child),
.swiper-slide:not(:first-child),
.hero-slide:not(:first-child) { display:none!important; }
/* Freeze all CSS animations so nothing shifts while editing */
*,*::before,*::after { animation-play-state:paused!important; transition-duration:0s!important; }
/* Re-enable transitions for the builder UI bar only */
#__vb_bar,#__vb_chip { transition-duration:0.15s!important; }
/* Force show normally hidden elements */
[data-aos], .wow, .invisible, .animate-on-scroll,
.fade-up, .fade-down, .fade-left, .fade-right, .fade-in, .zoom-in, .zoom-out { opacity:1!important; transform:none!important; visibility:visible!important; }
#preloader, .preloader { display:none!important; }
/* Builder: reset fixed-nav body offset so content isn't hidden below the fold */
body { padding-top:0!important; margin-top:0!important; }
/* Builder: un-fix the sticky header so it renders inline rather than floating */
.site-header, header.site-header { position:relative!important; top:auto!important; transform:none!important; box-shadow:none!important; }
</style>
<script>window.CMS_IS_EDITOR = true;<\/script>`;

function buildDoc(visual) {
  const bsCss = _bootstrap ? '<link rel="stylesheet" href="/assets/bootstrap/bootstrap.min.css"><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">' : '';
  const bsJs  = _bootstrap ? '<script src="/assets/bootstrap/bootstrap.bundle.min.js"><\/script>' : '';
  const allCss = [_globalCss, _css].filter(Boolean).join('\n');
  const allJs  = [_globalJs, _js].filter(Boolean).join('\n');
  
  const fixCss = visual ? VB_FIX_CSS : '';
  
  // ALWAYS inject user JS so APIs, sliders, and dynamic content can initialize properly in the builder
  const injectEnd = (_end||'');
  const injectJs = `<script>${allJs}<\/script>`;
  
  // Snapshot pristine DOM before user JS executes to allow reverting JS-driven DOM mutations later
  const snapshotJs = visual ? `<script>
    window._pristineDom = new Map();
    window._userEdited = new Set();
    let _vbIdCtr = 0;
    document.querySelectorAll('#__vb_page *').forEach(el => {
      const id = 'vb_' + (++_vbIdCtr);
      el.setAttribute('data-vb-id', id);
      // Store both the innerHTML and the outerHTML tag (for text elements)
      window._pristineDom.set(id, el.innerHTML);
    });
  <\/script>` : '';
  
  // Pre-render the DOM directly into the srcdoc so scripts execute naturally against the existing elements
  const emptyState = '<div style="padding:80px 40px;text-align:center;color:#aaa;font-family:system-ui"><h2 style="margin:0 0 12px">Start building</h2><p style="margin:0;opacity:.5">Drag templates or global elements from the left panel.</p></div>';
  const pageHtml = (_html||'').trim() ? _html : emptyState;
  
  const navLayer = (_navHtml && _navHtml.trim()) ? `<div data-vb-fixed="1">${_navHtml}</div>` : '';
  const footLayer = (_footHtml && _footHtml.trim()) ? `<div data-vb-fixed="1">${_footHtml}</div>` : '';

  return `<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">${bsCss}${_head||''}<style id="vb3-root-styles">${allCss}</style>${fixCss}</head><body><div id="vb-body">${navLayer}<div id="__vb_page">${pageHtml}</div>${footLayer}</div>${bsJs}${injectEnd}${snapshotJs}${injectJs}${visual?VIS_SCRIPT:''}</body></html>`;
}

/* ── Render ── */
function renderIframe() {
  iframe.srcdoc = buildDoc(_visualMode);
  iframe.onload = () => {
    // The DOM is now pre-rendered inside srcdoc, ensuring standard JS executes natively.
    // The auto-scaling ResizeObserver handles the iframe height calculations automatically.
  };
}

/* ── Strip builder-only attributes so they're never saved ── */
function cleanBuilderAttrs(html) {
  return html
    .replace(/ data-sel="[^"]*"/g, '')
    .replace(/ data-hov="[^"]*"/g, '')
    .replace(/ data-edit="[^"]*"/g, '')
    .replace(/ data-vb-id="[^"]*"/g, '')
    .replace(/ data-vb-js-mutated="[^"]*"/g, '')
    .replace(/ contenteditable="(true|false)"/g, '')
    .replace(/ contenteditable/g, '');
}

/* ── postMessage from iframe ── */
window.addEventListener('message', e => {
  if (!e.data) return;
  const d = e.data;
  if (d.type === 'vb3-update') {
    const doc = iframe.contentDocument;
    if (doc) {
      const pageEl = doc.querySelector('#__vb_page');
      if (pageEl) {
        const clone = pageEl.cloneNode(true);
        const win = iframe.contentWindow;
        if (win && win._pristineDom) {
          // Restore ONLY elements that were mutated by JS (typewriter, API, etc)
          clone.querySelectorAll('[data-vb-js-mutated]').forEach(el => {
            const id = el.getAttribute('data-vb-id');
            if (!id || !win._pristineDom.has(id)) { el.remove(); return; }
            const pristine = win._pristineDom.get(id);
            if (el.innerHTML !== pristine) {
              el.innerHTML = pristine;
            }
          });
        }
        _html = cleanBuilderAttrs(clone.innerHTML);
      }
    }
    markDirty();
  }
  if (d.type === 'vb3-set-text' && d.text !== undefined) {
    if (_sel && _sel.elementId) {
        // Just let vb3-update handle DOM synchronization.
        // The child frame posts update directly after setting text.
    }
  }
  if (d.type === 'vb3-selected') {
    _sel = d; populateInspector(d);
    showPanelTab('element', false);
  }
  if (d.type === 'vb3-deselected') {
    _sel = null;
    document.getElementById('panel-empty').style.display = '';
    document.getElementById('panel-inspector').style.display = 'none';
  }
  if (d.type === 'vb3-open-media-picker') {
    // Triggered by double-clicking an image in the canvas
    pickImage();
  }
});

/* ── Populate inspector ── */
function populateInspector(d) {
  document.getElementById('panel-empty').style.display = 'none';
  document.getElementById('panel-inspector').style.display = '';
  document.getElementById('insp-element-type').textContent = '<' + d.tag + '>';
  document.getElementById('insp-lock').checked = !!d.isLocked;
  document.getElementById('insp-text-group').style.display = d.isText ? '' : 'none';
  if (d.isText) document.getElementById('insp-text-val').value = d.text;
  document.getElementById('insp-img-group').style.display = (d.isImg || d.hasBgImg) ? '' : 'none';
  if (d.isImg || d.hasBgImg) {
    const url = d.isImg ? (d.src || '') : (d.bgUrl || '');
    document.getElementById('insp-img-src').value = url;
    document.getElementById('insp-img-alt').value = d.alt || '';
    
    const altWrap = document.getElementById('insp-img-alt').closest('.insp-field-label')?.parentElement || document.getElementById('insp-img-alt').parentElement;
    if(altWrap) altWrap.style.display = d.isImg ? '' : 'none'; // hide alt if background image

    const p = document.getElementById('insp-img-preview');
    if (url) { p.src = url; p.style.display = ''; } else p.style.display = 'none';
  }
  document.getElementById('insp-link-group').style.display = d.isLink ? '' : 'none';
  if (d.isLink) { document.getElementById('insp-link-href').value = d.href || ''; document.getElementById('insp-link-blank').checked = d.blank || false; }
  const s = d.styles || {};
  const f = (id,v) => { const el=document.getElementById(id); if(el) el.value=v||''; };
  const fSel = (id,v) => { const el=document.getElementById(id); if(el) el.value=v||''; };
  f('insp-font-size', s.fontSize); fSel('insp-font-weight', s.fontWeight);
  f('insp-color-txt', normColor(s.color));
  try { document.getElementById('insp-color-pick').value = normHex(s.color); } catch(e) {}
  f('insp-pt',s.paddingTop); f('insp-pr',s.paddingRight); f('insp-pb',s.paddingBottom); f('insp-pl',s.paddingLeft);
  f('insp-mt',s.marginTop); f('insp-mr',s.marginRight); f('insp-mb',s.marginBottom); f('insp-ml',s.marginLeft);
  f('insp-w',s.width); f('insp-h',s.height);
  f('insp-bg-txt', normColor(s.backgroundColor));
  try { document.getElementById('insp-bg-pick').value = normHex(s.backgroundColor); } catch(e) {}
  f('insp-border',s.border); f('insp-radius',s.borderRadius); f('insp-custom-css',s.customCss);
}
function normColor(c) {
  if (!c) return '';
  if (c.startsWith('#')) return c;
  const m = c.match(/rgb\((\d+),\s*(\d+),\s*(\d+)\)/);
  return m ? '#'+[m[1],m[2],m[3]].map(n=>(+n).toString(16).padStart(2,'0')).join('') : c;
}
function normHex(c) { const h=normColor(c); return h.startsWith('#')?h:'#000000'; }

/* ── Send updates to iframe ── */
function sendToFrame(msg) { try { iframe.contentWindow.postMessage(msg, '*'); } catch(e){} }
function applyInspText() { sendToFrame({type:'vb3-set-text',text:document.getElementById('insp-text-val').value}); markDirty(); }
function applyInspImg() {
  const src=document.getElementById('insp-img-src').value;
  const p=document.getElementById('insp-img-preview');
  if(src){p.src=src;p.style.display='';}else p.style.display='none';
  
  if (_sel && _sel.isImg) {
    sendToFrame({type:'vb3-set-img',src});
  } else if (_sel && _sel.hasBgImg) {
    sendToFrame({type:'vb3-set-style',prop:'backgroundImage',val:`url('${src}')`});
  }
  markDirty();
}
function applyInspImgAlt() { sendToFrame({type:'vb3-set-img',alt:document.getElementById('insp-img-alt').value}); markDirty(); }
function applyInspLink() { sendToFrame({type:'vb3-set-link',href:document.getElementById('insp-link-href').value,blank:document.getElementById('insp-link-blank').checked}); markDirty(); }
function applyInspStyle(prop,val) { sendToFrame({type:'vb3-set-style',prop,val}); markDirty(); }
function applyInspCustomCss() { sendToFrame({type:'vb3-set-custom-css',css:document.getElementById('insp-custom-css').value}); markDirty(); }
function applyInspLock() { sendToFrame({type:'vb3-set-lock',locked:document.getElementById('insp-lock').checked}); markDirty(); }
function setAlignActive(btn) { document.querySelectorAll('.insp-align-btn').forEach(b=>b.classList.remove('active')); btn.classList.add('active'); }
function deleteSelectedElement() { sendToFrame({type:'vb3-del'}); document.getElementById('panel-empty').style.display=''; document.getElementById('panel-inspector').style.display='none'; _sel=null; }
function pickImage() {
  if (window.cmsMediaPicker) {
    window.cmsMediaPicker.open({
      title: 'Select Image',
      imagesOnly: true,
      source: 'visual-builder',
      onSelect: (media) => {
        const url = media.url || media.raw_url || '';
        document.getElementById('insp-img-src').value = url;
        // ── FIX: use a single code-path for both <img> and background-image.
        // applyInspImg() already dispatches vb3-set-img / vb3-set-style to the
        // iframe which in turn posts vb3-update back — no second message needed.
        applyInspImg();
        // Update the inspector preview panel
        const p = document.getElementById('insp-img-preview');
        if (url) { p.src = url; p.style.display = ''; } else p.style.display = 'none';
      }
    });
  } else {
    const url = prompt('Image URL:');
    if (url) { document.getElementById('insp-img-src').value = url; applyInspImg(); }
  }
}

/* ── Panel tabs ── */
function showPanelTab(name, switchUI=true) {
  if (!switchUI) return;
  document.querySelectorAll('.panel-tabs > .panel-tab:not(.ltab-btn)').forEach(t=>t.classList.toggle('active',t.dataset.ptab===name));
  document.querySelectorAll('.ptab-content').forEach(c=>c.classList.toggle('active',c.id==='ptab-'+name));
}

function showLeftTab(name) {
  document.querySelectorAll('.ltab-btn').forEach(t=>t.classList.toggle('active',t.dataset.ltab===name));
  document.getElementById('ltab-templates').style.display = (name==='templates') ? 'flex' : 'none';
  document.getElementById('ltab-tokens').style.display = (name==='tokens') ? 'flex' : 'none';
}

/* ── Viewport ── */
function setViewport(vp) {
  ['desktop','tablet','mobile'].forEach(v => document.getElementById('vp-'+v).classList.toggle('active', v===vp));
  const shell = document.getElementById('vb3-frame-shell');
  shell.className = 'vp-' + vp;

  // Label shown in the frame chrome
  const labels = { desktop: '🖥 Desktop — 1280px', tablet: '📱 Tablet — 768px', mobile: '📱 Mobile — 390px' };
  shell.setAttribute('data-vp-label', labels[vp] || '');
}

/* ── Templates ── */
async function loadTemplates() {
  try {
    const res = await fetch(`{!! route('admin.vbuilder3.components.list') !!}`);
    const map = await res.json();
    const l = document.getElementById('tpl-list');
    l.innerHTML = '';
    let count = 0;
    for (const [cat, arr] of Object.entries(map)) {
      const h = document.createElement('div');
      h.className = 'tpl-cat-title';
      h.textContent = cat;
      l.appendChild(h);
      arr.forEach(t => {
        count++;
        const b = document.createElement('div');
        b.className = 'tpl-item';
        b.innerHTML = `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg> ${t.name}`;
        b.onclick = () => insertTemplate(t);
        l.appendChild(b);
      });
    }
    if(!count) l.innerHTML = '<div style="padding:20px;text-align:center;color:var(--t3);font-size:12px;">No templates yet.<br><br><a href="{{ route("admin.vbuilder3.pages") }}" style="color:var(--acc);text-decoration:none">Manage Templates</a></div>';
  } catch(e) {}
}

function insertTemplate(t) {
  pushHistory();
  if (_html.includes('Start building') && _html.includes('<h2')) _html = '';
  // Wrap in a tracked block so template updates can auto-propagate to this page
  const block = `\n<div data-tpl-id="${t.id}" data-tpl-name="${(t.name||'').replace(/"/g,'&quot;')}">\n${t.base_html || ''}\n</div>`;
  _html += block;
  if (t.base_css) _css += '\n/* ' + t.name + ' */\n' + t.base_css;
  if (t.base_js) _js += '\n// ' + t.name + '\n' + t.base_js;
  
  const pcss = document.getElementById('ps-css');
  if (pcss) pcss.value = _css;
  const pjs = document.getElementById('ps-js');
  if (pjs) pjs.value = _js;
  
  renderIframe();
  toast(`Added ${t.name}`, 'success');
  markDirty();
}

/* ── Page settings ── */
function pageCssChanged() { _css=document.getElementById('ps-css').value; markDirty(); renderIframe(); }
function pageJsChanged()  { _js=document.getElementById('ps-js').value;  markDirty(); }
function globalCssChanged() { _globalCss=document.getElementById('ps-global-css').value; markDirty(); renderIframe(); }
function globalJsChanged()  { _globalJs=document.getElementById('ps-global-js').value; markDirty(); renderIframe(); }

function extractStylesToGlobal() {
  const doc = iframe.contentDocument;
  if (!doc) return;
  const styles = doc.querySelectorAll('style:not(#vb3-root-styles):not(#__vb_fix)');
  let extractedItems = [];
  let modifiedAny = false;
  
  styles.forEach(s => {
    let cssText = s.textContent;
    // Exclude the system CSS reset injected by the builder itself
    if (cssText.includes('FIX: Stop fixed elements')) return;
    
    // Match :root { ... } blocks
    const rootRegex = /:root\s*\{([^}]*)\}/g;
    let match;
    let changesMadeToThisStyle = false;
    
    while ((match = rootRegex.exec(cssText)) !== null) {
      if (match[1] && match[1].trim()) {
        extractedItems.push(match[1].trim());
        changesMadeToThisStyle = true;
      }
    }
    
    if (changesMadeToThisStyle) {
      // Remove the :root blocks from the inline style
      cssText = cssText.replace(/:root\s*\{[^}]*\}/g, '');
      if (cssText.trim() === '') {
        s.remove();
      } else {
        s.textContent = cssText;
      }
      modifiedAny = true;
    }
  });
  
  if (extractedItems.length > 0) {
      // Sync the stripped HTML back to variables immediately before renderIframe runs
      const clone = doc.body.cloneNode(true);
      const navEl = clone.querySelector('[data-global="nav"]');
      if (navEl) {
          _navHtml = navEl.innerHTML;
          navEl.remove();
      }
      const footerEl = clone.querySelector('[data-global="footer"]');
      if (footerEl) {
          _footHtml = footerEl.innerHTML;
          footerEl.remove();
      }
      _html = clone.innerHTML;

      const gta = document.getElementById('ps-global-css');
      let existingCss = gta.value;
      
      const newRootContent = extractedItems.join('\n  ');
      if (existingCss.includes(':root {')) {
          existingCss = existingCss.replace(/:root\s*\{/, ':root {\n  ' + newRootContent + '\n');
      } else {
          existingCss = ':root {\n  ' + newRootContent + '\n}\n\n' + existingCss;
      }
      
      gta.value = existingCss.trim();
      globalCssChanged(); 
      toast('Extracted :root variables to Theme CSS tab.', 'success');
  } else {
      toast('No :root variables found in inline <style> tags.', 'info');
  }
}

function toggleBootstrap(on) { _bootstrap=on; renderIframe(); }

/* ── Dirty ── */
function markDirty() {
  _dirty=true;
  const el=document.getElementById('vb3-status');
  el.textContent='Unsaved'; el.className='tb-status unsaved';
}

/* ── History / Undo ── */
function pushHistory() { _history.push({html:_html,css:_css,js:_js,head:_head,end:_end}); if(_history.length>50) _history.shift(); }
function undoHistory() { if(!_history.length){toast('Nothing to undo','error');return;} const s=_history.pop(); _html=s.html;_css=s.css;_js=s.js;_head=s.head;_end=s.end; renderIframe(); toast('Undone','info'); }

function autoGenerateMeta() {
  const titleInput = document.getElementById('ps-title').value.trim() || document.getElementById('vb3-page-title').value.trim();
  document.getElementById('ps-meta-title').value = titleInput;

  let text = '';
  try {
      const frameBody = iframe.contentDocument?.getElementById('vb-body');
      if (frameBody) {
          const clone = frameBody.cloneNode(true);
          const headers = clone.querySelectorAll('header, footer, nav, script, style, .site-header, .site-footer');
          headers.forEach(h => h.remove());
          text = clone.innerText.replace(/\s+/g, ' ').trim();
      }
  } catch(e) {}
  
  // Extract keywords (unique words > 4 chars)
  const words = text.toLowerCase().replace(/[^a-z0-9\s]/g, '').split(' ')
    .filter(w => w.length > 4 && !['there', 'their', 'about', 'would', 'could', 'these', 'those', 'where', 'which'].includes(w));
  const keywords = [...new Set(words)].slice(0, 12).join(', ');
  if (keywords && !document.getElementById('ps-meta-keywords').value) {
      document.getElementById('ps-meta-keywords').value = keywords;
  }

  if (text.length > 155) text = text.substring(0, 153) + '...';
  document.getElementById('ps-meta-desc').value = text;
  
  updateMetaCounter('ps-meta-title', 'ps-meta-title-ct', 60);
  updateMetaCounter('ps-meta-desc', 'ps-meta-desc-ct', 160);
  
  toast('SEO Meta & Keywords generated!', 'success');
  markDirty();
}

/* ── Save ── */
async function savePage(publish = false) {
  const btnSave=document.getElementById('btn-save');
  const btnPublish=document.getElementById('btn-publish');
  const title=document.getElementById('vb3-page-title').value.trim();
  const slug=document.getElementById('vb3-page-slug').value.trim();
  const meta_title=document.getElementById('ps-meta-title')?.value.trim() || '';
  const meta_desc=document.getElementById('ps-meta-desc')?.value.trim() || '';

  if(!title){toast('Page Title is required','error');return;}
  const activeBtn = publish ? btnPublish : btnSave;
  activeBtn.disabled=true;
  activeBtn.textContent = publish ? 'Publishing…' : 'Saving…';
  
  let curNavHtml = _navHtml;
  let curFootHtml = _footHtml;

  // ── FIX: Final clean sync before save — strip ALL builder UI attributes ──
  // This guarantees the saved HTML is always clean regardless of editor state.
  try {
    const frameBody = iframe.contentDocument?.getElementById('vb-body');
    if (frameBody) {
       // Gracefully end any active inline text editing session
       const editEl = frameBody.querySelector('[data-edit]');
       if (editEl) {
         editEl.contentEditable = 'false';
         editEl.removeAttribute('data-edit');
         editEl.setAttribute('data-sel','1');
       }
       // Clear remaining selection markers before reading innerHTML
       frameBody.querySelectorAll('[data-sel]').forEach(el => el.removeAttribute('data-sel'));
       frameBody.querySelectorAll('[data-hov]').forEach(el => el.removeAttribute('data-hov'));
       const pageEl = frameBody.querySelector('#__vb_page');
       if (pageEl) _html = cleanBuilderAttrs(pageEl.innerHTML);
    }
  } catch(e) {}

  try {
    const payload = {
      title, slug, meta_title, meta_description: meta_desc,
      meta_keywords       : document.getElementById('ps-meta-keywords')?.value.trim() || '',
      canonical_url       : document.getElementById('ps-canonical')?.value.trim() || '',
      og_title            : document.getElementById('ps-og-title')?.value.trim() || '',
      og_description      : document.getElementById('ps-og-desc')?.value.trim() || '',
      og_image            : document.getElementById('ps-og-image')?.value.trim() || '',
      base_html : _html,
      nav_html  : curNavHtml,
      footer_html: curFootHtml,
      base_css  : _css,
      global_css: _globalCss,
      global_js : _globalJs,
      base_js   : _js,
      head_code : _head,
      end_code  : _end,
      page_id   : PAGE_ID || null,
      use_bootstrap: _bootstrap ? 1 : 0,
    };
    if (publish) payload.publish = true;
    
    let r=await fetch(SAVE_URL,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF,'Accept':'application/json'},body:JSON.stringify(payload)});
    
    // Auto-rescue CSRF mismatch
    if (r.status === 419) {
        toast('Renewing expired security token...', 'info');
        const refreshReq = await fetch(window.location.href);
        const refreshHtml = await refreshReq.text();
        const match = refreshHtml.match(/<meta name="csrf-token" content="(.*?)">/);
        if (match && match[1]) {
            CSRF = match[1];
            r = await fetch(SAVE_URL,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':CSRF,'Accept':'application/json'},body:JSON.stringify(payload)});
        }
    }

    if (r.status === 401) {
        toast('Your login session expired. Please log in again in a new tab, then retry saving here.', 'error');
        return;
    }

    const data=await r.json();
    if(data.success){
      _dirty=false;
      const st=document.getElementById('vb3-status');
      if(data.published){
        st.textContent='Published'; st.className='tb-status published';
        toast(data.message||'Published!','success');
      } else {
        st.textContent='Saved'; st.className='tb-status saved';
        toast(data.message||'Saved!','success');
      }
    } else toast(data.message||'Save failed','error');
  } catch(e){toast('Network error','error');}
  finally{
    btnSave.disabled=false;
    btnSave.innerHTML='<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13"/><polyline points="7 3 7 8 15 8"/></svg> Save Draft';
    btnPublish.disabled=false;
    btnPublish.innerHTML='<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg> Publish';
  }
}

/* ── Toast ── */
function toast(msg,type='info'){const el=document.getElementById('vb3-toast');el.textContent=msg;el.className='show '+type;clearTimeout(el._t);el._t=setTimeout(()=>el.className='',3000);}

/* ── Tab indent ── */
document.querySelectorAll('textarea').forEach(ta=>{ta.addEventListener('keydown',e=>{if(e.key==='Tab'){e.preventDefault();const s=ta.selectionStart;ta.value=ta.value.substring(0,s)+'  '+ta.value.substring(ta.selectionEnd);ta.selectionStart=ta.selectionEnd=s+2;}});})

/* ── Auto-slug + two-way title sync ── */
function makeSlug(v){return v.toLowerCase().trim().replace(/[^a-z0-9\s-]/g,'').replace(/\s+/g,'-').replace(/-+/g,'-');}

function syncTitleFromPanel(v) {
  // Panel title → header title
  document.getElementById('vb3-page-title').value = v;
  // Auto-slug only for NEW pages (no PAGE_ID yet)
  if (!PAGE_ID) {
    const s = makeSlug(v);
    document.getElementById('vb3-page-slug').value = s;
    document.getElementById('ps-slug').value = s;
    const base = '{{ url('/') }}';
    document.getElementById('ps-slug-preview').textContent = base + '/' + s;
  }
  markDirty();
}

// Header title → panel title + slug
document.getElementById('vb3-page-title').addEventListener('input', function() {
  document.getElementById('ps-title').value = this.value;
  if (!PAGE_ID) {
    const s = makeSlug(this.value);
    document.getElementById('vb3-page-slug').value = s;
    document.getElementById('ps-slug').value = s;
    const base = '{{ url('/') }}';
    document.getElementById('ps-slug-preview').textContent = base + '/' + s;
  }
  markDirty();
});

// Slug field → update preview URL
document.getElementById('ps-slug').addEventListener('input', function() {
  const base = '{{ url('/') }}';
  document.getElementById('ps-slug-preview').textContent = base + '/' + this.value;
});

/* ── Meta character counters ── */
function updateMetaCounter(fieldId, counterId, max) {
  const len = document.getElementById(fieldId).value.length;
  const el = document.getElementById(counterId);
  if (!el) return;
  el.textContent = len;
  el.style.color = len > max ? 'var(--red)' : (len > max * 0.85 ? 'var(--amber)' : 'var(--t3)');
}

/* ── OG Image preview ── */
function updateOgImagePreview() {
  const url = document.getElementById('ps-og-image').value.trim();
  const wrap = document.getElementById('ps-og-img-preview');
  const img  = document.getElementById('ps-og-img-el');
  if (url) { img.src = url; wrap.style.display = 'block'; }
  else      { wrap.style.display = 'none'; }
}

function pickOgImage() {
  if (!window.cmsMediaPicker?.open) { toast('Media picker not loaded', 'error'); return; }
  window.cmsMediaPicker.open({ title: 'Select OG Image', imagesOnly: true, onSelect(m) {
    if (!m?.url) return;
    document.getElementById('ps-og-image').value = m.url;
    updateOgImagePreview();
    markDirty();
  }});
}

/* ── Init character counters ── */
updateMetaCounter('ps-meta-title', 'ps-meta-title-ct', 60);
updateMetaCounter('ps-meta-desc',  'ps-meta-desc-ct',  160);

/* ── Keyboard shortcuts ── */
document.addEventListener('keydown',e=>{if(e.ctrlKey||e.metaKey){if(e.key==='s'){e.preventDefault();savePage(false);}if(e.key==='z'){e.preventDefault();undoHistory();}}});

/* ── Warn unsaved ── */
window.addEventListener('beforeunload',e=>{if(_dirty){e.preventDefault();e.returnValue='';}})

/* ── Set initial status badge color ── */
document.addEventListener('DOMContentLoaded', () => {
  const isPublished = '{{ $page?->status ?? "draft" }}' === 'published';
  const st = document.getElementById('vb3-status');
  if (!isPublished) { st.textContent = 'Draft'; st.className = 'tb-status draft'; }
});

/* ── Init ── */
renderIframe();
loadTemplates();
// Fetch fresh nav/footer from the DB so the canvas never shows stale data
refreshGlobalNav();

/* ── Auto Scale Canvas to Fit ── */
(function() {
  const canvasWrap = document.getElementById('vb3-canvas-wrap');
  const frameShell = document.getElementById('vb3-frame-shell');
  
  function applyScale() {
    let targetWidth = 1280;
    if (frameShell.classList.contains('vp-tablet')) targetWidth = 768;
    if (frameShell.classList.contains('vp-mobile')) targetWidth = 390;

    const wrapWidth = canvasWrap.clientWidth;
    const wrapHeight = canvasWrap.clientHeight;
    const padding = 48; // 24px left + 24px right padding
    
    if (wrapWidth < targetWidth + padding) {
      const scale = (wrapWidth - padding) / targetWidth;
      frameShell.style.transform = `scale(${scale})`;
      
      // Calculate exact layout height needed so visual height matches wrapper exactly minus padding
      const h = (wrapHeight - padding) / scale;
      frameShell.style.height = h + 'px';
    } else {
      frameShell.style.transform = 'none';
      frameShell.style.height = (wrapHeight - padding) + 'px';
    }
  }

  // Observe resizing of the canvas wrapper (due to window resize or panel toggles)
  new ResizeObserver(applyScale).observe(canvasWrap);
})();
</script>
</body>
</html>
