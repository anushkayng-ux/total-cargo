<?php
$extra = '<a class="btn btn-sm btn-outline-dark" href="' . site_url('hrms/team') . '"><i class="bi bi-people"></i> Team</a>';

echo tpt_toolbar([
    'close_href' => site_url('dashboard'),
    'extra'      => $extra,
    'auth'       => $auth,
]);
?>
<div class="tabs" role="tablist">
  <div class="tab active">Leave Approvals</div>
  <div class="spacer"></div>
  <div class="recordnav"><?= count($rows) ?> applications</div>
</div>

<div class="gridwrap">
  <div class="table-responsive">
    <table class="table grid align-middle mb-0" data-tpt-cols="leave_approvals">
      <thead><tr><th data-col="employee">Employee</th><th data-col="type">Type</th><th data-col="from">From</th><th data-col="to">To</th><th data-col="days">Days</th><th data-col="reason">Reason</th><th data-col="status">Status</th><th data-col="decision" class="text-end">Decision</th></tr></thead>
      <tbody>
        <?php if (empty($rows)): ?>
          <tr><td colspan="8" class="text-center text-muted py-3">No leave applications.</td></tr>
        <?php endif; ?>
        <?php foreach ($rows as $r):
          $cls = match($r['status']) {
            'Pending' => 'badge-warn', 'Approved' => 'badge-ok',
            'Rejected'=> 'badge-danger',  'Cancelled' => '',
            default   => '',
          };
        ?>
          <tr>
            <td data-col="employee"><?= esc($r['employee_name']) ?></td>
            <td data-col="type"><?= esc($r['type_code']) ?> <small class="text-muted">— <?= esc($r['type_name']) ?></small></td>
            <td data-col="from"><?= esc(date('d-m', strtotime($r['from_date']))) ?></td>
            <td data-col="to"><?= esc(date('d-m', strtotime($r['to_date']))) ?></td>
            <td data-col="days"><?= esc((string) $r['days']) ?></td>
            <td data-col="reason" style="max-width:240px;"><?= esc(mb_strimwidth($r['reason'] ?? '', 0, 80, '…')) ?></td>
            <td data-col="status"><span class="badge-soft <?= $cls ?>"><?= esc($r['status']) ?></span></td>
            <td data-col="decision" class="text-end">
              <?php if ($r['status'] === 'Pending'): ?>
                <form method="post" action="<?= site_url('hrms/leaves/' . (int) $r['id'] . '/decide') ?>" class="d-inline-flex align-items-center gap-1">
                  <?= csrf_field() ?>
                  <input type="text" name="approver_notes" placeholder="Notes (optional)" class="retro-box" style="width:150px;">
                  <button name="action" value="approve" class="retro-tbtn retro-primary" style="width:auto;flex-direction:row;padding:3px 8px;"><i class="bi bi-check-lg"></i></button>
                  <button name="action" value="reject"  class="retro-tbtn retro-danger" style="width:auto;flex-direction:row;padding:3px 8px;" onclick="return confirm('Reject this leave?');"><i class="bi bi-x-lg"></i></button>
                </form>
              <?php else: ?>
                <small class="text-muted"><?= esc(date('d-m', strtotime($r['approved_at'] ?? $r['updated_at']))) ?></small>
                <?php if (!empty($r['approver_notes'])): ?><div class="text-muted small" style="max-width:200px;"><?= esc($r['approver_notes']) ?></div><?php endif; ?>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
