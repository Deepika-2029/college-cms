/* College CMS Admin — Core JS */
'use strict';

/* ── Sidebar ────────────────────────────────────────────────────── */
function toggleSidebar(){
  document.getElementById('cms-sidebar').classList.toggle('open');
  document.getElementById('sb-overlay').classList.toggle('open');
}
function closeSidebar(){
  document.getElementById('cms-sidebar').classList.remove('open');
  document.getElementById('sb-overlay').classList.remove('open');
}

/* ── Toast ──────────────────────────────────────────────────────── */
function cmsToast(msg, type, title, duration) {
  type     = type     || 'success';
  duration = duration || 4500;

  var c = document.getElementById('cms-toasts');
  if (!c) return;

  var icons = {
    success: '<path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" fill="none"/>',
    error:   '<path d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" fill="none"/>',
    warning: '<path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" fill="none"/>',
    info:    '<path d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" fill="none"/>',
  };
  var defaultTitles = { success:'Success', error:'Error', warning:'Warning', info:'Info' };
  var icon  = icons[type]  || icons.info;
  var label = title || defaultTitles[type] || 'Notice';

  var el = document.createElement('div');
  el.className = 'cms-toast cms-toast-' + type;
  el.style.setProperty('--toast-dur', (duration / 1000) + 's');
  el.innerHTML =
    '<svg width="16" height="16" viewBox="0 0 24 24">' + icon + '</svg>' +
    '<div class="cms-toast-body">' +
      '<div class="cms-toast-title">' + label + '</div>' +
      '<div class="cms-toast-msg">' + msg + '</div>' +
    '</div>' +
    '<button class="cms-toast-close" aria-label="Dismiss">&times;</button>' +
    '<div class="cms-toast-progress"></div>';

  function dismiss() {
    el.style.animation = 'toastOut .25s ease forwards';
    setTimeout(function() { if (el.parentNode) el.parentNode.removeChild(el); }, 260);
  }

  el.querySelector('.cms-toast-close').addEventListener('click', dismiss);
  c.appendChild(el);
  var t = setTimeout(dismiss, duration);
  el.addEventListener('mouseenter', function() { clearTimeout(t); });
  el.addEventListener('mouseleave', function() { t = setTimeout(dismiss, 1500); });
}

/* ── Confirm dialog ─────────────────────────────────────────────── */
var _dlgResolve = null;
function cmsConfirm(title, msg, btnLabel){
  return new Promise(function(resolve){
    _dlgResolve = resolve;
    document.getElementById('dlg-title').textContent = title || 'Confirm';
    document.getElementById('dlg-msg').textContent = msg || 'Are you sure?';
    document.getElementById('dlg-confirm-btn').textContent = btnLabel || 'Delete';
    document.getElementById('dlg-backdrop').classList.add('open');
  });
}
function dlgCancel(){ _dlgResolve && _dlgResolve(false); document.getElementById('dlg-backdrop').classList.remove('open'); }
function dlgConfirm(){ _dlgResolve && _dlgResolve(true); document.getElementById('dlg-backdrop').classList.remove('open'); }
document.addEventListener('DOMContentLoaded', function(){
  var btn = document.getElementById('dlg-confirm-btn');
  if(btn) btn.addEventListener('click', dlgConfirm);
  // Delete confirm for data-delete links
  document.body.addEventListener('click', function(e){
    var t = e.target.closest('[data-delete]');
    if(!t) return;
    e.preventDefault();
    var msg = t.dataset.confirm || 'This action cannot be undone.';
    cmsConfirm('Delete', msg, 'Delete').then(function(ok){
      if(!ok) return;
      var form = document.createElement('form');
      form.method = 'POST';
      form.action = t.dataset.delete || t.href;
      form.innerHTML = '<input type="hidden" name="_token" value="' + window.CMS_CSRF + '"><input type="hidden" name="_method" value="DELETE">';
      document.body.appendChild(form);
      form.submit();
    });
  });
});

/* ── Command palette ────────────────────────────────────────────── */
var CMD_ROUTES = [];
function openCmd(){
  document.getElementById('cmd-backdrop').classList.add('open');
  var inp = document.getElementById('cmd-input');
  inp.value = '';
  inp.focus();
  renderCmd('');
}
function closeCmd(){ document.getElementById('cmd-backdrop').classList.remove('open'); }

function renderCmd(q){
  var res = document.getElementById('cmd-results');
  if(!q){ res.innerHTML = '<div class="cmd-group-label">Quick Navigation</div>'; return; }
  var items = CMD_ROUTES.filter(function(r){ return r.label.toLowerCase().includes(q.toLowerCase()); }).slice(0,8);
  if(!items.length){ res.innerHTML = '<div style="padding:.75rem 1rem;font-size:.8125rem;color:var(--text-3)">No results for "'+q+'"</div>'; return; }
  res.innerHTML = items.map(function(r){
    return '<a href="'+r.url+'" class="cmd-item" onclick="closeCmd()"><svg width="15" height="15" viewBox="0 0 24 24" fill="none"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg><span class="cmd-item-label">'+r.label+'</span><span class="cmd-item-tag">'+r.tag+'</span></a>';
  }).join('');
}

document.addEventListener('DOMContentLoaded', function(){
  var inp = document.getElementById('cmd-input');
  if(inp){
    inp.addEventListener('input', function(){ renderCmd(this.value); });
    inp.addEventListener('keydown', function(e){
      if(e.key === 'Escape') closeCmd();
      if(e.key === 'Enter'){
        var focused = document.querySelector('#cmd-results .cmd-item');
        if(focused) { window.location.href = focused.href; closeCmd(); }
      }
    });
  }
  document.addEventListener('keydown', function(e){
    if((e.ctrlKey || e.metaKey) && e.key === 'k'){ e.preventDefault(); openCmd(); }
    if(e.key === 'Escape') closeCmd();
  });
});
