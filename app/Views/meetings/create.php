<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
  <h5 class="m-0"><i class="bi bi-calendar-plus"></i> Plan a Meeting</h5>
  <a class="btn btn-sm btn-light ms-auto" href="<?= site_url('meetings') ?>"><i class="bi bi-arrow-left"></i> Back</a>
</div>

<form method="post" action="<?= site_url('meetings') ?>" class="card">
  <?= csrf_field() ?>
  <div class="card-body">
    <div class="row g-3">
      <div class="col-md-8">
        <label class="form-label">Meeting title *</label>
        <input class="form-control" type="text" name="title" maxlength="200" required placeholder="e.g. Rate negotiation — Blue Horizon Textiles">
      </div>
      <div class="col-md-4">
        <label class="form-label">Scheduled at *</label>
        <input class="form-control" type="datetime-local" name="scheduled_at" required value="<?= esc(date('Y-m-d\TH:i', strtotime('+1 hour'))) ?>">
      </div>
      <div class="col-md-6">
        <label class="form-label">With (company)</label>
        <input class="form-control" type="text" name="with_company" maxlength="200" placeholder="Client / vendor company">
      </div>
      <div class="col-md-3">
        <label class="form-label">Contact name</label>
        <input class="form-control" type="text" name="with_contact_name" maxlength="150">
      </div>
      <div class="col-md-3">
        <label class="form-label">Contact phone</label>
        <input class="form-control" type="tel" name="with_contact_phone" maxlength="30">
      </div>
      <div class="col-12">
        <label class="form-label">Location (address or landmark)</label>
        <textarea class="form-control" rows="2" name="location" maxlength="400"></textarea>
      </div>
    </div>
  </div>
  <div class="card-footer">
    <button class="btn btn-primary"><i class="bi bi-check2"></i> Save meeting</button>
    <small class="text-muted ms-2">After saving you'll get a phone-friendly PWA link to punch your travel events.</small>
  </div>
</form>
