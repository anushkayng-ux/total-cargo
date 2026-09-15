<?php
$isEdit = !empty($row);
$action = $isEdit ? site_url('drivers/' . $row['id']) : site_url('drivers/store');
$v = function ($k, $d = '') use ($row) { return old($k, $row[$k] ?? $d); };
?>
<?= tpt_toolbar([
    'save_form'   => 'driverForm',
    'delete_href' => $isEdit ? site_url('drivers/' . $row['id'] . '/delete') : null,
    'delete_confirm' => 'Delete this driver?',
    'close_href'  => site_url('drivers'),
    'auth'        => $auth,
]) ?>

<div class="tabs" role="tablist" id="driverFormTabs">
  <button type="button" class="tab active" data-bs-toggle="tab" data-bs-target="#drvf-details">Driver Details</button>
  <button type="button" class="tab" data-bs-toggle="tab" data-bs-target="#drvf-kyc">KYC / Verification</button>
  <div class="spacer"></div>
</div>

<form id="driverForm" method="post" action="<?= $action ?>">
  <?= csrf_field() ?>
  <div class="tab-content">

    <div class="tab-pane fade show active" id="drvf-details">
      <div class="formwrap">
        <div class="retro-row">
          <div class="retro-field" style="width:100%;"><label>Driver Name <span class="retro-required">*</span> :</label><input class="retro-box xwide" style="min-width:300px;" name="driver_name" required value="<?= esc($v('driver_name')) ?>"></div>
        </div>
        <div class="retro-row">
          <div class="retro-field" style="width:100%;"><label>Vendor :</label>
            <select class="retro-box xwide" name="vendor_id">
              <option value="">— None —</option>
              <?php foreach ($vendors as $ven): ?>
                <option value="<?= $ven['id'] ?>" <?= (int) $v('vendor_id') === (int) $ven['id'] ? 'selected' : '' ?>><?= esc($ven['company_name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="retro-row">
          <div class="retro-field"><label>Mobile :</label><input class="retro-box" name="mobile" value="<?= esc($v('mobile')) ?>"></div>
          <div class="retro-field"><label>Alt Mobile :</label><input class="retro-box" name="alt_mobile" value="<?= esc($v('alt_mobile')) ?>"></div>
        </div>
        <div class="retro-row">
          <div class="retro-field"><label>License No :</label><input class="retro-box wide" name="license_no" value="<?= esc($v('license_no')) ?>"></div>
          <div class="retro-field"><label>License Expiry :</label><input type="date" class="retro-box" name="license_expiry" value="<?= esc($v('license_expiry')) ?>"></div>
        </div>
        <div class="retro-row">
          <label class="retro-checkline"><input type="checkbox" name="status" value="1" <?= (int) ($row['status'] ?? 1) === 1 ? 'checked' : '' ?>> Active</label>
        </div>
      </div>
    </div>

    <div class="tab-pane fade" id="drvf-kyc">
      <div class="formwrap">
        <div class="retro-row">
          <div class="retro-field"><label>Aadhaar last 4 :</label><input class="retro-box narrow" maxlength="4" name="aadhaar_last_4" value="<?= esc($v('aadhaar_last_4')) ?>" placeholder="XXXX"></div>
        </div>
        <div class="retro-row" style="margin-top:-6px;">
          <div class="retro-field" style="color:var(--v2-fg-muted);font-size:11.5px;">Store last 4 digits only — never the full number.</div>
        </div>
        <div class="retro-row">
          <label class="retro-checkline"><input type="checkbox" name="dl_verified" value="1" <?= (int) ($row['dl_verified'] ?? 0) === 1 ? 'checked' : '' ?>> DL physically verified — Yes, physical DL seen</label>
        </div>
        <div class="retro-row">
          <div class="retro-field"><label>KYC Status :</label><div class="retro-box empty"><?= esc($row['kyc_status'] ?? 'Pending') ?></div></div>
          <div class="retro-field" style="color:var(--v2-fg-muted);font-size:11.5px;">Use the Verify / Reject buttons below.</div>
        </div>
        <?php if ($isEdit && !empty($row['kyc_verified_at'])): ?>
          <div class="retro-row"><div class="retro-field" style="color:var(--v2-fg-muted);font-size:11.5px;">Verified <?= esc(date('d-m-Y H:i', strtotime($row['kyc_verified_at']))) ?></div></div>
        <?php endif; ?>
        <?php if ($isEdit): ?>
        <hr>
        <h6 class="mb-3"><i class="bi bi-shield-check"></i> KYC verification</h6>
        <div class="retro-row">
          <!-- These two buttons submit #driverForm to a DIFFERENT endpoint via
               formaction/formmethod — avoids nesting a second <form> inside
               #driverForm (invalid HTML) while still being their own action. -->
          <div class="retro-field" style="width:100%;"><label>Notes :</label><input class="retro-box xwide" style="min-width:300px;" name="kyc_notes" placeholder="e.g. Aadhaar checked, DL valid until 2027"></div>
          <button type="submit" formaction="<?= site_url('drivers/' . $row['id'] . '/verify-kyc') ?>" formmethod="post" formnovalidate name="action" value="verify" class="retro-tbtn retro-primary" style="width:auto;flex-direction:row;"><i class="bi bi-check-circle"></i> Mark Verified</button>
          <button type="submit" formaction="<?= site_url('drivers/' . $row['id'] . '/verify-kyc') ?>" formmethod="post" formnovalidate name="action" value="reject" class="retro-tbtn retro-danger" style="width:auto;flex-direction:row;"><i class="bi bi-x-circle"></i> Reject</button>
        </div>
        <?php endif; ?>
      </div>
    </div>

  </div>

  <div class="retro-toolbar mt-3" style="position:static;">
    <button type="submit" class="retro-tbtn retro-primary"><i class="bi bi-save-fill"></i>Save</button>
    <a class="retro-tbtn" href="<?= site_url('drivers') ?>"><i class="bi bi-x-circle"></i>Close</a>
  </div>
</form>
