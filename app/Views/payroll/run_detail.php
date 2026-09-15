<?php
$fmt = fn($n) => '₹' . number_format((float) $n, 0);
$cls = match($run['run_status']) { 'Draft' => 'warning', 'Finalised' => 'primary', 'Paid' => 'success', default => 'light' };
$extra = $run['run_status'] === 'Draft'
    ? '<form method="post" action="' . site_url('payroll/' . (int) $run['id'] . '/finalise') . '" class="d-inline" data-confirm="Finalise this run? After finalisation, lines cannot be recomputed.">' . csrf_field() . '<button class="btn btn-sm btn-primary"><i class="bi bi-check2-square"></i> Finalise</button></form>'
    : '';
?>
<?= tpt_toolbar([
    'close_href' => site_url('payroll'),
    'extra'      => $extra,
    'auth'       => $auth,
]) ?>
<div class="tabs">
  <div class="tab active">Payroll Run</div>
  <div class="spacer"></div>
  <div class="recordnav">
    <a href="<?= site_url('payroll') ?>"><i class="bi bi-list"></i> All Runs</a> &middot;
    <?= esc(date('F Y', mktime(0,0,0,(int)$run['pay_period_month'],1,(int)$run['pay_period_year']))) ?> &middot;
    <span class="badge bg-<?= $cls ?> text-<?= $cls === 'warning' ? 'dark' : 'light' ?>"><?= esc($run['run_status']) ?></span>
  </div>
</div>

<div class="formwrap" style="flex:0 0 auto;">
  <div class="row g-3">
    <div class="col-md-4"><div class="stat"><span class="label">Total gross</span><span class="value"><?= $fmt($run['total_gross']) ?></span></div></div>
    <div class="col-md-4"><div class="stat"><span class="label">Total deductions</span><span class="value"><?= $fmt($run['total_deductions']) ?></span></div></div>
    <div class="col-md-4"><div class="stat"><span class="label">Total net</span><span class="value" style="color:#065f46;"><?= $fmt($run['total_net']) ?></span></div></div>
  </div>
</div>

<div class="gridwrap">
  <div class="table-responsive">
    <table class="table grid mb-0">
      <thead>
        <tr>
          <th>Employee</th><th class="text-end">Days paid</th><th class="text-end">LOP</th>
          <th class="text-end">Basic</th><th class="text-end">HRA</th><th class="text-end">Allow.</th>
          <th class="text-end">Gross</th><th class="text-end">Ded.</th><th class="text-end">Net</th><th></th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($lines)): ?>
          <tr><td colspan="10" class="text-center text-muted py-3">No salary structures configured. Set salary components on each employee's HR page.</td></tr>
        <?php endif; ?>
        <?php foreach ($lines as $l): ?>
          <tr>
            <td><?= esc($l['employee_name']) ?><br><small class="text-muted"><?= esc($l['email']) ?></small></td>
            <td class="text-end"><?= esc((string) $l['days_paid']) ?>/<?= esc((string) $l['days_in_month']) ?></td>
            <td class="text-end"><?= (float) $l['lop_days'] > 0 ? '<span class="text-danger">' . esc((string) $l['lop_days']) . '</span>' : '0' ?></td>
            <td class="text-end"><?= $fmt($l['basic']) ?></td>
            <td class="text-end"><?= $fmt($l['hra']) ?></td>
            <td class="text-end"><?= $fmt((float) $l['special_allowance'] + (float) $l['conveyance'] + (float) $l['medical'] + (float) $l['other_earnings']) ?></td>
            <td class="text-end"><strong><?= $fmt($l['gross']) ?></strong></td>
            <td class="text-end text-danger"><?= $fmt($l['total_deductions']) ?></td>
            <td class="text-end"><strong style="color:#065f46;"><?= $fmt($l['net_pay']) ?></strong></td>
            <td class="text-end"><a class="btn btn-sm btn-light" target="_blank" href="<?= site_url('payroll/payslip/' . (int) $l['id']) ?>"><i class="bi bi-file-earmark-text"></i></a></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
