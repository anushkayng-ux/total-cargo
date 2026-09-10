<?php
/** @var \App\Libraries\Auth $auth */
$appName = env('tpt.appName', 'TPT Aggregator');
$nav = [
    ['label' => 'Diagnostics',    'url' => 'sys',                 'icon' => 'speedometer2'],
    ['label' => 'Feature Flags',  'url' => 'sys/feature-flags',   'icon' => 'toggles'],
    ['label' => 'SQL Console',    'url' => 'sys/sql',             'icon' => 'terminal'],
    ['label' => 'Email Queue',    'url' => 'sys/email-queue',     'icon' => 'envelope-paper'],
    ['label' => 'Migrations',     'url' => 'sys/migrations',      'icon' => 'database-gear'],
    ['label' => 'Impersonate',    'url' => 'sys/impersonate',     'icon' => 'person-arms-up'],
    ['label' => 'Audit',          'url' => 'sys/audit',           'icon' => 'shield-shaded'],
    ['label' => 'Profile / 2FA',  'url' => 'sys/profile',         'icon' => 'shield-lock'],
];
$current = trim((string) service('request')->getUri()->getPath(), '/');
$base    = trim(parse_url(base_url(), PHP_URL_PATH) ?? '', '/');
if ($base && strpos($current, $base) === 0) {
    $current = trim(substr($current, strlen($base)), '/');
}
$isActive = function (string $url) use ($current): string {
    $url = trim($url, '/');
    if ($url === $current) return 'active';
    if ($url !== '' && strpos($current, $url . '/') === 0) return 'active';
    return '';
};
?><!DOCTYPE html>
<html lang="en"><head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="<?= csrf_hash() ?>">
<meta name="robots" content="noindex,nofollow">
<title><?= esc($pageTitle ?? 'Sys') ?> · <?= esc($appName) ?> · SUPER</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=JetBrains+Mono&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<style>
  body { font-family: 'Poppins', sans-serif; background:#0e1218; color:#e6e6e6; margin:0; }
  .sys-top { background:#1b1f27; padding:.6rem 1rem; border-bottom:2px solid #c1272d; display:flex; align-items:center; gap:1rem; }
  .sys-top .brand { font-weight:700; }
  .sys-top .brand small { opacity:.7; font-weight:400; margin-left:.4rem; font-size:.7rem; color:#ff7a7a; }
  .sys-top .me { margin-left:auto; font-size:.85rem; opacity:.85; }
  .sys-top a { color:#fff; opacity:.85; text-decoration:none; }
  .sys-top a:hover { opacity:1; }
  .sys-nav { background:#13171e; padding:.4rem 1rem; display:flex; gap:.4rem; flex-wrap:wrap; overflow-x:auto; border-bottom:1px solid #1f242c; }
  .sys-nav a { color:#bbb; text-decoration:none; padding:.4rem .8rem; border-radius:6px; font-size:.85rem; white-space:nowrap; }
  .sys-nav a.active { background:#c1272d; color:#fff; }
  .sys-nav a:hover { background:#222831; color:#fff; }
  .sys-nav a.active:hover { background:#c1272d; }
  .sys-content { padding:1.25rem; max-width:1400px; margin:0 auto; }
  .sys-content .card { background:#1b1f27; border:1px solid #232831; color:#e6e6e6; }
  .sys-content .card-header { background:#13171e; border-bottom:1px solid #232831; color:#bbb; font-weight:600; }
  .sys-content code, .sys-content pre, .sys-content .mono { font-family:'JetBrains Mono', monospace; }
  .sys-content .table { color:#ddd; }
  .sys-content .table thead { background:#13171e; }
  .sys-content .table thead th { color:#888; border-bottom-color:#232831; font-weight:500; }
  .sys-content .table td, .sys-content .table th { border-color:#232831; }
  .sys-content .form-control, .sys-content .form-select, .sys-content textarea {
    background:#0e1218; color:#e6e6e6; border:1px solid #2a3140;
  }
  .sys-content .btn-primary { background:#c1272d; border-color:#c1272d; }
  .sys-content .btn-primary:hover { background:#a01d22; border-color:#a01d22; }
  .danger-banner { background:#3a0d0f; color:#ffb3b3; padding:.5rem 1rem; font-size:.85rem; text-align:center; border-bottom:1px solid #5a1a1d; }
  .imp-banner { background:#2b1d3d; color:#cdb6f3; padding:.4rem 1rem; font-size:.85rem; text-align:center; border-bottom:1px solid #4a3370; }
  .stat-row { display:flex; gap:1rem; flex-wrap:wrap; }
  .stat-row .item { background:#13171e; border:1px solid #232831; padding:.6rem .9rem; border-radius:6px; min-width:160px; }
  .stat-row .item .l { font-size:.7rem; color:#888; text-transform:uppercase; letter-spacing:.04em; }
  .stat-row .item .v { font-size:1.1rem; font-weight:600; }
  pre.console { background:#0a0d12; border:1px solid #232831; padding:1rem; max-height:60vh; overflow:auto; color:#7fdbff; font-size:.85rem; }
  .badge-sys { background:#c1272d; color:#fff; padding:.18em .5em; border-radius:3px; font-size:.7rem; font-weight:600; }
</style>
</head><body>

<div class="danger-banner">
  <i class="bi bi-shield-exclamation"></i>
  SUPER-ADMIN MODE — every action on this console is recorded in the immutable Sys Audit log.
</div>

<?php if ($auth->isImpersonating()): ?>
<div class="imp-banner">
  Currently impersonating <?= esc($currentUser['name']) ?> ·
  <form method="post" action="<?= site_url('sys/impersonate/stop') ?>" class="d-inline">
    <?= csrf_field() ?>
    <button type="submit" style="background:none;border:0;color:#cdb6f3;text-decoration:underline;cursor:pointer;font-size:inherit;">Return to super-admin</button>
  </form>
</div>
<?php endif; ?>

<div class="sys-top">
  <div class="brand">
    <i class="bi bi-shield-lock"></i> <?= esc($appName) ?>
    <small>Sys Console</small>
  </div>
  <div class="me">
    <i class="bi bi-person-badge"></i> <?= esc($currentUser['name'] ?? 'Super') ?>
    &middot; <?= esc($currentUser['email'] ?? '') ?>
    <a href="<?= site_url('logout') ?>" class="ms-3"><i class="bi bi-box-arrow-right"></i> Sign out</a>
  </div>
</div>

<nav class="sys-nav" aria-label="Sys sections">
  <?php foreach ($nav as $item): ?>
    <a href="<?= site_url($item['url']) ?>" class="<?= $isActive($item['url']) ?>">
      <i class="bi bi-<?= esc($item['icon']) ?>"></i> <?= esc($item['label']) ?>
    </a>
  <?php endforeach; ?>
  <a href="<?= site_url('dashboard') ?>" class="ms-auto" style="color:#7fdbff;">
    <i class="bi bi-arrow-up-right-square"></i> Tenant app →
  </a>
</nav>

<div class="sys-content">
  <?php $flashSuccess = session()->getFlashdata('success'); $flashError = session()->getFlashdata('error'); ?>
  <?php if ($flashSuccess): ?><div class="alert alert-success"><?= esc($flashSuccess) ?></div><?php endif; ?>
  <?php if ($flashError): ?>  <div class="alert alert-danger"><?= esc($flashError) ?></div><?php endif; ?>
  <?= view($viewFile, array_diff_key(get_defined_vars(), array_flip(['viewFile','nav','appName','flashSuccess','flashError','isActive','current','base']))) ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body></html>
