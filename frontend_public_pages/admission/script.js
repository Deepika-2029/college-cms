// admissionprocedure
/* ========================================================================== */
  /* PAGE INTERACTIONS (Scroll Reveal & Counters)                               */
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

    /* ── Animated Counter for stat-num ── */
    function animateCounter(el) {
      const raw    = el.textContent.trim();
      const suffix = raw.replace(/[0-9,]/g, '');
      const numStr = raw.replace(/[^0-9]/g, '');
      const target = parseInt(numStr, 10);
      if (isNaN(target) || target === 0) return; // skip text like "UBTER"

      const duration = 1600;
      const start    = performance.now();
      function step(now) {
        const progress = Math.min((now - start) / duration, 1);
        const eased    = 1 - Math.pow(1 - progress, 3);
        
        let displayNum = Math.floor(eased * target);
        // Add comma formatting if target is >= 1000
        if (target >= 1000) {
           displayNum = displayNum.toLocaleString('en-IN');
        }
        
        el.textContent = displayNum + suffix;
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

    /* ── Step card hover pulse ── */
    document.querySelectorAll('.step-card').forEach((card) => {
      card.addEventListener('mouseenter', () => {
        const num = card.querySelector('.step-num');
        if (num) num.style.transform = 'scale(1.15)';
      });
      card.addEventListener('mouseleave', () => {
        const num = card.querySelector('.step-num');
        if (num) num.style.transform = 'scale(1)';
      });
    });

  })();