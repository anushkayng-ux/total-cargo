<?php
$isEdit = !empty($row);
$action = $isEdit ? site_url('cities/' . $row['id']) : site_url('cities/store');
$v = function ($k, $d = '') use ($row) { return old($k, $row[$k] ?? $d); };
?>
<?= tpt_toolbar([
    'save_form'      => 'cityForm',
    'delete_href'    => ($isEdit && $auth->can('cities', 'can_delete')) ? site_url('cities/' . $row['id'] . '/delete') : null,
    'delete_confirm' => 'Delete this city?',
    'close_href'     => site_url('cities'),
    'auth'           => $auth,
]) ?>
<div class="tabs">
  <div class="tab active">City Details</div>
  <div class="spacer"></div>
</div>

<form id="cityForm" method="post" action="<?= $action ?>">
  <?= csrf_field() ?>
  <div class="formwrap">
    <div class="retro-row">
      <div class="retro-field" style="width:48%;"><label>City Name <span class="retro-required">*</span> :</label>
        <input class="retro-box" style="width:100%;" name="name" required value="<?= esc($v('name')) ?>">
      </div>
      <div class="retro-field" style="width:48%;margin-left:auto;"><label>State <span class="retro-required">*</span> :</label>
        <input class="retro-box" style="width:100%;" name="state" required value="<?= esc($v('state')) ?>" list="states-list">
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
    </div>

    <div class="retro-row">
      <div class="retro-field"><label>GST Code :</label><input class="retro-box narrow" name="gst_state_code" value="<?= esc($v('gst_state_code')) ?>" maxlength="4" placeholder="e.g. 27"></div>
      <div class="retro-field"><label>Region :</label>
        <select class="retro-box wide" name="region">
          <option value="">—</option>
          <?php foreach ($regions as $r): ?>
            <option value="<?= $r ?>" <?= $v('region') === $r ? 'selected' : '' ?>><?= $r ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="retro-field"><label>Tier :</label>
        <select class="retro-box" name="tier">
          <option value="">—</option>
          <?php foreach ($tiers as $t): ?>
            <option value="<?= $t ?>" <?= $v('tier') === $t ? 'selected' : '' ?>>Tier <?= $t ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="retro-field"><label>Pincode Prefix :</label><input class="retro-box" name="pincode_prefix" value="<?= esc($v('pincode_prefix')) ?>" maxlength="6" placeholder="e.g. 4000"></div>
      <label class="retro-checkline"><input type="checkbox" name="status" value="1" <?= (int) ($row['status'] ?? 1) === 1 ? 'checked' : '' ?>> Active</label>
    </div>

    <div class="retro-row" style="align-items:flex-start;">
      <div class="retro-field" style="width:100%;"><label style="white-space:nowrap;">Aliases :</label>
        <input class="retro-box xwide" style="width:100%;" name="aliases" value="<?= esc($v('aliases')) ?>" placeholder="Old or alternate names — matched when normalising free-text input">
      </div>
    </div>
    <div style="font-size:11px;color:var(--v2-fg-muted);margin-top:-8px;">Comma-separated synonyms — e.g. "Bombay, Greater Mumbai".</div>

    <div class="retro-row" style="margin-top:10px;">
      <div class="retro-field"><label>Latitude :</label><input class="retro-box wide" name="latitude" type="number" step="0.0000001" value="<?= esc($v('latitude')) ?>"></div>
      <div class="retro-field"><label>Longitude :</label><input class="retro-box wide" name="longitude" type="number" step="0.0000001" value="<?= esc($v('longitude')) ?>"></div>
    </div>
  </div>

  <div class="retro-toolbar mt-3" style="position:static;">
    <button type="submit" class="retro-tbtn retro-primary"><i class="bi bi-save-fill"></i>Save</button>
    <a class="retro-tbtn" href="<?= site_url('cities') ?>"><i class="bi bi-x-circle"></i>Close</a>
  </div>
</form>
