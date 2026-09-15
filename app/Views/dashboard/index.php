<?php
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

<?= tpt_toolbar(['close_href' => site_url('dashboard'), 'auth' => $auth]) ?>

<div class="tabs" role="tablist">
  <div class="tab active">Master Records</div>
  <div class="spacer"></div>
  <div class="recordnav">
    <a href="<?= site_url('dashboard/widgets') ?>"><i class="bi bi-sliders"></i> Customise widgets</a>
  </div>
</div>

<div class="formwrap">

  <div class="mb-2" style="font-weight:700;color:var(--v2-navy-700,#163a7a);font-size:14px;">
    Welcome back<?= !empty($currentUser['name']) ? ', ' . esc(explode(' ', $currentUser['name'])[0]) : '' ?>
  </div>

  <div class="mb-2" style="font-weight:700;color:var(--v2-navy-700,#163a7a);">Select a master to open :</div>
  <?php $masters = tpt_hub_buckets($auth)['masters']; ?>
  <?php if (!empty($masters)): ?>
  <div class="masterlinks mb-3">
    <?php foreach ($masters as $m): ?>
      <a class="mastercard" href="<?= site_url($m['url']) ?>">
        <div class="mastercard-icon"><i class="bi bi-<?= esc($m['icon']) ?>"></i></div>
        <div class="mastercard-label"><?= esc($m['label']) ?> Master</div>
      </a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <div class="mb-2" style="font-weight:700;color:var(--v2-navy-700,#163a7a);">Other modules :</div>
  <div class="masterlinks mb-3">
    <a class="mastercard" href="<?= site_url('hub/transportation') ?>">
      <div class="mastercard-icon"><i class="bi bi-truck"></i></div>
      <div class="mastercard-label">Transportation</div>
    </a>
    <a class="mastercard" href="<?= site_url('hub/accounts') ?>">
      <div class="mastercard-icon"><i class="bi bi-cash-coin"></i></div>
      <div class="mastercard-label">Accounts</div>
    </a>
    <a class="mastercard" href="<?= site_url('hub/administration') ?>">
      <div class="mastercard-icon"><i class="bi bi-gear"></i></div>
      <div class="mastercard-label">Administration</div>
    </a>
    <a class="mastercard" href="<?= site_url('_search') ?>">
      <div class="mastercard-icon"><i class="bi bi-search"></i></div>
      <div class="mastercard-label">Search</div>
    </a>
  </div>

  <div class="row g-2 mb-3">
    <?php if ($auth->can('leads', 'can_add')): ?>
      <div class="col-6 col-md-3">
        <a class="btn btn-primary w-100 d-flex align-items-center justify-content-center gap-2" href="<?= site_url('leads/create') ?>">
          <i class="bi bi-person-plus"></i> New Lead
        </a>
      </div>
    <?php endif; ?>
    <?php if ($auth->can('bookings', 'can_add')): ?>
      <div class="col-6 col-md-3">
        <a class="btn btn-primary w-100 d-flex align-items-center justify-content-center gap-2" href="<?= site_url('bookings/create') ?>">
          <i class="bi bi-journal-plus"></i> New Booking
        </a>
      </div>
    <?php endif; ?>
    <?php if ($auth->can('trips', 'can_edit')): ?>
      <div class="col-6 col-md-3">
        <a class="btn btn-success w-100 d-flex align-items-center justify-content-center gap-2" href="<?= site_url('dockets/create') ?>">
          <i class="bi bi-file-earmark-ruled"></i> Create Docket
        </a>
      </div>
    <?php endif; ?>
    <?php if ($auth->can('invoices', 'can_add')): ?>
      <div class="col-6 col-md-3">
        <a class="btn btn-outline-primary w-100 d-flex align-items-center justify-content-center gap-2" href="<?= site_url('invoices/consolidated') ?>">
          <i class="bi bi-collection"></i> Combined Invoice
        </a>
      </div>
    <?php endif; ?>
  </div>

  <?php if (!empty($attention)): ?>
  <div class="mb-3 d-flex align-items-center flex-wrap gap-3" style="background:#fff;border:1px solid var(--v2-border-3,#b9c6de);border-left:4px solid #dc3545;border-radius:4px;padding:8px 14px;">
    <span style="font-size:.78rem;font-weight:700;color:#5c6b88;text-transform:uppercase;letter-spacing:.4px;">Attention</span>
    <?php foreach (array_slice($attention, 0, 4) as $a): ?>
      <a href="<?= $a['url'] ?>" class="text-decoration-none d-flex align-items-center gap-2" style="color:<?= $a['color'] ?>;font-weight:600;">
        <i class="bi bi-<?= $a['icon'] ?>"></i>
        <span style="font-size:1.05rem;"><?= $a['n'] ?></span>
        <span class="text-muted" style="font-weight:400;font-size:.85rem;"><?= $a['label'] ?></span>
      </a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <?php if ($widget('kpis')): ?>
  <div class="kpirow">
    <a class="kpi" href="<?= site_url('invoices') ?>?period=mtd" style="display:block;text-decoration:none;color:inherit;">
      <div class="num"><?= $fmtK($counts['revenue_this_month']) ?></div>
      <div class="lbl">Revenue this month</div>
    </a>
    <a class="kpi" href="<?= site_url('trips') ?>?filter=active" style="display:block;text-decoration:none;color:inherit;">
      <div class="num"><?= (int) $counts['active_trips'] ?></div>
      <div class="lbl">Active trips</div>
    </a>
    <a class="kpi" href="<?= site_url('reports/receivables') ?>" style="display:block;text-decoration:none;color:inherit;">
      <div class="num"><?= $fmtK($counts['receivables_total']) ?></div>
      <div class="lbl">Receivables</div>
    </a>
    <a class="kpi" href="<?= site_url('invoices') ?>?filter=unpaid" style="display:block;text-decoration:none;color:inherit;">
      <div class="num"><?= (int) $counts['invoices_unpaid'] ?></div>
      <div class="lbl">Unpaid invoices</div>
    </a>
  </div>

  <?php if ((float) ($counts['unbilled_billable_total'] ?? 0) > 0): ?>
  <div class="mb-3" style="background:#fff;border:1px solid #f1aeb5;border-left:4px solid #dc3545;border-radius:4px;padding:8px 14px;font-size:.85rem;">
    <i class="bi bi-exclamation-triangle text-danger"></i>
    <strong>Margin leak:</strong> <?= $fmtK($counts['unbilled_billable_total']) ?> of billable expenses recorded but not yet on any invoice.
    <a href="<?= site_url('reports/unbilled-billable') ?>" class="ms-2">Review →</a>
  </div>
  <?php endif; ?>
  <?php endif; /* kpis */ ?>

  <?php if ($widget('compliance')): ?>
    <div class="mb-3"><?= view('dashboard/_compliance', ['compliance' => $compliance]) ?></div>
  <?php endif; ?>

  <?php if ($widget('revenue') || $widget('funnel')): ?>
  <div class="row g-3 mb-3">
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
  <div class="row g-3 mb-3">
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
                  <td class="text-end"><?= $fmtK($c['total_billed']) ?></td>
                  <td class="text-end"><?= $fmtK($c['total_received']) ?></td>
                  <td class="text-end"><strong><?= $fmtK($c['outstanding']) ?></strong></td>
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
                  <td class="text-end"><?= $fmtK($v['total_buy']) ?></td>
                  <td class="text-end"><?= $fmtK($v['total_margin']) ?></td>
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

</div>

<?php if ($widget('revenue') || $widget('funnel')): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
<script>
(function () {
  const monthly = <?= json_encode($monthly ?? []) ?>;
  const funnel  = <?= json_encode($funnel ?? []) ?>;

  const rc = document.getElementById('revenueChart');
  if (rc && window.Chart && monthly.length) {
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
        plugins: { legend: { position: 'bottom' } },
        scales: {
          y: { ticks: { callback: v => '₹' + (v >= 1e7 ? (v/1e7).toFixed(1) + 'Cr' : v >= 1e5 ? (v/1e5).toFixed(1) + 'L' : v >= 1e3 ? (v/1e3).toFixed(0) + 'K' : v) } }
        }
      }
    });
  }
  const fc = document.getElementById('funnelChart');
  if (fc && window.Chart) {
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
})();
</script>
<?php endif; ?>
