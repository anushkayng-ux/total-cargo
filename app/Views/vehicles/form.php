<?php
$isEdit = !empty($row);
$action = $isEdit ? site_url('vehicles/' . $row['id']) : site_url('vehicles/store');
$v = function ($k, $d = '') use ($row) { return old($k, $row[$k] ?? $d); };
?>
<h5 class="mb-3"><?= esc($pageTitle) ?></h5>
<div class="card"><div class="card-body">
<form method="post" action="<?= $action ?>">
  <?= csrf_field() ?>
  <div class="row g-3">
    <div class="col-md-4"><label class="form-label">Vehicle Number <span class="text-danger">*</span></label>
      <input class="form-control" name="vehicle_number" required value="<?= esc($v('vehicle_number')) ?>"></div>
    <div class="col-md-4"><label class="form-label">Vehicle Type</label>
      <input class="form-control" name="vehicle_type" value="<?= esc($v('vehicle_type')) ?>" placeholder="e.g. 32ft SXL, Container 20ft"></div>
    <div class="col-md-4"><label class="form-label">Vendor</label>
      <select class="form-select" name="vendor_id">
        <option value="">— None —</option>
        <?php foreach ($vendors as $ven): ?>
          <option value="<?= $ven['id'] ?>" <?= (int)$v('vendor_id') === (int)$ven['id'] ? 'selected' : '' ?>><?= esc($ven['company_name']) ?></option>
        <?php endforeach; ?>
      </select></div>

    <div class="col-md-4">
      <label class="form-label d-block">Status</label>
      <div class="form-check form-switch">
        <input class="form-check-input" type="checkbox" name="status" value="1" id="s" <?= (int)($row['status'] ?? 1) === 1 ? 'checked' : '' ?>>
        <label class="form-check-label" for="s">Active</label>
      </div>
    </div>
  </div>

  <hr class="my-4">
  <h6 class="mb-3"><i class="bi bi-shield-check"></i> Compliance documents <small class="text-muted">— admins get alerted before any of these expire</small></h6>
  <div class="row g-3">
    <div class="col-md-3"><label class="form-label">RC No</label>
      <input class="form-control" name="rc_no" value="<?= esc($v('rc_no')) ?>"></div>
    <div class="col-md-3"><label class="form-label">RC Expiry</label>
      <input type="date" class="form-control" name="rc_expiry" value="<?= esc($v('rc_expiry')) ?>"></div>
    <div class="col-md-3"><label class="form-label">Permit No</label>
      <input class="form-control" name="permit_no" value="<?= esc($v('permit_no')) ?>"></div>
    <div class="col-md-3"><label class="form-label">Permit Expiry</label>
      <input type="date" class="form-control" name="permit_expiry" value="<?= esc($v('permit_expiry')) ?>"></div>

    <div class="col-md-3"><label class="form-label">Insurance No</label>
      <input class="form-control" name="insurance_no" value="<?= esc($v('insurance_no')) ?>"></div>
    <div class="col-md-3"><label class="form-label">Insurance Expiry</label>
      <input type="date" class="form-control" name="insurance_expiry" value="<?= esc($v('insurance_expiry')) ?>"></div>
    <div class="col-md-3"><label class="form-label">Fitness Expiry</label>
      <input type="date" class="form-control" name="fitness_expiry" value="<?= esc($v('fitness_expiry')) ?>"></div>
    <div class="col-md-3"><label class="form-label">Road Tax Expiry</label>
      <input type="date" class="form-control" name="tax_expiry" value="<?= esc($v('tax_expiry')) ?>"></div>

    <div class="col-md-3"><label class="form-label">PUC No</label>
      <input class="form-control" name="puc_no" value="<?= esc($v('puc_no')) ?>"></div>
    <div class="col-md-3"><label class="form-label">PUC Expiry</label>
      <input type="date" class="form-control" name="puc_expiry" value="<?= esc($v('puc_expiry')) ?>"></div>
  </div>

  <hr class="my-4">
  <h6 class="mb-2"><i class="bi bi-broadcast"></i> GPS / tracking</h6>
  <p class="text-muted" style="font-size:.85rem;">If the vendor has a GPS device on this truck, set the provider here so the cron can poll it. If they only share a portal link, paste it as <em>Vendor portal URL</em> — staff get a "Open vendor's tracker" button on the trip.</p>
  <div class="row g-3">
    <div class="col-md-4">
      <label class="form-label">Provider</label>
      <select class="form-select" name="gps_provider">
        <?php foreach (\App\Models\VehicleModel::GPS_PROVIDERS as $k => $label): ?>
          <option value="<?= $k ?>" <?= $v('gps_provider','none') === $k ? 'selected' : '' ?>><?= esc($label) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-3">
      <label class="form-label">Device IMEI</label>
      <input class="form-control" name="gps_device_imei" value="<?= esc($v('gps_device_imei')) ?>" placeholder="15-digit IMEI (if known)">
      <div class="form-text" style="font-size:.78rem;">Some providers (LocoNav, Sastra) need this to map the device. Leave blank if your provider works by registration number alone.</div>
    </div>
    <div class="col-md-5">
      <label class="form-label">Vendor portal URL</label>
      <input class="form-control" name="gps_tracking_url" value="<?= esc($v('gps_tracking_url')) ?>" placeholder="https://track.example.com/abc123">
      <div class="form-text" style="font-size:.78rem;">If the vendor only provides a public tracking link, paste it. The link will appear on every trip using this vehicle.</div>
    </div>
    <div class="col-12">
      <label class="form-label">GPS notes (internal)</label>
      <input class="form-control" name="gps_notes" value="<?= esc($v('gps_notes')) ?>" placeholder="e.g. Device subscription expires Mar 2027 · Login via WhatsApp from owner">
    </div>
  </div>

  <div class="mt-3 d-flex gap-2">
    <button class="btn btn-primary" type="submit">Save</button>
    <a class="btn btn-light" href="<?= site_url('vehicles') ?>">Cancel</a>
  </div>
</form>
</div></div>
