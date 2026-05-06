// ── Media picker for logo / favicon / university logo ─────────────────────────
function pickMediaSetting(key) {
    const titleMap = {
        logo:             'Select College Logo',
        'university-logo': 'Select University Affiliation Logo',
        favicon:          'Select Favicon',
    };

    window.cmsMediaPicker.open({
        imagesOnly: true,
        title: titleMap[key] || 'Select Image',
        onSelect: function(media) {
            // Store the value
            document.getElementById(key + '-input').value = media.url;

            // Update preview
            const preview = document.getElementById(key + '-preview');
            if (preview) {
                preview.src = media.url;
            } else {
                const empty = document.getElementById(key + '-preview-empty');
                if (empty) {
                    const img = document.createElement('img');
                    img.id    = key + '-preview';
                    img.src   = media.url;
                    img.alt   = key;
                    if (key === 'favicon') {
                        img.style.cssText = 'width:60px;height:60px;object-fit:contain;border-radius:var(--r-sm);border:1px solid var(--border);background:var(--surface-3);padding:.25rem;margin-bottom:.75rem;';
                    } else {
                        img.style.cssText = 'height:60px;max-width:180px;object-fit:contain;border-radius:var(--r);border:1px solid var(--border);background:var(--surface-3);padding:.25rem;margin-bottom:.75rem;';
                    }
                    empty.replaceWith(img);
                }
            }

            cmsToast(titleMap[key] + ' selected', 'success');
        }
    });
}

function clearMediaSetting(key) {
    document.getElementById(key + '-input').value = '';
    const preview = document.getElementById(key + '-preview');
    if (preview) {
        const empty = document.createElement('div');
        empty.id = key + '-preview-empty';
        if (key === 'favicon') {
            empty.className = 'favicon-placeholder';
            empty.innerHTML = '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>';
        } else {
            empty.className = 'logo-placeholder';
            empty.innerHTML = '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>';
        }
        preview.replaceWith(empty);
    }
    cmsToast('Image removed — save to apply', 'info');
}

// ── Token copy ──────────────────────────────────────────────────────────────
function copyToken(token) {
    const text = '[[' + token + ']]';
    navigator.clipboard.writeText(text).then(function() {
        cmsToast(text + ' copied to clipboard', 'success');
    }).catch(function() {
        // Fallback for older browsers
        const ta = document.createElement('textarea');
        ta.value = text;
        ta.style.position = 'fixed';
        ta.style.opacity  = '0';
        document.body.appendChild(ta);
        ta.select();
        document.execCommand('copy');
        document.body.removeChild(ta);
        cmsToast(text + ' copied!', 'success');
    });
}

// ── Scroll-spy for sidebar nav ──────────────────────────────────────────────
var sections = document.querySelectorAll('.section-card[id]');
var navLinks  = document.querySelectorAll('.identity-nav a');

window.addEventListener('scroll', function() {
    var scrollY  = window.scrollY + 120;
    var current  = '';
    sections.forEach(function(s) {
        if (s.offsetTop <= scrollY) current = s.id;
    });
    navLinks.forEach(function(a) {
        a.classList.toggle('active', a.getAttribute('href') === '#' + current);
    });
}, { passive: true });

navLinks.forEach(link => {
    link.addEventListener('click', function(e) {
        e.preventDefault();
        const target = document.getElementById(this.getAttribute('href').substring(1));
        if (target) window.scrollTo({ top: target.offsetTop - 90, behavior: 'smooth' });
    });
});
