<?php
/** @var \App\Libraries\ClientAuth $clientAuth */
$appName = env('tpt.appName', 'TPT Aggregator');
$role    = $clientAuth->role();
$client  = $clientAuth->client();

/* Portal nav — flat list of pages with optional permission gate (`gate`). */
$nav = [
    ['label' => 'Dashboard',        'url' => 'portal',           'icon' => 'speedometer2', 'gate' => null],
    ['label' => 'Calendar',         'url' => 'portal/calendar',  'icon' => 'calendar3',    'gate' => 'view_bookings'],
    ['label' => 'My Bookings',      'url' => 'portal/bookings',  'icon' => 'card-list',    'gate' => 'view_bookings'],
    ['label' => 'My Trips',         'url' => 'portal/trips',     'icon' => 'truck',        'gate' => 'view_trips'],
    ['label' => 'Invoices',         'url' => 'portal/invoices',  'icon' => 'receipt',      'gate' => 'view_invoices'],
    ['label' => 'Ledger',           'url' => 'portal/ledger',    'icon' => 'bank',         'gate' => 'view_ledger'],
    ['label' => 'Request a Truck',  'url' => 'portal/request',   'icon' => 'plus-circle',  'gate' => 'request_booking'],
    ['label' => 'Profile',          'url' => 'portal/profile',   'icon' => 'person-gear',  'gate' => 'manage_profile'],
];

/* Active-link detection (mirrors tpt_active()). */
$current = trim((string) service('request')->getUri()->getPath(), '/');
$base    = trim(parse_url(base_url(), PHP_URL_PATH) ?? '', '/');
if ($base && strpos($current, $base) === 0) $current = trim(substr($current, strlen($base)), '/');
$bestMatch = '';
foreach ($nav as $item) {
    $u = trim((string) $item['url'], '/');
    if ($u === '') continue;
    if ($u === $current || strpos($current, $u . '/') === 0) {
        if (strlen($u) > strlen($bestMatch)) $bestMatch = $u;
    }
}
$isActive = fn(string $u) => trim($u, '/') === $bestMatch ? 'active' : '';
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="csrf-token" content="<?= csrf_hash() ?>">
<meta name="robots" content="noindex,nofollow">
<title><?= esc($pageTitle ?? 'Client Portal') ?> · <?= esc($appName) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="<?= base_url('assets/css/app.css') ?>" rel="stylesheet">
<style>
  /* Portal-specific tweaks layered on top of the shared admin CSS */
  .kyc-banner { background:#fff8e1; color:#8a6d3b; padding:.7rem 1rem; border-bottom:1px solid #ffeeba; font-size:.88rem; }
  .kyc-banner i { margin-right:.3rem; }
  .portal-badge { font-size:.65rem; font-weight:600; background:#3730a3; color:#fff; padding:.15em .55em; border-radius:999px; margin-left:.4rem; vertical-align:middle; letter-spacing:.04em; }

  /* Stat tiles used on the portal dashboard */
  .stat-card {
    background:#fff; border:1px solid #e5e7eb; border-radius:10px;
    padding:1rem 1.1rem; display:flex; align-items:center; gap:.9rem;
    box-shadow:0 1px 2px rgba(15,23,42,.04);
    transition:transform .15s, box-shadow .15s;
  }
  .stat-card:hover { transform:translateY(-1px); box-shadow:0 10px 25px -10px rgba(15,23,42,.18); }
  .stat-card .stat-icon { flex:0 0 auto; width:44px; height:44px; border-radius:11px; display:inline-flex; align-items:center; justify-content:center; font-size:1.25rem; }
  .stat-card .stat-body { min-width:0; flex:1; }
  .stat-card .label { display:block; color:#6b7280; font-size:.72rem; text-transform:uppercase; letter-spacing:.06em; font-weight:500; }
  .stat-card .value { display:block; font-size:1.45rem; font-weight:600; line-height:1.15; margin-top:.15rem; }
  .stat-card .sub   { display:block; font-size:.72rem; color:#6b7280; margin-top:.1rem; }
  .stat-card .sub.danger { color:#b00020; }
  .stat-icon.blue   { background:#e0e7ff; color:#3730a3; }
  .stat-icon.green  { background:#d1fae5; color:#065f46; }
  .stat-icon.amber  { background:#fef3c7; color:#92400e; }
  .stat-icon.red    { background:#fee2e2; color:#991b1b; }
  .stat-icon.slate  { background:#e2e8f0; color:#1e293b; }

  /* Status pills */
  .badge-status { display:inline-block; font-size:.7rem; font-weight:500; padding:.22em .55em; border-radius:999px; line-height:1.4; white-space:nowrap; }
  .badge-pending   { background:#fff3cd; color:#856404; }
  .badge-approved, .badge-issued { background:#dbeafe; color:#1e40af; }
  .badge-paid, .badge-delivered, .badge-closed { background:#d1fae5; color:#065f46; }
  .badge-cancelled, .badge-overdue { background:#fee2e2; color:#991b1b; }
  .badge-transit, .badge-loading   { background:#e2e8f0; color:#334155; }

  /* "Mobile list" pattern — used on dashboard recent panels */
  .mobile-list { display:none; }
  .mobile-list .list-row { padding:.85rem 1.1rem; border-bottom:1px solid #e5e7eb; display:flex; gap:.8rem; align-items:flex-start; }
  .mobile-list .list-row:last-child { border-bottom:0; }
  .mobile-list .list-row .body { flex:1; min-width:0; }
  .mobile-list .list-row .title { font-weight:600; font-size:.92rem; }
  .mobile-list .list-row .sub   { font-size:.78rem; color:#6b7280; margin-top:.1rem; }
  .mobile-list .list-row .right { text-align:right; }
  .mobile-list .list-row .right .amt { font-weight:600; font-size:.92rem; }
  .mobile-list .empty { padding:1.5rem; text-align:center; color:#6b7280; font-size:.88rem; }

  @media (max-width:767.98px) {
    /* Swap dashboard tables for mobile lists */
    .swap-table-mobile .table-responsive { display:none; }
    .swap-table-mobile .mobile-list { display:block; }
  }
</style>
</head>
<body>
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
      <span class="portal-badge">PORTAL</span>
    </div>
    <ul class="tpt-nav">
      <?php foreach ($nav as $item): ?>
        <?php if (!empty($item['gate']) && !$clientAuth->can($item['gate'])) continue; ?>
        <li>
          <a href="<?= site_url($item['url']) ?>" class="tpt-single <?= $isActive($item['url']) ?>">
            <i class="bi bi-<?= esc($item['icon']) ?>"></i>
            <?= esc($item['label']) ?>
          </a>
        </li>
      <?php endforeach; ?>
    </ul>
  </aside>

  <div class="tpt-main">
    <div class="tpt-topbar">
      <button class="tpt-hamburger" data-toggle-sidebar aria-label="Menu">
        <i class="bi bi-list"></i>
      </button>
      <div class="tpt-topbar-title"><?= esc($pageTitle ?? '') ?></div>

      <div class="tpt-topbar-right">
        <a href="<?= site_url('portal/profile') ?>" class="d-none d-md-inline text-muted text-decoration-none" style="font-size:.85rem;" title="My profile">
          <i class="bi bi-person-circle"></i>
          <?= esc($currentUser['name'] ?? '') ?>
          <span class="text-muted" style="font-size:.72rem;">· <?= esc($role) ?></span>
        </a>
        <form method="post" action="<?= site_url('portal/logout') ?>" style="margin:0;">
          <?= csrf_field() ?>
          <button type="submit" class="btn btn-sm btn-light" aria-label="Sign out">
            <i class="bi bi-box-arrow-right"></i> <span class="d-none d-md-inline">Sign out</span>
          </button>
        </form>
      </div>
    </div>

    <?php if ($client && ($client['kyc_status'] ?? 'Pending') !== 'Verified'): ?>
      <div class="kyc-banner" role="status">
        <i class="bi bi-info-circle"></i>
        Your KYC is <strong><?= esc($client['kyc_status'] ?? 'Pending') ?></strong>.
        Booking requests will require staff review until KYC is verified.
      </div>
    <?php endif; ?>

    <main id="tpt-main-content" class="tpt-content" role="main" tabindex="-1">
      <?php $flashSuccess = session()->getFlashdata('success'); ?>
      <?php $flashError   = session()->getFlashdata('error');   ?>
      <?php if ($flashSuccess): ?>
        <div class="alert alert-success mb-3" role="status"><?= esc($flashSuccess) ?></div>
      <?php endif; ?>
      <?php if ($flashError): ?>
        <div class="alert alert-danger mb-3" role="alert"><?= esc($flashError) ?></div>
      <?php endif; ?>

      <?= view($viewFile, array_diff_key(get_defined_vars(), array_flip(['viewFile','nav','appName','flashSuccess','flashError','isActive','current','base','client','role','logo','bestMatch']))) ?>
    </main>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= base_url('assets/js/app.js') ?>"></script>
</body>
</html>
