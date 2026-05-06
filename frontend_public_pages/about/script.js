// about_page
/* ========================================================================== */
  /* PAGE INTERACTIONS (Scroll Reveal & Counters)                               */
  /* ========================================================================== */
  (function () {
    'use strict';

    /* ── Scroll-Reveal (IntersectionObserver) ── */
    const revealEls = document.querySelectorAll('.fade-up, .fade-in');
    if (revealEls.length) {
      const revealObs = new IntersectionObserver(
        (entries) => {
          entries.forEach((entry) => {
            if (entry.isIntersecting) {
              entry.target.classList.add('visible');
              revealObs.unobserve(entry.target);   // animate once
            }
          });
        },
        { threshold: 0.15 }
      );
      revealEls.forEach((el) => revealObs.observe(el));
    }

    /* ── Animated Counter for Stat Numbers ── */
    function animateCounter(el) {
      const raw    = el.textContent.trim();          
      const suffix = raw.replace(/[0-9]/g, '');      
      const target = parseInt(raw, 10);
      if (isNaN(target)) return;

      const duration = 2000;
      const start    = performance.now();

      function step(now) {
        const progress = Math.min((now - start) / duration, 1);
        const eased = 1 - Math.pow(1 - progress, 3); // ease-out cubic
        el.textContent = Math.floor(eased * target) + suffix;
        if (progress < 1) requestAnimationFrame(step);
      }
      requestAnimationFrame(step);
    }

    const statNums = document.querySelectorAll('.stat-num');
    if (statNums.length) {
      const counterObs = new IntersectionObserver(
        (entries) => {
          entries.forEach((entry) => {
            if (entry.isIntersecting) {
              animateCounter(entry.target);
              counterObs.unobserve(entry.target);
            }
          });
        },
        { threshold: 0.5 }
      );
      statNums.forEach((el) => counterObs.observe(el));
    }

  })();