// Feedback_Page
/* ========================================================================== */
  /* API & INTERACTION LOGIC                                                    */
  /* ========================================================================== */
  (function () {
    'use strict';

    const API_BASE   = 'https://gpnainital.com/api/data';
    const TABLE_NAME = 'feedback';
    const API_KEY    = '2669beece89d421e713bb9e42aea8363daad4f55c8c755f401890de90b8231af';

    let currentRating = 5;
    const ratingLabels = ["Poor", "Fair", "Good", "Very Good", "Excellent"];

    /* ── 1. Scroll-Reveal ── */
    const revealEls = document.querySelectorAll('.fade-up');
    const observer = new IntersectionObserver((entries) => {
      entries.forEach((e) => { if(e.isIntersecting) { e.target.classList.add('visible'); observer.unobserve(e.target); } });
    }, { threshold: 0.1 });
    revealEls.forEach(el => observer.observe(el));

    /* ── 2. Star Rating Logic ── */
    function initStars() {
      const stars = document.querySelectorAll('.star-btn');
      const ratingText = document.getElementById('ratingValue');

      stars.forEach((star, idx) => {
        star.addEventListener('click', () => {
          currentRating = idx + 1;
          updateStars();
        });
      });

      function updateStars() {
        stars.forEach((s, i) => {
          s.classList.toggle('active', i < currentRating);
        });
        ratingText.textContent = ratingLabels[currentRating - 1] + ` (${currentRating} ★)`;
      }
      updateStars();
    }

    /* ── 3. Fetch Feedbacks ── */
    async function loadFeedback() {
      const container = document.getElementById('feedbackList');
      try {
        const res = await fetch(`${API_BASE}/${TABLE_NAME}?key=${API_KEY}`);
        if(!res.ok) throw new Error('Fetch failed');
        const data = await res.json();
        const items = data.data || [];

        if(items.length === 0) {
          container.innerHTML = `<div class="col-12 text-center text-muted py-4">No feedback available yet. Be the first to share!</div>`;
          return;
        }

        container.innerHTML = items.reverse().slice(0, 12).map((fb, idx) => {
          const initials = fb.name ? fb.name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2) : '?';
          const starsHtml = Array(5).fill(0).map((_, i) => 
            `<i class="bi bi-star-fill" style="color: ${i < (fb.rating || 5) ? 'var(--gold)' : 'var(--sur-3)'}"></i>`
          ).join('');

          const time = fb.created_at ? new Date(fb.created_at).toLocaleDateString() : 'Recently';

          return `
            <div class="fb-card-wrap fade-up visible">
              <div class="fb-card">
                <div class="fb-header">
                  <div class="fb-user">
                    <div class="fb-avatar">${initials}</div>
                    <div>
                      <h4 class="fb-name">${escHtml(fb.name)}</h4>
                      <span class="fb-time">${time}</span>
                    </div>
                  </div>
                  <div class="fb-stars">${starsHtml}</div>
                </div>
                <div class="fb-content">"${escHtml(fb.message)}"</div>
                <span class="fb-type-badge">${fb.type || 'General'}</span>
              </div>
            </div>
          `;
        }).join('');

      } catch (err) {
        container.innerHTML = `<div class="col-12 text-center text-danger">Failed to load the feedback wall.</div>`;
      }
    }

    /* ── 4. Form Submission ── */
    async function submitFeedback(e) {
      e.preventDefault();
      const form = e.target;
      const btn = document.getElementById('submitBtn');
      
      const name = document.getElementById('fbName').value.trim();
      const email = document.getElementById('fbEmail').value.trim();
      const type = document.getElementById('fbType').value;
      const message = document.getElementById('fbMessage').value.trim();

      if (!name || !type || !message) {
        showToast('✗ Please fill in all required fields.', 'error');
        return;
      }

      btn.disabled = true;
      btn.classList.add('loading');

      const payload = { name, email, type, rating: currentRating, message };

      try {
        const res = await fetch(`${API_BASE}/${TABLE_NAME}?key=${API_KEY}`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload)
        });

        if (!res.ok) throw new Error(`Submit error ${res.status}`);

        showToast('✓ Thank you for your feedback!', 'success');
        form.reset();
        currentRating = 5;
        initStars();
        setTimeout(loadFeedback, 800);

      } catch (err) {
        showToast('✗ Submission failed: ' + err.message, 'error');
      } finally {
        btn.disabled = false;
        btn.classList.remove('loading');
      }
    }

    /* ── 5. FAQ Toggle ── */
    function initFAQ() {
      document.querySelectorAll('.faq-question').forEach(q => {
        q.addEventListener('click', () => {
          const item = q.parentElement;
          item.classList.toggle('active');
        });
      });
    }

    /* ── HELPERS ── */
    function showToast(msg, type) {
      const toast = document.getElementById('fbToast');
      const msgEl = document.getElementById('toastMsg');
      toast.className = `toast-box show toast-${type}`;
      msgEl.textContent = msg;
      setTimeout(() => toast.classList.remove('show'), 4000);
    }

    function escHtml(str) {
      const d = document.createElement('div');
      d.textContent = str || '';
      return d.innerHTML;
    }

    /* ── INIT ── */
    document.addEventListener('DOMContentLoaded', () => {
      initStars();
      initFAQ();
      loadFeedback();
      const form = document.getElementById('feedbackForm');
      if (form) form.addEventListener('submit', submitFeedback);
    });

  })();