<?php
$isEdit = !empty($row);
$action = $isEdit ? site_url('cities/' . $row['id']) : site_url('cities/store');
$v = function ($k, $d = '') use ($row) { return old($k, $row[$k] ?? $d); };
?>
<h5 class="mb-3"><?= esc($pageTitle) ?></h5>

<div class="card"><div class="card-body">
<form method="post" action="<?= $action ?>">
  <?= csrf_field() ?>
  <div class="row g-3">
    <div class="col-md-6">
      <label class="form-label">City Name <span class="text-danger">*</span></label>
      <input class="form-control" name="name" required value="<?= esc($v('name')) ?>">
    </div>
    <div class="col-md-6">
      <label class="form-label">State <span class="text-danger">*</span></label>
      <input class="form-control" name="state" required value="<?= esc($v('state')) ?>" list="states-list">
      <datalist id="states-list">
        <?php foreach ([
          'Andhra Pradesh','Arunachal Pradesh','Assam','Bihar','Chhattisgarh','Goa','Gujarat',
          'Haryana','Himachal Pradesh','Jammu and Kashmir','Jharkhand','Karnataka','Kerala',
          'Madhya Pradesh','Maharashtra','Manipur','Meghalaya','Mizoram','Nagaland','Odisha',
          'Punjab','Rajasthan','Sikkim','Tamil Nadu','Telangana','Tripura','Uttar Pradesh',
          'Uttarakhand','West Bengal','Andaman and Nicobar Islands','Chandigarh',
          'Dadra and Nagar Haveli and Daman and Diu','Delhi','Lakshadweep','Puducherry','Ladakh'
        ] as $s): ?>
          <option value="<?= esc($s) ?>"></option>
        <?php endforeach; ?>
      </datalist>
    </div>

    <div class="col-md-2">
      <label class="form-label">GST Code</label>
      <input class="form-control" name="gst_state_code" value="<?= esc($v('gst_state_code')) ?>" maxlength="4" placeholder="e.g. 27">
    </div>
    <div class="col-md-3">
      <label class="form-label">Region</label>
      <select class="form-select" name="region">
        <option value="">—</option>
        <?php foreach ($regions as $r): ?>
          <option value="<?= $r ?>" <?= $v('region') === $r ? 'selected' : '' ?>><?= $r ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2">
      <label class="form-label">Tier</label>
      <select class="form-select" name="tier">
        <option value="">—</option>
        <?php foreach ($tiers as $t): ?>
          <option value="<?= $t ?>" <?= $v('tier') === $t ? 'selected' : '' ?>>Tier <?= $t ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2">
      <label class="form-label">Pincode Prefix</label>
      <input class="form-control" name="pincode_prefix" value="<?= esc($v('pincode_prefix')) ?>" maxlength="6" placeholder="e.g. 4000">
    </div>
    <div class="col-md-3">
      <label class="form-label d-block">Status</label>
      <div class="form-check form-switch">
        <input class="form-check-input" type="checkbox" name="status" value="1" id="st" <?= (int) ($row['status'] ?? 1) === 1 ? 'checked' : '' ?>>
        <label class="form-check-label" for="st">Active</label>
      </div>
    </div>

    <div class="col-12">
      <label class="form-label">Aliases <small class="text-muted">(comma-separated synonyms — e.g. "Bombay, Greater Mumbai")</small></label>
      <input class="form-control" name="aliases" value="<?= esc($v('aliases')) ?>" placeholder="Old or alternate names — matched when normalising free-text input">
    </div>

    <div class="col-md-3">
      <label class="form-label">Latitude</label>
      <input class="form-control" name="latitude" type="number" step="0.0000001" value="<?= esc($v('latitude')) ?>">
    </div>
    <div class="col-md-3">
      <label class="form-label">Longitude</label>
      <input class="form-control" name="longitude" type="number" step="0.0000001" value="<?= esc($v('longitude')) ?>">
    </div>
  </div>

  <div class="mt-4 d-flex gap-2">
    <button class="btn btn-primary" type="submit">Save</button>
    <a class="btn btn-light" href="<?= site_url('cities') ?>">Cancel</a>
  </div>
</form>
</div></div>
