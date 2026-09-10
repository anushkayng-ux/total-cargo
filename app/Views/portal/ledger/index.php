<?php
$fmt = fn($n) => '₹' . number_format((float) $n, 2);
?>
<h5 class="mb-3">Ledger</h5>

<div class="row g-3 mb-3">
  <div class="col-6 col-md-3">
    <div class="stat-card"><span class="label">Total billed</span><span class="value"><?= $fmt($totalBilled) ?></span></div>
  </div>
  <div class="col-6 col-md-3">
    <div class="stat-card"><span class="label">Total received</span><span class="value"><?= $fmt($totalReceived) ?></span></div>
  </div>
  <div class="col-6 col-md-3">
    <div class="stat-card"><span class="label">Outstanding</span><span class="value"><?= $fmt($outstanding) ?></span></div>
  </div>
</div>

<form method="get" class="row g-2 mb-3 align-items-end">
  <div class="col-6 col-md-3">
    <label class="form-label" style="font-size:.85rem;">From</label>
    <input type="date" class="form-control form-control-sm" name="from" value="<?= esc($from) ?>">
  </div>
  <div class="col-6 col-md-3">
    <label class="form-label" style="font-size:.85rem;">To</label>
    <input type="date" class="form-control form-control-sm" name="to" value="<?= esc($to) ?>">
  </div>
  <div class="col-md-2"><button class="btn btn-sm btn-light w-100">Apply</button></div>
</form>

<div class="card">
  <div class="table-responsive">
    <table class="table table-sm align-middle mb-0">
      <thead>
        <tr>
          <th>Date</th><th>Type</th><th>Reference</th>
          <th class="text-end">Debit</th><th class="text-end">Credit</th>
          <th class="text-end">Balance</th><th>Note</th>
        </tr>
      </thead>
      <tbody>
      <?php if (empty($entries)): ?>
        <tr><td colspan="7" class="text-center text-muted py-4">No entries in this range.</td></tr>
      <?php endif; ?>
      <?php foreach ($entries as $e): ?>
        <tr>
          <td><?= esc(date('d-m-Y', strtotime($e['date']))) ?></td>
          <td><?= esc($e['type']) ?></td>
          <td>
            <?php if (!empty($e['link'])): ?>
              <a href="<?= esc($e['link']) ?>"><?= esc($e['ref']) ?></a>
            <?php else: ?>
              <?= esc($e['ref']) ?>
            <?php endif; ?>
          </td>
          <td class="text-end"><?= $e['debit'] > 0 ? $fmt($e['debit']) : '' ?></td>
          <td class="text-end"><?= $e['credit'] > 0 ? $fmt($e['credit']) : '' ?></td>
          <td class="text-end"><strong><?= $fmt($e['balance']) ?></strong></td>
          <td class="text-muted"><?= esc($e['note']) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
