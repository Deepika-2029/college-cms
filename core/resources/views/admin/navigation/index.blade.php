@extends('admin.layout')
@section('title', 'Navigation Editor')

@section('content')
<div id="nav-root">

  {{-- ── Header ──────────────────────────────────────────────────────────── --}}
  <div class="nav-header">
    <div>
      <h1 class="nav-title">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="21" x2="9" y2="9"/></svg>
        Navigation Editor
      </h1>
      <p class="nav-sub">Manage nav links and raw HTML/CSS/JS for your navbar and footer. Bootstrap 5 is available everywhere.</p>
    </div>
    <div style="display:flex;gap:10px;align-items:center;">
      <button class="btn-preview" onclick="previewLayout()">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
        Preview
      </button>
      <button class="btn-save-main" id="btn-save" onclick="saveAll()">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13"/><polyline points="7 3 7 8 15 8"/></svg>
        Save All
      </button>
    </div>
  </div>

  {{-- ── Main tabs ────────────────────────────────────────────────────────── --}}
  <div class="main-tabs">
    <button class="main-tab active" id="mt-links" onclick="mainTab('links')">
      <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
      Nav Links
    </button>
    <button class="main-tab" id="mt-nav" onclick="mainTab('nav')">
      <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
      Navbar Code
    </button>
    <button class="main-tab" id="mt-footer" onclick="mainTab('footer')">
      <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="3" y1="15" x2="21" y2="15"/></svg>
      Footer Code
    </button>
    <button class="main-tab" id="mt-branding" onclick="mainTab('branding')">
      <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12h4l3-9 5 18 3-9h5"/></svg>
      Logos & Identity
    </button>
  </div>

  {{-- ══════════════════════════════════════════════════════════════════════ --}}
  {{-- TAB: BRANDING                                                         --}}
  {{-- ══════════════════════════════════════════════════════════════════════ --}}
  <div id="panel-branding" class="panel" style="display:none; padding: 20px; max-width:800px; margin:0 auto;">
    <div style="background:var(--sur-2); border:1px solid var(--bdr); border-radius:8px; padding:20px; box-shadow:var(--sh-sm);">
      <h2 style="font-size:1.1rem; margin-top:0; margin-bottom:15px; color:var(--text);">Nav & Footer Branding</h2>
      <p style="font-size:0.9rem; color:var(--text-m); margin-bottom:20px;">Use these fields to quickly change the text and logos that appear globally in your navigation and footer. Ensure your HTML uses the tokens like <code>[[site_name]]</code> and <code>[[site_logo]]</code> for these to take effect instantly.</p>
      
      <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 20px;">
        <div>
          <label style="display:block; font-size:12px; font-weight:600; margin-bottom:6px; color:var(--text-s);">College / Site Name (Token: <code style="user-select:all">[[site_name]]</code>)</label>
          <input type="text" id="brand_site_name" value="{{ $brand['site_name'] }}" style="width:100%; padding:8px 12px; border:1px solid #d4e4da; border-radius:6px; font-size:14px;">
        </div>
        <div>
          <label style="display:block; font-size:12px; font-weight:600; margin-bottom:6px; color:var(--text-s);">Affiliation / Sub-title (Token: <code style="user-select:all">[[college_name]]</code>)</label>
          <input type="text" id="brand_college_name" value="{{ $brand['college_name'] }}" style="width:100%; padding:8px 12px; border:1px solid #d4e4da; border-radius:6px; font-size:14px;">
        </div>
        <div style="grid-column: span 2;">
          <label style="display:block; font-size:12px; font-weight:600; margin-bottom:6px; color:var(--text-s);">Footer Description / Tagline (Token: <code style="user-select:all">[[site_tagline]]</code>)</label>
          <textarea id="brand_site_tagline" rows="3" style="width:100%; padding:8px 12px; border:1px solid #d4e4da; border-radius:6px; font-size:14px; resize:vertical;">{{ $brand['site_tagline'] }}</textarea>
        </div>
        
        <div>
          <label style="display:block; font-size:12px; font-weight:600; margin-bottom:6px; color:var(--text-s);">Main Logo URL (Token: <code style="user-select:all">[[site_logo]]</code>)</label>
          <div style="display:flex; gap:8px;">
            <input type="text" id="brand_site_logo" value="{{ $brand['site_logo'] }}" style="flex:1; padding:8px 12px; border:1px solid #d4e4da; border-radius:6px; font-size:14px;">
            <button onclick="pickNavMedia('brand_site_logo')" style="background:#f1f5f9; border:1px solid #cbd5e1; border-radius:6px; padding:0 12px; cursor:pointer;" title="Media Manager">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
            </button>
          </div>
        </div>

        <div>
          <label style="display:block; font-size:12px; font-weight:600; margin-bottom:6px; color:var(--text-s);">Secondary Logo URL (Token: <code style="user-select:all">[[university_logo]]</code>)</label>
          <div style="display:flex; gap:8px;">
            <input type="text" id="brand_university_logo" value="{{ $brand['university_logo'] }}" style="flex:1; padding:8px 12px; border:1px solid #d4e4da; border-radius:6px; font-size:14px;">
            <button onclick="pickNavMedia('brand_university_logo')" style="background:#f1f5f9; border:1px solid #cbd5e1; border-radius:6px; padding:0 12px; cursor:pointer;" title="Media Manager">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
            </button>
          </div>
        </div>
      </div>
    </div>

    {{-- NEW: Auto-detected Brand Assets block --}}
    <div id="links-section-assets" class="links-section" style="margin-top:20px; background:var(--sur-2); border:1px solid var(--bdr); border-radius:8px; padding:20px; box-shadow:var(--sh-sm);">
      <div class="links-section-header" style="color:var(--text); margin-top:0px; font-size:1.1rem; margin-bottom: 5px;">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path></svg>
        Auto-detected Logos & Text (Nav & Footer)
      </div>
      <div class="links-info" style="margin-bottom:20px; font-size:0.9rem; color:var(--text-m);">Edit the images and text blocks detected in your Navbar and Footer HTML directly here. Changes save automatically!</div>
      
      <div class="links-section-header" style="font-size:12px; opacity:0.8; margin-top:10px;">Navigation Assets</div>
      <div id="assets-list" class="links-list" style="display:flex; flex-direction:column; gap:8px; margin-bottom: 20px;"></div>

      <div class="links-section-header" style="font-size:12px; opacity:0.8;">Footer Assets</div>
      <div id="assets-list-footer" class="links-list" style="display:flex; flex-direction:column; gap:8px;"></div>
    </div>

  </div>

  {{-- ══════════════════════════════════════════════════════════════════════ --}}
  {{-- TAB: NAV LINKS                                                        --}}
  {{-- ══════════════════════════════════════════════════════════════════════ --}}
  <div id="panel-links" class="panel active">
    <div class="links-toolbar">
      <span class="links-info">
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        Links auto-sync with your HTML. Edit here → HTML updates in real-time.
      </span>
      <div style="display:flex;gap:6px;flex-shrink:0;">
        <button class="btn-sync" onclick="parseNavLinks()" title="Re-parse links from Navbar HTML">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg>
          Sync Nav
        </button>
        <button class="btn-sync btn-sync-footer" onclick="parseFooterLinks()" title="Re-parse links from Footer HTML">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg>
          Sync Footer
        </button>
        <button class="btn-add-link" onclick="addLink()">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
          Add Link
        </button>
      </div>
    </div>

    {{-- Grouped sections --}}
    <div id="links-section-nav" class="links-section">
      <div class="links-section-header nav-header-badge">
        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="3" rx="1"/><path d="M3 10h18"/></svg>
        Navbar Links
        <span class="section-count" id="nav-link-count">0</span>
      </div>
      <div id="links-list" class="links-list"></div>
      <div id="links-empty" class="links-empty">
        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
        <p>No nav links. Paste HTML in <strong>Navbar Code</strong> tab then click <strong>Sync Nav</strong>.</p>
      </div>
    </div>


    <div id="links-section-footer" class="links-section">
      <div class="links-section-header footer-header-badge">
        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="18" width="18" height="3" rx="1"/><path d="M3 14h18"/></svg>
        Footer Links
        <span class="section-count" id="footer-link-count">0</span>
      </div>
      <div id="footer-links-list" class="links-list"></div>
      <div id="footer-links-empty" class="links-empty">
        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
        <p>No footer links. Paste HTML in <strong>Footer Code</strong> tab then click <strong>Sync Footer</strong>.</p>
      </div>
    </div>
  </div>

  {{-- ══════════════════════════════════════════════════════════════════════ --}}
  {{-- TAB: NAVBAR CODE                                                      --}}
  {{-- ══════════════════════════════════════════════════════════════════════ --}}
  <div id="panel-nav" class="panel" style="display:none;">
    <div class="code-panel-wrap">
      <div class="sub-tabs">
        <button class="sub-tab active" onclick="subTab(this,'nav-html')">HTML</button>
        <button class="sub-tab" onclick="subTab(this,'nav-css')">CSS</button>
        <button class="sub-tab" onclick="subTab(this,'nav-js')">JavaScript</button>
      </div>
      <div class="code-editors">
        <div id="ed-nav-html" class="code-editor-wrap active">
          <div class="editor-label">
            <span>Navbar HTML</span>
            <span class="editor-hint">Full <code>&lt;nav&gt;</code> markup. Bootstrap 5 + <code>window.NAV_LINKS</code> available.</span>
          </div>
          <textarea id="nav-html" class="code-area" spellcheck="false" placeholder="<nav class=&quot;navbar navbar-expand-lg navbar-dark bg-dark&quot;>&#10;  ...&#10;</nav>">{{ $navHtml }}</textarea>
        </div>
        <div id="ed-nav-css" class="code-editor-wrap">
          <div class="editor-label">
            <span>Navbar CSS</span>
            <span class="editor-hint">Styles scoped to your navbar. Applied globally via <code>&lt;style&gt;</code>.</span>
          </div>
          <textarea id="nav-css" class="code-area" spellcheck="false" placeholder="/* Navbar styles */">{{ $navCss }}</textarea>
        </div>
        <div id="ed-nav-js" class="code-editor-wrap">
          <div class="editor-label">
            <span>Navbar JavaScript</span>
            <span class="editor-hint">Runs after DOM load. <code>window.NAV_LINKS</code> is available here.</span>
          </div>
          <textarea id="nav-js" class="code-area" spellcheck="false" placeholder="// Navbar JS&#10;// window.NAV_LINKS is an array of {label, url, children:[]} objects">{{ $navJs }}</textarea>
        </div>
      </div>
    </div>
  </div>

  {{-- ══════════════════════════════════════════════════════════════════════ --}}
  {{-- TAB: FOOTER CODE                                                      --}}
  {{-- ══════════════════════════════════════════════════════════════════════ --}}
  <div id="panel-footer" class="panel" style="display:none;">
    <div class="code-panel-wrap">
      <div class="sub-tabs">
        <button class="sub-tab active" onclick="subTab(this,'footer-html')">HTML</button>
        <button class="sub-tab" onclick="subTab(this,'footer-css')">CSS</button>
        <button class="sub-tab" onclick="subTab(this,'footer-js')">JavaScript</button>
      </div>
      <div class="code-editors">
        <div id="ed-footer-html" class="code-editor-wrap active">
          <div class="editor-label">
            <span>Footer HTML</span>
            <span class="editor-hint">Full <code>&lt;footer&gt;</code> markup.</span>
          </div>
          <textarea id="footer-html" class="code-area" spellcheck="false" placeholder="<footer class=&quot;bg-dark text-white py-4&quot;>&#10;  ...&#10;</footer>">{{ $footerHtml }}</textarea>
        </div>
        <div id="ed-footer-css" class="code-editor-wrap">
          <div class="editor-label">
            <span>Footer CSS</span>
            <span class="editor-hint">Styles applied globally.</span>
          </div>
          <textarea id="footer-css" class="code-area" spellcheck="false" placeholder="/* Footer styles */">{{ $footerCss }}</textarea>
        </div>
        <div id="ed-footer-js" class="code-editor-wrap">
          <div class="editor-label">
            <span>Footer JavaScript</span>
            <span class="editor-hint">Runs after DOM load.</span>
          </div>
          <textarea id="footer-js" class="code-area" spellcheck="false" placeholder="// Footer JS">{{ $footerJs }}</textarea>
        </div>
      </div>
    </div>
  </div>

  {{-- Preview modal --}}
  <div id="preview-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.8);z-index:9999;align-items:center;justify-content:center;">
    <div style="width:92vw;height:88vh;background:#fff;border-radius:12px;overflow:hidden;display:flex;flex-direction:column;">
      <div style="display:flex;align-items:center;justify-content:space-between;padding:12px 20px;background:var(--surface);color:var(--text);font-size:14px;font-weight:600;">
        <span>Layout Preview</span>
        <button onclick="closePreview()" style="background:rgba(255,255,255,.1);border:none;color:var(--text);padding:5px 14px;border-radius:6px;cursor:pointer;">✕ Close</button>
      </div>
      <iframe id="preview-iframe" style="flex:1;border:none;width:100%;"></iframe>
    </div>
  </div>

  {{-- Link edit dialog --}}
  <div id="link-dialog" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.7);z-index:9998;align-items:center;justify-content:center;">
    <div class="link-dialog-box">
      <div class="link-dialog-header">
        <span id="link-dialog-title">Edit Link</span>
        <button onclick="closeLinkDialog()" style="background:none;border:none;color:#8b949e;font-size:18px;cursor:pointer;line-height:1;">✕</button>
      </div>
      <div class="link-dialog-body">
        <label class="field-label">Label</label>
        <input id="dlg-label" class="field-input" type="text" placeholder="e.g. About Us">
        <label class="field-label" style="margin-top:14px;">URL</label>
        <input id="dlg-url" class="field-input" type="text" placeholder="e.g. /about or https://...">
      </div>
      <div class="link-dialog-footer">
        <button onclick="closeLinkDialog()" class="btn-dlg-cancel">Cancel</button>
        <button onclick="saveLinkDialog()" class="btn-dlg-save">Save</button>
      </div>
    </div>
  </div>

  <div id="toast" class="toast-msg"></div>

</div>

{{-- Data bridge --}}
<div id="nav-data"
  data-links="{{ json_encode($navLinks) }}"
></div>
@endsection

@push('styles')
<style>
  #nav-root {
    max-width: 1160px;
    margin: 0 auto;
    padding: 24px;
    font-family: 'Inter', system-ui, sans-serif;
  }

  /* Header */
  .nav-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 24px;
  }
  .nav-title {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 22px;
    font-weight: 700;
    color: var(--text, var(--text));
    margin: 0 0 6px;
  }
  .nav-sub {
    font-size: 13px;
    color: var(--text-muted, var(--text-3));
    margin: 0;
    max-width: 540px;
  }

  .btn-save-main, .btn-preview {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 9px 20px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    border: none;
    transition: opacity .18s, transform .1s;
    white-space: nowrap;
  }
  .btn-save-main {
    background: linear-gradient(135deg, var(--accent), var(--accent-l));
    color: #fff;
  }
  .btn-preview {
    background: rgba(255,255,255,.07);
    border: 1px solid var(--border, #334155);
    color: var(--text-muted, var(--text-3));
  }
  .btn-save-main:hover, .btn-preview:hover { opacity: .88; }
  .btn-save-main:disabled { opacity: .5; cursor: default; }

  /* Main tabs */
  .main-tabs {
    display: flex;
    gap: 4px;
    border-bottom: 1px solid var(--border, #334155);
    margin-bottom: 0;
    padding: 0 2px;
  }
  .main-tab {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 10px 18px;
    background: transparent;
    border: none;
    color: var(--text-muted, var(--text-3));
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    border-bottom: 2px solid transparent;
    margin-bottom: -1px;
    transition: .15s;
    font-family: inherit;
  }
  .main-tab:hover { color: var(--text, var(--text)); }
  .main-tab.active { color: var(--accent); border-bottom-color: var(--accent); }

  /* Panels */
  .panel {
    background: var(--surface, var(--surface));
    border: 1px solid var(--border, #334155);
    border-top: none;
    border-radius: 0 0 12px 12px;
    padding: 0;
    overflow: hidden;
  }
  .panel.active { display: block; }

  /* ── LINKS PANEL ── */
  .links-toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 20px;
    border-bottom: 1px solid var(--border, #334155);
    gap: 12px;
    background: var(--bg2, rgba(0,0,0,.15));
  }
  .links-info {
    display: flex;
    align-items: center;
    gap: 7px;
    font-size: 12px;
    color: var(--text-muted, var(--text-3));
    flex: 1;
  }
  .links-info code {
    background: rgba(var(--accent-rgb),.15);
    color: var(--text-3);
    padding: 1px 5px;
    border-radius: 4px;
    font-size: 11px;
  }
  .btn-add-link {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 7px 16px;
    background: rgba(var(--accent-rgb),.15);
    border: 1px solid rgba(var(--accent-rgb),.3);
    color: var(--accent);
    border-radius: 7px;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: background .15s;
    white-space: nowrap;
  }
  .btn-add-link:hover { background: rgba(var(--accent-rgb),.28); }

  /* Sync buttons */
  .btn-sync {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 6px 12px;
    background: rgba(var(--green-rgb),.1);
    border: 1px solid rgba(var(--green-rgb),.25);
    color: var(--green);
    border-radius: 7px;
    font-size: 11px;
    font-weight: 600;
    cursor: pointer;
    transition: background .15s;
    white-space: nowrap;
  }
  .btn-sync:hover { background: rgba(var(--green-rgb),.22); }
  .btn-sync-footer {
    background: rgba(251,146,60,.1);
    border-color: rgba(251,146,60,.25);
    color: #fb923c;
  }
  .btn-sync-footer:hover { background: rgba(251,146,60,.22); }

  /* Section groups */
  .links-section { margin-bottom: 6px; }
  .links-section-header {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 9px 20px;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .06em;
    border-bottom: 1px solid var(--border, #334155);
    border-top: 1px solid var(--border, #334155);
    background: rgba(0,0,0,.18);
  }
  .nav-header-badge    { color: var(--accent); }
  .footer-header-badge { color: #fb923c; }
  .section-count {
    margin-left: auto;
    background: rgba(255,255,255,.07);
    border-radius: 999px;
    padding: 1px 8px;
    font-size: 10px;
    font-weight: 700;
    color: var(--text-muted, var(--text-3));
    min-width: 20px;
    text-align: center;
  }

  /* Source badge on each link */
  .src-badge {
    display: inline-flex;
    align-items: center;
    padding: 1px 7px;
    border-radius: 999px;
    font-size: 10px;
    font-weight: 700;
    margin-left: 6px;
    vertical-align: middle;
  }
  .nav-src    { background: rgba(var(--accent-rgb),.18); color: var(--accent); }
  .footer-src { background: rgba(251,146,60,.15); color: #fb923c; }

  .links-list { padding: 14px 20px; display: flex; flex-direction: column; gap: 10px; }

  .link-item {
    background: var(--bg2, #0f172a);
    border: 1px solid var(--border, #334155);
    border-radius: 10px;
    overflow: hidden;
  }
  .link-row {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 14px;
  }
  .link-drag {
    color: #334155;
    cursor: grab;
    flex-shrink: 0;
    font-size: 16px;
    line-height: 1;
  }
  .link-label-url {
    flex: 1;
    min-width: 0;
  }
  .link-label {
    font-size: 13px;
    font-weight: 600;
    color: var(--text, var(--text));
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }
  .link-url {
    font-size: 11px;
    color: var(--text-muted, var(--text-3));
    font-family: monospace;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }
  .link-actions {
    display: flex;
    gap: 5px;
    flex-shrink: 0;
  }
  .btn-link-action {
    padding: 5px 10px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 600;
    border: none;
    cursor: pointer;
    transition: .15s;
    display: inline-flex;
    align-items: center;
    gap: 4px;
  }
  .btn-link-edit   { background: rgba(var(--accent-rgb),.12); color: var(--accent); }
  .btn-link-sub    { background: rgba(var(--green-rgb),.1);  color: var(--green); }
  .btn-link-del    { background: rgba(var(--red-rgb),.1);   color: var(--red); }
  .btn-link-action:hover { opacity: .75; }

  /* Sub-links */
  .sub-links-list {
    border-top: 1px solid var(--border, #334155);
    padding: 8px 12px 8px 36px;
    display: flex;
    flex-direction: column;
    gap: 6px;
    background: rgba(0,0,0,.15);
  }
  .sub-link-row {
    display: flex;
    align-items: center;
    gap: 8px;
    background: var(--surface, var(--surface));
    border: 1px solid var(--border, #334155);
    border-radius: 7px;
    padding: 7px 10px;
  }
  .sub-link-info { flex:1; min-width:0; }
  .sub-link-label { font-size: 12px; font-weight: 600; color: var(--text, var(--text)); }
  .sub-link-url   { font-size: 11px; color: var(--text-3); font-family: monospace; }
  .add-sub-btn {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 5px 12px;
    font-size: 11px;
    font-weight: 600;
    background: rgba(var(--green-rgb),.1);
    border: 1px dashed rgba(var(--green-rgb),.3);
    color: var(--green);
    border-radius: 6px;
    cursor: pointer;
    margin-top: 4px;
    transition: background .15s;
  }
  .add-sub-btn:hover { background: rgba(var(--green-rgb),.2); }

  .links-empty {
    text-align: center;
    padding: 60px 20px;
    color: var(--text-muted, var(--text-3));
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 10px;
  }
  .links-empty p { margin: 0; font-size: 14px; }

  /* ── CODE PANEL ── */
  .code-panel-wrap { display: flex; flex-direction: column; height: calc(100vh - 220px); min-height: 400px; }

  .sub-tabs {
    display: flex;
    gap: 2px;
    padding: 10px 14px 0;
    border-bottom: 1px solid var(--border, #334155);
    background: var(--bg2, rgba(0,0,0,.12));
    flex-shrink: 0;
  }
  .sub-tab {
    padding: 7px 16px;
    background: transparent;
    border: none;
    color: var(--text-muted, var(--text-3));
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    border-bottom: 2px solid transparent;
    margin-bottom: -1px;
    transition: .15s;
    font-family: inherit;
    border-radius: 6px 6px 0 0;
  }
  .sub-tab:hover { color: var(--text); }
  .sub-tab.active { color: var(--accent); border-bottom-color: var(--accent); }

  .code-editors { flex: 1; overflow: hidden; position: relative; }
  .code-editor-wrap {
    position: absolute;
    inset: 0;
    display: none;
    flex-direction: column;
  }
  .code-editor-wrap.active { display: flex; }

  .editor-label {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 8px 16px;
    font-size: 12px;
    font-weight: 600;
    color: var(--text, var(--text));
    background: rgba(0,0,0,.2);
    border-bottom: 1px solid var(--border, #334155);
    flex-shrink: 0;
  }
  .editor-hint {
    font-size: 11px;
    color: var(--text-3);
    font-weight: 400;
  }
  .editor-hint code {
    background: rgba(var(--accent-rgb),.12);
    color: var(--text-3);
    padding: 1px 4px;
    border-radius: 3px;
    font-size: 10px;
  }

  .code-area {
    flex: 1;
    width: 100%;
    padding: 14px 18px;
    background: var(--bg);
    color: #adbac7;
    border: none;
    font-family: 'JetBrains Mono', 'Fira Code', 'Cascadia Code', monospace;
    font-size: 13px;
    line-height: 1.7;
    resize: none;
    outline: none;
    tab-size: 2;
  }
  .code-area::placeholder { color: #2d3748; }

  /* ── LINK DIALOG ─ */
  .link-dialog-box {
    width: 460px;
    background: var(--surface, var(--surface));
    border: 1px solid var(--border, #334155);
    border-radius: 14px;
    overflow: hidden;
    box-shadow: 0 24px 60px rgba(0,0,0,.6);
  }
  .link-dialog-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 20px;
    border-bottom: 1px solid var(--border, #334155);
    font-size: 14px;
    font-weight: 700;
    color: var(--text, var(--text));
    background: var(--bg2, #0f172a);
  }
  .link-dialog-body { padding: 20px; }
  .link-dialog-footer {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    padding: 14px 20px;
    border-top: 1px solid var(--border, #334155);
  }
  .field-label { display: block; font-size: 12px; font-weight: 600; color: var(--text-muted, var(--text-3)); margin-bottom: 6px; }
  .field-input {
    width: 100%;
    padding: 9px 12px;
    background: var(--bg2, #0f172a);
    border: 1px solid var(--border, #334155);
    border-radius: 7px;
    color: var(--text, var(--text));
    font-size: 13px;
    outline: none;
    box-sizing: border-box;
    transition: border-color .15s;
    font-family: inherit;
  }
  .field-input:focus { border-color: var(--accent); }
  .btn-dlg-cancel, .btn-dlg-save {
    padding: 8px 20px;
    border-radius: 7px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    border: none;
    transition: opacity .15s;
  }
  .btn-dlg-cancel { background: rgba(255,255,255,.06); border: 1px solid var(--border, #334155); color: var(--text-muted, var(--text-3)); }
  .btn-dlg-save   { background: linear-gradient(135deg, var(--accent), var(--accent-l)); color: #fff; }
  .btn-dlg-cancel:hover, .btn-dlg-save:hover { opacity: .85; }

  /* ── TOAST ── */
  .toast-msg {
    position: fixed;
    bottom: 24px;
    right: 24px;
    padding: 12px 20px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    color: #fff;
    opacity: 0;
    pointer-events: none;
    transition: opacity .3s;
    z-index: 20000;
    max-width: 300px;
  }
  .toast-msg.show { opacity: 1; }
  .toast-msg.success { background: #059669; }
  .toast-msg.error   { background: #dc2626; }
  .toast-msg.info    { background: #0284c7; }
</style>
@endpush

@push('scripts')
<script>
'use strict';

/* ── Data ──────────────────────────────────────────────────────────────────── */
const CSRF     = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
const SAVE_URL = '{{ route("admin.navigation.save-layout") }}';
let links = JSON.parse(document.getElementById('nav-data').dataset.links || '[]');

/* ── MAIN TAB SWITCH ────────────────────────────────────────────────────────── */
function mainTab(name) {
  ['links','nav','footer','branding'].forEach(t => {
    const tabEl = document.getElementById('mt-' + t);
    if (tabEl) tabEl.classList.toggle('active', t === name);
    const p = document.getElementById('panel-' + t);
    if (p) p.style.display = t === name ? 'block' : 'none';
    if (p) p.classList.toggle('active', t === name);
  });
  // Auto-parse links when switching to the links tab
  if (name === 'links') {
    const navHtml    = document.getElementById('nav-html').value.trim();
    const footerHtml = document.getElementById('footer-html').value.trim();
    if (navHtml)    parseNavLinks(false);
    if (footerHtml) parseFooterLinks(false);
  } else if (name === 'branding') {
    parseNavAssets();
  }
}

/* ── SUB TAB (inside code panels) ─────────────────────────────────────────── */
function subTab(btn, edId) {
  // scope to parent .code-panel-wrap
  const wrap = btn.closest('.code-panel-wrap');
  wrap.querySelectorAll('.sub-tab').forEach(t => t.classList.remove('active'));
  wrap.querySelectorAll('.code-editor-wrap').forEach(e => e.classList.remove('active'));
  btn.classList.add('active');
  document.getElementById('ed-' + edId).classList.add('active');
}

/* ══════════════════════════════════════════════════════════════════════════════
   LINKS MANAGER
══════════════════════════════════════════════════════════════════════════════ */
let _dlgMode    = 'link'; // 'link' | 'sub' | 'footerLink' | 'footerSub'
let _dlgLinkIdx = -1;
let _dlgSubIdx  = -1;
let footerLinks = []; // separate array for footer links

/* ── Parse links from raw HTML ────────────────────────────────────────────── */
function parseLinksFromHtml(html) {
  const container = document.createElement('div');
  container.innerHTML = html;
  const result = [];
  
  const allLinks = Array.from(container.querySelectorAll('a'));
  const processed = new Set();
  
  for (const a of allLinks) {
    if (processed.has(a)) continue;
    
    let label = (a.innerText || a.textContent || '').replace(/\s+/g, ' ').trim();
    if (!label) label = a.getAttribute('title') || '';
    if (!label) label = a.getAttribute('aria-label') || '';
    if (!label) {
        const img = a.querySelector('img');
        if (img && img.getAttribute('alt')) label = img.getAttribute('alt');
    }
    if (!label) continue;
    
    let subMenu = null;
    const li = a.closest('li');
    if (li) {
        subMenu = li.querySelector('ul');
    }
    
    let children = [];
    if (subMenu) {
        subMenu.querySelectorAll('a').forEach(subA => {
            let subLabel = (subA.innerText || subA.textContent || '').replace(/\s+/g, ' ').trim();
            if (!subLabel) subLabel = subA.getAttribute('title') || subA.getAttribute('aria-label') || '';
            if (subLabel) {
                children.push({ label: subLabel, url: subA.getAttribute('href') || '#' });
                processed.add(subA);
            }
        });
    }
    
    result.push({ label: label, url: a.getAttribute('href') || '#', children: children });
    processed.add(a);
  }
  
  return result;
}

/* ── Auto-detect Images and Text blocks ───────────────────────────────────── */
function parseNavAssets() {
    parseAssets('nav-html', 'assets-list');
    parseAssets('footer-html', 'assets-list-footer');
}

function parseAssets(htmlId, listId) {
  const html = document.getElementById(htmlId).value;
  const container = document.createElement('div');
  container.innerHTML = html;
  
  const list = document.getElementById(listId);
  list.innerHTML = '';
  
  // Find images
  const imgs = container.querySelectorAll('img');
  imgs.forEach((img, idx) => {
     let src = img.getAttribute('src') || '';
     appendAssetRow(listId, 'Image (src)', src, (newVal) => {
         syncAssetToHtml(htmlId, (div) => {
             const allImgs = div.querySelectorAll('img');
             if(allImgs[idx]) allImgs[idx].setAttribute('src', newVal);
         });
     });
  });

  // Find direct text nodes not in links
  let textIndex = 0;
  const els = container.querySelectorAll('*:not(a):not(ul):not(li):not(script):not(style)');
  els.forEach(el => {
     // A leaf node containing only text
     if (el.childNodes.length === 1 && el.childNodes[0].nodeType === 3) {
         const text = el.textContent.trim();
         if (text.length > 1 && text.length < 500) {
             const currentIndex = textIndex++;
             
             // Check if it's a long text (textarea)
             const typeLabel = text.length > 50 ? `Text Area` : `Text (${el.tagName.toLowerCase()})`;
             
             appendAssetRow(listId, typeLabel, text, (newVal) => {
                 syncAssetToHtml(htmlId, (div) => {
                     let c = 0;
                     const innerEls = div.querySelectorAll('*:not(a):not(ul):not(li):not(script):not(style)');
                     innerEls.forEach(innerEl => {
                         if (innerEl.childNodes.length === 1 && innerEl.childNodes[0].nodeType === 3) {
                             const innerText = innerEl.textContent.trim();
                             if (innerText.length > 1 && innerText.length < 500) {
                                 if (c === currentIndex) {
                                     innerEl.textContent = newVal;
                                 }
                                 c++;
                             }
                         }
                     });
                 });
             }, text.length > 50);
         }
     }
  });
  
  if (list.innerHTML === '') {
      list.innerHTML = `<div style="font-size:12px; color:var(--text-m); padding:10px;">No static text or logo placeholders found outside of your links.</div>`;
  }
}

function appendAssetRow(listId, label, value, onChangeCallback, isTextarea = false) {
    const list = document.getElementById(listId);
    if (!list) return;

    const div = document.createElement('div');
    div.className = 'link-row';
    
    let inp;
    if (isTextarea) {
        inp = document.createElement('textarea');
        inp.rows = 3;
        inp.style.fontFamily = 'inherit';
        inp.style.padding = '8px 12px';
        inp.style.border = '1px solid var(--bdr)';
        inp.style.borderRadius = '6px';
        inp.style.color = 'var(--text)';
        inp.style.background = 'var(--sur-2)';
    } else {
        inp = document.createElement('input');
        inp.type = 'text';
    }
    
    inp.id = 'asset_' + Math.random().toString(36).substr(2, 9);
    inp.className = 'link-url';
    inp.style.flex = '1';
    inp.value = value;
    
    inp.addEventListener('change', () => {
        onChangeCallback(inp.value);
        saveAll(); // Auto-save when an asset changes
    });
    
    div.innerHTML = `
      <div style="flex:0 0 120px; font-size:11px; font-weight:600; color:var(--text-m); text-transform:uppercase;">${label}</div>
    `;
    div.appendChild(inp);

    if (label.includes('Image')) {
        const btn = document.createElement('button');
        btn.innerHTML = `<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>`;
        btn.style.cssText = 'background:var(--sur-2); border:1px solid var(--bdr); border-radius:6px; padding:0 12px; height:34px; color:var(--text); cursor:pointer; margin-left:6px;';
        btn.onclick = () => {
            pickNavMedia(inp.id);
        };
        div.appendChild(btn);
    }
    
    list.appendChild(div);
}

function pickNavMedia(inputId) {
    if (window.cmsMediaPicker) {
        window.cmsMediaPicker.open({
            imagesOnly: true,
            title: 'Select Image',
            onSelect: function(media) {
                const inp = document.getElementById(inputId);
                inp.value = media.url;
                inp.dispatchEvent(new Event('change'));
            }
        });
    } else {
        window.open('/admin/media?picker=' + inputId, '_blank', 'width=1000,height=700');
        const check = setInterval(() => {
            const inp = document.getElementById(inputId);
            if (inp && inp.value !== inp.defaultValue) {
                clearInterval(check);
                inp.dispatchEvent(new Event('change'));
            }
        }, 1000);
    }
}

function syncAssetToHtml(htmlId, domModifierFn) {
    // We get latest html just in case
    const ta = document.getElementById(htmlId);
    if (!ta) return;
    const div = document.createElement('div');
    div.innerHTML = ta.value;
    domModifierFn(div);
    ta.value = div.innerHTML;
    toast('Syncing changes globally...', 'success');
}

/* Auto-sync link changes back into the HTML textarea */
function syncLinkToHtml(textareaId, oldLabel, oldUrl, newLabel, newUrl) {
  const ta = document.getElementById(textareaId);
  if (!ta || !ta.value) return;
  const container = document.createElement('div');
  container.innerHTML = ta.value;
  const allAs = container.querySelectorAll('a');
  for (const a of allAs) {
    let label = (a.innerText || a.textContent || '').replace(/\s+/g, ' ').trim();
    if (!label) label = a.getAttribute('title') || a.getAttribute('aria-label') || '';
    const aHref = a.getAttribute('href') || '';
    
    if (label === oldLabel && aHref === oldUrl) {
      if (a.childNodes.length === 1 && a.childNodes[0].nodeType === 3) {
        a.childNodes[0].textContent = newLabel;
      } else {
        let updatedText = false;
        for (let i = 0; i < a.childNodes.length; i++) {
          const child = a.childNodes[i];
          if (child.nodeType === 3 && child.textContent.replace(/\s+/g, ' ').includes(oldLabel)) {
            child.textContent = child.textContent.replace(oldLabel, newLabel);
            updatedText = true;
          }
        }
        if (!updatedText && a.getAttribute('title') === oldLabel) {
          a.setAttribute('title', newLabel);
        } else if (!updatedText && a.getAttribute('aria-label') === oldLabel) {
          a.setAttribute('aria-label', newLabel);
        }
      }
      a.setAttribute('href', newUrl);
      break;
    }
  }
  ta.value = container.innerHTML;
}

function syncDeleteFromHtml(textareaId, label, url) {
  const ta = document.getElementById(textareaId);
  if (!ta || !ta.value) return;
  const container = document.createElement('div');
  container.innerHTML = ta.value;
  let found = false;
  for (const a of container.querySelectorAll('a')) {
    let aLabel = (a.innerText || a.textContent || '').replace(/\s+/g, ' ').trim();
    if (!aLabel) aLabel = a.getAttribute('title') || a.getAttribute('aria-label') || '';
    if (!aLabel) {
        const img = a.querySelector('img');
        if (img && img.getAttribute('alt')) aLabel = img.getAttribute('alt');
    }
    const aHref = a.getAttribute('href') || '';
    if (aLabel === label && aHref === url) {
      const li = a.closest('li');
      if (li) li.remove(); else a.remove();
      found = true;
      break;
    }
  }
  if (found) ta.value = container.innerHTML;
}

function syncAddIntoHtml(textareaId, label, url) {
  const ta = document.getElementById(textareaId);
  if (!ta) return;
  const container = document.createElement('div');
  container.innerHTML = ta.value;
  
  let targetUl = container.querySelector('.navbar-nav, .nav-list, ul');
  if (targetUl) {
      const li = document.createElement('li');
      li.className = ta.value.includes('nav-item') ? 'nav-item' : '';
      li.innerHTML = `<a class="${ta.value.includes('nav-link') ? 'nav-link' : ''}" href="${url}">${label}</a>`;
      targetUl.appendChild(li);
  } else {
      container.innerHTML += ` <a href="${url}">${label}</a>`;
  }
  ta.value = container.innerHTML;
}

function syncAddSubIntoHtml(textareaId, parentLabel, label, url) {
  const ta = document.getElementById(textareaId);
  if (!ta) return;
  const container = document.createElement('div');
  container.innerHTML = ta.value;
  
  for (const a of container.querySelectorAll('a')) {
    let aLabel = (a.innerText || a.textContent || '').replace(/\s+/g, ' ').trim();
    if (!aLabel) aLabel = a.getAttribute('title') || a.getAttribute('aria-label') || '';
    if (!aLabel) {
        const img = a.querySelector('img');
        if (img && img.getAttribute('alt')) aLabel = img.getAttribute('alt');
    }
    if (aLabel === parentLabel) {
       let li = a.closest('li');
       if (li) {
           let ul = li.querySelector('ul');
           if (!ul) {
               ul = document.createElement('ul');
               ul.className = ta.value.includes('dropdown-menu') ? 'dropdown-menu' : 'dropdown';
               li.appendChild(ul);
           }
           let newLi = document.createElement('li');
           newLi.innerHTML = `<a href="${url}">${label}</a>`;
           ul.appendChild(newLi);
       }
       break;
    }
  }
  ta.value = container.innerHTML;
}

/* Called by toolbar buttons */
function parseNavLinks(showToast = true) {
  const html = document.getElementById('nav-html').value;
  links = parseLinksFromHtml(html);
  renderLinks();
  parseNavAssets();
  if (showToast) toast(`Parsed ${links.length} nav link(s) and static assets`, 'success');
}
function parseFooterLinks(showToast = true) {
  const html = document.getElementById('footer-html').value;
  footerLinks = parseLinksFromHtml(html);
  renderFooterLinks();
  if (showToast) toast(`Parsed ${footerLinks.length} footer link(s) from HTML`, 'success');
}

function renderLinks() {
  const list  = document.getElementById('links-list');
  const empty = document.getElementById('links-empty');
  document.getElementById('nav-link-count').textContent = links.length;
  list.innerHTML = '';
  empty.style.display = links.length ? 'none' : 'flex';
  links.forEach((link, li) => renderLinkItem(list, link, li, 'nav'));
}

function renderFooterLinks() {
  const list  = document.getElementById('footer-links-list');
  const empty = document.getElementById('footer-links-empty');
  document.getElementById('footer-link-count').textContent = footerLinks.length;
  list.innerHTML = '';
  empty.style.display = footerLinks.length ? 'none' : 'flex';
  footerLinks.forEach((link, li) => renderLinkItem(list, link, li, 'footer'));
}

function renderLinkItem(container, link, li, section) {
  const item = document.createElement('div');
  item.className = 'link-item';
  const children = link.children || [];
  const subHtml  = children.map((s, si) => `
    <div class="sub-link-row">
      <div class="sub-link-info">
        <div class="sub-link-label">${esc(s.label)}</div>
        <div class="sub-link-url">${esc(s.url)}</div>
      </div>
      <div class="link-actions">
        <button class="btn-link-action btn-link-edit" onclick="editSubLinkOf('${section}',${li},${si})">Edit</button>
        <button class="btn-link-action btn-link-del"  onclick="deleteSubLinkOf('${section}',${li},${si})">✕</button>
      </div>
    </div>
  `).join('');

  const sectionBadge = section === 'footer'
    ? `<span class="src-badge footer-src">footer</span>`
    : `<span class="src-badge nav-src">nav</span>`;

  item.innerHTML = `
    <div class="link-row">
      <span class="link-drag">⠿</span>
      <div class="link-label-url">
        <div class="link-label">${esc(link.label)} ${sectionBadge}</div>
        <div class="link-url">${esc(link.url)}</div>
      </div>
      <div class="link-actions">
        <button class="btn-link-action btn-link-edit" onclick="editLinkOf('${section}',${li})">
          <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
          Edit
        </button>
        <button class="btn-link-action btn-link-sub" onclick="addSubLinkOf('${section}',${li})">
          <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
          Sub-link
        </button>
        <button class="btn-link-action btn-link-del" onclick="deleteLinkOf('${section}',${li})">
          <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
          Delete
        </button>
      </div>
    </div>
    ${children.length > 0 ? `<div class="sub-links-list">${subHtml}<button class="add-sub-btn" onclick="addSubLinkOf('${section}',${li})">+ Add Sub-link</button></div>` : ''}
  `;
  container.appendChild(item);
}

function esc(s) {
  return String(s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

/* ── Section-aware link actions ─────────────────────────────────────────── */
function getLinkArr(section) { return section === 'footer' ? footerLinks : links; }
function getHtmlId(section) { return section === 'footer' ? 'footer-html' : 'nav-html'; }

function editLinkOf(section, li) {
  const arr = getLinkArr(section);
  _dlgMode = section === 'footer' ? 'footerLink' : 'link';
  _dlgLinkIdx = li;
  document.getElementById('link-dialog-title').textContent = 'Edit Link';
  document.getElementById('dlg-label').value = arr[li].label || '';
  document.getElementById('dlg-url').value   = arr[li].url   || '';
  openLinkDialog();
}
function addSubLinkOf(section, li) {
  _dlgMode = section === 'footer' ? 'footerSub' : 'sub';
  _dlgLinkIdx = li;
  _dlgSubIdx  = -1;
  const arr = getLinkArr(section);
  document.getElementById('link-dialog-title').textContent = 'Add Sub-link under "' + arr[li].label + '"';
  document.getElementById('dlg-label').value = '';
  document.getElementById('dlg-url').value   = '';
  openLinkDialog();
}
function editSubLinkOf(section, li, si) {
  _dlgMode = section === 'footer' ? 'footerSub' : 'sub';
  _dlgLinkIdx = li;
  _dlgSubIdx  = si;
  const arr = getLinkArr(section);
  const sub = arr[li].children[si];
  document.getElementById('link-dialog-title').textContent = 'Edit Sub-link';
  document.getElementById('dlg-label').value = sub.label || '';
  document.getElementById('dlg-url').value   = sub.url   || '';
  openLinkDialog();
}
function deleteLinkOf(section, li) {
  const arr    = getLinkArr(section);
  const htmlId = getHtmlId(section);
  window.cmsConfirm('Delete Link', `Delete "${arr[li].label}"?`, 'Delete').then((ok) => {
    if (!ok) return;
    syncDeleteFromHtml(htmlId, arr[li].label, arr[li].url);
    arr.splice(li, 1);
    section === 'footer' ? renderFooterLinks() : renderLinks();
    saveAll();
  });
}
function deleteSubLinkOf(section, li, si) {
  window.cmsConfirm('Delete Sub-link', 'Delete this sub-link?', 'Delete').then((ok) => {
    if (!ok) return;
    const arr = getLinkArr(section);
    const sub = arr[li].children[si];
    syncDeleteFromHtml(getHtmlId(section), sub.label, sub.url);
    arr[li].children.splice(si, 1);
    section === 'footer' ? renderFooterLinks() : renderLinks();
    saveAll();
  });
}

/* Legacy wrappers kept for old Add Link button */
function addLink() {
  _dlgMode = 'link';
  _dlgLinkIdx = -1;
  document.getElementById('link-dialog-title').textContent = 'Add Nav Link';
  document.getElementById('dlg-label').value = '';
  document.getElementById('dlg-url').value   = '';
  openLinkDialog();
}

/* Dialog helpers */
function openLinkDialog() {
  const d = document.getElementById('link-dialog');
  d.style.display = 'flex';
  setTimeout(() => document.getElementById('dlg-label').focus(), 60);
}
function closeLinkDialog() {
  document.getElementById('link-dialog').style.display = 'none';
}
function saveLinkDialog() {
  const newLabel = document.getElementById('dlg-label').value.trim();
  const newUrl   = document.getElementById('dlg-url').value.trim();
  if (!newLabel) { toast('Label is required', 'error'); return; }

  const section = (_dlgMode === 'footerLink' || _dlgMode === 'footerSub') ? 'footer' : 'nav';
  const arr     = getLinkArr(section);
  const htmlId  = getHtmlId(section);

  if (_dlgMode === 'link' || _dlgMode === 'footerLink') {
    if (_dlgLinkIdx === -1) {
      arr.push({ label: newLabel, url: newUrl, children: [] });
      syncAddIntoHtml(htmlId, newLabel, newUrl);
    } else {
      // Sync to HTML in real-time
      const old = arr[_dlgLinkIdx];
      syncLinkToHtml(htmlId, old.label, old.url, newLabel, newUrl);
      arr[_dlgLinkIdx].label = newLabel;
      arr[_dlgLinkIdx].url   = newUrl;
    }
  } else {
    if (!arr[_dlgLinkIdx].children) arr[_dlgLinkIdx].children = [];
    if (_dlgSubIdx === -1) {
      arr[_dlgLinkIdx].children.push({ label: newLabel, url: newUrl });
      syncAddSubIntoHtml(htmlId, arr[_dlgLinkIdx].label, newLabel, newUrl);
    } else {
      const old = arr[_dlgLinkIdx].children[_dlgSubIdx];
      syncLinkToHtml(htmlId, old.label, old.url, newLabel, newUrl);
      arr[_dlgLinkIdx].children[_dlgSubIdx] = { label: newLabel, url: newUrl };
    }
  }

  closeLinkDialog();
  section === 'footer' ? renderFooterLinks() : renderLinks();
  toast('Syncing changes globally...', 'success');
  saveAll();
}


/* Enter key in dialog */
document.getElementById('dlg-url').addEventListener('keydown', e => {
  if (e.key === 'Enter') saveLinkDialog();
});
document.getElementById('dlg-label').addEventListener('keydown', e => {
  if (e.key === 'Enter') document.getElementById('dlg-url').focus();
});

/* ══════════════════════════════════════════════════════════════════════════════
   SAVE ALL
══════════════════════════════════════════════════════════════════════════════ */
async function saveAll() {
  const btn = document.getElementById('btn-save');
  btn.disabled = true;
  btn.textContent = 'Saving…';

  const allNavLinks = [...links, ...footerLinks.map(l => ({...l, _src: 'footer'}))];
  const payload = {
    nav_links:   links,
    footer_links: footerLinks,
    nav_html:    document.getElementById('nav-html').value,
    nav_css:     document.getElementById('nav-css').value,
    nav_js:      document.getElementById('nav-js').value,
    footer_html: document.getElementById('footer-html').value,
    footer_css:  document.getElementById('footer-css').value,
    footer_js:   document.getElementById('footer-js').value,
    brand_site_name:       document.getElementById('brand_site_name').value,
    brand_college_name:    document.getElementById('brand_college_name').value,
    brand_site_tagline:    document.getElementById('brand_site_tagline').value,
    brand_site_logo:       document.getElementById('brand_site_logo').value,
    brand_university_logo: document.getElementById('brand_university_logo').value,
  };

  try {
    const res  = await fetch(SAVE_URL, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
      body: JSON.stringify(payload),
    });
    const data = await res.json();
    toast(data.message || (data.success ? 'Saved!' : 'Error'), data.success ? 'success' : 'error');
  } catch(e) {
    toast('Network error', 'error');
  } finally {
    btn.disabled = false;
    btn.innerHTML = `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13"/><polyline points="7 3 7 8 15 8"/></svg> Save All`;
  }
}

/* ── PREVIEW ─────────────────────────────────────────────────────────────────── */
function previewLayout() {
  const nav    = document.getElementById('nav-html').value;
  const navCss = document.getElementById('nav-css').value;
  const navJs  = document.getElementById('nav-js').value;
  const footer = document.getElementById('footer-html').value;
  const ftrCss = document.getElementById('footer-css').value;
  const ftrJs  = document.getElementById('footer-js').value;

  const doc = `<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Navigation Preview</title>
  <link rel="stylesheet" href="/assets/bootstrap/bootstrap.min.css">
  <style>${navCss}\n${ftrCss}</style>
  <script>window.NAV_LINKS = ${JSON.stringify(links)};<\/script>
</head>
<body>
${nav}
<div class="container py-5" style="min-height:180px;border:2px dashed #ccc;margin:20px auto;text-align:center;color:#888;">
  <p style="padding:32px 0;font-size:16px;">↑ Page content goes here ↑</p>
</div>
${footer}
<script src="/assets/bootstrap/bootstrap.bundle.min.js"><\/script>
<script>${navJs}\n${ftrJs}<\/script>
</body>
</html>`;

  document.getElementById('preview-iframe').srcdoc = doc;
  document.getElementById('preview-modal').style.display = 'flex';
}
function closePreview() {
  document.getElementById('preview-modal').style.display = 'none';
}

/* ── TOAST ──────────────────────────────────────────────────────────────────── */
function toast(msg, type='success') {
  const el = document.getElementById('toast');
  el.textContent = msg;
  el.className = 'toast-msg show ' + type;
  clearTimeout(el._t);
  el._t = setTimeout(() => el.className = 'toast-msg', 3200);
}

/* ── TAB INDENT ─────────────────────────────────────────────────────────────── */
document.querySelectorAll('.code-area').forEach(ta => {
  ta.addEventListener('keydown', e => {
    if (e.key === 'Tab') {
      e.preventDefault();
      const s = ta.selectionStart;
      ta.value = ta.value.substring(0, s) + '  ' + ta.value.substring(ta.selectionEnd);
      ta.selectionStart = ta.selectionEnd = s + 2;
    }
  });
});

/* ── INIT ────────────────────────────────────────────────────────────────────── */
renderLinks();
renderFooterLinks();
// Auto-parse on load if HTML is already set
(function autoInit() {
  const navHtml    = document.getElementById('nav-html').value.trim();
  const footerHtml = document.getElementById('footer-html').value.trim();
  if (navHtml    && !links.length)       { links        = parseLinksFromHtml(navHtml);    renderLinks(); }
  if (footerHtml && !footerLinks.length) { footerLinks  = parseLinksFromHtml(footerHtml); renderFooterLinks(); }
})();

/* Ctrl+S to save */
document.addEventListener('keydown', e => {
  if ((e.ctrlKey || e.metaKey) && e.key === 's') { e.preventDefault(); saveAll(); }
});
</script>
@endpush
