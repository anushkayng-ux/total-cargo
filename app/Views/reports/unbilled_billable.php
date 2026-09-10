<div class="d-flex align-items-center mb-3 gap-2 flex-wrap">
  <h5 class="m-0"><?= esc($pageTitle) ?></h5>
  <span class="text-muted ms-2" style="font-size:.85rem;">Billable expenses that have not yet been added to a client invoice — this is revenue leakage.</span>
  <a class="ms-auto btn btn-sm btn-light" href="<?= site_url('reports') ?>"><i class="bi bi-arrow-left"></i> All Reports</a>
</div>

<div class="row g-3 mb-3">
  <div class="col-6 col-md-3"><div class="stat"><span class="label">Unbilled Total</span><span class="value">₹<?= number_format((float) $total, 0) ?></span></div></div>
  <div class="col-6 col-md-3"><div class="stat"><span class="label">Entries</span><span class="value"><?= count($rows) ?></span></div></div>
</div>

<div class="card">
  <div class="table-responsive">
    <table class="table mb-0">
      <thead>
        <tr><th>Date</th><th>Trip</th><th>Client</th><th>Category</th><th>Description</th><th class="text-end">Amount</th><th class="text-end"></th></tr>
      </thead>
      <tbody>
        <?php if (empty($rows)): ?><tr><td colspan="7" class="text-center text-muted">Nothing unbilled — all billable expenses are on invoices. 🎉</td></tr><?php endif; ?>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td data-label="Date"><?= esc($r['expense_date']) ?></td>
            <td data-label="Trip">
              <?php if ($r['trip_no']): ?><a href="<?= site_url('trips/' . $r['trip_id']) ?>"><code><?= esc($r['trip_no']) ?></code></a><?php endif; ?>
            </td>
            <td data-label="Client"><?= esc($r['client_company']) ?></td>
            <td data-label="Category"><?= esc($r['category']) ?></td>
            <td data-label="Description"><?= esc($r['description']) ?></td>
            <td class="text-end" data-label="Amount"><strong>₹<?= number_format((float) $r['amount'], 2) ?></strong></td>
            <td class="text-end"><a class="btn btn-sm btn-outline-dark" href="<?= site_url('invoices/from-trip/' . $r['trip_id']) ?>">Invoice trip</a></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
