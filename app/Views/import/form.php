<?php
$isClient = ($type === 'clients');
$listUrl  = site_url($type);
$otherType = $isClient ? 'vendors' : 'clients';
?>

<div class="d-flex align-items-center mb-3 gap-2 flex-wrap">
  <h5 class="m-0"><i class="bi bi-upload"></i> <?= esc($pageTitle) ?></h5>
  <div class="ms-auto d-flex gap-2">
    <a class="btn btn-sm btn-outline-secondary" href="<?= site_url('import/' . $otherType) ?>">
      Switch to <?= ucfirst($otherType) ?> import
    </a>
    <a class="btn btn-sm btn-outline-dark" href="<?= site_url("import/$type/template") ?>">
      <i class="bi bi-download"></i> Download CSV template
    </a>
  </div>
</div>

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

      <form method="post" action="<?= site_url("import/$type/parse") ?>" enctype="multipart/form-data">
        <?= csrf_field() ?>

        <div class="mb-2">
          <label class="form-label small text-uppercase">Excel or CSV file</label>
          <input class="form-control" type="file" name="file" accept=".xlsx,.csv,.txt" required>
          <div class="form-text" style="font-size:.78rem;">Max 8 MB. First worksheet only for .xlsx.</div>
        </div>

        <div class="mt-3 d-flex gap-2 flex-wrap">
          <button class="btn btn-primary" type="submit">
            <i class="bi bi-upload"></i> Parse &amp; preview
          </button>
          <a class="btn btn-light" href="<?= esc($listUrl) ?>">Cancel</a>
        </div>
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
