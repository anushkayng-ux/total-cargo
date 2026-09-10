<?php
$counts = $summary['counts'] ?? [];
$badge  = function ($bucket, $days): string {
    if ($bucket === 'expired')  return '<span class="badge bg-danger">Expired ' . abs($days) . 'd</span>';
    if ($bucket === 'critical') return '<span class="badge bg-danger">' . $days . 'd left</span>';
    if ($bucket === 'warning')  return '<span class="badge bg-warning text-dark">' . $days . 'd</span>';
    return '<span class="badge bg-light text-dark">' . $days . 'd</span>';
};
?>
<div class="d-flex align-items-center mb-3 gap-2 flex-wrap">
  <h5 class="m-0"><i class="bi bi-shield-exclamation"></i> <?= esc($pageTitle) ?></h5>
  <small class="text-muted">— horizon <?= $horizon ?> days</small>
  <a class="ms-auto btn btn-sm btn-light" href="<?= site_url('dashboard') ?>"><i class="bi bi-arrow-left"></i> Dashboard</a>
</div>

<div class="row g-2 mb-3">
  <?php foreach (['expired','critical','warning','upcoming'] as $b):
    $active = $bucket === $b;
    $href   = site_url('compliance') . '?' . http_build_query(array_filter(['horizon' => $horizon, 'kind' => $kind, 'bucket' => $active ? null : $b]));
  ?>
    <div class="col-6 col-md-3">
      <a href="<?= $href ?>" class="stat d-block text-decoration-none <?= $active ? '' : '' ?>"
         style="<?= $active ? 'border-color:#3730a3;box-shadow:0 0 0 2px #c7d2fe inset;' : '' ?>">
        <span class="label"><?= ucfirst($b) ?></span>
        <span class="value"><?= (int) ($counts[$b] ?? 0) ?></span>
      </a>
    </div>
  <?php endforeach; ?>
</div>

<form method="get" class="card mb-3"><div class="card-body p-3">
  <div class="row g-2 align-items-end">
    <div class="col-md-3">
      <label class="form-label small text-uppercase text-muted">Type</label>
      <select class="form-select form-select-sm" name="kind">
        <option value="">All</option>
        <option value="driver"   <?= $kind === 'driver' ? 'selected' : '' ?>>Drivers</option>
        <option value="vehicle"  <?= $kind === 'vehicle' ? 'selected' : '' ?>>Vehicles</option>
      </select>
    </div>
    <div class="col-md-3">
      <label class="form-label small text-uppercase text-muted">Horizon (days)</label>
      <select class="form-select form-select-sm" name="horizon">
        <?php foreach ([30, 60, 90, 180, 365] as $h): ?>
          <option value="<?= $h ?>" <?= $horizon === $h ? 'selected' : '' ?>><?= $h ?> days</option>
        <?php endforeach; ?>
      </select>
    </div>
    <?php if ($bucket): ?>
      <input type="hidden" name="bucket" value="<?= esc($bucket) ?>">
    <?php endif; ?>
    <div class="col-md-2"><button class="btn btn-sm btn-primary"><i class="bi bi-funnel"></i> Apply</button></div>
  </div>
</div></form>

<div class="card">
  <div class="table-responsive">
    <table class="table mobile-cards mb-0">
      <thead><tr><th>Type</th><th>Entity</th><th>Document</th><th>Expiry</th><th class="text-end">Status</th><th class="text-end"></th></tr></thead>
      <tbody>
        <?php if (empty($rows)): ?>
          <tr><td colspan="6" class="text-center text-muted py-3">No documents match these filters.</td></tr>
        <?php endif; ?>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td data-label="Type">
              <?php if ($r['kind'] === 'driver'): ?>
                <i class="bi bi-person-badge text-primary"></i> Driver
              <?php else: ?>
                <i class="bi bi-truck text-secondary"></i> Vehicle
              <?php endif; ?>
            </td>
            <td data-label="Entity"><strong><?= esc($r['entity_label']) ?></strong></td>
            <td data-label="Document"><?= esc($r['label']) ?><?php if ($r['doc']): ?> <code class="text-muted small"><?= esc($r['doc']) ?></code><?php endif; ?></td>
            <td data-label="Expiry"><?= esc(date('d-m-Y', strtotime($r['expiry']))) ?></td>
            <td class="text-end"><?= $badge($r['bucket'], $r['days']) ?></td>
            <td class="text-end"><a class="btn btn-sm btn-light" href="<?= esc($r['url']) ?>"><i class="bi bi-pencil"></i> Edit</a></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
