<?php
/** @var \App\Libraries\Auth $auth */
$menu = tpt_menu_visible($auth);
$appName = env('tpt.appName', 'TPT Aggregator');
// Figure out which accordion section owns the active route, so we can open it on first render.
$activeSectionKey = '';
foreach ($menu as $item) {
    if (!empty($item['children'])) {
        foreach ($item['children'] as $c) {
            if (tpt_active($c['url']) === 'active') {
                $activeSectionKey = strtolower(preg_replace('/\s+/', '-', $item['label']));
                break 2;
            }
        }
    }
}
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="csrf-token" content="<?= csrf_hash() ?>">
<title><?= esc($pageTitle ?? $appName) ?> · <?= esc($appName) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<!-- Tom Select — turns any <select> with class "tpt-search" into a searchable, filter-as-you-type combobox -->
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
<link href="<?= base_url('assets/css/app.css') ?>" rel="stylesheet">
<!-- Theme v2 loads LAST so its CSS variables + overrides win. Delete this line to revert. -->
<link href="<?= base_url('assets/css/theme-v2.css') ?>?v=2026-08-03" rel="stylesheet">
</head>
<body data-active-section="<?= esc($activeSectionKey) ?>">
<a href="#tpt-main-content" class="skip-to-main">Skip to main content</a>
<div class="tpt-app">
  <aside class="tpt-sidebar">
    <div class="tpt-sidebar-brand">
      <?php $logo = function_exists('tpt_logo_url') ? tpt_logo_url() : null; ?>
      <?php if ($logo): ?>
        <img src="<?= esc($logo) ?>" alt="<?= esc($appName) ?>" style="height:24px;max-width:160px;vertical-align:middle;margin-right:.4rem;">
      <?php else: ?>
        <i class="bi bi-truck"></i>
      <?php endif; ?>
      <?= esc($appName) ?>
    </div>
    <ul class="tpt-nav">
      <?php foreach ($menu as $item): ?>
        <?php if (!empty($item['children'])):
          $key = strtolower(preg_replace('/\s+/', '-', $item['label']));
        ?>
          <li class="tpt-section" data-section="<?= esc($key) ?>">
            <button type="button" class="tpt-section-head" aria-expanded="false">
              <i class="bi bi-<?= esc($item['icon'] ?? 'folder') ?>"></i>
              <span class="flex-grow-1 text-start"><?= esc($item['label']) ?></span>
              <i class="bi bi-chevron-down tpt-section-caret"></i>
            </button>
            <ul class="tpt-section-items" style="padding:0;margin:0;list-style:none;">
              <?php foreach ($item['children'] as $c): ?>
                <li>
                  <a href="<?= site_url($c['url']) ?>" class="<?= tpt_active($c['url']) ?>">
                    <?= esc($c['label']) ?>
                  </a>
                </li>
              <?php endforeach; ?>
            </ul>
          </li>
        <?php else: ?>
          <li>
            <a href="<?= site_url($item['url']) ?>" class="tpt-single <?= tpt_active($item['url']) ?>">
              <?php if (!empty($item['icon'])): ?><i class="bi bi-<?= esc($item['icon']) ?>"></i><?php endif; ?>
              <?= esc($item['label']) ?>
            </a>
          </li>
        <?php endif; ?>
      <?php endforeach; ?>
    </ul>
  </aside>

  <div class="tpt-main">
    <div class="tpt-topbar">
      <button class="tpt-hamburger" data-toggle-sidebar aria-label="Menu">
        <i class="bi bi-list"></i>
      </button>
      <div class="tpt-topbar-title"><?= esc($pageTitle ?? '') ?></div>

      <!-- Universal search trigger -->
      <button id="tptSearchBtn" type="button" class="btn btn-sm btn-light d-none d-md-inline-flex align-items-center gap-2" style="font-size:.85rem;color:#6b7280;min-width:240px;justify-content:flex-start;" aria-label="Open search">
        <i class="bi bi-search"></i>
        <span>Search clients, trips, invoices…</span>
        <kbd style="margin-left:auto;background:#f3f4f6;border:1px solid #e5e7eb;border-radius:4px;padding:0 .35em;font-size:.7em;color:#6b7280;">Ctrl K</kbd>
      </button>
      <button id="tptSearchBtnMobile" type="button" class="btn btn-sm btn-light d-md-none" aria-label="Search"><i class="bi bi-search"></i></button>

      <div class="tpt-topbar-right">
        <?php
          // Quick Add menu — only shows actions the current user is allowed to create.
          $quickAdd = [];
          if ($auth->can('leads', 'can_add'))      $quickAdd[] = ['url' => site_url('leads/create'),    'icon' => 'person-plus',     'label' => 'Add Lead'];
          if ($auth->can('rfq', 'can_add'))        $quickAdd[] = ['url' => site_url('rfq/create'),       'icon' => 'file-earmark-plus','label' => 'Add RFQ'];
          if ($auth->can('bookings', 'can_add'))   $quickAdd[] = ['url' => site_url('bookings/create'),  'icon' => 'journal-plus',    'label' => 'Add Booking'];
          if ($auth->can('trips', 'can_edit'))     $quickAdd[] = ['url' => site_url('dockets/create'), 'icon' => 'file-earmark-ruled', 'label' => 'Create Docket (LR)'];
          if ($auth->can('clients', 'can_add'))    $quickAdd[] = ['url' => site_url('clients/create'),   'icon' => 'building-add',    'label' => 'Add Client'];
          if ($auth->can('vendors', 'can_add'))    $quickAdd[] = ['url' => site_url('vendors/create'),   'icon' => 'truck',           'label' => 'Add Vendor'];
        ?>
        <?php if (!empty($quickAdd)): ?>
        <div class="dropdown">
          <button class="btn btn-sm btn-primary dropdown-toggle d-inline-flex align-items-center gap-1" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Quick add">
            <i class="bi bi-plus-lg"></i> <span class="d-none d-md-inline">Quick Add</span>
          </button>
          <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="font-size:.9rem;">
            <li><h6 class="dropdown-header">Create new</h6></li>
            <?php foreach ($quickAdd as $qa): ?>
              <li><a class="dropdown-item d-flex align-items-center gap-2" href="<?= $qa['url'] ?>"><i class="bi bi-<?= $qa['icon'] ?>"></i> <?= esc($qa['label']) ?></a></li>
            <?php endforeach; ?>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item d-flex align-items-center gap-2 text-muted" href="<?= site_url('bookings') ?>?status=Pending"><i class="bi bi-check2-circle"></i> Confirm a Booking…</a></li>
          </ul>
        </div>
        <?php endif; ?>
        <?php
          // Hide the bell for admins — owner opted out of notifications for
          // admin roles. Master switch (\App\Libraries\Notify::isEnabled) still
          // hides it for everyone when off.
          $viewerId    = (int) ($currentUser['id'] ?? 0);
          $isAdminView = $viewerId > 0 && \App\Libraries\Notify::isAdminUser($viewerId);
          $showBell    = \App\Libraries\Notify::isEnabled() && !$isAdminView;
        ?>
        <?php if ($showBell): ?>
        <div class="tpt-bell" style="position:relative;">
          <button id="tptBellBtn" class="btn btn-sm btn-light" type="button" aria-label="Notifications" style="position:relative;">
            <i class="bi bi-bell"></i>
            <span id="tptBellBadge" class="badge bg-danger" style="position:absolute;top:-4px;right:-4px;font-size:.6rem;padding:.18em .35em;border-radius:99px;display:none;">0</span>
          </button>
          <div id="tptBellMenu" style="display:none;position:absolute;right:0;top:calc(100% + 6px);background:#fff;border:1px solid #e3e7ee;border-radius:10px;box-shadow:0 6px 24px rgba(15,23,42,.12);min-width:280px;max-width:340px;z-index:1000;padding:.4rem 0;">
            <div style="padding:.65rem .9rem;border-bottom:1px solid #f1f3f5;font-weight:600;font-size:.85rem;">Notifications</div>
            <div id="tptBellList" style="max-height:360px;overflow-y:auto;padding:.25rem 0;"></div>
            <div id="tptBellEmpty" style="padding:1rem;text-align:center;color:#9ca3af;font-size:.85rem;display:none;">All caught up ✓</div>
            <a href="<?= site_url('notifications') ?>" style="display:block;padding:.55rem .9rem;border-top:1px solid #f1f3f5;text-align:center;font-size:.82rem;color:#3b82f6;text-decoration:none;">View all notifications</a>
          </div>
        </div>
        <?php endif; ?>
        <a href="<?= site_url('profile') ?>" class="d-none d-md-inline text-muted text-decoration-none" style="font-size:.85rem;" title="My profile + 2FA">
          <i class="bi bi-person-circle"></i> <?= esc($currentUser['name'] ?? '') ?>
        </a>
        <a href="<?= site_url('logout') ?>" class="btn btn-sm btn-light" aria-label="Sign out">
          <i class="bi bi-box-arrow-right"></i> <span class="d-none d-md-inline">Sign out</span>
        </a>
      </div>
      <script>
        (function () {
          const btn = document.getElementById('tptBellBtn');
          const menu = document.getElementById('tptBellMenu');
          const badge = document.getElementById('tptBellBadge');
          const list = document.getElementById('tptBellList');
          const empty = document.getElementById('tptBellEmpty');
          if (!btn) return;
          let lastFetched = 0;
          async function refresh() {
            try {
              const r = await fetch('<?= site_url('_notifications/counts') ?>', { credentials:'same-origin' });
              if (!r.ok) return;
              const j = await r.json();
              if (!j.ok) return;
              const n = j.total || 0;
              if (n > 0) { badge.style.display = 'inline-block'; badge.textContent = n > 9 ? '9+' : String(n); }
              else { badge.style.display = 'none'; }
              list.innerHTML = '';
              (j.items || []).forEach(it => {
                const a = document.createElement('a');
                a.href = it.url; a.style.cssText = 'display:flex;align-items:center;gap:.6rem;padding:.55rem .9rem;color:#0f172a;text-decoration:none;font-size:.88rem;border-bottom:1px solid #f7f9fc;';
                a.onmouseenter = () => a.style.background = '#f4f6fa';
                a.onmouseleave = () => a.style.background = '';
                a.innerHTML = '<i class="bi bi-' + (it.icon || 'circle') + '" style="color:#6b7280;font-size:1rem;"></i>'
                            + '<span style="flex:1;">' + it.label + '</span>'
                            + '<span class="badge bg-danger">' + it.n + '</span>';
                list.appendChild(a);
              });
              empty.style.display = (j.items && j.items.length > 0) ? 'none' : 'block';
              lastFetched = Date.now();
            } catch (e) {}
          }
          btn.addEventListener('click', (e) => {
            e.stopPropagation();
            const shown = menu.style.display !== 'none';
            menu.style.display = shown ? 'none' : 'block';
            if (!shown && Date.now() - lastFetched > 5000) refresh();
          });
          document.addEventListener('click', (e) => { if (!e.target.closest('.tpt-bell')) menu.style.display = 'none'; });
          refresh();
          // Poll every 20s when the tab is visible; pause when hidden to save server load.
          let pollTimer = null;
          function startPolling() { if (!pollTimer) pollTimer = setInterval(refresh, 20000); }
          function stopPolling()  { if (pollTimer) { clearInterval(pollTimer); pollTimer = null; } }
          startPolling();
          document.addEventListener('visibilitychange', () => {
            if (document.hidden) { stopPolling(); }
            else { refresh(); startPolling(); }
          });
        })();
      </script>
    </div>

    <?php if ($auth->isImpersonating()): ?>
      <div style="background:#2b1d3d;color:#fff;padding:.5rem 1rem;font-size:.85rem;text-align:center;">
        <i class="bi bi-person-arms-up"></i>
        Impersonating <strong><?= esc($currentUser['name'] ?? '') ?></strong> ·
        <form method="post" action="<?= site_url('sys/impersonate/stop') ?>" class="d-inline" style="margin:0;">
          <?= csrf_field() ?>
          <button type="submit" style="background:none;border:0;color:#fff;text-decoration:underline;cursor:pointer;font:inherit;">Return to super-admin</button>
        </form>
      </div>
    <?php endif; ?>

    <main id="tpt-main-content" class="tpt-content" role="main" tabindex="-1">
      <?php $flashSuccess = session()->getFlashdata('success'); ?>
      <?php $flashError   = session()->getFlashdata('error'); ?>
      <?php if ($flashSuccess): ?>
        <div class="alert alert-success mb-3" role="status"><?= esc($flashSuccess) ?></div>
      <?php endif; ?>
      <?php if ($flashError): ?>
        <div class="alert alert-danger mb-3" role="alert"><?= esc($flashError) ?></div>
      <?php endif; ?>

      <?= view($viewFile, array_diff_key(get_defined_vars(), array_flip(['viewFile','menu','appName','flashSuccess','flashError','activeSectionKey']))) ?>
    </main>
  </div>
</div>

<!-- ─────────────────────────── Universal search palette ─────────────────────────── -->
<div id="tptSearchModal" style="display:none;position:fixed;inset:0;z-index:9000;background:rgba(15,23,42,.55);align-items:flex-start;justify-content:center;padding-top:8vh;backdrop-filter:blur(2px);">
  <div style="background:#fff;border-radius:14px;width:92%;max-width:640px;box-shadow:0 24px 60px rgba(15,23,42,.3);overflow:hidden;display:flex;flex-direction:column;max-height:80vh;">
    <div style="display:flex;align-items:center;gap:.6rem;padding:.85rem 1rem;border-bottom:1px solid #f1f3f5;">
      <i class="bi bi-search" style="color:#9ca3af;"></i>
      <input id="tptSearchInput" type="text" autocomplete="off" placeholder="Search clients, vendors, trips, bookings, invoices, leads, RFQs…" style="flex:1;border:0;outline:0;font:inherit;font-size:1rem;color:#0f172a;background:transparent;">
      <kbd style="background:#f3f4f6;border:1px solid #e5e7eb;border-radius:4px;padding:0 .35em;font-size:.7em;color:#6b7280;">Esc</kbd>
    </div>
    <div id="tptSearchResults" style="overflow-y:auto;flex:1;padding:.25rem 0;">
      <div id="tptSearchHint" style="padding:1.5rem 1rem;text-align:center;color:#9ca3af;font-size:.88rem;">Start typing — 2+ characters.</div>
    </div>
  </div>
</div>
<style>
  #tptSearchModal .hit { display:flex; align-items:center; gap:.7rem; padding:.6rem .9rem; cursor:pointer; color:#0f172a; text-decoration:none; border-bottom:1px solid #f7f9fc; }
  #tptSearchModal .hit:hover, #tptSearchModal .hit.active { background:#f4f6fa; }
  #tptSearchModal .hit .ic { width:32px; height:32px; border-radius:8px; background:#e0e7ff; color:#3730a3; display:inline-flex; align-items:center; justify-content:center; font-size:1rem; flex-shrink:0; }
  #tptSearchModal .hit .body { flex:1; min-width:0; }
  #tptSearchModal .hit .body .lbl { font-weight:600; font-size:.92rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
  #tptSearchModal .hit .body .sub { font-size:.78rem; color:#6b7280; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
  #tptSearchModal .hit .badge-mini { font-size:.66rem; background:#e5e7eb; color:#374151; padding:.15rem .45rem; border-radius:999px; font-weight:600; }
  #tptSearchModal .type-header { padding:.45rem .9rem .25rem; font-size:.66rem; font-weight:700; text-transform:uppercase; letter-spacing:.06em; color:#9ca3af; }
</style>
<script>
(function () {
  const SEARCH_URL = '<?= site_url('_search') ?>';
  const modal  = document.getElementById('tptSearchModal');
  const input  = document.getElementById('tptSearchInput');
  const results= document.getElementById('tptSearchResults');
  const hint   = document.getElementById('tptSearchHint');
  let debounce = null;
  let active   = -1;
  let currentHits = [];

  function open() {
    modal.style.display = 'flex';
    setTimeout(() => input.focus(), 30);
  }
  function close() { modal.style.display = 'none'; }

  document.getElementById('tptSearchBtn')?.addEventListener('click', open);
  document.getElementById('tptSearchBtnMobile')?.addEventListener('click', open);
  document.addEventListener('keydown', function (e) {
    if ((e.ctrlKey || e.metaKey) && (e.key === 'k' || e.key === 'K')) { e.preventDefault(); open(); }
    if (e.key === 'Escape' && modal.style.display !== 'none') close();
  });
  modal.addEventListener('click', function (e) { if (e.target === modal) close(); });

  function renderHits(hits) {
    currentHits = hits;
    if (!hits.length) {
      results.innerHTML = '<div style="padding:1.5rem 1rem;text-align:center;color:#9ca3af;font-size:.88rem;">No matches.</div>';
      return;
    }
    let html = '';
    const grouped = {};
    hits.forEach(h => { (grouped[h.type] = grouped[h.type] || []).push(h); });
    Object.keys(grouped).forEach(t => {
      html += '<div class="type-header">' + t + '</div>';
      grouped[t].forEach(h => {
        html += '<a class="hit" href="' + h.url + '">'
              + '<span class="ic"><i class="bi bi-' + (h.icon || 'circle') + '"></i></span>'
              + '<span class="body"><span class="lbl">' + escapeHtml(h.label) + '</span>'
              + '<span class="sub">' + escapeHtml(h.sub || '') + '</span></span>'
              + (h.badge ? '<span class="badge-mini">' + escapeHtml(h.badge) + '</span>' : '')
              + '</a>';
      });
    });
    results.innerHTML = html;
    active = -1;
  }
  function escapeHtml(s) { return String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c])); }

  input.addEventListener('input', function () {
    const q = input.value.trim();
    clearTimeout(debounce);
    if (q.length < 2) {
      results.innerHTML = '<div style="padding:1.5rem 1rem;text-align:center;color:#9ca3af;font-size:.88rem;">Start typing — 2+ characters.</div>';
      return;
    }
    debounce = setTimeout(async function () {
      try {
        const r = await fetch(SEARCH_URL + '?q=' + encodeURIComponent(q), { credentials:'same-origin' });
        if (!r.ok) return;
        const j = await r.json();
        if (!j.ok) return;
        renderHits(j.hits || []);
      } catch (e) {}
    }, 180);
  });

  // Arrow-key navigation
  input.addEventListener('keydown', function (e) {
    const items = results.querySelectorAll('.hit');
    if (!items.length) return;
    if (e.key === 'ArrowDown') { e.preventDefault(); active = (active + 1) % items.length; }
    else if (e.key === 'ArrowUp') { e.preventDefault(); active = (active - 1 + items.length) % items.length; }
    else if (e.key === 'Enter') { e.preventDefault(); if (active >= 0) items[active].click(); else if (items[0]) items[0].click(); return; }
    else { return; }
    items.forEach((el, i) => el.classList.toggle('active', i === active));
    if (items[active]) items[active].scrollIntoView({ block: 'nearest' });
  });
})();
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>window.TPT_BASE_URL = '<?= rtrim(site_url(), '/') ?>/';</script>
<script src="<?= base_url('assets/js/app.js') ?>"></script>
<script src="<?= base_url('assets/js/tpt-city.js') ?>"></script>
<script src="<?= base_url('assets/js/tpt-saved-views.js') ?>"></script>
<script src="<?= base_url('assets/js/tpt-inline-edit.js') ?>"></script>
<script src="<?= base_url('assets/js/tpt-columns.js') ?>?v=2026-08-03"></script>
<!-- Tom Select — auto-enhances every <select> that carries data-tpt-search
     (or has 6+ options) into a searchable, filter-as-you-type dropdown.
     Text typed inside filters options client-side, case-insensitive, on both
     the visible label AND any data-search-terms attribute we sprinkle on
     <option>s for extra hooks (mobile, GSTIN, city, etc.). -->
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
<script>
(function () {
  if (typeof TomSelect === 'undefined') return;
  // Selects we NEVER want to enhance (framework widgets like filters or bulk-action pickers).
  var skip = new Set(['dashboard-widgets-select','bulk-action-select']);
  document.querySelectorAll('select').forEach(function (sel) {
    if (sel.tomselect) return;
    if (sel.multiple) return;                                          // leave multi-selects alone
    if (sel.classList.contains('tpt-no-search')) return;                // explicit opt-out
    var wantSearch = sel.hasAttribute('data-tpt-search')
                  || sel.classList.contains('tpt-search')
                  || sel.classList.contains('party-select')            // consignor / consignee
                  || sel.classList.contains('party-select-trip')
                  || sel.options.length >= 6;                          // any long list gets it automatically
    if (!wantSearch) return;
    if (skip.has(sel.id)) return;
    try {
      new TomSelect(sel, {
        create: false,
        allowEmptyOption: true,
        maxOptions: 500,
        searchField: ['text','value'],
        // Fire the original element's `change` handlers even when Tom-Select
        // is the one flipping the value — needed for the client autofill JS
        // on the booking form. Tom Select already dispatches this natively
        // but we defensively re-dispatch to be sure.
        onChange: function () { sel.dispatchEvent(new Event('change', { bubbles: true })); }
      });
    } catch (e) { /* ignore init errors, fall back to native <select> */ }
  });
})();
</script>
</body>
</html>
