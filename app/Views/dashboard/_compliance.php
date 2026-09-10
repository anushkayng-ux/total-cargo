<?php
/** @var array $compliance  output of ComplianceTracker::summary() */
$counts = $compliance['counts'] ?? ['expired'=>0,'critical'=>0,'warning'=>0,'upcoming'=>0];
$rows   = $compliance['rows']   ?? [];
$total  = $compliance['total']  ?? 0;
$badge  = function ($bucket, $days): string {
    if ($bucket === 'expired')  return '<span class="badge bg-danger">Expired ' . abs($days) . 'd</span>';
    if ($bucket === 'critical') return '<span class="badge bg-danger">' . $days . 'd left</span>';
    if ($bucket === 'warning')  return '<span class="badge bg-warning text-dark">' . $days . 'd</span>';
    return '<span class="badge bg-light text-dark">' . $days . 'd</span>';
};
?>
<div class="card mb-3">
  <div class="card-header d-flex flex-wrap align-items-center gap-2">
    <span><i class="bi bi-shield-exclamation"></i> Expiring documents</span>
    <small class="text-muted">— next 60 days</small>
    <span class="ms-auto d-flex gap-2 flex-wrap">
      <?php if (($counts['expired'] ?? 0) > 0): ?>
        <span class="badge bg-danger"><?= $counts['expired'] ?> expired</span>
      <?php endif; ?>
      <?php if (($counts['critical'] ?? 0) > 0): ?>
        <span class="badge bg-danger"><?= $counts['critical'] ?> critical</span>
      <?php endif; ?>
      <?php if (($counts['warning'] ?? 0) > 0): ?>
        <span class="badge bg-warning text-dark"><?= $counts['warning'] ?> in 30d</span>
      <?php endif; ?>
      <?php if (($counts['upcoming'] ?? 0) > 0): ?>
        <span class="badge bg-light text-dark"><?= $counts['upcoming'] ?> in 60d</span>
      <?php endif; ?>
    </span>
  </div>
  <?php if ($total === 0): ?>
    <div class="card-body text-muted small">All drivers + vehicles are compliant for the next 60 days. 🎉</div>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table table-sm mb-0">
        <thead class="small text-muted"><tr>
          <th style="width:1%;"></th>
          <th>Document</th>
          <th>Expiry</th>
          <th class="text-end"></th>
        </tr></thead>
        <tbody>
          <?php foreach (array_slice($rows, 0, 8) as $r): ?>
            <tr>
              <td>
                <?php if ($r['kind'] === 'driver'): ?>
                  <i class="bi bi-person-badge text-primary"></i>
                <?php else: ?>
                  <i class="bi bi-truck text-secondary"></i>
                <?php endif; ?>
              </td>
              <td>
                <a href="<?= esc($r['url']) ?>" class="text-decoration-none">
                  <strong><?= esc($r['entity_label']) ?></strong>
                </a>
                <span class="text-muted small">· <?= esc($r['label']) ?></span>
              </td>
              <td><?= esc(date('d-m-Y', strtotime($r['expiry']))) ?></td>
              <td class="text-end"><?= $badge($r['bucket'], $r['days']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php if ($total > 8): ?>
      <div class="card-footer text-end small">
        <a href="<?= site_url('compliance') ?>">View all <?= $total ?> →</a>
      </div>
    <?php endif; ?>
  <?php endif; ?>
</div>
