{{--
    ╔══════════════════════════════════════════════════════════════════╗
    ║  CMS Media Picker Modal                                          ║
    ║  ─────────────────────────────────────────────────────────────── ║
    ║  Usage from JS:                                                   ║
    ║    window.cmsMediaPicker.open({                                  ║
    ║      onSelect: (media) => { /* media.id, media.url, ... */ },   ║
    ║      imagesOnly: false,   // optional, default false             ║
    ║      title: 'Select Image',  // optional                        ║
    ║    });                                                            ║
    ╚══════════════════════════════════════════════════════════════════╝
--}}

<style>
/* ── Picker backdrop ── */
#media-picker-backdrop {
    display: none;
    position: fixed; inset: 0;
    background: rgba(0, 0, 0, 0.4);
    backdrop-filter: blur(12px);
    z-index: 30000;
    align-items: center;
    justify-content: center;
    padding: 1rem;
    opacity: 0;
    transition: opacity 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}
#media-picker-backdrop.open {
    display: flex; opacity: 1;
}

/* ── Picker box ── */
#media-picker-box {
    background: var(--surface, #ffffff);
    color: var(--text, #0f172a);
    border: 1px solid var(--border, var(--text));
    border-radius: 20px;
    width: 100%;
    max-width: 960px;
    max-height: 90vh;
    display: flex;
    flex-direction: column;
    box-shadow: 0 40px 100px -10px rgba(0,0,0,0.5), 0 0 0 1px rgba(255,255,255,0.05) inset;
    transform: scale(0.95) translateY(20px);
    opacity: 0;
    transition: transform 0.35s cubic-bezier(0.34, 1.56, 0.64, 1), opacity 0.3s ease-out;
    overflow: hidden;
}
#media-picker-backdrop.open #media-picker-box {
    transform: scale(1) translateY(0);
    opacity: 1;
}

/* ── Header ── */
#mp-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid var(--border, #f1f5f9);
    flex-shrink: 0;
    background: var(--surface, #ffffff);
}
#mp-title {
    font-weight: 700;
    font-size: 1.1rem;
    flex: 1;
    letter-spacing: -0.01em;
}
#mp-search {
    padding: 0.5rem 1rem;
    background: var(--surface-2, var(--text));
    border: 1px solid var(--border, var(--text));
    color: var(--text, #0f172a);
    border-radius: 10px;
    font-size: 0.85rem;
    width: 240px;
    outline: none;
    transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    font-family: inherit;
}
#mp-search:focus { 
    border-color: var(--primary, var(--accent));
    box-shadow: 0 0 0 3px rgba(var(--accent-rgb),0.15);
    background: var(--surface, #ffffff);
}

.mp-filter-btn {
    padding: 0.4rem 1rem;
    border: 1px solid transparent;
    border-radius: 8px;
    background: transparent;
    font-size: 0.8rem;
    font-weight: 600;
    color: var(--text-2, var(--text-3));
    cursor: pointer;
    transition: all 0.2s;
    font-family: inherit;
}
.mp-filter-btn:hover {
    color: var(--text, #0f172a);
    background: var(--surface-2, #f1f5f9);
}
.mp-filter-btn.active {
    background: var(--primary, var(--accent));
    color: #ffffff;
    box-shadow: 0 4px 12px rgba(var(--accent-rgb),0.25);
}

#mp-close {
    background: transparent;
    border: none;
    border-radius: 50%;
    width: 36px; height: 36px;
    cursor: pointer;
    color: var(--text-2, var(--text-3));
    font-size: 1.2rem;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}
#mp-close:hover { background: var(--surface-2, #f1f5f9); color: var(--text, #0f172a); transform: rotate(90deg); }

/* ── Upload strip ── */
#mp-upload-strip {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 0.85rem 1.5rem;
    background: var(--surface-2, var(--text));
    border-bottom: 1px solid var(--border, #f1f5f9);
    flex-shrink: 0;
}
.mp-upload-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1.15rem;
    background: var(--surface, #ffffff);
    color: var(--text, #0f172a);
    border: 1px solid var(--border, var(--text));
    border-radius: 8px;
    font-size: 0.85rem;
    font-weight: 600;
    cursor: pointer;
    font-family: inherit;
    transition: all 0.2s;
    box-shadow: 0 2px 4px rgba(0,0,0,0.02);
}
.mp-upload-btn:hover { 
    border-color: var(--primary, var(--accent)); 
    color: var(--primary, var(--accent));
    transform: translateY(-1px);
    box-shadow: 0 4px 6px rgba(0,0,0,0.04);
}
.mp-upload-label { font-size: 0.8rem; color: var(--text-3, var(--text-3)); }
#mp-upload-progress { display: none; font-size: 0.8rem; color: var(--primary, var(--accent)); font-weight: 600; animation: mp-pulse 1.5s infinite; }
@keyframes mp-pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }

/* ── Driver toggle ── */
.mp-drv-btn {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 0.3rem 0.75rem;
    border: 1px solid var(--border, var(--text));
    border-radius: 20px;
    background: transparent;
    font-size: 0.75rem; font-weight: 600;
    color: var(--text-2, var(--text-3));
    cursor: pointer; transition: all 0.2s; font-family: inherit;
}
.mp-drv-btn:hover { border-color: var(--primary,var(--accent)); color: var(--primary,var(--accent)); }
.mp-drv-btn.active {
    background: var(--primary,var(--accent)); color: #fff;
    border-color: var(--primary,var(--accent));
    box-shadow: 0 2px 8px rgba(var(--accent-rgb),0.25);
}

/* ── Grid ── */
#mp-grid-wrap { flex: 1; overflow-y: auto; padding: 1.5rem; background: var(--surface, #ffffff); position: relative; transition: outline 0.2s; }
#mp-grid-wrap.dragover { outline: 3px dashed var(--primary, var(--accent)); outline-offset: -10px; background: rgba(var(--accent-rgb),0.05); }

#mp-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(130px, 1fr)); gap: 1.25rem; }
.mp-item {
    border-radius: 12px; overflow: hidden; border: 2px solid var(--border, var(--text));
    cursor: pointer; transition: all 0.25s cubic-bezier(0.16,1,0.3,1); position: relative;
    aspect-ratio: 1; background: var(--surface-2, var(--text));
    animation: mp-item-up 0.4s ease-out both; 
}
@keyframes mp-item-up {
    from { opacity: 0; transform: translateY(15px) scale(0.95); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}
.mp-item:hover { border-color: var(--primary, var(--text-3)); transform: translateY(-4px); box-shadow: 0 12px 24px -8px rgba(0,0,0,0.15); }
.mp-item.selected { border-color: var(--primary, var(--accent)); box-shadow: 0 0 0 4px rgba(var(--accent-rgb),0.15); transform: translateY(-4px) scale(0.98); }
.mp-item img { width: 100%; height: 100%; object-fit: cover; display: block; transition: transform 0.4s cubic-bezier(0.16,1,0.3,1); }
.mp-item:hover img { transform: scale(1.08); }
.mp-item .mp-icon { width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; font-size: 3rem; opacity: 0.8; transition: transform 0.3s; }
.mp-item:hover .mp-icon { transform: scale(1.1); opacity: 1; }
.mp-item .mp-check {
    display: none; position: absolute; top: 8px; right: 8px;
    width: 24px; height: 24px; background: var(--primary, var(--accent)); border-radius: 50%;
    color: white; font-size: 0.8rem; font-weight: 800; align-items: center; justify-content: center;
    box-shadow: 0 2px 8px rgba(var(--accent-rgb),0.4); animation: mp-popScale 0.25s cubic-bezier(0.34,1.56,0.64,1);
    z-index: 2;
}
@keyframes mp-popScale { from { transform: scale(0); } to { transform: scale(1); } }
.mp-item.selected .mp-check { display: flex; }
.mp-item .mp-cloud {
    position: absolute;
    top: 8px; left: 8px;
    background: #0ea5e9;
    color: white;
    border-radius: 6px;
    font-size: 0.6rem;
    padding: 2px 6px;
    font-weight: 800;
    letter-spacing: 0.05em;
    box-shadow: 0 2px 6px rgba(14,165,233,0.3);
    z-index: 2;
}

.mp-item .mp-label {
    position: absolute; bottom: 0; left: 0; right: 0;
    padding: 1.5rem 0.75rem 0.5rem;
    background: linear-gradient(transparent, rgba(0,0,0,0.85));
    color: #ffffff; font-size: 0.75rem; font-weight: 600;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    opacity: 0; transform: translateY(8px);
    transition: all 0.25s cubic-bezier(0.16,1,0.3,1);
    z-index: 2;
}
.mp-item:hover .mp-label { opacity: 1; transform: translateY(0); }

/* ── Empty / loading states ── */
#mp-empty, #mp-loading {
    display: none;
    text-align: center;
    padding: 4rem 2rem;
    color: var(--text-3, var(--text-3));
    font-size: 0.95rem;
    font-weight: 500;
    animation: mp-pulse 2s infinite;
}

/* ── Footer ── */
#mp-footer {
    display: flex; align-items: center; justify-content: space-between;
    padding: 1.25rem 1.5rem; border-top: 1px solid var(--border, #f1f5f9);
    flex-shrink: 0; gap: 1rem; background: var(--surface, #ffffff);
}
#mp-selected-info { font-size: 0.85rem; color: var(--text-2, var(--text-3)); font-weight: 500; }
#mp-selected-preview { display: flex; gap: 0.75rem; align-items: center; flex: 1; overflow: hidden; }
#mp-selected-preview img {
    width: 44px; height: 44px; border-radius: 8px; object-fit: cover;
    border: 1px solid var(--border, var(--text)); flex-shrink: 0;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}
#mp-selected-name {
    font-size: 0.85rem; font-weight: 600; color: var(--text, var(--surface));
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.mp-btn-insert {
    padding: 0.65rem 1.5rem; background: var(--primary, var(--accent));
    color: white; border: none; border-radius: 10px; font-size: 0.95rem;
    font-weight: 700; cursor: pointer; font-family: inherit; transition: all 0.2s cubic-bezier(0.16,1,0.3,1);
    box-shadow: 0 4px 12px rgba(var(--accent-rgb),0.25); flex-shrink: 0;
}
.mp-btn-insert:hover:not(:disabled) { background: #4f46e5; transform: translateY(-2px); box-shadow: 0 6px 16px rgba(var(--accent-rgb),0.35); }
.mp-btn-insert:disabled { opacity: 0.5; cursor: not-allowed; box-shadow: none; transform: none; filter: grayscale(1); }
</style>

<div id="media-picker-backdrop">
    <div id="media-picker-box" role="dialog" aria-modal="true" aria-label="Media Library">

        {{-- Header --}}
        <div id="mp-header">
            <div id="mp-title">Select Media</div>
            <input type="text" id="mp-search" placeholder="Search files…" autocomplete="off" />
            <button class="mp-filter-btn active" data-filter="all">All</button>
            <button class="mp-filter-btn" data-filter="image">Images</button>
            <button class="mp-filter-btn" data-filter="document">Docs</button>
            <button id="mp-close" title="Close (Esc)">✕</button>
        </div>

        {{-- Quick upload strip --}}
        <div id="mp-upload-strip">
            {{-- Driver toggle --}}
            <div id="mp-driver-wrap" style="display:flex;gap:6px;align-items:center;">
                <span style="font-size:0.75rem;color:var(--text-3,var(--text-3));font-weight:600;">Upload to:</span>
                <button class="mp-drv-btn active" data-driver="local" id="mp-drv-local" title="Upload to server storage">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 12H2M22 12l-4-4M22 12l-4 4M2 12l4-4M2 12l4 4"/></svg>
                    Local
                </button>
                @if($cloudinaryReady ?? false)
                <button class="mp-drv-btn" data-driver="cloudinary" id="mp-drv-cloud" title="Upload to Cloudinary CDN">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 10h-1.26A8 8 0 109 20h9a5 5 0 000-10z"/></svg>
                    CDN
                </button>
                @endif
            </div>
            <button class="mp-upload-btn" id="mp-upload-btn">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <polyline points="16 16 12 12 8 16"/><line x1="12" y1="12" x2="12" y2="21"/>
                    <path d="M20.39 18.39A5 5 0 0 0 18 9h-1.26A8 8 0 1 0 3 16.3"/>
                </svg>
                Upload New
            </button>
            <input type="file" id="mp-file-input" style="display:none"
                   accept="image/*,application/pdf,.doc,.docx,video/mp4" />
            <span class="mp-upload-label">or drag a file onto the grid</span>
            <span id="mp-upload-progress">Uploading…</span>
        </div>

        {{-- Grid --}}
        <div id="mp-grid-wrap">
            <div id="mp-loading">Loading media…</div>
            <div id="mp-grid"></div>
            <div id="mp-empty">No files found. Upload one above!</div>
        </div>

        {{-- Footer --}}
        <div id="mp-footer">
            <div id="mp-selected-preview" style="display:none;">
                <img id="mp-thumb" src="" alt="" />
                <span id="mp-selected-name"></span>
            </div>
            <div id="mp-selected-info">No file selected</div>
            <button class="mp-btn-insert" id="mp-insert-btn" disabled>Insert →</button>
        </div>

    </div>
</div>

<script>
window.cmsMediaPicker = (function () {
    'use strict';

    const CSRF = document.querySelector('meta[name="csrf-token"]').content;
    const JSON_URL  = '{{ route("admin.media.json") }}';
    const UPLOAD_URL= '{{ route("admin.media.quick-upload") }}';

    let _onSelect   = null;
    let _imagesOnly = false;
    let _selected   = null;
    let _allItems   = [];
    let _filter     = 'all';
    let _searchTerm = '';
    let _source     = '';   // e.g. 'visual-builder'

    const backdrop   = document.getElementById('media-picker-backdrop');
    const grid       = document.getElementById('mp-grid');
    const searchEl   = document.getElementById('mp-search');
    const emptyEl    = document.getElementById('mp-empty');
    const loadingEl  = document.getElementById('mp-loading');
    const infoEl     = document.getElementById('mp-selected-info');
    const insertBtn  = document.getElementById('mp-insert-btn');
    const previewEl  = document.getElementById('mp-selected-preview');
    const thumbEl    = document.getElementById('mp-thumb');
    const nameEl     = document.getElementById('mp-selected-name');
    const titleEl    = document.getElementById('mp-title');
    const progressEl = document.getElementById('mp-upload-progress');
    const fileInput  = document.getElementById('mp-file-input');

    // ── Open / close ──────────────────────────────────────────────────────
    function open(opts) {
        _onSelect   = opts.onSelect || null;
        _imagesOnly = opts.imagesOnly || false;
        _source     = opts.source || '';
        _selected   = null;
        _filter     = _imagesOnly ? 'image' : 'all';
        _searchTerm = '';

        titleEl.textContent = opts.title || 'Select Media';
        searchEl.value = '';
        updateFilterBtns();
        resetSelection();

        backdrop.classList.add('open');
        document.body.style.overflow = 'hidden';
        loadMedia();
        setTimeout(() => searchEl.focus(), 100);
    }

    function close() {
        backdrop.classList.remove('open');
        document.body.style.overflow = '';
    }

    // ── Load & render ─────────────────────────────────────────────────────
    function loadMedia() {
        showLoading(true);
        const params = new URLSearchParams();
        if (_imagesOnly) params.set('images_only', '1');
        if (_searchTerm) params.set('search', _searchTerm);

        fetch(`${JSON_URL}?${params}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(items => {
            _allItems = items;
            renderGrid();
            showLoading(false);
        })
        .catch(() => { showLoading(false); emptyEl.style.display = 'block'; });
    }

    function renderGrid() {
        const filtered = _allItems.filter(item => {
            if (_filter === 'image'    && !item.is_image) return false;
            if (_filter === 'document' && item.is_image)  return false;
            return true;
        });

        grid.innerHTML = '';
        emptyEl.style.display  = filtered.length ? 'none' : 'block';

        filtered.forEach((item, idx) => {
            const el = document.createElement('div');
            el.className = 'mp-item';
            el.dataset.id = item.id;
            
            // Stagger animation based on index (up to 20 items to prevent huge delays)
            el.style.animationDelay = `${Math.min(idx * 0.03, 0.6)}s`;

            const cloudBadge = item.is_cloud ? '<div class="mp-cloud">CDN</div>' : '';
            const check      = '<div class="mp-check">✓</div>';

            if (item.is_image) {
                el.innerHTML = `
                    <img src="${item.url}" alt="${item.alt || item.title}" loading="lazy" />
                    ${cloudBadge}${check}
                    <div class="mp-label">${item.title}</div>
                `;
            } else {
                el.innerHTML = `
                    <div class="mp-icon">${item.icon}</div>
                    ${cloudBadge}${check}
                    <div class="mp-label">${item.title}</div>
                `;
            }

            el.addEventListener('click', () => selectItem(item, el));
            grid.appendChild(el);
        });
    }

    function selectItem(item, el) {
        grid.querySelectorAll('.mp-item').forEach(i => i.classList.remove('selected'));
        el.classList.add('selected');
        _selected = item;

        // Footer preview
        infoEl.textContent = item.human_size || '—';
        if (item.is_image) {
            thumbEl.src = item.url;
            thumbEl.style.display = 'block';
        } else {
            thumbEl.style.display = 'none';
        }
        nameEl.textContent = item.title;
        previewEl.style.display = 'flex';
        insertBtn.disabled = false;
    }

    function resetSelection() {
        _selected = null;
        infoEl.textContent = 'No file selected';
        previewEl.style.display = 'none';
        insertBtn.disabled = true;
    }

    function showLoading(show) {
        loadingEl.style.display = show ? 'block' : 'none';
        if (show) { grid.innerHTML = ''; emptyEl.style.display = 'none'; }
    }

    // ── Filters ───────────────────────────────────────────────────────────
    function updateFilterBtns() {
        document.querySelectorAll('.mp-filter-btn').forEach(btn => {
            btn.classList.toggle('active', btn.dataset.filter === _filter);
        });
    }

    document.querySelectorAll('.mp-filter-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            _filter = btn.dataset.filter;
            updateFilterBtns();
            renderGrid();
        });
    });

    // ── Search ────────────────────────────────────────────────────────────
    let searchTimer = null;
    searchEl.addEventListener('input', () => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => {
            _searchTerm = searchEl.value.trim();
            loadMedia();
        }, 350);
    });

    // ── Insert ────────────────────────────────────────────────────────────
    insertBtn.addEventListener('click', () => {
        if (_selected && _onSelect) {
            _onSelect(_selected);
            close();
        }
    });

    // ── Upload ────────────────────────────────────────────────────────────
    let _driver = 'local'; // default

    // Wire driver toggle buttons
    document.querySelectorAll('.mp-drv-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            _driver = btn.dataset.driver;
            document.querySelectorAll('.mp-drv-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
        });
    });

    document.getElementById('mp-upload-btn').addEventListener('click', () => fileInput.click());

    fileInput.addEventListener('change', () => {
        if (fileInput.files[0]) uploadFile(fileInput.files[0]);
    });

    // Drag-onto-grid upload
    const gridWrap = document.getElementById('mp-grid-wrap');
    gridWrap.addEventListener('dragover', e => { 
        e.preventDefault(); 
        gridWrap.classList.add('dragover'); 
    });
    gridWrap.addEventListener('dragleave', () => { 
        gridWrap.classList.remove('dragover'); 
    });
    gridWrap.addEventListener('drop', e => {
        e.preventDefault();
        gridWrap.classList.remove('dragover');
        if (e.dataTransfer.files[0]) uploadFile(e.dataTransfer.files[0]);
    });

    function uploadFile(file) {
        progressEl.style.display = 'inline';
        const fd = new FormData();
        fd.append('file', file);
        fd.append('_token', CSRF);
        fd.append('driver', _driver); // pass chosen driver
        if (_source) fd.append('source', _source); // tag with source

        fetch(UPLOAD_URL, { method: 'POST', body: fd })
            .then(r => r.json())
            .then(data => {
                progressEl.style.display = 'none';
                if (data.error) { window.cmsToast(data.error, 'error'); return; }
                window.cmsToast('Uploaded!', 'success');
                // Refresh & auto-select the new file
                loadMedia();
            })
            .catch(() => {
                progressEl.style.display = 'none';
                window.cmsToast('Upload failed', 'error');
            });
    }

    // ── Close ────────────────────────────────────────────────────────────
    document.getElementById('mp-close').addEventListener('click', close);
    backdrop.addEventListener('click', e => { if (e.target === backdrop) close(); });
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape' && backdrop.classList.contains('open')) close();
        if (e.key === 'Enter'  && backdrop.classList.contains('open') && _selected) insertBtn.click();
    });

    return { open, close };
})();
</script>
