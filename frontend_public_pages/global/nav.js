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