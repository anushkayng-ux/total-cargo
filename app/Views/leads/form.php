<?php
$isEdit = !empty($row);
$action = $isEdit ? site_url('leads/' . $row['id']) : site_url('leads/store');
$v = function ($k, $d = '') use ($row) { return old($k, $row[$k] ?? $d); };
?>
<?= tpt_toolbar([
    'save_form'      => 'leadForm',
    'delete_href'    => $isEdit ? site_url('leads/' . $row['id'] . '/delete') : null,
    'delete_confirm' => 'Delete this lead?',
    'close_href'     => site_url('leads'),
    'auth'           => $auth,
]) ?>

<div class="tabs" id="leadFormTabs">
  <button type="button" class="tab active" data-bs-toggle="tab" data-bs-target="#leadf-details">Lead Details</button>
  <button type="button" class="tab" data-bs-toggle="tab" data-bs-target="#leadf-route">Route &amp; Cargo</button>
  <button type="button" class="tab" data-bs-toggle="tab" data-bs-target="#leadf-assign">Assignment &amp; Remarks</button>
  <div class="spacer"></div>
  <?php if ($isEdit): ?>
    <div class="recordnav">Lead <?= esc($row['lead_no']) ?></div>
  <?php endif; ?>
</div>

<form id="leadForm" method="post" action="<?= $action ?>">
  <?= csrf_field() ?>
  <div class="tab-content">

    <div class="tab-pane fade show active" id="leadf-details">
      <div class="formwrap">
        <div class="retro-row">
          <div class="retro-field"><label>Lead Date/Time :</label>
            <input type="datetime-local" class="retro-box wide" name="lead_datetime"
                   value="<?= esc(str_replace(' ', 'T', substr((string) $v('lead_datetime', date('Y-m-d H:i:00')), 0, 16))) ?>">
          </div>
          <div class="retro-field"><label>Priority :</label>
            <select class="retro-box" name="priority">
              <?php foreach (['Low','Normal','High','Urgent'] as $p): ?>
                <option value="<?= $p ?>" <?= $v('priority', 'Normal') === $p ? 'selected' : '' ?>><?= $p ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="retro-field"><label>Status :</label>
            <select class="retro-box wide" name="current_status">
              <?php foreach ($statuses as $s): ?>
                <option value="<?= esc($s) ?>" <?= $v('current_status', 'New') === $s ? 'selected' : '' ?>><?= esc($s) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="retro-row">
          <div class="retro-field"><label>Source :</label>
            <select class="retro-box wide" name="source_id">
              <option value="">— Select —</option>
              <?php foreach ($sources as $s): ?>
                <option value="<?= $s['id'] ?>" <?= (int) $v('source_id') === (int) $s['id'] ? 'selected' : '' ?>><?= esc($s['source_name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="retro-field" style="width:100%;"><label>Existing Client :</label>
            <select class="retro-box xwide" name="client_id" style="min-width:300px;">
              <option value="">— New / Ad-hoc —</option>
              <?php foreach ($clients as $c): ?>
                <option value="<?= $c['id'] ?>" <?= (int) $v('client_id') === (int) $c['id'] ? 'selected' : '' ?>><?= esc($c['company_name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="retro-row">
          <div class="retro-field"><label>Company Name (ad-hoc) :</label><input class="retro-box wide" name="company_name" value="<?= esc($v('company_name')) ?>"></div>
          <div class="retro-field"><label>Contact Name :</label><input class="retro-box wide" name="client_name" value="<?= esc($v('client_name')) ?>"></div>
        </div>
        <div class="retro-row">
          <div class="retro-field"><label>Mobile :</label><input class="retro-box" name="mobile" value="<?= esc($v('mobile')) ?>"></div>
          <div class="retro-field"><label>Alt Mobile :</label><input class="retro-box" name="alt_mobile" value="<?= esc($v('alt_mobile')) ?>"></div>
          <div class="retro-field"><label>Email :</label><input type="email" class="retro-box wide" name="email" value="<?= esc($v('email')) ?>"></div>
        </div>
      </div>
    </div>

    <div class="tab-pane fade" id="leadf-route">
      <div class="formwrap">
        <div class="retro-row">
          <div class="retro-field"><label>Pickup City :</label><input class="retro-box wide" name="pickup_city" data-tpt-city value="<?= esc($v('pickup_city')) ?>"></div>
          <div class="retro-field"><label>Pickup State :</label><input class="retro-box wide" name="pickup_state" value="<?= esc($v('pickup_state')) ?>"></div>
        </div>
        <div class="retro-row">
          <div class="retro-field"><label>Drop City :</label><input class="retro-box wide" name="drop_city" data-tpt-city value="<?= esc($v('drop_city')) ?>"></div>
          <div class="retro-field"><label>Drop State :</label><input class="retro-box wide" name="drop_state" value="<?= esc($v('drop_state')) ?>"></div>
        </div>
        <div class="retro-row">
          <div class="retro-field"><label>Material :</label><input class="retro-box wide" name="material_type" value="<?= esc($v('material_type')) ?>"></div>
          <div class="retro-field"><label>Vehicle Required :</label><input class="retro-box wide" name="vehicle_type_required" value="<?= esc($v('vehicle_type_required')) ?>" placeholder="e.g. 32ft SXL"></div>
        </div>
        <div class="retro-row">
          <div class="retro-field"><label>Weight :</label><input type="number" step="0.01" class="retro-box" name="weight" value="<?= esc($v('weight')) ?>"></div>
          <div class="retro-field"><label>Unit :</label>
            <select class="retro-box" name="weight_unit">
              <?php foreach (['TON','KG','MT'] as $u): ?>
                <option value="<?= $u ?>" <?= $v('weight_unit', 'TON') === $u ? 'selected' : '' ?>><?= $u ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      </div>
    </div>

    <div class="tab-pane fade" id="leadf-assign">
      <div class="formwrap">
        <div class="retro-row">
          <div class="retro-field"><label>Expected Dispatch :</label><input type="date" class="retro-box" name="expected_dispatch_date" value="<?= esc($v('expected_dispatch_date')) ?>"></div>
          <div class="retro-field"><label>Assigned CRM :</label>
            <select class="retro-box wide" name="assigned_crm_user_id">
              <option value="">— Unassigned —</option>
              <?php foreach ($users as $u): ?>
                <option value="<?= $u['id'] ?>" <?= (int) $v('assigned_crm_user_id') === (int) $u['id'] ? 'selected' : '' ?>><?= esc($u['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="retro-row" style="align-items:flex-start;">
          <div class="retro-field" style="width:100%;"><label style="white-space:nowrap;">Remarks :</label>
            <textarea class="retro-box retro-particulars" name="remarks" rows="2" style="width:100%;"><?= esc($v('remarks')) ?></textarea>
          </div>
        </div>
      </div>
    </div>

  </div>

  <div class="retro-toolbar mt-3" style="position:static;">
    <button type="submit" class="retro-tbtn retro-primary"><i class="bi bi-save-fill"></i>Save</button>
    <a class="retro-tbtn" href="<?= site_url('leads') ?>"><i class="bi bi-x-circle"></i>Close</a>
  </div>
</form>
