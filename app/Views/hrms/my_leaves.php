<?php
$extra = '<a class="btn btn-sm btn-outline-dark" href="' . site_url('hrms/leaves/apply') . '"><i class="bi bi-plus-circle"></i> Apply for Leave</a>';

echo tpt_toolbar([
    'close_href' => site_url('dashboard'),
    'extra'      => $extra,
    'auth'       => $auth,
]);
?>
<div class="tabs" role="tablist">
  <div class="tab active">My Leaves</div>
  <div class="spacer"></div>
  <div class="recordnav"><?= count($leaves) ?> applications</div>
</div>

<div class="formwrap" style="flex:0 0 auto;">
  <div class="kpirow" style="margin-bottom:0;">
    <?php foreach ($balances as $b): ?>
      <div class="kpi">
        <div class="lbl"><?= esc($b['name']) ?> (<?= esc($b['code']) ?>)</div>
        <div class="num"><?= esc((string) $b['balance']) ?></div>
        <div style="font-size:11px;color:var(--v2-fg-muted);margin-top:2px;">of <?= esc((string) $b['allocated']) ?> · used <?= esc((string) $b['used']) ?></div>
      </div>
    <?php endforeach; ?>
    <?php if (empty($balances)): ?>
      <div class="text-muted">No leave balances on record yet.</div>
    <?php endif; ?>
  </div>
</div>

<div class="gridwrap">
  <div class="table-responsive">
    <table class="table grid mb-0" data-tpt-cols="my_leaves">
      <thead><tr><th data-col="type">Type</th><th data-col="from">From</th><th data-col="to">To</th><th data-col="days">Days</th><th data-col="reason">Reason</th><th data-col="status">Status</th><th data-col="notes">Approver notes</th></tr></thead>
      <tbody>
        <?php if (empty($leaves)): ?>
          <tr><td colspan="7" class="text-muted text-center py-3">No leave applications yet.</td></tr>
        <?php endif; ?>
        <?php foreach ($leaves as $l):
          $cls = match($l['status']) {
            'Pending'   => 'badge-warn', 'Approved' => 'badge-ok',
            'Rejected'  => 'badge-danger',  'Cancelled' => '',
            default     => '',
          };
        ?>
          <tr>
            <td data-col="type"><?= esc($l['type_code']) ?> <small class="text-muted">— <?= esc($l['type_name']) ?></small></td>
            <td data-col="from"><?= esc(date('d-m-Y', strtotime($l['from_date']))) ?></td>
            <td data-col="to"><?= esc(date('d-m-Y', strtotime($l['to_date']))) ?></td>
            <td data-col="days"><?= esc((string) $l['days']) ?></td>
            <td data-col="reason"><?= esc(mb_strimwidth($l['reason'] ?? '', 0, 50, '…')) ?></td>
            <td data-col="status"><span class="badge-soft <?= $cls ?>"><?= esc($l['status']) ?></span></td>
            <td data-col="notes"><?= esc($l['approver_notes'] ?? '—') ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
