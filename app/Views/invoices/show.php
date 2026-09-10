<?php
$statusCls = match ((string) $row['invoice_status']) {
    'Paid'           => 'badge-soft badge-ok',
    'Partially Paid' => 'badge-soft badge-warn',
    'Cancelled'      => 'badge-soft badge-danger',
    default          => 'badge-soft',
};
?>
<div class="d-flex align-items-center mb-3 gap-2 flex-wrap">
  <h5 class="m-0">Invoice <code><?= esc($row['invoice_no']) ?></code></h5>
  <span class="<?= $statusCls ?>"><?= esc($row['invoice_status']) ?></span>
  <?php if (!empty($row['irn_no'])): ?><span class="badge-soft badge-ok">IRN ✓</span><?php endif; ?>
  <a class="ms-auto btn btn-sm btn-light" href="<?= site_url('invoices') ?>"><i class="bi bi-arrow-left"></i> Back</a>

  <?php if ($row['invoice_status'] === 'Draft'): ?>
    <a class="btn btn-sm btn-outline-dark" href="<?= site_url('invoices/' . $row['id'] . '/edit') ?>"><i class="bi bi-pencil"></i> Edit</a>
    <form method="post" action="<?= site_url('invoices/' . $row['id'] . '/finalize') ?>" class="d-inline" data-confirm="Finalize this invoice? Cannot be edited after.">
      <?= csrf_field() ?><button class="btn btn-sm btn-primary"><i class="bi bi-lock"></i> Finalize</button>
    </form>
  <?php endif; ?>

  <a class="btn btn-sm btn-outline-dark" target="_blank" href="<?= site_url('invoices/' . $row['id'] . '/pdf') ?>"><i class="bi bi-file-earmark-pdf"></i> PDF</a>

  <?php if ($row['invoice_status'] !== 'Draft' && $row['invoice_status'] !== 'Cancelled'): ?>
    <form method="post" action="<?= site_url('invoices/' . $row['id'] . '/share-wa') ?>" class="d-inline" data-confirm="Share invoice via WhatsApp?">
      <?= csrf_field() ?><button class="btn btn-sm btn-outline-dark"><i class="bi bi-whatsapp"></i> Share WA</button>
    </form>
    <form method="post" action="<?= site_url('invoices/' . $row['id'] . '/share-email') ?>" class="d-inline" data-confirm="Email invoice with PDF attachment?">
      <?= csrf_field() ?><button class="btn btn-sm btn-outline-dark"><i class="bi bi-envelope"></i> Email</button>
    </form>
    <?php if ((float) $row['balance_due'] > 0.01): ?>
      <form method="post" action="<?= site_url('invoices/' . $row['id'] . '/remind') ?>" class="d-inline" data-confirm="Send WhatsApp payment reminder?">
        <?= csrf_field() ?><button class="btn btn-sm btn-outline-dark"><i class="bi bi-bell"></i> Remind WA</button>
      </form>
      <form method="post" action="<?= site_url('invoices/' . $row['id'] . '/remind-email') ?>" class="d-inline" data-confirm="Send email payment reminder?">
        <?= csrf_field() ?><button class="btn btn-sm btn-outline-dark"><i class="bi bi-envelope-exclamation"></i> Remind Email</button>
      </form>
    <?php endif; ?>
    <?php if (empty($row['irn_no'])): ?>
      <form method="post" action="<?= site_url('invoices/' . $row['id'] . '/irn') ?>" class="d-inline" data-confirm="Generate IRN via ClearTax?">
        <?= csrf_field() ?>
        <button class="btn btn-sm btn-outline-dark"><i class="bi bi-patch-check"></i> Generate IRN</button>
      </form>
    <?php endif; ?>
    <?php if (!in_array($row['invoice_status'], ['Paid','Cancelled'], true)): ?>
      <form method="post" action="<?= site_url('invoices/' . $row['id'] . '/cancel') ?>" class="d-inline" data-confirm="Cancel this invoice?">
        <?= csrf_field() ?><button class="btn btn-sm btn-light"><i class="bi bi-x-octagon"></i> Cancel</button>
      </form>
    <?php endif; ?>
  <?php endif; ?>
</div>

<div class="row g-3">
  <div class="col-lg-8">
    <div class="card mb-3">
      <div class="card-header">Invoice Details</div>
      <div class="card-body">
        <div class="row g-3" style="font-size:.92rem;">
          <div class="col-md-4"><div class="text-muted">Invoice Date</div><div><?= esc($row['invoice_date']) ?></div></div>
          <div class="col-md-4"><div class="text-muted">Due Date</div><div><?= esc($row['due_date'] ?? '—') ?></div></div>
          <div class="col-md-4"><div class="text-muted">Client</div>
            <div><?= esc($row['client_company']) ?></div>
            <small class="text-muted"><?= esc($row['client_gstin']) ?> · <?= esc($row['client_state']) ?></small>
          </div>
          <div class="col-md-4"><div class="text-muted">Booking</div><div><?= $row['booking_no'] ? '<a href="' . site_url('bookings/' . $row['booking_id']) . '"><code>' . esc($row['booking_no']) . '</code></a>' : '—' ?></div></div>
          <div class="col-md-4"><div class="text-muted">Trip</div><div><?= $row['trip_no'] ? '<a href="' . site_url('trips/' . $row['trip_id']) . '"><code>' . esc($row['trip_no']) . '</code></a>' : '—' ?></div></div>
          <div class="col-md-4"><div class="text-muted">IRN</div><div><?= $row['irn_no'] ? '<code>' . esc($row['irn_no']) . '</code>' : '—' ?></div></div>
        </div>
      </div>
    </div>

    <div class="card mb-3">
      <div class="card-header">Line Items</div>
      <div class="table-responsive">
        <table class="table mb-0">
          <thead>
            <tr><th>Description</th><th>HSN/SAC</th><th class="text-end">Qty</th><th class="text-end">Rate</th><th class="text-end">Taxable</th><th class="text-end">GST%</th><th class="text-end">GST Amt</th><th class="text-end">Total</th></tr>
          </thead>
          <tbody>
            <?php foreach ($items as $it): ?>
              <tr>
                <td data-label="Description"><?= esc($it['description']) ?></td>
                <td data-label="HSN"><code><?= esc($it['hsn_sac']) ?></code></td>
                <td class="text-end" data-label="Qty"><?= esc($it['qty']) ?></td>
                <td class="text-end" data-label="Rate">₹<?= number_format((float) $it['rate'], 2) ?></td>
                <td class="text-end" data-label="Taxable">₹<?= number_format((float) $it['taxable_amount'], 2) ?></td>
                <td class="text-end" data-label="GST%"><?= esc($it['gst_percent']) ?>%</td>
                <td class="text-end" data-label="GST Amt">₹<?= number_format((float) $it['gst_amount'], 2) ?></td>
                <td class="text-end" data-label="Total">₹<?= number_format((float) $it['total_amount'], 2) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
          <tfoot>
            <tr><th colspan="7" class="text-end">Taxable</th><th class="text-end">₹<?= number_format((float) $row['taxable_amount'], 2) ?></th></tr>
            <?php if ((float) $row['cgst_amount'] > 0): ?>
              <tr><td colspan="7" class="text-end">CGST</td><td class="text-end">₹<?= number_format((float) $row['cgst_amount'], 2) ?></td></tr>
              <tr><td colspan="7" class="text-end">SGST</td><td class="text-end">₹<?= number_format((float) $row['sgst_amount'], 2) ?></td></tr>
            <?php endif; ?>
            <?php if ((float) $row['igst_amount'] > 0): ?>
              <tr><td colspan="7" class="text-end">IGST</td><td class="text-end">₹<?= number_format((float) $row['igst_amount'], 2) ?></td></tr>
            <?php endif; ?>
            <?php if (($row['gst_treatment'] ?? '') === 'rcm'): ?>
              <tr><td colspan="8" class="text-end text-muted" style="font-size:.85rem;">Tax payable by recipient under RCM (Reverse Charge Mechanism). Transporter has not charged GST.</td></tr>
            <?php endif; ?>
            <?php if ((float) ($row['detention_amount'] ?? 0) > 0): ?>
              <tr><td colspan="7" class="text-end">Detention charges</td><td class="text-end">₹<?= number_format((float) $row['detention_amount'], 2) ?></td></tr>
            <?php endif; ?>
            <tr><td colspan="7" class="text-end">Round Off</td><td class="text-end">₹<?= number_format((float) $row['round_off'], 2) ?></td></tr>
            <tr><th colspan="7" class="text-end">Grand Total</th><th class="text-end">₹<?= number_format((float) $row['total_amount'], 2) ?></th></tr>
            <?php if ((float) ($row['tds_amount'] ?? 0) > 0): ?>
              <tr><td colspan="7" class="text-end">Less: TDS @ <?= number_format((float) $row['tds_rate'], 2) ?>% (Sec 194C)</td><td class="text-end">−₹<?= number_format((float) $row['tds_amount'], 2) ?></td></tr>
              <tr><th colspan="7" class="text-end">Net receivable from client</th><th class="text-end">₹<?= number_format((float) $row['net_receivable'], 2) ?></th></tr>
            <?php endif; ?>
            <tr><td colspan="7" class="text-end">Received</td><td class="text-end">₹<?= number_format((float) $row['amount_received'], 2) ?></td></tr>
            <tr><th colspan="7" class="text-end">Balance Due</th><th class="text-end">₹<?= number_format((float) $row['balance_due'], 2) ?></th></tr>
          </tfoot>
        </table>
      </div>
    </div>

    <?php if (!empty($row['notes'])): ?>
      <div class="card mb-3"><div class="card-header">Notes</div><div class="card-body"><?= nl2br(esc($row['notes'])) ?></div></div>
    <?php endif; ?>
  </div>

  <div class="col-lg-4">
    <?php if ($row['invoice_status'] !== 'Draft' && $row['invoice_status'] !== 'Cancelled' && (float) $row['balance_due'] > 0.01): ?>
    <div class="card mb-3">
      <div class="card-header">Record Receipt</div>
      <div class="card-body">
        <form method="post" action="<?= site_url('receipts/store') ?>">
          <?= csrf_field() ?>
          <input type="hidden" name="client_id" value="<?= (int) $row['client_id'] ?>">
          <input type="hidden" name="invoice_id" value="<?= (int) $row['id'] ?>">
          <div class="mb-2"><label class="form-label">Date</label>
            <input type="date" class="form-control form-control-sm" name="receipt_date" value="<?= date('Y-m-d') ?>" required></div>
          <div class="mb-2"><label class="form-label">Amount (INR)</label>
            <input type="number" step="0.01" class="form-control form-control-sm" name="amount_received" max="<?= (float) $row['balance_due'] ?>" required></div>
          <div class="mb-2"><label class="form-label">Mode</label>
            <select class="form-select form-select-sm" name="payment_mode">
              <?php foreach (['Bank Transfer','UPI','Cheque','Cash','RTGS','NEFT','Other'] as $m): ?>
                <option><?= $m ?></option>
              <?php endforeach; ?>
            </select></div>
          <div class="mb-2"><label class="form-label">Reference</label>
            <input class="form-control form-control-sm" name="reference_no"></div>
          <div class="mb-2"><label class="form-label">Notes</label>
            <input class="form-control form-control-sm" name="notes"></div>
          <button class="btn btn-primary btn-sm w-100">Save Receipt</button>
        </form>
      </div>
    </div>
    <?php endif; ?>

    <div class="card mb-3">
      <div class="card-header">Receipts</div>
      <div class="card-body">
        <?php if (empty($receipts)): ?>
          <div class="text-muted">No receipts yet.</div>
        <?php else: ?>
          <ul class="list-unstyled m-0" style="font-size:.88rem;">
            <?php foreach ($receipts as $rc): ?>
              <li class="mb-2 pb-2" style="border-bottom:1px solid var(--tpt-border);">
                <div class="d-flex align-items-center gap-2">
                  <strong>₹<?= number_format((float) $rc['amount_received'], 2) ?></strong>
                  <span class="badge-soft"><?= esc($rc['payment_mode']) ?></span>
                  <span class="ms-auto">
                    <form method="post" action="<?= site_url('receipts/' . $rc['id'] . '/delete') ?>" class="d-inline" data-confirm="Delete this receipt?">
                      <?= csrf_field() ?>
                      <button class="btn btn-sm btn-light"><i class="bi bi-trash"></i></button>
                    </form>
                  </span>
                </div>
                <small class="text-muted"><?= esc($rc['receipt_date']) ?> · <?= esc($rc['reference_no']) ?> · by <?= esc($rc['created_by_name'] ?? '—') ?></small>
                <?php if (!empty($rc['notes'])): ?><div class="mt-1"><?= esc($rc['notes']) ?></div><?php endif; ?>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>
    </div>

    <?php if ($einvoice): ?>
    <div class="card">
      <div class="card-header">E-Invoice (ClearTax)</div>
      <div class="card-body" style="font-size:.85rem;">
        <p class="mb-1">Status: <span class="badge-soft <?= $einvoice['irn_status'] === 'Success' ? 'badge-ok' : ($einvoice['irn_status'] === 'Failed' ? 'badge-danger' : 'badge-warn') ?>"><?= esc($einvoice['irn_status']) ?></span></p>
        <?php if (!empty($row['irn_no'])): ?>
          <p class="mb-1">IRN: <code><?= esc($row['irn_no']) ?></code></p>
          <p class="mb-1">ACK: <code><?= esc($row['ack_no']) ?></code> · <?= esc($row['ack_date']) ?></p>
        <?php endif; ?>
        <?php if (!$clearTax->isConfigured()): ?>
          <div class="alert alert-danger mb-0 mt-2" style="font-size:.8rem;">
            ClearTax not configured. Fill <code>cleartax.*</code> keys in <code>.env</code> to go live.
          </div>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>
