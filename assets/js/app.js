/**
 * EVENT RESERVATION — Navbar + Global UI Scripts
 * Pure vanilla JS, zero dependencies
 */

(function () {
  'use strict';

  /* ── Refs ── */
  const navbar   = document.getElementById('navbar');
  const burger   = document.getElementById('navBurger');
  const drawer   = document.getElementById('navDrawer');
  const overlay  = document.getElementById('navOverlay');

  let isOpen  = false;
  let ticking = false;

  /* ── Scroll → add .is-scrolled ── */
  function onScroll() {
    if (!ticking) {
      requestAnimationFrame(() => {
        if (navbar) navbar.classList.toggle('is-scrolled', window.scrollY > 16);
        ticking = false;
      });
      ticking = true;
    }
  }
  window.addEventListener('scroll', onScroll, { passive: true });
  onScroll();

  /* ── Mobile drawer ── */
  function openDrawer() {
    isOpen = true;
    if (drawer)  drawer.classList.add('open');
    if (overlay) overlay.classList.add('visible');
    if (burger)  { burger.setAttribute('aria-expanded', 'true'); burger.setAttribute('aria-label', 'Close menu'); }
    document.body.style.overflow = 'hidden';
  }
  function closeDrawer() {
    isOpen = false;
    if (drawer)  drawer.classList.remove('open');
    if (overlay) overlay.classList.remove('visible');
    if (burger)  { burger.setAttribute('aria-expanded', 'false'); burger.setAttribute('aria-label', 'Open menu'); }
    document.body.style.overflow = '';
  }
  if (burger)  burger.addEventListener('click', () => isOpen ? closeDrawer() : openDrawer());
  if (overlay) overlay.addEventListener('click', closeDrawer);

  /* Close on Escape */
  document.addEventListener('keydown', e => {
    if (e.key === 'Escape' && isOpen) { closeDrawer(); burger && burger.focus(); }
  });

  /* Close on resize > 900 */
  let rTimer;
  window.addEventListener('resize', () => {
    clearTimeout(rTimer);
    rTimer = setTimeout(() => { if (window.innerWidth > 900 && isOpen) closeDrawer(); }, 100);
  });

  /* Close on drawer link click */
  if (drawer) drawer.querySelectorAll('a').forEach(a => a.addEventListener('click', () => setTimeout(closeDrawer, 80)));

  /* ── Active link detection ── */
  const currentFile = window.location.pathname.split('/').pop() || 'index.php';
  document.querySelectorAll('[data-page]').forEach(el => {
    const page = el.getAttribute('data-page');
    const match = page === currentFile || (page === 'index.php' && (currentFile === '' || currentFile === '/'));
    el.classList.toggle('active', match);
    if (match) el.setAttribute('aria-current', 'page');
  });

  /* ── Admin sidebar toggle (mobile) ── */
  const sidebarToggle = document.getElementById('sidebarToggle');
  const adminSidebar  = document.getElementById('adminSidebar');
  if (sidebarToggle && adminSidebar) {
    sidebarToggle.addEventListener('click', () => adminSidebar.classList.toggle('open'));
    document.addEventListener('click', e => {
      if (adminSidebar.classList.contains('open') &&
          !adminSidebar.contains(e.target) &&
          !sidebarToggle.contains(e.target)) {
        adminSidebar.classList.remove('open');
      }
    });
  }

  /* ── Password visibility toggle ── */
  document.querySelectorAll('.password-toggle').forEach(btn => {
    btn.addEventListener('click', () => {
      const input = btn.closest('.password-wrap')?.querySelector('input');
      if (!input) return;
      const isPass = input.type === 'password';
      input.type = isPass ? 'text' : 'password';
      btn.innerHTML = isPass
        ? `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/></svg>`
        : `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>`;
    });
  });

  /* ── Filter chips (homepage) ── */
  document.querySelectorAll('.chip[data-filter]').forEach(chip => {
    chip.addEventListener('click', () => {
      const group = chip.closest('.filter-chips');
      if (group) group.querySelectorAll('.chip').forEach(c => c.classList.remove('active'));
      chip.classList.add('active');
      // Dispatch custom event so PHP pages can hook in if needed
      document.dispatchEvent(new CustomEvent('filterChange', { detail: { value: chip.dataset.filter } }));
    });
  });

  /* ── Reservation tabs ── */
  document.querySelectorAll('.res-tab[data-tab]').forEach(tab => {
    tab.addEventListener('click', () => {
      document.querySelectorAll('.res-tab').forEach(t => t.classList.remove('active'));
      tab.classList.add('active');
      const status = tab.dataset.tab;
      document.querySelectorAll('.res-card[data-status]').forEach(card => {
        const show = status === 'all' || card.dataset.status === status;
        card.style.display = show ? '' : 'none';
        if (show) card.style.animation = 'fadeUp 0.3s ease both';
      });
    });
  });

  /* ── Wishlist toggle ── */
  document.querySelectorAll('.event-card__wish').forEach(btn => {
    btn.addEventListener('click', e => {
      e.preventDefault(); e.stopPropagation();
      btn.classList.toggle('liked');
      const svg = btn.querySelector('svg');
      if (svg) svg.setAttribute('fill', btn.classList.contains('liked') ? 'currentColor' : 'none');
    });
  });

  /* ── Fade-up on scroll (IntersectionObserver) ── */
  if ('IntersectionObserver' in window) {
    const io = new IntersectionObserver((entries) => {
      entries.forEach(en => {
        if (en.isIntersecting) { en.target.classList.add('fade-up'); io.unobserve(en.target); }
      });
    }, { threshold: 0.1 });
    document.querySelectorAll('.event-card, .stat-card, .res-card').forEach(el => {
      if (!el.classList.contains('fade-up')) io.observe(el);
    });
  }

})();
