<?php
$isEdit = !empty($row);
$action = $isEdit ? site_url('rate-contracts/' . $row['id']) : site_url('rate-contracts/store');
$v = function ($k, $d = '') use ($row) { return old($k, $row[$k] ?? $d); };
?>
<h5 class="mb-3"><?= esc($pageTitle) ?></h5>
<div class="card"><div class="card-body">
<form method="post" action="<?= $action ?>">
  <?= csrf_field() ?>
  <div class="row g-3">
    <div class="col-md-3"><label class="form-label">Contract no</label>
      <input class="form-control" name="contract_no" value="<?= esc($v('contract_no')) ?>" placeholder="Auto if blank"></div>
    <div class="col-md-5"><label class="form-label">Client *</label>
      <select class="form-select" name="client_id" required>
        <option value="">—</option>
        <?php foreach ($clients as $c): ?>
          <option value="<?= (int) $c['id'] ?>" <?= (int) $v('client_id') === (int) $c['id'] ? 'selected' : '' ?>><?= esc($c['company_name']) ?></option>
        <?php endforeach; ?>
      </select></div>
    <div class="col-md-2"><label class="form-label">Valid from *</label><input type="date" class="form-control" name="valid_from" value="<?= esc($v('valid_from')) ?>" required></div>
    <div class="col-md-2"><label class="form-label">Valid to *</label><input type="date" class="form-control" name="valid_to" value="<?= esc($v('valid_to')) ?>" required></div>

    <div class="col-md-3"><label class="form-label">Status</label>
      <select class="form-select" name="status">
        <?php foreach (\App\Models\RateContractModel::STATUSES as $s): ?>
          <option value="<?= $s ?>" <?= $v('status','Active') === $s ? 'selected' : '' ?>><?= $s ?></option>
        <?php endforeach; ?>
      </select></div>
    <div class="col-md-3"><label class="form-label">GST treatment</label>
      <select class="form-select" name="gst_treatment">
        <option value="rcm"   <?= $v('gst_treatment','fcm5') === 'rcm'   ? 'selected' : '' ?>>RCM</option>
        <option value="fcm5"  <?= $v('gst_treatment','fcm5') === 'fcm5'  ? 'selected' : '' ?>>FCM 5%</option>
        <option value="fcm12" <?= $v('gst_treatment','fcm5') === 'fcm12' ? 'selected' : '' ?>>FCM 12%</option>
      </select></div>
    <div class="col-md-2"><label class="form-label">TDS %</label><input type="number" step="0.01" class="form-control" name="tds_rate" value="<?= esc($v('tds_rate')) ?>" placeholder="2.00"></div>
    <div class="col-md-4"><label class="form-label">Notes</label><input class="form-control" name="notes" value="<?= esc($v('notes')) ?>"></div>
  </div>

  <hr class="my-4">
  <h6 class="mb-3">Lanes</h6>
  <div id="lanes">
    <?php $rows2 = !empty($lanes) ? $lanes : [['pickup_city'=>'','drop_city'=>'','vehicle_type'=>'','rate_inr'=>'','free_loading_hrs'=>'','free_unloading_hrs'=>'','detention_per_hour'=>'']]; ?>
    <?php foreach ($rows2 as $l): ?>
      <div class="row g-2 mb-2 lane-row align-items-end">
        <div class="col-md-2"><label class="form-label">Pickup</label><input class="form-control form-control-sm" data-tpt-city name="lane_pickup[]" value="<?= esc($l['pickup_city']) ?>"></div>
        <div class="col-md-2"><label class="form-label">Drop</label><input class="form-control form-control-sm" data-tpt-city name="lane_drop[]" value="<?= esc($l['drop_city']) ?>"></div>
        <div class="col-md-2"><label class="form-label">Vehicle</label><input class="form-control form-control-sm" name="lane_vehicle[]" value="<?= esc($l['vehicle_type']) ?>" placeholder="Any"></div>
        <div class="col-md-2"><label class="form-label">Rate ₹</label><input class="form-control form-control-sm" type="number" step="0.01" name="lane_rate[]" value="<?= esc($l['rate_inr']) ?>"></div>
        <div class="col-md-1"><label class="form-label">Free Ld</label><input class="form-control form-control-sm" type="number" name="lane_free_loading[]" value="<?= esc($l['free_loading_hrs']) ?>"></div>
        <div class="col-md-1"><label class="form-label">Free Ul</label><input class="form-control form-control-sm" type="number" name="lane_free_unloading[]" value="<?= esc($l['free_unloading_hrs']) ?>"></div>
        <div class="col-md-1"><label class="form-label">Det ₹/hr</label><input class="form-control form-control-sm" type="number" step="0.01" name="lane_detention[]" value="<?= esc($l['detention_per_hour']) ?>"></div>
        <div class="col-md-1 text-end"><button type="button" class="btn btn-sm btn-light" onclick="this.closest('.lane-row').remove();"><i class="bi bi-trash"></i></button></div>
      </div>
    <?php endforeach; ?>
  </div>
  <button type="button" class="btn btn-sm btn-outline-dark" onclick="(function(){ var t=document.querySelector('.lane-row').cloneNode(true); t.querySelectorAll('input').forEach(i=>{ i.value=''; if (i.dataset.tptCity !== undefined) i.__tptCityBound = false; }); document.getElementById('lanes').appendChild(t); if (window.tptCityScan) window.tptCityScan(); })();"><i class="bi bi-plus-lg"></i> Add lane</button>

  <div class="mt-4 d-flex gap-2">
    <button class="btn btn-primary">Save contract</button>
    <a class="btn btn-light" href="<?= site_url('rate-contracts') ?>">Cancel</a>
  </div>
</form>
</div></div>
