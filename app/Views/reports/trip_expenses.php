<div class="d-flex align-items-center mb-3 gap-2 flex-wrap">
  <h5 class="m-0"><?= esc($pageTitle) ?></h5>
  <a class="ms-auto btn btn-sm btn-light" href="<?= site_url('reports') ?>"><i class="bi bi-arrow-left"></i> All Reports</a>
</div>

<div class="card mb-3"><div class="card-body">
  <form class="row g-2 align-items-end" method="get" action="<?= site_url('reports/trip-expenses') ?>">
    <div class="col-md-2"><label class="form-label">From</label>
      <input type="date" class="form-control form-control-sm" name="from" value="<?= esc($filters['from']) ?>"></div>
    <div class="col-md-2"><label class="form-label">To</label>
      <input type="date" class="form-control form-control-sm" name="to" value="<?= esc($filters['to']) ?>"></div>
    <div class="col-md-3"><label class="form-label">Category</label>
      <select name="category" class="form-select form-select-sm">
        <option value="">All</option>
        <?php foreach ($categories as $c): ?>
          <option value="<?= esc($c['name']) ?>" <?= $filters['category'] === $c['name'] ? 'selected' : '' ?>><?= esc($c['name']) ?></option>
        <?php endforeach; ?>
      </select></div>
    <div class="col-md-2"><label class="form-label">Type</label>
      <select name="type" class="form-select form-select-sm">
        <option value="">All</option>
        <option value="internal" <?= $filters['type'] === 'internal' ? 'selected' : '' ?>>Internal only</option>
        <option value="billable" <?= $filters['type'] === 'billable' ? 'selected' : '' ?>>Billable only</option>
      </select></div>
    <div class="col-md-2"><label class="form-label">Vendor</label>
      <select name="vendor_id" class="form-select form-select-sm">
        <option value="">All</option>
        <?php foreach ($vendors as $v): ?>
          <option value="<?= $v['id'] ?>" <?= (int)$filters['vendor_id'] === (int)$v['id'] ? 'selected' : '' ?>><?= esc($v['company_name']) ?></option>
        <?php endforeach; ?>
      </select></div>
    <div class="col-md-1"><button class="btn btn-sm btn-primary w-100">Filter</button></div>
  </form>
</div></div>

<div class="row g-3 mb-3">
  <div class="col-6 col-md-3"><div class="stat"><span class="label">Internal</span><span class="value">₹<?= number_format((float) $totals['internal'], 0) ?></span></div></div>
  <div class="col-6 col-md-3"><div class="stat"><span class="label">Billable</span><span class="value">₹<?= number_format((float) $totals['billable'], 0) ?></span></div></div>
  <div class="col-6 col-md-3"><div class="stat"><span class="label">Unbilled to Client</span><span class="value">₹<?= number_format((float) $totals['unbilled'], 0) ?></span></div></div>
  <div class="col-6 col-md-3"><div class="stat"><span class="label">Entries</span><span class="value"><?= count($rows) ?></span></div></div>
</div>

<div class="card">
  <div class="table-responsive">
    <table class="table mb-0">
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
