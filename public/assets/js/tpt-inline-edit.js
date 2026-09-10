/*
 * tpt-inline-edit.js — click a badge to switch it into a <select>, save on
 * blur/Enter. Wire by adding `data-tpt-inline-select` attributes:
 *
 *   <span class="badge"
 *         data-tpt-inline-select
 *         data-url="/trips/42/status"
 *         data-field="new_status"
 *         data-options='["Pending","In Transit","Delivered","Closed"]'
 *         data-value="In Transit">In Transit</span>
 *
 * POSTs <field>=<new value> + CSRF to <url>. Treats any 200/302 as success.
 */
(function () {
  function csrfToken() {
    const m = document.querySelector('meta[name="csrf-token"]');
    return m ? m.getAttribute('content') : '';
  }

  function activate(el) {
    if (el.__inlineActive) return;
    el.__inlineActive = true;

    const url   = el.dataset.url;
    const field = el.dataset.field;
    let opts;
    try { opts = JSON.parse(el.dataset.options || '[]'); } catch (e) { opts = []; }
    const cur = el.dataset.value || el.textContent.trim();
    const orig = el.innerHTML;

    const sel = document.createElement('select');
    sel.className = 'form-select form-select-sm d-inline-block';
    sel.style.cssText = 'width:auto;max-width:200px;';
    opts.forEach(o => {
      const opt = document.createElement('option');
      opt.value = typeof o === 'object' ? o.value : o;
      opt.textContent = typeof o === 'object' ? o.label : o;
      if (opt.value === cur) opt.selected = true;
      sel.appendChild(opt);
    });
    el.replaceWith(sel);
    sel.focus();

    function save() {
      const val = sel.value;
      if (val === cur) { restore(orig); return; }
      const body = new URLSearchParams();
      body.set(field, val);
      body.set('csrf_token_name', csrfToken());
      sel.disabled = true;
      fetch(url, { method: 'POST', credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' }, body })
        .then(r => {
          if (!r.ok && r.status !== 302) throw new Error('save failed: ' + r.status);
          // Update the original badge in place
          el.dataset.value = val;
          el.textContent = (typeof opts[0] === 'object'
            ? (opts.find(o => o.value === val) || {}).label
            : val) || val;
          sel.replaceWith(el);
          el.__inlineActive = false;
          // Visual ack
          el.classList.add('inline-edit-ack');
          setTimeout(() => el.classList.remove('inline-edit-ack'), 1200);
        })
        .catch(err => {
          alert('Could not save: ' + err.message);
          restore(orig);
        });
    }

    function restore(html) {
      el.innerHTML = html;
      sel.replaceWith(el);
      el.__inlineActive = false;
    }

    sel.addEventListener('change', save);
    sel.addEventListener('blur',   () => setTimeout(() => { if (sel.parentNode) restore(orig); }, 150));
    sel.addEventListener('keydown', e => {
      if (e.key === 'Escape') restore(orig);
      if (e.key === 'Enter')  { e.preventDefault(); save(); }
    });
  }

  document.addEventListener('click', e => {
    const el = e.target.closest('[data-tpt-inline-select]');
    if (!el) return;
    e.preventDefault();
    activate(el);
  });

  // Tiny CSS for the success flash + cursor hint
  const css = document.createElement('style');
  css.textContent = `
    [data-tpt-inline-select] { cursor: pointer; border-bottom: 1px dashed currentColor; padding-bottom: 1px; }
    [data-tpt-inline-select]:hover { opacity: .85; }
    .inline-edit-ack { animation: tpt-flash .9s ease; }
    @keyframes tpt-flash {
      0%   { box-shadow: 0 0 0 0   rgba(16,185,129,.5); }
      30%  { box-shadow: 0 0 0 6px rgba(16,185,129,.4); }
      100% { box-shadow: 0 0 0 0   rgba(16,185,129,0); }
    }
  `;
  document.head.appendChild(css);
})();
