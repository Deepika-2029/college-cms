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

@section('title', 'Manage: ' . $table)
@section('page-title', 'Manage: ' . $table)

@section('topbar-actions')
    <a href="{{ route('admin.crud.create', $table) }}" class="btn-cms btn-cms-primary">＋ Add Record</a>
@endsection

@push('styles')
<style>
/* Modern List UI (Theme Compatible) */
.card {
    background: var(--surface); border: 1px solid var(--border); border-radius: var(--r-xl);
    padding: 1.5rem; box-shadow: var(--shadow-sm);
}
.header-actions {
    display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;
    border-bottom: 1px solid var(--border); padding-bottom: 1rem;
}
.search-form { display: flex; gap: 0.5rem; width: 100%; max-width: 450px; }
.search-input {
    flex: 1; padding: 0.5rem 1rem; background: var(--surface-2); border: 1.5px solid var(--border);
    border-radius: var(--r); color: var(--text); transition: all var(--t); font-size: 0.9rem;
}
.search-input:focus { outline: none; border-color: var(--accent); box-shadow: 0 0 0 3px rgba(var(--accent-rgb), 0.15); background: var(--surface); }

.search-btn {
    background: var(--surface-3); color: var(--text-2); border: 1px solid var(--border); padding: 0.5rem 1.15rem;
    border-radius: var(--r); font-weight: 500; cursor: pointer; transition: all var(--t); font-size: 0.9rem;
}
.search-btn:hover { background: var(--accent-muted); color: var(--accent); border-color: rgba(var(--accent-rgb),.3); }

.search-btn-clear {
    background: transparent; color: var(--text-3); border: 1px solid var(--border-2); padding: 0.5rem 0.85rem;
    border-radius: var(--r); font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; transition: all var(--t);
}
.search-btn-clear:hover { background: var(--red-bg); color: var(--red); border-color: var(--red-border); }

.table-wrap { overflow-x: auto; background: var(--surface); border: 1px solid var(--border); border-radius: var(--r-lg); -webkit-overflow-scrolling: touch; }
.data-table { width: 100%; border-collapse: collapse; text-align: left; min-width: 600px; }
.data-table th {
    padding: 0.85rem 1.15rem; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px;
    color: var(--text-3); background: var(--surface-2); border-bottom: 2px solid var(--border); font-weight: 700; white-space: nowrap;
}
.data-table td { 
    padding: 0.85rem 1.15rem; color: var(--text); border-bottom: 1px solid var(--border); 
    font-size: 0.9rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 250px; 
}
.data-table th:last-child, .data-table td:last-child {
    position: sticky;
    right: 0;
    z-index: 10;
}
.data-table th:last-child {
    background: var(--surface-2);
    box-shadow: -4px 0 12px rgba(0,0,0,0.15);
}
.data-table td:last-child {
    background: inherit;
    box-shadow: -4px 0 12px rgba(0,0,0,0.15);
    border-left: 1px solid rgba(255,255,255,0.05); /* very subtle separation */
}

.data-table tbody tr { transition: background var(--t); background: var(--surface); animation: cmsFadeIn 0.3s cubic-bezier(0.16, 1, 0.3, 1); animation-fill-mode: both; }
.data-table tbody tr:last-child td { border-bottom: none; }
.data-table tbody tr:hover { background: var(--surface-2); }

/* Stagger animations for rows */
.data-table tbody tr:nth-child(1) { animation-delay: 0.05s; }
.data-table tbody tr:nth-child(2) { animation-delay: 0.1s; }
.data-table tbody tr:nth-child(3) { animation-delay: 0.15s; }
.data-table tbody tr:nth-child(4) { animation-delay: 0.2s; }
.data-table tbody tr:nth-child(5) { animation-delay: 0.25s; }
.data-table tbody tr:nth-child(n+6) { animation-delay: 0.3s; }

.badge-blue { background: rgba(var(--accent-rgb), 0.12); color: var(--accent); padding: 0.3rem 0.75rem; border-radius: 6px; font-weight: 600; font-size: 0.85rem; text-transform: capitalize; border: 1px solid rgba(var(--accent-rgb), 0.2); }

.btn-sm { padding: 0.3rem 0.65rem; font-size: 0.8rem; border-radius: 6px; font-weight: 500; cursor: pointer; text-decoration: none; }
.btn-secondary { background: var(--surface-2); color: var(--text-2); border: 1px solid var(--border); transition: all var(--t); }
.btn-secondary:hover { background: var(--surface-3); color: var(--text); border-color: var(--border-2); }
.btn-danger { background: var(--red-bg); color: var(--red); border: 1px solid var(--red-border); transition: all var(--t); }
.btn-danger:hover { background: var(--red); color: white; border-color: var(--red); }

#cms-confirm-modal {
    display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); backdrop-filter: blur(4px);
    z-index: 100000; align-items: center; justify-content: center; opacity: 0; transition: opacity 0.2s;
}
#cms-confirm-modal.active { display: flex !important; opacity: 1; }
#cms-confirm-box {
    background: var(--surface); border: 1px solid var(--border); border-radius: var(--r-xl); width: 90%; max-width: 400px;
    padding: 2rem 1.5rem; text-align: center; box-shadow: var(--shadow-lg); transform: translateY(15px); transition: all 0.2s;
}
#cms-confirm-modal.active #cms-confirm-box { transform: translateY(0); }
#cms-confirm-title { font-size: 1.15rem; font-weight: 700; color: var(--text); margin-bottom: 0.5rem; }
#cms-confirm-msg { color: var(--text-3); font-size: 0.9rem; margin-bottom: 2rem; line-height: 1.5; }
#cms-confirm-actions { display: flex; justify-content: center; gap: 1rem; }

@keyframes cmsFadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Kebab Menu Dropdown */
.kebab-menu { position: relative; cursor: pointer; padding: 0.2rem; display: inline-flex; align-items: center; justify-content: center; color: var(--text-3); transition: color var(--t); }
.kebab-menu:hover { color: var(--text); }
.kebab-dropdown {
    position: fixed; min-width: 140px; background: var(--surface); border: 1px solid var(--border);
    border-radius: var(--r); box-shadow: var(--shadow-lg); z-index: 999999;
    display: none; flex-direction: column; padding: 0.4rem; transform-origin: top right;
}
.kebab-dropdown.show { display: flex; animation: dropFadeIn 0.15s ease; }
@keyframes dropFadeIn { from{opacity:0;transform:scale(0.95)} to{opacity:1;transform:scale(1)} }
.kd-item {
    padding: 0.5rem 0.75rem; color: var(--text); font-size: 0.85rem; font-weight: 500;
    text-decoration: none; display: flex; align-items: center; gap: 0.5rem;
    border-radius: 6px; transition: background 0.2s; cursor: pointer;
}
.kd-item:hover { background: var(--surface-3); }
.kd-item.kd-danger { color: var(--red); }
.kd-item.kd-danger:hover { background: var(--red-bg); }

/* Info Modal */
#cms-info-modal {
    display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); backdrop-filter: blur(4px);
    z-index: 100000; align-items: center; justify-content: center; opacity: 0; transition: opacity 0.2s;
}
#cms-info-modal.active { display: flex !important; opacity: 1; }
#cms-info-box {
    background: var(--surface); border: 1px solid var(--border); border-radius: var(--r-xl); width: 90%; max-width: 500px;
    padding: 1.5rem; text-align: left; box-shadow: var(--shadow-lg); transform: translateY(15px); transition: all 0.2s;
    max-height: 85vh; overflow-y: auto; overflow-x: hidden;
}
#cms-info-modal.active #cms-info-box { transform: translateY(0); }
.info-row { border-bottom: 1px solid var(--border); padding: 0.75rem 0; }
.info-row:last-child { border-bottom: none; }
.info-lbl { font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-3); font-weight: 700; margin-bottom: 0.2rem; }
.info-val { font-size: 0.95rem; color: var(--text); word-wrap: break-word; }

@media(max-width: 768px) {
    .card { padding: 1rem; }
    .header-actions { flex-direction: column; align-items: stretch; }
    .search-form { max-width: none; }
    
    .data-table th:not(.mobile-col),
    .data-table td:not(.mobile-col) { display: none; }
    .data-table { min-width: 100%; }
    .table-wrap { overflow-x: visible; }
}
</style>
@endpush

@section('content')

@php $hasId = in_array('id', $columns, true); @endphp

@if(!$hasId)
<div style="background:var(--amber-bg);border:1px solid var(--amber-border);border-radius:8px;padding:0.85rem 1.1rem;margin-bottom:1.5rem;display:flex;align-items:center;gap:0.75rem;font-size:0.875rem;color:var(--amber);">
    <span>⚠️</span>
    <span>This table has no <code>id</code> column. <strong>Edit and Delete</strong> are disabled.
    To enable them, recreate the table with <code>id INT AUTO_INCREMENT PRIMARY KEY</code>.</span>
</div>
@endif

<div class="card">
    <div class="header-actions">
        <div>
            <span class="badge badge-blue">Table: {{ $table }}</span>
            <span style="color:var(--text-3);font-size:0.95rem;margin-left:0.75rem;font-weight:600;">
                {{ $rows->total() }} record{{ $rows->total() !== 1 ? 's' : '' }}
                @if(request()->has('search') && request('search') !== '')
                 <span style="color:var(--accent);">(Filtered)</span>
                @endif
            </span>
        </div>
        
        <form action="{{ route('admin.crud.index', $table) }}" method="GET" class="search-form">
            <input type="text" name="search" class="search-input" value="{{ request('search') }}" placeholder="Filter by any keyword...">
            <button type="submit" class="search-btn">Filter</button>
            @if(request()->has('search') && request('search') !== '')
                <a href="{{ route('admin.crud.index', $table) }}" class="search-btn-clear" title="Clear Filter">✕</a>
            @endif
        </form>
    </div>

    @if($rows->isEmpty())
        <div style="text-align:center;padding:3rem;color:var(--text-3);">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
                 style="margin:0 auto 1rem;display:block;opacity:0.3;">
                <path d="M9 3H5a2 2 0 0 0-2 2v4m6-6h10a2 2 0 0 1 2 2v4M9 3v18m0 0h10a2 2 0 0 0 2-2V9M9 21H5a2 2 0 0 1-2-2V9m0 0h18"/>
            </svg>
            <p style="font-size:0.9rem;">No records yet.</p>
            <a href="{{ route('admin.crud.create', $table) }}" class="btn-cms btn-cms-primary" style="margin-top:1rem;">Add First Record</a>
        </div>
    @else
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        @php $iHead = 0; @endphp
                        @foreach($columns as $col)
                            @if(in_array($col, ['id', 'document_url'])) @continue @endif
                            <th class="{{ $iHead === 0 ? 'mobile-col' : '' }}">{{ ucwords(str_replace('_', ' ', $col)) }}</th>
                            @php $iHead++; @endphp
                        @endforeach
                        <th class="mobile-col" style="width:40px;text-align:right;"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rows as $row)
                    @php $arr = (array) $row; @endphp
                    <tr>
                        @php $iCell = 0; @endphp
                        @foreach($columns as $col)
                        @if(in_array($col, ['id', 'document_url'])) @continue @endif
                        <td class="{{ $iCell === 0 ? 'mobile-col' : '' }}" title="{{ $arr[$col] ?? '' }}">
                            @php $val = $arr[$col] ?? ''; @endphp
                            @if(strlen($val) > 60)
                                {{ substr($val, 0, 60) }}…
                            @else
                                {{ $val ?: '—' }}
                            @endif
                        </td>
                        @php $iCell++; @endphp
                        @endforeach
                        <td class="mobile-col" style="text-align:right;">
                            @if($hasId)
                            <div class="kebab-menu" onclick="openGlobalDropdown(event, this)" 
                                 data-id="{{ $arr['id'] }}" 
                                 data-edit="{{ route('admin.crud.edit', [$table, $arr['id']]) }}" 
                                 data-info="{{ base64_encode(json_encode($arr)) }}">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="1.5"/><circle cx="12" cy="5" r="1.5"/><circle cx="12" cy="19" r="1.5"/></svg>
                            </div>
                            <form id="del-{{ $arr['id'] }}" action="{{ route('admin.crud.destroy', [$table, $arr['id']]) }}" method="POST" style="display:none;">
                                @csrf @method('DELETE')
                            </form>
                            @else
                            <span style="color:var(--text-4);font-size:0.75rem;">no id</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($rows->hasPages())
        <div style="display:flex;justify-content:center;gap:0.5rem;margin-top:1.5rem;flex-wrap:wrap;">
            {{ $rows->links() }}
        </div>
        @endif
    @endif
</div>

{{-- Custom Confirmation Modal --}}
<div id="cms-confirm-modal">
    <div id="cms-confirm-box">
        <div style="font-size:3rem;margin-bottom:0.5rem;">⚠️</div>
        <div id="cms-confirm-title">Confirm Deletion</div>
        <div id="cms-confirm-msg">Are you sure you want to permanently delete this record? This action cannot be undone.</div>
        <div id="cms-confirm-actions">
            <button class="btn btn-secondary" style="padding:0.6rem 1.25rem;cursor:pointer;border-radius:8px;" onclick="closeConfirmModal()">Cancel</button>
            <button class="btn btn-danger" style="padding:0.6rem 1.25rem;cursor:pointer;border-radius:8px;" id="cms-confirm-btn">Yes, Delete</button>
        </div>
    </div>
</div>

{{-- Info Modal --}}
<div id="cms-info-modal">
    <div id="cms-info-box">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;border-bottom:1px solid var(--border);padding-bottom:1rem;">
            <h3 style="margin:0;font-size:1.15rem;">Record Details</h3>
            <button onclick="closeInfoModal()" style="background:transparent;border:none;color:var(--text-3);cursor:pointer;padding:0.2rem;">✕</button>
        </div>
        <div id="cms-info-content"></div>
    </div>
</div>

{{-- Global Dropdown --}}
<div class="kebab-dropdown" id="global-kebab-menu">
    <div class="kd-item" id="gk-info">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg> Info
    </div>
    <a href="#" class="kd-item" id="gk-edit">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg> Edit
    </a>
    <div style="border-top:1px solid var(--border);margin:4px 0;"></div>
    <div class="kd-item kd-danger" id="gk-delete">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg> Delete
    </div>
</div>

@endsection

@push('scripts')
<script>
let currentDeleteFormId = null;
function openDeleteConfirm(formId) {
    currentDeleteFormId = formId;
    document.getElementById('cms-confirm-modal').classList.add('active');
}
function closeConfirmModal() {
    document.getElementById('cms-confirm-modal').classList.remove('active');
    currentDeleteFormId = null;
}
document.getElementById('cms-confirm-btn').addEventListener('click', function() {
    if(currentDeleteFormId) document.getElementById(currentDeleteFormId).submit();
});
document.getElementById('cms-confirm-modal').addEventListener('click', function(e) {
    if(e.target === this) closeConfirmModal();
});

// Dropdown handling
let gkMenu = null;

function openGlobalDropdown(e, el) {
    e.stopPropagation();
    if (!gkMenu) gkMenu = document.getElementById('global-kebab-menu');
    if (!gkMenu) return;
    
    // Check if already open on this element
    if (gkMenu.classList.contains('show') && gkMenu.dataset.sourceId === el.dataset.id) {
        gkMenu.classList.remove('show');
        return;
    }
    
    let infoData = {};
    try { infoData = JSON.parse(atob(el.getAttribute('data-info') || '')); } catch(e) { console.error('Data parse error', e); }
    
    // Bind actions
    document.getElementById('gk-info').onclick = () => { gkMenu.classList.remove('show'); openInfoModal(infoData); };
    document.getElementById('gk-edit').href = el.dataset.edit;
    document.getElementById('gk-delete').onclick = () => { gkMenu.classList.remove('show'); openDeleteConfirm('del-' + el.dataset.id); };
    
    gkMenu.dataset.sourceId = el.dataset.id;
    gkMenu.classList.add('show');
    
    // Position
    const rect = el.getBoundingClientRect();
    gkMenu.style.top = (rect.bottom + 5) + 'px';
    gkMenu.style.left = 'auto'; // Reset left
    gkMenu.style.right = Math.max(0, (window.innerWidth - rect.right)) + 'px';
}

document.addEventListener('click', (e) => {
    if(gkMenu && !gkMenu.contains(e.target)) gkMenu.classList.remove('show');
});

// Info Modal
function openInfoModal(data) {
    let html = '';
    for(let key in data) {
        let val = data[key];
        if(val === null) val = '<i>null</i>';
        else if(val === '') val = '<i>empty</i>';
        
        let label = key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
        html += `<div class="info-row"><div class="info-lbl">${label}</div><div class="info-val">${val}</div></div>`;
    }
    document.getElementById('cms-info-content').innerHTML = html;
    document.getElementById('cms-info-modal').classList.add('active');
}
function closeInfoModal() {
    document.getElementById('cms-info-modal').classList.remove('active');
}
document.getElementById('cms-info-modal').addEventListener('click', function(e) {
    if(e.target === this) closeInfoModal();
});
</script>
@endpush

