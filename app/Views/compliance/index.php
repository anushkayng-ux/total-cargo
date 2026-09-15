<?php
$counts = $summary['counts'] ?? [];
$badge  = function ($bucket, $days): string {
    if ($bucket === 'expired')  return '<span class="badge bg-danger">Expired ' . abs($days) . 'd</span>';
    if ($bucket === 'critical') return '<span class="badge bg-danger">' . $days . 'd left</span>';
    if ($bucket === 'warning')  return '<span class="badge bg-warning text-dark">' . $days . 'd</span>';
    return '<span class="badge bg-light text-dark">' . $days . 'd</span>';
};

$extra = '<form method="get" class="d-flex align-items-center gap-2 m-0">'
    . '<select class="form-select form-select-sm" style="width:auto;" name="kind"><option value="">All Types</option>'
    . '<option value="driver"' . ($kind === 'driver' ? ' selected' : '') . '>Drivers</option>'
    . '<option value="vehicle"' . ($kind === 'vehicle' ? ' selected' : '') . '>Vehicles</option>'
    . '</select><select class="form-select form-select-sm" style="width:auto;" name="horizon">';
foreach ([30, 60, 90, 180, 365] as $h) {
    $extra .= '<option value="' . $h . '"' . ($horizon === $h ? ' selected' : '') . '>' . $h . ' days</option>';
}
$extra .= '</select>';
if ($bucket) $extra .= '<input type="hidden" name="bucket" value="' . esc($bucket) . '">';
$extra .= '<button class="btn btn-sm btn-outline-dark">Apply</button></form>';

echo tpt_toolbar([
    'close_href' => site_url('dashboard'),
    'extra'      => $extra,
    'auth'       => $auth,
]);
?>
<div class="tabs">
  <div class="tab active">Compliance Watchlist</div>
  <div class="spacer"></div>
  <div class="recordnav">Horizon <?= $horizon ?> days</div>
</div>

<div class="formwrap" style="flex:0 0 auto;">
  <div class="row g-2">
    <?php foreach (['expired','critical','warning','upcoming'] as $b):
      $active = $bucket === $b;
      $href   = site_url('compliance') . '?' . http_build_query(array_filter(['horizon' => $horizon, 'kind' => $kind, 'bucket' => $active ? null : $b]));
    ?>
      <div class="col-6 col-md-3">
        <a href="<?= $href ?>" class="stat d-block text-decoration-none"
           style="<?= $active ? 'border-color:#3730a3;box-shadow:0 0 0 2px #c7d2fe inset;' : '' ?>">
          <span class="label"><?= ucfirst($b) ?></span>
          <span class="value"><?= (int) ($counts[$b] ?? 0) ?></span>
        </a>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<div class="gridwrap">
  <div class="table-responsive">
    <table class="table grid mobile-cards mb-0">
      <thead><tr><th>Type</th><th>Entity</th><th>Document</th><th>Expiry</th><th class="text-end">Status</th><th class="text-end"></th></tr></thead>
      <tbody>
        <?php if (empty($rows)): ?>
          <tr><td colspan="6" class="text-center text-muted py-3">No documents match these filters.</td></tr>
        <?php endif; ?>
        <?php foreach ($rows as $r): ?>
          <tr class="row-link" data-href="<?= esc($r['url']) ?>">
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
