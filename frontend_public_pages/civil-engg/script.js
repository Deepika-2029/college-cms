(function () {
      'use strict';

      function initNav() {
        const header   = document.getElementById('siteHeader');
        const menuBtn  = document.getElementById('menuBtn');
        const navList  = document.getElementById('navList');
        const backdrop = document.getElementById('backdrop');

        if (!header || !menuBtn || !navList || !backdrop) {
          console.warn('UGIP Nav: Required elements not found', { header, menuBtn, navList, backdrop });
          return;
        }

        const mob = () => window.innerWidth < 992;

        function openDrawer() {
          navList.classList.add('open');
          backdrop.classList.add('show');
          menuBtn.classList.add('open');
          menuBtn.setAttribute('aria-expanded', 'true');
          document.body.style.overflow = 'hidden';
        }

        function closeDrawer() {
          navList.classList.remove('open');
          backdrop.classList.remove('show');
          menuBtn.classList.remove('open');
          menuBtn.setAttribute('aria-expanded', 'false');
          document.body.style.overflow = '';
          navList.querySelectorAll('.dropdown.open').forEach(d => d.classList.remove('open'));
          navList.querySelectorAll('.has-drop.drop-open').forEach(l => l.classList.remove('drop-open'));
        }

        menuBtn.addEventListener('click', function (e) {
          e.stopPropagation();
          navList.classList.contains('open') ? closeDrawer() : openDrawer();
        });

        backdrop.addEventListener('click', closeDrawer);

        navList.querySelectorAll('.nav-item').forEach(function (item) {
          item.addEventListener('mouseenter', function () {
            if (!mob()) {
              const active = document.activeElement;
              if (active && navList.contains(active) && !item.contains(active)) {
                active.blur();
              }
            }
          });
        });

        navList.addEventListener('mouseleave', function () {
          if (!mob()) {
            const active = document.activeElement;
            if (active && navList.contains(active)) {
              active.blur();
            }
          }
        });

        navList.querySelectorAll('.has-drop').forEach(function (trigger) {
          trigger.addEventListener('click', function (e) {
            if (!mob()) {
              if (this.getAttribute('href') === '#') e.preventDefault();
              return;
            }
            e.preventDefault();
            e.stopPropagation();
            const drop   = this.closest('.drop-wrap').querySelector('.dropdown');
            const isOpen = drop.classList.contains('open');
            navList.querySelectorAll('.dropdown.open').forEach(d => d.classList.remove('open'));
            navList.querySelectorAll('.has-drop.drop-open').forEach(l => l.classList.remove('drop-open'));
            if (!isOpen) {
              drop.classList.add('open');
              this.classList.add('drop-open');
            }
          });
        });

        navList.querySelectorAll('a:not(.has-drop)').forEach(function (a) {
          a.addEventListener('click', function () {
            if (mob()) setTimeout(closeDrawer, 120);
          });
        });

        window.addEventListener('resize', function () {
          if (!mob() && navList.classList.contains('open')) {
            closeDrawer();
          }
        });

        let lastY = 0;
        
        window.addEventListener('scroll', function () {
          const y = window.scrollY;
          header.classList.toggle('scrolled', y > 10);
          if (!mob()) header.classList.toggle('hide', y > 100 && y > lastY);
          lastY = y;
        }, { passive: true });

        const path = window.location.pathname.split('/').pop() || 'index.html';
        navList.querySelectorAll('a[href]').forEach(function (a) {
          const href = a.getAttribute('href').split('/').pop();
          if (href && href !== '#' && href === path) {
            document.querySelectorAll('.nav-link.active').forEach(active => active.classList.remove('active'));
            a.classList.add('active');
          }
        });

        document.addEventListener('keydown', function (e) {
          if (e.key === 'Escape') closeDrawer();
        });
      }

      if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initNav);
      } else {
        initNav();
      }

    })();
(function () {
      'use strict';

      function initFooter() {
        /* ── Back To Top Element ── */
        const btt = document.getElementById('btt');
        
        if (btt) {
          window.addEventListener('scroll', function () {
            btt.classList.toggle('show', window.scrollY > 300);
          }, { passive: true });

          btt.addEventListener('click', () => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
          });
        }

        /* ── Footer year auto-update ── */
        const yearEl = document.getElementById('footerYear');
        if (yearEl) yearEl.textContent = new Date().getFullYear();
      }

      if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initFooter);
      } else {
        initFooter();
      }

    })();
// civil
/* ========================================================================== */
  /* PAGE INTERACTIONS (Scroll Reveal & Accordion)                              */
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
      if (isNaN(target) || target === 0) return;

      const duration = 1600;
      const start    = performance.now();
      function step(now) {
        const progress = Math.min((now - start) / duration, 1);
        const eased    = 1 - Math.pow(1 - progress, 3);
        el.textContent = Math.floor(eased * target) + suffix;
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

    /* ── Accordion Toggle Helper ── */
    window.toggleAccordion = function(btn) {
      btn.classList.toggle('open');
      const body = btn.nextElementSibling;
      body.classList.toggle('open');
    };

  })();