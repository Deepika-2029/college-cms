// topper_page
/* ========================================================================== */
  /* PAGE INTERACTIONS (Scroll Reveal, Counters, Modals)                        */
  /* ========================================================================== */
  (function () {
    'use strict';

    /* ── 1. Scroll-Reveal ── */
    const revealEls = document.querySelectorAll('.fade-up, .fade-in');
    if (revealEls.length) {
      const obs = new IntersectionObserver(
        (entries) => {
          entries.forEach((entry) => {
            if (entry.isIntersecting) {
              entry.target.classList.add('visible');
              obs.unobserve(entry.target);
            }
          });
        },
        { threshold: 0.12 }
      );
      revealEls.forEach((el) => obs.observe(el));
    }

    /* ── 2. Animated Counter for stat-num ── */
    function animateCounter(el) {
      const raw    = el.textContent.trim();
      const suffix = raw.replace(/[0-9.]/g, '');
      const numStr = raw.replace(/[^0-9.]/g, '');
      const target = parseFloat(numStr);
      if (isNaN(target) || target === 0) return; 

      const duration = 1600;
      const start    = performance.now();
      function step(now) {
        const progress = Math.min((now - start) / duration, 1);
        const eased    = 1 - Math.pow(1 - progress, 3);
        
        let displayNum = (eased * target);
        if (raw.includes('.')) {
          el.textContent = displayNum.toFixed(1) + suffix;
        } else {
          el.textContent = Math.floor(displayNum) + suffix;
        }
        
        if (progress < 1) requestAnimationFrame(step);
      }
      requestAnimationFrame(step);
    }

    const statNums = document.querySelectorAll('.stat-num');
    if (statNums.length) {
      const cObs = new IntersectionObserver(
        (entries) => {
          entries.forEach((entry) => {
            if (entry.isIntersecting) {
              animateCounter(entry.target);
              cObs.unobserve(entry.target);
            }
          });
        },
        { threshold: 0.5 }
      );
      statNums.forEach((el) => cObs.observe(el));
    }

    /* ── 3. Split-Layout Modal Logic ── */
    const grid = document.getElementById('topperGrid');
    const modalOverlay = document.getElementById('topperModalOverlay');

    grid.addEventListener('click', e => {
      const card = e.target.closest('.topper-card');
      if (!card) return;

      document.getElementById('mEmoji').textContent    = card.dataset.emoji || '🏆';
      document.getElementById('mEmoji').style.color = '#fff';
      document.getElementById('topperModalOverlay').querySelector('.m-left').style.backgroundColor = card.dataset.bg;
      
      document.getElementById('mDeptTitle').textContent = card.dataset.dept  || '–';

      const toppers = JSON.parse(card.dataset.toppers || '[]');
      const listContainer = document.getElementById('mTopperList');
      
      listContainer.innerHTML = toppers.map((t, idx) => {
        const rank = parseInt(t.m);
        let rankClass = 'rc-other';
        if (rank === 1) rankClass = 'rc-1';
        else if (rank === 2) rankClass = 'rc-2';
        else if (rank === 3) rankClass = 'rc-3';

        return `
          <li class="m-topper-item">
            <div class="rank-circle ${rankClass}">${rank}</div>
            <div class="m-info">
              <div class="m-name">${t.n}</div>
              <div class="m-pct-bar-wrap">
                <div class="m-pct-bar"><div class="m-pct-fill" style="width: 0%" data-w="${t.p}%"></div></div>
                <div class="m-pct-val">${t.p}%</div>
              </div>
            </div>
          </li>
        `;
      }).join('');

      modalOverlay.classList.add('open');
      document.body.style.overflow = 'hidden';

      // Trigger bar animations after short delay
      setTimeout(() => {
        document.querySelectorAll('.m-pct-fill').forEach(bar => {
          bar.style.width = bar.dataset.w;
        });
      }, 300);
    });

    const closeBtn = document.getElementById('mClose');
    function closeModal() { 
      modalOverlay.classList.remove('open'); 
      document.body.style.overflow = ''; 
    }
    
    if(closeBtn) closeBtn.addEventListener('click', closeModal);
    if(modalOverlay) modalOverlay.addEventListener('click', e => { if (e.target === modalOverlay) closeModal(); });
    document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });

  })();