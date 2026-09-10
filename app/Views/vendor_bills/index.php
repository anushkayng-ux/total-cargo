<div class="d-flex align-items-center mb-3 gap-2 flex-wrap">
  <h5 class="m-0"><?= esc($pageTitle) ?></h5>
  <form class="ms-auto d-flex gap-2" method="get" action="<?= site_url('vendor-bills') ?>">
    <input type="text" name="q" class="form-control form-control-sm" placeholder="Search bill/vendor/trip" value="<?= esc($search) ?>">
    <select name="status" class="form-select form-select-sm">
      <option value="">All</option>
      <?php foreach ($statuses as $s): ?>
        <option value="<?= esc($s) ?>" <?= $status === $s ? 'selected' : '' ?>><?= esc($s) ?></option>
      <?php endforeach; ?>
    </select>
    <button class="btn btn-sm btn-outline-dark">Filter</button>
  </form>
  <a class="btn btn-sm btn-primary" href="<?= site_url('vendor-bills/create') ?>"><i class="bi bi-plus-lg"></i> New</a>
</div>

<div class="card">
  <div class="table-responsive">
    <table class="table mb-0" data-tpt-cols="vendor-bills">
      <thead>
        <tr><th data-col="bill">Bill</th><th data-col="date">Date</th><th data-col="vendor">Vendor</th><th data-col="trip">Trip</th><th class="text-end" data-col="amount">Amount</th><th class="text-end" data-col="paid">Paid</th><th class="text-end" data-col="balance">Balance</th><th data-col="due">Due</th><th data-col="status">Status</th><th data-col="actions"></th></tr>
      </thead>
      <tbody>
        <?php if (empty($rows)): ?><tr><td colspan="10" class="text-center text-muted">No vendor bills.</td></tr><?php endif; ?>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td data-col="bill" data-label="Bill"><a href="<?= site_url('vendor-bills/' . $r['id']) ?>"><code><?= esc($r['bill_no'] ?: '#' . $r['id']) ?></code></a></td>
            <td data-col="date" data-label="Date"><?= esc($r['bill_date']) ?></td>
            <td data-col="vendor" data-label="Vendor"><?= esc($r['vendor_company']) ?></td>
            <td data-col="trip" data-label="Trip"><?= $r['trip_no'] ? '<code>' . esc($r['trip_no']) . '</code>' : '—' ?></td>
            <td class="text-end" data-col="amount" data-label="Amount">₹<?= number_format((float) $r['bill_amount'], 2) ?></td>
            <td class="text-end" data-col="paid" data-label="Paid">₹<?= number_format((float) $r['amount_paid'], 2) ?></td>
            <td class="text-end" data-col="balance" data-label="Balance"><strong>₹<?= number_format((float) $r['balance_due'], 2) ?></strong></td>
            <td data-col="due" data-label="Due"><?= esc($r['due_date']) ?></td>
            <td data-col="status" data-label="Status">
              <?php
                $cls = match ((string) $r['status']) {
                    'Paid'           => 'badge-soft badge-ok',
                    'Partially Paid' => 'badge-soft badge-warn',
                    'Cancelled'      => 'badge-soft badge-danger',
                    default          => 'badge-soft',
                };
              ?>
              <span class="<?= $cls ?>"><?= esc($r['status']) ?></span>
            </td>
            <td class="text-end" data-col="actions"><a class="btn btn-sm btn-light" href="<?= site_url('vendor-bills/' . $r['id']) ?>"><i class="bi bi-eye"></i></a></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php if (!empty($pager)): ?><div class="mt-3"><?= $pager->links() ?></div><?php endif; ?>
