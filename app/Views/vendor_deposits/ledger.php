<?php $fmt = fn($n) => '₹' . number_format((float) $n, 2); ?>
<div class="d-flex align-items-center mb-3 gap-2">
  <h5 class="m-0"><?= esc($vendor['company_name']) ?> — Deposit ledger</h5>
  <a class="btn btn-sm btn-light ms-auto" href="<?= site_url('vendor-deposits') ?>">&larr; Back</a>
</div>

<div class="row g-3 mb-3">
  <div class="col-md-3"><div class="stat"><span class="label">Current balance</span><span class="value"><?= $fmt($balance) ?></span></div></div>
  <div class="col-md-3"><div class="stat"><span class="label">Transactions</span><span class="value"><?= count($rows) ?></span></div></div>
</div>

<div class="card mb-3">
  <div class="card-header">Record a transaction</div>
  <div class="card-body">
    <form method="post" action="<?= site_url('vendor-deposits/' . $vendor['id'] . '/record') ?>" class="row g-2 align-items-end">
      <?= csrf_field() ?>
      <div class="col-md-2"><label class="form-label">Type</label>
        <select class="form-select" name="txn_type">
          <option value="Deposit">Deposit (in)</option>
          <option value="Release">Release (refund)</option>
          <option value="Forfeit">Forfeit (claim)</option>
        </select>
      </div>
      <div class="col-md-2"><label class="form-label">Amount ₹ *</label><input class="form-control" type="number" step="0.01" name="amount" required></div>
      <div class="col-md-2"><label class="form-label">Date</label><input type="date" class="form-control" name="txn_date" value="<?= date('Y-m-d') ?>"></div>
      <div class="col-md-2"><label class="form-label">Reference</label><input class="form-control" name="reference_no" placeholder="cheque / UTR"></div>
      <div class="col-md-3"><label class="form-label">Reason / notes</label><input class="form-control" name="reason"></div>
      <div class="col-md-1"><button class="btn btn-primary w-100">Add</button></div>
    </form>
  </div>
</div>

<div class="card">
  <div class="table-responsive">
    <table class="table table-sm align-middle mb-0">
      <thead><tr><th>Date</th><th>Type</th><th class="text-end">Amount</th><th class="text-end">Balance after</th><th>Reference</th><th>Reason</th></tr></thead>
      <tbody>
      <?php if (empty($rows)): ?>
        <tr><td colspan="6" class="text-center text-muted py-3">No transactions yet.</td></tr>
      <?php endif; ?>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><?= esc(date('d-m-Y', strtotime($r['txn_date']))) ?></td>
          <td><span class="badge-soft <?= $r['txn_type']==='Deposit' ? 'badge-ok' : ($r['txn_type']==='Release'?'badge-warn':'badge-danger') ?>"><?= esc($r['txn_type']) ?></span></td>
          <td class="text-end"><?= $fmt($r['amount']) ?></td>
          <td class="text-end"><strong><?= $fmt($r['balance_after']) ?></strong></td>
          <td><?= esc($r['reference_no'] ?: '—') ?></td>
          <td><?= esc($r['reason'] ?: '—') ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
