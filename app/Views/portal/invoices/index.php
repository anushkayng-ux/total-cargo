<?php
$fmt = fn($n) => '₹' . number_format((float) $n, 0);
$badgeFor = fn($s) => [
    'Issued' => 'badge-issued', 'Paid' => 'badge-paid', 'Partially Paid' => 'badge-issued',
    'Cancelled' => 'badge-cancelled', 'Draft' => 'badge-pending',
][$s] ?? 'badge-pending';
?>
<h5 class="mb-3">Invoices</h5>

<form method="get" class="row g-2 mb-3">
  <div class="col-12 col-md-6">
    <input class="form-control form-control-sm" name="q" placeholder="Search invoice no" value="<?= esc($search) ?>">
  </div>
  <div class="col-8 col-md-4">
    <select name="status" class="form-select form-select-sm">
      <option value="">All statuses</option>
      <?php foreach ($statuses as $s): if ($s === 'Draft') continue; ?>
        <option value="<?= esc($s) ?>" <?= $s === $status ? 'selected' : '' ?>><?= esc($s) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-4 col-md-2"><button class="btn btn-sm btn-light w-100">Filter</button></div>
</form>

<div class="card">
  <div class="table-responsive">
    <table class="table table-sm align-middle mb-0">
      <thead><tr><th>Invoice</th><th>Date</th><th>Due</th><th class="text-end">Total</th><th class="text-end">Balance</th><th>Status</th><th></th></tr></thead>
      <tbody>
      <?php if (empty($rows)): ?>
        <tr><td colspan="7" class="text-center text-muted py-4">No invoices yet.</td></tr>
      <?php endif; ?>
      <?php foreach ($rows as $r): if ($r['invoice_status'] === 'Draft') continue; ?>
        <tr>
          <td><a href="<?= site_url('portal/invoices/' . $r['id']) ?>"><?= esc($r['invoice_no']) ?></a></td>
          <td><?= esc(date('d-m-Y', strtotime($r['invoice_date']))) ?></td>
          <td>
            <?= !empty($r['due_date']) ? esc(date('d-m-Y', strtotime($r['due_date']))) : '—' ?>
            <?php if (!empty($r['due_date']) && strtotime($r['due_date']) < time() && (float) $r['balance_due'] > 0): ?>
              <span class="badge-status badge-overdue">Overdue</span>
            <?php endif; ?>
          </td>
          <td class="text-end"><?= $fmt($r['total_amount']) ?></td>
          <td class="text-end"><strong><?= $fmt($r['balance_due']) ?></strong></td>
          <td><span class="badge-status <?= $badgeFor($r['invoice_status']) ?>"><?= esc($r['invoice_status']) ?></span></td>
          <td><a href="<?= site_url('portal/invoices/' . $r['id'] . '/pdf') ?>" target="_blank" class="btn btn-sm btn-light"><i class="bi bi-file-pdf"></i></a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php if ($pager): ?><div class="mt-3"><?= $pager->links() ?></div><?php endif; ?>
