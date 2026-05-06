// Home
(function () {
      'use strict';

      const API_URL_CAROUSEL = 'https://gpnainital.com/api/data/carousel?key=5661fa420f0023287d9289a52d15dcf38bf12ab963aec731bfefdbc0a1a7c9bf';
      const API_URL_NOTICES  = 'https://gpnainital.com/api/data/notices?key=7c861a823c1f36959071bdc8c38b86c3a363b29d41cc391fc7fd24954afd57c1';
      const API_URL_EVENTS   = 'https://gpnainital.com/api/data/events?key=0c73210a5cdb7936a79b042eded25384b541329c2c59ba89e08112daf7474629';
      /* ── HELPER: Sanitize Image URLs ── */
      function sanitizeUrl(url) {
        if (!url) return '';
        // Fix for local dev IP leaking in API responses
        return url.replace('http://192.168.1.2:8000', 'https://gpnainital.com');
      }

      /* ── HELPER: Check if date is within last 30 days ── */
      function isNoticeNew(dateStr) {
        if (!dateStr) return false;
        // Works best if date is YYYY-MM-DD
        const d = new Date(dateStr);
        if (isNaN(d.getTime())) return false; 
        const absDiff = Date.now() - d.getTime();
        // Return true if date is in the past 30 days, or slightly in the future
        return absDiff >= -86400000 && absDiff <= (30 * 24 * 60 * 60 * 1000);
      }

      /* ── 1. API: FETCH HERO CAROUSEL ── */
      async function fetchCarousel() {
        const innerContainer = document.getElementById('carouselInner');
        const indicatorsContainer = document.getElementById('carouselIndicators');
        
        try {
          const res = await fetch(API_URL_CAROUSEL);
          if (!res.ok) throw new Error('Network response was not ok');
          const data = await res.json();
          const items = data.data || (Array.isArray(data) ? data : []);

          if (items && items.length > 0) {
            innerContainer.innerHTML = '';
            indicatorsContainer.innerHTML = '';
            
            items.forEach((item, index) => {
              const btn = document.createElement('button');
              btn.type = 'button';
              btn.setAttribute('data-bs-target', '#heroCarousel');
              btn.setAttribute('data-bs-slide-to', index);
              if (index === 0) btn.className = 'active';
              indicatorsContainer.appendChild(btn);

              const safeImageUrl = sanitizeUrl(item.image_url);

              const div = document.createElement('div');
              div.className = `carousel-item h-100 ${index === 0 ? 'active' : ''}`;
              div.innerHTML = `<img src="${safeImageUrl}" alt="${item.title || 'UGIP'}" class="d-block w-100 h-100" style="object-fit:cover;" onerror="this.src='https://images.unsplash.com/photo-1541339907198-e08756dedf3f?w=900&q=80'">`;
              innerContainer.appendChild(div);
            });
          } else {
             throw new Error('Empty');
          }
        } catch (error) {
          innerContainer.innerHTML = `
            <div class="text-center w-100 h-100 d-flex flex-column align-items-center justify-content-center text-white-50">
              <i class="bi bi-images mb-2" style="font-size: 2rem;"></i>
              <small>Images unavailable</small>
            </div>
          `;
        }
      }

      /* ── 2. API: FETCH NOTICES ── */
      async function fetchNotices() {
        const noticesList = document.getElementById('noticesList');
        try {
          const res = await fetch(API_URL_NOTICES);
          if (!res.ok) throw new Error('Network response was not ok');
          const data = await res.json();
          const notices = data.data || (Array.isArray(data) ? data : []);

          if (notices && notices.length > 0) {
            noticesList.innerHTML = ''; 
            
            notices.slice(0, 5).forEach(notice => {
              // Map accurately to SQL table headers
              const pubDate = notice.publish_date || notice.date || '';
              const fileLink = notice.file_url || notice.fileUrl || '';
              
              const safeFileUrl = sanitizeUrl(fileLink);
              const ext = getFileExt(safeFileUrl);
              // Ensure we fallback to generic "Document" badge if file exists but no matching extension
              const fileMeta = safeFileUrl ? (ext ? getFileMeta(ext) : { icon:'bi-file-earmark-text', badge:'idx-fmt-none', label:'DOCUMENT' }) : null;

              const div = document.createElement('div');
              div.className = 'notice-item-card';
              div.setAttribute('data-cat', notice.category || 'general');
              div.setAttribute('data-title', notice.title || 'Notice');
              div.setAttribute('data-body', notice.description || '');
              div.setAttribute('data-publisher', notice.publisher || 'Admin');
              div.setAttribute('data-date', pubDate);
              div.setAttribute('data-file', safeFileUrl);
              
              const titleHtml = safeFileUrl 
                ? `<a href="${safeFileUrl}" class="notice-title-link" download target="_blank" onclick="event.stopPropagation()">${notice.title}</a>` 
                : `<span class="notice-title-link">${notice.title}</span>`;
              
              const newBadgeHtml = isNoticeNew(pubDate) ? `<span class="notice-new-badge">NEW</span>` : '';
              const fileBadgeHtml = fileMeta ? `<span class="notice-file-badge"><i class="bi ${fileMeta.icon}"></i> ${fileMeta.label}</span>` : '';

              div.innerHTML = `
                <div class="notice-title">${titleHtml} ${newBadgeHtml}</div>
                <div class="d-flex justify-content-between align-items-center mt-2">
                  <div class="notice-date"><i class="bi bi-calendar3"></i> ${pubDate}</div>
                  ${fileBadgeHtml}
                </div>
              `;
              div.addEventListener('click', function() { openIdxModal(this); });
              noticesList.appendChild(div);
            });
          } else {
            throw new Error('No notice data');
          }
        } catch (error) {
          noticesList.innerHTML = `
            <div class="text-center p-3 text-muted">
              <i class="bi bi-inbox mb-2" style="font-size: 1.5rem;"></i><br>
              <small>No notices currently available.</small>
            </div>
          `;
        }
      }

      /* ── 3. API: FETCH EVENTS & RENDER NEW CARDS ── */
      async function fetchEvents() {
        try {
          const res = await fetch(API_URL_EVENTS);
          if (!res.ok) throw new Error('Network response was not ok');
          const data = await res.json();
          const events = data.data || (Array.isArray(data) ? data : []);

          if (events && events.length > 0) {
            renderEvents(events);
          } else {
            throw new Error('No events data');
          }
        } catch (error) {
          const eventsList = document.getElementById('newEventsList');
          eventsList.innerHTML = `
            <div class="col-12 text-center p-5 theme-text-m" style="width: 100%;">
              <i class="bi bi-calendar-x mb-3" style="font-size: 2.5rem; display: block;"></i>
              <h5 class="fw-bold">No Events</h5>
              <p>Check back later for new events and activities.</p>
            </div>
          `;
        }
      }

      function renderEvents(events) {
        const container = document.getElementById('newEventsList');
        container.innerHTML = '';
        
        events.forEach((ev, idx) => {
          const col = document.createElement('div');
          col.className = 'ev-slide-item';
          
          const typeLower = ev.type ? ev.type.toLowerCase() : 'general';
          
          const safeImageUrl = sanitizeUrl(ev.image_url || ev.image);
          const fileLink = ev.file_url || ev.fileUrl || '';
          const safeFileUrl = sanitizeUrl(fileLink);
          const evDate = ev.event_date || ev.date || '';
          
          ev.image = safeImageUrl;
          ev.fileUrl = safeFileUrl;
          ev.date = evDate;

          col.innerHTML = `
            <div class="ev-card" onclick='window.openEventModal(${JSON.stringify(ev).replace(/'/g, "&#39;")})'>
              <div class="ev-top">
                ${safeImageUrl ? `<img src="${safeImageUrl}" class="ev-img" alt="${ev.title}">` : `<i class="bi bi-image text-muted" style="font-size: 2rem;"></i>`}
              </div>
              <div class="ev-body">
                <div class="ev-tags">
                  <span class="ev-tag ev-tag-${typeLower}">${ev.type || 'EVENT'}</span>
                </div>
                <div class="ev-title">${ev.title}</div>
                <div class="ev-meta-row">
                  <div><i class="bi bi-calendar3"></i> ${evDate}</div>
                  <div><i class="bi bi-geo-alt"></i> ${ev.venue || 'TBA'}</div>
                </div>
                <div class="ev-action">
                  <i class="bi bi-eye"></i> View Details
                </div>
              </div>
            </div>
          `;
          container.appendChild(col);
        });
        
        initEventSlider();
      }

      /* ── EVENT SLIDER LOGIC ── */
      function initEventSlider() {
        const slider = document.getElementById('newEventsList');
        const btnPrev = document.getElementById('btnEventPrev');
        const btnNext = document.getElementById('btnEventNext');
        const btnPrevMob = document.getElementById('btnEventPrevMob');
        const btnNextMob = document.getElementById('btnEventNextMob');

        function scrollSlider(dir) {
          const scrollAmount = slider.offsetWidth > 600 ? 300 : slider.offsetWidth * 0.85;
          slider.scrollBy({ left: dir * scrollAmount, behavior: 'smooth' });
        }

        if(btnPrev) btnPrev.addEventListener('click', () => scrollSlider(-1));
        if(btnNext) btnNext.addEventListener('click', () => scrollSlider(1));
        if(btnPrevMob) btnPrevMob.addEventListener('click', () => scrollSlider(-1));
        if(btnNextMob) btnNextMob.addEventListener('click', () => scrollSlider(1));
      }

      /* ── EVENT MODAL LOGIC ── */
      window.openEventModal = function(ev) {
        const overlay = document.getElementById('evModalOverlay');
        const evMTop = document.getElementById('evMTop');
        
        if (ev.image) {
          evMTop.style.backgroundImage = `url(${ev.image})`;
          document.getElementById('evMIcon').style.display = 'none';
        } else {
          evMTop.style.backgroundColor = ev.bg || 'var(--sur-2)';
          evMTop.style.backgroundImage = 'none';
          document.getElementById('evMIcon').textContent = ev.icon || '📅';
          document.getElementById('evMIcon').style.display = 'block';
        }
        
        const catBadge = document.getElementById('evMCat');
        catBadge.textContent = ev.type || 'EVENT';
        catBadge.className = `ev-tag ev-tag-${(ev.type || 'general').toLowerCase()}`;
        
        document.getElementById('evMTitle').textContent = ev.title;
        document.getElementById('evMDesc').textContent = ev.description || ev.desc || 'No description available.';
        document.getElementById('evMDate').textContent = ev.date || 'TBA';
        document.getElementById('evMVenue').textContent = ev.venue || 'TBA';
        document.getElementById('evMOrg').textContent = ev.organizer || 'UGIP Administration';

        const docContainer = document.getElementById('evMDocContainer');
        const docLink = document.getElementById('evMDocLink');
        
        if (ev.fileUrl) {
            docLink.href = ev.fileUrl;
            docContainer.style.display = 'flex';
        } else {
            docContainer.style.display = 'none';
        }

        overlay.classList.add('open');
        document.body.style.overflow = 'hidden';
      };

      document.getElementById('evMClose').addEventListener('click', () => {
        document.getElementById('evModalOverlay').classList.remove('open');
        document.body.style.overflow = '';
      });
      document.getElementById('evModalOverlay').addEventListener('click', (e) => {
        if(e.target === document.getElementById('evModalOverlay')) {
          document.getElementById('evModalOverlay').classList.remove('open');
          document.body.style.overflow = '';
        }
      });


      /* ── INITIALIZE APIs ── */
      fetchCarousel();
      fetchNotices();
      fetchEvents();

      /* ── SCROLL ANIMATIONS ── */
      const observer = new IntersectionObserver((entries, obs) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            entry.target.classList.add('is-visible');
            obs.unobserve(entry.target);
          }
        });
      }, { rootMargin: '0px', threshold: 0.08 });

      document.querySelectorAll('.animate-on-scroll').forEach(el => observer.observe(el));

      /* ── TYPEWRITER ── */
          /* ── TYPEWRITER ── */
      const typeTarget = document.getElementById('typewriter-target');
      if (typeTarget) {
        // --- THIS IS THE FIX ---
        // Instantly wipe any old duplicated text from the database before typing!
        typeTarget.innerHTML = ''; 
        // -----------------------

        const lines = [
          { text: "Uttarakhand Government", cls: "d-block" },
          { text: "Institute of Polytechnic", cls: "d-block" },
          { text: "Nainital", cls: "d-block" }
        ];
        const ACCENT = "var(--gold)"; 
        let li = 0, ci = 0;
        function typeNext() {
          if (li >= lines.length) return;
          const line = lines[li];
          if (ci === 0) {
            const span = document.createElement('span');
            span.className = line.cls;
            span.id = 'tl' + li;
            if (li === 1) span.style.color = ACCENT;
            typeTarget.appendChild(span);
          }
          document.getElementById('tl' + li).innerHTML += line.text.charAt(ci);
          ci++;
          if (ci >= line.text.length) { li++; ci = 0; setTimeout(typeNext, 380); }
          else setTimeout(typeNext, 55);
        }
        setTimeout(typeNext, 900);
      }


      /* ── COUNTER ANIMATION ── */
      const counterObs = new IntersectionObserver((entries, obs) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            const el = entry.target;
            const target = +el.getAttribute('data-target');
            const dur = 2000;
            const inc = target / (dur / 16);
            let cur = 0;
            const tick = () => {
              cur += inc;
              if (cur < target) { el.innerText = Math.ceil(cur); requestAnimationFrame(tick); }
              else el.innerText = target;
            };
            tick();
            obs.unobserve(el);
          }
        });
      }, { threshold: 0.5 });
      document.querySelectorAll('.counter-val').forEach(el => counterObs.observe(el));

      /* ── NOTICE MODAL LOGIC (Matched to UI Screenshot) ── */
      const catConfig = {
        urgent:  { icon:'bi-exclamation-triangle-fill', col:'idx-col-urgent', badge:'idx-badge-urgent', label:'Urgent' },
        exam:    { icon:'bi-journal-text',              col:'idx-col-exam',   badge:'idx-badge-exam',   label:'Examination' },
        event:   { icon:'bi-trophy-fill',               col:'idx-col-event',  badge:'idx-badge-event',  label:'Event' },
        general: { icon:'bi-info-circle-fill',          col:'idx-col-general',badge:'idx-badge-general',label:'General' },
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
        const cat  = card.dataset.cat   || 'general';
        const cfg  = catConfig[cat]      || catConfig.general;
        const title = card.dataset.title || 'Notice';
        const desc  = card.dataset.body  || 'No description available.';
        const pub   = card.dataset.publisher || 'Administration';
        const date  = card.dataset.date  || '—';
        const file  = card.dataset.file  || '';

        const iconWrap = document.getElementById('idxMIconWrap');
        iconWrap.className = `idx-modal-icon-wrap ${cfg.col}`;
        document.getElementById('idxMCatIcon').className = `bi ${cfg.icon}`;

        const badge = document.getElementById('idxMCatBadge');
        badge.className = `idx-modal-cat-badge ${cfg.badge}`;
        badge.innerHTML = `• ${cfg.label}`;

        document.getElementById('idxMTitle').textContent     = title;
        document.getElementById('idxMDesc').innerHTML        = desc; // Allows rich text if needed
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

    })();