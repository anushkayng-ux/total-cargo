<?php
$isEdit = !empty($row);
$action = $isEdit ? site_url('vehicles/' . $row['id']) : site_url('vehicles/store');
$v = function ($k, $d = '') use ($row) { return old($k, $row[$k] ?? $d); };
?>
<?= tpt_toolbar([
    'save_form'      => 'vehicleForm',
    'delete_href'    => $isEdit ? site_url('vehicles/' . $row['id'] . '/delete') : null,
    'delete_confirm' => 'Delete this vehicle?',
    'close_href'     => site_url('vehicles'),
    'auth'           => $auth,
]) ?>

<div class="tabs" id="vehicleFormTabs">
  <button type="button" class="tab active" data-bs-toggle="tab" data-bs-target="#vehf-details">Vehicle Details</button>
  <button type="button" class="tab" data-bs-toggle="tab" data-bs-target="#vehf-compliance">Compliance Documents</button>
  <button type="button" class="tab" data-bs-toggle="tab" data-bs-target="#vehf-gps">GPS / Tracking</button>
  <div class="spacer"></div>
</div>

<form id="vehicleForm" method="post" action="<?= $action ?>">
  <?= csrf_field() ?>
  <div class="tab-content">

    <div class="tab-pane fade show active" id="vehf-details">
      <div class="formwrap">
        <div class="retro-row">
          <div class="retro-field"><label>Vehicle Number <span class="retro-required">*</span> :</label><input class="retro-box wide" name="vehicle_number" required value="<?= esc($v('vehicle_number')) ?>"></div>
        </div>
        <div class="retro-row">
          <div class="retro-field"><label>Vehicle Type :</label><input class="retro-box wide" name="vehicle_type" value="<?= esc($v('vehicle_type')) ?>" placeholder="e.g. 32ft SXL, Container 20ft"></div>
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
          <label class="retro-checkline"><input type="checkbox" name="status" value="1" <?= (int) ($row['status'] ?? 1) === 1 ? 'checked' : '' ?>> Active</label>
        </div>
      </div>
    </div>

    <div class="tab-pane fade" id="vehf-compliance">
      <div class="formwrap">
        <div class="retro-row" style="margin-bottom:16px;">
          <div class="retro-field" style="color:var(--v2-fg-muted);font-size:11.5px;">Admins get alerted before any of these expire.</div>
        </div>
        <div class="retro-row">
          <div class="retro-field"><label>RC No :</label><input class="retro-box wide" name="rc_no" value="<?= esc($v('rc_no')) ?>"></div>
          <div class="retro-field"><label>RC Expiry :</label><input type="date" class="retro-box" name="rc_expiry" value="<?= esc($v('rc_expiry')) ?>"></div>
        </div>
        <div class="retro-row">
          <div class="retro-field"><label>Permit No :</label><input class="retro-box wide" name="permit_no" value="<?= esc($v('permit_no')) ?>"></div>
          <div class="retro-field"><label>Permit Expiry :</label><input type="date" class="retro-box" name="permit_expiry" value="<?= esc($v('permit_expiry')) ?>"></div>
        </div>
        <div class="retro-row">
          <div class="retro-field"><label>Insurance No :</label><input class="retro-box wide" name="insurance_no" value="<?= esc($v('insurance_no')) ?>"></div>
          <div class="retro-field"><label>Insurance Expiry :</label><input type="date" class="retro-box" name="insurance_expiry" value="<?= esc($v('insurance_expiry')) ?>"></div>
        </div>
        <div class="retro-row">
          <div class="retro-field"><label>Fitness Expiry :</label><input type="date" class="retro-box" name="fitness_expiry" value="<?= esc($v('fitness_expiry')) ?>"></div>
          <div class="retro-field"><label>Road Tax Expiry :</label><input type="date" class="retro-box" name="tax_expiry" value="<?= esc($v('tax_expiry')) ?>"></div>
        </div>
        <div class="retro-row">
          <div class="retro-field"><label>PUC No :</label><input class="retro-box wide" name="puc_no" value="<?= esc($v('puc_no')) ?>"></div>
          <div class="retro-field"><label>PUC Expiry :</label><input type="date" class="retro-box" name="puc_expiry" value="<?= esc($v('puc_expiry')) ?>"></div>
        </div>
      </div>
    </div>

    <div class="tab-pane fade" id="vehf-gps">
      <div class="formwrap">
        <div class="retro-row" style="margin-bottom:16px;">
          <div class="retro-field" style="color:var(--v2-fg-muted);font-size:11.5px;max-width:640px;">If the vendor has a GPS device on this truck, set the provider so the cron can poll it. If they only share a portal link, paste it below — staff get an "Open vendor's tracker" button on the trip.</div>
        </div>
        <div class="retro-row">
          <div class="retro-field"><label>Provider :</label>
            <select class="retro-box wide" name="gps_provider">
              <?php foreach (\App\Models\VehicleModel::GPS_PROVIDERS as $k => $label): ?>
                <option value="<?= $k ?>" <?= $v('gps_provider', 'none') === $k ? 'selected' : '' ?>><?= esc($label) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="retro-field"><label>Device IMEI :</label><input class="retro-box wide" name="gps_device_imei" value="<?= esc($v('gps_device_imei')) ?>" placeholder="15-digit IMEI (if known)"></div>
        </div>
        <div class="retro-row">
          <div class="retro-field" style="width:100%;"><label>Vendor Portal URL :</label><input class="retro-box xwide" style="min-width:400px;" name="gps_tracking_url" value="<?= esc($v('gps_tracking_url')) ?>" placeholder="https://track.example.com/abc123"></div>
        </div>
        <div class="retro-row">
          <div class="retro-field" style="width:100%;"><label>GPS Notes :</label><input class="retro-box xwide" style="min-width:400px;" name="gps_notes" value="<?= esc($v('gps_notes')) ?>" placeholder="e.g. Device subscription expires Mar 2027"></div>
        </div>
      </div>
    </div>

  </div>

  <div class="retro-toolbar mt-3" style="position:static;">
    <button type="submit" class="retro-tbtn retro-primary"><i class="bi bi-save-fill"></i>Save</button>
    <a class="retro-tbtn" href="<?= site_url('vehicles') ?>"><i class="bi bi-x-circle"></i>Close</a>
  </div>
</form>
