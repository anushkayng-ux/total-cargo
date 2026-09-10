<?php
$fmt  = fn($n) => '₹' . number_format((float) $n, 0);
$fmtK = function ($n) {
    $v = (float) $n;
    if ($v >= 10000000) return '₹' . number_format($v / 10000000, 2) . 'Cr';
    if ($v >= 100000)   return '₹' . number_format($v / 100000, 2) . 'L';
    if ($v >= 1000)     return '₹' . number_format($v / 1000, 1) . 'K';
    return '₹' . number_format($v, 0);
};
$widget = fn($k) => !empty($widgetsOn[$k]);

// "Needs attention today" — pick the top-3 actionable buckets, only if non-zero
$attention = [];
if (!empty($counts['delayed_trips']))        $attention[] = ['n' => (int) $counts['delayed_trips'],    'label' => 'delayed trips',      'icon' => 'exclamation-triangle', 'color' => '#dc3545', 'url' => site_url('trips') . '?filter=delayed'];
if (!empty($counts['pod_pending']))          $attention[] = ['n' => (int) $counts['pod_pending'],      'label' => 'POD pending',        'icon' => 'inbox',                'color' => '#fd7e14', 'url' => site_url('trips') . '?pod=pending'];
if (!empty($counts['invoices_overdue']))     $attention[] = ['n' => (int) $counts['invoices_overdue'], 'label' => 'invoices overdue',   'icon' => 'clock-history',        'color' => '#dc3545', 'url' => site_url('invoices') . '?filter=overdue'];
if (!empty($counts['open_rfq']))             $attention[] = ['n' => (int) $counts['open_rfq'],         'label' => 'RFQs waiting',       'icon' => 'chat-square-text',     'color' => '#0d6efd', 'url' => site_url('rfq') . '?status=Open'];
?>

<!-- ─── Welcome ─── -->
<div class="d-flex align-items-center mb-3 flex-wrap gap-2">
  <div>
    <h5 class="m-0">Welcome back<?= !empty($currentUser['name']) ? ', ' . esc(explode(' ', $currentUser['name'])[0]) : '' ?></h5>
  </div>
  <div class="ms-auto d-flex gap-2">
    <a href="<?= site_url('dashboard/widgets') ?>" class="btn btn-sm btn-light" title="Show/hide widgets">
      <i class="bi bi-sliders"></i> Customise widgets
    </a>
  </div>
</div>

<!-- ─── Quick actions (single row, big, obvious) ─── -->
<div class="row g-2 mb-4">
  <?php if ($auth->can('leads', 'can_add')): ?>
    <div class="col-6 col-md-3">
      <a class="btn btn-primary w-100 py-3 d-flex align-items-center justify-content-center gap-2" href="<?= site_url('leads/create') ?>">
        <i class="bi bi-person-plus"></i> New Lead
      </a>
    </div>
  <?php endif; ?>
  <?php if ($auth->can('bookings', 'can_add')): ?>
    <div class="col-6 col-md-3">
      <a class="btn btn-primary w-100 py-3 d-flex align-items-center justify-content-center gap-2" href="<?= site_url('bookings/create') ?>">
        <i class="bi bi-journal-plus"></i> New Booking
      </a>
    </div>
  <?php endif; ?>
  <?php if ($auth->can('trips', 'can_edit')): ?>
    <div class="col-6 col-md-3">
      <a class="btn btn-success w-100 py-3 d-flex align-items-center justify-content-center gap-2" href="<?= site_url('dockets/create') ?>">
        <i class="bi bi-file-earmark-ruled"></i> Create Docket
      </a>
    </div>
  <?php endif; ?>
  <?php if ($auth->can('invoices', 'can_add')): ?>
    <div class="col-6 col-md-3">
      <a class="btn btn-outline-primary w-100 py-3 d-flex align-items-center justify-content-center gap-2" href="<?= site_url('invoices/consolidated') ?>">
        <i class="bi bi-collection"></i> Combined Invoice
      </a>
    </div>
  <?php endif; ?>
</div>

<!-- ─── Needs attention (only actionable items, one bar) ─── -->
<?php if (!empty($attention)): ?>
<div class="card mb-4" style="border-left:4px solid #dc3545;">
  <div class="card-body py-3">
    <div class="d-flex align-items-center flex-wrap gap-3">
      <div class="text-muted" style="font-size:.85rem;font-weight:600;text-transform:uppercase;letter-spacing:.03em;">
        Attention
      </div>
      <?php foreach (array_slice($attention, 0, 4) as $a): ?>
        <a href="<?= $a['url'] ?>" class="text-decoration-none d-flex align-items-center gap-2" style="color:<?= $a['color'] ?>;font-weight:600;">
          <i class="bi bi-<?= $a['icon'] ?>"></i>
          <span style="font-size:1.15rem;"><?= $a['n'] ?></span>
          <span class="text-muted" style="font-weight:400;font-size:.9rem;"><?= $a['label'] ?></span>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- ─── Only 4 essential KPIs (was 12) ─── -->
<?php if ($widget('kpis')): ?>
<style>
  a.stat-link { text-decoration:none; color:inherit; display:block; }
  a.stat-link .stat { transition: box-shadow .15s ease, transform .15s ease; }
  a.stat-link:hover .stat { box-shadow:0 4px 14px rgba(15,23,42,.10); transform:translateY(-1px); }
</style>
<div class="row g-3 mb-4">
  <div class="col-6 col-md-3">
    <a class="stat-link" href="<?= site_url('invoices') ?>?period=mtd">
      <div class="stat"><span class="label">Revenue this month</span><span class="value"><?= $fmtK($counts['revenue_this_month']) ?></span></div>
    </a>
  </div>
  <div class="col-6 col-md-3">
    <a class="stat-link" href="<?= site_url('receipts') ?>?period=mtd">
      <div class="stat"><span class="label">Collected this month</span><span class="value"><?= $fmtK($counts['collected_this_month']) ?></span></div>
    </a>
  </div>
  <div class="col-6 col-md-3">
    <a class="stat-link" href="<?= site_url('trips') ?>?filter=active">
      <div class="stat"><span class="label">Active trips</span><span class="value"><?= (int) $counts['active_trips'] ?></span></div>
    </a>
  </div>
  <div class="col-6 col-md-3">
    <a class="stat-link" href="<?= site_url('reports/receivables') ?>">
      <div class="stat"><span class="label">Receivables</span><span class="value"><?= $fmtK($counts['receivables_total']) ?></span></div>
    </a>
  </div>
</div>

<?php if ((float) ($counts['unbilled_billable_total'] ?? 0) > 0): ?>
<div class="alert alert-danger mb-4">
  <i class="bi bi-exclamation-triangle"></i>
  <strong>Margin leak:</strong> <?= $fmtK($counts['unbilled_billable_total']) ?> of billable expenses recorded but not yet on any invoice.
  <a href="<?= site_url('reports/unbilled-billable') ?>" class="ms-2">Review →</a>
</div>
<?php endif; ?>
<?php endif; /* kpis */ ?>

<!-- ─── Secondary KPIs + charts + top-clients/vendors (always visible) ─── -->
<div id="dashMore">

<?php if ($widget('kpis')): ?>
<div class="row g-3 mb-3">
  <?php
    $trueMargin = (float) $counts['margin_this_month'] - (float) $counts['internal_expenses_mtd'];
  ?>
  <div class="col-6 col-md-3">
    <a class="stat-link" href="<?= site_url('reports/profitability') ?>?period=mtd">
      <div class="stat"><span class="label">True margin (MTD)</span><span class="value"><?= $fmtK($trueMargin) ?></span></div>
    </a>
  </div>
  <div class="col-6 col-md-3">
    <a class="stat-link" href="<?= site_url('leads') ?>?period=mtd">
      <div class="stat"><span class="label">Leads (MTD)</span><span class="value"><?= (int) $counts['leads_this_month'] ?></span></div>
    </a>
  </div>
  <div class="col-6 col-md-3">
    <a class="stat-link" href="<?= site_url('reports/payables') ?>">
      <div class="stat"><span class="label">Payables</span><span class="value"><?= $fmtK($counts['payables_total']) ?></span></div>
    </a>
  </div>
  <div class="col-6 col-md-3">
    <a class="stat-link" href="<?= site_url('invoices') ?>?filter=unpaid">
      <div class="stat"><span class="label">Unpaid invoices</span><span class="value"><?= (int) $counts['invoices_unpaid'] ?></span></div>
    </a>
  </div>
</div>
<?php endif; ?>

<?php if ($widget('compliance')): ?>
  <div class="mt-3"><?= view('dashboard/_compliance', ['compliance' => $compliance]) ?></div>
<?php endif; ?>

<?php if ($widget('revenue') || $widget('funnel')): ?>
<div class="row g-3 mt-1">
  <?php if ($widget('revenue')): ?>
  <div class="col-lg-<?= $widget('funnel') ? 8 : 12 ?>">
    <div class="card">
      <div class="card-header">Revenue trend (last 6 months)</div>
      <div class="card-body"><canvas id="revenueChart" height="110"></canvas></div>
    </div>
  </div>
  <?php endif; ?>
  <?php if ($widget('funnel')): ?>
  <div class="col-lg-<?= $widget('revenue') ? 4 : 12 ?>">
    <div class="card h-100">
      <div class="card-header">Lead funnel</div>
      <div class="card-body"><canvas id="funnelChart" height="220"></canvas></div>
    </div>
  </div>
  <?php endif; ?>
</div>
<?php endif; ?>

<?php if ($widget('top_clients') || $widget('top_vendors')): ?>
<div class="row g-3 mt-3">
  <?php if ($widget('top_clients')): ?>
  <div class="col-lg-<?= $widget('top_vendors') ? 6 : 12 ?>">
    <div class="card h-100">
      <div class="card-header">Top clients</div>
      <div class="table-responsive">
        <table class="table mb-0">
          <thead><tr><th>Client</th><th class="text-end">Billed</th><th class="text-end">Received</th><th class="text-end">Outstanding</th></tr></thead>
          <tbody>
            <?php if (empty($topClients)): ?><tr><td colspan="4" class="text-center text-muted">No data yet.</td></tr><?php endif; ?>
            <?php foreach ($topClients as $c): ?>
              <tr>
                <td><?= esc($c['company_name']) ?></td>
                <td class="text-end"><?= $fmt($c['total_billed']) ?></td>
                <td class="text-end"><?= $fmt($c['total_received']) ?></td>
                <td class="text-end"><strong><?= $fmt($c['outstanding']) ?></strong></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <?php endif; ?>
  <?php if ($widget('top_vendors')): ?>
  <div class="col-lg-<?= $widget('top_clients') ? 6 : 12 ?>">
    <div class="card h-100">
      <div class="card-header">Top vendors</div>
      <div class="table-responsive">
        <table class="table mb-0">
          <thead><tr><th>Vendor</th><th class="text-end">Bookings</th><th class="text-end">Buy total</th><th class="text-end">Margin</th><th>Rating</th></tr></thead>
          <tbody>
            <?php if (empty($topVendors)): ?><tr><td colspan="5" class="text-center text-muted">No data yet.</td></tr><?php endif; ?>
            <?php foreach ($topVendors as $v): ?>
              <tr>
                <td><?= esc($v['company_name']) ?></td>
                <td class="text-end"><?= (int) $v['bookings_count'] ?></td>
                <td class="text-end"><?= $fmt($v['total_buy']) ?></td>
                <td class="text-end"><?= $fmt($v['total_margin']) ?></td>
                <td><?= esc(number_format((float) $v['rating'], 1)) ?>/5</td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <?php endif; ?>
</div>
<?php endif; ?>

</div><!-- /#dashMore -->

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
<script>
(function () {
  const monthly = <?= json_encode($monthly) ?>;
  const funnel  = <?= json_encode($funnel) ?>;

  // Charts render on load (dashMore is always visible now)
  function renderCharts() {
    const rc = document.getElementById('revenueChart');
    if (rc && !rc.dataset.rendered && window.Chart && monthly && monthly.length) {
      rc.dataset.rendered = '1';
      new Chart(rc, {
        type: 'bar',
        data: {
          labels: monthly.map(m => m.month),
          datasets: [
            { label: 'Billed',   data: monthly.map(m => m.billed),   backgroundColor: '#555' },
            { label: 'Received', data: monthly.map(m => m.received), backgroundColor: '#bbb' },
            { label: 'Margin',   data: monthly.map(m => m.margin),   type: 'line', borderColor: '#166c3b', backgroundColor: '#166c3b', tension: 0.3 },
          ],
        },
        options: {
          responsive: true,
          plugins: { legend: { position: 'bottom', labels: { font: { family: 'Poppins' } } } },
          scales: {
            y: { ticks: { callback: v => '₹' + (v >= 1e7 ? (v/1e7).toFixed(1) + 'Cr' : v >= 1e5 ? (v/1e5).toFixed(1) + 'L' : v >= 1e3 ? (v/1e3).toFixed(0) + 'K' : v) } }
          }
        }
      });
    }
    const fc = document.getElementById('funnelChart');
    if (fc && !fc.dataset.rendered && window.Chart) {
      fc.dataset.rendered = '1';
      new Chart(fc, {
        type: 'bar',
        data: {
          labels: funnel.map(f => f.status),
          datasets: [{ label: 'Leads', data: funnel.map(f => f.count), backgroundColor: '#555' }],
        },
        options: {
          indexAxis: 'y',
          responsive: true,
          plugins: { legend: { display: false } },
          scales: { x: { ticks: { precision: 0 } } }
        }
      });
    }
  }
  renderCharts();
})();
</script>
