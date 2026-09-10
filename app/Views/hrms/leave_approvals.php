<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
  <h5 class="m-0"><i class="bi bi-clipboard-check"></i> Leave Approvals</h5>
  <a class="btn btn-sm btn-light ms-auto" href="<?= site_url('hrms/team') ?>"><i class="bi bi-people"></i> Team</a>
</div>

<div class="card">
  <div class="table-responsive">
    <table class="table table-sm align-middle mb-0" data-tpt-cols="leave_approvals">
      <thead><tr><th data-col="employee">Employee</th><th data-col="type">Type</th><th data-col="from">From</th><th data-col="to">To</th><th data-col="days">Days</th><th data-col="reason">Reason</th><th data-col="status">Status</th><th data-col="decision" class="text-end">Decision</th></tr></thead>
      <tbody>
        <?php if (empty($rows)): ?>
          <tr><td colspan="8" class="text-center text-muted py-3">No leave applications.</td></tr>
        <?php endif; ?>
        <?php foreach ($rows as $r):
          $cls = match($r['status']) {
            'Pending' => 'warning', 'Approved' => 'success',
            'Rejected'=> 'danger',  'Cancelled' => 'secondary',
            default   => 'light',
          };
        ?>
          <tr>
            <td data-col="employee"><?= esc($r['employee_name']) ?></td>
            <td data-col="type"><?= esc($r['type_code']) ?> <small class="text-muted">— <?= esc($r['type_name']) ?></small></td>
            <td data-col="from"><?= esc(date('d-m', strtotime($r['from_date']))) ?></td>
            <td data-col="to"><?= esc(date('d-m', strtotime($r['to_date']))) ?></td>
            <td data-col="days"><?= esc((string) $r['days']) ?></td>
            <td data-col="reason" style="max-width:240px;"><?= esc(mb_strimwidth($r['reason'] ?? '', 0, 80, '…')) ?></td>
            <td data-col="status"><span class="badge bg-<?= $cls ?> text-<?= $cls === 'warning' ? 'dark' : 'light' ?>"><?= esc($r['status']) ?></span></td>
            <td data-col="decision" class="text-end">
              <?php if ($r['status'] === 'Pending'): ?>
                <form method="post" action="<?= site_url('hrms/leaves/' . (int) $r['id'] . '/decide') ?>" class="d-inline">
                  <?= csrf_field() ?>
                  <input type="text" name="approver_notes" placeholder="Notes (optional)" class="form-control form-control-sm d-inline-block" style="width:150px;">
                  <button name="action" value="approve" class="btn btn-sm btn-success"><i class="bi bi-check-lg"></i></button>
                  <button name="action" value="reject"  class="btn btn-sm btn-danger" onclick="return confirm('Reject this leave?');"><i class="bi bi-x-lg"></i></button>
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
