<?php
$fmt = fn($n) => '₹' . number_format((float) $n, 0);
$extra = '<form method="post" action="' . site_url('payroll/new') . '" class="d-flex gap-2 align-items-center m-0">'
    . csrf_field()
    . '<select class="form-select form-select-sm" name="month" style="width:auto;">';
for ($m = 1; $m <= 12; $m++) {
    $extra .= '<option value="' . $m . '"' . ($m == (int) date('n') ? ' selected' : '') . '>' . date('F', mktime(0,0,0,$m,1)) . '</option>';
}
$extra .= '</select><select class="form-select form-select-sm" name="year" style="width:auto;">';
for ($y = (int) date('Y'); $y >= (int) date('Y') - 2; $y--) {
    $extra .= '<option value="' . $y . '">' . $y . '</option>';
}
$extra .= '</select><button class="btn btn-primary btn-sm" type="submit"><i class="bi bi-calculator"></i> New / Recompute</button></form>';

echo tpt_toolbar([
    'close_href' => site_url('dashboard'),
    'extra'      => $extra,
    'auth'       => $auth,
]);
?>
<div class="tabs">
  <div class="tab active">Payroll Runs</div>
  <div class="spacer"></div>
  <div class="recordnav"><?= count($rows) ?> total records</div>
</div>

<div class="gridwrap">
  <div class="table-responsive">
    <table class="table grid mb-0" data-tpt-cols="payroll_runs">
      <thead><tr><th data-col="period">Period</th><th data-col="status">Status</th><th data-col="gross" class="text-end">Gross</th><th data-col="deductions" class="text-end">Deductions</th><th data-col="net" class="text-end">Net</th><th data-col="finalised">Finalised</th><th data-col="actions" class="text-end">Actions</th></tr></thead>
      <tbody>
        <?php if (empty($rows)): ?>
          <tr><td colspan="7" class="text-center text-muted py-3">No payroll runs yet. Pick a month above and click "New / Recompute" to generate.</td></tr>
        <?php endif; ?>
        <?php foreach ($rows as $r):
          $cls = match($r['run_status']) { 'Draft' => 'warning', 'Finalised' => 'primary', 'Paid' => 'success', default => 'light' };
        ?>
          <tr class="row-link" data-href="<?= site_url('payroll/' . (int) $r['id']) ?>">
            <td data-col="period"><?= esc(date('F Y', mktime(0,0,0,(int)$r['pay_period_month'],1,(int)$r['pay_period_year']))) ?></td>
            <td data-col="status"><span class="badge bg-<?= $cls ?> text-<?= $cls === 'warning' ? 'dark' : 'light' ?>"><?= esc($r['run_status']) ?></span></td>
            <td data-col="gross" class="text-end"><?= $fmt($r['total_gross']) ?></td>
            <td data-col="deductions" class="text-end"><?= $fmt($r['total_deductions']) ?></td>
            <td data-col="net" class="text-end"><strong><?= $fmt($r['total_net']) ?></strong></td>
            <td data-col="finalised"><?= !empty($r['finalised_at']) ? esc(date('d-m-Y', strtotime($r['finalised_at']))) : '—' ?></td>
            <td data-col="actions" class="text-end">
              <a class="btn btn-sm btn-light" href="<?= site_url('payroll/' . (int) $r['id']) ?>"><i class="bi bi-search"></i> Open</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php if (!empty($pagerHtml)): ?><div class="mt-3"><?= $pagerHtml ?></div><?php endif; ?>
</div>
