(function () {
  const btn = document.querySelector('[data-toggle-sidebar]');
  const sb  = document.querySelector('.tpt-sidebar');
  if (btn && sb) {
    btn.addEventListener('click', () => sb.classList.toggle('open'));
    document.addEventListener('click', (e) => {
      if (window.innerWidth >= 992) return;
      if (!sb.classList.contains('open')) return;
      if (sb.contains(e.target) || btn.contains(e.target)) return;
      sb.classList.remove('open');
    });
  }

  // Retro theme: fold the global topbar controls (Quick Add / notifications /
  // profile / sign out) into the navy title banner's right side, next to
  // "TOTAL CARGO EXPRESS…" — the banner is on every page (unlike the icon
  // toolbar, which only exists on retro-styled pages), so this keeps them
  // reachable everywhere instead of only on the handful of rebuilt pages.
  (function () {
    var controls = document.getElementById('tptGlobalControls');
    var target = document.getElementById('tptBannerControls');
    if (controls && target) {
      target.appendChild(controls);
    }
  })();

  // Retro-style clickable grid rows — click anywhere on a row to open its
  // detail page, same as the total-cargo-demo prototype. Clicks on a link,
  // button, form, or form field inside the row still do their own thing.
  document.querySelectorAll('tr.row-link[data-href]').forEach(tr => {
    tr.addEventListener('click', (e) => {
      if (e.target.closest('a, button, input, select, textarea, form, [data-tpt-inline-select]')) return;
      window.location.href = tr.dataset.href;
    });
  });

  document.querySelectorAll('[data-confirm]').forEach(el => {
    el.addEventListener('submit', (e) => {
      const msg = el.getAttribute('data-confirm') || 'Are you sure?';
      if (!confirm(msg)) e.preventDefault();
    });
  });

  // --- Accordion sidebar ---
  const STORAGE_KEY = 'tpt_open_section';
  const sections = Array.from(document.querySelectorAll('.tpt-section'));
  if (sections.length) {
    // Mark the section containing an active link, regardless of stored state.
    sections.forEach(sec => {
      if (sec.querySelector('a.active')) sec.classList.add('has-active');
    });

    // Decide which section to auto-open: the active one wins; else the stored one.
    const activeFromPage = document.body.dataset.activeSection || '';
    const stored         = (function () {
      try { return localStorage.getItem(STORAGE_KEY) || ''; } catch (e) { return ''; }
    })();
    const initialKey = activeFromPage || stored;

    const openOnly = (key) => {
      sections.forEach(sec => sec.classList.toggle('open', sec.dataset.section === key));
    };
    if (initialKey) openOnly(initialKey);

    sections.forEach(sec => {
      const head = sec.querySelector('.tpt-section-head');
      if (!head) return;
      head.addEventListener('click', () => {
        const isOpen = sec.classList.contains('open');
        // Strict accordion: close all, open only this if it was closed.
        sections.forEach(s => s.classList.remove('open'));
        if (!isOpen) {
          sec.classList.add('open');
          try { localStorage.setItem(STORAGE_KEY, sec.dataset.section); } catch (e) {}
        } else {
          try { localStorage.removeItem(STORAGE_KEY); } catch (e) {}
        }
      });
    });

    // When a child link is clicked, remember its parent section so the next page
    // loads with the same section open (active-on-server takes priority anyway).
    sections.forEach(sec => {
      sec.querySelectorAll('a').forEach(a => {
        a.addEventListener('click', () => {
          try { localStorage.setItem(STORAGE_KEY, sec.dataset.section); } catch (e) {}
        });
      });
    });
  }
})();
