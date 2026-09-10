<h5 class="mb-3">Raise a support ticket</h5>
<div class="card"><div class="card-body">
<form method="post" action="<?= site_url('support/store') ?>">
  <?= csrf_field() ?>
  <div class="row g-3">
    <div class="col-md-3"><label class="form-label">Type</label>
      <select class="form-select" name="type" required>
        <?php foreach (\App\Models\SupportTicketModel::TYPES as $t): ?>
          <option value="<?= $t ?>" <?= old('type') === $t ? 'selected' : '' ?>><?= $t ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-3"><label class="form-label">Priority</label>
      <select class="form-select" name="priority">
        <?php foreach (\App\Models\SupportTicketModel::PRIORITIES as $p): ?>
          <option value="<?= $p ?>" <?= old('priority', 'Normal') === $p ? 'selected' : '' ?>><?= $p ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-6"><label class="form-label">Where did you see this? <small class="text-muted">(optional URL)</small></label>
      <input class="form-control" name="screen_url" value="<?= esc(old('screen_url')) ?>" placeholder="e.g. /tpt/public/bookings/123">
    </div>

    <div class="col-12"><label class="form-label">Subject</label>
      <input class="form-control" name="subject" required maxlength="200" value="<?= esc(old('subject')) ?>"
             placeholder="One-line summary of what you need help with">
    </div>

    <div class="col-12"><label class="form-label">Describe the issue / suggestion</label>
      <textarea class="form-control" name="body" rows="6" required maxlength="5000"
                placeholder="Steps to reproduce, what you expected, what actually happened, screenshots/links if any."><?= esc(old('body')) ?></textarea>
      <div class="form-text">Tip: For bugs, include the steps you took and the screen URL. For improvements, tell us the outcome you want.</div>
    </div>
  </div>

  <div class="mt-3 d-flex gap-2">
    <button class="btn btn-primary" type="submit"><i class="bi bi-send"></i> Submit ticket</button>
    <a class="btn btn-light" href="<?= site_url('support') ?>">Cancel</a>
  </div>
</form>
</div></div>
