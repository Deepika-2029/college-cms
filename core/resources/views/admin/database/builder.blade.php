@extends('admin.layout')
@section('title','Database Builder Wizard')
@section('page-title','Create New Table')

@section('topbar-actions')
<a href="{{ route('admin.database.index') }}" class="btn-cms btn-cms-secondary" style="display:inline-flex;align-items:center;gap:6px">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
    Back to Tables
</a>
@endsection

@push('styles')
<style>
/* Base UI */
.wiz-steps { display: flex; gap: 8px; margin-bottom: 24px; flex-wrap: wrap; }
.wiz-step { flex: 1; min-width: 140px; padding: 14px 16px; background: var(--surface); border: 1px solid var(--border); border-radius: 12px; display: flex; align-items: center; gap: 12px; font-size: 14px; font-weight: 600; color: var(--text-3); cursor: pointer; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); position: relative; overflow: hidden; box-shadow: var(--shadow-sm); }
.wiz-step::before { content: ""; position: absolute; inset: 0; background: linear-gradient(135deg, rgba(var(--accent-rgb), 0.1), transparent); opacity: 0; transition: 0.3s; }
.wiz-step:hover { border-color: rgba(var(--accent-rgb), 0.3); transform: translateY(-1px); }
.wiz-step:hover::before { opacity: 1; }
.wiz-step.active { background: var(--surface); border-color: var(--accent); color: var(--text); box-shadow: 0 4px 12px rgba(var(--accent-rgb), 0.15); }
.wiz-step.done { background: var(--surface-2); border-color: transparent; color: var(--text-2); }
.wiz-step .sn { width: 26px; height: 26px; border-radius: 8px; background: var(--surface-3); display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 800; flex-shrink: 0; transition: 0.3s; color: var(--text-3); }
.wiz-step.active .sn { background: var(--accent); color: #fff; }
.wiz-step.done .sn { background: rgba(var(--accent-rgb), 0.15); color: var(--accent); }
.wiz-panel { display: none; animation: fadeIn 0.4s ease; }
.wiz-panel.active { display: block; }

@keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }

/* Layout Grid */
.db-grid { max-width: 900px; margin: 0 auto; }

/* Panels */
.db-panel { background: var(--surface); border-radius: 16px; border: 1px solid var(--border); padding: 24px; box-shadow: var(--shadow-sm); position: relative; overflow: hidden; }
.db-panel::after { content: ""; position: absolute; top: 0; left: 0; width: 100%; height: 4px; background: linear-gradient(90deg, var(--accent), transparent); opacity: 0.5; }
.panel-title { font-size: 16px; font-weight: 700; color: var(--text); margin-bottom: 20px; display: flex; align-items: center; gap: 8px; }

/* Inputs & Form Elements */
.input-cms { width: 100%; border: 1px solid var(--border); border-radius: 8px; padding: 10px 14px; background: var(--surface-2); color: var(--text); font-size: 14px; transition: 0.2s; box-sizing: border-box; }
.input-cms:focus { outline: none; border-color: var(--accent); box-shadow: 0 0 0 3px rgba(var(--accent-rgb), 0.15); background: var(--surface); }

/* Field Row Designer */
.field-row { display: grid; grid-template-columns: 1fr 1fr auto auto; gap: 12px; align-items: center; background: var(--surface); border: 1px solid var(--border); border-radius: 10px; padding: 12px 16px; margin-bottom: 12px; transition: all 0.2s; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
@media(max-width: 600px) { .field-row { grid-template-columns: 1fr 1fr; } .field-row > button { grid-column: auto; } }
.field-row:hover { border-color: rgba(var(--accent-rgb), 0.4); box-shadow: 0 4px 12px rgba(var(--accent-rgb), 0.08); transform: translateY(-1px); }
.col-opts { display: none; grid-column: 1/-1; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 12px; margin-top: 12px; padding-top: 12px; border-top: 1px dashed var(--border); }
.col-opts.open { display: grid; animation: fadeIn 0.3s ease; }
.col-opts label { display: flex; align-items: center; gap: 8px; font-size: 13px; color: var(--text-2); cursor: pointer; user-select: none; }
.col-opts label input[type="checkbox"] { width: 16px; height: 16px; accent-color: var(--accent); cursor: pointer; }

/* Buttons */
.add-col-btn { width: 100%; border: 2px dashed var(--border); background: var(--surface); color: var(--text-2); padding: 14px; border-radius: 10px; font-weight: 600; cursor: pointer; transition: all 0.2s; margin-bottom: 24px; display: flex; justify-content: center; align-items: center; gap: 8px; }
.add-col-btn:hover { border-color: var(--accent); color: var(--accent); background: rgba(var(--accent-rgb), 0.05); }

/* Template Chips */
.tpl-chips { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 20px; padding-bottom: 16px; border-bottom: 1px solid var(--border); }
.tpl-btn { background: var(--surface-2); border: 1px solid var(--border); border-radius: 999px; padding: 6px 14px; font-size: 13px; font-weight: 600; cursor: pointer; color: var(--text-2); transition: all 0.2s; display: inline-flex; align-items: center; gap: 6px; }
.tpl-btn:hover { background: rgba(var(--accent-rgb), 0.1); border-color: var(--accent); color: var(--accent); transform: scale(1.02); }

/* Review Grid */
.review-col { display: flex; align-items: center; justify-content: space-between; padding: 10px 14px; background: var(--surface); border: 1px solid var(--border); border-radius: 8px; margin-bottom: 8px; font-size: 14px; color: var(--text); }
.type-badge { font-size: 11px; font-weight: 700; padding: 3px 10px; border-radius: 6px; background: rgba(var(--accent-rgb), 0.1); color: var(--accent); text-transform: uppercase; letter-spacing: 0.05em; }

/* Table Item Card */
.table-item { background: var(--surface); border-radius: 12px; border: 1px solid var(--border); padding: 20px; margin-bottom: 16px; transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1); box-shadow: var(--shadow-sm); position: relative; overflow: hidden; }
.table-item::before { content: ""; position: absolute; left: 0; top: 0; bottom: 0; width: 4px; background: var(--surface-3); transition: 0.2s; }
.table-item:hover { border-color: rgba(var(--accent-rgb), 0.3); box-shadow: var(--shadow-md); transform: translateY(-2px); }
.table-item:hover::before { background: var(--accent); }
.table-item-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; flex-wrap: wrap; }
.table-item-actions { display: flex; gap: 8px; flex-wrap: wrap; }
.action-btn { background: var(--surface-2); color: var(--text-2); border: 1px solid var(--border); padding: 6px 12px; border-radius: 8px; font-size: 12px; font-weight: 600; cursor: pointer; transition: 0.2s; display: inline-flex; align-items: center; justify-content: center; gap: 6px; text-decoration: none; }
.action-btn:hover { background: var(--surface-3); color: var(--text); }
.action-btn.primary { background: rgba(var(--green-rgb), 0.1); color: var(--green); border-color: rgba(var(--green-rgb), 0.2); }
.action-btn.primary:hover { background: var(--green); color: #fff; }
.action-btn.danger { background: rgba(var(--red-rgb), 0.1); color: var(--red); border-color: rgba(var(--red-rgb), 0.2); }
.action-btn.danger:hover { background: var(--red); color: #fff; }

/* Raw SQL Editor */
.sql-editor { width: 100%; min-height: 180px; background: rgba(0,0,0,0.2); border: 1px solid var(--border); color: var(--text); border-radius: 8px; padding: 14px; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Courier New", monospace; font-size: 13px; line-height: 1.5; resize: vertical; box-sizing: border-box; }
.sql-editor:focus { outline: none; border-color: var(--accent); box-shadow: 0 0 0 3px rgba(var(--accent-rgb), 0.15); }

/* Badges/Tags */
</style>
@endpush

@section('content')
@if(session('new_key')||str_contains(session('success',''),'API Key'))
<div style="background:linear-gradient(135deg,var(--green),#059669);border-radius:var(--r-xl);padding:24px;margin-bottom:24px;color:#fff">
  <h3 style="margin:0 0 8px;font-size:18px">✅ Table Created & API Key Generated!</h3>
  <p style="margin:0 0 14px;opacity:.9;font-size:13px">Save this key now — it won't be shown again.</p>
  @php $k=session('new_key') ?: (preg_match('/Key[^:]*:\s*([a-f0-9]+)/i',session('success',''),$m)?$m[1]:null); @endphp
  @if($k)
  <div style="display:flex;gap:10px;align-items:center">
    <code id="gkv" style="background:rgba(0,0,0,.3);padding:12px 18px;border-radius:8px;font-size:15px;flex:1;font-weight:700;user-select:all">{{$k}}</code>
    <button onclick="navigator.clipboard.writeText(document.getElementById('gkv').textContent);this.textContent='Copied!';setTimeout(()=>this.textContent='Copy',2000)" class="btn-cms btn-cms-primary">Copy</button>
  </div>
  @endif
</div>
@endif

{{-- Step Wizard --}}
<div class="wiz-steps">
  <button class="wiz-step active" id="ws1" onclick="goStep(1)"><span class="sn">1</span>Table Info</button>
  <button class="wiz-step" id="ws2" onclick="goStep(2)"><span class="sn">2</span>Design Columns</button>
  <button class="wiz-step" id="ws3" onclick="goStep(3)"><span class="sn">3</span>Review & Execute</button>
  <button class="wiz-step" id="ws4" onclick="goStep(4)"><span class="sn">⌨</span>Raw SQL</button>
</div>

<div class="db-grid">
  {{-- LEFT: Wizard --}}
  <div>
    {{-- Step 1 --}}
    <div class="wiz-panel active" id="wp1">
      <div class="db-panel">
        <div class="panel-title">📋 Table Information</div>
        <div style="display:grid;gap:16px">
          <div>
            <label style="display:block;font-size:13px;font-weight:600;color:var(--text-2);margin-bottom:6px">Table Name <span style="color:var(--red)">*</span></label>
            <input id="s_table_name" class="input-cms" type="text" placeholder="e.g. faculty_members" oninput="this.value=this.value.toLowerCase().replace(/[^a-z0-9_]/g,'');document.getElementById('rev_table').textContent=this.value||'—'" required>
            <p style="margin:5px 0 0;font-size:11px;color:var(--text-3)">Lowercase, underscores only. Auto columns: id, created_at, updated_at.</p>
          </div>
          <div>
            <label style="display:block;font-size:13px;font-weight:600;color:var(--text-2);margin-bottom:6px">CRUD UI Type</label>
            <select id="s_ui_type" class="input-cms">
              <option value="list">📋 List / Table (data-heavy)</option>
              <option value="grid">🖼 Grid Gallery (image-heavy)</option>
            </select>
          </div>
        </div>
        <div style="display:flex;justify-content:flex-end;margin-top:20px">
          <button class="btn-cms btn-cms-primary" onclick="goStep(2)">Next: Design Columns →</button>
        </div>
      </div>
    </div>

    {{-- Step 2 --}}
    <div class="wiz-panel" id="wp2">
      <div class="db-panel">
        <div class="panel-title">🏗 Column Designer</div>
        <div class="tpl-chips">
          <button class="tpl-btn" onclick="loadTpl('notices')">📢 Notice Board</button>
          <button class="tpl-btn" onclick="loadTpl('faculty')">👨‍🏫 Faculty</button>
          <button class="tpl-btn" onclick="loadTpl('achievements')">🏆 Achievement</button>
          <button class="tpl-btn" onclick="loadTpl('events')">📅 Event</button>
          <button class="tpl-btn" onclick="loadTpl('courses')">📚 Course</button>
          <button class="tpl-btn" onclick="loadTpl('gallery')">🖼 Gallery</button>
          <button class="tpl-btn" onclick="loadTpl('products')">📦 Product</button>
        </div>
        <div id="col-list"></div>
        <button type="button" class="add-col-btn" onclick="addCol()">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
          Add Column
        </button>
        <div style="display:flex;justify-content:space-between">
          <button class="btn-cms btn-cms-secondary" onclick="goStep(1)">← Back</button>
          <button class="btn-cms btn-cms-primary" onclick="goStep(3)">Next: Review →</button>
        </div>
      </div>
    </div>

    {{-- Step 3 --}}
    <div class="wiz-panel" id="wp3">
      <div class="db-panel">
        <div class="panel-title">🔍 Review & Execute</div>
        <div style="background:var(--surface-2);border-radius:var(--r-lg);padding:16px;margin-bottom:16px">
          <div style="font-size:13px;color:var(--text-3);margin-bottom:10px">Table: <strong id="rev_table" style="color:var(--accent)">—</strong> &nbsp;|&nbsp; UI: <strong id="rev_ui">List</strong></div>
          <div style="font-size:12px;color:var(--text-3);margin-bottom:6px">Auto columns: <span style="color:var(--text-2)">id, created_at, updated_at</span></div>
          <div id="rev-cols"></div>
        </div>
        <div style="margin-bottom:16px">
          <button onclick="toggleSql()" style="background:none;border:none;color:var(--accent);font-size:12px;font-weight:600;cursor:pointer">▶ Preview SQL</button>
          <pre id="sql-preview" style="display:none;background:var(--surface-3);border-radius:var(--r);padding:14px;font-size:12px;overflow-x:auto;margin-top:8px;color:var(--text-2)"></pre>
        </div>
        <form action="{{ route('admin.database.run') }}" method="POST" id="schema-form">
          @csrf
          <input type="hidden" name="table_name" id="f_table_name">
          <input type="hidden" name="ui_type" id="f_ui_type">
          <div id="f_columns_hidden"></div>
          <div style="display:flex;justify-content:space-between">
            <button type="button" class="btn-cms btn-cms-secondary" onclick="goStep(2)">← Back</button>
            <button type="submit" class="btn-cms btn-cms-primary" onclick="return prepSubmit()">⚡ Execute & Scaffold UI</button>
          </div>
        </form>
      </div>
    </div>

    {{-- Step 4: Raw SQL --}}
    <div class="wiz-panel" id="wp4">
      <div class="db-panel">
        <div class="panel-title">⌨ Raw SQL</div>
        <p style="font-size:12px;color:var(--text-3);margin:0 0 12px">Run CREATE TABLE or other DDL directly. Table will be auto-registered.</p>
        <form action="{{ route('admin.database.run-raw') }}" method="POST">
          @csrf
          <textarea name="raw_sql" class="sql-editor" placeholder="CREATE TABLE IF NOT EXISTS notices (&#10;  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,&#10;  title VARCHAR(500) NOT NULL&#10;);" required></textarea>
          <div style="display:flex;justify-content:flex-end;gap:12px;margin-top:16px;align-items:center">
            <select name="ui_type" class="input-cms" style="width:auto;min-width:140px;margin-bottom:0">
              <option value="list">List UI</option>
              <option value="grid">Grid UI</option>
            </select>
            <button type="submit" class="btn-cms btn-cms-primary">⚡ Run SQL</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection
@push('scripts')
<script>
const CSRF=document.querySelector('meta[name="csrf-token"]')?.content||'';
const TYPES=['string','text','tinyText','mediumText','integer','bigInteger','float','decimal','boolean','date','dateTime','json','uuid','year'];
let colIdx=0;
function goStep(n){
  if(n===2&&!document.getElementById('s_table_name').value.trim()){cmsToast('Enter a table name first.','error');return;}
  if(n===3&&!document.getElementById('s_table_name').value.trim()){cmsToast('Enter a table name first.','error');return;}
  if(n===3)buildReview();
  [1,2,3,4].forEach(i=>{
    const wp = document.getElementById('wp'+i);
    if(wp) wp.classList.toggle('active',i===n);
    const ws=document.getElementById('ws'+i);
    if(ws) {
      ws.classList.toggle('active',i===n);
      ws.classList.toggle('done',i!==4 && i<n); // don't mark raw sql as 'done'
    }
  });
}
function typeOpts(sel='string'){return TYPES.map(t=>`<option value="${t}"${t===sel?' selected':''}>${t}</option>`).join('');}
function addCol(name='',type='string',nullable=false){const i=colIdx++;const row=document.createElement('div');row.className='field-row';row.id='cr_'+i;row.innerHTML=`<input type="text" id="cn_${i}" class="input-cms" placeholder="column_name" value="${name}" oninput="this.value=this.value.toLowerCase().replace(/[^a-z0-9_]/g,'')"><select id="ct_${i}" class="input-cms">${typeOpts(type)}</select><button type="button" onclick="toggleOpts(${i})" class="action-btn">⚙</button><button type="button" onclick="document.getElementById('cr_${i}').remove()" class="action-btn danger">×</button><div class="col-opts" id="co_${i}"><label><input type="checkbox" id="c_null_${i}" ${nullable?'checked':''}> Nullable</label><label><input type="checkbox" id="c_uniq_${i}"> Unique</label><label><input type="checkbox" id="c_idx_${i}"> Index</label><label style="flex-direction:column;align-items:flex-start">Default<input type="text" class="input-cms" id="c_def_${i}" placeholder="optional" style="margin-top:4px;padding:6px 10px;font-size:12px"></label></div>`;document.getElementById('col-list').appendChild(row);}
function toggleOpts(i){document.getElementById('co_'+i).classList.toggle('open');}
const TEMPLATES={notices:{name:'notices',cols:[['title','string'],['content','text',true],['category','string',true],['published_at','dateTime',true],['is_active','boolean']]},faculty:{name:'faculty',cols:[['name','string'],['designation','string'],['department','string',true],['email','string',true],['photo','string',true],['bio','text',true],['sort_order','integer',true]]},achievements:{name:'achievements',cols:[['title','string'],['student_name','string'],['year','year',true],['category','string',true],['description','text',true],['image','string',true]]},events:{name:'events',cols:[['title','string'],['event_date','date'],['location','string',true],['description','text',true],['banner_image','string',true],['registration_url','string',true]]},courses:{name:'courses',cols:[['name','string'],['code','string'],['department','string',true],['duration','string',true],['description','text',true],['syllabus_pdf','string',true]]},gallery:{name:'gallery',cols:[['title','string'],['category','string',true],['cover_image','string',true],['date','date',true],['description','text',true]]},products:{name:'products',cols:[['title','string'],['price','decimal'],['image','string',true],['description','text',true],['category','string',true],['in_stock','boolean']]}};
function loadTpl(key){const t=TEMPLATES[key];if(!t)return;document.getElementById('s_table_name').value=t.name;document.getElementById('rev_table').textContent=t.name;document.getElementById('col-list').innerHTML='';colIdx=0;t.cols.forEach(([n,tp,nl])=>addCol(n,tp,!!nl));cmsToast('Template loaded!','success');}
function buildReview(){document.getElementById('rev_table').textContent=document.getElementById('s_table_name').value||'—';document.getElementById('rev_ui').textContent=document.getElementById('s_ui_type').selectedOptions[0]?.text||'List';const rc=document.getElementById('rev-cols');rc.innerHTML='';document.querySelectorAll('.field-row').forEach(row=>{const i=row.id.replace('cr_','');const n=document.getElementById('cn_'+i)?.value;const t=document.getElementById('ct_'+i)?.value;if(!n)return;rc.innerHTML+=`<div class="review-col"><span>${n}</span><span class="type-badge">${t}</span></div>`;});buildSqlPreview();}
function buildSqlPreview(){const tbl=document.getElementById('s_table_name').value;if(!tbl)return;const m={string:'VARCHAR(255)',text:'LONGTEXT',tinyText:'TINYTEXT',mediumText:'MEDIUMTEXT',integer:'INT',bigInteger:'BIGINT',float:'FLOAT',decimal:'DECIMAL(10,2)',boolean:'TINYINT(1)',date:'DATE',dateTime:'DATETIME',json:'JSON',uuid:'CHAR(36)',year:'YEAR'};let sql=`CREATE TABLE \`${tbl}\` (\n  \`id\` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,\n`;document.querySelectorAll('.field-row').forEach(row=>{const i=row.id.replace('cr_','');const n=document.getElementById('cn_'+i)?.value;const t=document.getElementById('ct_'+i)?.value;const nl=document.getElementById('c_null_'+i)?.checked;if(!n)return;sql+=`  \`${n}\` ${m[t]||'VARCHAR(255)'}${nl?' NULL':' NOT NULL'},\n`;});sql+=`  \`created_at\` TIMESTAMP NULL,\n  \`updated_at\` TIMESTAMP NULL,\n  PRIMARY KEY (\`id\`)\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;`;document.getElementById('sql-preview').textContent=sql;}
function toggleSql(){const el=document.getElementById('sql-preview');el.style.display=el.style.display==='none'?'block':'none';}
function prepSubmit(){const tbl=document.getElementById('s_table_name').value.trim();if(!tbl){cmsToast('Table name required.','error');return false;}document.getElementById('f_table_name').value=tbl;document.getElementById('f_ui_type').value=document.getElementById('s_ui_type').value;const wrap=document.getElementById('f_columns_hidden');wrap.innerHTML='';let idx=0;document.querySelectorAll('.field-row').forEach(row=>{const i=row.id.replace('cr_','');const n=document.getElementById('cn_'+i)?.value?.trim();const t=document.getElementById('ct_'+i)?.value;if(!n)return;wrap.innerHTML+=`<input type="hidden" name="columns[${idx}][name]" value="${n}"><input type="hidden" name="columns[${idx}][type]" value="${t}"><input type="hidden" name="columns[${idx}][nullable]" value="${document.getElementById('c_null_'+i)?.checked?1:0}">`;idx++;});return idx>0||(cmsToast('Add at least one column.','error'),false);}
document.addEventListener('DOMContentLoaded', () => addCol());
</script>
@endpush
