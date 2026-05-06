// Nnotice
(function () {
    'use strict';

    const API_URL_NOTICES = 'https://gpnainital.com/api/data/notices?key=8b484ef3cbc00cb09e9f46e92bf359bb896b64d9d50ce6f71a2226efc67db722';
    let currentCat = 'all';
    let allNotices = [];

    /* ── Scroll-Reveal ── */
    const revealEls = document.querySelectorAll('.fade-up');
    const observer = new IntersectionObserver((entries) => {
      entries.forEach((e) => { 
        if(e.isIntersecting) { 
            e.target.classList.add('visible'); 
            observer.unobserve(e.target); 
        } 
      });
    }, { threshold: 0.1 });
    revealEls.forEach(el => observer.observe(el));

    /* ── Helper Functions ── */
    function sanitizeUrl(url) {
      if (!url) return '';
      return url.replace(/http:\/\/192\.168\.1\.2:8000/gi, 'https://gpnainital.com');
    }

    function isNoticeNew(dateStr) {
      if (!dateStr) return false;
      const d = new Date(dateStr);
      if (isNaN(d.getTime())) return false; 
      const absDiff = Date.now() - d.getTime();
      return absDiff >= -86400000 && absDiff <= (30 * 24 * 60 * 60 * 1000);
    }

    /* ── Fetch Notices ── */
    async function fetchNotices() {
      const loader = document.getElementById('loadingNotices');
      const noRes = document.getElementById('noResults');

      try {
        const res = await fetch(API_URL_NOTICES);
        if (!res.ok) throw new Error('API Error');
        const data = await res.json();
        allNotices = data.data || (Array.isArray(data) ? data : []);
        
        loader.style.display = 'none';

        if (allNotices.length > 0) {
          generateFilters(allNotices);
          renderNotices(allNotices);
          window.filterNotices();
        } else {
          noRes.style.display = 'block';
        }
      } catch (err) {
        console.error("Failed to load notices:", err);
        loader.style.display = 'none';
        noRes.querySelector('h4').innerText = "Failed to load notices";
        noRes.querySelector('p').innerText = "Please try refreshing the page later.";
        noRes.style.display = 'block';
      }
    }

    /* ── Generate Dynamic Filters ── */
    function generateFilters(notices) {
      const filterContainer = document.getElementById('filterTabs');
      const categories = new Set();
      
      // Extract unique categories
      notices.forEach(n => {
        const cat = (n.category && n.category.trim() !== '') ? n.category.trim() : 'General';
        categories.add(cat);
      });
      
      const uniqueCategories = Array.from(categories).sort();
      
      // Start with "All Updates"
      let html = `<button class="filter-tab active" data-cat="all" onclick="window.setCat('all', this)">All Updates</button>`;
      
      // Append a tab for each unique category
      uniqueCategories.forEach(cat => {
        const safeCat = cat.toLowerCase().replace(/[^a-z0-9]/g, '-');
        html += `<button class="filter-tab" data-cat="${safeCat}" onclick="window.setCat('${safeCat}', this)">${cat}</button>`;
      });
      
      filterContainer.innerHTML = html;
    }

    /* ── Render Notices ── */
    function renderNotices(notices) {
      const grid = document.getElementById('noticeGrid');
      // Clear existing cards
      grid.querySelectorAll('.notice-card-wrap').forEach(el => el.remove());

      notices.forEach((notice, idx) => {
        const pubDate = notice.publish_date || notice.date || '';
        const fileLink = notice.file_url || notice.fileUrl || '';
        const safeFileUrl = sanitizeUrl(fileLink);
        
        const rawCat = (notice.category && notice.category.trim() !== '') ? notice.category.trim() : 'General';
        const safeCat = rawCat.toLowerCase().replace(/[^a-z0-9]/g, '-');

        const ext = getFileExt(safeFileUrl);
        const fileMeta = safeFileUrl ? (ext ? getFileMeta(ext) : { icon:'bi-file-earmark-text', badge:'idx-fmt-none', label:'DOCUMENT' }) : null;

        const newBadge = isNoticeNew(pubDate) ? `<span class="notice-new-badge">NEW</span>` : '';
        const descSnippet = (notice.description || '').substring(0, 140) + ((notice.description && notice.description.length > 140) ? '...' : '');

        const wrap = document.createElement('div');
        wrap.className = 'notice-card-wrap';
        wrap.setAttribute('data-cat', safeCat);
        wrap.style.display = 'none';

        wrap.innerHTML = `
          <div class="notice-card" 
               data-cat="${safeCat}" 
               data-rawcat="${rawCat.replace(/"/g, '&quot;')}"
               data-title="${(notice.title || 'Notice').replace(/"/g, '&quot;')}" 
               data-body="${(notice.description || '').replace(/"/g, '&quot;')}"
               data-publisher="${(notice.publisher || 'Admin').replace(/"/g, '&quot;')}"
               data-date="${pubDate}"
               data-file="${safeFileUrl}"
               onclick="window.openIdxModal(this)">
            
            <span class="notice-cat cat-${safeCat}">${rawCat}</span>
            <h3 class="notice-title">${notice.title} ${newBadge}</h3>
            <p class="notice-body">${descSnippet}</p>
            
            <div class="notice-footer">
              <span class="notice-date"><i class="bi bi-calendar3"></i> ${pubDate}</span>
              <span class="notice-publisher">${notice.publisher || 'Admin'}</span>
            </div>

            ${fileMeta ? `
            <div class="notice-attachment">
              <span class="btn-download"><i class="bi ${fileMeta.icon}"></i> Attached ${fileMeta.label}</span>
            </div>` : ''}

          </div>
        `;
        grid.insertBefore(wrap, document.getElementById('noResults'));
      });
    }

    /* ── Filter Logic ── */
    window.setCat = function(cat, btn) {
      currentCat = cat;
      document.querySelectorAll('.filter-tab').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      window.filterNotices();
    };

    window.filterNotices = function() {
      const q = document.getElementById('searchInput').value.toLowerCase();
      const wrappers = document.querySelectorAll('.notice-card-wrap');
      let visibleCount = 0;

      wrappers.forEach((wrap, index) => {
        const card = wrap.querySelector('.notice-card');
        const title = (card.dataset.title || '').toLowerCase();
        const body  = (card.dataset.body || '').toLowerCase();
        const cat   = wrap.dataset.cat;

        const matchCat = (currentCat === 'all' || cat === currentCat);
        const matchQuery = (title.includes(q) || body.includes(q));

        if (matchCat && matchQuery) {
          wrap.style.display = 'block';
          wrap.style.animation = 'none';
          void wrap.offsetWidth; // trigger reflow
          wrap.style.animation = `fadeInSlideUp 0.4s ease-out forwards`;
          wrap.style.animationDelay = `${(visibleCount % 12) * 0.05}s`;
          visibleCount++;
        } else {
          wrap.style.display = 'none';
        }
      });

      document.getElementById('noResults').style.display = (visibleCount === 0 && allNotices.length > 0) ? 'block' : 'none';
    };


    /* ── Modal Logic ── */
    const catConfig = {
      admin:   { icon:'bi-exclamation-triangle-fill', col:'idx-col-urgent', badge:'idx-badge-urgent' },
      exam:    { icon:'bi-journal-text',              col:'idx-col-exam',   badge:'idx-badge-exam' },
      event:   { icon:'bi-trophy-fill',               col:'idx-col-event',  badge:'idx-badge-event' },
      general: { icon:'bi-info-circle-fill',          col:'idx-col-general',badge:'idx-badge-general' }
    };

    function getFileExt(url){
      if(!url) return null;
      const m = url.match(/\.([a-z0-9]+)(\?|$)/i);
      return m ? m[1].toLowerCase() : null;
    }
    
    function getFileMeta(ext){
      const map = {
        pdf:  { icon:'bi-file-earmark-pdf',   badge:'idx-fmt-pdf',  label:'PDF'   },
        doc:  { icon:'bi-file-earmark-word',  badge:'idx-fmt-doc',  label:'DOC'   },
        docx: { icon:'bi-file-earmark-word',  badge:'idx-fmt-docx', label:'DOCX'  },
        xls:  { icon:'bi-file-earmark-excel', badge:'idx-fmt-xls',  label:'XLS'   },
        xlsx: { icon:'bi-file-earmark-excel', badge:'idx-fmt-xlsx', label:'XLSX'  },
        png:  { icon:'bi-file-earmark-image', badge:'idx-fmt-img',  label:'IMAGE' },
        jpg:  { icon:'bi-file-earmark-image', badge:'idx-fmt-img',  label:'IMAGE' },
        jpeg: { icon:'bi-file-earmark-image', badge:'idx-fmt-img',  label:'IMAGE' },
      };
      return map[ext] || { icon:'bi-file-earmark-text', badge:'idx-fmt-none', label:'DOCUMENT' };
    }

    window.openIdxModal = function(card){ 
      const safeCat = card.dataset.cat    || 'general';
      const rawCat  = card.dataset.rawcat || 'General';
      const title   = card.dataset.title  || 'Notice';
      const desc    = card.dataset.body   || 'No description available.';
      const pub     = card.dataset.publisher || 'Administration';
      const date    = card.dataset.date   || '—';
      const file    = card.dataset.file   || '';

      // Fallback styling logic for the modal based on keywords in category
      let cfg = catConfig.general;
      if (safeCat.includes('exam')) cfg = catConfig.exam;
      else if (safeCat.includes('admin') || safeCat.includes('urgent')) cfg = catConfig.admin;
      else if (safeCat.includes('event')) cfg = catConfig.event;

      const iconWrap = document.getElementById('idxMIconWrap');
      iconWrap.className = `idx-modal-icon-wrap ${cfg.col}`;
      document.getElementById('idxMCatIcon').className = `bi ${cfg.icon}`;

      const badge = document.getElementById('idxMCatBadge');
      badge.className = `idx-modal-cat-badge ${cfg.badge}`;
      badge.innerHTML = `• ${rawCat}`; // Dynamic category label

      document.getElementById('idxMTitle').textContent     = title;
      document.getElementById('idxMDesc').innerHTML        = desc; 
      document.getElementById('idxMPublisher').textContent = pub;
      document.getElementById('idxMDate').textContent      = date;

      const ext      = getFileExt(file);
      const fileMeta = file ? (ext ? getFileMeta(ext) : { icon:'bi-file-earmark-text', badge:'idx-fmt-none', label:'DOCUMENT' }) : null;
      const dlBtn    = document.getElementById('idxMDownloadBtn');
      const dlText   = document.getElementById('idxMDownloadText');
      const fmtBadge = document.getElementById('idxMFmtBadge');

      if(file && fileMeta){
        dlBtn.href      = file;
        dlBtn.className = 'idx-btn-download';
        dlText.textContent = `Download File`;
        
        fmtBadge.className = `idx-fmt-badge ${fileMeta.badge}`;
        fmtBadge.innerHTML = `<i class="bi ${fileMeta.icon}"></i> ${fileMeta.label}`;
        fmtBadge.style.display = 'inline-flex';
      } else {
        dlBtn.href      = '#';
        dlBtn.className = 'idx-btn-download no-file';
        dlText.textContent = `No File Attached`;
        fmtBadge.style.display = 'none';
      }

      document.getElementById('idxNoticeModal').classList.add('open');
      document.body.style.overflow = 'hidden';
    }

    function closeIdxModal(){
      document.getElementById('idxNoticeModal').classList.remove('open');
      document.body.style.overflow = '';
    }

    document.getElementById('idxModalCloseTop').addEventListener('click', closeIdxModal);
    document.getElementById('idxModalCloseFooter').addEventListener('click', closeIdxModal);
    document.getElementById('idxNoticeModal').addEventListener('click', function(e){
      if(e.target === this) closeIdxModal();
    });

    /* ── Initialize ── */
    document.addEventListener('DOMContentLoaded', () => {
       fetchNotices();
    });

  })();