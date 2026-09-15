<?php
$fmt = fn($n) => '₹' . number_format((float) $n, 0);

$extra = '<form method="get" class="d-flex align-items-center gap-2 flex-wrap m-0">'
    . '<input class="form-control form-control-sm" style="width:150px;" type="date" name="from" value="' . esc($from) . '">'
    . '<input class="form-control form-control-sm" style="width:150px;" type="date" name="to" value="' . esc($to) . '">'
    . '<button class="btn btn-sm btn-outline-dark">Apply</button></form>';
?>
<?= tpt_toolbar([
    'close_href' => site_url('reports'),
    'extra'      => $extra,
    'auth'       => $auth,
]) ?>
<div class="tabs" role="tablist">
  <div class="tab active">Lane Profitability</div>
  <div class="spacer"></div>
  <div class="recordnav"><?= count($rows) ?> lanes</div>
</div>

<div class="formwrap" style="flex:0 0 auto;">
  <p class="text-muted mb-0" style="font-size:.85rem;">Lanes derived from booking <code>route_text</code> — naive split on common delimiters.</p>
</div>

<div class="gridwrap">
  <div class="table-responsive">
    <table class="table grid mb-0">
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
