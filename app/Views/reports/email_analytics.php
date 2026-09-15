<?php
$t = $totals;
$pct = function ($num, $den) {
    $den = (int) $den; $num = (int) $num;
    return $den > 0 ? round($num * 100 / $den, 1) : 0.0;
};
$openRate    = $pct($t['opened']     ?? 0, $t['delivered'] ?? $t['sent'] ?? 0);
$clickRate   = $pct($t['clicked']    ?? 0, $t['delivered'] ?? $t['sent'] ?? 0);
$bounceRate  = $pct($t['bounced']    ?? 0, $t['sent']      ?? 0);
$complaintR  = $pct($t['complained'] ?? 0, $t['delivered'] ?? $t['sent'] ?? 0);
$unsubRate   = $pct($t['unsubscribed'] ?? 0, $t['delivered'] ?? $t['sent'] ?? 0);
$deliverRate = $pct($t['delivered']  ?? 0, $t['sent']      ?? 0);

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
  <div class="tab active">Email Analytics</div>
  <div class="spacer"></div>
  <div class="recordnav"><?= esc($from) ?> – <?= esc($to) ?></div>
</div>

<div class="formwrap" style="flex:0 0 auto;">
  <div class="row g-3 mb-3">
    <div class="col-6 col-md-2"><div class="stat"><span class="label">Total</span><span class="value"><?= (int) ($t['total']   ?? 0) ?></span></div></div>
    <div class="col-6 col-md-2"><div class="stat"><span class="label">Sent</span><span class="value"><?= (int) ($t['sent']    ?? 0) ?></span></div></div>
    <div class="col-6 col-md-2"><div class="stat"><span class="label">Delivered</span><span class="value"><?= $deliverRate ?>%</span><small class="text-muted" style="font-size:.7rem;"><?= (int) ($t['delivered'] ?? 0) ?> msgs</small></div></div>
    <div class="col-6 col-md-2"><div class="stat"><span class="label">Open rate</span><span class="value"><?= $openRate ?>%</span><small class="text-muted" style="font-size:.7rem;"><?= (int) ($t['opened'] ?? 0) ?> opens</small></div></div>
    <div class="col-6 col-md-2"><div class="stat"><span class="label">Click rate</span><span class="value"><?= $clickRate ?>%</span><small class="text-muted" style="font-size:.7rem;"><?= (int) ($t['clicked'] ?? 0) ?> clicks</small></div></div>
    <div class="col-6 col-md-2"><div class="stat"><span class="label">Queued</span><span class="value"><?= (int) ($t['queued'] ?? 0) ?></span></div></div>
    <div class="col-6 col-md-3"><div class="stat"><span class="label">Bounce rate</span><span class="value" style="<?= $bounceRate > 5 ? 'color:#b00020' : '' ?>"><?= $bounceRate ?>%</span></div></div>
    <div class="col-6 col-md-3"><div class="stat"><span class="label">Spam complaints</span><span class="value" style="<?= $complaintR > 0.1 ? 'color:#b00020' : '' ?>"><?= $complaintR ?>%</span></div></div>
    <div class="col-6 col-md-3"><div class="stat"><span class="label">Unsub rate</span><span class="value"><?= $unsubRate ?>%</span></div></div>
    <div class="col-6 col-md-3"><div class="stat"><span class="label">Failed / Suppressed</span><span class="value"><?= (int) ($t['failed'] ?? 0) + (int) ($t['suppressed'] ?? 0) ?></span></div></div>
  </div>

  <div class="card">
    <div class="card-header">Daily volume</div>
    <div class="card-body"><canvas id="emailDailyChart" height="100"></canvas></div>
  </div>
</div>

<div class="gridwrap">
  <div class="table-responsive">
    <table class="table grid mb-0">
      <thead><tr><th>Template</th><th class="text-end">Sent</th><th class="text-end">Open %</th><th class="text-end">Click %</th><th class="text-end">Bounce %</th><th class="text-end">Spam %</th></tr></thead>
      <tbody>
      <?php if (empty($perTemplate)): ?>
        <tr><td colspan="6" class="text-center text-muted py-3">No emails in this window.</td></tr>
      <?php endif; ?>
      <?php foreach ($perTemplate as $r):
        $rOpen   = $pct($r['opened'],     $r['sent']);
        $rClick  = $pct($r['clicked'],    $r['sent']);
        $rBounce = $pct($r['bounced'],    $r['sent']);
        $rSpam   = $pct($r['complained'], $r['sent']);
      ?>
        <tr>
          <td><code><?= esc($r['template_key']) ?></code></td>
          <td class="text-end"><?= (int) $r['sent'] ?></td>
          <td class="text-end"><?= $rOpen ?>%</td>
          <td class="text-end"><?= $rClick ?>%</td>
          <td class="text-end" style="<?= $rBounce > 5 ? 'color:#b00020' : '' ?>"><?= $rBounce ?>%</td>
          <td class="text-end" style="<?= $rSpam > 0.1 ? 'color:#b00020' : '' ?>"><?= $rSpam ?>%</td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
<script>
(function(){
  var daily = <?= json_encode($daily) ?>;
  var labels = daily.map(d => d.d);
  var ctx = document.getElementById('emailDailyChart');
  if (!ctx || !window.Chart) return;
  new Chart(ctx, {
    type: 'line',
    data: {
      labels,
      datasets: [
        { label: 'Sent',    data: daily.map(d => +d.sent),    borderColor:'#1d6cb1', backgroundColor:'rgba(29,108,177,.1)', tension:.3, fill:true },
        { label: 'Opened',  data: daily.map(d => +d.opened),  borderColor:'#166c3b', tension:.3 },
        { label: 'Clicked', data: daily.map(d => +d.clicked), borderColor:'#d4a017', tension:.3 },
        { label: 'Bounced', data: daily.map(d => +d.bounced), borderColor:'#b00020', tension:.3 },
      ]
    },
    options: { responsive:true, plugins:{ legend:{ position:'bottom', labels:{ font:{ family:'Poppins'}}}}}
  });
})();
</script>
