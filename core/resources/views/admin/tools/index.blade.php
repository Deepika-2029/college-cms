@extends('admin.layout')

@section('title', 'Tools & Health')
@section('page-title', 'Tools & System Health')

@push('styles')
<link rel="stylesheet" id="page-css" href="{{ route('admin.page-asset', ['tools/style.css']) }}">
@endpush

@section('content')

{{-- System Health --}}
<div class="card" style="margin-bottom:2rem;">
    <div class="card-title">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
        System Health Overview
    </div>
    <div class="health-grid">
        @php
            $checks = [
                ['label' => 'PHP '.PHP_VERSION,         'ok' => true,                   'note' => 'Current Runtime'],
                ['label' => 'Laravel '.app()->version(), 'ok' => true,                  'note' => 'Application Framework'],
                ['label' => 'Database Status',           'ok' => true,                   'note' => strtoupper($stats['db_driver']).' · '.$stats['db_size']],
                ['label' => 'Storage Directory',         'ok' => $stats['storage_ok'],   'note' => $stats['storage_ok'] ? 'Writable' : 'Not writable — verify permissions'],
                ['label' => 'Media Directory',           'ok' => $stats['media_dir_ok'], 'note' => $stats['media_dir_ok'] ? 'Writable' : 'Not writable'],
                ['label' => 'Cache Driver',              'ok' => true,                   'note' => strtoupper($stats['cache_driver'])],
                ['label' => 'XML Sitemap',               'ok' => $stats['sitemap_exists'],'note' => $stats['sitemap_exists'] ? '<a href="'.$stats['sitemap_url'].'" target="_blank">View Sitemap →</a>' : 'Not generated yet'],
            ];
        @endphp
        @foreach($checks as $c)
        <div class="health-item">
            <div class="health-dot {{ $c['ok'] ? 'ok' : 'fail' }}"></div>
            <div>
                <div style="font-weight:700;font-size:0.85rem;color:var(--text);margin-bottom:0.15rem;">{{ $c['label'] }}</div>
                <div style="font-size:0.75rem;color:var(--text-3);">{!! $c['note'] !!}</div>
            </div>
        </div>
        @endforeach
    </div>
</div>

{{-- Stats Bar --}}
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:1.25rem;margin-bottom:2rem;">
    @foreach([
        ['label' => 'Total Pages',   'value' => $stats['page_count'],   'icon' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/>'],
        ['label' => 'Media Assets',  'value' => $stats['media_count'],  'icon' => '<rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/>'],
        ['label' => 'Audit Log Rows','value' => $stats['audit_count'],  'icon' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/>'],
        ['label' => 'Media Growth',  'value' => $stats['media_size'],   'icon' => '<ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/>'],
        ['label' => 'DB Usage',      'value' => $stats['db_size'],      'icon' => '<rect x="4" y="4" width="16" height="16" rx="2" ry="2"/><rect x="9" y="9" width="6" height="6"/><line x1="9" y1="1" x2="9" y2="4"/><line x1="15" y1="1" x2="15" y2="4"/><line x1="9" y1="20" x2="9" y2="23"/><line x1="15" y1="20" x2="15" y2="23"/><line x1="20" y1="9" x2="23" y2="9"/><line x1="20" y1="14" x2="23" y2="14"/><line x1="1" y1="9" x2="4" y2="9"/><line x1="1" y1="14" x2="4" y2="14"/>'],
    ] as $s)
    <div class="stat-box">
        <div class="stat-box-icon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">{!! $s['icon'] !!}</svg></div>
        <div>
            <div class="stat-box-value">{{ $s['value'] }}</div>
            <div class="stat-box-label">{{ $s['label'] }}</div>
        </div>
    </div>
    @endforeach
</div>

{{-- Tools Grid --}}
<div class="tools-grid">

    {{-- Clear Cache --}}
    <div class="tool-card">
        <div class="tool-header">
            <div class="tool-icon" style="color:var(--blue);background:var(--blue-bg);border-color:var(--blue-border);"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12a9 9 0 109-9 9.75 9.75 0 00-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg></div>
            <div>
                <div class="tool-title">Clear Cache</div>
                <div class="tool-desc">Flushes application views and cache.</div>
            </div>
        </div>
        <form action="{{ route('admin.tools.clear-cache') }}" method="POST" style="margin-top:auto;">
            @csrf
            <button type="submit" class="btn-cms btn-cms-primary w-100" style="justify-content:center;">Clear Cache Now</button>
        </form>
    </div>

    {{-- Generate Sitemap --}}
    <div class="tool-card">
        <div class="tool-header">
            <div class="tool-icon" style="color:var(--green);background:var(--green-bg);border-color:var(--green-border);"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 21L15 15M3 9h6M9 3v6M14.9 3.09C15.93 2.45 17.5 2 19 2s3.07.45 4.1 1.09M2.1 14.9C3.13 15.54 4.7 16 6.2 16s3.07-.46 4.1-1.1M14 21c-2.34-1.39-7.66-1.39-10 0"/></svg></div>
            <div>
                <div class="tool-title">Generate Sitemap</div>
                <div class="tool-desc">Creates <code>/sitemap.xml</code> from pages.</div>
            </div>
        </div>
        @if($stats['sitemap_exists'])
        <div class="tool-meta">Last generated — <a href="{{ $stats['sitemap_url'] }}" target="_blank">View sitemap →</a></div>
        @endif
        <form action="{{ route('admin.tools.sitemap') }}" method="POST" style="margin-top:auto;">
            @csrf
            <button type="submit" class="btn-cms btn-cms-secondary w-100" style="justify-content:center;">Generate Sitemap</button>
        </form>
    </div>


    {{-- Clean Orphaned Media --}}
    <div class="tool-card">
        <div class="tool-header">
            <div class="tool-icon" style="color:var(--amber);background:var(--amber-bg);border-color:var(--amber-border);"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2M10 11v6M14 11v6"/></svg></div>
            <div>
                <div class="tool-title">Clean Missing Media</div>
                <div class="tool-desc">Removes files on disk with no DB record.</div>
            </div>
        </div>
        <div class="tool-meta">
            <strong>{{ $stats['media_count'] }}</strong> DB records · <strong>{{ $stats['media_size'] }}</strong> disk
        </div>
        <form action="{{ route('admin.tools.clean-media') }}" method="POST" style="margin-top:auto;">
            @csrf
            <button type="button" class="btn-cms btn-cms-warning w-100" style="justify-content:center;"
                onclick="cmsConfirm('Remove orphaned media files from disk?', 'This deletes physical files that are missing from the media manager DB.', 'Clean files').then(ok=>{if(ok)this.closest('form').submit()})">
                Clean Orphaned Files
            </button>
        </form>
    </div>

    {{-- Bulk Image Optimizer --}}
    <div class="tool-card">
        <div class="tool-header">
            <div class="tool-icon" style="color:var(--emerald);background:rgba(var(--green-rgb),0.1);border-color:rgba(var(--green-rgb),0.2);"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg></div>
            <div>
                <div class="tool-title">Bulk Image Optimizer</div>
                <div class="tool-desc">Retroactively generate responsive WebP variants for all local images.</div>
            </div>
        </div>
        <div class="tool-meta" id="optimize-meta">
            <strong>{{ $stats['unoptimized'] }}</strong> unoptimized local images found.
        </div>
        <div style="margin-top:auto;">
            <button type="button" class="btn-cms btn-cms-primary w-100" style="justify-content:center;" id="btn-optimize-all"
                onclick="startBulkOptimization()" {{ $stats['unoptimized'] == 0 ? 'disabled' : '' }}>
                {{ $stats['unoptimized'] > 0 ? 'Optimize All Images' : 'All Optimized 🎉' }}
            </button>
            <div id="optimize-progress-container" style="display:none; margin-top:10px;">
                <div style="font-size:12px; color:var(--text-3); margin-bottom:5px;" id="optimize-status">Processing...</div>
                <div style="width:100%;background:var(--surface-3);border-radius:4px;height:6px;overflow:hidden;">
                    <div id="optimize-progress-bar" style="height:100%;width:0%;background:var(--emerald);transition:width 0.3s;border-radius:4px;"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- DB Export --}}
    <div class="tool-card">
        <div class="tool-header">
            <div class="tool-icon" style="color:var(--text);background:var(--surface-3);border-color:var(--border-2);"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4M7 10l5 5 5-5M12 15V3"/></svg></div>
            <div>
                <div class="tool-title">Export Database</div>
                <div class="tool-desc">Download a full SQL dump dump of all tables.</div>
            </div>
        </div>
        <div style="margin-top:auto;">
            <a href="{{ route('admin.tools.export-db') }}" class="btn-cms btn-cms-secondary w-100" style="justify-content:center;">
                ⬇ Download SQL Backup
            </a>
        </div>
    </div>

    {{-- Clear Log --}}
    <div class="tool-card">
        <div class="tool-header">
            <div class="tool-icon" style="color:var(--red);background:var(--red-bg);border-color:var(--red-border);"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="9" y1="15" x2="15" y2="15"/></svg></div>
            <div>
                <div class="tool-title">Clear Log File</div>
                <div class="tool-desc">Empties the main Laravel server log file.</div>
            </div>
        </div>
        <div class="tool-meta">Current size: <strong>{{ $stats['log_size'] }}</strong></div>
        <form action="{{ route('admin.tools.clear-log') }}" method="POST" style="margin-top:auto;">
            @csrf
            <button type="button" class="btn-cms btn-cms-danger w-100" style="justify-content:center;"
                onclick="cmsConfirm('Clear the log file?', 'All existing log entries will be immediately deleted.', 'Clear log').then(ok=>{if(ok)this.closest('form').submit()})">
                Clear Log File
            </button>
        </form>
    </div>

    {{-- Sync from DB (Recovery) --}}
    <div class="tool-card">
        <div class="tool-header">
            <div class="tool-icon" style="color:var(--accent);background:rgba(var(--accent-rgb),.12);border-color:rgba(var(--accent-rgb),.25);">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/>
                    <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/>
                </svg>
            </div>
            <div>
                <div class="tool-title">Sync Pages from DB</div>
                <div class="tool-desc">Re-publish all pages from the database to static files. Use this to recover after an FTP wipe.</div>
            </div>
        </div>
        <div class="tool-meta" style="color:var(--accent);font-weight:600;">⚠️ Super Admin only — regenerates all live HTML files.</div>
        <div style="margin-top:auto;">
            <button type="button" class="btn-cms w-100" id="btn-sync-db"
                style="justify-content:center;background:var(--accent);color:#fff;border-color:var(--accent);"
                onclick="syncFromDb()">
                🔄 Recover Pages from Database
            </button>
            <div id="sync-progress-container" style="display:none;margin-top:10px;">
                <div style="font-size:12px;color:var(--text-3);margin-bottom:5px;" id="sync-status">Publishing...</div>
                <div style="width:100%;background:var(--surface-3);border-radius:4px;height:6px;overflow:hidden;">
                    <div id="sync-progress-bar" style="height:100%;width:5%;background:var(--accent);transition:width 0.4s;border-radius:4px;"></div>
                </div>
            </div>
        </div>
    </div>
    <div class="tool-card" style="grid-column: 1 / -1; display:grid; grid-template-columns: 280px 1fr; padding:0; overflow:hidden;">
        <div style="padding:1.5rem; background:var(--surface-2); border-right:1px solid var(--border);">
            <div class="tool-icon" style="color:var(--text);background:var(--surface);border-color:var(--border);margin-bottom:1rem;"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg></div>
            <div class="tool-title">Environment Info</div>
            <div class="tool-desc">Quick reference for the application runtime.</div>
        </div>
        <div style="padding:1.5rem; display:grid; grid-template-columns: repeat(auto-fill, minmax(280px,1fr)); gap:1rem;">
            @foreach([
                ['PHP Version',     PHP_VERSION],
                ['Laravel Core',    app()->version()],
                ['Deployment Env',  app()->environment()],
                ['Database',        strtoupper(config('database.default'))],
                ['Cache Store',     strtoupper($stats['cache_driver'])],
                ['Media Store',     strtoupper(setting('media_driver','local'))],
            ] as [$label, $value])
            <div class="stat-row" style="margin:0;">
                <span class="label">{{ $label }}</span>
                <code class="value" style="background:var(--surface);border:1px solid var(--border);padding:0.2rem 0.5rem;border-radius:4px;font-size:0.75rem;">{{ $value }}</code>
            </div>
            @endforeach
        </div>
    </div>

</div>

{{-- Log Viewer --}}
<div class="card">
    <div class="card-header" style="flex-wrap:wrap;gap:1rem;">
        <h2 class="card-title" style="margin:0;"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="4 17 10 11 4 5"/><line x1="12" y1="19" x2="20" y2="19"/></svg> Server Log Viewer</h2>
        <div style="display:flex;gap:.5rem;align-items:center;flex-wrap:wrap;margin-left:auto;">
            <span style="font-size:.78rem;color:var(--text-3);margin-right:0.5rem;font-weight:600;">{{ $stats['log_size'] }}</span>
            <div style="display:flex;gap:0.3rem;">
                <button onclick="toggleLogLevel('error')"   class="log-filter-btn active" data-level="error">Error</button>
                <button onclick="toggleLogLevel('warning')" class="log-filter-btn active" data-level="warning">Warn</button>
                <button onclick="toggleLogLevel('info')"    class="log-filter-btn active" data-level="info">Info</button>
                <button onclick="toggleLogLevel('debug')"   class="log-filter-btn active" data-level="debug">Debug</button>
            </div>
            <div style="width:1px;height:24px;background:var(--border);margin:0 0.5rem;"></div>
            <button onclick="copyAllLogs()" class="btn-cms btn-cms-secondary btn-cms-sm" title="Copy visible rows">📋 Copy View</button>
            <button onclick="copyErrorsOnly()" class="btn-cms btn-cms-danger btn-cms-sm" title="Copy errors">⚠ Copy Errors</button>
        </div>
    </div>

    @if(empty($logs))
        <div class="empty-state">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="9" y1="15" x2="15" y2="15"/></svg>
            <div class="empty-state-title">Log file is empty</div>
            <div class="empty-state-desc">No entries found in laravel.log</div>
        </div>
    @else
    <div class="log-viewer-container">
        <div class="log-viewer" id="log-viewer">
            @foreach($logs as $line)
            @php
                $level = 'default';
                if (str_contains($line, '.ERROR') || str_contains($line, '.CRITICAL') || str_contains($line, '.EMERGENCY')) $level = 'error';
                elseif (str_contains($line, '.WARNING')) $level = 'warning';
                elseif (str_contains($line, '.INFO'))    $level = 'info';
                elseif (str_contains($line, '.DEBUG'))   $level = 'debug';
            @endphp
            <div class="log-line {{ $level }}" data-level="{{ $level }}">{{ $line }}</div>
            @endforeach
        </div>
    </div>
    @endif
</div>

@endsection

@push('scripts')
<script>
// Sync from DB — Page Recovery
async function syncFromDb() {
    const confirmed = await cmsConfirm(
        'Recover All Pages from Database?',
        'This will re-generate ALL static HTML page files from the database. It will overwrite any manually edited files. Continue?',
        'Recover Pages'
    );
    if (!confirmed) return;

    const btn      = document.getElementById('btn-sync-db');
    const prog     = document.getElementById('sync-progress-container');
    const bar      = document.getElementById('sync-progress-bar');
    const status   = document.getElementById('sync-status');

    btn.disabled = true;
    btn.textContent = '⏳ Recovering...';
    prog.style.display = 'block';
    bar.style.width = '10%';
    status.textContent = 'Connecting to database...';

    try {
        const fd = new FormData();
        fd.append('_token', document.querySelector('meta[name="csrf-token"]').content);

        const r = await fetch('{{ route('admin.tools.sync-from-db') }}', {
            method: 'POST', body: fd, headers: { 'Accept': 'application/json' }
        });
        const data = await r.json();

        if (!r.ok) throw new Error(data.message || 'Server error');

        bar.style.width = '100%';
        bar.style.background = 'var(--emerald)';
        status.textContent = data.message;
        btn.textContent = '✅ Recovery Complete';

        if (data.failed && data.failed.length > 0) {
            cmsToast('Recovered ' + data.published + '/' + data.total + ' pages. Some failed — check log.', 'warning', null, 7000);
        } else {
            cmsToast(data.message, 'success', null, 6000);
        }
    } catch(e) {
        bar.style.background = 'var(--red)';
        status.textContent = 'Error: ' + e.message;
        btn.disabled = false;
        btn.textContent = '🔄 Retry Recovery';
        cmsToast('Recovery failed: ' + e.message, 'error');
    }
}

// Bulk Optimizer Logic
async function optimizeAll() {
    if(!(await cmsConfirm('Optimize Media', 'This process will optimize all unsupported local images into fast WebP variants. This may take time. Continue?', 'Optimize'))) return;
    
    const btn = document.getElementById('btn-optimize-all');
    const progContainer = document.getElementById('optimize-progress-container');
    const progBar = document.getElementById('optimize-progress-bar');
    const statusTxt = document.getElementById('optimize-status');
    const metaTxt = document.getElementById('optimize-meta');
    
    let totalUnoptimized = {{ (int) $stats['unoptimized'] }};
    if(totalUnoptimized === 0) return;
    
    let processedSoFar = 0;
    
    btn.disabled = true;
    btn.textContent = 'Optimizing...';
    progContainer.style.display = 'block';
    
    const processBatch = async () => {
        try {
            const formData = new FormData();
            formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);
            
            const r = await fetch('{{ route('admin.tools.optimize-media') }}', {
                method: 'POST',
                body: formData,
                headers: { 'Accept': 'application/json' }
            });
            const data = await r.json();
            
            if (!r.ok) throw new Error(data.error || 'Server error');
            
            processedSoFar += data.processed + data.failed;
            
            // Calculate progress relative to the starting total
            // (Note: data.remaining is the true db remaining, but we use it to finish)
            const remaining = data.remaining;
            
            let percentTag = processedSoFar / totalUnoptimized;
            if(percentTag > 1) percentTag = 1;
            
            progBar.style.width = (percentTag * 100) + '%';
            statusTxt.textContent = `Processed ${processedSoFar} / ${totalUnoptimized} (Failed: ${data.failed})`;
            metaTxt.innerHTML = `<strong>${remaining}</strong> unoptimized local images found.`;
            
            if (remaining > 0 && (data.processed > 0 || data.failed > 0)) {
                // Next batch
                setTimeout(processBatch, 500);
            } else {
                // Done
                progBar.style.background = 'var(--emerald)';
                statusTxt.textContent = 'Optimization Complete! 🎉';
                btn.textContent = 'All Optimized 🎉';
                cmsToast('Bulk optimization completed successfully', 'success');
            }
        } catch(e) {
            statusTxt.textContent = 'Error: ' + e.message;
            statusTxt.style.color = 'var(--red)';
            progBar.style.background = 'var(--red)';
            btn.disabled = false;
            btn.textContent = 'Resume Optimization';
            cmsToast('Optimization stopped due to error', 'error');
        }
    };
    
    processBatch();
}

// Log level filter
const activeFilters = new Set(['error', 'warning', 'info', 'debug']);

function toggleLogLevel(level) {
    const btn = document.querySelector(`[data-level="${level}"]`);
    if (activeFilters.has(level)) {
        activeFilters.delete(level);
        btn.classList.remove('active');
    } else {
        activeFilters.add(level);
        btn.classList.add('active');
    }
    document.querySelectorAll('#log-viewer .log-line').forEach(line => {
        const lvl = line.dataset.level;
        line.style.display = (lvl === 'default' || activeFilters.has(lvl)) ? '' : 'none';
    });
}

function copyAllLogs() {
    const lines = [...document.querySelectorAll('#log-viewer .log-line')]
        .filter(l => l.style.display !== 'none')
        .map(l => l.textContent.trim())
        .join('\n');
    if (!lines) return cmsToast('No logs currently visible to copy', 'warning');
    
    // Copy fallback logic
    const execCopy = (t) => {
        const ta = document.createElement('textarea');
        ta.value = t; document.body.appendChild(ta); ta.select(); document.execCommand('copy'); document.body.removeChild(ta);
    };
    if(navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(lines).then(()=>cmsToast('Logs copied to clipboard', 'success')).catch(()=> { execCopy(lines); cmsToast('Logs copied', 'success'); });
    } else {
        execCopy(lines); cmsToast('Logs copied', 'success');
    }
}

function copyErrorsOnly() {
    const lines = [...document.querySelectorAll('#log-viewer .log-line.error')]
        .filter(l => l.style.display !== 'none')
        .map(l => l.textContent.trim())
        .join('\n');
    if (!lines) return cmsToast('No error lines found', 'error');
    
    // Copy fallback logic
    const execCopy = (t) => {
        const ta = document.createElement('textarea');
        ta.value = t; document.body.appendChild(ta); ta.select(); document.execCommand('copy'); document.body.removeChild(ta);
    };
    if(navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(lines).then(()=>cmsToast('Error logs copied to clipboard', 'success')).catch(()=> { execCopy(lines); cmsToast('Error logs copied', 'success'); });
    } else {
        execCopy(lines); cmsToast('Error logs copied', 'success');
    }
}

// Add warning button style if not in core css
if(!document.querySelector('style#tools-btn-fix')) {
    const style = document.createElement('style');
    style.id = 'tools-btn-fix';
    style.innerHTML = `
        .btn-cms-warning { background: var(--amber); color: #fff; border-color: var(--amber); }
        .btn-cms-warning:hover { background: #d97706; transform: translateY(-1px); }
        @media (max-width: 900px) {
            .tool-card { grid-template-columns: 1fr; }
            .tool-card[style*="column"] { grid-template-columns: 1fr !important; }
            .tool-card[style*="column"] > div:first-child { border-right: none !important; border-bottom: 1px solid var(--border); }
        }
    `;
    document.head.appendChild(style);
}
</script>
@endpush
