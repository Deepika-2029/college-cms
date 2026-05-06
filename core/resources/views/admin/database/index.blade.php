@extends('admin.layout')
@section('title','Managed Tables')
@section('page-title','Database Tables')

@section('topbar-actions')
@endsection

@push('styles')
<style>
.table-grid { display: grid; gap: 16px; }
.db-panel { background: var(--surface); border-radius: 16px; border: 1px solid var(--border); padding: 24px; box-shadow: var(--shadow-sm); position: relative; overflow: hidden; }
.db-panel::after { content: ""; position: absolute; top: 0; left: 0; width: 100%; height: 4px; background: linear-gradient(90deg, var(--accent), transparent); opacity: 0.5; }

/* Table Item Card */
.table-item { background: var(--surface); border-radius: 12px; border: 1px solid var(--border); padding: 20px; transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1); box-shadow: var(--shadow-sm); position: relative; overflow: hidden; }
.table-item::before { content: ""; position: absolute; left: 0; top: 0; bottom: 0; width: 4px; background: var(--surface-3); transition: 0.2s; }
.table-item:hover { border-color: rgba(var(--accent-rgb), 0.3); box-shadow: var(--shadow-md); transform: translateY(-2px); }
.table-item:hover::before { background: var(--accent); }
.table-item-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; flex-wrap: wrap; }
.table-item-actions { display: flex; gap: 8px; flex-wrap: wrap; }

/* Buttons */
.action-btn { background: var(--surface-2); color: var(--text-2); border: 1px solid var(--border); padding: 8px 14px; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; transition: 0.2s; display: inline-flex; align-items: center; justify-content: center; gap: 6px; text-decoration: none; }
.action-btn:hover { background: var(--surface-3); color: var(--text); }
.action-btn.primary { background: rgba(var(--green-rgb), 0.1); color: var(--green); border-color: rgba(var(--green-rgb), 0.2); }
.action-btn.primary:hover { background: var(--green); color: #fff; }
.action-btn.danger { background: rgba(var(--red-rgb), 0.1); color: var(--red); border-color: rgba(var(--red-rgb), 0.2); }
.action-btn.danger:hover { background: var(--red); color: #fff; }

/* Alter Panel */
@keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }
.alter-panel { display: none; margin-top: 16px; padding: 20px; background: var(--surface-2); border: 1px solid var(--border); border-radius: 10px; animation: fadeIn 0.3s ease; }
.alter-panel.open { display: block; }
.alter-row { display: grid; grid-template-columns: 1fr 1fr auto; gap: 10px; align-items: center; margin-bottom: 12px; }
@media(max-width: 600px) { .alter-row { grid-template-columns: 1fr; } }

/* Inputs */
.input-cms { width: 100%; border: 1px solid var(--border); border-radius: 8px; padding: 10px 14px; background: var(--surface); color: var(--text); font-size: 14px; transition: 0.2s; box-sizing: border-box; }
.input-cms:focus { outline: none; border-color: var(--accent); box-shadow: 0 0 0 3px rgba(var(--accent-rgb), 0.15); }

/* Stat Pill */
.stat-pill { font-size: 11px; font-weight: 700; padding: 3px 10px; border-radius: 999px; background: var(--surface-3); color: var(--text-3); display: inline-flex; align-items: center; gap: 4px; }
.stat-pill.has-data { background: rgba(var(--green-rgb), 0.15); color: var(--green); }

/* Badges/Tags */
.link-tag { display: inline-flex; align-items: center; gap: 6px; background: rgba(var(--accent-rgb), 0.1); color: var(--accent); border: 1px solid rgba(var(--accent-rgb), 0.2); border-radius: 6px; padding: 4px 10px; font-size: 12px; font-weight: 600; margin: 4px 4px 4px 0; transition: 0.2s; }
.link-tag button { background: none; border: none; color: inherit; cursor: pointer; font-size: 14px; line-height: 1; padding: 0 0 0 4px; opacity: 0.7; }
.link-tag button:hover { opacity: 1; }
</style>
@endpush

@section('content')

@if(session('success'))
<div style="background:rgba(var(--green-rgb),0.1); border:1px solid var(--green-border); border-radius:12px; padding:16px; margin-bottom:24px; color:var(--green); font-weight:600;">
    {{ session('success') }}
</div>
@endif
@if(session('error'))
<div style="background:rgba(var(--red-rgb),0.1); border:1px solid var(--red-border); border-radius:12px; padding:16px; margin-bottom:24px; color:var(--red); font-weight:600;">
    {{ session('error') }}
</div>
@endif

<div class="db-panel" style="margin-bottom:24px; background: linear-gradient(145deg, var(--surface) 0%, var(--surface-2) 100%);">
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:16px;">
        <div>
            <h2 style="margin:0 0 8px; font-size:20px; color:var(--text);">Database Dashboard</h2>
            <p style="margin:0; font-size:13px; color:var(--text-3);">Manage all dynamic tables, view schema, and configure frontend API tokens.</p>
        </div>
        <div style="display:flex; gap:24px; align-items:center;">
            <div style="text-align:right;">
                <div style="font-size:24px; font-weight:800; color:var(--accent); line-height:1;">{{ $tables->count() }}</div>
                <div style="font-size:11px; font-weight:700; text-transform:uppercase; color:var(--text-3); margin-top:4px;">Total Tables</div>
            </div>
            <div style="width:1px;height:40px;background:var(--border);"></div>
            <a href="{{ route('admin.database.builder') }}" class="btn-cms btn-cms-primary" style="display:inline-flex;align-items:center;gap:6px;background:var(--accent);border:none;box-shadow:0 4px 12px rgba(var(--accent-rgb),0.3);padding:10px 18px;border-radius:8px;color:#fff;text-decoration:none;font-weight:700;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                Create New Table
            </a>
        </div>
    </div>
</div>

<div class="table-grid">
    @forelse($tables as $t)
    <div class="table-item" id="ti-{{$t->table_name}}">
      <div class="table-item-head">
        <div>
          <div style="font-weight:800;font-size:16px;color:var(--text);display:flex;align-items:center;gap:8px;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--text-2)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="3" y1="9" x2="21" y2="9"></line><line x1="9" y1="21" x2="9" y2="9"></line></svg>
            {{$t->table_name}}
          </div>
          <div style="font-size:12px;color:var(--text-3);margin-top:6px;display:flex;gap:6px;">
            <span class="stat-pill" id="stat-{{$t->table_name}}">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="spin-icon"><line x1="12" y1="2" x2="12" y2="6"></line><line x1="12" y1="18" x2="12" y2="22"></line><line x1="4.93" y1="4.93" x2="7.76" y2="7.76"></line><line x1="16.24" y1="16.24" x2="19.07" y2="19.07"></line><line x1="2" y1="12" x2="6" y2="12"></line><line x1="18" y1="12" x2="22" y2="12"></line><line x1="4.93" y1="19.07" x2="7.76" y2="16.24"></line><line x1="16.24" y1="7.76" x2="19.07" y2="4.93"></line></svg>
                Loading...
            </span>
            <span class="stat-pill">UI: {{strtoupper($t->ui_type??'LIST')}}</span>
            <span class="stat-pill" style="background:transparent;border:1px solid var(--border);">Created: {{$t->created_at->format('M d, Y')}}</span>
          </div>
        </div>
        <div class="table-item-actions">
          <a href="{{route('admin.crud.index',$t->table_name)}}" class="action-btn primary">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
              Manage Data
          </a>
          <button onclick="toggleAlter('{{$t->table_name}}')" class="action-btn">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
              API Tokens
          </button>
          <button onclick="regenUi('{{$t->table_name}}')" class="action-btn">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 4 23 10 17 10"></polyline><polyline points="1 20 1 14 7 14"></polyline><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path></svg>
              Regen UI
          </button>
          <form id="drop-{{$t->table_name}}" method="POST" action="{{route('admin.database.drop',$t->table_name)}}" style="margin:0">@csrf @method('DELETE')
            <button type="button" onclick="dropTable('{{$t->table_name}}')" class="action-btn danger">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                Drop
            </button>
          </form>
        </div>
      </div>

      {{-- Alter Panel --}}
      <div class="alter-panel" id="alter-{{$t->table_name}}">
        <div style="font-size:14px;font-weight:700;color:var(--text);margin-bottom:16px;padding-bottom:8px;border-bottom:1px solid var(--border)">API Access & Permissions</div>

        {{-- Token / Page Links --}}
        <div>
          <div style="font-size:12px;font-weight:700;color:var(--text-2);margin-bottom:8px;display:flex;align-items:center;gap:6px;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path></svg>
            Frontend API Token Access (Page Linking)
          </div>
          <div id="links-{{$t->table_name}}" style="margin-bottom:10px">
            @php 
              $links = collect();
              try {
                  if (\Illuminate\Support\Facades\Schema::hasTable('page_table_links')) {
                      $links = \App\Models\PageTableLink::where('table_name',$t->table_name)->pluck('page_slug'); 
                  }
              } catch (\Exception $e) {}
            @endphp
            @foreach($links as $slug)
            <span class="link-tag">{{$slug}}<button onclick="unlinkPage('{{$t->table_name}}','{{$slug}}',this)">×</button></span>
            @endforeach
            @if($links->isEmpty())
                <span style="font-size:11px;color:var(--text-3);font-style:italic;" class="no-link-msg">No pages linked yet.</span>
            @endif
          </div>
          <div style="display:grid;grid-template-columns:1fr auto;gap:8px">
            <input type="text" id="lp_slug_{{$t->table_name}}" class="input-cms" placeholder="page-slug (e.g. about-us)">
            <button onclick="linkPage('{{$t->table_name}}')" class="action-btn" style="background:var(--accent);color:#fff;border:none;">Link Page</button>
          </div>
          <p style="margin:6px 0 0;font-size:11px;color:var(--text-3);line-height:1.5;">
            By linking a page slug, you authorize the frontend at that URL to request short-lived API tokens for this table.
          </p>
        </div>
      </div>
    </div>
    @empty
    <div style="padding:60px 20px;text-align:center;background:var(--surface);border-radius:16px;border:1px dashed var(--border);">
      <div style="width:64px;height:64px;border-radius:50%;background:rgba(var(--accent-rgb),0.1);display:flex;align-items:center;justify-content:center;margin:0 auto 16px;color:var(--accent);">
        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
      </div>
      <h3 style="margin:0 0 8px;font-size:18px;color:var(--text);">No Tables Found</h3>
      <p style="margin:0 0 20px;font-size:14px;color:var(--text-3);">Create your first database table using the automated builder.</p>
      <a href="{{ route('admin.database.builder') }}" class="btn-cms btn-cms-primary">Launch Database Builder</a>
    </div>
    @endforelse
</div>
@endsection

@push('scripts')
<style>
@keyframes spin { 100% { transform: rotate(360deg); } }
.spin-icon { animation: spin 2s linear infinite; }
</style>
<script>
const CSRF=document.querySelector('meta[name="csrf-token"]')?.content||'';
const BASE='{{ url("/") }}/{{ env("ADMIN_PREFIX","admin") }}/database';

function toggleAlter(tbl){
    const el=document.getElementById('alter-'+tbl);
    el.classList.toggle('open');
}

async function loadStats(tbl){
    try{
        const r=await fetch(`${BASE}/${tbl}/stats`,{headers:{'X-CSRF-TOKEN':CSRF,'Accept':'application/json'}});
        const d=await r.json();
        const pill=document.getElementById('stat-'+tbl);
        if(pill){
            pill.innerHTML = `<strong>${d.rows}</strong> rows`;
            if(d.rows>0) pill.classList.add('has-data');
        }
    }catch(e){
        const pill=document.getElementById('stat-'+tbl);
        if(pill) pill.textContent='Stats err';
    }
}

async function regenUi(tbl){const r=await fetch(`${BASE}/${tbl}/regenerate-ui`,{method:'POST',headers:{'X-CSRF-TOKEN':CSRF,'Accept':'application/json'}});const d=await r.json();cmsToast(d.message||d.error,d.success?'success':'error');}

async function linkPage(tbl){
    const slug=document.getElementById('lp_slug_'+tbl).value.trim();
    if(!slug){cmsToast('Enter page slug.','error');return;}
    const r=await fetch(`${BASE}/${tbl}/link-page`,{method:'POST',headers:{'X-CSRF-TOKEN':CSRF,'Content-Type':'application/json','Accept':'application/json'},body:JSON.stringify({page_slug:slug})});
    const d=await r.json();
    if(d.success){
        const wrap=document.getElementById('links-'+tbl);
        const msg = wrap.querySelector('.no-link-msg');
        if(msg) msg.remove();
        const tag=document.createElement('span');
        tag.className='link-tag';
        tag.innerHTML=`${slug}<button onclick="unlinkPage('${tbl}','${slug}',this)">×</button>`;
        wrap.appendChild(tag);
        document.getElementById('lp_slug_'+tbl).value='';
    }
    cmsToast(d.message||d.error,d.success?'success':'error');
}

async function unlinkPage(tbl,slug,btn){
    const r=await fetch(`${BASE}/${tbl}/link-page`,{method:'POST',headers:{'X-CSRF-TOKEN':CSRF,'Content-Type':'application/json','Accept':'application/json'},body:JSON.stringify({page_slug:slug,_method:'DELETE'})});
    const d=await r.json();
    if(d.success) btn.closest('.link-tag').remove();
    cmsToast(d.message||d.error,d.success?'success':'error');
}

async function dropTable(name){
    const ok=await cmsConfirm(`Drop "${name}"?`,'Permanently destroys the table, all data, and API keys.','Yes, Drop','btn-cms-danger');
    if(ok) document.getElementById('drop-'+name).submit();
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.table-item').forEach(el => loadStats(el.id.replace('ti-','')));
});
</script>
@endpush
