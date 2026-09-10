<?php
$isEdit = !empty($row);
$action = $isEdit ? site_url('clients/' . $row['id']) : site_url('clients/store');
$v = function ($k, $d = '') use ($row) { return old($k, $row[$k] ?? $d); };
?>
<h5 class="mb-3"><?= esc($pageTitle) ?></h5>
<div class="card"><div class="card-body">
<form method="post" action="<?= $action ?>">
  <?= csrf_field() ?>
  <div class="row g-3">
    <div class="col-md-4"><label class="form-label">Client Code</label>
      <input class="form-control" name="client_code" value="<?= esc($v('client_code')) ?>" placeholder="Auto if blank"></div>
    <div class="col-md-8"><label class="form-label">Company Name <span class="text-danger">*</span></label>
      <input class="form-control" name="company_name" required value="<?= esc($v('company_name')) ?>"></div>

    <div class="col-md-6"><label class="form-label">Contact Person</label>
      <input class="form-control" name="contact_name" value="<?= esc($v('contact_name')) ?>"></div>
    <div class="col-md-3"><label class="form-label">Mobile</label>
      <input class="form-control" name="mobile" value="<?= esc($v('mobile')) ?>"></div>
    <div class="col-md-3"><label class="form-label">Alt Mobile</label>
      <input class="form-control" name="alt_mobile" value="<?= esc($v('alt_mobile')) ?>"></div>

    <div class="col-md-6"><label class="form-label">Email</label>
      <input type="email" class="form-control" name="email" value="<?= esc($v('email')) ?>"></div>
    <div class="col-md-3"><label class="form-label">GSTIN</label>
      <input class="form-control" name="gst_no" value="<?= esc($v('gst_no')) ?>"></div>
    <div class="col-md-3"><label class="form-label">PAN</label>
      <input class="form-control" name="pan_no" value="<?= esc($v('pan_no')) ?>"></div>

    <div class="col-12"><label class="form-label">Address</label>
      <textarea class="form-control" name="address" rows="2"><?= esc($v('address')) ?></textarea></div>

    <div class="col-md-4"><label class="form-label">City</label>
      <input class="form-control" name="city" data-tpt-city value="<?= esc($v('city')) ?>"></div>
    <div class="col-md-4"><label class="form-label">State</label>
      <input class="form-control" name="state" value="<?= esc($v('state')) ?>"></div>
    <div class="col-md-4"><label class="form-label">Pincode</label>
      <input class="form-control" name="pincode" value="<?= esc($v('pincode')) ?>"></div>

    <div class="col-md-3"><label class="form-label">Credit Limit</label>
      <input type="number" step="0.01" class="form-control" name="credit_limit" value="<?= esc($v('credit_limit', '0')) ?>"></div>
    <div class="col-md-3"><label class="form-label">Credit Days</label>
      <input type="number" class="form-control" name="credit_days" value="<?= esc($v('credit_days', '0')) ?>"></div>
    <div class="col-md-3"><label class="form-label">Account Manager</label>
      <select class="form-select" name="account_manager_user_id">
        <option value="">— Unassigned —</option>
        <?php foreach (($managers ?? []) as $m): ?>
          <option value="<?= (int) $m['id'] ?>" <?= (int) ($v('account_manager_user_id') ?: 0) === (int) $m['id'] ? 'selected' : '' ?>><?= esc($m['name']) ?></option>
        <?php endforeach; ?>
      </select>
      <div class="form-text" style="font-size:.78rem;">Portal booking requests from this client are auto-assigned to them.</div>
    </div>
    <div class="col-md-3"><label class="form-label d-block">Status</label>
      <div class="form-check form-switch">
        <input class="form-check-input" type="checkbox" name="status" value="1" id="s" <?= (int)($row['status'] ?? 1) === 1 ? 'checked' : '' ?>>
        <label class="form-check-label" for="s">Active</label>
      </div></div>
  </div>

  <hr class="my-4">
  <h6 class="mb-3"><i class="bi bi-globe2"></i> Client Portal</h6>
  <div class="row g-3">
    <div class="col-md-3">
      <label class="form-label d-block">Portal access</label>
      <div class="form-check form-switch">
        <input class="form-check-input" type="checkbox" name="portal_enabled" value="1" id="pe" <?= (int)($row['portal_enabled'] ?? 0) === 1 ? 'checked' : '' ?>>
        <label class="form-check-label" for="pe">Enabled</label>
      </div>
    </div>
    <div class="col-md-3">
      <label class="form-label">KYC status</label>
      <select class="form-select" name="kyc_status">
        <?php foreach (['Pending','Verified','Rejected'] as $k): ?>
          <option value="<?= $k ?>" <?= ($row['kyc_status'] ?? 'Pending') === $k ? 'selected' : '' ?>><?= $k ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-6">
      <label class="form-label">Portal notes (internal)</label>
      <input class="form-control" name="portal_notes" value="<?= esc($v('portal_notes')) ?>" placeholder="Visible to staff only">
    </div>
    <?php if ($isEdit): ?>
      <div class="col-12">
        <a href="<?= site_url('clients/' . $row['id'] . '/portal-users') ?>" class="btn btn-sm btn-light">
          <i class="bi bi-people"></i> Manage portal users
        </a>
      </div>
    <?php endif; ?>
  </div>

  <hr class="my-4">
  <h6 class="mb-3"><i class="bi bi-receipt-cutoff"></i> Tax &amp; detention</h6>
  <div class="row g-3">
    <div class="col-md-4">
      <label class="form-label">GST treatment <span class="text-danger">*</span></label>
      <select class="form-select" name="gst_treatment">
        <?php foreach (\App\Models\ClientModel::GST_TREATMENTS as $k => $label): ?>
          <option value="<?= $k ?>" <?= ($v('gst_treatment','fcm5') === $k) ? 'selected' : '' ?>><?= esc($label) ?></option>
        <?php endforeach; ?>
      </select>
      <div class="form-text" style="font-size:.78rem;">RCM = client pays GST. FCM = transporter pays. Mismatch = GST notice.</div>
    </div>
    <div class="col-md-2">
      <label class="form-label">TDS % deducted</label>
      <input class="form-control" type="number" step="0.01" name="tds_rate" value="<?= esc($v('tds_rate','2')) ?>">
      <div class="form-text" style="font-size:.78rem;">Sec 194C — usually 2%.</div>
    </div>
    <div class="col-md-2">
      <label class="form-label">Free loading (hrs)</label>
      <input class="form-control" type="number" name="detention_free_hours_loading" value="<?= esc($v('detention_free_hours_loading','4')) ?>">
    </div>
    <div class="col-md-2">
      <label class="form-label">Free unloading (hrs)</label>
      <input class="form-control" type="number" name="detention_free_hours_unloading" value="<?= esc($v('detention_free_hours_unloading','4')) ?>">
    </div>
    <div class="col-md-2">
      <label class="form-label">Detention ₹/hr</label>
      <input class="form-control" type="number" step="0.01" name="detention_rate_per_hour" value="<?= esc($v('detention_rate_per_hour','150')) ?>">
    </div>
    <div class="col-md-3">
      <div class="form-check form-switch">
        <input class="form-check-input" type="checkbox" name="is_msme" value="1" id="msme" <?= (int)($row['is_msme'] ?? 0) === 1 ? 'checked' : '' ?>>
        <label class="form-check-label" for="msme">MSME registered (45-day payment rule applies)</label>
      </div>
    </div>
  </div>

  <div class="mt-3 d-flex gap-2">
    <button class="btn btn-primary" type="submit">Save</button>
    <a class="btn btn-light" href="<?= site_url('clients') ?>">Cancel</a>
  </div>
</form>
</div></div>
