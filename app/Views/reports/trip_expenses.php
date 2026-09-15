<?php
$extra = '<form method="get" class="d-flex align-items-center gap-2 flex-wrap m-0" action="' . site_url('reports/trip-expenses') . '">'
    . '<input class="form-control form-control-sm" style="width:140px;" type="date" name="from" value="' . esc($filters['from']) . '">'
    . '<input class="form-control form-control-sm" style="width:140px;" type="date" name="to" value="' . esc($filters['to']) . '">'
    . '<select class="form-select form-select-sm" name="category" style="width:auto;"><option value="">All Categories</option>';
foreach ($categories as $c) {
    $extra .= '<option value="' . esc($c['name']) . '"' . ($filters['category'] === $c['name'] ? ' selected' : '') . '>' . esc($c['name']) . '</option>';
}
$extra .= '</select>'
    . '<select class="form-select form-select-sm" name="type" style="width:auto;"><option value="">All Types</option>'
    . '<option value="internal"' . ($filters['type'] === 'internal' ? ' selected' : '') . '>Internal only</option>'
    . '<option value="billable"' . ($filters['type'] === 'billable' ? ' selected' : '') . '>Billable only</option>'
    . '</select>'
    . '<select class="form-select form-select-sm" name="vendor_id" style="width:auto;"><option value="">All Vendors</option>';
foreach ($vendors as $v) {
    $extra .= '<option value="' . (int) $v['id'] . '"' . ((int) $filters['vendor_id'] === (int) $v['id'] ? ' selected' : '') . '>' . esc($v['company_name']) . '</option>';
}
$extra .= '</select>'
    . '<button class="btn btn-sm btn-outline-dark">Filter</button></form>';
?>
<?= tpt_toolbar([
    'close_href' => site_url('reports'),
    'extra'      => $extra,
    'auth'       => $auth,
]) ?>
<div class="tabs" role="tablist">
  <div class="tab active">Trip Expenses</div>
  <div class="spacer"></div>
  <div class="recordnav"><?= count($rows) ?> entries</div>
</div>

<div class="formwrap" style="flex:0 0 auto;">
  <div class="row g-3">
    <div class="col-6 col-md-3"><div class="stat"><span class="label">Internal</span><span class="value">₹<?= number_format((float) $totals['internal'], 0) ?></span></div></div>
    <div class="col-6 col-md-3"><div class="stat"><span class="label">Billable</span><span class="value">₹<?= number_format((float) $totals['billable'], 0) ?></span></div></div>
    <div class="col-6 col-md-3"><div class="stat"><span class="label">Unbilled to Client</span><span class="value">₹<?= number_format((float) $totals['unbilled'], 0) ?></span></div></div>
    <div class="col-6 col-md-3"><div class="stat"><span class="label">Entries</span><span class="value"><?= count($rows) ?></span></div></div>
  </div>
</div>

<div class="gridwrap">
  <div class="table-responsive">
    <table class="table grid mb-0">
      <thead>
        <tr><th>Date</th><th>Trip</th><th>Vendor</th><th>Category</th><th>Description</th><th class="text-end">Amount</th><th>Type</th><th>Invoice</th></tr>
      </thead>
      <tbody>
        <?php if (empty($rows)): ?><tr><td colspan="8" class="text-center text-muted">No expenses match.</td></tr><?php endif; ?>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td data-label="Date"><?= esc($r['expense_date']) ?></td>
            <td data-label="Trip">
              <?php if ($r['trip_no']): ?><a href="<?= site_url('trips/' . $r['trip_id']) ?>"><code><?= esc($r['trip_no']) ?></code></a><br><small class="text-muted"><?= esc($r['vehicle_number']) ?></small><?php endif; ?>
            </td>
            <td data-label="Vendor"><?= esc($r['vendor_company']) ?></td>
            <td data-label="Category"><?= esc($r['category']) ?></td>
            <td data-label="Description"><?= esc($r['description']) ?></td>
            <td class="text-end" data-label="Amount"><strong>₹<?= number_format((float) $r['amount'], 2) ?></strong></td>
            <td data-label="Type">
              <?php if ((int) $r['is_billable'] === 1): ?><span class="badge-soft badge-warn">Billable</span>
              <?php else: ?><span class="badge-soft">Internal</span><?php endif; ?>
            </td>
            <td data-label="Invoice">
              <?php if (!empty($r['billed_on_invoice_id'])): ?>
                <a href="<?= site_url('invoices/' . (int) $r['billed_on_invoice_id']) ?>"><code><?= esc($r['invoice_no']) ?></code></a>
              <?php elseif ((int) $r['is_billable'] === 1): ?>
                <span class="badge-soft badge-warn">Unbilled</span>
              <?php else: ?>—<?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
