@extends('admin.layout')

@section('title', 'V3 Visual Builder — Pages')

@section('content')
<div id="v3-pages-root">

  <div class="vb3-header">
    <div>
      <div style="display:flex; align-items:center; gap:20px; margin-bottom: 8px;">
        <h1 class="vb3-title" style="margin:0">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg>
          V3 Visual Builder
        </h1>
        <div class="vb3-tabs">
          <button class="vb3-tab active" id="tabbtn-pages" onclick="switchTab('pages')">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
            Pages
          </button>
          <button class="vb3-tab" id="tabbtn-templates" onclick="switchTab('templates')">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
            Templates
          </button>
        </div>
      </div>
      <p class="vb3-sub" id="vb3-sub-text">Build pages with raw HTML, auto-wrapped with your global nav &amp; footer. Bootstrap 5 included.</p>
    </div>
    <div class="vb3-header-actions" id="actions-pages">
      <a href="{{ route('admin.navigation.index') }}" class="btn-secondary">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="21" x2="9" y2="9"/></svg>
        Edit Nav &amp; Footer
      </a>
      <button class="btn-secondary" onclick="syncAllPages()">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg>
        Sync All Files
      </button>
      <a href="{{ route('admin.vbuilder3.page.new') }}" class="btn-primary">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        New Page
      </a>
    </div>
    <div class="vb3-header-actions" id="actions-templates" style="display:none">
      <button class="btn-primary" onclick="openCreateModal()">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Create Template
      </button>
    </div>
  </div>

  {{-- PAGES GRID --}}
  <section id="sec-pages" class="pages-section">
    @if($pages->isEmpty())
      <div class="empty-state">
        <div class="empty-icon">
          <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg>
        </div>
        <h3>No pages yet</h3>
        <p>Create your first page to get started with the Visual Builder.</p>
        <a href="{{ route('admin.vbuilder3.page.new') }}" class="btn-primary">+ New Page</a>
      </div>
    @else
      <div class="pages-grid">
        @foreach($pages as $page)
        <div class="page-card {{ $page->is_home ? 'is-home' : '' }} {{ $page->status === 'published' ? 'is-published' : 'is-draft' }}" id="page-card-{{ $page->id }}">

          {{-- Preview mockup strip --}}
          <div class="card-preview">
            <div class="preview-bar">
              <span></span><span></span><span></span>
              <div class="preview-url-bar">/{{ $page->slug }}</div>
            </div>
            <div class="preview-body">
              <div class="preview-nav-strip">
                <div class="preview-nav-dot" style="width:22px"></div>
                <div class="preview-nav-dot" style="width:14px"></div>
                <div class="preview-nav-dot" style="width:18px"></div>
                <div class="preview-nav-dot" style="width:14px"></div>
              </div>
              <div class="preview-hero"></div>
              <div class="preview-lines">
                <div class="preview-line" style="width:70%"></div>
                <div class="preview-line" style="width:50%"></div>
              </div>
              <div style="display:flex;gap:6px;margin-top:6px">
                <div class="preview-block"></div>
                <div class="preview-block preview-block-alt"></div>
              </div>
            </div>
            {{-- Status + Home badges --}}
            <div class="card-badges">
              @if($page->is_home)
              <div class="badge-home">
                <svg width="9" height="9" viewBox="0 0 24 24" fill="currentColor"><path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/></svg>
                Home
              </div>
              @endif
              <div class="badge-status {{ $page->status === 'published' ? 'badge-pub' : 'badge-draft' }}">
                <span class="badge-dot"></span>
                {{ $page->status === 'published' ? 'Live' : 'Draft' }}
              </div>
            </div>
          </div>

          {{-- Card body --}}
          <div class="card-body">
            <div class="card-icon">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg>
            </div>
            <div class="card-info">
              <h3 class="page-name">{{ $page->title ?: '(Untitled)' }}</h3>
              <div class="card-meta-row">
                <span class="meta-slug">/{{ $page->slug }}</span>
                <span class="meta-dot">·</span>
                <span class="meta-time">{{ $page->updated_at->diffForHumans() }}</span>
              </div>
            </div>
          </div>

          {{-- 2×2 Action Grid --}}
          <div class="card-actions">
            <a href="{{ route('admin.vbuilder3.page.edit', $page->slug) }}" class="btn-action btn-edit">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
              Edit
            </a>
            <a href="/{{ $page->slug }}" target="_blank" class="btn-action btn-view">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
              View Live
            </a>
            @if(!$page->is_home)
            <button onclick="setHome({{ $page->id }})" class="btn-action btn-home">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
              Set Home
            </button>
            @else
            <div class="btn-action btn-home-active">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/></svg>
              Home Page
            </div>
            @endif
            <button onclick="deletePage({{ $page->id }}, '{{ addslashes($page->title) }}')" class="btn-action btn-delete">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
              Delete
            </button>
          </div>
        </div>
        @endforeach
      </div>
    @endif
  </section>

  {{-- TEMPLATES GRID --}}
  <section id="sec-templates" class="components-section" style="display:none">
    <div class="components-grid">
      @forelse($components ?? [] as $comp)
        @php
           $_c = $comp instanceof \Illuminate\Support\Collection ? $comp->first() : (is_array($comp) && isset($comp[0]) ? $comp[0] : $comp);
           $colors = ['var(--accent)','var(--accent-l)','#06b6d4','var(--green)','var(--amber)','var(--red)'];
           $color  = $colors[crc32(data_get($_c,'category','')) % count($colors)];
        @endphp
        <div class="comp-card" id="comp-{{ data_get($_c, 'id') }}">
          <div class="comp-preview" style="--accent:{{ $color }}">
            <div class="comp-preview-code">
              <span class="code-line" style="width:55%"></span>
              <span class="code-line" style="width:80%;background:{{ $color }}33"></span>
              <span class="code-line" style="width:40%"></span>
              <span class="code-line" style="width:70%;background:{{ $color }}22"></span>
              <span class="code-line" style="width:35%"></span>
            </div>
            <div class="comp-glow" style="background:{{ $color }}"></div>
          </div>
          <div class="comp-card-body">
            <span class="comp-category" style="--cat-color:{{ $color }}">{{ data_get($_c, 'category') }}</span>
            <h3 class="comp-name">{{ data_get($_c, 'name') }}</h3>
          </div>
          <div class="comp-card-actions">
            <button class="comp-btn comp-btn-edit" onclick="editComponent({{ json_encode($_c) }})">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
              Edit
            </button>
            <button class="comp-btn comp-btn-delete" onclick="deleteComponent({{ data_get($_c, 'id') }}, '{{ addslashes(data_get($_c, 'name', '')) }}')">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
              Delete
            </button>
          </div>
        </div>
      @empty
        <div class="empty-state" style="grid-column:1/-1">
          <div class="empty-icon">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 16V8l-9-5.14L3 8v8l9 5.14L21 16z"/></svg>
          </div>
          <h3>Empty Templates Library</h3>
          <p>Start by creating reusable snippets for your pages.</p>
          <button class="btn-primary" onclick="openCreateModal()">+ Create Template</button>
        </div>
      @endforelse
    </div>
  </section>

  {{-- EDITOR MODAL --}}
  <div id="comp-modal" class="modal-backdrop">
    <div class="modal-content">
      <div class="modal-header">
        <h2 id="modal-title">New Template</h2>
        <button class="btn-close" onclick="closeModal()">✕</button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="comp-id">
        <div class="form-row">
          <div class="form-group">
            <label>Template Name</label>
            <input type="text" id="comp-name" placeholder="e.g. Hero Section">
          </div>
          <div class="form-group">
            <label>Category</label>
            <input type="text" id="comp-category" list="categories" placeholder="e.g. Sections, Buttons...">
            <datalist id="categories">
              @if(isset($components))
                @foreach($components->pluck('category')->unique() as $cat)
                  <option value="{{ $cat }}">
                @endforeach
              @endif
            </datalist>
          </div>
        </div>

        <div class="editor-tabs">
          <button class="et-tab active" onclick="setEditorTab('html')">HTML</button>
          <button class="et-tab" onclick="setEditorTab('css')">CSS</button>
          <button class="et-tab" onclick="setEditorTab('js')">JS</button>
        </div>

        <div class="editors-wrap">
          <div id="wrapper-html" class="editor-pane active">
             <textarea id="comp-html" spellcheck="false" placeholder="<div>Your HTML here...</div>"></textarea>
          </div>
          <div id="wrapper-css" class="editor-pane">
             <textarea id="comp-css" spellcheck="false" placeholder="/* Custom styles */"></textarea>
          </div>
          <div id="wrapper-js" class="editor-pane">
             <textarea id="comp-js" spellcheck="false" placeholder="// Custom scripts"></textarea>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn-secondary" onclick="closeModal()">Cancel</button>
        <button class="btn-primary" id="btn-save-comp" onclick="saveComponent()">Save Template</button>
      </div>
    </div>
  </div>

  <div id="toast" class="toast-msg"></div>
</div>
@endsection

@push('styles')
<style>
/* ── Root ── */
#v3-pages-root {
  max-width: 1280px;
  margin: 0 auto;
  padding: 32px 28px;
  font-family: 'Inter', system-ui, sans-serif;
}

/* ── Header ── */
.vb3-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 20px;
  margin-bottom: 36px;
  flex-wrap: wrap;
}
.vb3-title {
  display: flex;
  align-items: center;
  gap: 12px;
  font-size: 22px;
  font-weight: 800;
  color: var(--text, var(--text));
  margin: 0 0 4px;
  letter-spacing: -0.3px;
}
.vb3-title svg { color: var(--accent); flex-shrink: 0; }
.vb3-sub {
  font-size: 13px;
  color: var(--text-3, var(--text-3));
  margin: 0;
}

/* ── Tabs ── */
.vb3-tabs {
  display: inline-flex;
  background: rgba(0,0,0,0.25);
  border: 1px solid var(--border, var(--border));
  border-radius: 10px;
  padding: 4px;
  gap: 3px;
}
.vb3-tab {
  display: flex; align-items: center; gap: 7px;
  padding: 7px 16px;
  background: transparent;
  color: var(--text-3, var(--text-3));
  border: none; border-radius: 8px;
  font-size: 13px; font-weight: 600;
  cursor: pointer; transition: all 0.2s;
  letter-spacing: -0.1px;
}
.vb3-tab:hover { background: rgba(255,255,255,0.06); color: var(--text, var(--text)); }
.vb3-tab.active {
  background: linear-gradient(135deg,var(--accent),var(--accent-l));
  color: #fff;
  box-shadow: 0 4px 14px rgba(var(--accent-rgb),0.35);
}

/* ── Action Buttons ── */
.vb3-header-actions { display: flex; gap: 10px; align-items: center; flex-shrink: 0; }
.btn-primary, .btn-secondary {
  display: inline-flex; align-items: center; gap: 7px;
  padding: 9px 18px; border-radius: 9px;
  font-size: 13px; font-weight: 600;
  text-decoration: none; border: none; cursor: pointer;
  transition: all 0.2s; white-space: nowrap;
}
.btn-primary  { background: linear-gradient(135deg,var(--accent),var(--accent-l)); color:#fff; box-shadow:0 4px 14px rgba(var(--accent-rgb),.3); }
.btn-secondary { background: rgba(255,255,255,.05); border: 1px solid var(--border,var(--border)); color: var(--text-2,var(--text-3)); }
.btn-primary:hover  { opacity:.9; transform:translateY(-1px); box-shadow:0 6px 20px rgba(var(--accent-rgb),.4); }
.btn-secondary:hover { background: rgba(255,255,255,.09); color: var(--text,var(--text)); }

/* ── Empty state ── */
.empty-state {
  text-align: center; padding: 80px 20px;
  color: var(--text-3, var(--text-3));
}
.empty-icon {
  width: 72px; height: 72px; margin: 0 auto 20px;
  background: rgba(var(--accent-rgb),.1); border-radius: 20px;
  display: flex; align-items: center; justify-content: center;
  color: var(--accent);
}
.empty-state h3 { font-size: 18px; font-weight: 700; color: var(--text,var(--text)); margin: 0 0 8px; }
.empty-state p  { font-size: 14px; margin: 0 0 24px; }

/* ─────────────────────────────────────────────
   PAGE CARDS — Enhanced 2×2 Grid UI
───────────────────────────────────────────── */
.pages-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
  gap: 18px;
}
.page-card {
  background: var(--surface, var(--surface));
  border: 1px solid rgba(255,255,255,.07);
  border-radius: 16px;
  overflow: hidden;
  transition: transform .3s cubic-bezier(.16,1,.3,1), box-shadow .3s, border-color .3s;
  position: relative;
  display: flex; flex-direction: column;
  box-shadow: 0 2px 12px rgba(0,0,0,.4);
}
.page-card::after {
  content: '';
  position: absolute; inset: 0;
  border-radius: 16px;
  background: radial-gradient(ellipse at 50% 0%, rgba(var(--accent-rgb),.06) 0%, transparent 70%);
  pointer-events: none; z-index: 0;
  opacity: 0; transition: opacity .3s;
}
.page-card:hover::after { opacity: 1; }
.page-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 20px 50px rgba(0,0,0,.6), 0 0 0 1px rgba(var(--accent-rgb),.18);
  border-color: rgba(var(--accent-rgb),.35);
}
.page-card.is-home {
  border-color: rgba(var(--accent-rgb),.5);
  box-shadow: 0 2px 20px rgba(var(--accent-rgb),.1);
}
.page-card.is-home::before {
  content: '';
  position: absolute; top: 0; left: 0; right: 0; height: 2px;
  background: linear-gradient(90deg,var(--accent),var(--accent-l),var(--accent-l));
  z-index: 2;
}
.page-card.is-draft { opacity: .85; }
.page-card.is-draft:hover { opacity: 1; }

/* ── Enhanced Preview mockup ── */
.card-preview {
  position: relative;
  height: 128px;
  background: linear-gradient(160deg, #080d14 0%, #10192a 100%);
  border-bottom: 1px solid rgba(255,255,255,.06);
  overflow: hidden;
  flex-shrink: 0;
}
/* Ambient glow inside preview for home pages */
.page-card.is-home .card-preview::after {
  content: '';
  position: absolute; bottom: -30px; left: 50%; transform: translateX(-50%);
  width: 120px; height: 60px;
  background: rgba(var(--accent-rgb),.18);
  filter: blur(24px);
  border-radius: 50%;
  pointer-events: none;
}
.preview-bar {
  display: flex; gap: 5px; align-items: center;
  padding: 6px 10px;
  border-bottom: 1px solid rgba(255,255,255,.05);
  background: rgba(0,0,0,.25);
}
.preview-bar span {
  width: 7px; height: 7px; border-radius: 50%; flex-shrink: 0;
}
.preview-bar span:nth-child(1) { background: #ff605c55; }
.preview-bar span:nth-child(2) { background: #ffbd4455; }
.preview-bar span:nth-child(3) { background: #00ca4e55; }
.preview-url-bar {
  flex: 1; margin-left: 6px;
  background: rgba(255,255,255,.05);
  border-radius: 4px;
  height: 14px; line-height: 14px;
  font-size: 8.5px; font-family: monospace;
  color: rgba(255,255,255,.18);
  padding: 0 7px;
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.preview-body {
  padding: 8px 12px 6px;
  display: flex; flex-direction: column; gap: 6px;
}
.preview-nav-strip {
  height: 20px; width: 100%;
  border-radius: 4px;
  background: rgba(255,255,255,.05);
  display: flex; align-items: center; gap: 4px;
  padding: 0 6px;
  margin-bottom: 2px;
}
.preview-nav-dot {
  height: 5px; border-radius: 3px; flex-shrink: 0;
  background: rgba(255,255,255,.1);
}
.preview-hero {
  height: 26px; width: 100%;
  border-radius: 4px;
  background: linear-gradient(90deg, rgba(var(--accent-rgb),.2) 0%, rgba(139,92,246,.1) 100%);
}
.preview-lines { display: flex; flex-direction: column; gap: 5px; }
.preview-line { height: 6px; border-radius: 3px; background: rgba(255,255,255,.08); }
.preview-block {
  height: 22px; width: 50px; border-radius: 4px;
  background: rgba(var(--accent-rgb),.2); flex-shrink: 0;
}
.preview-block-alt {
  flex: 1; width: auto;
  background: rgba(255,255,255,.05);
}

/* ── Badges (Home + Status) ── */
.card-badges {
  position: absolute; top: 8px; right: 8px;
  display: flex; flex-direction: column; align-items: flex-end; gap: 5px;
  z-index: 3;
}
.badge-home {
  background: linear-gradient(135deg,var(--accent),var(--accent-l));
  color: #fff; font-size: 10px; font-weight: 700;
  padding: 3px 9px; border-radius: 20px;
  display: flex; align-items: center; gap: 4px;
  box-shadow: 0 4px 12px rgba(var(--accent-rgb),.4);
  letter-spacing: .2px;
}
.badge-status {
  display: flex; align-items: center; gap: 5px;
  font-size: 9.5px; font-weight: 700;
  padding: 2px 8px; border-radius: 20px;
  letter-spacing: .3px; text-transform: uppercase;
}
.badge-pub  { background: rgba(var(--green-rgb),.15); color: var(--green); border: 1px solid rgba(var(--green-rgb),.2); }
.badge-draft { background: rgba(100,116,139,.15); color: var(--text-3); border: 1px solid rgba(100,116,139,.2); }
.badge-dot {
  width: 5px; height: 5px; border-radius: 50%; flex-shrink: 0;
}
.badge-pub  .badge-dot { background: var(--green); box-shadow: 0 0 5px var(--green)80; animation: pulse-dot 2s ease-in-out infinite; }
.badge-draft .badge-dot { background: var(--text-3); }
@keyframes pulse-dot {
  0%,100% { opacity: 1; } 50% { opacity: .4; }
}

/* ── Card body ── */
.card-body {
  display: flex; align-items: center; gap: 11px;
  padding: 13px 14px 10px;
  position: relative; z-index: 1;
}
.card-icon {
  width: 36px; height: 36px; flex-shrink: 0;
  background: linear-gradient(135deg, rgba(var(--accent-rgb),.2), rgba(139,92,246,.12));
  border: 1px solid rgba(var(--accent-rgb),.22);
  border-radius: 10px;
  display: flex; align-items: center; justify-content: center;
  color: var(--accent);
  box-shadow: 0 0 12px rgba(var(--accent-rgb),.12) inset;
}
.card-info { flex: 1; min-width: 0; }
.page-name {
  font-size: 13.5px; font-weight: 700;
  color: var(--text, var(--text));
  margin: 0 0 4px;
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
  letter-spacing: -0.2px;
}
.card-meta-row {
  display: flex; align-items: center; gap: 5px;
  flex-wrap: nowrap; overflow: hidden;
}
.meta-slug {
  font-size: 10px; font-family: 'Fira Code', 'Courier New', monospace;
  color: rgba(var(--accent-rgb),.6);
  background: rgba(var(--accent-rgb),.08);
  padding: 1px 6px; border-radius: 4px;
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
  max-width: 120px;
  border: 1px solid rgba(var(--accent-rgb),.12);
}
.meta-dot { color: rgba(255,255,255,.15); font-size: 10px; flex-shrink: 0; }
.meta-time {
  font-size: 10px; color: rgba(255,255,255,.18);
  white-space: nowrap; flex-shrink: 0;
}

/* ── 2×2 Card action grid ── */
.card-actions {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1px;
  padding: 0;
  border-top: 1px solid rgba(255,255,255,.05);
  background: rgba(255,255,255,.04);
  overflow: hidden;
  position: relative; z-index: 1;
}
.btn-action {
  display: flex; align-items: center; justify-content: center; gap: 5px;
  padding: 9px 6px;
  font-size: 11.5px; font-weight: 600;
  text-decoration: none; border: none; cursor: pointer;
  transition: background .15s, color .15s;
  letter-spacing: -0.1px;
  background: rgba(255,255,255,.01);
  color: rgba(255,255,255,.28);
  white-space: nowrap;
}
.btn-action svg { flex-shrink: 0; transition: transform .2s; }
.btn-action:hover { transform: none; }
.btn-action:hover svg { transform: scale(1.2); }

.btn-edit:hover   { background: rgba(var(--accent-rgb),.15); color: var(--text-3); }
.btn-view:hover   { background: rgba(var(--green-rgb),.12);  color: var(--green); }
.btn-home:hover   { background: rgba(var(--amber-rgb),.12);  color: var(--amber); }
.btn-delete:hover { background: rgba(var(--red-rgb),.12);   color: var(--red); }

.btn-home-active {
  color: rgba(var(--accent-rgb),.5) !important;
  background: rgba(var(--accent-rgb),.06) !important;
  cursor: default; pointer-events: none;
}
.page-card:hover .card-actions { background: rgba(255,255,255,.05); }

/* Mobile: stack to 1 column below 360px */
@media (max-width: 360px) {
  .card-actions { grid-template-columns: 1fr; }
  .btn-action { padding: 10px; justify-content: flex-start; padding-left: 16px; }
}

/* ─────────────────────────────────────────────
   TEMPLATE CARDS
───────────────────────────────────────────── */
.components-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(270px, 1fr));
  gap: 20px;
}
.comp-card {
  background: var(--surface,var(--surface));
  border: 1px solid var(--border,var(--border));
  border-radius: 16px;
  overflow: hidden;
  display: flex; flex-direction: column;
  transition: transform .25s cubic-bezier(.16,1,.3,1), box-shadow .25s, border-color .25s;
}
.comp-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 20px 50px rgba(0,0,0,.5);
  border-color: rgba(var(--accent-rgb),.3);
}
/* Code preview area */
.comp-preview {
  position: relative;
  height: 108px;
  background: linear-gradient(160deg,var(--bg),var(--surface));
  border-bottom: 1px solid rgba(255,255,255,.05);
  overflow: hidden;
  padding: 16px;
  display: flex; flex-direction: column; gap: 8px;
}
.comp-preview-code { display: flex; flex-direction: column; gap: 7px; flex: 1; }
.code-line {
  height: 7px; border-radius: 3px;
  background: rgba(255,255,255,.1);
  display: block;
}
.comp-glow {
  position: absolute; bottom: -20px; right: -20px;
  width: 80px; height: 80px;
  border-radius: 50%;
  opacity: .12;
  filter: blur(20px);
}
/* Card body */
.comp-card-body { padding: 16px; flex: 1; }
.comp-category {
  display: inline-block;
  font-size: 10px; font-weight: 700;
  background: color-mix(in srgb, var(--cat-color,var(--accent)) 15%, transparent);
  color: var(--cat-color,var(--accent));
  padding: 3px 9px; border-radius: 20px;
  text-transform: uppercase; letter-spacing: .6px;
  margin-bottom: 9px;
}
.comp-name {
  font-size: 15px; font-weight: 700;
  color: var(--text,var(--text)); margin: 0;
  letter-spacing: -0.2px;
}
/* Comp actions */
.comp-card-actions {
  display: grid; grid-template-columns: 1fr 1fr;
  border-top: 1px solid rgba(255,255,255,.05);
}
.comp-btn {
  display: flex; align-items: center; justify-content: center; gap: 6px;
  padding: 11px 8px; font-size: 12px; font-weight: 600;
  border: none; cursor: pointer; transition: all .15s;
  color: var(--text-2,var(--text-3));
  background: transparent;
  letter-spacing: -0.1px;
}
.comp-btn + .comp-btn { border-left: 1px solid rgba(255,255,255,.05); }
.comp-btn-edit:hover   { background: rgba(var(--accent-rgb),.12); color: var(--accent); }
.comp-btn-delete:hover { background: rgba(var(--red-rgb),.1);   color: var(--red); }

/* ── Modal ── */
.modal-backdrop { display:none; position:fixed; inset:0; background:rgba(0,0,0,.75); backdrop-filter:blur(8px); z-index:9999; align-items:center; justify-content:center; padding:20px; }
.modal-backdrop.open { display:flex; }
.modal-content { background:var(--surface,var(--surface)); border:1px solid var(--border,var(--border)); width:100%; max-width:920px; border-radius:18px; box-shadow:0 32px 64px -16px rgba(0,0,0,.7); overflow:hidden; display:flex; flex-direction:column; max-height:92vh; }
.modal-header { padding:20px 26px; border-bottom:1px solid rgba(255,255,255,.06); display:flex; justify-content:space-between; align-items:center; }
.modal-header h2 { margin:0; font-size:1.2rem; font-weight:800; color:var(--text,var(--text)); letter-spacing:-0.3px; }
.btn-close { background:rgba(255,255,255,.05); border:1px solid rgba(255,255,255,.08); border-radius:8px; width:32px; height:32px; display:flex; align-items:center; justify-content:center; color:var(--text-3,var(--text-3)); font-size:16px; cursor:pointer; transition:.2s; }
.btn-close:hover { background:rgba(var(--red-rgb),.15); color:var(--red); border-color:rgba(var(--red-rgb),.3); }
.modal-body { padding:24px 26px; overflow-y:auto; flex:1; }
.form-row { display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-bottom:22px; }
.form-group label { display:block; font-size:12px; font-weight:700; color:var(--text-3,var(--text-3)); margin-bottom:7px; text-transform:uppercase; letter-spacing:.5px; }
.form-group input { width:100%; padding:10px 14px; background:rgba(255,255,255,.04); border:1px solid rgba(255,255,255,.08); border-radius:9px; color:var(--text,var(--text)); font-size:14px; outline:none; transition:.2s; }
.form-group input:focus { border-color:var(--accent); box-shadow:0 0 0 3px rgba(var(--accent-rgb),.15); }
.editor-tabs { display:flex; gap:6px; margin-bottom:12px; }
.et-tab { background:rgba(255,255,255,.04); border:1px solid rgba(255,255,255,.07); color:var(--text-3,var(--text-3)); font-weight:700; padding:6px 18px; cursor:pointer; border-radius:8px; font-size:12px; text-transform:uppercase; letter-spacing:.4px; transition:.2s; }
.et-tab.active { background:linear-gradient(135deg,var(--accent),var(--accent-l)); color:#fff; border-color:transparent; box-shadow:0 4px 12px rgba(var(--accent-rgb),.3); }
.editors-wrap { height:400px; background:var(--bg); border:1px solid rgba(255,255,255,.07); border-radius:10px; overflow:hidden; }
.editor-pane { display:none; height:100%; }
.editor-pane.active { display:block; }
.editor-pane textarea { width:100%; height:100%; background:transparent; border:none; color:var(--text); font-family:'Fira Code',monospace; font-size:13px; padding:18px; resize:none; outline:none; line-height:1.65; }
.modal-footer { padding:18px 26px; border-top:1px solid rgba(255,255,255,.06); display:flex; justify-content:flex-end; gap:12px; }

/* ── Toast (legacy fallback, cmsToast is primary) ── */
.toast-msg { position:fixed; bottom:24px; right:24px; padding:12px 20px; border-radius:10px; font-size:14px; font-weight:600; color:#fff; opacity:0; pointer-events:none; transition:opacity .3s; z-index:10000; }
.toast-msg.show { opacity:1; }
.toast-msg.success { background:var(--green); }
.toast-msg.error   { background:var(--red); }
</style>
@endpush

@push('scripts')
<script>
const CSRF = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

function setHome(id) {
  window.cmsConfirm('Set Home Page', 'Are you sure you want to set this as the home page?', 'Set Home').then((ok) => {
    if (!ok) return;
    const url = '{{ route("admin.vbuilder3.page.set-home", ["id" => "__ID__"]) }}'.replace('__ID__', id);
    fetch(url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
      body: JSON.stringify({ _token: CSRF }),
    })
    .then(r => r.json())
    .then(d => {
      if (d.success) { window.cmsToast('Home page updated!', 'success'); setTimeout(() => location.reload(), 1000); }
      else window.cmsToast(d.message || 'Error', 'error');
    })
    .catch(() => window.cmsToast('Network error', 'error'));
  });
}

function deletePage(id, title) {
  window.cmsConfirm('Delete Page', `Are you sure you want to delete the page "${title}"? This cannot be undone.`, 'Delete Page').then((ok) => {
    if (!ok) return;
    const url = '{{ route("admin.vbuilder3.page.destroy", ["id" => "__ID__"]) }}'.replace('__ID__', id);
    fetch(url, {
      method: 'DELETE',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
      body: JSON.stringify({ _token: CSRF }),
    })
    .then(r => r.json())
    .then(d => {
      if (d.success) {
        document.getElementById('page-card-' + id)?.remove();
        window.cmsToast('Page deleted', 'success');
      } else window.cmsToast(d.message || 'Error', 'error');
    })
    .catch(() => window.cmsToast('Network error', 'error'));
  });
}

function syncAllPages() {
  window.cmsConfirm('Sync All Pages & Templates', 'This will rewrite all files to their correct folders. Proceed?', 'Sync All').then((ok) => {
    if (!ok) return;
    window.cmsToast('Syncing all files, please wait...', 'info');
    fetch('{{ route("admin.vbuilder3.page.sync-all") }}', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
      body: JSON.stringify({ _token: CSRF }),
    })
    .then(r => r.json())
    .then(d => {
      if (d.success) {
        window.cmsToast(d.message, 'success');
      } else {
        window.cmsToast(d.message || 'Error', 'error');
      }
    })
    .catch(() => window.cmsToast('Network error during sync', 'error'));
  });
}


function switchTab(tab) {
  document.getElementById('tabbtn-pages').classList.toggle('active', tab === 'pages');
  document.getElementById('tabbtn-templates').classList.toggle('active', tab === 'templates');
  
  document.getElementById('sec-pages').style.display = tab === 'pages' ? 'block' : 'none';
  document.getElementById('sec-templates').style.display = tab === 'templates' ? 'block' : 'none';
  
  document.getElementById('actions-pages').style.display = tab === 'pages' ? 'flex' : 'none';
  document.getElementById('actions-templates').style.display = tab === 'templates' ? 'flex' : 'none';
  
  document.getElementById('vb3-sub-text').innerText = tab === 'pages' 
    ? 'Build pages with raw HTML, auto-wrapped with your global nav & footer. Bootstrap 5 included.'
    : 'Pre-defined HTML/CSS/JS snippets for your Visual Builder V3 pages. Totally separate from legacy builders.';
}

let editors = {
  html: document.getElementById('comp-html'),
  css:  document.getElementById('comp-css'),
  js:   document.getElementById('comp-js')
};

function setEditorTab(tab) {
  document.querySelectorAll('.et-tab').forEach(t => t.classList.toggle('active', t.innerText.toLowerCase() === tab));
  document.querySelectorAll('.editor-pane').forEach(p => p.classList.toggle('active', p.id === 'wrapper-' + tab));
}

function openCreateModal() {
  document.getElementById('comp-id').value = '';
  document.getElementById('comp-name').value = '';
  document.getElementById('comp-category').value = '';
  if(editors.html) editors.html.value = '';
  if(editors.css) editors.css.value = '';
  if(editors.js) editors.js.value = '';
  document.getElementById('modal-title').innerText = 'New Template';
  document.getElementById('comp-modal').classList.add('open');
  setEditorTab('html');
}

function editComponent(comp) {
  document.getElementById('comp-id').value = comp.id;
  document.getElementById('comp-name').value = comp.name;
  document.getElementById('comp-category').value = comp.category;
  if(editors.html) editors.html.value = comp.base_html || '';
  if(editors.css) editors.css.value = comp.base_css || '';
  if(editors.js) editors.js.value = comp.base_js || '';
  document.getElementById('modal-title').innerText = 'Edit Template';
  document.getElementById('comp-modal').classList.add('open');
  setEditorTab('html');
}

function closeModal() {
  document.getElementById('comp-modal').classList.remove('open');
}

async function saveComponent() {
  const btn = document.getElementById('btn-save-comp');
  const id = document.getElementById('comp-id').value;
  const name = document.getElementById('comp-name').value;
  const category = document.getElementById('comp-category').value;
  
  if(!name || !category) return alert('Name and Category are required');
  
  btn.disabled = true;
  btn.innerText = 'Saving...';

  const prefix = '{{ env("ADMIN_PREFIX", "admin") }}';
  const url = id ? `/${prefix}/visual-builder-v3/components/${id}` : `/${prefix}/visual-builder-v3/components`;
  
  try {
    const response = await fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': CSRF,
        'Accept': 'application/json'
      },
      body: JSON.stringify({
        name, category, 
        base_html: editors.html ? editors.html.value : '',
        base_css: editors.css ? editors.css.value : '',
        base_js: editors.js ? editors.js.value : ''
      })
    });
    
    if (response.status === 419) {
      window.cmsToast('Session expired. Please refresh the page.', 'error');
      setTimeout(() => location.reload(), 2000);
      return;
    }

    const rawText = await response.text();
    let data;
    try {
      data = JSON.parse(rawText);
    } catch(err) {
      console.error("RAW HTTP RESPONSE:", rawText);
      alert("Raw error from server. \nCheck console. \nStarts with: " + rawText.substring(0, 100));
      return window.cmsToast('Invalid server response (Format)', 'error');
    }

    if(data.success) {
      window.cmsToast(data.message || 'Template saved!', 'success');
      setTimeout(() => location.reload(), 1500);
    } else {
      window.cmsToast(data.message || 'Error saving template', 'error');
      if (!data.message) {
         console.error("No message provided by server in json payload:", data);
         alert("Server returned JSON but no success/message keys. Payload: " + JSON.stringify(data));
      }
    }
  } catch(e) {
    console.error(e);
    alert('Fetch catch block hit: ' + e.message);
    window.cmsToast('Network error: ' + e.message, 'error');
  } finally {
    btn.disabled = false;
    btn.innerText = 'Save Template';
  }
}

function deleteComponent(id, name) {
  window.cmsConfirm('Delete Template', `Are you sure you want to delete the template "${name}"?`, 'Delete Template').then((ok) => {
    if (!ok) return;
    const prefix = '{{ env("ADMIN_PREFIX", "admin") }}';
    fetch(`/${prefix}/visual-builder-v3/components/${id}`, {
      method: 'DELETE',
      headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }
    }).then(async (response) => {
        if (response.status === 419) {
          window.cmsToast('Session expired. Please refresh.', 'error');
          return setTimeout(() => location.reload(), 2000);
        }
        const data = await response.json();
        if(data.success) {
          window.cmsToast('Template deleted', 'success');
          setTimeout(() => location.reload(), 1000);
        } else {
          window.cmsToast(data.message || 'Error deleting template', 'error');
        }
    });
  });
}

// Tab support in textareas
document.querySelectorAll('textarea').forEach(ta => {
  ta.addEventListener('keydown', e => {
    if(e.key === 'Tab') {
      e.preventDefault();
      const start = ta.selectionStart;
      ta.value = ta.value.substring(0, start) + '  ' + ta.value.substring(ta.selectionEnd);
      ta.selectionStart = ta.selectionEnd = start + 2;
    }
  });
});
</script>
@endpush
