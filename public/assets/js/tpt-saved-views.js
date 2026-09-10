/*
 * tpt-saved-views.js — per-user saved filter shortcuts.
 *
 * Drop a `<div data-tpt-saved-views="page-key"></div>` into any list view and
 * this script will render: a "Save current filter" button + a chip per saved
 * view that re-applies its querystring on click.
 *
 *   <div data-tpt-saved-views="leads"></div>
 *
 * Querystring captured = `window.location.search` minus the `page=` param so
 * pagination doesn't pollute the saved filter.
 */
(function () {
  const base = (window.TPT_BASE_URL || '') + '_views';

  function csrfToken() {
    const m = document.querySelector('meta[name="csrf-token"]');
    return m ? m.getAttribute('content') : '';
  }

  function strippedQuery() {
    const u = new URL(window.location.href);
    u.searchParams.delete('page');
    return u.search.replace(/^\?/, '');
  }

  function render(host, page, views) {
    host.innerHTML = '';
    const wrap = document.createElement('div');
    wrap.className = 'd-flex flex-wrap gap-1 align-items-center';
    wrap.style.fontSize = '.78rem';

    views.forEach(v => {
      const a = document.createElement('a');
      a.href = window.location.pathname + (v.qs ? '?' + v.qs : '');
      a.className = 'badge text-decoration-none border';
      a.style.cssText = 'background:#eef2ff;color:#3730a3;border-color:#c7d2fe;cursor:pointer;padding:.35rem .55rem;';
      a.innerHTML = '<i class="bi bi-bookmark-star"></i> ' + escapeHtml(v.name)
                  + ' <span class="ms-1 text-muted" data-del="' + v.id + '" title="Delete view">×</span>';
      a.addEventListener('click', e => {
        if (e.target.dataset.del) {
          e.preventDefault();
          if (!confirm('Delete this saved view?')) return;
          fetch(base + '/delete', { method: 'POST', credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'id=' + encodeURIComponent(v.id) + '&page=' + encodeURIComponent(page) + '&csrf_token_name=' + encodeURIComponent(csrfToken()),
          }).then(r => r.json()).then(j => render(host, page, j.views || []));
        }
      });
      wrap.appendChild(a);
    });

    const save = document.createElement('button');
    save.type = 'button';
    save.className = 'btn btn-sm btn-light';
    save.innerHTML = '<i class="bi bi-bookmark-plus"></i> Save view';
    save.addEventListener('click', () => {
      const name = prompt('Save current filter as…');
      if (!name || !name.trim()) return;
      fetch(base + '/save', { method: 'POST', credentials: 'same-origin',
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'name=' + encodeURIComponent(name) + '&page=' + encodeURIComponent(page) + '&querystring=' + encodeURIComponent(strippedQuery()) + '&csrf_token_name=' + encodeURIComponent(csrfToken()),
      }).then(r => r.json()).then(j => render(host, page, j.views || []));
    });
    wrap.appendChild(save);

    host.appendChild(wrap);
  }

  function escapeHtml(s) {
    return String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
  }

  document.querySelectorAll('[data-tpt-saved-views]').forEach(host => {
    const page = host.dataset.tptSavedViews;
    fetch(base + '?page=' + encodeURIComponent(page), { credentials: 'same-origin' })
      .then(r => r.ok ? r.json() : { views: [] })
      .then(j => render(host, page, j.views || []))
      .catch(() => render(host, page, []));
  });
})();
