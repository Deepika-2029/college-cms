// Downloads_Page
(function () {
  'use strict';

  /* ══════════════════════════════════════════════════════
     CONFIG — apni API key yahan daalo
  ══════════════════════════════════════════════════════ */
  const API_KEY = '6fee47e273120f7caf24e62b3c52c6222869d4ac6917102f3292b191199a054a';
  const API_URL = `https://gpnainital.com/api/data/downloads?key=${API_KEY}`;

  /* ══════════════════════════════════════════════════════
     STATE
  ══════════════════════════════════════════════════════ */
  let allDocs     = [];
  let currentCat  = 'all';

  const container   = document.getElementById('dlContainer');
  const filterTabs  = document.getElementById('filterTabs');
  const searchInput = document.getElementById('dlSearch');

  /* ── Category icon map ── */
  const CAT_ICONS = {
    'notice':       'bi-megaphone-fill',
    'notices':      'bi-megaphone-fill',
    'form':         'bi-file-earmark-text-fill',
    'forms':        'bi-file-earmark-text-fill',
    'result':       'bi-award-fill',
    'results':      'bi-award-fill',
    'admit card':   'bi-card-heading',
    'admit cards':  'bi-card-heading',
    'syllabus':     'bi-book-fill',
    'circular':     'bi-envelope-paper-fill',
    'circulars':    'bi-envelope-paper-fill',
    'brochure':     'bi-image-fill',
    'brochures':    'bi-image-fill',
    'schedule':     'bi-calendar-event-fill',
    'timetable':    'bi-calendar3',
    'fee':          'bi-cash-stack',
    'fees':         'bi-cash-stack',
    'default':      'bi-folder-fill',
  };

  function getCatIcon(cat) {
    const key = (cat || '').toLowerCase().trim();
    return CAT_ICONS[key] || CAT_ICONS['default'];
  }

  /* ── File type icon class ── */
  function getFileIconClass(url, type) {
    const str = ((url || '') + (type || '')).toLowerCase();
    if (str.includes('pdf'))                        return 'pdf';
    if (str.includes('doc') || str.includes('word')) return 'doc';
    if (str.includes('xls') || str.includes('sheet')) return 'xls';
    if (str.includes('jpg') || str.includes('png') || str.includes('jpeg') || str.includes('webp')) return 'img';
    if (str.includes('zip') || str.includes('rar')) return 'zip';
    return 'file';
  }

  function getFileIconSymbol(cls) {
    const map = { pdf: 'bi-file-earmark-pdf-fill', doc: 'bi-file-earmark-word-fill', xls: 'bi-file-earmark-excel-fill', img: 'bi-file-earmark-image-fill', zip: 'bi-file-zip-fill', file: 'bi-file-earmark-fill' };
    return map[cls] || map['file'];
  }

  /* ── Format date ── */
  function fmtDate(d) {
    if (!d) return '';
    const dt = new Date(d);
    if (isNaN(dt)) return d;
    return dt.toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' });
  }

  /* ── Format file size ── */
  function fmtSize(bytes) {
    if (!bytes || isNaN(bytes)) return '';
    const b = Number(bytes);
    if (b < 1024) return b + ' B';
    if (b < 1024 * 1024) return (b / 1024).toFixed(1) + ' KB';
    return (b / (1024 * 1024)).toFixed(1) + ' MB';
  }

  /* ── Is this month? ── */
  function isThisMonth(d) {
    if (!d) return false;
    const dt = new Date(d);
    const now = new Date();
    return dt.getMonth() === now.getMonth() && dt.getFullYear() === now.getFullYear();
  }

  /* ── Normalise record fields ── */
  function norm(r) {
    return {
      id:       r.id || r.doc_id || '',
      title:    r.title || r.name || r.document_name || r.file_name || 'Untitled',
      category: r.category || r.cat || r.type || 'General',
      url:      r.url || r.file_url || r.download_url || r.link || '#',
      file_type:r.file_type || r.type || '',
      size:     r.size || r.file_size || '',
      date:     r.date || r.created_at || r.upload_date || '',
      desc:     r.description || r.desc || '',
    };
  }

  /* ══════════════════════════════════════════════════════
     BUILD UI
  ══════════════════════════════════════════════════════ */

  /* Inject dynamic category tabs */
  function buildTabs(docs) {
    const cats = [...new Set(docs.map(d => d.category))].filter(Boolean).sort();
    const existing = filterTabs.querySelectorAll('.filter-tab:not([data-cat="all"])');
    existing.forEach(e => e.remove());

    cats.forEach(cat => {
      const btn = document.createElement('button');
      btn.className = 'filter-tab';
      btn.dataset.cat = cat;
      btn.textContent = cat;
      filterTabs.appendChild(btn);
    });
  }

  /* Build grouped sections */
  function buildSections(docs) {
    if (docs.length === 0) {
      container.innerHTML = `
        <div class="dl-state empty">
          <i class="bi bi-folder2-open"></i>
          <h4>No documents found</h4>
          <p>Try adjusting your search or category filter.</p>
        </div>`;
      return;
    }

    // Group by category
    const groups = {};
    docs.forEach(d => {
      const cat = d.category || 'General';
      if (!groups[cat]) groups[cat] = [];
      groups[cat].push(d);
    });

    let html = '';
    let itemIdx = 0;

    Object.keys(groups).sort().forEach(cat => {
      const items = groups[cat];
      const icon  = getCatIcon(cat);

      let rowsHtml = items.map((d, i) => {
        const fcls  = getFileIconClass(d.url, d.file_type);
        const fsym  = getFileIconSymbol(fcls);
        const delay = (itemIdx++ % 10) * 0.04;
        const size  = fmtSize(d.size);
        const date  = fmtDate(d.date);
        const isNew = isThisMonth(d.date);

        return `
          <div class="dl-item" style="animation-delay:${delay}s" data-cat="${cat}" data-title="${d.title.toLowerCase()}">
            <div class="dl-file-icon ${fcls}">
              <i class="bi ${fsym}"></i>
            </div>
            <div class="dl-info">
              <div class="dl-title">
                ${d.title}
                ${isNew ? '<span class="badge bg-success ms-2" style="font-size:.65rem;vertical-align:middle;">NEW</span>' : ''}
              </div>
              <div class="dl-meta">
                <span class="badge-cat">${cat}</span>
                ${date ? `<span><i class="bi bi-calendar3"></i>${date}</span>` : ''}
                ${size ? `<span><i class="bi bi-hdd"></i>${size}</span>` : ''}
                ${d.desc ? `<span><i class="bi bi-info-circle"></i>${d.desc}</span>` : ''}
              </div>
            </div>
            <a href="${d.url}" target="_blank" rel="noopener" class="dl-btn" download>
              <i class="bi bi-download"></i> Download
            </a>
          </div>`;
      }).join('');

      html += `
        <div class="dl-section" data-section="${cat}">
          <div class="dl-section-header">
            <div class="dl-section-icon"><i class="bi ${icon}"></i></div>
            <h2 class="dl-section-title">${cat}</h2>
            <span class="dl-section-count">${items.length} file${items.length !== 1 ? 's' : ''}</span>
          </div>
          <div class="dl-rows">${rowsHtml}</div>
        </div>`;
    });

    container.innerHTML = html;
  }

  /* Update hero stats */
  function updateStats(docs) {
    const total = docs.length;
    const cats  = new Set(docs.map(d => d.category)).size;
    const newC  = docs.filter(d => isThisMonth(d.date)).length;

    document.getElementById('statTotal').textContent = total;
    document.getElementById('statCats').textContent  = cats;
    document.getElementById('statNew').textContent   = newC;
    document.getElementById('heroStats').style.display = 'flex';
  }

  /* ══════════════════════════════════════════════════════
     FILTER
  ══════════════════════════════════════════════════════ */
  window.applyFilters = function () {
    const query    = (searchInput.value || '').toLowerCase().trim();
    const sections = container.querySelectorAll('.dl-section');
    let totalVisible = 0;

    sections.forEach(section => {
      const sectionCat = section.dataset.section;
      const catMatch   = currentCat === 'all' || sectionCat === currentCat;

      if (!catMatch) { section.style.display = 'none'; return; }
      section.style.display = 'block';

      const items = section.querySelectorAll('.dl-item');
      let sectionVisible = 0;

      items.forEach((item, idx) => {
        const titleMatch = !query || item.dataset.title.includes(query);
        if (titleMatch) {
          item.style.display = 'flex';
          item.style.animation = 'none';
          void item.offsetWidth;
          item.style.animation = `fadeInSlideUp 0.35s ease-out forwards`;
          item.style.animationDelay = `${(sectionVisible % 10) * 0.04}s`;
          sectionVisible++;
          totalVisible++;
        } else {
          item.style.display = 'none';
        }
      });

      // Hide section if no items match
      section.style.display = sectionVisible > 0 ? 'block' : 'none';

      // Update section count badge
      const badge = section.querySelector('.dl-section-count');
      if (badge) badge.textContent = `${sectionVisible} file${sectionVisible !== 1 ? 's' : ''}`;
    });

    // Show empty state if nothing matches
    let emptyEl = container.querySelector('.dl-state.empty');
    if (totalVisible === 0 && sections.length > 0) {
      if (!emptyEl) {
        emptyEl = document.createElement('div');
        emptyEl.className = 'dl-state empty';
        emptyEl.innerHTML = `<i class="bi bi-folder2-open"></i><h4>No documents found</h4><p>Try adjusting your search or category filter.</p>`;
        container.appendChild(emptyEl);
      }
      emptyEl.style.display = 'block';
    } else if (emptyEl) {
      emptyEl.style.display = 'none';
    }
  };

  /* ══════════════════════════════════════════════════════
     FILTER TAB CLICK
  ══════════════════════════════════════════════════════ */
  filterTabs.addEventListener('click', e => {
    const btn = e.target.closest('.filter-tab');
    if (!btn) return;
    filterTabs.querySelectorAll('.filter-tab').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    currentCat = btn.dataset.cat;
    searchInput.value = '';
    applyFilters();
  });

  /* ══════════════════════════════════════════════════════
     FETCH API
  ══════════════════════════════════════════════════════ */
  async function fetchDownloads() {
    try {
      const res = await fetch(API_URL);
      if (!res.ok) throw new Error(`HTTP ${res.status}`);
      const json = await res.json();

      const records = json.data || json.records || json || [];
      if (!Array.isArray(records)) throw new Error('Unexpected API response format');

      allDocs = records.map(norm);

      if (allDocs.length === 0) {
        container.innerHTML = `
          <div class="dl-state empty">
            <i class="bi bi-folder2-open"></i>
            <h4>No documents available</h4>
            <p>Koi bhi document abhi upload nahi hua hai.</p>
          </div>`;
        return;
      }

      buildTabs(allDocs);
      buildSections(allDocs);
      updateStats(allDocs);

    } catch (err) {
      console.error('Downloads API error:', err);
      container.innerHTML = `
        <div class="dl-state error">
          <i class="bi bi-exclamation-triangle-fill"></i>
          <h4>Failed to load documents</h4>
          <p>Server se data nahi aa saka. Please try again later.<br>
          <small class="text-muted">${err.message}</small></p>
        </div>`;
    }
  }

  /* ══════════════════════════════════════════════════════
     SCROLL REVEAL
  ══════════════════════════════════════════════════════ */
  const revealEls = document.querySelectorAll('.fade-up, .fade-in');
  if (revealEls.length) {
    const obs = new IntersectionObserver(
      entries => entries.forEach(en => {
        if (en.isIntersecting) { en.target.classList.add('visible'); obs.unobserve(en.target); }
      }),
      { threshold: 0.1 }
    );
    revealEls.forEach(el => obs.observe(el));
  }

  /* ── Init ── */
  fetchDownloads();

})();