<?php
$isClient  = ($type === 'clients');
$listUrl   = site_url($type);
$otherType = $isClient ? 'vendors' : 'clients';

$extra = '<a class="retro-tbtn" href="' . site_url('import/' . $otherType) . '"><i class="bi bi-arrow-left-right"></i>Switch to ' . ucfirst($otherType) . '</a>'
    . '<a class="retro-tbtn" href="' . site_url("import/$type/template") . '"><i class="bi bi-download"></i>Download template</a>';
?>
<?= tpt_toolbar([
    'save_form'   => 'importUploadForm',
    'close_href'  => $listUrl,
    'extra'       => $extra,
    'auth'        => $auth,
]) ?>
<div class="tabs" role="tablist">
  <div class="tab active">Import <?= esc(ucfirst($type)) ?></div>
  <div class="spacer"></div>
</div>

<div class="formwrap">
  <?php if (!empty($lastSummary)): ?>
    <div class="alert alert-light border mb-3">
      Last <?= esc($type) ?> import: <strong><?= esc($lastSummary['when']) ?></strong> ·
      <strong><?= (int) $lastSummary['created'] ?></strong> created,
      <strong><?= (int) ($lastSummary['updated'] ?? 0) ?></strong> updated,
      <strong><?= (int) $lastSummary['skipped'] ?></strong> skipped.
    </div>
  <?php endif; ?>

  <div class="row g-3">
    <div class="col-lg-7">
      <div class="card"><div class="card-body">
        <h6 class="mb-2">Upload your <?= esc($type) ?> sheet</h6>
        <p class="text-muted small mb-3">Both <strong>.xlsx</strong> and <strong>.csv</strong> are supported. The system reads the first sheet, finds the header row, then maps known columns automatically.</p>

        <form id="importUploadForm" method="post" action="<?= site_url("import/$type/parse") ?>" enctype="multipart/form-data">
          <?= csrf_field() ?>

          <div class="retro-row">
            <div class="retro-field" style="width:100%;"><label style="white-space:nowrap;">Excel or CSV file :</label>
              <input class="retro-box" style="width:100%;" type="file" name="file" accept=".xlsx,.csv,.txt" required>
            </div>
          </div>
          <div style="font-size:11px;color:var(--v2-fg-muted);margin-top:-6px;">Max 8 MB. First worksheet only for .xlsx.</div>
        </form>
      </div></div>
    </div>

    <div class="col-lg-5">
      <div class="card"><div class="card-body" style="font-size:.85rem;">
        <h6 class="mb-2">File columns</h6>
        <?php if ($isClient): ?>
          <ol class="mb-2 ps-3">
            <li><code>S. No.</code></li>
            <li><code>Customer Name</code> <span class="text-danger">*required</span></li>
            <li><code>GST Number</code></li>
          </ol>
        <?php else: ?>
          <ol class="mb-2 ps-3">
            <li><code>S.NO</code></li>
            <li><code>Vendor's Name</code> <span class="text-danger">*required</span></li>
            <li><code>Broker / Fleet Owner</code></li>
            <li><code>Vendor Address</code></li>
            <li><code>Contact Person</code></li>
            <li><code>Contact Number &amp; Email ID</code></li>
            <li><code>Open</code> (vehicle types — open body)</li>
            <li><code>Closed Body</code> (vehicle types)</li>
            <li><code>Origin</code> (Loading Point)</li>
            <li><code>Destination</code> (Offloading Point)</li>
          </ol>
        <?php endif; ?>
        <p class="text-muted mb-0" style="font-size:.78rem;">
          Duplicates by <?= $isClient ? 'GSTIN or' : '' ?> name are detected — you'll choose whether to <em>skip</em> or <em>update</em> them.
        </p>
      </div></div>
    </div>
  </div>
</div>

<div class="retro-toolbar mt-3" style="position:static;">
  <button type="submit" form="importUploadForm" class="retro-tbtn retro-primary"><i class="bi bi-upload"></i>Parse &amp; Preview</button>
  <a class="retro-tbtn" href="<?= esc($listUrl) ?>"><i class="bi bi-x-circle"></i>Close</a>
</div>
