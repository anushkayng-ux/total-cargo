<?php
$isEdit = !empty($row);
$action = $isEdit ? site_url('drivers/' . $row['id']) : site_url('drivers/store');
$v = function ($k, $d = '') use ($row) { return old($k, $row[$k] ?? $d); };
?>
<h5 class="mb-3"><?= esc($pageTitle) ?></h5>
<div class="card"><div class="card-body">
<form method="post" action="<?= $action ?>">
  <?= csrf_field() ?>
  <div class="row g-3">
    <div class="col-md-6"><label class="form-label">Driver Name <span class="text-danger">*</span></label>
      <input class="form-control" name="driver_name" required value="<?= esc($v('driver_name')) ?>"></div>
    <div class="col-md-6"><label class="form-label">Vendor</label>
      <select class="form-select" name="vendor_id">
        <option value="">— None —</option>
        <?php foreach ($vendors as $ven): ?>
          <option value="<?= $ven['id'] ?>" <?= (int)$v('vendor_id') === (int)$ven['id'] ? 'selected' : '' ?>><?= esc($ven['company_name']) ?></option>
        <?php endforeach; ?>
      </select></div>

    <div class="col-md-4"><label class="form-label">Mobile</label>
      <input class="form-control" name="mobile" value="<?= esc($v('mobile')) ?>"></div>
    <div class="col-md-4"><label class="form-label">Alt Mobile</label>
      <input class="form-control" name="alt_mobile" value="<?= esc($v('alt_mobile')) ?>"></div>
    <div class="col-md-4"><label class="form-label">License No</label>
      <input class="form-control" name="license_no" value="<?= esc($v('license_no')) ?>"></div>
    <div class="col-md-4"><label class="form-label">License Expiry</label>
      <input type="date" class="form-control" name="license_expiry" value="<?= esc($v('license_expiry')) ?>"></div>
    <div class="col-md-4">
      <label class="form-label d-block">Status</label>
      <div class="form-check form-switch">
        <input class="form-check-input" type="checkbox" name="status" value="1" id="s" <?= (int)($row['status'] ?? 1) === 1 ? 'checked' : '' ?>>
        <label class="form-check-label" for="s">Active</label>
      </div>
    </div>

    <div class="col-md-3">
      <label class="form-label">Aadhaar last 4</label>
      <input class="form-control" maxlength="4" name="aadhaar_last_4" value="<?= esc($v('aadhaar_last_4')) ?>" placeholder="XXXX">
      <div class="form-text" style="font-size:.78rem;">Store last 4 digits only — never the full number.</div>
    </div>
    <div class="col-md-3">
      <label class="form-label d-block">DL physically verified</label>
      <div class="form-check form-switch">
        <input class="form-check-input" type="checkbox" name="dl_verified" value="1" id="dlv" <?= (int)($row['dl_verified'] ?? 0) === 1 ? 'checked' : '' ?>>
        <label class="form-check-label" for="dlv">Yes — physical DL seen</label>
      </div>
    </div>
    <div class="col-md-3">
      <label class="form-label">KYC status</label>
      <select class="form-select" name="kyc_status" disabled>
        <option><?= esc($row['kyc_status'] ?? 'Pending') ?></option>
      </select>
      <div class="form-text" style="font-size:.78rem;">Use the Verify / Reject buttons below.</div>
    </div>
    <div class="col-md-3">
      <?php if ($isEdit && !empty($row['kyc_verified_at'])): ?>
        <div class="form-text">Verified <?= esc(date('d-m-Y H:i', strtotime($row['kyc_verified_at']))) ?></div>
      <?php endif; ?>
    </div>
  </div>
  <div class="mt-3 d-flex gap-2">
    <button class="btn btn-primary" type="submit">Save</button>
    <a class="btn btn-light" href="<?= site_url('drivers') ?>">Cancel</a>
  </div>
</form>

<?php if ($isEdit): ?>
<hr class="my-4">
<h6 class="mb-3"><i class="bi bi-shield-check"></i> KYC verification</h6>
<form method="post" action="<?= site_url('drivers/' . $row['id'] . '/verify-kyc') ?>" class="row g-2 align-items-end">
  <?= csrf_field() ?>
  <div class="col-md-6">
    <label class="form-label">KYC notes (visible internally)</label>
    <input class="form-control" name="kyc_notes" value="<?= esc($v('kyc_notes')) ?>" placeholder="e.g. Aadhaar checked, DL valid until 2027">
  </div>
  <div class="col-md-3">
    <button class="btn btn-success w-100" name="action" value="verify"><i class="bi bi-check-circle"></i> Mark Verified</button>
  </div>
  <div class="col-md-3">
    <button class="btn btn-outline-danger w-100" name="action" value="reject"><i class="bi bi-x-circle"></i> Reject</button>
  </div>
</form>
<?php endif; ?>

</div></div>
