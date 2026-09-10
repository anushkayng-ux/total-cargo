/*
 * tpt-city.js — city autocomplete widget.
 *
 * Usage:
 *   <input type="text" name="city" data-tpt-city>                  // single-city autocomplete
 *   <input type="text" name="route_drop_cities" data-tpt-city="multi">  // comma-separated chip input
 *
 * Hits GET /cities/lookup?q=… which returns {data:[{id,name,state,label}]}.
 * Caches responses for the session. Highlights the matched substring.
 * Falls back gracefully (regular text input) if JS is disabled or the
 * endpoint errors.
 */
(function () {
  if (typeof document === 'undefined') return;

  const LOOKUP = (window.TPT_BASE_URL || '') + 'cities/lookup';
  const cache  = new Map();   // q -> Promise<Array>

  function fetchCities(q) {
    const key = (q || '').trim().toLowerCase();
    if (cache.has(key)) return cache.get(key);
    const p = fetch(LOOKUP + '?q=' + encodeURIComponent(q) + '&limit=12', { credentials: 'same-origin' })
      .then(r => r.ok ? r.json() : { data: [] })
      .then(j => Array.isArray(j.data) ? j.data : [])
      .catch(() => []);
    cache.set(key, p);
    return p;
  }

  function escapeHtml(s) {
    return String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
  }

  function highlight(text, q) {
    if (!q) return escapeHtml(text);
    const i = text.toLowerCase().indexOf(q.toLowerCase());
    if (i < 0) return escapeHtml(text);
    return escapeHtml(text.slice(0, i))
      + '<strong>' + escapeHtml(text.slice(i, i + q.length)) + '</strong>'
      + escapeHtml(text.slice(i + q.length));
  }

  // Floating dropdown shared across all inputs on the page (only one open at a time)
  let dropdown = null;
  let activeInput = null;
  let activeIndex = -1;
  let currentItems = [];

  function ensureDropdown() {
    if (dropdown) return dropdown;
    dropdown = document.createElement('div');
    dropdown.className = 'tpt-city-dropdown';
    dropdown.style.cssText = [
      'position:absolute', 'z-index:1080', 'background:#fff',
      'border:1px solid #d1d5db', 'border-radius:6px',
      'box-shadow:0 4px 12px rgba(0,0,0,.08)',
      'max-height:280px', 'overflow-y:auto', 'display:none',
      'font-size:.875rem', 'min-width:240px',
    ].join(';');
    document.body.appendChild(dropdown);
    dropdown.addEventListener('mousedown', e => e.preventDefault()); // keep input focused
    return dropdown;
  }

  function positionDropdown(input) {
    const r = input.getBoundingClientRect();
    dropdown.style.left  = (window.scrollX + r.left) + 'px';
    dropdown.style.top   = (window.scrollY + r.bottom + 2) + 'px';
    dropdown.style.width = Math.max(r.width, 240) + 'px';
  }

  function renderItems(items, q) {
    currentItems = items;
    activeIndex  = -1;
    if (!items.length) {
      dropdown.innerHTML = '<div style="padding:.5rem .75rem;color:#6b7280;">No matches. Press Enter to use as-is.</div>';
      return;
    }
    dropdown.innerHTML = items.map((it, i) => (
      '<div class="tpt-city-item" data-i="' + i + '" style="padding:.4rem .75rem;cursor:pointer;border-bottom:1px solid #f3f4f6;">'
      + '<div>' + highlight(it.name, q) + '</div>'
      + '<div style="font-size:.72rem;color:#6b7280;">' + escapeHtml(it.state || '') + (it.tier ? ' · Tier ' + escapeHtml(it.tier) : '') + '</div>'
      + '</div>'
    )).join('');
    [...dropdown.querySelectorAll('.tpt-city-item')].forEach(el => {
      el.addEventListener('mouseenter', () => {
        activeIndex = parseInt(el.dataset.i, 10);
        paintActive();
      });
      el.addEventListener('mousedown', () => pickItem(parseInt(el.dataset.i, 10)));
    });
  }

  function paintActive() {
    [...dropdown.querySelectorAll('.tpt-city-item')].forEach((el, i) => {
      el.style.background = (i === activeIndex) ? '#eef2ff' : '';
    });
    const el = dropdown.querySelector('.tpt-city-item[data-i="' + activeIndex + '"]');
    if (el) el.scrollIntoView({ block: 'nearest' });
  }

  function closeDropdown() {
    if (dropdown) dropdown.style.display = 'none';
    activeInput  = null;
    currentItems = [];
    activeIndex  = -1;
  }

  function pickItem(i) {
    if (!activeInput || !currentItems[i]) return;
    const it    = currentItems[i];
    const mode  = activeInput.dataset.tptCity || 'single';
    if (mode === 'multi') {
      const parts = (activeInput.value || '').split(',');
      parts[parts.length - 1] = ' ' + it.name;          // replace the partial fragment
      // Tidy: trim, dedupe (case-insensitive), drop empties, re-join "A, B, C"
      const seen = new Set(), cleaned = [];
      parts.map(p => p.trim()).forEach(p => {
        if (!p) return;
        const k = p.toLowerCase();
        if (seen.has(k)) return;
        seen.add(k);
        cleaned.push(p);
      });
      activeInput.value = cleaned.join(', ') + ', ';
    } else {
      activeInput.value = it.name;
    }
    activeInput.dispatchEvent(new Event('input', { bubbles: true }));
    activeInput.dispatchEvent(new Event('change', { bubbles: true }));
    closeDropdown();
  }

  function currentFragment(input) {
    if ((input.dataset.tptCity || 'single') !== 'multi') return input.value;
    const parts = (input.value || '').split(',');
    return (parts[parts.length - 1] || '').trim();
  }

  function refresh(input) {
    const q = currentFragment(input);
    if (q.length < 1) { closeDropdown(); return; }
    fetchCities(q).then(items => {
      if (activeInput !== input) return;   // user switched fields meanwhile
      const dd = ensureDropdown();
      positionDropdown(input);
      renderItems(items, q);
      dd.style.display = 'block';
    });
  }

  function attach(input) {
    if (input.__tptCityBound) return;
    input.__tptCityBound = true;
    input.setAttribute('autocomplete', 'off');
    input.setAttribute('spellcheck', 'false');

    input.addEventListener('input', () => { activeInput = input; refresh(input); });
    input.addEventListener('focus', () => { activeInput = input; refresh(input); });
    input.addEventListener('blur',  () => { setTimeout(closeDropdown, 120); });
    input.addEventListener('keydown', e => {
      if (!dropdown || dropdown.style.display === 'none') return;
      if (e.key === 'ArrowDown') {
        e.preventDefault();
        activeIndex = Math.min(activeIndex + 1, currentItems.length - 1);
        paintActive();
      } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        activeIndex = Math.max(activeIndex - 1, 0);
        paintActive();
      } else if (e.key === 'Enter') {
        if (activeIndex >= 0) {
          e.preventDefault();
          pickItem(activeIndex);
        }
      } else if (e.key === 'Escape') {
        closeDropdown();
      } else if (e.key === ',' && (input.dataset.tptCity || 'single') === 'multi') {
        // Let the comma through so the user can type "Mumbai, Pune" manually
        setTimeout(() => refresh(input), 0);
      }
    });
  }

  function scan() {
    document.querySelectorAll('input[data-tpt-city]').forEach(attach);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', scan);
  } else {
    scan();
  }
  // Re-scan on demand (e.g. when route-pair rows are added dynamically)
  window.tptCityScan = scan;
  document.addEventListener('click', e => {
    if (dropdown && e.target !== dropdown && !dropdown.contains(e.target) && e.target !== activeInput) {
      closeDropdown();
    }
  });
  window.addEventListener('resize', () => { if (activeInput) positionDropdown(activeInput); });
  window.addEventListener('scroll', () => { if (activeInput) positionDropdown(activeInput); }, true);
})();
