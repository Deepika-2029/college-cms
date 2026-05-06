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