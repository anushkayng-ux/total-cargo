<?php
$isReceivables = $kind === 'receivables';
$base          = $isReceivables ? 'reports/receivables' : 'reports/payables';
$activeBucket  = $bucket ?? '';
?>
<div class="d-flex align-items-center mb-3 gap-2 flex-wrap">
  <h5 class="m-0"><?= esc($pageTitle) ?>
    <?php if ($activeBucket): ?>
      <small class="text-muted">· filtered: <strong><?= esc($activeBucket) ?></strong></small>
    <?php endif; ?>
  </h5>
  <?php if ($activeBucket): ?>
    <a class="btn btn-sm btn-light" href="<?= site_url($base) ?>"><i class="bi bi-x-circle"></i> Clear filter</a>
  <?php endif; ?>
  <a class="ms-auto btn btn-sm btn-light" href="<?= site_url('reports') ?>"><i class="bi bi-arrow-left"></i> All Reports</a>
</div>

<div class="row g-3 mb-3">
  <?php foreach ($aging as $bkt => $amount):
    $href   = site_url($base) . '?bucket=' . urlencode($bkt);
    $active = $activeBucket === $bkt;
  ?>
    <div class="col-6 col-md-2 col-lg-2">
      <a href="<?= $active ? site_url($base) : $href ?>" class="stat d-block text-decoration-none <?= $active ? 'stat-active' : '' ?>"
         style="<?= $active ? 'border-color:#3730a3;box-shadow:0 0 0 2px #c7d2fe inset;' : '' ?>"
         title="<?= $active ? 'Click to clear filter' : 'Click to filter list' ?>">
        <span class="label"><?= esc($bkt) ?></span>
        <span class="value">₹<?= number_format((float) $amount, 0) ?></span>
      </a>
    </div>
  <?php endforeach; ?>
</div>

<div class="card">
  <div class="table-responsive">
    <?php if ($isReceivables): ?>
      <table class="table mb-0">
        <thead><tr><th>Invoice</th><th>Date</th><th>Client</th><th class="text-end">Total</th><th class="text-end">Balance</th><th>Due</th><th>Status</th></tr></thead>
        <tbody>
          <?php if (empty($rows)): ?><tr><td colspan="7" class="text-center text-muted">No outstanding invoices.</td></tr><?php endif; ?>
          <?php foreach ($rows as $r):
            $overdue = !empty($r['due_date']) && $r['due_date'] < date('Y-m-d');
          ?>
            <tr>
              <td><a href="<?= site_url('invoices/' . $r['id']) ?>"><code><?= esc($r['invoice_no']) ?></code></a></td>
              <td><?= esc($r['invoice_date']) ?></td>
              <td><?= esc($r['client_company']) ?></td>
              <td class="text-end">₹<?= number_format((float) $r['total_amount'], 0) ?></td>
              <td class="text-end"><strong>₹<?= number_format((float) $r['balance_due'], 0) ?></strong></td>
              <td><?= esc($r['due_date'] ?? '—') ?><?= $overdue ? ' <span class="badge-soft badge-danger">Overdue</span>' : '' ?></td>
              <td><span class="badge-soft"><?= esc($r['invoice_status']) ?></span></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php else: ?>
      <table class="table mb-0">
        <thead><tr><th>Bill</th><th>Date</th><th>Vendor</th><th>Trip</th><th class="text-end">Bill</th><th class="text-end">Balance</th><th>Due</th><th>Status</th></tr></thead>
        <tbody>
          <?php if (empty($rows)): ?><tr><td colspan="8" class="text-center text-muted">No outstanding vendor bills.</td></tr><?php endif; ?>
          <?php foreach ($rows as $r):
            $overdue = !empty($r['due_date']) && $r['due_date'] < date('Y-m-d');
          ?>
            <tr>
              <td><a href="<?= site_url('vendor-bills/' . $r['id']) ?>"><code><?= esc($r['bill_no'] ?: '#' . $r['id']) ?></code></a></td>
              <td><?= esc($r['bill_date']) ?></td>
              <td><?= esc($r['vendor_company']) ?></td>
              <td><?= $r['trip_no'] ? '<code>' . esc($r['trip_no']) . '</code>' : '—' ?></td>
              <td class="text-end">₹<?= number_format((float) $r['bill_amount'], 0) ?></td>
              <td class="text-end"><strong>₹<?= number_format((float) $r['balance_due'], 0) ?></strong></td>
              <td><?= esc($r['due_date'] ?? '—') ?><?= $overdue ? ' <span class="badge-soft badge-danger">Overdue</span>' : '' ?></td>
              <td><span class="badge-soft"><?= esc($r['status']) ?></span></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</div>
