<?php
$kpis = [
    ['label' => 'Booking approval',          'desc' => 'Booking created → approved',          'data' => $bookingApproval,  'target_h' => 4],
    ['label' => 'Vehicle placement',         'desc' => 'Booking approved → vehicle placed',   'data' => $vehiclePlacement, 'target_h' => 24],
    ['label' => 'Transit',                   'desc' => 'Dispatched → delivered',              'data' => $transit,          'target_h' => 72],
    ['label' => 'POD collection',            'desc' => 'Delivered → POD received',            'data' => $podCollection,    'target_h' => 48],
    ['label' => 'Invoice issuance',          'desc' => 'POD received → invoice raised',       'data' => $invoiceIssuance,  'target_h' => 24],
];

$fmt = function ($v) {
    if ($v === null) return '—';
    if ($v >= 24)  return number_format($v / 24, 1) . ' d';
    return number_format((float) $v, 1) . ' h';
};
$ratingFor = function ($p50, $target) {
    if ($p50 === null) return ['—', 'badge-pending'];
    if ($p50 <= $target)         return ['On track', 'badge-paid'];
    if ($p50 <= $target * 1.5)   return ['At risk',  'badge-issued'];
    return ['Off track', 'badge-cancelled'];
};
?>
<h5 class="mb-3"><?= esc($pageTitle) ?></h5>

<form method="get" class="row g-2 mb-3 align-items-end">
  <div class="col-6 col-md-3"><label class="form-label" style="font-size:.85rem;">From</label><input type="date" class="form-control form-control-sm" name="from" value="<?= esc($from) ?>"></div>
  <div class="col-6 col-md-3"><label class="form-label" style="font-size:.85rem;">To</label><input type="date" class="form-control form-control-sm" name="to" value="<?= esc($to) ?>"></div>
  <div class="col-md-2"><button class="btn btn-sm btn-light w-100">Apply</button></div>
  <div class="col-md-4 text-end text-muted" style="font-size:.85rem;">Stats over the selected window. P50 = median, P90 = 90th percentile.</div>
</form>

<div class="row g-3 mb-3">
  <?php foreach ($kpis as $k): [$label, $cls] = $ratingFor($k['data']['p50_h'] ?? null, $k['target_h']); ?>
  <div class="col-md-6 col-lg-4">
    <div class="card h-100">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div style="font-weight:600;"><?= esc($k['label']) ?></div>
            <div class="text-muted" style="font-size:.78rem;"><?= esc($k['desc']) ?></div>
          </div>
          <span class="badge-status <?= esc($cls) ?>"><?= esc($label) ?></span>
        </div>
        <div class="mt-3 d-flex gap-3 flex-wrap" style="font-size:.92rem;">
          <div><div class="text-muted" style="font-size:.72rem;">SAMPLES</div><strong><?= (int) ($k['data']['count'] ?? 0) ?></strong></div>
          <div><div class="text-muted" style="font-size:.72rem;">AVG</div><strong><?= $fmt($k['data']['avg_h'] ?? null) ?></strong></div>
          <div><div class="text-muted" style="font-size:.72rem;">P50</div><strong><?= $fmt($k['data']['p50_h'] ?? null) ?></strong></div>
          <div><div class="text-muted" style="font-size:.72rem;">P90</div><strong><?= $fmt($k['data']['p90_h'] ?? null) ?></strong></div>
          <div><div class="text-muted" style="font-size:.72rem;">TARGET</div><strong><?= $fmt($k['target_h']) ?></strong></div>
        </div>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<div class="card">
  <div class="card-header">Per-user scorecard</div>
  <div class="table-responsive">
    <table class="table table-sm align-middle mb-0">
      <thead>
        <tr>
          <th>User</th>
          <th class="text-end">Leads</th>
          <th class="text-end">Won</th>
          <th class="text-end">Lost</th>
          <th class="text-end">Conv %</th>
          <th class="text-end">Bookings</th>
          <th class="text-end">Avg approve TAT</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($perUser)): ?>
          <tr><td colspan="7" class="text-center text-muted py-3">No activity in this window.</td></tr>
        <?php endif; ?>
        <?php foreach ($perUser as $u):
          $handled = (int) $u['leads_handled']; $won = (int) $u['leads_won'];
          $conv    = $handled > 0 ? round($won * 100 / $handled, 1) : null;
          $avgH    = $u['avg_approve_h'] !== null ? round((float) $u['avg_approve_h'], 1) : null;
        ?>
          <tr>
            <td><?= esc($u['name']) ?></td>
            <td class="text-end"><?= $handled ?></td>
            <td class="text-end"><?= $won ?></td>
            <td class="text-end"><?= (int) $u['leads_lost'] ?></td>
            <td class="text-end"><?= $conv === null ? '—' : $conv . '%' ?></td>
            <td class="text-end"><?= (int) $u['bookings'] ?></td>
            <td class="text-end"><?= $avgH === null ? '—' : $fmt($avgH) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
