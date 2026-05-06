const CSRF = document.querySelector('meta[name="csrf-token"]').content;
function toggleStatus(userId, btn) {
    fetch(`/${window.ADMIN_PREFIX}/users/${userId}/toggle-status`, {
        method:'POST',
        headers:{'X-CSRF-TOKEN':CSRF,'Content-Type':'application/json','Accept':'application/json'}
    }).then(r=>r.json()).then(data=>{
        if(data.error){ cmsToast(data.error, 'error'); return; }
        const active = data.status == 1;
        btn.dataset.status = active?'1':'0';
        btn.querySelector('span:first-child').style.background = active?'var(--green)':'var(--text-3)';
        btn.querySelector('span:last-child').textContent = active?'Active':'Inactive';
    }).catch(()=>cmsToast('Failed to update status', 'error'));
}
