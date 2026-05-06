// mechanical_Page
(function () {
  'use strict';

  /* ── Scroll-Reveal ── */
  const revealEls = document.querySelectorAll('.fade-up, .fade-in');
  if ('IntersectionObserver' in window && revealEls.length) {
    const obs = new IntersectionObserver(
      entries => entries.forEach(e => {
        if (e.isIntersecting) { e.target.classList.add('visible'); obs.unobserve(e.target); }
      }),
      { threshold: 0.12 }
    );
    revealEls.forEach(el => obs.observe(el));
  } else {
    revealEls.forEach(el => el.classList.add('visible')); // fallback
  }

  /* ── Animated Counter ── */
  function animateCounter(el) {
    const raw    = el.textContent.trim();
    const suffix = raw.replace(/[\d,]/g, '');
    const target = parseInt(raw.replace(/\D/g, ''), 10);
    if (isNaN(target) || target === 0) return;
    const duration = 1600;
    const start    = performance.now();
    (function step(now) {
      const p = Math.min((now - start) / duration, 1);
      el.textContent = Math.floor((1 - Math.pow(1 - p, 3)) * target) + suffix;
      if (p < 1) requestAnimationFrame(step);
    })(start);
  }

  const statNums = document.querySelectorAll('.stat-num');
  if ('IntersectionObserver' in window && statNums.length) {
    const cObs = new IntersectionObserver(
      entries => entries.forEach(e => {
        if (e.isIntersecting) { animateCounter(e.target); cObs.unobserve(e.target); }
      }),
      { threshold: 0.5 }
    );
    statNums.forEach(el => cObs.observe(el));
  }

  /* ── Accordion ── */
  document.querySelectorAll('.acc-toggle').forEach(btn => {
    btn.addEventListener('click', function () {
      this.classList.toggle('open');
      this.nextElementSibling.classList.toggle('open');
    });
  });

})();