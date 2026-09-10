/**
 * Column-toggle helper — attaches a "Columns" dropdown to any <table>
 * that carries `data-tpt-cols="<key>"` in its markup.
 *
 * Usage in views:
 *   <table data-tpt-cols="clients">
 *     <thead><tr>
 *       <th data-col="code">Code</th>
 *       <th data-col="company">Company</th>
 *       ...
 *     </tr></thead>
 *     <tbody>
 *       <tr>
 *         <td data-col="code">...</td>
 *         <td data-col="company">...</td>
 *       </tr>
 *     </tbody>
 *   </table>
 *
 * The user's chosen hidden-column set is persisted in localStorage under
 * `tpt_cols_<key>`, so their preference survives navigation. Falls back
 * gracefully if a <td> lacks a data-col (matches by column index).
 */
(function () {
  if (typeof document === 'undefined') return;

  function init() {
    document.querySelectorAll('table[data-tpt-cols]').forEach(function (table) {
      if (table.dataset.tptColsInit === '1') return;
      table.dataset.tptColsInit = '1';

      var storeKey = 'tpt_cols_' + table.dataset.tptCols;
      var heads    = Array.prototype.slice.call(table.querySelectorAll('thead th[data-col]'));
      if (!heads.length) return;

      // Read hidden-column set from localStorage
      var hidden = new Set();
      try {
        (localStorage.getItem(storeKey) || '').split(',').filter(Boolean).forEach(function (k) { hidden.add(k); });
      } catch (e) {}

      // Build toggle UI
      var wrap = document.createElement('div');
      wrap.className = 'dropdown d-inline-block';
      wrap.innerHTML =
        '<button type="button" class="btn btn-sm btn-light dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" title="Show / hide columns">' +
          '<i class="bi bi-layout-three-columns"></i> Columns' +
        '</button>' +
        '<ul class="dropdown-menu dropdown-menu-end shadow-sm" style="padding:.5rem;min-width:220px;max-height:360px;overflow-y:auto;"></ul>';

      var menu = wrap.querySelector('ul.dropdown-menu');
      heads.forEach(function (th) {
        var key   = th.dataset.col;
        var label = (th.dataset.colLabel || th.textContent || key).trim();
        var isChecked = !hidden.has(key);
        var li = document.createElement('li');
        li.innerHTML =
          '<label class="d-flex align-items-center gap-2 px-2 py-1" style="cursor:pointer;font-size:.85rem;user-select:none;">' +
            '<input type="checkbox" class="form-check-input m-0" data-col-key="' + key + '" ' + (isChecked ? 'checked' : '') + '>' +
            '<span>' + label + '</span>' +
          '</label>';
        menu.appendChild(li);
      });
      // Reset all
      var reset = document.createElement('li');
      reset.innerHTML = '<hr class="dropdown-divider my-1"><button type="button" class="dropdown-item text-primary" data-cols-reset style="font-size:.82rem;">Show all columns</button>';
      menu.appendChild(reset);

      // Anchor: try to inject inside the header row above the card, else make one
      var cardEl = table.closest('.card');
      var anchor = cardEl && cardEl.previousElementSibling;
      var isHeaderRow = anchor && (anchor.classList.contains('d-flex') || anchor.classList.contains('flex-wrap'));
      if (!anchor || !isHeaderRow) {
        anchor = document.createElement('div');
        anchor.className = 'd-flex justify-content-end mb-2';
        cardEl && cardEl.parentNode && cardEl.parentNode.insertBefore(anchor, cardEl);
      }
      // Insert BEFORE the primary "Add" button so it groups nicely with other tools
      var primary = anchor.querySelector('.btn-primary');
      if (primary) anchor.insertBefore(wrap, primary);
      else         anchor.appendChild(wrap);

      // Apply visibility (position-based, in case some <td>s don't carry data-col)
      function apply() {
        heads.forEach(function (th, idx) {
          var key = th.dataset.col;
          var isHidden = hidden.has(key);
          th.style.display = isHidden ? 'none' : '';
          table.querySelectorAll('tbody tr').forEach(function (tr) {
            var cell = tr.querySelector('td[data-col="' + key + '"]') || tr.cells[idx];
            if (cell) cell.style.display = isHidden ? 'none' : '';
          });
          // colspan="N" cells (empty-state row) should always show
          table.querySelectorAll('tbody tr td[colspan]').forEach(function (td) { td.style.display = ''; });
        });
      }
      apply();

      // Wire checkboxes
      menu.querySelectorAll('input[type="checkbox"][data-col-key]').forEach(function (cb) {
        cb.addEventListener('change', function () {
          if (cb.checked) hidden.delete(cb.dataset.colKey);
          else            hidden.add(cb.dataset.colKey);
          try { localStorage.setItem(storeKey, Array.from(hidden).join(',')); } catch (e) {}
          apply();
        });
        // Prevent the dropdown from closing when a label is clicked
        cb.closest('label').addEventListener('click', function (e) { e.stopPropagation(); });
      });
      menu.querySelector('[data-cols-reset]').addEventListener('click', function () {
        hidden.clear();
        try { localStorage.removeItem(storeKey); } catch (e) {}
        menu.querySelectorAll('input[type="checkbox"][data-col-key]').forEach(function (cb) { cb.checked = true; });
        apply();
      });
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();

/**
 * Table wheel-scroll — Shift+wheel or wheel-on-overflowed-container
 * scrolls horizontally, so users don't have to hunt for the bottom scrollbar.
 * Only activates on containers that actually need horizontal scroll.
 */
(function () {
  function bind() {
    document.querySelectorAll('.table-responsive').forEach(function (el) {
      if (el.dataset.wheelBound === '1') return;
      el.dataset.wheelBound = '1';
      el.addEventListener('wheel', function (ev) {
        var overflows = el.scrollWidth > el.clientWidth + 1;
        if (!overflows) return;
        // Shift+wheel always scrolls sideways; plain vertical wheel does too
        // when the container has horizontal overflow AND the page can't
        // absorb the vertical delta (already at top/bottom of the page).
        var deltaX = ev.deltaX;
        var deltaY = ev.deltaY;
        if (ev.shiftKey || Math.abs(deltaY) > Math.abs(deltaX)) {
          el.scrollLeft += (deltaY || deltaX);
          ev.preventDefault();
        }
      }, { passive: false });
    });
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', bind);
  else bind();
})();

/**
 * Universal per-page selector — attaches a small "Show N per page" dropdown
 * next to any Bootstrap-pager. Uses ?per=N; user's choice re-runs the URL.
 */
(function () {
  function attach() {
    document.querySelectorAll('.pagination').forEach(function (pg) {
      if (pg.dataset.perAttached === '1') return;
      pg.dataset.perAttached = '1';
      var url  = new URL(window.location.href);
      var curr = url.searchParams.get('per') || '';
      var wrap = document.createElement('div');
      wrap.className = 'd-inline-flex align-items-center gap-2 ms-3';
      wrap.style.fontSize = '.85rem';
      wrap.innerHTML =
        '<span class="text-muted">Rows:</span>' +
        '<select class="form-select form-select-sm" style="width:auto;">' +
          ['50','100','250','500'].map(function (n) {
            return '<option value="' + n + '"' + (curr === n ? ' selected' : '') + '>' + n + '</option>';
          }).join('') +
        '</select>';
      wrap.querySelector('select').addEventListener('change', function (e) {
        var u = new URL(window.location.href);
        u.searchParams.set('per', e.target.value);
        u.searchParams.delete('page');
        window.location.href = u.toString();
      });
      var host = pg.closest('nav, .mt-3, .pagination-wrapper') || pg.parentElement;
      if (host) host.appendChild(wrap);
    });
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', attach);
  else attach();
})();
