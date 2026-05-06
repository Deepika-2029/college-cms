// Gallery
(function () {
      'use strict';

      /* ── CONFIG ── */
      const API_URL = 'https://gpnainital.com/api/data/gallery?key=931bab0cc9280c8d975caeda70ae97460c7baf82142219cd81b70c727ce8e91b';

      /* Category registry */
      const CATS = {
        all:       { label: 'All',       icon: 'bi-grid-3x3-gap-fill' },
        campus:    { label: 'Campus',    icon: 'bi-building'          },
        academics: { label: 'Academics', icon: 'bi-book-fill'         },
        workshops: { label: 'Workshops', icon: 'bi-tools'             },
        events:    { label: 'Events',    icon: 'bi-calendar-event-fill'},
        sports:    { label: 'Sports',    icon: 'bi-trophy-fill'       },
        general:   { label: 'General',   icon: 'bi-camera-fill'       },
      };

      function resolvecat(raw) {
        if (!raw) return 'general';
        const s = raw.toLowerCase().trim();
        return CATS[s] ? s : 'general';
      }

      /* DOM */
      const grid        = document.getElementById('masonryGrid');
      const countEl     = document.getElementById('galCount');
      const filterBar   = document.getElementById('filterBar');
      const loadMoreBtn = document.getElementById('loadMoreBtn');
      const loadingBox  = document.getElementById('loadingBox');
      const errorBox    = document.getElementById('errorBox');
      const emptyBox    = document.getElementById('emptyBox');
      const totalPhotosEl = document.getElementById('totalPhotos');
      const totalCatsEl   = document.getElementById('totalCats');

      if (!grid) return; // Only run if gallery exists on page

      /* Skeleton */
      grid.innerHTML = Array.from({ length: 12 }, (_, i) =>
        `<div class="g-skeleton"></div>`
      ).join('');

      /* ══ FETCH ══ */
      fetch(API_URL)
        .then(r => { if (!r.ok) throw new Error(r.status); return r.json(); })
        .then(data => {
          // Use data.data if it exists, otherwise fallback to data itself if it's an array
          const rows = data.data || (Array.isArray(data) ? data : null);
          if (!rows?.length) throw new Error('empty');

          const ITEMS = rows
            .map(row => ({
              src:   row.image || '',
              thumb: row.image || '',
              title: row.title || 'Untitled',
              cat:   resolvecat(row.category),
            }))
            .filter(i => i.src);

          if (!ITEMS.length) throw new Error('no images');

          loadingBox.style.display = 'none';
          initGallery(ITEMS);
        })
        .catch(err => {
          console.error('[Gallery]', err);
          grid.innerHTML = '';
          loadingBox.style.display = 'none';
          errorBox.classList.add('show');
          if (countEl) countEl.textContent = '0';
        });

      /* ── Toast Logic ── */
      function showToast(icon, msg, ms = 2500) {
        const el  = document.getElementById('gal-toast');
        const ico = el.querySelector('i');
        const txt = document.getElementById('gal-toast-msg');
        ico.className = `bi ${icon}`;
        txt.textContent = msg;
        el.classList.add('show');
        clearTimeout(el._t);
        el._t = setTimeout(() => el.classList.remove('show'), ms);
      }

      /* ══ GALLERY ══ */
      function initGallery(ITEMS) {
        let currentCat   = 'all';
        let currentIdx   = 0;
        let visibleItems = [];
        const BATCH      = 24;
        let rendered     = 0;

        if (totalPhotosEl) totalPhotosEl.textContent = ITEMS.length;
        if (totalCatsEl)   totalCatsEl.textContent   = new Set(ITEMS.map(i => i.cat)).size;

        /* ── Filter bar ── */
        filterBar.innerHTML = '';
        Object.entries(CATS).forEach(([key, val]) => {
          const count = key === 'all' ? ITEMS.length : ITEMS.filter(i => i.cat === key).length;
          if (key !== 'all' && count === 0) return;

          const btn = document.createElement('button');
          btn.className   = 'filter-btn' + (key === 'all' ? ' active' : '');
          btn.dataset.cat = key;
          btn.innerHTML   =
            `<i class="bi ${val.icon}"></i> ${val.label} ` +
            `<span class="badge">${count}</span>`;
          btn.addEventListener('click', () => applyFilter(key));
          filterBar.appendChild(btn);
        });

        /* ── Card ── */
        function buildCard(item, gIdx) {
          const div = document.createElement('div');
          div.className = 'g-card';
          div.dataset.idx = gIdx;
          div.style.animationDelay = ((gIdx % BATCH) * 28) + 'ms';

          const cat = CATS[item.cat] || CATS.general;

          div.innerHTML = `
            <img src="${item.thumb}" alt="${item.title}" loading="lazy"
                 onerror="this.closest('.g-card').style.display='none'">
            <div class="g-overlay">
              <div class="g-top">
                <span class="g-badge">
                  <i class="bi ${cat.icon}"></i> ${cat.label}
                </span>
                <button class="g-share-btn" title="Share" aria-label="Share this photo">
                  <i class="bi bi-share-fill"></i>
                </button>
              </div>
              <p class="g-title">${item.title}</p>
            </div>
          `;

          /* open lightbox on card click */
          div.addEventListener('click', e => {
            if (e.target.closest('.g-share-btn')) return;
            openLight(gIdx);
          });

          /* share */
          div.querySelector('.g-share-btn').addEventListener('click', e => {
            e.stopPropagation();
            doShare(item);
          });

          return div;
        }

        /* ── Filter ── */
        function getFiltered() {
          return ITEMS.filter(i => currentCat === 'all' || i.cat === currentCat);
        }

        function renderBatch(items, from) {
          const to = Math.min(from + BATCH, items.length);
          const frag = document.createDocumentFragment();
          for (let i = from; i < to; i++)
            frag.appendChild(buildCard(items[i], ITEMS.indexOf(items[i])));
          grid.appendChild(frag);
          return to;
        }

        function applyFilter(cat) {
          currentCat = cat;
          document.querySelectorAll('.filter-btn').forEach(b =>
            b.classList.toggle('active', b.dataset.cat === cat)
          );
          grid.innerHTML = '';
          rendered = 0;
          emptyBox.classList.remove('show');

          const filtered = getFiltered();
          visibleItems = filtered;

          if (!filtered.length) {
            emptyBox.classList.add('show');
            if (loadMoreBtn) loadMoreBtn.style.display = 'none';
            if (countEl) countEl.textContent = '0';
            return;
          }

          if (countEl) countEl.textContent = filtered.length;
          rendered = renderBatch(filtered, 0);
          if (loadMoreBtn)
            loadMoreBtn.style.display = rendered < filtered.length ? 'inline-flex' : 'none';
        }

        loadMoreBtn?.addEventListener('click', () => {
          rendered = renderBatch(getFiltered(), rendered);
          if (rendered >= getFiltered().length) loadMoreBtn.style.display = 'none';
        });

        applyFilter('all');

        /* ── Share ── */
        function doShare(item) {
          const shareData = {
            title: `${item.title} — UGIP Gallery`,
            text:  `📸 ${item.title} | UGIP Nainital`,
            url:   item.src,
          };
          if (navigator.share) {
            navigator.share(shareData).catch(() => {});
          } else {
            navigator.clipboard.writeText(item.src)
              .then(() => showToast('bi-link-45deg', 'Link copied to clipboard!'))
              .catch(() => showToast('bi-exclamation-circle', 'Copy failed'));
          }
        }

        /* ════════════════════════════════════════════════
           LIGHTBOX
        ════════════════════════════════════════════════ */
        const lb    = document.getElementById('glBackdrop');
        const img   = document.getElementById('glImg');
        const title = document.getElementById('glTitle');
        const ctr   = document.getElementById('glCounter');

        function updateMeta() {
          const item = ITEMS[currentIdx];
          const ci   = visibleItems.findIndex(i => ITEMS.indexOf(i) === currentIdx);
          if (title) title.textContent = item.title;
          if (ctr)   ctr.textContent   = `${ci + 1} / ${visibleItems.length}`;
        }

        function openLight(gIdx) {
          currentIdx = gIdx;
          img.classList.remove('ready');
          img.src = '';
          /* small tick so remove-class transition registers */
          requestAnimationFrame(() => {
            const item = ITEMS[gIdx];
            img.alt = item.title;
            img.src = item.src;
            img.onload  = () => img.classList.add('ready');
            img.onerror = () => img.classList.add('ready');
          });
          updateMeta();
          lb.classList.add('open');
          document.body.style.overflow = 'hidden';
        }

        function closeLight() {
          lb.classList.remove('open');
          document.body.style.overflow = '';
          setTimeout(() => { img.src = ''; img.classList.remove('ready'); }, 300);
        }

        function prevImg() {
          const f = visibleItems;
          const ci = f.findIndex(i => ITEMS.indexOf(i) === currentIdx);
          openLight(ITEMS.indexOf(f[(ci - 1 + f.length) % f.length]));
        }
        function nextImg() {
          const f = visibleItems;
          const ci = f.findIndex(i => ITEMS.indexOf(i) === currentIdx);
          openLight(ITEMS.indexOf(f[(ci + 1) % f.length]));
        }

        /* click backdrop to close */
        lb.addEventListener('click', e => {
          if (e.target === lb) closeLight();
        });

        document.getElementById('glClose').addEventListener('click', closeLight);
        document.getElementById('glPrev').addEventListener('click', e => {
          e.stopPropagation(); prevImg();
        });
        document.getElementById('glNext').addEventListener('click', e => {
          e.stopPropagation(); nextImg();
        });

        /* Keyboard ← → Esc */
        document.addEventListener('keydown', e => {
          if (!lb.classList.contains('open')) return;
          if (e.key === 'Escape')     closeLight();
          if (e.key === 'ArrowLeft')  { e.preventDefault(); prevImg(); }
          if (e.key === 'ArrowRight') { e.preventDefault(); nextImg(); }
        });

        /* Touch swipe */
        let tx = 0;
        lb.addEventListener('touchstart', e => {
          tx = e.touches[0].clientX;
        }, { passive: true });
        lb.addEventListener('touchend', e => {
          const d = tx - e.changedTouches[0].clientX;
          if (Math.abs(d) > 50) d > 0 ? nextImg() : prevImg();
        });

      } /* end initGallery */

    })();