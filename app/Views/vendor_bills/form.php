<?php
$isEdit = !empty($row);
$action = $isEdit ? site_url('vendor-bills/' . $row['id']) : site_url('vendor-bills/store');
$pre    = $prefill ?? [];
$v = function ($k, $d = '') use ($row, $pre) { return old($k, $row[$k] ?? $pre[$k] ?? $d); };
?>
<h5 class="mb-3"><?= esc($pageTitle) ?></h5>
<div class="card"><div class="card-body">
<form method="post" action="<?= $action ?>" enctype="multipart/form-data">
  <?= csrf_field() ?>
  <div class="row g-3">
    <div class="col-md-6"><label class="form-label">Vendor <span class="text-danger">*</span></label>
      <select class="form-select" name="vendor_id" required>
        <option value="">— Select —</option>
        <?php foreach ($vendors as $vn): ?>
          <option value="<?= $vn['id'] ?>" <?= (int)$v('vendor_id') === (int)$vn['id'] ? 'selected' : '' ?>><?= esc($vn['company_name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-6"><label class="form-label">Trip</label>
      <select class="form-select" name="trip_id">
        <option value="">—</option>
        <?php foreach ($trips as $t): ?>
          <option value="<?= $t['id'] ?>" <?= (int)$v('trip_id') === (int)$t['id'] ? 'selected' : '' ?>>
            <?= esc($t['trip_no']) ?> · <?= esc($t['vehicle_number']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="col-md-3"><label class="form-label">Bill No</label>
      <input class="form-control" name="bill_no" value="<?= esc($v('bill_no')) ?>"></div>
    <div class="col-md-3"><label class="form-label">Bill Date</label>
      <input type="date" class="form-control" name="bill_date" value="<?= esc($v('bill_date', date('Y-m-d'))) ?>" required></div>
    <div class="col-md-3"><label class="form-label">Bill Amount (INR)</label>
      <input type="number" step="0.01" class="form-control" name="bill_amount" value="<?= esc($v('bill_amount', '0')) ?>" required></div>
    <div class="col-md-3"><label class="form-label">Due Date</label>
      <input type="date" class="form-control" name="due_date" value="<?= esc($v('due_date')) ?>"></div>

    <div class="col-12"><label class="form-label">Notes</label>
      <textarea class="form-control" name="notes" rows="2"><?= esc($v('notes')) ?></textarea></div>

    <div class="col-12"><hr class="mt-1 mb-0"><small class="text-muted">Bill copy (optional)</small></div>
    <div class="col-md-8">
      <label class="form-label">Upload vendor bill (PDF / image)</label>
      <input type="file" class="form-control" name="bill_file" accept="image/*,.pdf">
      <small class="text-muted">Up to 10 MB. Leave blank to keep any previously uploaded copy.</small>
    </div>
    <?php if ($isEdit && !empty($existingDoc)): ?>
      <div class="col-md-4 d-flex align-items-end">
        <div class="w-100" style="font-size:.85rem;">
          <div class="text-muted">Current bill copy</div>
          <a class="btn btn-sm btn-light mt-1 w-100 text-start" target="_blank" href="<?= site_url('vendor-bills/' . $row['id'] . '/bill-file') ?>">
            <i class="bi bi-file-earmark-pdf"></i> <?= esc($existingDoc['original_file_name']) ?>
          </a>
        </div>
      </div>
    <?php endif; ?>
  </div>
  <div class="mt-3 d-flex gap-2">
    <button class="btn btn-primary" type="submit">Save</button>
    <a class="btn btn-light" href="<?= site_url('vendor-bills') ?>">Cancel</a>
  </div>
</form>
</div></div>
