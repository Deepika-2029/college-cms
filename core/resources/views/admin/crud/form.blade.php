@extends('admin.layout')

@push('styles')
{{-- Page-level admin CSS --}}
@if(file_exists(resource_path('views/admin/crud/style.css')))
<link rel="stylesheet" href="{{ route('admin.page-asset', ['crud/style.css']) }}">
@endif
{{-- Table-specific CSS (edit in resources/crud-ui/{table}/style.css) --}}
<link rel="stylesheet" href="{{ route('admin.crud-asset', [$table, 'style.css']) }}">
@endpush

@push('scripts')
{{-- Table-specific JS (edit in resources/crud-ui/{table}/script.js) --}}
<script src="{{ route('admin.crud-asset', [$table, 'script.js']) }}" defer></script>
@endpush

@section('title', ($row ? 'Edit' : 'Create') . ' — ' . $table)
@section('page-title', ($row ? 'Edit Record' : 'New Record') . ' — ' . $table)

@section('topbar-actions')
    <a href="{{ route('admin.crud.index', $table) }}" class="btn-cms btn-cms-secondary">← Back to List</a>
@endsection

@push('styles')
<link rel="stylesheet" id="page-css" href="{{ route('admin.page-asset', ['crud/style.css']) }}">
<style>
/* Modern Elegant Form Styling (Theme Compatible) */
.card {
    background: var(--surface); border: 1px solid var(--border); border-radius: var(--r-xl);
    padding: 2rem; box-shadow: var(--shadow-sm);
}
@media(max-width: 768px) {
    .card { padding: 1.25rem; }
}

.card-title {
    font-size: 1.25rem; font-weight: 700; margin-bottom: 1.5rem;
    color: var(--text); border-bottom: 1px solid var(--border); padding-bottom: 1rem;
    display: flex; align-items: center; gap: 0.5rem;
}
.form-group { margin-bottom: 1.5rem; }
.form-group label {
    display: block; font-size: 0.85rem; font-weight: 600;
    color: var(--text-2); margin-bottom: 0.5rem; letter-spacing: 0.3px;
}
.form-control {
    width: 100%; padding: 0.8rem 1rem; background: var(--surface);
    border: 1.5px solid var(--border); border-radius: var(--r); color: var(--text);
    font-size: 0.95rem; transition: all var(--t); box-sizing: border-box;
}
.form-control:focus {
    outline: none; border-color: var(--accent);
    box-shadow: 0 0 0 3px rgba(var(--accent-rgb), 0.15); background: var(--surface);
}

/* Enhanced Media Uploader Container */
.mu-wrap {
    background: var(--surface-2); border: 1px solid var(--border);
    border-radius: var(--r-lg); padding: 1.25rem; margin-top: 0.25rem;
}
.mu-list { min-height: 10px; display: flex; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 0.75rem; }
.mu-list:empty { margin-bottom: 0; }
.mu-actions {
    display: flex; gap: 0.75rem; align-items: center; flex-wrap: wrap;
    padding-top: 0.85rem; border-top: 1px dashed var(--border-2);
}
.mu-list:empty + .mu-actions { padding-top: 0; border-top: none; }
.mu-hidden { display: none !important; }
.mu-btn {
    display: inline-flex; align-items: center; gap: 0.4rem;
    background: var(--surface); color: var(--text-2); border: 1px solid var(--border); padding: 0.5rem 1rem;
    border-radius: var(--r); font-size: 0.85rem; cursor: pointer; font-weight: 500;
    transition: all var(--t);
}
.mu-btn:hover { background: var(--surface-3); border-color: var(--border-2); color: var(--text); }
.mu-btn.green {
    background: var(--accent); color:var(--text); border-color: var(--accent);
}
.mu-btn.green:hover { background: var(--accent-h); transform: translateY(-1px); }

/* URL Input Row */
.mu-url-row {
    display: none;
    align-items: center;
    gap: 0.5rem;
    margin-top: 0.6rem;
    padding: 0.65rem 0.75rem;
    background: var(--surface);
    border: 1.5px dashed var(--border-2);
    border-radius: var(--r);
    animation: muFadeIn 0.18s ease;
}
.mu-url-row.open { display: flex; }
.mu-url-row input {
    flex: 1;
    padding: 0.45rem 0.75rem;
    background: var(--surface-2);
    border: 1.5px solid var(--border);
    border-radius: var(--r-sm);
    color: var(--text);
    font-size: 0.88rem;
    outline: none;
    transition: border-color 0.2s;
}
.mu-url-row input:focus { border-color: var(--accent); }
.mu-url-row input::placeholder { color: var(--text-3); }
.mu-url-add-btn {
    padding: 0.45rem 1rem;
    background: var(--accent);
    color: white;
    border: none;
    border-radius: var(--r-sm);
    font-size: 0.85rem;
    font-weight: 600;
    cursor: pointer;
    white-space: nowrap;
    transition: opacity 0.2s;
}
.mu-url-add-btn:hover { opacity: 0.85; }
.mu-url-cancel-btn {
    padding: 0.45rem 0.65rem;
    background: transparent;
    color: var(--text-3);
    border: 1px solid var(--border);
    border-radius: var(--r-sm);
    font-size: 0.85rem;
    cursor: pointer;
    transition: all 0.2s;
}
.mu-url-cancel-btn:hover { color: var(--red); border-color: var(--red); }
@keyframes muFadeIn { from { opacity:0; transform: translateY(-4px); } to { opacity:1; transform: translateY(0); } }

/* Storage Toggle Buttons */
.storage-toggle { display: flex; background: var(--surface-3); border-radius: var(--r-sm); overflow: hidden; border: 1px solid var(--border); margin-left: auto; }
.storage-opt { background: transparent; border: none; padding: 0.4rem 0.75rem; color: var(--text-3); font-size: 0.75rem; font-weight: 600; cursor: pointer; transition: 0.2s; }
.storage-opt:hover { color: var(--text); background: var(--surface-3); }
.storage-opt.active { background: var(--accent); color: white; }

/* Custom Toggle Switch for Boolean Fields */
.toggle-switch-wrap { display: flex; align-items: center; margin-top: 0.25rem; }
.toggle-switch { position: relative; display: inline-block; width: 44px; height: 24px; flex-shrink: 0; }
.toggle-switch input { opacity: 0; width: 0; height: 0; }
.toggle-switch .slider { position: absolute; cursor: pointer; inset: 0; background-color: var(--border-2); transition: .3s cubic-bezier(0.4, 0, 0.2, 1); border-radius: 24px; overflow: hidden; box-shadow: inset 0 1px 2px rgba(0,0,0,0.1); }
.toggle-switch .slider:before { position: absolute; content: ""; height: 18px; width: 18px; left: 3px; bottom: 3px; background-color: white; transition: .3s cubic-bezier(0.4, 0, 0.2, 1); border-radius: 50%; box-shadow: 0 2px 4px rgba(0,0,0,0.2); }
.toggle-switch input:checked + .slider { background-color: var(--accent); }
.toggle-switch input:focus + .slider { box-shadow: 0 0 0 3px rgba(var(--accent-rgb), 0.2); }
.toggle-switch input:checked + .slider:before { transform: translateX(20px); }

/* ─── Media Preview Cards ────────────────────────────── */
.mu-list { display: flex; flex-wrap: wrap; gap: 0.75rem; margin-bottom: 0.75rem; }
.mu-list:empty { margin-bottom: 0; }

.mu-preview-card {
    position: relative;
    width: 130px;
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--r-lg);
    overflow: hidden;
    transition: box-shadow 0.2s, transform 0.2s;
    flex-shrink: 0;
}
.mu-preview-card:hover { box-shadow: var(--shadow); transform: translateY(-2px); }

.mu-card-thumb {
    position: relative;
    width: 100%;
    height: 100px;
    background: var(--surface-2);
    overflow: hidden;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--text-3);
}
.mu-card-thumb img {
    width: 100%; height: 100%; object-fit: cover;
    transition: transform 0.3s;
}
.mu-preview-card:hover .mu-card-thumb img { transform: scale(1.05); }

.mu-card-doc { background: linear-gradient(135deg, var(--surface-2), var(--surface-3)); flex-direction: column; gap: 4px; }
.mu-doc-ext  { font-size: 0.65rem; font-weight: 800; letter-spacing: 0.08em; color: var(--accent); text-transform: uppercase; }

.mu-card-overlay {
    position: absolute; inset: 0;
    background: rgba(0,0,0,0.45);
    display: flex; align-items: center; justify-content: center;
    opacity: 0; transition: opacity 0.2s;
}
.mu-card-thumb:hover .mu-card-overlay { opacity: 1; }

.mu-card-info {
    padding: 0.5rem 0.6rem 0.4rem;
    display: flex; flex-direction: column; gap: 2px;
    border-top: 1px solid var(--border);
}
.mu-card-name {
    font-size: 0.72rem; font-weight: 600; color: var(--text);
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    max-width: 110px;
}
.mu-card-type { font-size: 0.65rem; color: var(--text-3); text-transform: uppercase; letter-spacing: 0.05em; }

.mu-card-remove {
    position: absolute; top: 6px; right: 6px;
    background: rgba(0,0,0,0.6); backdrop-filter: blur(4px);
    border: none; border-radius: 50%;
    width: 22px; height: 22px;
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-size: 0.75rem; cursor: pointer;
    opacity: 0; transition: opacity 0.2s, background 0.2s; line-height: 1;
}
.mu-preview-card:hover .mu-card-remove { opacity: 1; }
.mu-card-remove:hover { background: var(--red) !important; }

/* Loading Spinner Card */
.mu-loading-card { opacity: 0.85; }
.mu-spinner {
    width: 34px; height: 34px;
    border: 3px solid rgba(var(--accent-rgb), 0.2);
    border-top-color: var(--accent);
    border-radius: 50%;
    animation: muSpin 0.85s linear infinite;
}
@keyframes muSpin { to { transform: rotate(360deg); } }

/* Media Lightbox */
#mu-lightbox {
    display: none; position: fixed; inset: 0;
    background: rgba(0,0,0,0.88); backdrop-filter: blur(8px);
    z-index: 999999;
    align-items: center; justify-content: center;
    flex-direction: column; gap: 1rem;
    opacity: 0; transition: opacity 0.25s;
}
#mu-lightbox.active { display: flex !important; opacity: 1; }
#mu-lightbox-close {
    position: absolute; top: 20px; right: 24px;
    background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2);
    color: #fff; font-size: 1.4rem; border-radius: 50%;
    width: 42px; height: 42px; cursor: pointer; display: flex;
    align-items: center; justify-content: center; transition: background 0.2s;
}
#mu-lightbox-close:hover { background: rgba(255,255,255,0.25); }
#mu-lightbox img {
    max-width: 90vw; max-height: 80vh;
    border-radius: 8px; object-fit: contain;
    box-shadow: 0 24px 80px rgba(0,0,0,0.5);
}
#mu-lightbox-caption {
    color: rgba(255,255,255,0.7);
    font-size: 0.82rem; max-width: 80vw;
    text-align: center; word-break: break-all;
}
#mu-lightbox-open-link {
    display: inline-flex; align-items: center; gap: 0.4rem;
    color: rgba(255,255,255,0.8); font-size: 0.82rem; text-decoration: none;
    background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2);
    border-radius: 20px; padding: 0.35rem 0.9rem;
    transition: background 0.2s;
}
#mu-lightbox-open-link:hover { background: rgba(255,255,255,0.2); color:#fff; }
</style>
@endpush

@section('content')

<div style="max-width:700px;">
    <div class="card">
        <div class="card-title">
            {{ $row ? 'Edit' : 'Create' }} record in <span class="badge badge-blue">{{ $table }}</span>
        </div>

        <form action="{{ $action }}" method="POST" id="crud-form">
            @csrf
            @if($method === 'PUT') @method('PUT') @endif

            @foreach($columns as $col)
            @php
                $label   = ucwords(str_replace('_', ' ', $col));
                $value   = old($col, $row ? (is_object($row) ? $row->$col : ($row[$col] ?? '')) : '');
                $isDate  = str_contains($col, 'date') || str_contains($col, '_at');
                $isEmail = str_contains($col, 'email');
                $isBool  = str_starts_with($col, 'is_') || str_starts_with($col, 'has_') || $col === 'active' || $col === 'published';
                $isLong  = str_contains($col, 'description') || str_contains($col, 'body')
                        || str_contains($col, 'content')     || str_contains($col, 'bio')
                        || str_contains($col, 'text')        || str_contains($col, 'summary');
                $isUrl   = ! $isLong && (str_contains($col, 'url') || str_contains($col, 'link'));

                // ── Multi-image fields (store JSON array of URLs) ──────────────────
                // Matches common column names: image_url, photos, gallery, avatar, etc.
                $imageKeywords = ['image', 'photo', 'banner', 'thumbnail', 'gallery',
                                  'avatar', 'picture', 'cover', 'logo', 'icon',
                                  'screenshot', 'poster', 'media'];
                $isImage = collect($imageKeywords)->contains(fn($kw) => str_contains($col, $kw));

                // ── Multi-file / document fields (store JSON array of URLs) ─────────
                // Matches columns like document_url, resume, pdf, video_url, report, etc.
                $docKeywords = ['document', 'file', 'attachment', 'resume', 'pdf',
                                'report', 'certificate', 'brochure', 'slides', 'ppt',
                                'video', 'presentation', 'download'];
                $isDoc = !$isImage
                    && collect($docKeywords)->contains(fn($kw) => str_contains($col, $kw))
                    && !str_contains($col, 'name')
                    && !str_contains($col, 'type');
            @endphp

            <div class="form-group">
                <label>{{ $label }}</label>

                @if($isImage)
                {{-- ── Image field ── --}}
                <div class="mu-wrap">
                    <div class="mu-list" id="mul-{{ $col }}"></div>
                    <div class="mu-actions">
                        <button type="button" class="mu-btn green"
                                onclick="document.getElementById('mufile-img-{{ $col }}').click()">
                            📷 Upload
                        </button>
                        <input type="file" id="mufile-img-{{ $col }}" class="mu-hidden"
                               accept="image/*" multiple
                               onchange="uploadFilesAndAdd(this, '{{ $col }}')">
                        <button type="button" class="mu-btn"
                                onclick="openPicker('{{ $col }}', 'image')">
                            🖼 Library
                        </button>
                        <button type="button" class="mu-btn"
                                onclick="toggleUrlRow('urlrow-{{ $col }}')">
                            🔗 Add URL
                        </button>
                        <div class="storage-toggle" id="st-{{ $col }}" title="Storage driver for this field">
                            <button type="button" class="storage-opt active" data-col="{{ $col }}" data-value="local"
                                    onclick="setFieldDriver('{{ $col }}','local')">💾 Local</button>
                            <button type="button" class="storage-opt cloud" data-col="{{ $col }}" data-value="cloudinary"
                                    onclick="setFieldDriver('{{ $col }}','cloudinary')">☁️ Cloud</button>
                        </div>
                    </div>
                    <div class="mu-url-row" id="urlrow-{{ $col }}">
                        <input type="text" id="urlInput-{{ $col }}" placeholder="https://example.com/image.jpg"
                               onkeydown="if(event.key==='Enter'){event.preventDefault();addUrlToField('{{ $col }}');}">
                        <button type="button" class="mu-url-add-btn" onclick="addUrlToField('{{ $col }}')">
                            ＋ Add
                        </button>
                        <button type="button" class="mu-url-cancel-btn" onclick="toggleUrlRow('urlrow-{{ $col }}')">
                            ✕
                        </button>
                    </div>
                </div>
                <input type="hidden" name="{{ $col }}" id="{{ $col }}" value="{{ $value }}">

                @elseif($isDoc)
                {{-- ── Document field ── --}}
                <div class="mu-wrap">
                    <div class="mu-list" id="mul-{{ $col }}"></div>
                    <div class="mu-actions">
                        <button type="button" class="mu-btn green"
                                onclick="document.getElementById('mufile-doc-{{ $col }}').click()">
                            📎 Upload
                        </button>
                        <input type="file" id="mufile-doc-{{ $col }}" class="mu-hidden"
                               accept="image/*,application/pdf,video/mp4,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.zip,.txt" multiple
                               onchange="uploadFilesAndAdd(this, '{{ $col }}')">
                        <button type="button" class="mu-btn"
                                onclick="openPicker('{{ $col }}', 'all')">
                            📂 Library
                        </button>
                        <button type="button" class="mu-btn"
                                onclick="toggleUrlRow('urlrow-{{ $col }}')">
                            🔗 Add URL
                        </button>
                        <div class="storage-toggle" id="st-{{ $col }}" title="Storage driver for this field">
                            <button type="button" class="storage-opt active" data-col="{{ $col }}" data-value="local"
                                    onclick="setFieldDriver('{{ $col }}','local')">💾 Local</button>
                            <button type="button" class="storage-opt cloud" data-col="{{ $col }}" data-value="cloudinary"
                                    onclick="setFieldDriver('{{ $col }}','cloudinary')">☁️ Cloud</button>
                        </div>
                    </div>
                    <div class="mu-url-row" id="urlrow-{{ $col }}">
                        <input type="text" id="urlInput-{{ $col }}" placeholder="https://example.com/document.pdf"
                               onkeydown="if(event.key==='Enter'){event.preventDefault();addUrlToField('{{ $col }}');}">
                        <button type="button" class="mu-url-add-btn" onclick="addUrlToField('{{ $col }}')">
                            ＋ Add
                        </button>
                        <button type="button" class="mu-url-cancel-btn" onclick="toggleUrlRow('urlrow-{{ $col }}')">
                            ✕
                        </button>
                    </div>
                </div>
                <input type="hidden" name="{{ $col }}" id="{{ $col }}" value="{{ $value }}">

                @elseif($isLong)
                <textarea name="{{ $col }}" id="{{ $col }}" class="form-control" rows="5">{{ $value }}</textarea>

                @elseif($isDate)
                <input type="datetime-local" name="{{ $col }}" id="{{ $col }}" class="form-control"
                       value="{{ $value ? \Carbon\Carbon::parse($value)->format('Y-m-d\TH:i') : '' }}">

                @elseif($isEmail)
                <input type="email" name="{{ $col }}" id="{{ $col }}" class="form-control" value="{{ $value }}">

                @elseif($isUrl)
                <input type="url" name="{{ $col }}" id="{{ $col }}" class="form-control"
                       value="{{ $value }}" placeholder="https://">

                @elseif($isBool)
                <div class="toggle-switch-wrap">
                    <input type="hidden" name="{{ $col }}" value="0">
                    <label class="toggle-switch">
                        <input type="checkbox" name="{{ $col }}" id="{{ $col }}" value="1" {{ $value ? 'checked' : '' }}>
                        <span class="slider"></span>
                    </label>
                </div>

                @else
                <input type="text" name="{{ $col }}" id="{{ $col }}" class="form-control" value="{{ $value }}">
                @endif
            </div>
            @endforeach

            <div style="display:flex;gap:0.75rem;margin-top:1.5rem;">
                <button type="submit" class="btn-cms btn-cms-primary">
                    {{ $row ? '✓ Save Changes' : '＋ Create Record' }}
                </button>
                <a href="{{ route('admin.crud.index', $table) }}" class="btn-cms btn-cms-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>



@endsection

{{-- ─── Media Lightbox Modal ─────────────────────────── --}}
<div id="mu-lightbox">
    <button id="mu-lightbox-close" onclick="closeMediaLightbox()" title="Close">✕</button>
    <div id="mu-lightbox-body"></div>
    <p id="mu-lightbox-caption"></p>
    <a id="mu-lightbox-open-link" href="#" target="_blank">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
        Open in new tab
    </a>
</div>

@push('scripts')
<script>
window.crudUploadUrl = "{{ route('admin.media.quick-upload') }}";
window.crudJsonUrl = "{{ route('admin.media.json') }}";
window.csrfToken = window.CMS_CSRF || "{{ csrf_token() }}";

// ─── Media Lightbox ──────────────────────────────────────
function openMediaLightbox(url, type) {
    const lb = document.getElementById('mu-lightbox');
    const body = document.getElementById('mu-lightbox-body');
    const caption = document.getElementById('mu-lightbox-caption');
    const link = document.getElementById('mu-lightbox-open-link');
    
    link.href = url;
    caption.textContent = url.split('/').pop() || url;
    
    if (type === 'image') {
        body.innerHTML = `<img src="${url}" alt="preview">`;
    } else {
        body.innerHTML = `
            <iframe src="${url}" style="width:85vw;height:75vh;border:none;border-radius:8px;background:#fff;"></iframe>
        `;
    }
    
    lb.classList.add('active');
    document.body.style.overflow = 'hidden';
}
function closeMediaLightbox() {
    const lb = document.getElementById('mu-lightbox');
    lb.classList.remove('active');
    document.body.style.overflow = '';
    document.getElementById('mu-lightbox-body').innerHTML = '';
}
document.getElementById('mu-lightbox').addEventListener('click', function(e) {
    if (e.target === this) closeMediaLightbox();
});
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeMediaLightbox(); });


const State = {
    activeCol: null,
    storageDrivers: {}, // { colName: 'local' }
    modalDriver: 'local',
    mediaItems: [],     // All items loaded from library
    selectedIds: [],    // IDs selected in library
};

function renderFieldArray(col) {
    const hidden = document.getElementById(col);
    const wrap = document.getElementById('mul-' + col);
    if(!hidden || !wrap) return;

    let arr = [];
    try { arr = JSON.parse(hidden.value) || []; } catch(e){
        if (hidden.value) arr = [hidden.value];
    }
    
    wrap.innerHTML = '';
    arr.forEach((url, index) => {
        const isImgField = document.getElementById('mufile-img-' + col) !== null;
        const isImg = isImgField || /\.(jpeg|jpg|gif|png|webp|svg)$/i.test(url);

        const card = document.createElement('div');
        card.className = 'mu-preview-card';

        if (isImg) {
            card.innerHTML = `
                <div class="mu-card-thumb" onclick="openMediaLightbox('${url}', 'image')" title="Click to preview">
                    <img src="${url}" alt="preview" loading="lazy">
                    <div class="mu-card-overlay"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><path d="M1 1l22 22M17 5H9a4 4 0 0 0-4 4v8m-2 4h14a2 2 0 0 0 2-2v-5"/><circle cx="12" cy="12" r="3"/></svg></div>
                </div>
                <div class="mu-card-info">
                    <span class="mu-card-name">${url.split('/').pop() || url}</span>
                    <span class="mu-card-type">Image</span>
                </div>
                <button type="button" class="mu-card-remove" onclick="removeFieldItem('${col}', ${index})" title="Remove">✕</button>
            `;
        } else {
            const ext = url.split('.').pop().toUpperCase() || 'FILE';
            card.innerHTML = `
                <div class="mu-card-thumb mu-card-doc" onclick="openMediaLightbox('${url}', 'doc')" title="Click to preview">
                    <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                    <span class="mu-doc-ext">${ext}</span>
                    <div class="mu-card-overlay"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg></div>
                </div>
                <div class="mu-card-info">
                    <span class="mu-card-name">${url.split('/').pop() || url}</span>
                    <span class="mu-card-type">${ext} File</span>
                </div>
                <button type="button" class="mu-card-remove" onclick="removeFieldItem('${col}', ${index})" title="Remove">✕</button>
            `;
        }
        wrap.appendChild(card);
    });

    hidden.value = JSON.stringify(arr);
}

function removeFieldItem(col, index) {
    const hidden = document.getElementById(col);
    let arr = JSON.parse(hidden.value || '[]');
    arr.splice(index, 1);
    hidden.value = JSON.stringify(arr);
    renderFieldArray(col);
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('input[type="hidden"]').forEach(el => {
        if(document.getElementById('mul-' + el.id)) {
            renderFieldArray(el.id);
        }
    });
});

async function uploadFilesAndAdd(input, col) {
    if(!input.files || input.files.length === 0) return;
    
    const driver = State.storageDrivers[col] || 'local';
    const hidden = document.getElementById(col);
    const wrap = document.getElementById('mul-' + col);
    let currentArr = [];
    try { currentArr = JSON.parse(hidden.value) || []; } catch(e){}

    const btns = input.parentElement.querySelectorAll('.mu-btn');
    btns.forEach(b => b.style.opacity = '0.5');

    // Create a loading placeholder for each file being uploaded
    const loadingCards = [];
    for(let i = 0; i < input.files.length; i++) {
        const file = input.files[i];
        const isImg = file.type.startsWith('image/');
        const loader = document.createElement('div');
        loader.className = 'mu-preview-card mu-loading-card';
        loader.innerHTML = `
            <div class="mu-card-thumb ${isImg ? '' : 'mu-card-doc'}" style="cursor:default;">
                <div class="mu-spinner"></div>
            </div>
            <div class="mu-card-info">
                <span class="mu-card-name" style="font-size:0.75rem;">${file.name}</span>
                <span class="mu-card-type" style="color:var(--accent);">Uploading…</span>
            </div>
        `;
        wrap.appendChild(loader);
        loadingCards.push(loader);
    }

    for(let i = 0; i < input.files.length; i++) {
        let fd = new FormData();
        fd.append('file', input.files[i]);
        fd.append('driver', driver);
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content || window.csrfToken;
        fd.append('_token', csrf);
        
        try {
            let res = await fetch(window.crudUploadUrl, {
                method: 'POST',
                body: fd,
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf }
            });
            let json = await res.json();
            if(json.url) {
                currentArr.push(json.url);
                let filename = input.files[i].name;
                let ext = filename.split('.').pop().toLowerCase();
                let cleanName = filename.replace(/\.[^/.]+$/, "");
                let baseColPrefix = col.split('_')[0];
                let typeInput = document.querySelector(`input[name="${baseColPrefix}_type"]`) || document.querySelector('input[name*="type"]');
                let nameInput = document.querySelector(`input[name="${baseColPrefix}_name"]`) || document.querySelector('input[name*="name"]');
                if (typeInput && !typeInput.value) { typeInput.value = ext; }
                if (nameInput && !nameInput.value) { nameInput.value = cleanName; }
            } else if(json.error) {
                cmsToast('Error: ' + json.error, 'error');
            } else if(json.message) {
                cmsToast('Error: ' + json.message, 'error');
            }
        } catch(e) {
            cmsToast('Upload failed: ' + e, 'error');
        }
        // Remove this file's loading card
        if(loadingCards[i]) loadingCards[i].remove();
    }
    
    hidden.value = JSON.stringify(currentArr);
    renderFieldArray(col);
    input.value = '';
    btns.forEach(b => b.style.opacity = '1');
}

function setFieldDriver(col, driver) {
    State.storageDrivers[col] = driver;
    const parent = document.getElementById('st-' + col);
    if(parent) {
        parent.querySelectorAll('.storage-opt').forEach(b => b.classList.remove('active'));
        parent.querySelector(`[data-value="${driver}"]`).classList.add('active');
    }
}

function openPicker(col, type) {
    if(!window.cmsMediaPicker || !window.cmsMediaPicker.open) {
        cmsToast("Media Picker has not loaded yet!", 'error');
        return;
    }
    window.cmsMediaPicker.open({
        title: type === 'image' ? 'Select Image' : 'Select Media',
        imagesOnly: type === 'image',
        onSelect: function(media) {
            if(!media || !media.url) return;
            const hidden = document.getElementById(col);
            let currentArr = [];
            try { currentArr = JSON.parse(hidden.value) || []; } catch(e){}
            
            if(!currentArr.includes(media.url)) {
                currentArr.push(media.url);
                
                let filename = media.url.split('/').pop() || "";
                let ext = filename.split('.').pop().toLowerCase();
                let cleanName = media.title || media.name || filename.replace(/\.[^/.]+$/, "");
                
                let baseColPrefix = col.split('_')[0]; 
                let typeInput = document.querySelector(`input[name="${baseColPrefix}_type"]`) || document.querySelector('input[name*="type"]');
                let nameInput = document.querySelector(`input[name="${baseColPrefix}_name"]`) || document.querySelector('input[name*="name"]');
                
                if (typeInput && !typeInput.value) { typeInput.value = ext; }
                if (nameInput && !nameInput.value) { nameInput.value = cleanName; }
            }
            
            hidden.value = JSON.stringify(currentArr);
            renderFieldArray(col);
        }
    });
}

// ─── URL Input Helpers ───────────────────────────────────────────────────────
function toggleUrlRow(rowId) {
    const row = document.getElementById(rowId);
    if (!row) return;
    const isOpen = row.classList.toggle('open');
    if (isOpen) {
        // Focus the input when opened
        const inp = row.querySelector('input[type="text"]');
        if (inp) setTimeout(() => inp.focus(), 50);
    }
}

function addUrlToField(col) {
    const inp = document.getElementById('urlInput-' + col);
    if (!inp) return;
    const url = inp.value.trim();
    if (!url) {
        inp.style.borderColor = 'var(--red)';
        setTimeout(() => inp.style.borderColor = '', 1200);
        return;
    }
    // Basic URL validation
    try { new URL(url); } catch(e) {
        cmsToast('Please enter a valid URL starting with http:// or https://', 'error');
        inp.focus();
        return;
    }
    const hidden = document.getElementById(col);
    let arr = [];
    try { arr = JSON.parse(hidden.value) || []; } catch(e) {}
    if (arr.includes(url)) {
        cmsToast('This URL is already added', 'error');
        return;
    }
    arr.push(url);
    hidden.value = JSON.stringify(arr);
    renderFieldArray(col);
    inp.value = '';
    inp.focus(); // Keep row open so user can add more
    cmsToast('URL added ✓', 'success');
}
</script>
@endpush

