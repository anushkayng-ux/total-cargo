<?php $fmt = fn($n) => '₹' . number_format((float) $n, 0); ?>
<h5 class="mb-3">Lane Profitability</h5>

<form method="get" class="row g-2 mb-3 align-items-end">
  <div class="col-6 col-md-3"><label class="form-label" style="font-size:.85rem;">From</label><input type="date" class="form-control form-control-sm" name="from" value="<?= esc($from) ?>"></div>
  <div class="col-6 col-md-3"><label class="form-label" style="font-size:.85rem;">To</label><input type="date" class="form-control form-control-sm" name="to" value="<?= esc($to) ?>"></div>
  <div class="col-md-2"><button class="btn btn-sm btn-light w-100">Apply</button></div>
  <div class="col-md-4 text-muted text-end" style="font-size:.85rem;">Lanes derived from booking <code>route_text</code> — naive split on common delimiters.</div>
</form>

<div class="card">
  <div class="table-responsive">
    <table class="table table-sm align-middle mb-0">
      <thead><tr><th>Pickup</th><th>Drop</th><th class="text-end">Trips</th><th class="text-end">Revenue</th><th class="text-end">Buy cost</th><th class="text-end">Margin</th><th class="text-end">Margin %</th></tr></thead>
      <tbody>
      <?php if (empty($rows)): ?>
        <tr><td colspan="7" class="text-center text-muted py-4">No completed trips in this window.</td></tr>
      <?php endif; ?>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><?= esc($r['pickup']) ?></td>
          <td><?= esc($r['drop_city']) ?></td>
          <td class="text-end"><?= (int) $r['trips'] ?></td>
          <td class="text-end"><?= $fmt($r['revenue']) ?></td>
          <td class="text-end"><?= $fmt($r['buy_cost']) ?></td>
          <td class="text-end"><strong><?= $fmt($r['margin']) ?></strong></td>
          <td class="text-end" style="<?= (float) $r['margin_pct'] < 5 ? 'color:#b00020;' : '' ?>"><?= esc($r['margin_pct']) ?>%</td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
