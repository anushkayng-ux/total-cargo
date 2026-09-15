<?php
$isEdit = !empty($row);
$action = $isEdit ? site_url('vendor-bills/' . $row['id']) : site_url('vendor-bills/store');
$pre    = $prefill ?? [];
$v = function ($k, $d = '') use ($row, $pre) { return old($k, $row[$k] ?? $pre[$k] ?? $d); };
?>
<?= tpt_toolbar([
    'save_form'      => 'vendorBillForm',
    'delete_href'    => $isEdit ? site_url('vendor-bills/' . $row['id'] . '/delete') : null,
    'delete_confirm' => 'Delete this vendor bill?',
    'close_href'     => site_url('vendor-bills'),
    'auth'           => $auth,
]) ?>

<div class="tabs">
  <div class="tab active">Bill Details</div>
  <div class="spacer"></div>
</div>

<form id="vendorBillForm" method="post" action="<?= $action ?>" enctype="multipart/form-data">
  <?= csrf_field() ?>
  <div class="formwrap">
    <div class="retro-row">
      <div class="retro-field" style="width:48%;"><label>Vendor <span class="retro-required">*</span> :</label>
        <select class="retro-box" style="width:100%;" name="vendor_id" required>
          <option value="">— Select —</option>
          <?php foreach ($vendors as $vn): ?>
            <option value="<?= $vn['id'] ?>" <?= (int) $v('vendor_id') === (int) $vn['id'] ? 'selected' : '' ?>><?= esc($vn['company_name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="retro-field" style="width:48%;margin-left:auto;"><label>Trip :</label>
        <select class="retro-box" style="width:100%;" name="trip_id">
          <option value="">—</option>
          <?php foreach ($trips as $t): ?>
            <option value="<?= $t['id'] ?>" <?= (int) $v('trip_id') === (int) $t['id'] ? 'selected' : '' ?>>
              <?= esc($t['trip_no']) ?> · <?= esc($t['vehicle_number']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <div class="retro-row">
      <div class="retro-field"><label>Bill No :</label><input class="retro-box wide" name="bill_no" value="<?= esc($v('bill_no')) ?>"></div>
      <div class="retro-field"><label>Bill Date :</label><input type="date" class="retro-box" name="bill_date" value="<?= esc($v('bill_date', date('Y-m-d'))) ?>" required></div>
      <div class="retro-field"><label>Bill Amount (INR) :</label><input type="number" step="0.01" class="retro-box" name="bill_amount" value="<?= esc($v('bill_amount', '0')) ?>" required></div>
      <div class="retro-field"><label>Due Date :</label><input type="date" class="retro-box" name="due_date" value="<?= esc($v('due_date')) ?>"></div>
    </div>
    <div class="retro-row" style="align-items:flex-start;">
      <div class="retro-field" style="width:100%;"><label style="white-space:nowrap;">Notes :</label>
        <textarea class="retro-box retro-particulars" name="notes" rows="2" style="width:100%;"><?= esc($v('notes')) ?></textarea>
      </div>
    </div>

    <h6 class="mb-2 mt-3 text-muted" style="font-size:.82rem;text-transform:uppercase;letter-spacing:.5px;">Bill Copy (optional)</h6>
    <div class="retro-row">
      <div class="retro-field" style="width:60%;"><label style="white-space:nowrap;">Upload (PDF/image) :</label>
        <input type="file" class="retro-box" style="width:100%;" name="bill_file" accept="image/*,.pdf">
      </div>
    </div>
    <div style="font-size:11px;color:var(--v2-fg-muted);margin-top:-6px;">Up to 10 MB. Leave blank to keep any previously uploaded copy.</div>
    <?php if ($isEdit && !empty($existingDoc)): ?>
      <div class="retro-row" style="margin-top:10px;">
        <div class="retro-field"><label>Current copy :</label>
          <a class="btn btn-sm btn-light" target="_blank" href="<?= site_url('vendor-bills/' . $row['id'] . '/bill-file') ?>">
            <i class="bi bi-file-earmark-pdf"></i> <?= esc($existingDoc['original_file_name']) ?>
          </a>
        </div>
      </div>
    <?php endif; ?>
  </div>

  <div class="retro-toolbar mt-3" style="position:static;">
    <button type="submit" class="retro-tbtn retro-primary"><i class="bi bi-save-fill"></i>Save</button>
    <a class="retro-tbtn" href="<?= site_url('vendor-bills') ?>"><i class="bi bi-x-circle"></i>Close</a>
  </div>
</form>
