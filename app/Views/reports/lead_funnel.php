<?php
$total = 0; foreach ($funnel as $f) $total += $f['count'];
?>
<?= tpt_toolbar([
    'close_href' => site_url('reports'),
    'auth'       => $auth,
]) ?>
<div class="tabs">
  <div class="tab active">Lead Funnel</div>
  <div class="spacer"></div>
  <div class="recordnav"><?= $total ?> leads total</div>
</div>

<div class="formwrap">
  <div class="row g-3">
    <div class="col-lg-6">
      <div class="card">
        <div class="card-header">Lead counts by status · Total <?= $total ?></div>
        <div class="table-responsive">
          <table class="table grid mb-0">
            <thead><tr><th>Status</th><th class="text-end">Count</th><th class="text-end">% of total</th></tr></thead>
            <tbody>
              <?php foreach ($funnel as $f):
                $pct = $total > 0 ? round($f['count'] * 100 / $total, 1) : 0;
              ?>
                <tr>
                  <td><?= esc($f['status']) ?></td>
                  <td class="text-end"><?= (int) $f['count'] ?></td>
                  <td class="text-end"><?= $pct ?>%</td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <div class="col-lg-6">
      <div class="card h-100">
        <div class="card-header">Visualization</div>
        <div class="card-body"><canvas id="funnelChart" height="220"></canvas></div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
<script>
(function () {
  const funnel = <?= json_encode($funnel) ?>;
  const fc = document.getElementById('funnelChart');
  if (fc && window.Chart) {
    new Chart(fc, {
      type: 'bar',
      data: { labels: funnel.map(f => f.status), datasets: [{ label: 'Leads', data: funnel.map(f => f.count), backgroundColor: '#555' }] },
      options: { indexAxis: 'y', responsive: true, plugins: { legend: { display: false } }, scales: { x: { ticks: { precision: 0 } } } }
    });
  }
})();
</script>
