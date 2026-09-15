<?php
$isEdit = !empty($row);
$action = $isEdit ? site_url('rate-contracts/' . $row['id']) : site_url('rate-contracts/store');
$v = function ($k, $d = '') use ($row) { return old($k, $row[$k] ?? $d); };
?>
<?= tpt_toolbar([
    'save_form'      => 'rateContractForm',
    'delete_href'    => $isEdit ? site_url('rate-contracts/' . $row['id'] . '/delete') : null,
    'delete_confirm' => 'Delete contract?',
    'close_href'     => site_url('rate-contracts'),
    'auth'           => $auth,
]) ?>

<div class="tabs" role="tablist" id="rcFormTabs">
  <button type="button" class="tab active" data-bs-toggle="tab" data-bs-target="#rcf-details">Contract Details</button>
  <button type="button" class="tab" data-bs-toggle="tab" data-bs-target="#rcf-lanes">Lanes</button>
  <div class="spacer"></div>
  <?php if ($isEdit): ?><div class="recordnav"><?= esc($row['contract_no']) ?></div><?php endif; ?>
</div>

<form id="rateContractForm" method="post" action="<?= $action ?>">
  <?= csrf_field() ?>
  <div class="tab-content">

    <div class="tab-pane fade show active" id="rcf-details">
      <div class="formwrap">
        <div class="retro-row">
          <div class="retro-field"><label>Contract No :</label><input class="retro-box wide" name="contract_no" value="<?= esc($v('contract_no')) ?>" placeholder="Auto if blank"></div>
          <div class="retro-field" style="width:100%;"><label>Client <span class="retro-required">*</span> :</label>
            <select class="retro-box xwide" style="min-width:300px;" name="client_id" required>
              <option value="">—</option>
              <?php foreach ($clients as $c): ?>
                <option value="<?= (int) $c['id'] ?>" <?= (int) $v('client_id') === (int) $c['id'] ? 'selected' : '' ?>><?= esc($c['company_name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="retro-row">
          <div class="retro-field"><label>Valid From <span class="retro-required">*</span> :</label><input type="date" class="retro-box" name="valid_from" value="<?= esc($v('valid_from')) ?>" required></div>
          <div class="retro-field"><label>Valid To <span class="retro-required">*</span> :</label><input type="date" class="retro-box" name="valid_to" value="<?= esc($v('valid_to')) ?>" required></div>
          <div class="retro-field"><label>Status :</label>
            <select class="retro-box wide" name="status">
              <?php foreach (\App\Models\RateContractModel::STATUSES as $s): ?>
                <option value="<?= $s ?>" <?= $v('status', 'Active') === $s ? 'selected' : '' ?>><?= $s ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="retro-row">
          <div class="retro-field"><label>GST Treatment :</label>
            <select class="retro-box wide" name="gst_treatment">
              <option value="rcm"   <?= $v('gst_treatment', 'fcm5') === 'rcm'   ? 'selected' : '' ?>>RCM</option>
              <option value="fcm5"  <?= $v('gst_treatment', 'fcm5') === 'fcm5'  ? 'selected' : '' ?>>FCM 5%</option>
              <option value="fcm12" <?= $v('gst_treatment', 'fcm5') === 'fcm12' ? 'selected' : '' ?>>FCM 12%</option>
            </select>
          </div>
          <div class="retro-field"><label>TDS % :</label><input type="number" step="0.01" class="retro-box" name="tds_rate" value="<?= esc($v('tds_rate')) ?>" placeholder="2.00"></div>
        </div>
        <div class="retro-row" style="align-items:flex-start;">
          <div class="retro-field" style="width:100%;"><label style="white-space:nowrap;">Notes :</label>
            <input class="retro-box xwide" style="width:100%;" name="notes" value="<?= esc($v('notes')) ?>">
          </div>
        </div>
      </div>
    </div>

    <div class="tab-pane fade" id="rcf-lanes">
      <div class="gridwrap" style="padding:0;">
        <table class="table table-sm grid mb-0" id="lanesTbl">
          <thead>
            <tr><th>Pickup</th><th>Drop</th><th>Vehicle</th><th>Rate ₹</th><th>Free Ld</th><th>Free Ul</th><th>Det ₹/hr</th><th></th></tr>
          </thead>
          <tbody id="lanes">
            <?php $rows2 = !empty($lanes) ? $lanes : [['pickup_city'=>'','drop_city'=>'','vehicle_type'=>'','rate_inr'=>'','free_loading_hrs'=>'','free_unloading_hrs'=>'','detention_per_hour'=>'']]; ?>
            <?php foreach ($rows2 as $l): ?>
              <tr class="lane-row">
                <td><input class="retro-box" style="width:100%;" data-tpt-city name="lane_pickup[]" value="<?= esc($l['pickup_city']) ?>"></td>
                <td><input class="retro-box" style="width:100%;" data-tpt-city name="lane_drop[]" value="<?= esc($l['drop_city']) ?>"></td>
                <td><input class="retro-box" style="width:100%;" name="lane_vehicle[]" value="<?= esc($l['vehicle_type']) ?>" placeholder="Any"></td>
                <td><input class="retro-box" style="width:100%;" type="number" step="0.01" name="lane_rate[]" value="<?= esc($l['rate_inr']) ?>"></td>
                <td><input class="retro-box" style="width:100%;" type="number" name="lane_free_loading[]" value="<?= esc($l['free_loading_hrs']) ?>"></td>
                <td><input class="retro-box" style="width:100%;" type="number" name="lane_free_unloading[]" value="<?= esc($l['free_unloading_hrs']) ?>"></td>
                <td><input class="retro-box" style="width:100%;" type="number" step="0.01" name="lane_detention[]" value="<?= esc($l['detention_per_hour']) ?>"></td>
                <td class="text-center"><button type="button" class="retro-tbtn retro-danger" style="width:auto;flex-direction:row;padding:4px 8px !important;" onclick="this.closest('.lane-row').remove();"><i class="bi bi-trash"></i></button></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <div class="formwrap" style="flex:0 0 auto !important;padding-top:10px;">
        <button type="button" class="retro-tbtn retro-primary" style="width:auto;flex-direction:row;" onclick="(function(){ var t=document.querySelector('.lane-row').cloneNode(true); t.querySelectorAll('input').forEach(i=>{ i.value=''; if (i.dataset.tptCity !== undefined) i.__tptCityBound = false; }); document.getElementById('lanes').appendChild(t); if (window.tptCityScan) window.tptCityScan(); })();"><i class="bi bi-plus-lg"></i>Add lane</button>
      </div>
    </div>

  </div>

  <div class="retro-toolbar mt-3" style="position:static;">
    <button type="submit" class="retro-tbtn retro-primary"><i class="bi bi-save-fill"></i>Save Contract</button>
    <a class="retro-tbtn" href="<?= site_url('rate-contracts') ?>"><i class="bi bi-x-circle"></i>Close</a>
  </div>
</form>
