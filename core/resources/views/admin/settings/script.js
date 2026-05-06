function selectDriver(value) {
    document.querySelectorAll('.driver-opt').forEach(function(btn) {
        btn.classList.toggle('active', btn.dataset.value === value);
    });
    document.getElementById('media_driver').value = value;
}

function toggleSecret() {
    var input = document.getElementById('api-secret');
    var eye = document.querySelector('.secret-eye svg path');
    if (input.type === 'password') {
        input.type = 'text';
        // Eye off icon
        eye.setAttribute('d', 'M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24M1 1l22 22');
    } else {
        input.type = 'password';
        // Eye icon
        eye.setAttribute('d', 'M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z');
    }
}

// ── Media picker for logo / favicon ──────────────────────────────────────
function pickMediaSetting(key) {
    window.cmsMediaPicker.open({
        imagesOnly: true,
        title: key === 'logo' ? 'Select Site Logo' : 'Select Favicon',
        onSelect: function(media) {
            document.getElementById(key + '-input').value = media.url;
            const preview = document.getElementById(key + '-preview');
            if (preview) {
                preview.src = media.url;
            } else {
                // Replace placeholder with img
                const empty = document.getElementById(key + '-preview-empty');
                if (empty) {
                    const img = document.createElement('img');
                    img.id = key + '-preview';
                    img.src = media.url;
                    img.alt = key;
                    img.style.cssText = key === 'logo'
                        ? 'height:56px;max-width:160px;object-fit:contain;border-radius:var(--r);border:1px solid var(--border);background:var(--surface-3);padding:0.25rem;'
                        : 'width:56px;height:56px;object-fit:contain;border-radius:var(--r-sm);border:1px solid var(--border);background:var(--surface-3);padding:0.25rem;';
                    empty.replaceWith(img);
                }
            }
            cmsToast(key.charAt(0).toUpperCase() + key.slice(1) + ' selected', 'success');
        }
    });
}

function clearMediaSetting(key) {
    document.getElementById(key + '-input').value = '';
    const preview = document.getElementById(key + '-preview');
    if (preview) {
        // Revert to placeholder block
        const empty = document.createElement('div');
        empty.id = key + '-preview-empty';
        if (key === 'logo') {
            empty.style.cssText = 'height:56px;width:90px;background:var(--surface-2);border-radius:var(--r);border:2px dashed var(--border-2);display:flex;align-items:center;justify-content:center;color:var(--text-4);';
            empty.innerHTML = '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>';
        } else {
            empty.style.cssText = 'width:56px;height:56px;background:var(--surface-2);border-radius:var(--r-sm);border:2px dashed var(--border-2);display:flex;align-items:center;justify-content:center;color:var(--text-4);';
            empty.innerHTML = '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>';
        }
        preview.replaceWith(empty);
    }
    cmsToast(key + ' removed — save settings to apply', 'success');
}

// Highlight active nav section on scroll
var sections = document.querySelectorAll('.section-card[id]');
var navLinks  = document.querySelectorAll('.settings-nav a');
window.addEventListener('scroll', function() {
    var scrollY = window.scrollY + 120;
    var current = '';
    sections.forEach(function(s) {
        if (s.offsetTop <= scrollY) current = s.id;
    });
    navLinks.forEach(function(a) {
        a.classList.toggle('active', a.getAttribute('href') === '#' + current);
    });
}, { passive: true });

// Scroll spy click override for smooth scrolling
navLinks.forEach(link => {
    link.addEventListener('click', function(e) {
        e.preventDefault();
        const targetId = this.getAttribute('href').substring(1);
        const targetSection = document.getElementById(targetId);
        if (targetSection) {
            window.scrollTo({
                top: targetSection.offsetTop - 85,
                behavior: 'smooth'
            });
        }
    });
});

// ── Cloudinary connection test ──────────────────────────────────────────────
async function testCloudinary() {
    const btn = document.getElementById('btn-test-cld');
    const box = document.getElementById('cld-test-result');
    const badge = document.getElementById('cld-status-badge');

    btn.disabled = true;
    const originalBtnHTML = btn.innerHTML;
    btn.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 11-6.219-8.56"/></svg> Testing…';
    btn.querySelector('svg').style.animation = 'spin 1s linear infinite';
    if(!document.getElementById('spin-style')) {
        const style = document.createElement('style');
        style.id = 'spin-style';
        style.innerHTML = '@keyframes spin { 100% { transform: rotate(360deg); } }';
        document.head.appendChild(style);
    }

    box.style.display = 'none';

    const fd = new FormData();
    fd.append('_token',                  document.querySelector('meta[name=csrf-token]').content);
    fd.append('cloudinary_cloud_name',   document.getElementById('cld-cloud-name')?.value || '');
    fd.append('cloudinary_api_key',      document.getElementById('cld-api-key')?.value || '');
    // Only send secret if it was changed (not the masked placeholder)
    const secretEl = document.getElementById('api-secret');
    if (secretEl && secretEl.value && secretEl.value !== '••••••••') {
        fd.append('cloudinary_api_secret', secretEl.value);
    }

    try {
        const routeEl = document.querySelector('form#settings-form').getAttribute('action').replace(/\/save$/, '/test-cloudinary');
        const r = await fetch(routeEl, { method:'POST', body:fd });
        const d = await r.json();

        box.style.display = '';
        if (d.ok) {
            box.style.background = 'var(--green-bg)';
            box.style.borderColor = 'var(--green-border)';
            box.style.color = 'var(--green)';
            box.innerHTML = '<strong>Success:</strong> ' + d.message;
            badge.className = 'badge badge-success';
            badge.textContent = '✓ Connected';
            cmsToast('Cloudinary connected successfully!', 'success');
        } else {
            box.style.background = 'var(--red-bg)';
            box.style.borderColor = 'var(--red-border)';
            box.style.color = 'var(--red)';
            box.innerHTML = '<strong>Error:</strong> ' + d.message;
            badge.className = 'badge badge-danger';
            badge.textContent = '✗ Failed';
            cmsToast('Cloudinary connection failed', 'error');
        }
    } catch(e) {
        box.style.display = '';
        box.style.background = 'var(--red-bg)'; box.style.borderColor = 'var(--red-border)'; box.style.color = 'var(--red)';
        box.innerHTML = '<strong>Network Error:</strong> ' + e.message;
        cmsToast('Network error while testing connection', 'error');
    }

    btn.disabled = false;
    btn.innerHTML = originalBtnHTML;
}
