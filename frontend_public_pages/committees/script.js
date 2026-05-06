// commitee
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

    /* ── Split-Layout Modal — Card click handler ── */
    document.querySelectorAll('.cmt-card').forEach((card) => {
      card.addEventListener('click', () => {
        document.getElementById('mIcon').textContent = card.dataset.emoji || '';
        document.getElementById('mTop').style.backgroundColor = card.dataset.bg || 'var(--primary-l)';
        
        document.getElementById('mTitle').textContent = card.dataset.title || '';
        document.getElementById('mBadge').textContent = card.dataset.badge || '';

        // Process Members
        const membersData = card.dataset.members;
        const memberListEl = document.getElementById('mMemberList');
        
        if (membersData) {
          try {
            const members = JSON.parse(membersData);
            const listHtml = members.map(m => {
              // Assign distinct styling classes based on role
              let roleClass = 'role-member';
              const roleLower = m.r.toLowerCase();
              if (roleLower.includes('chair') || roleLower.includes('presiding')) {
                roleClass = 'role-chairman';
              } else if (roleLower.includes('student')) {
                roleClass = 'role-student';
              }

              return `
                <li class="member-row">
                  <div class="member-name">
                    <div class="member-icon"><i class="bi bi-person-fill"></i></div>
                    ${m.n}
                  </div>
                  <div class="member-role ${roleClass}">${m.r}</div>
                </li>
              `;
            }).join('');
            memberListEl.innerHTML = listHtml;
          } catch (e) {
            console.error("Invalid JSON in data-members", e);
            memberListEl.innerHTML = '';
          }
        } else {
          memberListEl.innerHTML = '';
        }

        // Show Modal
        document.getElementById('cmtModalOverlay').classList.add('open');
        document.body.style.overflow = 'hidden';
      });
    });

    // Close Modal Logic
    const overlay = document.getElementById('cmtModalOverlay');
    const closeBtn = document.getElementById('mClose');

    function closeCmtModal() {
      overlay.classList.remove('open');
      document.body.style.overflow = '';
    }

    if(closeBtn) closeBtn.addEventListener('click', closeCmtModal);
    if(overlay) overlay.addEventListener('click', e => {
      if (e.target === overlay) closeCmtModal();
    });
    document.addEventListener('keydown', e => {
      if (e.key === 'Escape') closeCmtModal();
    });

  })();