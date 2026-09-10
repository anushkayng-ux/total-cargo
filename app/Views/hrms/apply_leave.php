<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
  <h5 class="m-0"><i class="bi bi-calendar-plus"></i> Apply for Leave</h5>
  <a class="btn btn-sm btn-light ms-auto" href="<?= site_url('hrms/leaves') ?>"><i class="bi bi-arrow-left"></i> Back to my leaves</a>
</div>

<form method="post" action="<?= site_url('hrms/leaves/apply') ?>" class="card">
  <?= csrf_field() ?>
  <div class="card-body">
    <div class="row g-3">
      <div class="col-md-6">
        <label class="form-label">Leave type *</label>
        <select class="form-select" name="leave_type_id" required>
          <option value="">— select —</option>
          <?php foreach ($types as $t): ?>
            <option value="<?= (int) $t['id'] ?>"><?= esc($t['code']) ?> — <?= esc($t['name']) ?> (<?= esc((string) $t['default_annual_quota']) ?>/yr)</option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label">From date *</label>
        <input class="form-control" type="date" name="from_date" required>
      </div>
      <div class="col-md-3">
        <label class="form-label">To date *</label>
        <input class="form-control" type="date" name="to_date" required>
      </div>
      <div class="col-12">
        <div class="form-check">
          <input class="form-check-input" type="checkbox" id="is_half_day" name="is_half_day" value="1">
          <label class="form-check-label" for="is_half_day">Half-day leave</label>
        </div>
      </div>
      <div class="col-12">
        <label class="form-label">Reason</label>
        <textarea class="form-control" rows="3" name="reason" placeholder="Briefly explain the reason for this leave"></textarea>
      </div>
    </div>
  </div>
  <div class="card-footer">
    <button class="btn btn-primary"><i class="bi bi-send"></i> Submit application</button>
    <a class="btn btn-light" href="<?= site_url('hrms/leaves') ?>">Cancel</a>
  </div>
</form>
