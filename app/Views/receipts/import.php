<?= tpt_toolbar([
    'save_form'  => 'bankImportForm',
    'close_href' => site_url('receipts'),
    'auth'       => $auth,
]) ?>
<div class="tabs">
  <div class="tab active">Bank Import</div>
  <div class="spacer"></div>
</div>

<div class="formwrap">
  <?php if (!empty($lastSummary)): ?>
    <div class="alert alert-light border mb-3">
      Last import: <?= esc($lastSummary['when']) ?> · <strong><?= (int) $lastSummary['created'] ?></strong> receipts created,
      <strong><?= (int) $lastSummary['skipped'] ?></strong> skipped.
    </div>
  <?php endif; ?>

  <p class="text-muted">Upload a bank-statement CSV (HDFC, ICICI, SBI, Axis or any standard format). We'll detect credits, match them against open invoices, and let you review before creating receipts.</p>

  <form id="bankImportForm" method="post" action="<?= site_url('receipts/import/parse') ?>" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <div class="retro-row">
      <div class="retro-field" style="width:60%;"><label style="white-space:nowrap;">CSV file :</label>
        <input class="retro-box" style="width:100%;" type="file" name="csv" accept=".csv,text/csv" required>
      </div>
    </div>
    <div style="font-size:11px;color:var(--v2-fg-muted);margin-top:-6px;">Max 5 MB. Only credit-side transactions are imported. UTR references are deduplicated against existing receipts.</div>
  </form>

  <h6 class="mb-1 mt-4 text-muted" style="font-size:.82rem;text-transform:uppercase;letter-spacing:.5px;">How matching works</h6>
  <ol class="mb-0 mt-2" style="font-size:.85rem;">
    <li><strong>UTR in narration</strong> matched against existing receipts → flagged as duplicate, skipped.</li>
    <li><strong>Invoice number in narration</strong> + amount equals invoice balance → 95% confidence.</li>
    <li><strong>Amount + due-date proximity</strong> when exactly one invoice matches → 70–85%.</li>
    <li><strong>Client company name</strong> in narration (Pvt Ltd / Limited stripped) → 45%, surface for confirmation.</li>
  </ol>
</div>

<div class="retro-toolbar mt-3" style="position:static;">
  <button type="submit" form="bankImportForm" class="retro-tbtn retro-primary"><i class="bi bi-upload"></i>Parse &amp; Preview</button>
  <a class="retro-tbtn" href="<?= site_url('receipts') ?>"><i class="bi bi-x-circle"></i>Close</a>
</div>
