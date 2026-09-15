<?php
$val = fn ($v, $empty = '—') => ($v === null || $v === '') ? $empty : esc((string) $v);
?>
<?= tpt_toolbar([
    'new_href'       => site_url('vehicles/create'),
    'new_item_label' => 'New Vehicle',
    'edit_href'      => site_url('vehicles/' . $row['id'] . '/edit'),
    'delete_href'    => site_url('vehicles/' . $row['id'] . '/delete'),
    'delete_confirm' => 'Delete this vehicle?',
    'close_href'     => site_url('vehicles'),
    'auth'           => $auth,
]) ?>

<div class="tabs" role="tablist" id="vehicleTabs">
  <button type="button" class="tab active" data-bs-toggle="tab" data-bs-target="#veh-details">Vehicle Details</button>
  <button type="button" class="tab" data-bs-toggle="tab" data-bs-target="#veh-compliance">Compliance Documents</button>
  <button type="button" class="tab" data-bs-toggle="tab" data-bs-target="#veh-gps">GPS / Tracking</button>
  <div class="spacer"></div>
  <div class="recordnav">
    <a href="<?= site_url('vehicles') ?>"><i class="bi bi-list"></i> List</a> &middot;
    Record <?= (int) $row['id'] ?> &middot; <?= (int) $total ?> total
    <a class="<?= $prevId ? '' : 'disabled' ?>" href="<?= $prevId ? site_url('vehicles/' . $prevId) : '#' ?>">&#9664; Prev</a>
    <a class="<?= $nextId ? '' : 'disabled' ?>" href="<?= $nextId ? site_url('vehicles/' . $nextId) : '#' ?>">Next &#9654;</a>
  </div>
</div>

<div class="tab-content">
  <div class="tab-pane fade show active" id="veh-details">
    <div class="retro-detail">
      <div class="retro-detail-main formwrap">
        <div class="retro-row">
          <div class="retro-field"><label>Vehicle Number :</label><div class="retro-box wide"><?= $val($row['vehicle_number']) ?></div></div>
          <div class="retro-field" style="margin-left:auto;"><label>Status :</label><div class="retro-box"><?= (int) ($row['status'] ?? 1) === 1 ? 'Active' : 'Inactive' ?></div></div>
        </div>
        <div class="retro-row">
          <div class="retro-field"><label>Vehicle Type :</label><div class="retro-box wide"><?= $val($row['vehicle_type']) ?></div></div>
          <div class="retro-field"><label>Vendor :</label><div class="retro-box wide"><?= $val($row['vendor_name'] ?? null) ?></div></div>
        </div>
      </div>
      <div class="retro-detail-side">
        <h4>GPS Notes :</h4>
        <div class="remarksbox"><?= $val($row['gps_notes'] ?? null, 'No notes recorded.') ?></div>
        <div class="scenic"><div class="sun"></div><div class="box1"></div><div class="box2"></div><div class="box3"></div></div>
      </div>
    </div>
  </div>

  <div class="tab-pane fade" id="veh-compliance">
    <div class="formwrap">
      <div class="retro-row">
        <div class="retro-field"><label>RC No :</label><div class="retro-box wide"><?= $val($row['rc_no'] ?? null) ?></div></div>
        <div class="retro-field"><label>RC Expiry :</label><div class="retro-box"><?= $val($row['rc_expiry'] ?? null) ?></div></div>
      </div>
      <div class="retro-row">
        <div class="retro-field"><label>Permit No :</label><div class="retro-box wide"><?= $val($row['permit_no'] ?? null) ?></div></div>
        <div class="retro-field"><label>Permit Expiry :</label><div class="retro-box"><?= $val($row['permit_expiry'] ?? null) ?></div></div>
      </div>
      <div class="retro-row">
        <div class="retro-field"><label>Insurance No :</label><div class="retro-box wide"><?= $val($row['insurance_no'] ?? null) ?></div></div>
        <div class="retro-field"><label>Insurance Expiry :</label><div class="retro-box"><?= $val($row['insurance_expiry'] ?? null) ?></div></div>
      </div>
      <div class="retro-row">
        <div class="retro-field"><label>Fitness Expiry :</label><div class="retro-box"><?= $val($row['fitness_expiry'] ?? null) ?></div></div>
        <div class="retro-field"><label>Road Tax Expiry :</label><div class="retro-box"><?= $val($row['tax_expiry'] ?? null) ?></div></div>
      </div>
      <div class="retro-row">
        <div class="retro-field"><label>PUC No :</label><div class="retro-box wide"><?= $val($row['puc_no'] ?? null) ?></div></div>
        <div class="retro-field"><label>PUC Expiry :</label><div class="retro-box"><?= $val($row['puc_expiry'] ?? null) ?></div></div>
      </div>
    </div>
  </div>

  <div class="tab-pane fade" id="veh-gps">
    <div class="formwrap">
      <div class="retro-row">
        <div class="retro-field"><label>Provider :</label><div class="retro-box wide"><?= $val(\App\Models\VehicleModel::GPS_PROVIDERS[$row['gps_provider'] ?? 'none'] ?? $row['gps_provider'] ?? null) ?></div></div>
        <div class="retro-field"><label>Device IMEI :</label><div class="retro-box wide empty"><?= $val($row['gps_device_imei'] ?? null) ?></div></div>
      </div>
      <div class="retro-row">
        <div class="retro-field" style="width:100%;"><label>Vendor Portal URL :</label>
          <div class="retro-box xwide" style="min-width:300px;">
            <?php if (!empty($row['gps_tracking_url'])): ?>
              <a href="<?= esc($row['gps_tracking_url']) ?>" target="_blank" rel="noopener"><?= esc($row['gps_tracking_url']) ?></a>
            <?php else: ?>—<?php endif; ?>
          </div>
        </div>
      </div>
      <div class="retro-row" style="align-items:flex-start;">
        <div class="retro-field" style="width:100%;"><label style="white-space:nowrap;">GPS Notes :</label>
          <div class="retro-particulars"><?= $val($row['gps_notes'] ?? null, 'No notes recorded.') ?></div>
        </div>
      </div>
    </div>
  </div>
</div>
