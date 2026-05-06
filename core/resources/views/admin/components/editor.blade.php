@extends('admin.layout')

@section('title', 'Component Editor')

@section('content')
<div id="comp-editor-app" style="display:flex;height:calc(100vh - var(--admin-topbar-h,60px));overflow:hidden">

  {{-- ── LEFT SIDEBAR: component list + actions ─────────────────────── --}}
  <div id="ce-sidebar" style="width:240px;flex-shrink:0;background:var(--bg2);border-right:1px solid var(--bd1);display:flex;flex-direction:column;overflow:hidden">

    {{-- Header --}}
    <div style="padding:14px 16px;border-bottom:1px solid var(--bd1)">
      <div style="font-size:13px;font-weight:700;color:var(--t1);margin-bottom:10px">📁 Component Editor</div>
      <button id="ce-btn-new" style="width:100%;height:30px;background:var(--ac);color:#fff;border:none;border-radius:var(--r2);font-size:12px;font-weight:600;cursor:pointer">+ New Component</button>
    </div>

    {{-- Search --}}
    <div style="padding:8px 12px;border-bottom:1px solid var(--bd1)">
      <input id="ce-search" type="text" placeholder="Search components…" style="width:100%;height:28px;background:var(--bg3);border:1px solid var(--bd2);border-radius:var(--r);color:var(--t1);padding:0 10px;font-size:11px;box-sizing:border-box;outline:none">
    </div>

    {{-- List --}}
    <div id="ce-list" style="flex:1;overflow-y:auto;padding:6px">
      @forelse($components as $comp)
        <div class="ce-item{{ isset($current) && $current['slug'] === $comp['slug'] ? ' active' : '' }}"
             data-slug="{{ $comp['slug'] }}"
             onclick="location.href=`/${window.ADMIN_PREFIX}/components/editor?slug={{ $comp['slug'] }}`"
             style="display:flex;align-items:center;gap:8px;padding:7px 10px;border-radius:var(--r);cursor:pointer;margin-bottom:2px;transition:background .1s">
          <span style="font-size:16px">{{ $comp['icon'] ?? '🧩' }}</span>
          <div style="flex:1;min-width:0">
            <div style="font-size:11px;font-weight:600;color:var(--t1);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $comp['name'] }}</div>
            <div style="font-size:9px;color:var(--t3)">{{ $comp['category'] ?? 'custom' }}</div>
          </div>
        </div>
      @empty
        <div style="padding:12px;font-size:11px;color:var(--t3);text-align:center">
          No components yet.<br>Click "+ New" to create one.
        </div>
      @endforelse
    </div>

    {{-- Footer --}}
    <div style="padding:10px 12px;border-top:1px solid var(--bd1);font-size:10px;color:var(--t3)">
      Folder: <code style="font-size:9px">builder/components/</code>
    </div>
  </div>

  {{-- ── MAIN EDITOR AREA ─────────────────────────────────────────────── --}}
  <div id="ce-main" style="flex:1;display:flex;flex-direction:column;overflow:hidden;background:var(--bg1)">

    @if($current)
    {{-- Tab bar --}}
    <div id="ce-tabs" style="display:flex;align-items:center;padding:0 16px;height:38px;background:var(--bg2);border-bottom:1px solid var(--bd1);gap:2px;flex-shrink:0">
      <button class="ce-tab active" data-tab="template" style="height:26px;padding:0 14px;border-radius:var(--r);font-size:11px;font-weight:600;cursor:pointer;border:1px solid transparent">template.html</button>
      <button class="ce-tab" data-tab="css" style="height:26px;padding:0 14px;border-radius:var(--r);font-size:11px;cursor:pointer;border:1px solid transparent">style.css</button>
      <button class="ce-tab" data-tab="js" style="height:26px;padding:0 14px;border-radius:var(--r);font-size:11px;cursor:pointer;border:1px solid transparent">script.js</button>
      <button class="ce-tab" data-tab="schema" style="height:26px;padding:0 14px;border-radius:var(--r);font-size:11px;cursor:pointer;border:1px solid transparent">component.json</button>
      <div style="flex:1"></div>
      <div id="ce-save-status" style="font-size:10px;color:var(--t3);margin-right:8px"></div>
      <button id="ce-btn-save" style="height:28px;padding:0 16px;background:var(--ac);color:#fff;border:none;border-radius:var(--r2);font-size:12px;font-weight:600;cursor:pointer">💾 Save</button>
      <button id="ce-btn-delete" style="height:28px;padding:0 10px;background:none;border:1px solid var(--red);color:var(--red);border-radius:var(--r2);font-size:11px;cursor:pointer;margin-left:6px">🗑</button>
    </div>

    {{-- Component info bar --}}
    <div style="display:flex;align-items:center;gap:10px;padding:8px 16px;background:var(--bg2);border-bottom:1px solid var(--bd1);flex-shrink:0">
      <span style="font-size:20px">{{ $current['icon'] ?? '🧩' }}</span>
      <div>
        <div style="font-size:13px;font-weight:700;color:var(--t1)">{{ $current['name'] }}</div>
        <div style="font-size:10px;color:var(--t3)">slug: <strong>{{ $current['slug'] }}</strong> · category: <strong>{{ $current['category'] }}</strong> · {{ count($current['fields'] ?? []) }} field(s)</div>
      </div>
      <div style="flex:1"></div>
      <a href="javascript:void(0)" onclick="window.open(`/${window.ADMIN_PREFIX}/visual-builder/pages`, '_blank')" style="font-size:11px;color:var(--ac);text-decoration:none;padding:5px 10px;border:1px solid var(--ac);border-radius:var(--r)">↗ Open in Builder</a>
    </div>

    {{-- Editors (one shown at a time) --}}
    <div style="flex:1;overflow:hidden;position:relative">
      <textarea id="ce-editor-template" class="ce-editor"
        style="position:absolute;inset:0;width:100%;height:100%;background:var(--bg0);color:var(--text);border:none;padding:16px;font-family:'Fira Code',Consolas,monospace;font-size:13px;line-height:1.6;resize:none;outline:none;box-sizing:border-box;tab-size:2"
      >{{ $current['template'] ?? '' }}</textarea>

      <textarea id="ce-editor-css" class="ce-editor"
        style="position:absolute;inset:0;width:100%;height:100%;background:var(--bg0);color:var(--text);border:none;padding:16px;font-family:'Fira Code',Consolas,monospace;font-size:13px;line-height:1.6;resize:none;outline:none;box-sizing:border-box;tab-size:2;display:none"
      >{{ $current['css'] ?? '' }}</textarea>

      <textarea id="ce-editor-js" class="ce-editor"
        style="position:absolute;inset:0;width:100%;height:100%;background:var(--bg0);color:var(--text);border:none;padding:16px;font-family:'Fira Code',Consolas,monospace;font-size:13px;line-height:1.6;resize:none;outline:none;box-sizing:border-box;tab-size:2;display:none"
      >{{ $current['js'] ?? '' }}</textarea>

      <textarea id="ce-editor-schema" class="ce-editor"
        style="position:absolute;inset:0;width:100%;height:100%;background:var(--bg0);color:var(--text);border:none;padding:16px;font-family:'Fira Code',Consolas,monospace;font-size:13px;line-height:1.6;resize:none;outline:none;box-sizing:border-box;tab-size:2;display:none"
      >{{ $current['schema_raw'] ?? '' }}</textarea>
    </div>

    {{-- Preview strip --}}
    <div id="ce-preview" style="height:180px;flex-shrink:0;border-top:1px solid var(--bd1);overflow:auto;background:#f9fafb">
      <div style="padding:4px 12px;font-size:9px;color:#9ca3af;border-bottom:1px solid #e5e7eb;display:flex;align-items:center;gap:6px">
        <span>HTML Preview</span>
        <button id="ce-refresh-preview" style="font-size:9px;background:none;border:none;cursor:pointer;color:var(--accent)">↻ Refresh</button>
      </div>
      <div id="ce-preview-body" style="padding:12px"></div>
    </div>

    @else
    {{-- No component selected --}}
    <div style="flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:12px;color:var(--t3)">
      <div style="font-size:48px">📁</div>
      <div style="font-size:14px;font-weight:600">Select a component or create a new one</div>
      <div style="font-size:12px">Components are stored as files in <code>builder/components/</code></div>
      <button onclick="document.getElementById('ce-btn-new').click()" style="height:32px;padding:0 20px;background:var(--ac);color:#fff;border:none;border-radius:var(--r2);font-weight:600;cursor:pointer;font-size:13px">+ Create Component</button>
    </div>
    @endif
  </div>
</div>

<style>
:root { --admin-topbar-h: 60px; }
.ce-item:hover  { background: var(--bg3); }
.ce-item.active { background: var(--ac3); }
.ce-item.active .ce-name { color: var(--t1); font-weight: 700; }
.ce-tab { background: transparent; color: var(--t2); }
.ce-tab.active  { background: var(--ac3); color: var(--ac2); border-color: var(--bd3) !important; }
.ce-tab:hover   { background: var(--bg3); color: var(--t1); }
</style>

<script>
(function(){
  const SLUG = @json($current['slug'] ?? null);
  const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';

  // ── Tab switching ──────────────────────────────────────────────────
  const tabs = document.querySelectorAll('.ce-tab');
  tabs.forEach(tab => {
    tab.addEventListener('click', () => {
      tabs.forEach(t => t.classList.remove('active'));
      tab.classList.add('active');
      document.querySelectorAll('.ce-editor').forEach(e => e.style.display = 'none');
      const target = document.getElementById('ce-editor-' + tab.dataset.tab);
      if (target) target.style.display = '';
    });
  });

  // ── Search filter in sidebar ───────────────────────────────────────
  const searchEl = document.getElementById('ce-search');
  searchEl?.addEventListener('input', () => {
    const q = searchEl.value.toLowerCase();
    document.querySelectorAll('.ce-item').forEach(item => {
      item.style.display = item.dataset.slug.includes(q) || item.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
  });

  // ── Save ──────────────────────────────────────────────────────────
  const saveBtn = document.getElementById('ce-btn-save');
  const status  = document.getElementById('ce-save-status');

  async function save() {
    if (!SLUG) return;
    saveBtn.disabled = true;
    status.textContent = 'Saving…';
    try {
      const res = await fetch(`/${window.ADMIN_PREFIX}/components/files/${SLUG}`, {
        method : 'PUT',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
        body   : JSON.stringify({
          template:   document.getElementById('ce-editor-template')?.value,
          css:        document.getElementById('ce-editor-css')?.value,
          js:         document.getElementById('ce-editor-js')?.value,
          schema_raw: document.getElementById('ce-editor-schema')?.value,
        }),
      });
      const data = await res.json();
      if (!res.ok || data.error) throw new Error(data.error || 'Save failed');
      status.textContent = '✓ Saved';
      status.style.color = 'var(--grn)';
      refreshPreview();
    } catch(e) {
      status.textContent = '✕ ' + e.message;
      status.style.color = 'var(--red)';
    } finally {
      saveBtn.disabled = false;
    }
  }

  saveBtn?.addEventListener('click', save);

  // Ctrl+S
  document.addEventListener('keydown', e => {
    if ((e.ctrlKey || e.metaKey) && e.key === 's') { e.preventDefault(); save(); }
  });

  // ── Delete ────────────────────────────────────────────────────────
  document.getElementById('ce-btn-delete')?.addEventListener('click', async function deleteComponent() {
    if (!(await cmsConfirm('Delete Component', `Delete component "${SLUG}" and its files? This cannot be undone.`, 'Delete'))) return;
    await fetch(`/${window.ADMIN_PREFIX}/components/files/${SLUG}`, {
      method : 'DELETE',
      headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
    });
    location.href = `/${window.ADMIN_PREFIX}/components/editor`;
  });

  // ── New component ─────────────────────────────────────────────────
  document.getElementById('ce-btn-new')?.addEventListener('click', async () => {
    const name = prompt('Component name (e.g. "Testimonial Card"):');
    if (!name) return;
    const res  = await fetch(`/${window.ADMIN_PREFIX}/components/files`, {
      method : 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
      body   : JSON.stringify({ name }),
    });
    const data = await res.json();
    if (data.error) { cmsToast(data.error, 'error'); return; }
    location.href = `/${window.ADMIN_PREFIX}/components/editor?slug=${data.component?.slug}`;
  });

  // ── Live preview ──────────────────────────────────────────────────
  function refreshPreview() {
    const templateEl = document.getElementById('ce-editor-template');
    const cssEl      = document.getElementById('ce-editor-css');
    const body       = document.getElementById('ce-preview-body');
    if (!body || !templateEl) return;

    // Replace {{key}} with placeholder text for preview
    let html = templateEl.value.replace(/\{\{([a-z_][a-z0-9_]*)\}\}/gi, (_, k) => `<em style="color:var(--accent)">[${k}]</em>`);
    const css  = cssEl ? `<style>${cssEl.value}</style>` : '';
    body.innerHTML = css + html;
  }

  document.getElementById('ce-refresh-preview')?.addEventListener('click', refreshPreview);

  // Auto-preview on tab switch to template/css
  document.querySelectorAll('.ce-tab[data-tab="template"], .ce-tab[data-tab="css"]').forEach(t => {
    t.addEventListener('click', () => setTimeout(refreshPreview, 50));
  });

  // Initial preview
  if (SLUG) setTimeout(refreshPreview, 100);

  // Tab char in textareas
  document.querySelectorAll('.ce-editor').forEach(ta => {
    ta.addEventListener('keydown', e => {
      if (e.key === 'Tab') {
        e.preventDefault();
        const s = ta.selectionStart, en = ta.selectionEnd;
        ta.value = ta.value.substring(0, s) + '  ' + ta.value.substring(en);
        ta.selectionStart = ta.selectionEnd = s + 2;
      }
    });
  });

  // Dirty tracking
  document.querySelectorAll('.ce-editor').forEach(ta => {
    ta.addEventListener('input', () => {
      status.textContent = 'Unsaved changes•';
      status.style.color = 'var(--org)';
    });
  });
})();
</script>
@endsection
