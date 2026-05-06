// Auto-generated UI Script for: events
document.addEventListener('DOMContentLoaded', function () {
    // Stagger-animate table rows on load
    document.querySelectorAll('.cms-modern-table tbody tr').forEach((row, i) => {
        row.style.opacity = '0';
        row.style.animation = `cmsFadeIn 0.35s ease-out ${i * 0.04}s forwards`;
    });

    // Toggle label update for boolean fields
    document.querySelectorAll('.cms-toggle input[type="checkbox"]').forEach(cb => {
        const lbl = document.getElementById('lbl_' + cb.name);
        if (lbl) {
            const update = () => { lbl.textContent = cb.checked ? 'On' : 'Off'; };
            cb.addEventListener('change', update);
            update();
        }
    });
});

function openCreateForm() {
    const list = document.getElementById('cms-events-list');
    const form = document.getElementById('cms-events-form');
    if (list && form) {
        list.style.display = 'none';
        form.style.display = 'block';
        const t = document.getElementById('form-title');
        if (t) t.textContent = 'Create New Record';
        form.querySelector('form').reset();
    }
}

function closeForm() {
    const list = document.getElementById('cms-events-list');
    const form = document.getElementById('cms-events-form');
    if (list && form) { form.style.display = 'none'; list.style.display = 'block'; }
}

function handleFormSubmit(e) {
    const btn = e.target.querySelector('button[type="submit"]');
    if (btn) {
        btn.innerHTML = '<span style="opacity:.7">Saving…</span>';
        btn.disabled = true;
    }
}

// Stub — connect to your media picker
function openMediaPicker(col, type) {
    if (window.cmsMediaPicker?.open) {
        window.cmsMediaPicker.open({ imagesOnly: type === 'image', onSelect: m => {
            const hidden = document.getElementById('f_' + col);
            if (hidden) { hidden.value = m.url; }
        }});
    }
}

// Add URL inline for media fields
function addMediaUrl(col) {
    const url = prompt('Paste a URL:');
    if (!url) return;
    const hidden = document.getElementById('f_' + col);
    if (hidden) { hidden.value = url; }
}