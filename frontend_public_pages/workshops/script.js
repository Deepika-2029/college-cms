// Workshops
/* ========================================================================== */
  /* PAGE INTERACTIONS (Filters, Modals, Scroll Reveal, API)                    */
  /* ========================================================================== */
  (function () {
    'use strict';

    const API_KEY = '2a41f966b3ad3103ed29ce8b4c7800abfe39abd245d821c4ac565f15dba956d8';
    const API_URL = `https://gpnainital.com/api/data/workshop?key=${API_KEY}`;
    
    let allWorkshops = [];
    let currentFilter = 'all';

    // Map for department colors
    const deptColors = {
      'it': '#1d4ed8', 'mech': '#d97706', 'mechanical': '#d97706', 'civil': '#9333ea',
      'elec': '#059669', 'electrical': '#059669', 'elex': '#db2777', 'electronics': '#db2777', 
      'pharm': '#be123c', 'pharmacy': '#be123c', 'general': '#3385ff'
    };

    const EMOJIS = ['🔧','⚙️','🏗️','⚡','🔌','🖥️','🔨','🛠️'];

    /* ── 1. Scroll-Reveal ── */
    const revealEls = document.querySelectorAll('.fade-up, .fade-in');
    const revealObserver = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add('visible');
          revealObserver.unobserve(entry.target);
        }
      });
    }, { threshold: 0.1 });
    revealEls.forEach((el) => revealObserver.observe(el));

    /* ── 2. Sanitization Helper ── */
    function escHtml(str) {
      if (!str) return '';
      return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
    }

    function sanitizeImgUrl(url) {
      if (!url) return '';
      return url.replace(/http:\/\/192\.168\.1\.2:8000/gi, 'https://gpnainital.com');
    }

    /* ── 3. Fetch API Data ── */
    async function fetchWorkshops() {
      try {
        const res = await fetch(API_URL);
        if (!res.ok) throw new Error('API Error');
        const json = await res.json();
        allWorkshops = json.data || json.records || [];
        
        document.getElementById('loadingWs').style.display = 'none';

        if (allWorkshops.length > 0) {
          document.getElementById('wsCount').textContent = allWorkshops.length + '+';
          generateFilters(allWorkshops);
          renderWorkshops(allWorkshops);
          initFilterScroll(); // Initialize arrows after filters are built
          
          // Trigger initial "All" filter to start staggered animation
          const allBtn = document.querySelector('.filter-tab[data-f="all"]');
          if (allBtn) allBtn.click();
        } else {
          document.getElementById('errorWs').style.display = 'block';
        }
      } catch (err) {
        console.error("Failed to fetch workshops:", err);
        document.getElementById('loadingWs').style.display = 'none';
        const noRes = document.getElementById('errorWs');
        noRes.querySelector('h4').textContent = "Failed to load workshops";
        noRes.querySelector('p').textContent = "Please try refreshing the page later.";
        noRes.style.display = 'block';
      }
    }

    /* ── 4. Generate Dynamic Filters ── */
    function generateFilters(workshops) {
      const filterContainer = document.getElementById('wsFilter');
      const depts = new Set();
      
      workshops.forEach(ws => {
        const dept = ws.department || ws.dept || ws.type || 'General';
        if (dept.trim() !== '') {
          depts.add(dept.trim());
        }
      });
      
      let html = `<button class="filter-tab active" data-f="all" onclick="window.filterWorkshops('all', this)">All Workshops</button>`;
      
      // Sort alphabetically for consistency
      const sortedDepts = Array.from(depts).sort();
      sortedDepts.forEach(dept => {
        const safeDept = dept.toLowerCase().replace(/[^a-z0-9]/g, '-');
        html += `<button class="filter-tab" data-f="${safeDept}" data-raw="${escHtml(dept)}" onclick="window.filterWorkshops('${safeDept}', this)">
                   ${escHtml(dept)} 
                   <span class="badge"></span>
                 </button>`;
      });
      
      filterContainer.innerHTML = html;
      updateFilterCounts();
    }

    function updateFilterCounts() {
      const tabs = document.querySelectorAll('.filter-tab');
      tabs.forEach(tab => {
        const f = tab.dataset.f;
        const badge = tab.querySelector('.badge');
        if(!badge) return;

        let count = 0;
        if (f === 'all') {
          count = allWorkshops.length;
        } else {
          count = allWorkshops.filter(ws => {
            const deptName = ws.department || ws.dept || ws.type || 'General';
            return deptName.toLowerCase().replace(/[^a-z0-9]/g, '-') === f;
          }).length;
        }
        badge.textContent = count;
      });
    }

    /* ── 4.5 Initialize Filter Scroll Arrows ── */
    function initFilterScroll() {
      const container = document.getElementById('wsFilter');
      const leftBtn = document.getElementById('filterArrowLeft');
      const rightBtn = document.getElementById('filterArrowRight');
      if(!container || !leftBtn || !rightBtn) return;

      const updateArrows = () => {
        // Only run arrow logic on mobile where the wrapper exists and is flex
        if (window.innerWidth > 768) {
          leftBtn.style.opacity = '0';
          rightBtn.style.opacity = '0';
          leftBtn.style.pointerEvents = 'none';
          rightBtn.style.pointerEvents = 'none';
          return;
        }

        const scrollLeft = container.scrollLeft;
        const maxScroll = container.scrollWidth - container.clientWidth;
        
        leftBtn.style.opacity = scrollLeft > 5 ? '1' : '0';
        leftBtn.style.pointerEvents = scrollLeft > 5 ? 'auto' : 'none';
        
        rightBtn.style.opacity = scrollLeft < maxScroll - 5 ? '1' : '0';
        rightBtn.style.pointerEvents = scrollLeft < maxScroll - 5 ? 'auto' : 'none';
      };

      container.addEventListener('scroll', updateArrows);
      window.addEventListener('resize', updateArrows);
      
      leftBtn.addEventListener('click', () => {
        container.scrollBy({ left: -150, behavior: 'smooth' });
      });
      rightBtn.addEventListener('click', () => {
        container.scrollBy({ left: 150, behavior: 'smooth' });
      });

      // Call once after a slight delay to allow rendering calculations
      setTimeout(updateArrows, 150);
    }

    /* ── 5. Render Workshops ── */
    function renderWorkshops(workshops) {
      const grid = document.getElementById('wsGrid');
      grid.querySelectorAll('.ws-card-wrap').forEach(el => el.remove());

      workshops.forEach((ws, idx) => {
        const title = ws.title || ws.workshop_name || ws.name || '—';
        const deptName = ws.department || ws.dept || ws.type || 'General';
        const deptKey = deptName.toLowerCase().replace(/[^a-z0-9]/g, '-');
        const tools = ws.tools || ws.machines || ws.equipment || '—';
        const cap = ws.capacity || '—';
        const incharge = ws.incharge || ws.in_charge || ws.supervisor || '—';
        const emoji = EMOJIS[idx % EMOJIS.length];

        // Determine color based on department string
        let bg = deptColors['general'];
        for (const [key, color] of Object.entries(deptColors)) {
          if (deptName.toLowerCase().includes(key)) {
            bg = color;
            break;
          }
        }

        let images = [];
        const rawImageUrl = ws.image_url || ws.image || ws.images;
        try {
          if (rawImageUrl && rawImageUrl !== 'undefined') {
             const parsed = JSON.parse(rawImageUrl);
             images = Array.isArray(parsed) ? parsed : [rawImageUrl];
          }
        } catch(e) {
          if (rawImageUrl) images = [rawImageUrl];
        }
        const coverImg = images.length > 0 ? sanitizeImgUrl(images[0]) : '';
        const toolsSnippet = tools.substring(0, 30) + (tools.length > 30 ? '…' : '');

        const wrap = document.createElement('div');
        wrap.className = 'ws-card-wrap';
        wrap.setAttribute('data-dept', deptKey);
        wrap.style.display = 'none'; // Hidden until filtered

        // Store strings securely on attributes
        wrap.innerHTML = `
          <div class="ws-card" 
               data-title="${escHtml(title)}" 
               data-dept="${deptKey}" 
               data-deptname="${escHtml(deptName)}" 
               data-emoji="${emoji}" 
               data-bg="${bg}"
               data-images="${escHtml(rawImageUrl)}"
               data-docs="${escHtml(ws.document_url || ws.docs || '')}"
               data-desc="${escHtml(ws.description || ws.desc || '')}"
               data-tools="${escHtml(tools)}"
               data-cap="${escHtml(cap)}"
               data-incharge="${escHtml(incharge)}"
               onclick="window.openWsModal(this)">
               
            <div class="ws-top" style="background-color: ${bg}; ${coverImg ? `background-image: url('${coverImg}');` : ''}">
                ${!coverImg ? emoji : ''}
            </div>
            <div class="ws-body">
              <span class="ws-badge" style="color:${bg}; background:${bg}15; border-color:${bg}30;">${escHtml(deptName)}</span>
              <h3 class="ws-title">${escHtml(title)}</h3>
              
              <div class="ws-meta-sm">
                <span><i class="bi bi-tools"></i> <span>${escHtml(toolsSnippet)}</span></span>
                <span><i class="bi bi-people-fill"></i> <span>${escHtml(cap)}</span></span>
              </div>

              <div class="ws-action"><i class="bi bi-eye"></i> View Details</div>
            </div>
          </div>
        `;
        
        grid.insertBefore(wrap, document.getElementById('errorWs'));
      });
    }

    /* ── 6. Filter & Search Logic ── */
    window.filterWorkshops = function(filterCat, btn) {
      if (btn) {
        document.querySelectorAll('#wsFilter .filter-tab').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        currentFilter = filterCat;
        
        // Scroll to clicked button if on mobile to keep it visible
        if(window.innerWidth <= 768) {
          const container = document.getElementById('wsFilter');
          const scrollTarget = btn.offsetLeft - container.offsetWidth / 2 + btn.offsetWidth / 2;
          container.scrollTo({ left: scrollTarget, behavior: 'smooth' });
        }
      }
      
      const q = document.getElementById('searchInput').value.toLowerCase();
      let visibleCount = 0;
      
      document.querySelectorAll('.ws-card-wrap').forEach((wrap) => {
        const card = wrap.querySelector('.ws-card');
        const title = card.dataset.title.toLowerCase();
        const tools = card.dataset.tools.toLowerCase();
        const deptKey = wrap.dataset.dept;

        const matchCat = (currentFilter === 'all' || deptKey === currentFilter);
        const matchQuery = (title.includes(q) || tools.includes(q));

        if (matchCat && matchQuery) {
          wrap.style.display = 'block';
          wrap.style.animation = 'none';
          void wrap.offsetWidth; // Trigger reflow
          wrap.style.animation = `fadeInSlideUp 0.4s ease-out forwards`;
          wrap.style.animationDelay = `${(visibleCount % 6) * 0.05}s`;
          visibleCount++;
        } else {
          wrap.style.display = 'none';
        }
      });

      document.getElementById('errorWs').style.display = (visibleCount === 0 && allWorkshops.length > 0) ? 'block' : 'none';
    };

    /* ── 7. Modal Logic ── */
    const visualEl = document.getElementById('mVisual');
    const carouselEl = document.getElementById('wsCarousel');
    const carouselInner = document.getElementById('wsCarouselInner');
    const emojiOverlay = document.getElementById('mEmojiOverlay');
    const carouselControls = document.querySelectorAll('#wsCarousel .carousel-control-prev, #wsCarousel .carousel-control-next');

    window.openWsModal = function(card) {
      const bg = card.dataset.bg || 'var(--primary-l)';
      const emoji = card.dataset.emoji || '🔧';
      
      // Setup Badge
      document.getElementById('mDeptTag').style.color = bg;
      document.getElementById('mDeptTag').style.backgroundColor = `${bg}15`;
      document.getElementById('mDeptTag').style.borderColor = `${bg}30`;
      document.getElementById('mDeptTag').textContent = card.dataset.deptname || '';
      
      // Setup Content
      document.getElementById('mTitle').textContent   = card.dataset.title   || '';
      document.getElementById('mDesc').textContent    = card.dataset.desc    || 'No description available.';
      document.getElementById('mTools').textContent   = card.dataset.tools   || '—';
      document.getElementById('mCap').textContent     = card.dataset.cap     || '—';
      document.getElementById('mIncharge').textContent= card.dataset.incharge|| '—';

      // Setup Carousel / Visual logic with Netflix-style ambient background
      const imagesData = card.dataset.images;
      let images = [];
      try {
        if(imagesData && imagesData !== 'undefined' && imagesData !== 'null') {
          const parsed = JSON.parse(imagesData);
          images = Array.isArray(parsed) ? parsed : [imagesData];
        }
      } catch(e) {
        if (imagesData && imagesData.trim() !== '') {
          images = [imagesData]; 
        }
      }

      if (images.length > 0) {
          let html = '';
          images.forEach((img, i) => {
              const safeImg = sanitizeImgUrl(img);
              html += `
              <div class="carousel-item h-100 ${i === 0 ? 'active' : ''}" style="position:relative; overflow:hidden; background-color:#000;">
                  <div style="position:absolute; inset:-20px; background-image:url('${safeImg}'); background-size:cover; background-position:center; filter:blur(15px); opacity:0.6;"></div>
                  <img src="${safeImg}" class="d-block w-100 h-100" style="object-fit:contain; position:relative; z-index:2;" alt="Workshop Image">
              </div>`;
          });
          carouselInner.innerHTML = html;
          carouselEl.style.display = 'block';
          emojiOverlay.style.display = 'none';
          visualEl.style.backgroundColor = '#000';

          carouselControls.forEach(c => c.style.display = images.length > 1 ? 'flex' : 'none');
      } else {
          showFallback(emoji, bg);
      }

      // Setup Documents Section
      const docsData = card.dataset.docs;
      const docsContainer = document.getElementById('mDocsContainer');
      const docsList = document.getElementById('mDocsList');
      
      let docs = [];
      try {
        if(docsData && docsData !== 'undefined' && docsData !== 'null') {
          docs = JSON.parse(docsData);
          if(!Array.isArray(docs)) docs = [];
        }
      } catch(e) {
        docs = [];
      }

      if (docs.length > 0) {
        docsList.innerHTML = docs.map(doc => {
          const safeUrl = sanitizeImgUrl(doc.url || doc.document_url || '#');
          const docName = doc.name || doc.title || 'Document';
          return `
            <a href="${safeUrl}" target="_blank" class="m-doc-item">
              <div class="m-doc-item-left">
                <div class="m-doc-icon"><i class="bi bi-file-earmark-text-fill"></i></div>
                <div class="m-doc-title">${docName}</div>
              </div>
              <i class="bi bi-download m-doc-dl"></i>
            </a>
          `;
        }).join('');
        docsContainer.style.display = 'block';
      } else {
        docsContainer.style.display = 'none';
      }

      document.getElementById('wsModalOverlay').classList.add('open');
      document.body.style.overflow = 'hidden';
    };

    function showFallback(emoji, bg) {
        carouselEl.style.display = 'none';
        emojiOverlay.style.display = 'flex';
        emojiOverlay.textContent = emoji;
        visualEl.style.backgroundColor = bg;
    }

    const overlay = document.getElementById('wsModalOverlay');
    const closeBtnFloating = document.getElementById('mCloseFloating');

    function closeWsModal() {
      overlay.classList.remove('open');
      document.body.style.overflow = '';
    }

    if(closeBtnFloating) closeBtnFloating.addEventListener('click', closeWsModal);
    if(overlay) overlay.addEventListener('click', e => {
      if (e.target === overlay) closeWsModal();
    });
    document.addEventListener('keydown', e => {
      if (e.key === 'Escape') closeWsModal();
    });

    /* ── INIT ── */
    fetchWorkshops();

  })();