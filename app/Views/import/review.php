<?php
$listUrl = site_url($type);
$isVendor = ($type === 'vendors');
?>

<div class="d-flex align-items-center mb-3 gap-2 flex-wrap">
  <h5 class="m-0"><i class="bi bi-list-check"></i> <?= esc($pageTitle) ?></h5>
  <a class="btn btn-sm btn-light ms-auto" href="<?= site_url("import/$type") ?>"><i class="bi bi-arrow-left"></i> Re-upload</a>
</div>

<?php if (empty($rows)): ?>
  <div class="alert alert-light border text-center py-4">
    <i class="bi bi-inbox" style="font-size:1.8rem;opacity:.4;"></i>
    <div class="mt-1">Nothing to review. <a href="<?= site_url("import/$type") ?>">Upload a sheet</a> to begin.</div>
  </div>
<?php else:
  $totalNew = 0; $totalDup = 0; $totalErr = 0;
  foreach ($rows as $r) {
      if (!empty($r['_errors']))         $totalErr++;
      elseif (!empty($r['_duplicate']))  $totalDup++;
      else                               $totalNew++;
  }
  if ($isVendor) {
      $colsToShow = ['company_name','vendor_type','owner_name','mobile','city','address'];
      $extraCols  = ['_route_origin' => 'Origin', '_route_destination' => 'Destination', '_vehicle_open' => 'Open', '_vehicle_closed' => 'Closed Body'];
  } else {
      $colsToShow = ['company_name','gst_no','pan_no','contact_name','mobile','city','state'];
      $extraCols  = [];
  }
?>

<div class="row g-2 mb-3">
  <div class="col-sm-4">
    <div class="card border-success h-100"><div class="card-body py-2 px-3 d-flex align-items-center justify-content-between">
      <span><i class="bi bi-plus-circle text-success"></i> New</span>
      <strong style="font-size:1.4rem;"><?= $totalNew ?></strong>
    </div></div>
  </div>
  <div class="col-sm-4">
    <div class="card border-warning h-100"><div class="card-body py-2 px-3 d-flex align-items-center justify-content-between">
      <span><i class="bi bi-arrow-repeat text-warning"></i> Duplicates</span>
      <strong style="font-size:1.4rem;"><?= $totalDup ?></strong>
    </div></div>
  </div>
  <div class="col-sm-4">
    <div class="card border-danger h-100"><div class="card-body py-2 px-3 d-flex align-items-center justify-content-between">
      <span><i class="bi bi-exclamation-circle text-danger"></i> Errors (skipped)</span>
      <strong style="font-size:1.4rem;"><?= $totalErr ?></strong>
    </div></div>
  </div>
</div>

<?php if (!empty($mapping)): ?>
<div class="card mb-3"><div class="card-body py-2 px-3" style="font-size:.85rem;">
  <strong>Detected columns:</strong>
  <?php foreach ($mapping as $field => $colIdx): ?>
    <span class="badge-soft me-1 mb-1" style="font-size:.72rem;">
      <?= esc(ucwords(str_replace('_',' ',$field))) ?>
      <span class="text-muted">←</span>
      <code style="background:transparent;padding:0;"><?= esc($header[$colIdx] ?? '') ?></code>
    </span>
  <?php endforeach; ?>
</div></div>
<?php endif; ?>

<form method="post" action="<?= site_url("import/$type/confirm") ?>">
  <?= csrf_field() ?>

  <div class="card mb-3"><div class="card-body py-3">
    <label class="form-label small text-uppercase d-block mb-2 fw-bold">If a duplicate is found</label>
    <div class="d-flex gap-3 flex-wrap">
      <div class="form-check">
        <input class="form-check-input" type="radio" name="on_duplicate" id="dup_skip" value="skip" checked>
        <label class="form-check-label" for="dup_skip"><strong>Skip</strong> — keep existing record</label>
      </div>
      <div class="form-check">
        <input class="form-check-input" type="radio" name="on_duplicate" id="dup_update" value="update">
        <label class="form-check-label" for="dup_update"><strong>Update</strong> existing record with new values</label>
      </div>
    </div>
  </div></div>

  <div class="card">
    <div class="table-responsive">
      <table class="table table-sm table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th style="width:36px;"><input type="checkbox" id="selAll" aria-label="Select all"></th>
            <th style="width:110px;">Status</th>
            <?php foreach ($colsToShow as $c): ?>
              <th><?= esc(ucwords(str_replace('_',' ',$c))) ?></th>
            <?php endforeach; ?>
            <?php foreach ($extraCols as $label): ?>
              <th><?= esc($label) ?></th>
            <?php endforeach; ?>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $i => $r):
            $hasErr = !empty($r['_errors']);
            $isDup  = !empty($r['_duplicate']);
            $cls = $hasErr ? 'table-danger' : ($isDup ? 'table-warning' : '');
            $auto = !$hasErr;
          ?>
            <tr class="<?= $cls ?>">
              <td>
                <input type="checkbox" name="idx[]" value="<?= (int) $i ?>" <?= $auto ? 'checked' : '' ?>
                       <?= $hasErr ? 'disabled' : '' ?> aria-label="Import row <?= (int) $i + 1 ?>">
              </td>
              <td>
                <?php if ($hasErr): ?>
                  <span class="badge-soft badge-danger"><i class="bi bi-exclamation-circle"></i> Error</span>
                  <div class="small text-danger mt-1"><?= esc(implode(' · ', $r['_errors'])) ?></div>
                <?php elseif ($isDup): ?>
                  <span class="badge-soft badge-warn" title="Matched by <?= esc($r['_duplicate']['by']) ?>">
                    <i class="bi bi-arrow-repeat"></i> Duplicate
                  </span>
                  <div class="small text-muted mt-1">by <?= esc($r['_duplicate']['by']) ?></div>
                <?php else: ?>
                  <span class="badge-soft badge-ok"><i class="bi bi-plus-circle"></i> New</span>
                <?php endif; ?>
              </td>
              <?php foreach ($colsToShow as $c): ?>
                <td style="font-size:.85rem;"><?= esc($r[$c] ?? '') ?: '<span class="text-muted">—</span>' ?></td>
              <?php endforeach; ?>
              <?php foreach ($extraCols as $k => $label): ?>
                <td style="font-size:.78rem;color:#555;"><?= esc($r[$k] ?? '') ?: '<span class="text-muted">—</span>' ?></td>
              <?php endforeach; ?>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="mt-3 d-flex gap-2 align-items-center">
    <button class="btn btn-primary" type="submit" data-confirm="Import the selected rows?">
      <i class="bi bi-check2-circle"></i> Import selected
    </button>
    <a class="btn btn-light" href="<?= site_url("import/$type") ?>">Cancel</a>
    <span class="text-muted ms-2 small">Rows with errors are disabled and will be skipped automatically.</span>
  </div>
</form>

<script>
document.getElementById('selAll')?.addEventListener('change', function (e) {
  document.querySelectorAll('input[name="idx[]"]:not([disabled])').forEach(c => c.checked = e.target.checked);
});
</script>

<?php endif; ?>
