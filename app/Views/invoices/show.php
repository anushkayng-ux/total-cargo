<?php
$val = fn ($v, $empty = '—') => ($v === null || $v === '') ? $empty : esc((string) $v);
$statusCls = match ((string) $row['invoice_status']) {
    'Paid'           => 'badge-soft badge-ok',
    'Partially Paid' => 'badge-soft badge-warn',
    'Cancelled'      => 'badge-soft badge-danger',
    default          => 'badge-soft',
};
$otherQuickAdd = array_values(array_filter(
    tpt_quick_add_items($auth),
    fn ($qa) => rtrim($qa['url'], '/') !== rtrim(site_url('invoices/create'), '/')
));
?>
<div class="retro-toolbar is-sticky">
  <?php if ($otherQuickAdd): ?>
    <div class="dropdown d-inline-block">
      <button type="button" class="retro-tbtn retro-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false"><i class="bi bi-plus-circle-fill"></i>New</button>
      <ul class="dropdown-menu shadow-sm" style="font-size:.85rem;">
        <li><a class="dropdown-item d-flex align-items-center gap-2" href="<?= site_url('invoices/create') ?>"><i class="bi bi-plus-circle"></i> New Invoice</a></li>
        <li><hr class="dropdown-divider"></li>
        <li><h6 class="dropdown-header">Other…</h6></li>
        <?php foreach ($otherQuickAdd as $qa): ?>
          <li><a class="dropdown-item d-flex align-items-center gap-2" href="<?= esc($qa['url']) ?>"><i class="bi bi-<?= esc($qa['icon']) ?>"></i> <?= esc($qa['label']) ?></a></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php else: ?>
    <a class="retro-tbtn retro-primary" href="<?= site_url('invoices/create') ?>"><i class="bi bi-plus-circle-fill"></i>New</a>
  <?php endif; ?>
  <?php if ($row['invoice_status'] === 'Draft'): ?>
    <a class="retro-tbtn" href="<?= site_url('invoices/' . $row['id'] . '/edit') ?>"><i class="bi bi-pencil-fill"></i>Edit</a>
    <form method="post" action="<?= site_url('invoices/' . $row['id'] . '/finalize') ?>" class="d-inline" data-confirm="Finalize this invoice? Cannot be edited after.">
      <?= csrf_field() ?><button type="submit" class="retro-tbtn retro-primary"><i class="bi bi-lock"></i>Finalize</button>
    </form>
  <?php else: ?>
    <div class="retro-tbtn retro-tbtn-off"><i class="bi bi-pencil-fill"></i>Edit</div>
  <?php endif; ?>
  <a class="retro-tbtn" target="_blank" href="<?= site_url('invoices/' . $row['id'] . '/pdf') ?>"><i class="bi bi-file-earmark-pdf"></i>PDF</a>
  <?php if ($row['invoice_status'] !== 'Draft' && $row['invoice_status'] !== 'Cancelled'): ?>
    <form method="post" action="<?= site_url('invoices/' . $row['id'] . '/share-wa') ?>" class="d-inline" data-confirm="Share invoice via WhatsApp?">
      <?= csrf_field() ?><button type="submit" class="retro-tbtn"><i class="bi bi-whatsapp"></i>Share WA</button>
    </form>
    <form method="post" action="<?= site_url('invoices/' . $row['id'] . '/share-email') ?>" class="d-inline" data-confirm="Email invoice with PDF attachment?">
      <?= csrf_field() ?><button type="submit" class="retro-tbtn"><i class="bi bi-envelope"></i>Email</button>
    </form>
    <?php if ((float) $row['balance_due'] > 0.01): ?>
      <form method="post" action="<?= site_url('invoices/' . $row['id'] . '/remind') ?>" class="d-inline" data-confirm="Send WhatsApp payment reminder?">
        <?= csrf_field() ?><button type="submit" class="retro-tbtn"><i class="bi bi-bell"></i>Remind WA</button>
      </form>
      <form method="post" action="<?= site_url('invoices/' . $row['id'] . '/remind-email') ?>" class="d-inline" data-confirm="Send email payment reminder?">
        <?= csrf_field() ?><button type="submit" class="retro-tbtn"><i class="bi bi-envelope-exclamation"></i>Remind Email</button>
      </form>
    <?php endif; ?>
    <?php if (empty($row['irn_no'])): ?>
      <form method="post" action="<?= site_url('invoices/' . $row['id'] . '/irn') ?>" class="d-inline" data-confirm="Generate IRN via ClearTax?">
        <?= csrf_field() ?><button type="submit" class="retro-tbtn"><i class="bi bi-patch-check"></i>Generate IRN</button>
      </form>
    <?php endif; ?>
    <?php if (!in_array($row['invoice_status'], ['Paid','Cancelled'], true)): ?>
      <form method="post" action="<?= site_url('invoices/' . $row['id'] . '/cancel') ?>" class="d-inline" data-confirm="Cancel this invoice?">
        <?= csrf_field() ?><button type="submit" class="retro-tbtn"><i class="bi bi-x-octagon"></i>Cancel</button>
      </form>
    <?php endif; ?>
  <?php endif; ?>
  <a class="retro-tbtn" href="<?= site_url('invoices') ?>"><i class="bi bi-x-lg"></i>Close</a>
</div>

<div class="tabs" role="tablist" id="invoiceTabs">
  <button type="button" class="tab active" data-bs-toggle="tab" data-bs-target="#inv-details">Invoice Details</button>
  <button type="button" class="tab" data-bs-toggle="tab" data-bs-target="#inv-items">Line Items</button>
  <div class="spacer"></div>
  <div class="recordnav">
    <a href="<?= site_url('invoices') ?>"><i class="bi bi-list"></i> List</a> &middot;
    Invoice <?= esc($row['invoice_no']) ?> &middot;
    <span class="<?= $statusCls ?>"><?= esc($row['invoice_status']) ?></span>
    <?php if (!empty($row['irn_no'])): ?> &middot; <span class="badge-soft badge-ok">IRN ✓</span><?php endif; ?>
  </div>
</div>

<div class="tab-content">

  <div class="tab-pane fade show active" id="inv-details">
    <div class="retro-detail">
      <div class="retro-detail-main formwrap">
        <div class="retro-row">
          <div class="retro-field"><label>Invoice Date :</label><div class="retro-box wide"><?= $val($row['invoice_date']) ?></div></div>
          <div class="retro-field"><label>Due Date :</label><div class="retro-box wide"><?= $val($row['due_date'] ?? null) ?></div></div>
        </div>
        <div class="retro-row">
          <div class="retro-field" style="width:100%;"><label>Client :</label><div class="retro-box xwide"><?= $val($row['client_company']) ?> — <?= $val($row['client_gstin'] ?? null) ?> · <?= $val($row['client_state'] ?? null) ?></div></div>
        </div>
        <div class="retro-row">
          <div class="retro-field"><label>Booking :</label><div class="retro-box wide"><?= $row['booking_no'] ? '<a href="' . site_url('bookings/' . $row['booking_id']) . '">' . esc($row['booking_no']) . '</a>' : '—' ?></div></div>
          <div class="retro-field"><label>Trip :</label><div class="retro-box wide"><?= $row['trip_no'] ? '<a href="' . site_url('trips/' . $row['trip_id']) . '">' . esc($row['trip_no']) . '</a>' : '—' ?></div></div>
          <div class="retro-field"><label>IRN :</label><div class="retro-box wide empty"><?= $val($row['irn_no'] ?? null) ?></div></div>
        </div>
        <?php if (!empty($row['notes'])): ?>
        <div class="retro-row" style="align-items:flex-start;">
          <div class="retro-field" style="width:100%;"><label style="white-space:nowrap;">Notes :</label>
            <div class="retro-particulars"><?= nl2br(esc($row['notes'])) ?></div>
          </div>
        </div>
        <?php endif; ?>
      </div>
      <div class="retro-detail-side">
        <?php if ($row['invoice_status'] !== 'Draft' && $row['invoice_status'] !== 'Cancelled' && (float) $row['balance_due'] > 0.01): ?>
          <h4>Record Receipt :</h4>
          <form method="post" action="<?= site_url('receipts/store') ?>" class="mb-2">
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
        <?php endif; ?>

        <h4>Receipts :</h4>
        <?php if (empty($receipts)): ?>
          <div class="remarksbox">No receipts yet.</div>
        <?php else: ?>
          <ul class="list-unstyled m-0" style="font-size:.85rem;">
            <?php foreach ($receipts as $rc): ?>
              <li class="mb-2 pb-2" style="border-bottom:1px solid var(--tpt-border);">
                <div class="d-flex align-items-center gap-2">
                  <strong>₹<?= number_format((float) $rc['amount_received'], 2) ?></strong>
                  <span class="badge-soft"><?= esc($rc['payment_mode']) ?></span>
                  <span class="ms-auto">
                    <form method="post" action="<?= site_url('receipts/' . $rc['id'] . '/delete') ?>" class="d-inline" data-confirm="Delete this receipt?">
                      <?= csrf_field() ?>
                      <button class="btn btn-sm btn-light" style="padding:0 4px;"><i class="bi bi-trash"></i></button>
                    </form>
                  </span>
                </div>
                <small class="text-muted"><?= esc($rc['receipt_date']) ?> · <?= esc($rc['reference_no']) ?> · by <?= esc($rc['created_by_name'] ?? '—') ?></small>
                <?php if (!empty($rc['notes'])): ?><div class="mt-1"><?= esc($rc['notes']) ?></div><?php endif; ?>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>

        <?php if ($einvoice): ?>
          <h4 style="margin-top:14px;">E-Invoice (ClearTax) :</h4>
          <div class="remarksbox" style="font-size:.85rem;">
            <p class="mb-1">Status: <span class="badge-soft <?= $einvoice['irn_status'] === 'Success' ? 'badge-ok' : ($einvoice['irn_status'] === 'Failed' ? 'badge-danger' : 'badge-warn') ?>"><?= esc($einvoice['irn_status']) ?></span></p>
            <?php if (!empty($row['irn_no'])): ?>
              <p class="mb-1">IRN: <?= esc($row['irn_no']) ?></p>
              <p class="mb-0">ACK: <?= esc($row['ack_no']) ?> · <?= esc($row['ack_date']) ?></p>
            <?php endif; ?>
          </div>
          <?php if (!$clearTax->isConfigured()): ?>
            <div class="alert alert-danger mt-2 mb-0" style="font-size:.78rem;">
              ClearTax not configured. Fill <code>cleartax.*</code> keys in <code>.env</code> to go live.
            </div>
          <?php endif; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="tab-pane fade" id="inv-items">
    <div class="gridwrap">
      <div class="table-responsive">
        <table class="table grid mb-0">
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
  </div>

</div>
