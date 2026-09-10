<?php
$fmt = fn($n) => '₹' . number_format((float) $n, 2);
?>
<div class="card mb-3" id="expenses">
  <div class="card-header d-flex align-items-center gap-2 flex-wrap">
    <span>Trip Expenses</span>
    <span class="ms-auto d-flex gap-2 flex-wrap" style="font-size:.82rem;">
      <span class="badge-soft">Internal: <strong><?= $fmt($expTotals['internal']) ?></strong> · <?= (int) $expTotals['internal_count'] ?></span>
      <span class="badge-soft badge-warn">Billable: <strong><?= $fmt($expTotals['billable']) ?></strong> · <?= (int) $expTotals['billable_count'] ?></span>
      <?php if ($expTotals['unbilled_billable'] > 0.01): ?>
        <span class="badge-soft badge-warn">Unbilled to client: <strong><?= $fmt($expTotals['unbilled_billable']) ?></strong></span>
      <?php endif; ?>
    </span>
  </div>
  <div class="card-body">

    <form method="post" action="<?= site_url('trips/' . $row['id'] . '/expenses') ?>" enctype="multipart/form-data" class="mb-3">
      <?= csrf_field() ?>
      <div class="row g-2">
        <div class="col-md-2"><label class="form-label">Date</label>
          <input type="date" class="form-control form-control-sm" name="expense_date" value="<?= date('Y-m-d') ?>"></div>
        <div class="col-md-3"><label class="form-label">Category</label>
          <select class="form-select form-select-sm" name="category" id="expCategory" required>
            <option value="">— Select —</option>
            <?php foreach ($expCategories as $c): ?>
              <option value="<?= esc($c['name']) ?>" data-billable="<?= (int) $c['default_is_billable'] ?>"><?= esc($c['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-2"><label class="form-label">Amount (INR)</label>
          <input type="number" step="0.01" class="form-control form-control-sm" name="amount" required></div>
        <div class="col-md-2"><label class="form-label">Paid To</label>
          <select class="form-select form-select-sm" name="paid_to">
            <?php foreach (['Driver','Vendor','Direct','Self'] as $p): ?>
              <option value="<?= $p ?>" <?= $p === 'Direct' ? 'selected' : '' ?>><?= $p ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3"><label class="form-label d-block">Bill to client?</label>
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" name="is_billable" value="1" id="expBillable">
            <label class="form-check-label" for="expBillable">Reimbursable</label>
          </div>
        </div>

        <div class="col-md-4"><label class="form-label">Description</label>
          <input class="form-control form-control-sm" name="description" placeholder="e.g. FASTag Morbi to Surat"></div>
        <div class="col-md-2"><label class="form-label">Mode</label>
          <input class="form-control form-control-sm" name="payment_mode" placeholder="Cash / UPI / NEFT"></div>
        <div class="col-md-2"><label class="form-label">Reference</label>
          <input class="form-control form-control-sm" name="reference_no" placeholder="Voucher no"></div>
        <div class="col-md-4"><label class="form-label">Receipt (optional)</label>
          <input type="file" class="form-control form-control-sm" name="receipt_file" accept="image/*,.pdf"></div>
      </div>
      <div class="mt-2 d-flex gap-2">
        <button class="btn btn-sm btn-primary" type="submit"><i class="bi bi-plus-lg"></i> Add Expense</button>
      </div>
    </form>

    <?php if (empty($expenses)): ?>
      <div class="text-muted">No expenses recorded yet.</div>
    <?php else: ?>
      <div class="table-responsive">
        <table class="table mb-0">
          <thead>
            <tr>
              <th>Date</th><th>Category</th><th>Description</th>
              <th class="text-end">Amount</th><th>Paid To</th>
              <th>Type</th><th>Receipt</th><th>Invoice</th><th class="text-end"></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($expenses as $e): ?>
              <tr>
                <td data-label="Date"><?= esc($e['expense_date']) ?></td>
                <td data-label="Category"><?= esc($e['category']) ?></td>
                <td data-label="Description"><?= esc($e['description']) ?>
                  <?php if (!empty($e['reference_no'])): ?>
                    <small class="text-muted d-block">Ref: <?= esc($e['reference_no']) ?></small>
                  <?php endif; ?>
                </td>
                <td class="text-end" data-label="Amount"><strong><?= $fmt($e['amount']) ?></strong></td>
                <td data-label="Paid To"><?= esc($e['paid_to']) ?><?php if ($e['payment_mode']): ?><br><small class="text-muted"><?= esc($e['payment_mode']) ?></small><?php endif; ?></td>
                <td data-label="Type">
                  <?php if ((int) $e['is_billable'] === 1): ?>
                    <span class="badge-soft badge-warn">Billable</span>
                  <?php else: ?>
                    <span class="badge-soft">Internal</span>
                  <?php endif; ?>
                </td>
                <td data-label="Receipt">
                  <?php if (!empty($e['document_id'])): ?>
                    <a class="btn btn-sm btn-light" href="<?= site_url('trips/' . $row['id'] . '/expenses/' . $e['id'] . '/receipt') ?>" target="_blank" title="<?= esc($e['receipt_name']) ?>"><i class="bi bi-paperclip"></i></a>
                  <?php else: ?>
                    <span class="text-muted">—</span>
                  <?php endif; ?>
                </td>
                <td data-label="Invoice">
                  <?php if (!empty($e['billed_on_invoice_id'])): ?>
                    <a class="badge-soft badge-ok" href="<?= site_url('invoices/' . (int) $e['billed_on_invoice_id']) ?>"><?= esc($e['billed_on_invoice_no']) ?></a>
                  <?php elseif ((int) $e['is_billable'] === 1): ?>
                    <span class="badge-soft badge-warn">Unbilled</span>
                  <?php else: ?>
                    <span class="text-muted">—</span>
                  <?php endif; ?>
                </td>
                <td class="text-end" data-label="Actions">
                  <?php if (!empty($e['billed_on_invoice_id'])): ?>
                    <form method="post" action="<?= site_url('trips/' . $row['id'] . '/expenses/' . $e['id'] . '/unbill') ?>" class="d-inline" data-confirm="Return this expense to Unbilled?">
                      <?= csrf_field() ?>
                      <button class="btn btn-sm btn-light" title="Unbill"><i class="bi bi-arrow-counterclockwise"></i></button>
                    </form>
                  <?php else: ?>
                    <form method="post" action="<?= site_url('trips/' . $row['id'] . '/expenses/' . $e['id'] . '/delete') ?>" class="d-inline" data-confirm="Delete this expense?">
                      <?= csrf_field() ?>
                      <button class="btn btn-sm btn-light" title="Delete"><i class="bi bi-trash"></i></button>
                    </form>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>

  </div>
</div>

<script>
(function () {
  const sel = document.getElementById('expCategory');
  const chk = document.getElementById('expBillable');
  if (sel && chk) {
    sel.addEventListener('change', function () {
      const opt = sel.options[sel.selectedIndex];
      chk.checked = opt && opt.dataset.billable === '1';
    });
  }
})();
</script>
