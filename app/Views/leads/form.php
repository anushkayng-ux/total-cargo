<?php
$isEdit = !empty($row);
$action = $isEdit ? site_url('leads/' . $row['id']) : site_url('leads/store');
$v = function ($k, $d = '') use ($row) { return old($k, $row[$k] ?? $d); };
?>
<h5 class="mb-3"><?= esc($pageTitle) ?><?= $isEdit ? ' <small class="text-muted">' . esc($row['lead_no']) . '</small>' : '' ?></h5>
<div class="card"><div class="card-body">
<form method="post" action="<?= $action ?>">
  <?= csrf_field() ?>

  <div class="row g-3">
    <div class="col-md-3"><label class="form-label">Lead Date/Time</label>
      <input type="datetime-local" class="form-control" name="lead_datetime"
             value="<?= esc(str_replace(' ', 'T', substr((string) $v('lead_datetime', date('Y-m-d H:i:00')), 0, 16))) ?>">
    </div>
    <div class="col-md-3"><label class="form-label">Source</label>
      <select class="form-select" name="source_id">
        <option value="">— Select —</option>
        <?php foreach ($sources as $s): ?>
          <option value="<?= $s['id'] ?>" <?= (int)$v('source_id') === (int)$s['id'] ? 'selected' : '' ?>><?= esc($s['source_name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-3"><label class="form-label">Priority</label>
      <select class="form-select" name="priority">
        <?php foreach (['Low','Normal','High','Urgent'] as $p): ?>
          <option value="<?= $p ?>" <?= $v('priority', 'Normal') === $p ? 'selected' : '' ?>><?= $p ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-3"><label class="form-label">Status</label>
      <select class="form-select" name="current_status">
        <?php foreach ($statuses as $s): ?>
          <option value="<?= esc($s) ?>" <?= $v('current_status', 'New') === $s ? 'selected' : '' ?>><?= esc($s) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="col-md-5"><label class="form-label">Existing Client</label>
      <select class="form-select" name="client_id">
        <option value="">— New / Ad-hoc —</option>
        <?php foreach ($clients as $c): ?>
          <option value="<?= $c['id'] ?>" <?= (int)$v('client_id') === (int)$c['id'] ? 'selected' : '' ?>><?= esc($c['company_name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-4"><label class="form-label">Company Name (ad-hoc)</label>
      <input class="form-control" name="company_name" value="<?= esc($v('company_name')) ?>">
    </div>
    <div class="col-md-3"><label class="form-label">Contact Name</label>
      <input class="form-control" name="client_name" value="<?= esc($v('client_name')) ?>">
    </div>

    <div class="col-md-3"><label class="form-label">Mobile</label>
      <input class="form-control" name="mobile" value="<?= esc($v('mobile')) ?>"></div>
    <div class="col-md-3"><label class="form-label">Alt Mobile</label>
      <input class="form-control" name="alt_mobile" value="<?= esc($v('alt_mobile')) ?>"></div>
    <div class="col-md-6"><label class="form-label">Email</label>
      <input type="email" class="form-control" name="email" value="<?= esc($v('email')) ?>"></div>

    <div class="col-md-3"><label class="form-label">Pickup City</label>
      <input class="form-control" name="pickup_city" data-tpt-city value="<?= esc($v('pickup_city')) ?>"></div>
    <div class="col-md-3"><label class="form-label">Pickup State</label>
      <input class="form-control" name="pickup_state" value="<?= esc($v('pickup_state')) ?>"></div>
    <div class="col-md-3"><label class="form-label">Drop City</label>
      <input class="form-control" name="drop_city" data-tpt-city value="<?= esc($v('drop_city')) ?>"></div>
    <div class="col-md-3"><label class="form-label">Drop State</label>
      <input class="form-control" name="drop_state" value="<?= esc($v('drop_state')) ?>"></div>

    <div class="col-md-4"><label class="form-label">Material</label>
      <input class="form-control" name="material_type" value="<?= esc($v('material_type')) ?>"></div>
    <div class="col-md-4"><label class="form-label">Vehicle Required</label>
      <input class="form-control" name="vehicle_type_required" value="<?= esc($v('vehicle_type_required')) ?>"
             placeholder="e.g. 32ft SXL"></div>
    <div class="col-md-2"><label class="form-label">Weight</label>
      <input type="number" step="0.01" class="form-control" name="weight" value="<?= esc($v('weight')) ?>"></div>
    <div class="col-md-2"><label class="form-label">Unit</label>
      <select class="form-select" name="weight_unit">
        <?php foreach (['TON','KG','MT'] as $u): ?>
          <option value="<?= $u ?>" <?= $v('weight_unit','TON') === $u ? 'selected' : '' ?>><?= $u ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="col-md-3"><label class="form-label">Expected Dispatch</label>
      <input type="date" class="form-control" name="expected_dispatch_date" value="<?= esc($v('expected_dispatch_date')) ?>"></div>
    <div class="col-md-4"><label class="form-label">Assigned CRM</label>
      <select class="form-select" name="assigned_crm_user_id">
        <option value="">— Unassigned —</option>
        <?php foreach ($users as $u): ?>
          <option value="<?= $u['id'] ?>" <?= (int)$v('assigned_crm_user_id') === (int)$u['id'] ? 'selected' : '' ?>><?= esc($u['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-5"><label class="form-label">Remarks</label>
      <input class="form-control" name="remarks" value="<?= esc($v('remarks')) ?>"></div>
  </div>

  <div class="mt-3 d-flex gap-2">
    <button class="btn btn-primary" type="submit">Save</button>
    <a class="btn btn-light" href="<?= site_url('leads') ?>">Cancel</a>
  </div>
</form>
</div></div>
