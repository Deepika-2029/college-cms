@extends('admin.layout')
@section('title','API & Database Integration')
@section('page-title','API Keys & Integrations')

@section('topbar-actions')
<button onclick="document.getElementById('create-modal').classList.add('open')" class="btn-cms btn-cms-primary" style="background:var(--green); border:none; box-shadow:0 4px 12px var(--green-border); padding:8px 16px; border-radius:8px; display:flex; align-items:center; gap:8px;">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4"/></svg>
    Generate New Key
</button>
@endsection

@push('styles')
<style>
    .api-hero {
        background: color-mix(in srgb, var(--surface) 20%, var(--surface-2));
        border-radius: 16px; border: 1px solid var(--border); padding: 24px;
        margin-bottom: 24px; position: relative; overflow: hidden; box-shadow: var(--shadow-sm);
    }
    .api-hero::after {
        content: ""; position: absolute; right: -50px; top: -50px; width: 300px; height: 300px;
        background: radial-gradient(circle, rgba(var(--accent-rgb),0.15) 0%, transparent 70%); border-radius: 50%;
    }
    .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 30px; }
    .stat-card {
        background: var(--surface); border-radius: 12px; padding: 20px;
        border: 1px solid var(--border); box-shadow: var(--shadow-xs);
        display: flex; flex-direction: column; gap: 8px;
    }
    .stat-label { font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-3); }
    .stat-val { font-size: 28px; font-weight: 800; color: var(--text); line-height: 1; }
    
    .api-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 24px; }
    @media(max-width: 900px) { .api-grid { grid-template-columns: 1fr; } }
    
    .keys-panel { background: var(--surface); border-radius: 16px; border: 1px solid var(--border); overflow: hidden; box-shadow: var(--shadow-xs); }
    .keys-header { padding: 20px; border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; }
    .keys-title { font-size: 16px; font-weight: 600; color: var(--text); margin:0; }
    
    .key-list { display: flex; flex-direction: column; }
    .key-item { padding: 16px 20px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; transition: 0.2s; }
    .key-item:last-child { border-bottom: none; }
    .key-item:hover { background: color-mix(in srgb, var(--surface) 50%, var(--surface-2)); }
    
    .key-info { display: flex; flex-direction: column; gap: 4px; }
    .key-name { font-weight: 600; font-size: 14px; color: var(--text); }
    .key-meta { font-size: 12px; color: var(--text-3); display: flex; gap: 12px; align-items: center; }
    .key-badge { padding: 2px 8px; border-radius: 4px; font-size: 10px; font-weight: 700; text-transform: uppercase; }
    .badge-active { background: var(--green-bg); color: var(--green); }
    .badge-inactive { background: var(--red-bg); color: var(--red); }
    
    .key-actions { display: flex; gap: 8px; align-items: center; }
    .action-btn { background: var(--surface-2); border: 1px solid var(--border); border-radius: 6px; padding: 6px 12px; color: var(--text-2); font-size: 12px; font-weight: 600; cursor: pointer; transition: 0.2s; }
    .action-btn:hover { background: var(--surface-3); color: var(--text); }
    .action-btn.delete:hover { background: var(--red-bg); color: var(--red); border-color: var(--red-border); }

    .doc-panel { background: var(--surface); border-radius: 16px; border: 1px solid var(--border); padding: 20px; box-shadow: var(--shadow-xs); }
    .doc-title { font-size: 16px; font-weight: 600; color: var(--text); margin-bottom: 16px; }
    .code-block { background: var(--surface-2); border-radius: 8px; padding: 16px; margin-bottom: 16px; overflow-x: auto; border: 1px solid var(--border); font-family: 'Consolas', monospace; font-size: 12px; color: var(--text-2); }
    .copy-code-btn { background: transparent; border: 1px solid var(--border); color: var(--text); border-radius: 4px; padding: 4px 8px; font-size: 10px; cursor: pointer; position: absolute; right: 12px; top: 12px; }

</style>
@endpush

@section('content')

@if(session('new_key'))
<div style="background: linear-gradient(135deg, var(--green), var(--green-border)); border-radius: 12px; padding: 20px; margin-bottom: 24px; box-shadow: 0 10px 25px rgba(var(--green-rgb), 0.2); color: var(--text);">
    <h3 style="margin: 0 0 12px; font-size: 18px; font-weight: 700; display:flex; align-items:center; gap:8px;">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        New API Key Generated Successfully!
    </h3>
    <p style="margin: 0 0 16px; font-size: 14px; opacity: 0.9;">Please copy your API key now. For your security, it will not be shown again.</p>
    <div style="display:flex; gap: 12px; align-items:center;">
        <code id="new-key-box" style="background: var(--surface-3); padding: 12px 16px; border-radius: 8px; font-family: monospace; font-size: 16px; flex: 1; border: 1px solid var(--border); user-select:all;">{{ session('new_key') }}</code>
        <button onclick="copyRawKey()" id="raw-copy-btn" class="btn-cms btn-cms-primary" style="padding: 12px 24px; font-size: 1.05rem;">Copy Key</button>
    </div>
</div>
<script>
function copyRawKey() {
    navigator.clipboard.writeText(document.getElementById('new-key-box').textContent);
    document.getElementById('raw-copy-btn').textContent = 'Copied!';
    setTimeout(() => document.getElementById('raw-copy-btn').textContent = 'Copy Key', 2000);
}
</script>
@endif

<div class="api-hero">
    <h2 style="margin:0 0 8px; color:var(--text); font-size:24px;">Frontend Data Integrations</h2>
    <p style="margin:0; color:var(--text-3); font-size:14px; max-width:600px; line-height:1.5;">
        Easily bind your database tables to frontend Visual Builder pages using these Read-Only API Keys. 
        You can securely fetch database records directly into your customized UI components.
    </p>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <span class="stat-label">Active Keys</span>
        <span class="stat-val" style="color:var(--green);">{{ $keys->where('is_active', true)->count() }} <span style="font-size:12px; color:var(--text-3);">/ {{ $keys->count() }}</span></span>
    </div>
    <div class="stat-card">
        <span class="stat-label">Total API Requests</span>
        <span class="stat-val" style="color:var(--accent);">{{ number_format($keys->sum('request_count')) }}</span>
    </div>
    <div class="stat-card">
        <span class="stat-label">Bound Tables</span>
        <span class="stat-val" style="color:var(--amber);">{{ $keys->unique('table_name')->count() }}</span>
    </div>
</div>

<div class="api-grid">
    <div class="keys-panel">
        <div class="keys-header">
            <h3 class="keys-title">Active API Keys</h3>
            <span style="font-size:12px; color:var(--text-3);">Manage and monitor usage</span>
        </div>
        <div class="key-list">
            @forelse($keys as $key)
            <div class="key-item">
                <div class="key-info">
                    <div style="display:flex; align-items:center; gap:8px;">
                        <span class="key-name">{{ $key->name }}</span>
                        @if($key->is_active) <span class="key-badge badge-active">Active</span>
                        @else <span class="key-badge badge-inactive">Disabled</span> @endif
                        <span style="font-size:11px; font-family:monospace; background:var(--surface-3); padding:2px 6px; border-radius:4px; color:var(--accent-l);">{{ $key->key_prefix }}••••••••</span>
                    </div>
                    <div class="key-meta">
                        <span><strong>Table:</strong> <code style="color:var(--amber);">{{ $key->table_name }}</code></span>
                        @if($key->data_limit)<span><strong>Limit:</strong> {{ $key->data_limit }}</span>@endif
                        @if($key->data_sort)<span><strong>Sort:</strong> {{ $key->data_sort }}</span>@endif
                        <span><strong>Usage:</strong> {{ number_format($key->request_count) }} reqs</span>
                        <span><strong>Last Used:</strong> {{ $key->last_used_at ? $key->last_used_at->diffForHumans() : 'Never' }}</span>
                    </div>
                </div>
                <div class="key-actions">
                    <button class="action-btn" onclick="copyExistingKey('{{ $key->id }}', '{{ $key->decrypted_key }}')" id="copy-btn-{{ $key->id }}" style="background:rgba(var(--accent-rgb),0.1); color:var(--accent); border:1px solid rgba(var(--accent-rgb),0.2);">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:inline; margin-right:4px; vertical-align:-2px;"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                        Copy Key
                    </button>
                    <form method="POST" action="{{ route('admin.api-keys.toggle', $key) }}" style="margin:0;">
                        @csrf
                        <button class="action-btn">{{ $key->is_active ? 'Disable' : 'Enable' }}</button>
                    </form>
                    <form method="POST" action="{{ route('admin.api-keys.destroy', $key) }}" onsubmit="event.preventDefault(); cmsConfirm('Revoke API Key', 'Revoke this API Key permanently?', 'Revoke').then(ok => { if(ok) this.submit(); });" style="margin:0;">
                        @csrf @method('DELETE')
                        <button class="action-btn delete">Revoke</button>
                    </form>
                </div>
            </div>
            @empty
            <div style="padding:40px; text-align:center; color:var(--text-3);">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-bottom:12px; opacity:0.5;"><path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4"/></svg>
                <p style="margin:0; font-size:14px;">No API Keys generated yet.</p>
                <p style="margin:4px 0 0; font-size:12px;">Create tables in the Database Builder or generate one above to securely fetch data.</p>
            </div>
            @endforelse
        </div>
    </div>

    <div class="doc-panel" style="position:sticky;top:80px">
        <h3 class="doc-title" style="display:flex;align-items:center;gap:8px">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--accent)" stroke-width="2"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
            Integration Guide
        </h3>

        {{-- Method tabs --}}
        <div style="display:flex;gap:6px;margin-bottom:16px;background:var(--surface-2);border-radius:8px;padding:4px">
            <button id="tab-token" onclick="switchGuide('token')" style="flex:1;padding:7px;border:none;border-radius:6px;font-size:12px;font-weight:700;cursor:pointer;background:var(--accent);color:#fff;transition:.2s">🔐 Token (Recommended)</button>
            <button id="tab-key"   onclick="switchGuide('key')"   style="flex:1;padding:7px;border:none;border-radius:6px;font-size:12px;font-weight:600;cursor:pointer;background:transparent;color:var(--text-3);transition:.2s">🗝 Legacy Key</button>
        </div>

        {{-- TOKEN GUIDE --}}
        <div id="guide-token">
            <div style="background:rgba(var(--green-rgb),.08);border:1px solid var(--green-border);border-radius:8px;padding:10px 14px;margin-bottom:14px;display:flex;gap:10px;align-items:flex-start">
                <span style="font-size:18px;line-height:1">🛡</span>
                <div>
                    <div style="font-size:12px;font-weight:700;color:var(--green);margin-bottom:2px">Zero Key Exposure</div>
                    <div style="font-size:11px;color:var(--text-3);line-height:1.5">Tokens expire in 15 min. No permanent secret ever lives in your HTML or JS. Even if someone reads your source code — they get nothing useful.</div>
                </div>
            </div>

            <div style="font-size:12px;font-weight:700;color:var(--text-2);margin-bottom:8px">How it works</div>
            <div style="display:flex;flex-direction:column;gap:6px;margin-bottom:14px">
                @foreach(['1. Your page calls /api/token/{table} (no secret needed)','2. Server returns a 15-min signed token scoped to that table','3. Your page uses the token to fetch data','4. Token expires → next page load gets a fresh one'] as $i=>$step)
                <div style="display:flex;align-items:flex-start;gap:8px;font-size:11px;color:var(--text-3)">
                    <span style="background:rgba(var(--accent-rgb),.12);color:var(--accent);border-radius:50%;width:18px;height:18px;display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:800;flex-shrink:0">{{$i+1}}</span>
                    {{$step}}
                </div>
                @endforeach
            </div>

            {{-- Step 1 snippet --}}
            <div style="font-size:11px;font-weight:700;color:var(--text-3);text-transform:uppercase;letter-spacing:.05em;margin-bottom:6px">Step 1 — Link your page in Database Builder</div>
            <div style="background:rgba(var(--accent-rgb),.06);border:1px solid rgba(var(--accent-rgb),.15);border-radius:8px;padding:10px 14px;margin-bottom:12px;font-size:11px;color:var(--text-2);line-height:1.6">
                Go to <strong>Database Builder → Active Tables → Schema panel</strong> for your table.<br>
                Under <em>"Token Access (Page Links)"</em>, type your page slug (e.g. <code>about</code> or <code>notices</code>) and click <strong>Link</strong>.<br>
                This authorises that page slug to request tokens for the table.
            </div>

            {{-- Step 2 snippet --}}
            <div style="font-size:11px;font-weight:700;color:var(--text-3);text-transform:uppercase;letter-spacing:.05em;margin-bottom:6px">Step 2 — JS snippet for your page</div>
            <div style="position:relative;margin-bottom:14px">
                <button onclick="copyGuide('token-snippet')" style="position:absolute;right:8px;top:8px;background:rgba(var(--accent-rgb),.15);border:none;border-radius:5px;padding:3px 8px;font-size:10px;font-weight:700;color:var(--accent);cursor:pointer">Copy</button>
                <pre id="token-snippet" style="background:var(--surface-2);border:1px solid var(--border);border-radius:8px;padding:14px;font-size:11px;font-family:'Consolas',monospace;color:var(--text-2);overflow-x:auto;margin:0;line-height:1.7">// Fetch a short-lived token (no API key needed!)
const { token } = await fetch(
  '{{ config('app.url') }}/api/token/YOUR_TABLE'
).then(r => r.json());

// Use the token to get your data
const { data } = await fetch(
  `{{ config('app.url') }}/api/data/YOUR_TABLE?token=${token}`
).then(r => r.json());

// Render your records
data.forEach(row => {
  document.getElementById('container').innerHTML +=
    `&lt;div class="card"&gt;&lt;h3&gt;${row.title}&lt;/h3&gt;&lt;/div&gt;`;
});</pre>
            </div>

            {{-- Live tester --}}
            <div style="font-size:11px;font-weight:700;color:var(--text-3);text-transform:uppercase;letter-spacing:.05em;margin-bottom:6px">Live Token Tester</div>
            <div style="display:grid;grid-template-columns:1fr auto;gap:8px;margin-bottom:8px">
                <input id="test-table-name" type="text" placeholder="table_name" style="border:1px solid var(--border);border-radius:7px;padding:8px 10px;background:var(--surface-2);color:var(--text);font-size:12px">
                <button onclick="testToken()" style="background:var(--accent);color:#fff;border:none;border-radius:7px;padding:8px 14px;font-size:12px;font-weight:700;cursor:pointer">Test</button>
            </div>
            <pre id="token-result" style="display:none;background:var(--surface-2);border:1px solid var(--border);border-radius:7px;padding:10px;font-size:11px;font-family:monospace;color:var(--text-2);max-height:120px;overflow-y:auto;margin:0;white-space:pre-wrap;word-break:break-all"></pre>
        </div>

        {{-- LEGACY KEY GUIDE --}}
        <div id="guide-key" style="display:none">
            <div style="background:rgba(var(--amber-rgb,255,160,0),.08);border:1px solid rgba(255,160,0,.3);border-radius:8px;padding:10px 14px;margin-bottom:14px;display:flex;gap:10px;align-items:flex-start">
                <span style="font-size:18px;line-height:1">⚠️</span>
                <div>
                    <div style="font-size:12px;font-weight:700;color:#d97706;margin-bottom:2px">Legacy Method — API Key in Code</div>
                    <div style="font-size:11px;color:var(--text-3);line-height:1.5">This method embeds a permanent key in your frontend JavaScript. It is secured by origin-checking but the key can still be read from source. Use the Token method for better security.</div>
                </div>
            </div>
            <div style="font-size:11px;font-weight:700;color:var(--text-3);text-transform:uppercase;letter-spacing:.05em;margin-bottom:6px">Usage</div>
            <div style="position:relative;margin-bottom:12px">
                <button onclick="copyGuide('key-snippet')" style="position:absolute;right:8px;top:8px;background:rgba(var(--accent-rgb),.15);border:none;border-radius:5px;padding:3px 8px;font-size:10px;font-weight:700;color:var(--accent);cursor:pointer">Copy</button>
                <pre id="key-snippet" style="background:var(--surface-2);border:1px solid var(--border);border-radius:8px;padding:14px;font-size:11px;font-family:'Consolas',monospace;color:var(--text-2);overflow-x:auto;margin:0;line-height:1.7">fetch('{{ config('app.url') }}/api/data/TABLE_NAME?key=YOUR_API_KEY')
  .then(res => res.json())
  .then(({ data }) => {
    data.forEach(row => {
      document.getElementById('container').innerHTML +=
        `&lt;div class="card"&gt;&lt;h3&gt;${row.title}&lt;/h3&gt;&lt;/div&gt;`;
    });
  });</pre>
            </div>
            <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:10px">
                <span style="font-size:10px;background:var(--green-bg);color:var(--green);border-radius:4px;padding:2px 8px;font-weight:700">✓ Origin-checked</span>
                <span style="font-size:10px;background:var(--green-bg);color:var(--green);border-radius:4px;padding:2px 8px;font-weight:700">✓ Table-scoped</span>
                <span style="font-size:10px;background:var(--green-bg);color:var(--green);border-radius:4px;padding:2px 8px;font-weight:700">✓ Read-only</span>
                <span style="font-size:10px;background:var(--red-bg);color:var(--red);border-radius:4px;padding:2px 8px;font-weight:700">✗ Key visible in source</span>
            </div>
            <p style="font-size:11px;color:var(--text-3);margin:0">Tip: Migrate to the Token method by linking your page slug in the Database Builder. No code changes needed — just swap <code>?key=</code> for <code>?token=</code>.</p>
        </div>

        {{-- Query Parameters --}}
        <div style="margin-top:16px;padding-top:14px;border-top:1px solid var(--border)">
            <div style="font-size:11px;font-weight:700;color:var(--text-3);text-transform:uppercase;letter-spacing:.05em;margin-bottom:6px">Query Parameters (Optional)</div>
            <p style="font-size:11px;color:var(--text-3);margin:0 0 8px 0;line-height:1.5">You can append these to your <code>/api/data/...</code> URL to control the returned data. <strong>Note:</strong> By default, the API now returns all records (up to 2000) sorted by latest, ignoring any legacy API key limits.</p>
            <table style="width:100%;font-size:11px;color:var(--text-2);border-collapse:collapse;margin-bottom:8px;">
                <tr style="border-bottom:1px solid var(--surface-3);"><th style="text-align:left;padding:6px 4px;color:var(--text);width:100px;">Parameter</th><th style="text-align:left;padding:6px 4px;color:var(--text);">Description</th></tr>
                <tr style="border-bottom:1px solid var(--surface-3);"><td style="padding:6px 4px;"><code>?limit=50</code></td><td style="padding:6px 4px;">Override the number of records returned. Default is 2000 (all records).</td></tr>
                <tr style="border-bottom:1px solid var(--surface-3);"><td style="padding:6px 4px;"><code>&sort=oldest</code></td><td style="padding:6px 4px;">Change the sorting order. Options are <code>latest</code> (default) or <code>oldest</code>.</td></tr>
                <tr style="border-bottom:1px solid var(--surface-3);"><td style="padding:6px 4px;"><code>&page=2</code></td><td style="padding:6px 4px;">Used for manual pagination if you have a massive dataset and don't want to load it all at once.</td></tr>
            </table>
        </div>

        {{-- Response schema --}}
        <div style="margin-top:16px;padding-top:14px;border-top:1px solid var(--border)">
            <div style="font-size:11px;font-weight:700;color:var(--text-3);text-transform:uppercase;letter-spacing:.05em;margin-bottom:6px">Response Structure</div>
            <pre style="background:var(--surface-2);border:1px solid var(--border);border-radius:8px;padding:12px;font-size:11px;font-family:monospace;color:var(--text-2);margin:0;overflow-x:auto;line-height:1.6">{
  "table": "notices",
  "page": 1,
  "per_page": 10,
  "total": 42,
  "total_pages": 5,
  "data": [ { "id":1, "title":"..." } ]
}</pre>
        </div>
    </div>
</div>

{{-- Create Modal --}}
<div id="create-modal" class="modal-backdrop" onclick="if(event.target===this)this.classList.remove('open')" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; z-index:999; background:rgba(0,0,0,0.5); backdrop-filter:blur(4px); align-items:center; justify-content:center;">
    <div class="modal-box" style="background:var(--surface); padding:24px; border-radius:16px; width:400px; border:1px solid var(--border); box-shadow:var(--shadow-lg);">
        <h4 style="margin:0 0 16px; color:var(--text);">Generate API Key</h4>
        <form method="POST" action="{{ route('admin.api-keys.store') }}">
            @csrf
            <div style="margin-bottom:16px;">
                <label style="display:block; font-size:12px; color:var(--text-3); margin-bottom:6px;">Key Label Name</label>
                <input type="text" name="name" required placeholder="e.g. Frontend App Key" style="width:100%; background:var(--surface-2); border:1px solid var(--border); border-radius:8px; padding:10px; color:var(--text);">
            </div>
            <div style="margin-bottom:20px;">
                <label style="display:block; font-size:12px; color:var(--text-3); margin-bottom:6px;">Bound Table (Read Access)</label>
                <select name="table_name" required style="width:100%; background:var(--surface-2); border:1px solid var(--border); border-radius:8px; padding:10px; color:var(--text);">
                    <option value="">-- Select a Table --</option>
                    @foreach($tables as $t)
                    <option value="{{ $t }}">{{ $t }}</option>
                    @endforeach
                </select>
            </div>
            <div style="display:flex; gap:16px; margin-bottom:20px;">
                <div style="flex:1;">
                    <label style="display:block; font-size:12px; color:var(--text-3); margin-bottom:6px;">Data Limit (Optional)</label>
                    <input type="number" min="1" max="100" name="data_limit" placeholder="e.g. 5" style="width:100%; background:var(--surface-2); border:1px solid var(--border); border-radius:8px; padding:10px; color:var(--text);">
                </div>
                <div style="flex:1;">
                    <label style="display:block; font-size:12px; color:var(--text-3); margin-bottom:6px;">Sort Data (Optional)</label>
                    <select name="data_sort" style="width:100%; background:var(--surface-2); border:1px solid var(--border); border-radius:8px; padding:10px; color:var(--text);">
                        <option value="">Default order</option>
                        <option value="latest">Latest First</option>
                        <option value="oldest">Oldest First</option>
                    </select>
                </div>
            </div>
            <div style="display:flex; justify-content:flex-end; gap:8px;">
                <button type="button" class="action-btn" onclick="document.getElementById('create-modal').classList.remove('open')">Cancel</button>
                <button type="submit" class="action-btn" style="background:var(--green); color:var(--text); border:none; box-shadow:0 4px 12px var(--green-border);">Generate Key</button>
            </div>
        </form>
    </div>
</div>

<style>
.modal-backdrop.open { display: flex !important; }
</style>
<script>
function switchGuide(tab) {
    document.getElementById('guide-token').style.display = tab==='token'?'block':'none';
    document.getElementById('guide-key').style.display   = tab==='key'  ?'block':'none';
    document.getElementById('tab-token').style.background = tab==='token'?'var(--accent)':'transparent';
    document.getElementById('tab-token').style.color      = tab==='token'?'#fff':'var(--text-3)';
    document.getElementById('tab-key').style.background   = tab==='key'  ?'var(--accent)':'transparent';
    document.getElementById('tab-key').style.color        = tab==='key'  ?'#fff':'var(--text-3)';
}
function copyGuide(id) {
    const el = document.getElementById(id);
    navigator.clipboard.writeText(el.textContent).then(() => cmsToast('Copied!', 'success'));
}
async function testToken() {
    const tbl = document.getElementById('test-table-name').value.trim();
    const out  = document.getElementById('token-result');
    if (!tbl) { cmsToast('Enter a table name first.', 'error'); return; }
    out.style.display = 'block';
    out.textContent = 'Fetching token…';
    try {
        const r = await fetch(`{{ config('app.url') }}/api/token/${tbl}`);
        const d = await r.json();
        out.textContent = JSON.stringify(d, null, 2);
        out.style.borderColor = d.token ? 'var(--green-border)' : 'var(--red-border)';
        if (d.token) cmsToast('Token issued! ✓', 'success');
        else cmsToast(d.error || 'Failed', 'error');
    } catch(e) {
        out.textContent = 'Error: ' + e.message;
        out.style.borderColor = 'var(--red-border)';
    }
}
function copyExistingKey(id, rawText) {
    navigator.clipboard.writeText(rawText);
    const btn = document.getElementById('copy-btn-' + id);
    const originalHtml = btn.innerHTML;
    btn.textContent = 'Copied!';
    btn.style.background = 'var(--green-bg)';
    btn.style.color = 'var(--green)';
    btn.style.borderColor = 'var(--green-border)';
    setTimeout(() => {
        btn.innerHTML = originalHtml;
        btn.style.background = 'rgba(var(--accent-rgb),0.1)';
        btn.style.color = 'var(--accent)';
        btn.style.borderColor = 'rgba(var(--accent-rgb),0.2)';
    }, 2000);
}
</script>
@endsection

