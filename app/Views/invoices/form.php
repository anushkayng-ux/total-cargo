<?php
$isEdit = !empty($row);
$action = $isEdit ? site_url('invoices/' . $row['id']) : site_url('invoices/store');
$items  = $row['items'] ?? [];
if (empty($items) && !empty($prefill['items'])) {
    $items = $prefill['items'];
}
if (empty($items) && !empty($prefill['line_desc'])) {
    $items = [[
        'description'    => $prefill['line_desc'],
        'hsn_sac'        => '996791',
        'qty'            => 1,
        'rate'           => $prefill['line_rate'] ?? 0,
        'gst_percent'    => (float) ($settings['billing']['default_gst_rate'] ?? 0),
    ]];
}
if (empty($items)) {
    $items = [['description' => '', 'hsn_sac' => '996791', 'qty' => 1, 'rate' => 0, 'gst_percent' => (float) ($settings['billing']['default_gst_rate'] ?? 0)]];
}
$v = function ($k, $d = '') use ($row, $prefill) {
    return old($k, $row[$k] ?? $prefill[$k] ?? $d);
};
?>
<?= tpt_toolbar([
    'save_form'   => 'invoiceForm',
    'close_href'  => site_url('invoices'),
    'auth'        => $auth,
]) ?>

<div class="tabs" id="invoiceFormTabs">
  <button type="button" class="tab active" data-bs-toggle="tab" data-bs-target="#invf-details">Invoice Details</button>
  <button type="button" class="tab" data-bs-toggle="tab" data-bs-target="#invf-items">Line Items</button>
  <div class="spacer"></div>
  <?php if ($isEdit): ?><div class="recordnav">Invoice <?= esc($row['invoice_no']) ?></div><?php endif; ?>
</div>

<form id="invoiceForm" method="post" action="<?= $action ?>">
  <?= csrf_field() ?>
  <div class="tab-content">

    <div class="tab-pane fade show active" id="invf-details">
      <div class="formwrap">
        <div class="retro-row">
          <div class="retro-field"><label>Invoice No. :</label><input class="retro-box wide" name="invoice_no" value="<?= esc($v('invoice_no')) ?>" placeholder="Leave blank to auto-generate"></div>
          <div class="retro-field"><label>Invoice Date :</label><input type="date" class="retro-box" name="invoice_date" value="<?= esc($v('invoice_date', date('Y-m-d'))) ?>" required></div>
          <div class="retro-field"><label>Due Date :</label><input type="date" class="retro-box" name="due_date" value="<?= esc($v('due_date')) ?>"></div>
        </div>
        <div class="retro-row">
          <div class="retro-field" style="width:100%;"><label>Client <span class="retro-required">*</span> :</label>
            <select class="retro-box xwide" style="min-width:400px;" name="client_id" required>
              <option value="">— Select —</option>
              <?php foreach ($clients as $c): ?>
                <option value="<?= $c['id'] ?>" <?= (int) $v('client_id') === (int) $c['id'] ? 'selected' : '' ?>>
                  <?= esc($c['company_name']) ?><?= $c['gst_no'] ? ' · ' . esc($c['gst_no']) : '' ?> (<?= esc($c['state']) ?>)
                </option>
              <?php endforeach; ?>
            </select>
            <div style="font-size:11px;color:var(--v2-fg-muted);margin-top:3px;">GST is split CGST+SGST if same state as <?= esc($settings['company']['company_state'] ?? '(company state unset)') ?>, else IGST.</div>
          </div>
        </div>
        <div class="retro-row">
          <div class="retro-field"><label>Booking (optional) :</label>
            <select class="retro-box wide" name="booking_id">
              <option value="">—</option>
              <?php foreach ($bookings as $b): ?>
                <option value="<?= $b['id'] ?>" <?= (int) $v('booking_id') === (int) $b['id'] ? 'selected' : '' ?>>
                  <?= esc($b['booking_no']) ?> · <?= esc(tpt_route($b['route_text'] ?? '', '')) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="retro-field"><label>Trip (optional) :</label>
            <select class="retro-box wide" name="trip_id">
              <option value="">—</option>
              <?php foreach ($trips as $t): ?>
                <option value="<?= $t['id'] ?>" <?= (int) $v('trip_id') === (int) $t['id'] ? 'selected' : '' ?>>
                  <?= esc($t['trip_no']) ?> · <?= esc($t['vehicle_number']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="retro-row" style="align-items:flex-start;">
          <div class="retro-field" style="width:100%;"><label style="white-space:nowrap;">Payment Terms :</label>
            <textarea class="retro-box retro-particulars" name="payment_terms" rows="2" style="width:100%;" placeholder="e.g. Payment due within 30 days by RTGS / NEFT. Advance %, Balance on delivery."><?= esc($v('payment_terms')) ?></textarea>
          </div>
        </div>
        <div class="retro-row" style="align-items:flex-start;">
          <div class="retro-field" style="width:100%;"><label style="white-space:nowrap;">Notes :</label>
            <textarea class="retro-box retro-particulars" name="notes" rows="2" style="width:100%;" placeholder="Internal — prints as Remarks on invoice"><?= esc($v('notes')) ?></textarea>
          </div>
        </div>
      </div>
    </div>

    <div class="tab-pane fade" id="invf-items">
      <div class="formwrap" style="flex:0 0 auto;">
        <div class="retro-row" style="align-items:center;">
          <div class="retro-field" style="font-weight:600;">Line Items</div>
          <button type="button" class="retro-tbtn retro-primary" style="width:auto;flex-direction:row;margin-left:auto;" id="addRow"><i class="bi bi-plus-lg"></i>Add Row</button>
        </div>
      </div>
      <div class="gridwrap" style="padding:0;">
        <div class="table-responsive">
          <table class="table grid mb-0" id="itemsTbl">
            <thead>
              <tr>
                <th style="min-width:260px;">Description</th>
                <th>HSN/SAC</th>
                <th class="text-end">Qty</th>
                <th class="text-end">Rate</th>
                <th class="text-end">Taxable</th>
                <th class="text-end">GST %</th>
                <th class="text-end">GST Amt</th>
                <th class="text-end">Total</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($items as $i => $it): ?>
                <tr class="item-row">
                  <td>
                    <input class="retro-box itm-desc" style="width:100%;" name="line_description[]" value="<?= esc($it['description']) ?>">
                    <input type="hidden" class="itm-expense-id" name="line_expense_id[]" value="<?= (int) ($it['expense_id'] ?? 0) ?>">
                    <?php if (!empty($it['expense_id'])): ?>
                      <small class="text-warning" style="font-size:.72rem;"><i class="bi bi-link-45deg"></i> Linked to trip expense #<?= (int) $it['expense_id'] ?></small>
                    <?php endif; ?>
                  </td>
                  <td><input class="retro-box itm-hsn" style="width:100%;" name="line_hsn[]" value="<?= esc($it['hsn_sac'] ?? '996791') ?>"></td>
                  <td class="text-end"><input type="number" step="0.01" class="retro-box text-end itm-qty" style="width:100%;" name="line_qty[]" value="<?= esc($it['qty'] ?? 1) ?>"></td>
                  <td class="text-end"><input type="number" step="0.01" class="retro-box text-end itm-rate" style="width:100%;" name="line_rate[]" value="<?= esc($it['rate'] ?? 0) ?>"></td>
                  <td class="text-end itm-taxable">0.00</td>
                  <td class="text-end"><input type="number" step="0.01" class="retro-box text-end itm-gst" style="width:100%;" name="line_gst[]" value="<?= esc($it['gst_percent'] ?? 5) ?>"></td>
                  <td class="text-end itm-gstamt">0.00</td>
                  <td class="text-end itm-total">0.00</td>
                  <td class="text-end"><button type="button" class="retro-tbtn retro-danger rm-row" style="width:auto;flex-direction:row;padding:4px 8px !important;"><i class="bi bi-trash"></i></button></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
            <tfoot>
              <tr>
                <td colspan="7" class="text-end"><strong>Taxable</strong></td><td class="text-end"><strong id="sumTaxable">0.00</strong></td><td></td>
              </tr>
              <tr>
                <td colspan="7" class="text-end">GST</td><td class="text-end" id="sumGst">0.00</td><td></td>
              </tr>
              <tr>
                <td colspan="7" class="text-end">Round Off</td><td class="text-end" id="sumRound">0.00</td><td></td>
              </tr>
              <tr>
                <td colspan="7" class="text-end"><strong>Grand Total (INR)</strong></td><td class="text-end"><strong id="sumTotal">0.00</strong></td><td></td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
    </div>

  </div>

  <div class="retro-toolbar mt-3" style="position:static;">
    <button type="submit" class="retro-tbtn retro-primary"><i class="bi bi-save-fill"></i>Save Draft</button>
    <a class="retro-tbtn" href="<?= site_url('invoices') ?>"><i class="bi bi-x-circle"></i>Close</a>
  </div>
</form>

<script>
(function () {
  const tbl = document.getElementById('itemsTbl');
  const tbody = tbl.querySelector('tbody');

  function rowTemplate() {
    const d = (<?= (float) ($settings['billing']['default_gst_rate'] ?? 0) ?>).toFixed(2);
    return `<tr class="item-row">
      <td>
        <input class="retro-box itm-desc" style="width:100%;" name="line_description[]">
        <input type="hidden" class="itm-expense-id" name="line_expense_id[]" value="0">
      </td>
      <td><input class="retro-box itm-hsn" style="width:100%;" name="line_hsn[]" value="996791"></td>
      <td class="text-end"><input type="number" step="0.01" class="retro-box text-end itm-qty" style="width:100%;" name="line_qty[]" value="1"></td>
      <td class="text-end"><input type="number" step="0.01" class="retro-box text-end itm-rate" style="width:100%;" name="line_rate[]" value="0"></td>
      <td class="text-end itm-taxable">0.00</td>
      <td class="text-end"><input type="number" step="0.01" class="retro-box text-end itm-gst" style="width:100%;" name="line_gst[]" value="${d}"></td>
      <td class="text-end itm-gstamt">0.00</td>
      <td class="text-end itm-total">0.00</td>
      <td class="text-end"><button type="button" class="retro-tbtn retro-danger rm-row" style="width:auto;flex-direction:row;padding:4px 8px !important;"><i class="bi bi-trash"></i></button></td>
    </tr>`;
  }

  function recompute() {
    let taxable = 0, gst = 0;
    tbody.querySelectorAll('.item-row').forEach(r => {
      const qty  = parseFloat(r.querySelector('.itm-qty').value)  || 0;
      const rate = parseFloat(r.querySelector('.itm-rate').value) || 0;
      const gstP = parseFloat(r.querySelector('.itm-gst').value)  || 0;
      const tx   = Math.round(qty * rate * 100) / 100;
      const ga   = Math.round(tx * gstP) / 100;
      taxable += tx; gst += ga;
      r.querySelector('.itm-taxable').textContent = tx.toFixed(2);
      r.querySelector('.itm-gstamt').textContent  = ga.toFixed(2);
      r.querySelector('.itm-total').textContent   = (tx + ga).toFixed(2);
    });
    const pre   = Math.round((taxable + gst) * 100) / 100;
    const total = Math.round(pre);
    const round = Math.round((total - pre) * 100) / 100;
    document.getElementById('sumTaxable').textContent = taxable.toFixed(2);
    document.getElementById('sumGst').textContent     = gst.toFixed(2);
    document.getElementById('sumRound').textContent   = round.toFixed(2);
    document.getElementById('sumTotal').textContent   = total.toFixed(2);
  }

  document.getElementById('addRow').addEventListener('click', () => {
    tbody.insertAdjacentHTML('beforeend', rowTemplate());
    recompute();
  });
  tbody.addEventListener('click', (e) => {
    if (e.target.closest('.rm-row')) {
      if (tbody.querySelectorAll('.item-row').length > 1) {
        e.target.closest('tr').remove();
        recompute();
      }
    }
  });
  tbody.addEventListener('input', recompute);
  recompute();
})();
</script>
