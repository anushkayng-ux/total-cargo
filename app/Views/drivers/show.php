<?php
$val = fn ($v, $empty = '—') => ($v === null || $v === '') ? $empty : esc((string) $v);
$kycCls = match ($row['kyc_status'] ?? 'Pending') {
    'Verified' => 'retro-box',
    'Rejected' => 'retro-box empty',
    default    => 'retro-box empty',
};
?>
<?= tpt_toolbar([
    'new_href'       => site_url('drivers/create'),
    'new_item_label' => 'New Driver',
    'edit_href'      => site_url('drivers/' . $row['id'] . '/edit'),
    'delete_href'    => site_url('drivers/' . $row['id'] . '/delete'),
    'delete_confirm' => 'Delete this driver?',
    'close_href'     => site_url('drivers'),
    'auth'           => $auth,
]) ?>

<div class="tabs" role="tablist" id="driverTabs">
  <button type="button" class="tab active" data-bs-toggle="tab" data-bs-target="#drv-details">Driver Details</button>
  <button type="button" class="tab" data-bs-toggle="tab" data-bs-target="#drv-kyc">KYC / Verification</button>
  <div class="spacer"></div>
  <div class="recordnav">
    <a href="<?= site_url('drivers') ?>"><i class="bi bi-list"></i> List</a> &middot;
    Record <?= (int) $row['id'] ?> &middot; <?= (int) $total ?> total
    <a class="<?= $prevId ? '' : 'disabled' ?>" href="<?= $prevId ? site_url('drivers/' . $prevId) : '#' ?>">&#9664; Prev</a>
    <a class="<?= $nextId ? '' : 'disabled' ?>" href="<?= $nextId ? site_url('drivers/' . $nextId) : '#' ?>">Next &#9654;</a>
  </div>
</div>

<div class="tab-content">
  <div class="tab-pane fade show active" id="drv-details">
    <div class="retro-detail">
      <div class="retro-detail-main formwrap">
        <div class="retro-row">
          <div class="retro-field"><label>Driver Name :</label><div class="retro-box wide"><?= $val($row['driver_name']) ?></div></div>
          <div class="retro-field" style="margin-left:auto;"><label>Status :</label><div class="retro-box"><?= (int) ($row['status'] ?? 1) === 1 ? 'Active' : 'Inactive' ?></div></div>
        </div>
        <div class="retro-row">
          <div class="retro-field"><label>Vendor :</label><div class="retro-box wide"><?= $val($row['vendor_name'] ?? null) ?></div></div>
        </div>
        <div class="retro-row">
          <div class="retro-field"><label>Mobile :</label><div class="retro-box"><?= $val($row['mobile']) ?></div></div>
          <div class="retro-field"><label>Alt. Mobile :</label><div class="retro-box empty"><?= $val($row['alt_mobile']) ?></div></div>
        </div>
        <div class="retro-row">
          <div class="retro-field"><label>License No :</label><div class="retro-box wide"><?= $val($row['license_no']) ?></div></div>
          <div class="retro-field"><label>License Expiry :</label><div class="retro-box"><?= $val($row['license_expiry']) ?></div></div>
        </div>
      </div>
      <div class="retro-detail-side">
        <h4>Notes :</h4>
        <div class="remarksbox"><?= $val($row['kyc_notes'] ?? null, 'No notes recorded.') ?></div>
        <div class="scenic"><div class="sun"></div><div class="box1"></div><div class="box2"></div><div class="box3"></div></div>
      </div>
    </div>
  </div>

  <div class="tab-pane fade" id="drv-kyc">
    <div class="formwrap">
      <div class="retro-row">
        <div class="retro-field"><label>Aadhaar last 4 :</label><div class="retro-box narrow"><?= $val($row['aadhaar_last_4'] ?? null) ?></div></div>
        <div class="retro-field"><label>DL Physically Verified :</label><div class="retro-box"><?= (int) ($row['dl_verified'] ?? 0) === 1 ? 'Yes' : 'No' ?></div></div>
      </div>
      <div class="retro-row">
        <div class="retro-field"><label>KYC Status :</label><div class="<?= $kycCls ?>"><?= $val($row['kyc_status'] ?? null, 'Pending') ?></div></div>
        <div class="retro-field"><label>Verified At :</label><div class="retro-box wide empty"><?= $val($row['kyc_verified_at'] ?? null) ?></div></div>
      </div>
      <div class="retro-row" style="align-items:flex-start;">
        <div class="retro-field" style="width:100%;"><label style="white-space:nowrap;">KYC Notes :</label>
          <div class="retro-particulars"><?= $val($row['kyc_notes'] ?? null, 'No notes recorded.') ?></div>
        </div>
      </div>
      <form method="post" action="<?= site_url('drivers/' . $row['id'] . '/verify-kyc') ?>" class="retro-row">
        <?= csrf_field() ?>
        <div class="retro-field" style="width:100%;"><label>Notes :</label><input class="retro-box xwide" style="min-width:300px;" name="kyc_notes" placeholder="e.g. Aadhaar checked, DL valid until 2027"></div>
        <button type="submit" name="action" value="verify" class="retro-tbtn retro-primary" style="width:auto;flex-direction:row;"><i class="bi bi-check-circle"></i> Mark Verified</button>
        <button type="submit" name="action" value="reject" class="retro-tbtn retro-danger" style="width:auto;flex-direction:row;"><i class="bi bi-x-circle"></i> Reject</button>
      </form>
    </div>
  </div>
</div>
