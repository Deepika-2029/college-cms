function showDetail(log) {
    document.getElementById('det-title').textContent = log.action;
    let html = '';
    [
        ['ID',      log.id],
        ['Time',    log.created_at],
        ['User',    log.user_name ? log.user_name+' <'+log.user_email+'>' : (log.user_email||'— guest —')],
        ['Role',    log.user_role||'—'],
        ['IP',      log.ip_address||'—'],
        ['Location',log.city?(log.city+', '+log.country):(log.country||'—')],
        ['Target',  log.target_type?(log.target_type+': '+(log.target_label||log.target_id||'—')):'—'],
        ['Suspicious', log.is_suspicious ? '⚠ YES — '+log.suspicious_reason : 'No'],
    ].forEach(([k,v])=>{ html+=`<div class="det-row"><span class="det-key">${k}</span><span class="det-val">${v??'—'}</span></div>`; });

    if (log.old_values||log.new_values) {
        html+='<div style="margin-top:.75rem;font-weight:600;font-size:.78rem;color:var(--muted);">CHANGES</div>';
        html+='<div class="diff-block">';
        if (log.old_values && log.new_values) {
            Object.entries(log.old_values).forEach(([k,v])=>{
                const nv=log.new_values[k];
                if (nv!==undefined&&nv!==v){
                    html+=`<div class="diff-old">- ${k}: ${v}</div><div class="diff-new">+ ${k}: ${nv}</div>`;
                }
            });
        } else if (log.new_values) {
            Object.entries(log.new_values).forEach(([k,v])=>{ html+=`<div class="diff-new">+ ${k}: ${v}</div>`; });
        }
        html+='</div>';
    }
    document.getElementById('det-body').innerHTML=html;
    document.getElementById('det-back').classList.add('open');
}
function closeDet(){ document.getElementById('det-back').classList.remove('open'); }
document.getElementById('det-back').addEventListener('click',e=>{ if(e.target===document.getElementById('det-back')) closeDet(); });
document.addEventListener('keydown',e=>{ if(e.key==='Escape') closeDet(); });

function prefillBlock(ip) {
    const input = document.querySelector('input[name="ip_address"]');
    if (input) { input.value=ip; input.scrollIntoView({behavior:'smooth',block:'center'}); input.focus(); }
}
