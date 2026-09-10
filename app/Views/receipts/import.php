<h5 class="mb-3"><i class="bi bi-bank"></i> <?= esc($pageTitle) ?></h5>

<?php if (!empty($lastSummary)): ?>
  <div class="alert alert-light border mb-3">
    Last import: <?= esc($lastSummary['when']) ?> · <strong><?= (int) $lastSummary['created'] ?></strong> receipts created,
    <strong><?= (int) $lastSummary['skipped'] ?></strong> skipped.
  </div>
<?php endif; ?>

<div class="card"><div class="card-body">
  <p class="text-muted">Upload a bank-statement CSV (HDFC, ICICI, SBI, Axis or any standard format). We'll detect credits, match them against open invoices, and let you review before creating receipts.</p>

  <form method="post" action="<?= site_url('receipts/import/parse') ?>" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <div class="mb-2">
      <label class="form-label small text-uppercase">CSV file</label>
      <input class="form-control" type="file" name="csv" accept=".csv,text/csv" required>
      <div class="form-text" style="font-size:.78rem;">Max 5 MB. Only credit-side transactions are imported. UTR references are deduplicated against existing receipts.</div>
    </div>
    <div class="mt-3 d-flex gap-2">
      <button class="btn btn-primary" type="submit"><i class="bi bi-upload"></i> Parse &amp; preview</button>
      <a class="btn btn-light" href="<?= site_url('receipts') ?>">Cancel</a>
    </div>
  </form>
</div></div>

<div class="card mt-3"><div class="card-body" style="font-size:.85rem;">
  <strong>How matching works:</strong>
  <ol class="mb-0 mt-2">
    <li><strong>UTR in narration</strong> matched against existing receipts → flagged as duplicate, skipped.</li>
    <li><strong>Invoice number in narration</strong> + amount equals invoice balance → 95% confidence.</li>
    <li><strong>Amount + due-date proximity</strong> when exactly one invoice matches → 70–85%.</li>
    <li><strong>Client company name</strong> in narration (Pvt Ltd / Limited stripped) → 45%, surface for confirmation.</li>
  </ol>
</div></div>
