// acadmicbody_page
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
        { threshold: 0.15 }
      );
      revealEls.forEach((el) => obs.observe(el));
    }

    /* ── Bootstrap Modal — Gov Card click handler ── */
    const govModalEl = document.getElementById('govModal');
    let bsModal = null;

    if (govModalEl && typeof bootstrap !== 'undefined') {
      bsModal = new bootstrap.Modal(govModalEl);
    }

    document.querySelectorAll('.gov-card').forEach((card) => {
      card.addEventListener('click', () => {
        document.getElementById('mEmoji').textContent   = card.dataset.emoji   || '';
        document.getElementById('mGTitle').textContent  = card.dataset.title   || '';
        document.getElementById('mGDesc').textContent   = card.dataset.desc    || '';
        document.getElementById('mGRole').textContent   = card.dataset.role    || '';
        document.getElementById('mGMandate').textContent= card.dataset.mandate || '';
        document.getElementById('mGWeb').textContent    = card.dataset.website || '';
        document.getElementById('mGImpact').textContent = card.dataset.impact  || '';

        if (bsModal) {
          bsModal.show();
        }
      });
    });

  })();