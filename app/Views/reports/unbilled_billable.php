<?= tpt_toolbar([
    'close_href' => site_url('reports'),
    'auth'       => $auth,
]) ?>
<div class="tabs" role="tablist">
  <div class="tab active">Unbilled Billable Expenses</div>
  <div class="spacer"></div>
  <div class="recordnav"><?= count($rows) ?> entries · ₹<?= number_format((float) $total, 0) ?></div>
</div>

<div class="formwrap" style="flex:0 0 auto;">
  <p class="text-muted mb-0" style="font-size:.85rem;">Billable expenses that have not yet been added to a client invoice — this is revenue leakage.</p>
  <div class="row g-3 mt-0">
    <div class="col-6 col-md-3"><div class="stat"><span class="label">Unbilled Total</span><span class="value">₹<?= number_format((float) $total, 0) ?></span></div></div>
    <div class="col-6 col-md-3"><div class="stat"><span class="label">Entries</span><span class="value"><?= count($rows) ?></span></div></div>
  </div>
</div>

<div class="gridwrap">
  <div class="table-responsive">
    <table class="table grid mb-0">
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
