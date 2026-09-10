<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
  <h5 class="m-0"><i class="bi bi-calendar-event"></i> Holiday Calendar — <?= esc((string) $year) ?></h5>
  <div class="btn-group btn-group-sm ms-2">
    <a class="btn btn-light" href="?y=<?= $year - 1 ?>">&laquo; <?= $year - 1 ?></a>
    <a class="btn btn-light" href="?y=<?= (int) date('Y') ?>"><?= (int) date('Y') ?></a>
    <a class="btn btn-light" href="?y=<?= $year + 1 ?>"><?= $year + 1 ?> &raquo;</a>
  </div>
  <span class="badge bg-secondary ms-auto"><?= count($rows) ?> holidays</span>
</div>

<div class="row g-3">
  <div class="col-lg-7">
    <div class="card">
      <div class="card-header"><i class="bi bi-list"></i> Public holidays</div>
      <div class="table-responsive">
        <table class="table table-sm align-middle mb-0" data-tpt-cols="holidays">
          <thead><tr><th data-col="date">Date</th><th data-col="day">Day</th><th data-col="name">Name</th><th data-col="pay">Pay</th><th data-col="actions" class="text-end"></th></tr></thead>
          <tbody>
            <?php if (empty($rows)): ?>
              <tr><td colspan="5" class="text-center text-muted py-3">No holidays yet for <?= esc((string) $year) ?>. Use the form on the right to add one.</td></tr>
            <?php endif; ?>
            <?php foreach ($rows as $r):
              $t = strtotime($r['holiday_date']);
            ?>
              <tr>
                <td data-col="date"><?= esc(date('d-m-Y', $t)) ?></td>
                <td data-col="day"><?= esc(date('D', $t)) ?></td>
                <td data-col="name"><strong><?= esc($r['name']) ?></strong></td>
                <td data-col="pay">
                  <?php
                  $cls = match($r['pay_status']) { 'paid' => 'success', 'unpaid' => 'danger', 'optional' => 'warning', default => 'light' };
                  ?>
                  <span class="badge bg-<?= $cls ?> text-<?= $cls === 'warning' ? 'dark' : 'light' ?>"><?= esc($r['pay_status']) ?></span>
                </td>
                <td data-col="actions" class="text-end">
                  <form method="post" action="<?= site_url('hrms/holidays/' . (int) $r['id'] . '/delete') ?>" class="d-inline" onsubmit="return confirm('Remove this holiday?');">
                    <?= csrf_field() ?>
                    <button class="btn btn-sm btn-light text-danger"><i class="bi bi-trash"></i></button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="col-lg-5">
    <form method="post" action="<?= site_url('hrms/holidays') ?>" class="card">
      <?= csrf_field() ?>
      <div class="card-header"><i class="bi bi-plus-circle"></i> Add a holiday</div>
      <div class="card-body">
        <div class="mb-2">
          <label class="form-label">Date *</label>
          <input class="form-control" type="date" name="holiday_date" required>
        </div>
        <div class="mb-2">
          <label class="form-label">Name *</label>
          <input class="form-control" type="text" name="name" placeholder="e.g. Diwali, Pongal" maxlength="120" required>
        </div>
        <div class="mb-2">
          <label class="form-label">Pay status</label>
          <select class="form-select" name="pay_status">
            <option value="paid" selected>Paid (default)</option>
            <option value="unpaid">Unpaid</option>
            <option value="optional">Optional / restricted</option>
          </select>
          <div class="form-text" style="font-size:.78rem;">Paid holidays don't deduct from salary; unpaid does. Optional is treated as paid by payroll but flagged in attendance.</div>
        </div>
        <button class="btn btn-primary"><i class="bi bi-check2"></i> Add</button>
      </div>
    </form>

    <div class="alert alert-light mt-3" style="font-size:.85rem;">
      <i class="bi bi-info-circle text-muted"></i>
      <strong>How it works:</strong> Holidays in this calendar count as <em>paid</em> days during payroll computation. Employees who punch in on a holiday still get the day credited — no salary deduction. Weekends are auto-detected separately.
    </div>
  </div>
</div>
