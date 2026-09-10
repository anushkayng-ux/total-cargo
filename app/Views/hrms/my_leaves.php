<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
  <h5 class="m-0"><i class="bi bi-calendar-x"></i> My Leaves</h5>
  <a class="btn btn-primary btn-sm ms-auto" href="<?= site_url('hrms/leaves/apply') ?>"><i class="bi bi-plus-circle"></i> Apply for leave</a>
</div>

<div class="card mb-3">
  <div class="card-header"><i class="bi bi-pie-chart"></i> Leave balances · <?= date('Y') ?></div>
  <div class="card-body">
    <div class="row g-3">
      <?php foreach ($balances as $b): ?>
        <div class="col-md-3 col-sm-6">
          <div class="stat-card" style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;padding:.85rem;">
            <div class="text-muted small" style="font-size:.72rem;letter-spacing:.04em;text-transform:uppercase;font-weight:600;"><?= esc($b['name']) ?> (<?= esc($b['code']) ?>)</div>
            <div style="font-size:1.4rem;font-weight:700;color:#0f172a;margin-top:.2rem;"><?= esc((string) $b['balance']) ?></div>
            <div class="text-muted small" style="font-size:.78rem;">of <?= esc((string) $b['allocated']) ?> · used <?= esc((string) $b['used']) ?></div>
          </div>
        </div>
      <?php endforeach; ?>
      <?php if (empty($balances)): ?>
        <div class="col-12 text-muted">No leave balances on record yet.</div>
      <?php endif; ?>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-header"><i class="bi bi-list-ul"></i> Applications</div>
  <div class="table-responsive">
    <table class="table table-sm mb-0" data-tpt-cols="my_leaves">
      <thead><tr><th data-col="type">Type</th><th data-col="from">From</th><th data-col="to">To</th><th data-col="days">Days</th><th data-col="reason">Reason</th><th data-col="status">Status</th><th data-col="notes">Approver notes</th></tr></thead>
      <tbody>
        <?php if (empty($leaves)): ?>
          <tr><td colspan="7" class="text-muted text-center py-3">No leave applications yet.</td></tr>
        <?php endif; ?>
        <?php foreach ($leaves as $l):
          $cls = match($l['status']) {
            'Pending'   => 'warning', 'Approved' => 'success',
            'Rejected'  => 'danger',  'Cancelled' => 'secondary',
            default     => 'light',
          };
        ?>
          <tr>
            <td data-col="type"><?= esc($l['type_code']) ?> <small class="text-muted">— <?= esc($l['type_name']) ?></small></td>
            <td data-col="from"><?= esc(date('d-m-Y', strtotime($l['from_date']))) ?></td>
            <td data-col="to"><?= esc(date('d-m-Y', strtotime($l['to_date']))) ?></td>
            <td data-col="days"><?= esc((string) $l['days']) ?></td>
            <td data-col="reason"><?= esc(mb_strimwidth($l['reason'] ?? '', 0, 50, '…')) ?></td>
            <td data-col="status"><span class="badge bg-<?= $cls ?> text-<?= $cls === 'warning' ? 'dark' : 'light' ?>"><?= esc($l['status']) ?></span></td>
            <td data-col="notes"><?= esc($l['approver_notes'] ?? '—') ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
