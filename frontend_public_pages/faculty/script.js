// Faculty_page
(function () {
  'use strict';

  /* ══════════════════════════════════════════════════════════
     CONFIG — apni API key yahan daalo
  ══════════════════════════════════════════════════════════ */
  const API_KEY   = '4f86b1b9751adcc91f492f54d632f62bb566075e5568b3da7bab52582e08ce78';
  const API_URL   = `https://gpnainital.com/api/data/faculty?key=${API_KEY}`;

  /* 
    Department → Year mapping.
    Applied Science = 1st Year
    Baki sab departments ko aap apni zaroorat ke hisaab se set karein.
    API record mein agar "year" column hai toh woh directly use hoga —
    yeh mapping sirf fallback ke liye hai.
  */
  const DEPT_YEAR_MAP = {
    // 1st Year departments
    'applied science':    '1',
    'basic science':      '1',
    'applied sciences':   '1',
    // 2nd Year departments (examples — update as needed)
    'information technology': '2',
    'it':                 '2',
    'civil':              '2',
    'civil engineering':  '2',
    'mechanical':         '2',
    'mechanical engineering': '2',
    'electrical':         '2',
    'electrical engineering': '2',
    'electronics':        '2',
    'electronics engineering': '2',
    'pharmacy':           '2',
    // 3rd Year departments (update as needed)
    'pharmacy (3rd)':     '3',
    'diploma final':      '3',
  };

  const BATCH = 8;
  let allFaculty = [];   // raw API data
  let filteredFaculty = [];
  let visibleCount = BATCH;
  let currentFilter = 'all';

  /* ── Department keyword maps ── */
  const DEPT_FILTER_MAP = {
    'it':     ['information technology', 'it', 'information tech'],
    'elex':   ['electronics', 'electronics engineering', 'elex', 'elect. & comm.', 'ece'],
    'elec':   ['electrical', 'electrical engineering', 'elec', 'ee'],
    'civil':  ['civil', 'civil engineering'],
    'mech':   ['mechanical', 'mechanical engineering', 'mech'],
    'pharma': ['pharmacy', 'pharma', 'pharmaceutics'],
  };

  const grid        = document.getElementById('facultyGrid');
  const loadMoreBtn = document.getElementById('loadMoreBtn');
  const loadCount   = document.getElementById('loadCount');
  const loadMoreWrap = document.getElementById('loadMoreWrap');

  /* ── Helper: get initials ── */
  function getInitials(name) {
    if (!name) return '??';
    return name.trim().split(/\s+/).map(w => w[0]).slice(0, 2).join('').toUpperCase();
  }

  /* ── Helper: resolve year from record ── */
  function resolveYear(record) {
    if (record.year) return String(record.year).trim();
    const dept = (record.department || record.dept || '').toLowerCase().trim();
    return DEPT_YEAR_MAP[dept] || 'all';
  }

  /* ── Helper: year label ── */
  function yearLabel(year) {
    if (year === '1') return '1st Year';
    if (year === '2') return '2nd Year';
    if (year === '3') return '3rd Year';
    return '–';
  }

  /* ── Helper: match faculty to active filter ── */
  function matchesFilter(f, filter) {
    if (filter === 'all') return true;

    const dept = (f.department || f.dept || f.branch || '').toLowerCase().trim();
    const desg = (f.designation || f.desg || f.post || '').toLowerCase().trim();

    if (filter === 'principal') {
      return desg.includes('principal');
    }
    if (filter === 'hod') {
      return desg.includes('hod') || desg.includes('head of department') || desg.includes('head of dept');
    }
    if (filter === '1st year') {
      return resolveYear(f) === '1';
    }
    // Department filters
    const keywords = DEPT_FILTER_MAP[filter];
    if (keywords) return keywords.some(k => dept.includes(k));

    return false;
  }

  /* ── Build one card HTML ── */
  function buildCard(f, index) {
    const name     = f.name        || f.faculty_name || f.full_name || '–';
    const desg     = f.designation || f.desg         || f.post      || '–';
    const dept     = f.department  || f.dept         || f.branch    || '–';
    const qual     = f.qualification|| f.qual        || f.education || '–';
    const exp      = f.experience  || f.exp          || '–';
    const email    = f.email       || '–';
    const photo    = f.photo       || f.image        || f.photo_url || '';
    const year     = resolveYear(f);
    const initials = getInitials(name);
    const delay    = (index % BATCH) * 0.05;

    const avatarHtml = photo
      ? `<img src="${photo}" class="fac-avatar" alt="${name}" onerror="this.outerHTML='<div class=\\'fac-avatar\\'>${initials}</div>'">`
      : `<div class="fac-avatar">${initials}</div>`;

    return `
      <div class="fac-card-wrap" style="animation-delay:${delay}s">
        <div class="fac-card"
          data-name="${name}"
          data-desg="${desg}"
          data-deptname="${dept}"
          data-qual="${qual}"
          data-exp="${exp}"
          data-email="${email}"
          data-photo="${photo}"
          data-initials="${initials}"
          data-year="${year}">
          <div class="fac-top"></div>
          <div class="fac-avatar-wrap">${avatarHtml}</div>
          <div class="fac-body">
            <h3 class="fac-name">${name}</h3>
            <div class="fac-desg">${desg}</div>
            <div class="fac-dept-badge">${dept}</div>
            <div class="fac-action">View Profile <i class="bi bi-arrow-right"></i></div>
          </div>
        </div>
      </div>`;
  }

  /* ── Render visible cards ── */
  function renderCards() {
    // Filter by active filter
    filteredFaculty = currentFilter === 'all'
      ? allFaculty
      : allFaculty.filter(f => matchesFilter(f, currentFilter));

    const slice = filteredFaculty.slice(0, visibleCount);

    if (filteredFaculty.length === 0) {
      grid.innerHTML = `
        <div class="fac-empty">
          <i class="bi bi-person-x"></i>
          <h4>No faculty found</h4>
          <p>No faculty members found for the selected filter.</p>
        </div>`;
      loadMoreWrap.style.display = 'none';
      return;
    }

    grid.innerHTML = slice.map((f, i) => buildCard(f, i)).join('');

    // Load More button state
    if (visibleCount >= filteredFaculty.length) {
      loadMoreBtn.style.display = 'none';
      loadCount.innerHTML = `<i class="bi bi-check-circle-fill text-success"></i> Showing all ${filteredFaculty.length} members`;
    } else {
      loadMoreBtn.style.display = 'inline-flex';
      loadCount.textContent = `Showing ${slice.length} of ${filteredFaculty.length} — ${filteredFaculty.length - slice.length} more available`;
    }
    loadMoreWrap.style.display = 'block';
  }

  /* ── Fetch faculty from API ── */
  async function fetchFaculty() {
    try {
      const res = await fetch(API_URL);
      if (!res.ok) throw new Error(`HTTP ${res.status}`);
      const json = await res.json();

      // API returns { data: [...] }
      const records = json.data || json.records || json || [];
      if (!Array.isArray(records)) throw new Error('Unexpected API response format');

      allFaculty = records;
      renderCards();

    } catch (err) {
      console.error('Faculty API error:', err);
      grid.innerHTML = `
        <div class="fac-error">
          <i class="bi bi-exclamation-triangle-fill"></i>
          <h4>Failed to load faculty</h4>
          <p>Could not fetch data from the server. Please try again later.<br>
          <small class="text-muted">${err.message}</small></p>
        </div>`;
      loadMoreWrap.style.display = 'none';
    }
  }

  /* ── Filter tab clicks ── */
  document.getElementById('filterTabs').addEventListener('click', e => {
    const btn = e.target.closest('.filter-tab');
    if (!btn) return;
    document.querySelectorAll('.filter-tab').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    currentFilter = btn.dataset.filter;
    visibleCount = BATCH;
    renderCards();
  });

  /* ── Load More ── */
  loadMoreBtn.addEventListener('click', () => {
    visibleCount += BATCH;
    renderCards();
  });

  /* ── Modal Logic ── */
  grid.addEventListener('click', e => {
    const card = e.target.closest('.fac-card');
    if (!card) return;

    const avatarEl = document.getElementById('mAvatar');
    const photoUrl = card.dataset.photo;
    const initials = card.dataset.initials || '??';

    if (photoUrl && photoUrl !== 'undefined' && photoUrl !== '') {
      avatarEl.innerHTML = `<img src="${photoUrl}" alt="${card.dataset.name}" style="width:100%; height:100%; object-fit:cover; border-radius:50%;">`;
      avatarEl.style.backgroundColor = '#fff';
    } else {
      avatarEl.innerHTML = initials;
      avatarEl.style.backgroundColor = 'var(--digital-blue-50)';
    }

    document.getElementById('mName').textContent    = card.dataset.name     || '–';
    document.getElementById('mDesg').textContent    = card.dataset.desg     || '–';
    document.getElementById('mDeptTag').textContent = card.dataset.deptname || '–';
    document.getElementById('mQual').textContent    = card.dataset.qual     || '–';
    document.getElementById('mDept').textContent    = card.dataset.deptname || '–';
    document.getElementById('mExp').textContent     = card.dataset.exp      || '–';
    document.getElementById('mEmail').textContent   = card.dataset.email    || '–';
    document.getElementById('mYear').textContent    = yearLabel(card.dataset.year);

    document.getElementById('facModalOverlay').classList.add('open');
    document.body.style.overflow = 'hidden';
  });

  const overlay = document.getElementById('facModalOverlay');
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

  /* ── Init: fetch data ── */
  fetchFaculty();

})();