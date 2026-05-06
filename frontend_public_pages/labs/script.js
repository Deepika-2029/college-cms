// labs_page
/* ========================================================================== */
  /* PAGE INTERACTIONS (Filters, Modals, Scroll Reveal, API)                    */
  /* ========================================================================== */
  (function () {
    'use strict';

    const API_URL = 'https://gpnainital.com/api/data/labs?key=aa92b03d59d1f5c56ca11872264dd074d8583bb88018e23dcf77549c9b3d0257';
    let allLabs = [];

    // Map for department colors
    const deptColors = {
      'it': '#1d4ed8', 'mech': '#d97706', 'civil': '#9333ea',
      'elec': '#059669', 'elex': '#db2777', 'pharm': '#be123c', 'general': '#3385ff'
    };

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
    async function fetchLabs() {
      try {
        const res = await fetch(API_URL);
        if (!res.ok) throw new Error('API Error');
        const json = await res.json();
        allLabs = json.data || json.records || [];
        
        document.getElementById('loadingLabs').style.display = 'none';

        if (allLabs.length > 0) {
          generateFilters(allLabs);
          renderLabs(allLabs);
          
          // Trigger initial "All" filter to start staggered animation
          const allBtn = document.querySelector('.filter-tab[data-f="all"]');
          if (allBtn) allBtn.click();
        } else {
          document.getElementById('noResults').style.display = 'block';
        }
      } catch (err) {
        console.error("Failed to fetch labs:", err);
        document.getElementById('loadingLabs').style.display = 'none';
        const noRes = document.getElementById('noResults');
        noRes.querySelector('h4').textContent = "Failed to load labs";
        noRes.querySelector('p').textContent = "Please try refreshing the page later.";
        noRes.style.display = 'block';
      }
    }

    /* ── 4. Generate Dynamic Filters ── */
    function generateFilters(labs) {
      const filterContainer = document.getElementById('filterTabs');
      const depts = new Map();
      
      labs.forEach(lab => {
        if (lab.department_key && lab.department_name) {
          depts.set(lab.department_key, lab.department_name);
        }
      });
      
      let html = `<button class="filter-tab active" data-f="all" onclick="window.filterLabs('all', this)">All Labs</button>`;
      depts.forEach((name, key) => {
        html += `<button class="filter-tab" data-f="${key}" onclick="window.filterLabs('${key}', this)">${name}</button>`;
      });
      
      filterContainer.innerHTML = html;
    }

    /* ── 5. Render Labs ── */
    function renderLabs(labs) {
      const grid = document.getElementById('labGrid');
      grid.querySelectorAll('.lab-card-wrap').forEach(el => el.remove());

      labs.forEach((lab, idx) => {
        const deptKey = lab.department_key || 'general';
        const deptName = lab.department_name || 'General';
        const bg = deptColors[deptKey] || deptColors['general'];
        const descSnippet = (lab.description || '').substring(0, 120) + ((lab.description && lab.description.length > 120) ? '...' : '');

        let images = [];
        try {
          if (lab.image_url && lab.image_url !== 'undefined') {
             const parsed = JSON.parse(lab.image_url);
             images = Array.isArray(parsed) ? parsed : [lab.image_url];
          }
        } catch(e) {
          if (lab.image_url) images = [lab.image_url];
        }
        const coverImg = images.length > 0 ? sanitizeImgUrl(images[0]) : '';

        const wrap = document.createElement('div');
        wrap.className = 'lab-card-wrap';
        wrap.setAttribute('data-dept', deptKey);
        wrap.style.display = 'none'; // Hidden until filtered

        // Store strings securely on attributes
        wrap.innerHTML = `
          <div class="lab-card" 
               data-title="${escHtml(lab.title)}" 
               data-dept="${deptKey}" 
               data-deptname="${escHtml(deptName)}" 
               data-bg="${bg}"
               data-images="${escHtml(lab.image_url)}"
               data-docs="${escHtml(lab.document_url)}"
               data-desc="${escHtml(lab.description)}"
               data-equip="${escHtml(lab.equipment)}"
               data-capacity="${escHtml(lab.capacity)}"
               data-software="${escHtml(lab.software)}"
               onclick="window.openLabModal(this)">
               
            <div class="lab-top" style="background-color: ${bg}; ${coverImg ? `background-image: url('${coverImg}');` : ''}">
                ${!coverImg ? '<i class="bi bi-image text-white" style="opacity:0.5;"></i>' : ''}
            </div>
            <div class="lab-body">
              <span class="lab-badge" style="color:${bg}; background:${bg}15; border-color:${bg}30;">${escHtml(deptName)}</span>
              <h3 class="lab-title">${escHtml(lab.title)}</h3>
              <p class="lab-desc-preview">${descSnippet}</p>
              <div class="lab-action"><i class="bi bi-eye"></i> View Details</div>
            </div>
          </div>
        `;
        
        grid.insertBefore(wrap, document.getElementById('noResults'));
      });
    }

    /* ── 6. Filter Logic ── */
    window.filterLabs = function(filter, btn) {
      document.querySelectorAll('.filter-tab').forEach(b => b.classList.remove('active'));
      if(btn) btn.classList.add('active');
      
      let visibleCount = 0;
      
      document.querySelectorAll('.lab-card-wrap').forEach((wrap) => {
        if (filter === 'all' || wrap.dataset.dept === filter) {
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

      document.getElementById('noResults').style.display = (visibleCount === 0 && allLabs.length > 0) ? 'block' : 'none';
    };

    /* ── 7. Modal Logic ── */
    const visualEl = document.getElementById('mVisual');
    const carouselEl = document.getElementById('labCarousel');
    const carouselInner = document.getElementById('labCarouselInner');
    const emojiOverlay = document.getElementById('mEmojiOverlay');
    const carouselControls = document.querySelectorAll('#labCarousel .carousel-control-prev, #labCarousel .carousel-control-next');
    const docsContainer = document.getElementById('mDocsContainer');
    const docsList = document.getElementById('mDocsList');

    window.openLabModal = function(card) {
      const bg = card.dataset.bg || 'var(--primary-l)';
      
      // Setup Badge
      document.getElementById('mDeptTag').style.color = bg;
      document.getElementById('mDeptTag').style.backgroundColor = `${bg}15`;
      document.getElementById('mDeptTag').style.borderColor = `${bg}30`;
      document.getElementById('mDeptTag').textContent = card.dataset.deptname || '';
      
      // Setup Content
      document.getElementById('mTitle').textContent   = card.dataset.title   || '';
      document.getElementById('mDesc').textContent    = card.dataset.desc    || '';
      document.getElementById('mEquip').textContent   = card.dataset.equip   || 'Standard Lab Equipment';
      document.getElementById('mCap').textContent     = card.dataset.capacity|| '30 Students';
      
      const software = card.dataset.software;
      if(software && software.toLowerCase() !== 'n/a' && software.trim() !== '') {
          document.getElementById('mSoft').textContent = software;
          document.getElementById('mSoftRow').style.display = 'flex';
      } else {
          document.getElementById('mSoftRow').style.display = 'none';
      }

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
          images = [imagesData]; // fallback if simple string
        }
      }

      if (images.length > 0) {
          let html = '';
          images.forEach((img, i) => {
              const safeImg = sanitizeImgUrl(img);
              html += `
              <div class="carousel-item h-100 ${i === 0 ? 'active' : ''}" style="position:relative; overflow:hidden; background-color:#000;">
                  <div style="position:absolute; inset:-20px; background-image:url('${safeImg}'); background-size:cover; background-position:center; filter:blur(15px); opacity:0.6;"></div>
                  <img src="${safeImg}" class="d-block w-100 h-100" style="object-fit:contain; position:relative; z-index:2;" alt="Lab Image">
              </div>`;
          });
          carouselInner.innerHTML = html;
          carouselEl.style.display = 'block';
          emojiOverlay.style.display = 'none';
          visualEl.style.backgroundColor = '#000';

          carouselControls.forEach(c => c.style.display = images.length > 1 ? 'flex' : 'none');
      } else {
          showFallback(bg);
      }

      // Setup Documents Section
      const docsData = card.dataset.docs;
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

      document.getElementById('labModalOverlay').classList.add('open');
      document.body.style.overflow = 'hidden';
    };

    function showFallback(bg) {
        carouselEl.style.display = 'none';
        emojiOverlay.style.display = 'flex';
        emojiOverlay.innerHTML = '<i class="bi bi-images" style="opacity: 0.5;"></i>';
        visualEl.style.backgroundColor = bg;
    }

    const overlay = document.getElementById('labModalOverlay');
    const closeBtnFloating = document.getElementById('mCloseFloating');

    function closeLabModal() {
      overlay.classList.remove('open');
      document.body.style.overflow = '';
    }

    if(closeBtnFloating) closeBtnFloating.addEventListener('click', closeLabModal);
    if(overlay) overlay.addEventListener('click', e => {
      if (e.target === overlay) closeLabModal();
    });
    document.addEventListener('keydown', e => {
      if (e.key === 'Escape') closeLabModal();
    });

    /* ── INIT ── */
    fetchLabs();

  })();