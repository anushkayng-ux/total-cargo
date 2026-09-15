<?php $fmt = fn ($n) => '₹' . number_format((float) $n, 2); ?>
<?= tpt_toolbar([
    'close_href' => site_url('dashboard'),
    'auth'       => $auth,
]) ?>
<div class="tabs">
  <div class="tab active">Vendor Security Deposits</div>
  <div class="spacer"></div>
  <div class="recordnav"><?= count($rows) ?> vendors</div>
</div>

<div class="gridwrap">
  <div class="table-responsive">
    <table class="table grid mb-0">
      <thead><tr><th>Code</th><th>Vendor</th><th class="text-end">Balance</th><th class="text-end">Transactions</th><th></th></tr></thead>
      <tbody>
      <?php if (empty($rows)): ?>
        <tr><td colspan="5" class="text-center text-muted py-3">No vendors yet.</td></tr>
      <?php endif; ?>
      <?php foreach ($rows as $r): ?>
        <tr class="row-link" data-href="<?= site_url('vendor-deposits/' . $r['id']) ?>">
          <td><code><?= esc($r['vendor_code']) ?></code></td>
          <td><?= esc($r['company_name']) ?></td>
          <td class="text-end"><strong><?= $fmt($r['balance']) ?></strong></td>
          <td class="text-end"><?= (int) $r['txn_count'] ?></td>
          <td><a class="btn btn-sm btn-light" href="<?= site_url('vendor-deposits/' . $r['id']) ?>"><i class="bi bi-eye"></i> Ledger</a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
