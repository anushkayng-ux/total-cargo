<?php
$fmt = fn($n) => '₹' . number_format((float) $n, 2);
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h5 class="mb-1">Invoice <?= esc($row['invoice_no']) ?></h5>
    <span class="text-muted" style="font-size:.9rem;">
      <?= esc(date('d-m-Y', strtotime($row['invoice_date']))) ?> · <?= esc($row['invoice_status']) ?>
    </span>
  </div>
  <div>
    <a href="<?= site_url('portal/invoices/' . $row['id'] . '/pdf') ?>" target="_blank" class="btn btn-sm btn-primary">
      <i class="bi bi-file-pdf"></i> PDF
    </a>
    <a href="<?= site_url('portal/invoices') ?>" class="btn btn-sm btn-light ms-1">&larr; All invoices</a>
  </div>
</div>

<?php if ((float) $row['balance_due'] > 0.01): ?>
  <?php
    $companySettings = (new \App\Models\SettingModel())->getAllGrouped();
    $bankInfo = trim((string) (
        ($companySettings['billing']['bank_name']     ?? '') . ' ' .
        ($companySettings['billing']['bank_account']  ?? '') . ' ' .
        ($companySettings['billing']['bank_ifsc']     ?? '')
    ));
  ?>
  <div class="alert" style="background:#fff8e1;border:1px solid #ffe082;color:#5d4a16;">
    <div class="d-flex justify-content-between flex-wrap gap-2">
      <div>
        <strong>How to pay</strong> — please remit <strong>₹<?= number_format((float) $row['balance_due'], 2) ?></strong> via bank transfer or UPI.
        <?php if ($bankInfo !== ''): ?>
          <div class="mt-1" style="font-size:.9rem;"><?= esc($bankInfo) ?></div>
        <?php else: ?>
          <div class="mt-1" style="font-size:.9rem;">Bank details available from your account manager.</div>
        <?php endif; ?>
      </div>
      <div style="font-size:.85rem;">
        Reference: <code><?= esc($row['invoice_no']) ?></code>
      </div>
    </div>
  </div>
<?php endif; ?>

<div class="row g-3">
  <div class="col-lg-7">
    <div class="card mb-3">
      <div class="card-header">Line items</div>
      <div class="table-responsive">
        <table class="table table-sm mb-0">
          <thead><tr><th>Description</th><th class="text-end">Qty</th><th class="text-end">Rate</th><th class="text-end">Amount</th></tr></thead>
          <tbody>
            <?php if (empty($items)): ?>
              <tr><td colspan="4" class="text-center text-muted py-3">No items.</td></tr>
            <?php endif; ?>
            <?php foreach ($items as $it): ?>
              <tr>
                <td><?= esc($it['description'] ?? $it['item_name'] ?? '') ?></td>
                <td class="text-end"><?= esc($it['quantity'] ?? '1') ?></td>
                <td class="text-end"><?= $fmt($it['rate'] ?? $it['amount'] ?? 0) ?></td>
                <td class="text-end"><?= $fmt($it['amount'] ?? 0) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <?php if (!empty($receipts)): ?>
      <div class="card">
        <div class="card-header">Payments received</div>
        <div class="table-responsive">
          <table class="table table-sm mb-0">
            <thead><tr><th>Date</th><th>Mode</th><th>Reference</th><th class="text-end">Amount</th></tr></thead>
            <tbody>
              <?php foreach ($receipts as $r): ?>
                <tr>
                  <td><?= esc(!empty($r['receipt_date']) ? date('d-m-Y', strtotime($r['receipt_date'])) : '') ?></td>
                  <td><?= esc($r['payment_mode'] ?? '—') ?></td>
                  <td><?= esc($r['reference_no'] ?? '—') ?></td>
                  <td class="text-end"><?= $fmt($r['amount_received']) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    <?php endif; ?>
  </div>

  <div class="col-lg-5">
    <div class="card">
      <div class="card-header">Summary</div>
      <div class="card-body">
        <dl class="row mb-0" style="font-size:.92rem;">
          <dt class="col-7">Taxable</dt><dd class="col-5 text-end"><?= $fmt($row['taxable_amount']) ?></dd>
          <dt class="col-7">CGST</dt><dd class="col-5 text-end"><?= $fmt($row['cgst_amount']) ?></dd>
          <dt class="col-7">SGST</dt><dd class="col-5 text-end"><?= $fmt($row['sgst_amount']) ?></dd>
          <dt class="col-7">IGST</dt><dd class="col-5 text-end"><?= $fmt($row['igst_amount']) ?></dd>
          <dt class="col-7">Round off</dt><dd class="col-5 text-end"><?= $fmt($row['round_off']) ?></dd>
          <dt class="col-7"><strong>Total</strong></dt><dd class="col-5 text-end"><strong><?= $fmt($row['total_amount']) ?></strong></dd>
          <dt class="col-7">Received</dt><dd class="col-5 text-end"><?= $fmt($row['amount_received']) ?></dd>
          <dt class="col-7"><strong>Balance due</strong></dt><dd class="col-5 text-end"><strong><?= $fmt($row['balance_due']) ?></strong></dd>
        </dl>
      </div>
    </div>
  </div>
</div>
