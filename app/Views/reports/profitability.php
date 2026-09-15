<?php
$extra = '<form method="get" class="d-flex align-items-center gap-2 flex-wrap m-0" action="' . site_url('reports/profitability') . '">'
    . '<input class="form-control form-control-sm" style="width:140px;" type="date" name="from" value="' . esc($filters['from']) . '">'
    . '<input class="form-control form-control-sm" style="width:140px;" type="date" name="to" value="' . esc($filters['to']) . '">'
    . '<select class="form-select form-select-sm" name="client_id" style="width:auto;"><option value="">All Clients</option>';
foreach ($clients as $c) {
    $extra .= '<option value="' . (int) $c['id'] . '"' . ((int) $filters['client_id'] === (int) $c['id'] ? ' selected' : '') . '>' . esc($c['company_name']) . '</option>';
}
$extra .= '</select>'
    . '<select class="form-select form-select-sm" name="vendor_id" style="width:auto;"><option value="">All Vendors</option>';
foreach ($vendors as $v) {
    $extra .= '<option value="' . (int) $v['id'] . '"' . ((int) $filters['vendor_id'] === (int) $v['id'] ? ' selected' : '') . '>' . esc($v['company_name']) . '</option>';
}
$extra .= '</select>'
    . '<select class="form-select form-select-sm" name="status" style="width:auto;"><option value="">All Status</option>';
foreach ($statuses as $s) {
    $extra .= '<option value="' . esc($s) . '"' . ($filters['status'] === $s ? ' selected' : '') . '>' . esc($s) . '</option>';
}
$extra .= '</select>'
    . '<button class="btn btn-sm btn-outline-dark">Filter</button>'
    . '<a class="btn btn-sm btn-light" href="' . site_url('reports/profitability') . '">Reset</a>'
    . '</form>';
?>
<?= tpt_toolbar([
    'close_href' => site_url('reports'),
    'extra'      => $extra,
    'auth'       => $auth,
]) ?>
<div class="tabs">
  <div class="tab active">Profitability</div>
  <div class="spacer"></div>
  <div class="recordnav"><?= count($rows) ?> bookings</div>
</div>

<div class="formwrap" style="flex:0 0 auto;">
  <div class="row g-3">
    <div class="col-6 col-md-2"><div class="stat"><span class="label">Sell</span><span class="value">₹<?= number_format((float) $totals['sell'], 0) ?></span></div></div>
    <div class="col-6 col-md-2"><div class="stat"><span class="label">Buy</span><span class="value">₹<?= number_format((float) $totals['buy'], 0) ?></span></div></div>
    <div class="col-6 col-md-2"><div class="stat"><span class="label">Booked Margin</span><span class="value">₹<?= number_format((float) $totals['margin'], 0) ?></span></div></div>
    <div class="col-6 col-md-2"><div class="stat"><span class="label">Internal Exp</span><span class="value">₹<?= number_format((float) ($totals['internal_exp'] ?? 0), 0) ?></span></div></div>
    <div class="col-6 col-md-2"><div class="stat"><span class="label">Billable Recovered</span><span class="value">₹<?= number_format((float) ($totals['billable_recovered'] ?? 0), 0) ?></span></div></div>
    <div class="col-6 col-md-2"><div class="stat"><span class="label">True Margin</span><span class="value">₹<?= number_format((float) ($totals['true_margin'] ?? 0), 0) ?></span><small class="text-muted" style="font-size:.7rem;"><?= esc($totals['true_margin_pct'] ?? 0) ?>%</small></div></div>
  </div>
</div>

<div class="gridwrap">
  <div class="table-responsive">
    <table class="table grid mb-0">
      <thead>
        <tr><th>Booking</th><th>Route</th><th>Client</th><th>Vendor</th><th class="text-end">Buy</th><th class="text-end">Sell</th><th class="text-end">Margin</th><th class="text-end">Int Exp</th><th class="text-end">Recov.</th><th class="text-end">True M.</th><th>Status</th></tr>
      </thead>
      <tbody>
        <?php if (empty($rows)): ?><tr><td colspan="11" class="text-center text-muted">No bookings match.</td></tr><?php endif; ?>
        <?php foreach ($rows as $r):
          $buy = (float) $r['final_buy_rate']; $sell = (float) $r['final_sell_rate'];
          $trueM = (float) ($r['true_margin'] ?? $r['margin_amount']);
        ?>
          <tr>
            <td data-label="Booking"><a href="<?= site_url('bookings/' . $r['id']) ?>"><code><?= esc($r['booking_no']) ?></code></a>
              <?php if ($r['trip_no']): ?><br><small class="text-muted"><?= esc($r['trip_no']) ?></small><?php endif; ?>
            </td>
            <td data-label="Route"><?= esc($r['route_text']) ?><br><small class="text-muted"><?= esc($r['loading_date']) ?></small></td>
            <td data-label="Client"><?= esc($r['client_name']) ?></td>
            <td data-label="Vendor"><?= esc($r['vendor_name']) ?></td>
            <td class="text-end" data-label="Buy">₹<?= number_format($buy, 0) ?></td>
            <td class="text-end" data-label="Sell">₹<?= number_format($sell, 0) ?></td>
            <td class="text-end" data-label="Margin">₹<?= number_format((float) $r['margin_amount'], 0) ?></td>
            <td class="text-end" data-label="Int Exp"><?php $ie = (float) ($r['internal_expenses'] ?? 0); ?><?= $ie > 0 ? '<span class="text-danger">₹' . number_format($ie, 0) . '</span>' : '—' ?></td>
            <td class="text-end" data-label="Recov."><?php $br = (float) ($r['billed_recovered'] ?? 0); ?><?= $br > 0 ? '₹' . number_format($br, 0) : '—' ?></td>
            <td class="text-end" data-label="True M."><strong>₹<?= number_format($trueM, 0) ?></strong></td>
            <td data-label="Status"><span class="badge-soft"><?= esc($r['booking_status']) ?></span></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
