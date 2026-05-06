// placement_page
/* ========================================================================== */
  /* PAGE INTERACTIONS (Scroll Reveal & Modals)                                 */
  /* ========================================================================== */
  (function () {
    'use strict';

    /* ── Scroll-Reveal (IntersectionObserver) ── */
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

    /* ── Recruiter Card click handler ── */
    const overlay = document.getElementById('coModalOverlay');
    const recruiterGrid = document.getElementById('recruiterGrid');

    recruiterGrid.addEventListener('click', e => {
      const card = e.target.closest('.co-card');
      if (!card) return;

      document.getElementById('mCoIcon').textContent   = card.dataset.icon;
      document.getElementById('mCoName').textContent   = card.dataset.name;
      document.getElementById('mCoSector').textContent = card.dataset.sector;
      document.getElementById('mDepts').textContent    = card.dataset.depts;
      document.getElementById('mRoles').textContent    = card.dataset.roles;
      document.getElementById('mPkg').textContent      = card.dataset.package || card.dataset.pkg;
      document.getElementById('mVisits').textContent   = card.dataset.visits;

      overlay.classList.add('open');
      document.body.style.overflow = 'hidden';
    });

    const closeBtn = document.getElementById('mCoClose');
    function closeModal() { 
      overlay.classList.remove('open'); 
      document.body.style.overflow = ''; 
    }
    
    if(closeBtn) closeBtn.addEventListener('click', closeModal);
    if(overlay) overlay.addEventListener('click', e => { if (e.target === overlay) closeModal(); });
    document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });

  })();