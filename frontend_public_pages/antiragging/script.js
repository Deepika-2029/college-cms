// antiragging
/* ========================================================================== */
  /* PAGE INTERACTIONS (Scroll Reveal & Hovers)                                 */
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

    /* ── Action card hover lift (Icon Zoom) ── */
    document.querySelectorAll('.action-card').forEach((card) => {
      card.addEventListener('mouseenter', () => {
        const icon = card.querySelector('.action-icon');
        if (icon) icon.style.transform = 'scale(1.2) rotate(-5deg)';
      });
      card.addEventListener('mouseleave', () => {
        const icon = card.querySelector('.action-icon');
        if (icon) icon.style.transform = 'scale(1) rotate(0deg)';
      });
    });

  })();