// Officestaff_Page
(function () {
  'use strict';

  /* ══════════════════════════════════════════════════════
     CONFIG — apni API key yahan daalo
  ══════════════════════════════════════════════════════ */
  const API_KEY = 'a213e105b1f970acbad0d21003650a29b1aa93ef8df780b01c66da4a2b8036ec';
  const API_URL = `https://gpnainital.com/api/data/officestaff?key=${API_KEY}`;

  /*
    Category mapping — API ke "category" / "dept" / "section" field ke values
    ko filter tabs ke data-cat se match karata hai.
    Apni DB ke actual values yahan set karo.
  */
  const CAT_MAP = {
    'administration': 'admin',
    'admin':          'admin',
    'technical':      'technical',
    'it':             'technical',
    'accounts':       'accounts',
    'account':        'accounts',
    'finance':        'accounts',
    'workshop':       'workshop',
  };

  /* ══════════════════════════════════════════════════════
     STATE
  ══════════════════════════════════════════════════════ */
  let allStaff      = [];   // raw records from API
  let currentCat    = 'all';

  const grid        = document.getElementById('staffGrid');
  const searchInput = document.getElementById('staffSearch');

  /* ── Helper: initials from name ── */
  function getInitials(name) {
    if (!name) return '??';
    return name.trim().split(/\s+/).map(w => w[0]).slice(0, 2).join('').toUpperCase();
  }

  /* ── Helper: resolve category from record ── */
  function resolveCat(record) {
    if (record.category) {
      const mapped = CAT_MAP[(record.category + '').toLowerCase().trim()];
      if (mapped) return mapped;
    }
    const dept = (record.department || record.dept || record.section || '').toLowerCase().trim();
    return CAT_MAP[dept] || 'admin';
  }

  /* ── Build one card HTML ── */
  function buildCard(s, index) {
    const name     = s.name         || s.staff_name  || s.full_name  || '–';
    const desg     = s.designation  || s.desg        || s.post       || '–';
    const dept     = s.department   || s.dept        || s.section    || '–';
    const qual     = s.qualification|| s.qual        || s.education  || '–';
    const exp      = s.experience   || s.exp         || '–';
    const email    = s.email        || '–';
    const photo    = s.photo        || s.image       || s.photo_url  || '';
    const cat      = resolveCat(s);
    const initials = getInitials(name);
    const delay    = (index % 8) * 0.05;

    const avatarHtml = photo
      ? `<img src="${photo}" class="staff-avatar" alt="${name}" onerror="this.outerHTML='<div class=\\'staff-avatar\\'>${initials}</div>'">`
      : `<div class="staff-avatar">${initials}</div>`;

    return `
      <div class="staff-card-wrap" data-cat="${cat}" style="animation-delay:${delay}s">
        <div class="staff-card"
          data-name="${name}"
          data-desg="${desg}"
          data-dept="${dept}"
          data-qual="${qual}"
          data-exp="${exp}"
          data-email="${email}"
          data-photo="${photo}"
          data-initials="${initials}">
          <div class="staff-top"></div>
          <div class="staff-avatar-wrap">${avatarHtml}</div>
          <div class="staff-body">
            <h3 class="staff-name">${name}</h3>
            <div class="staff-desg">${desg}</div>
            <div class="staff-dept-badge">${dept}</div>
            <div class="staff-action">View Profile <i class="bi bi-arrow-right"></i></div>
          </div>
        </div>
      </div>`;
  }

  /* ── Apply filters (category + search) ── */
  window.applyFilters = function () {
    const query    = (searchInput.value || '').toLowerCase().trim();
    const wrappers = grid.querySelectorAll('.staff-card-wrap');
    let visible    = 0;

    wrappers.forEach((wrap, i) => {
      const card     = wrap.querySelector('.staff-card');
      const name     = (card.dataset.name || '').toLowerCase();
      const desg     = (card.dataset.desg || '').toLowerCase();
      const catMatch = currentCat === 'all' || wrap.dataset.cat === currentCat;
      const qMatch   = !query || name.includes(query) || desg.includes(query);

      if (catMatch && qMatch) {
        wrap.style.display = 'block';
        wrap.style.animation = 'none';
        void wrap.offsetWidth;
        wrap.style.animation = `fadeInSlideUp 0.4s ease-out forwards`;
        wrap.style.animationDelay = `${(visible % 8) * 0.05}s`;
        visible++;
      } else {
        wrap.style.display = 'none';
      }
    });

    // Show / hide empty state
    let emptyEl = grid.querySelector('.staff-empty');
    if (visible === 0) {
      if (!emptyEl) {
        emptyEl = document.createElement('div');
        emptyEl.className = 'staff-empty';
        emptyEl.innerHTML = `
          <i class="bi bi-person-x"></i>
          <h4>No staff members found</h4>
          <p>Try adjusting your search or category filters.</p>`;
        grid.appendChild(emptyEl);
      }
      emptyEl.style.display = 'block';
    } else if (emptyEl) {
      emptyEl.style.display = 'none';
    }
  };

  /* ── Filter tab click handler ── */
  document.getElementById('filterTabs').addEventListener('click', e => {
    const btn = e.target.closest('.filter-tab');
    if (!btn) return;
    document.querySelectorAll('.filter-tab').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    currentCat = btn.dataset.cat;
    applyFilters();
  });

  /* ── Fetch staff from API ── */
  async function fetchStaff() {
    try {
      const res = await fetch(API_URL);
      if (!res.ok) throw new Error(`HTTP ${res.status}`);
      const json = await res.json();

      const records = json.data || json.records || json || [];
      if (!Array.isArray(records)) throw new Error('Unexpected API response format');

      allStaff = records;

      if (allStaff.length === 0) {
        grid.innerHTML = `
          <div class="staff-empty">
            <i class="bi bi-people"></i>
            <h4>No staff records found</h4>
            <p>No data returned from the server.</p>
          </div>`;
        return;
      }

      grid.innerHTML = allStaff.map((s, i) => buildCard(s, i)).join('');
      applyFilters();

    } catch (err) {
      console.error('Office Staff API error:', err);
      grid.innerHTML = `
        <div class="staff-error">
          <i class="bi bi-exclamation-triangle-fill"></i>
          <h4>Failed to load staff</h4>
          <p>Could not fetch data from the server. Please try again later.<br>
          <small class="text-muted">${err.message}</small></p>
        </div>`;
    }
  }

  /* ── Modal logic ── */
  grid.addEventListener('click', e => {
    const card = e.target.closest('.staff-card');
    if (!card) return;

    const avatarEl = document.getElementById('mAvatar');
    const photoUrl = card.dataset.photo;
    const initials = card.dataset.initials || '??';

    if (photoUrl && photoUrl !== 'undefined' && photoUrl !== '') {
      avatarEl.innerHTML = `<img src="${photoUrl}" alt="${card.dataset.name}" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">`;
      avatarEl.style.backgroundColor = '#fff';
    } else {
      avatarEl.innerHTML = initials;
      avatarEl.style.backgroundColor = 'var(--digital-blue-50)';
    }

    document.getElementById('mName').textContent    = card.dataset.name  || '–';
    document.getElementById('mDesg').textContent    = card.dataset.desg  || '–';
    document.getElementById('mDeptTag').textContent = card.dataset.dept  || '–';
    document.getElementById('mQual').textContent    = card.dataset.qual  || '–';
    document.getElementById('mDept').textContent    = card.dataset.dept  || '–';
    document.getElementById('mExp').textContent     = card.dataset.exp   || '–';
    document.getElementById('mEmail').textContent   = card.dataset.email || '–';

    document.getElementById('staffModalOverlay').classList.add('open');
    document.body.style.overflow = 'hidden';
  });

  const overlay = document.getElementById('staffModalOverlay');
  const closeBtn = document.getElementById('mClose');
  function closeModal() {
    overlay.classList.remove('open');
    document.body.style.overflow = '';
  }
  closeBtn.addEventListener('click', closeModal);
  overlay.addEventListener('click', e => { if (e.target === overlay) closeModal(); });
  document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });

  /* ── Scroll reveal ── */
  const revealEls = document.querySelectorAll('.fade-up, .fade-in');
  if (revealEls.length) {
    const obs = new IntersectionObserver(
      entries => entries.forEach(entry => {
        if (entry.isIntersecting) { entry.target.classList.add('visible'); obs.unobserve(entry.target); }
      }),
      { threshold: 0.1 }
    );
    revealEls.forEach(el => obs.observe(el));
  }

  /* ── Init ── */
  fetchStaff();

})();