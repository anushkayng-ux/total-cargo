<?php
$fmt = function ($n) {
    $v = (float) $n;
    if ($v >= 10000000) return '₹' . number_format($v / 10000000, 2) . ' Cr';
    if ($v >= 100000)   return '₹' . number_format($v / 100000, 2) . ' L';
    if ($v >= 1000)     return '₹' . number_format($v / 1000, 1) . 'K';
    return '₹' . number_format($v, 0);
};
$marMtdPct = $revMtd > 0 ? round(($marMtd / $revMtd) * 100, 1) : 0;
$marYtdPct = $revYtd > 0 ? round(($marYtd / $revYtd) * 100, 1) : 0;

$extra = '<a class="btn btn-sm btn-outline-dark" href="' . site_url('reports/profitability') . '"><i class="bi bi-graph-up-arrow"></i> Profitability detail</a>';
?>
<style>
  .exec-stat { background:#fff; border:1px solid #e3e7ee; border-radius:12px; padding:1rem 1.1rem; box-shadow:0 1px 2px rgba(15,23,42,.04); }
  .exec-stat .label { font-size:.7rem; text-transform:uppercase; letter-spacing:.06em; color:#6b7280; font-weight:600; }
  .exec-stat .value { font-size:1.55rem; font-weight:700; color:#0f172a; letter-spacing:-.01em; margin-top:.25rem; }
  .exec-stat .sub { font-size:.78rem; color:#6b7280; margin-top:.15rem; }
  .exec-stat.warn .value { color:#b00020; }
  .exec-stat.ok .value { color:#065f46; }
  .leader-row { display:flex; align-items:center; padding:.5rem 0; border-bottom:1px solid #f1f3f5; font-size:.92rem; }
  .leader-row:last-child { border-bottom:0; }
  .leader-row .rank { width:24px; height:24px; border-radius:50%; background:#f3f4f6; color:#6b7280; display:inline-flex; align-items:center; justify-content:center; font-size:.78rem; font-weight:700; margin-right:.7rem; flex-shrink:0; }
  .leader-row .name { flex:1; min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
  .leader-row .val  { font-weight:600; color:#0f172a; margin-left:.6rem; }
  .leader-row .meta { font-size:.72rem; color:#9ca3af; margin-left:.5rem; }
  .star-display { letter-spacing:.1rem; color:#f59e0b; }
</style>

<?= tpt_toolbar([
    'close_href' => site_url('reports'),
    'extra'      => $extra,
    'auth'       => $auth,
]) ?>
<div class="tabs" role="tablist">
  <div class="tab active">Executive Dashboard</div>
  <div class="spacer"></div>
  <div class="recordnav">Today · <?= esc(date('d M Y')) ?></div>
</div>

<div class="formwrap" style="flex:0 0 auto;">
  <p class="text-muted mb-3" style="font-size:.85rem;">Snapshot for management — top-line KPIs, leaderboards, and recent trip activity.</p>

  <!-- ─── Top-line KPIs ─── -->
  <div class="row g-3 mb-3">
    <div class="col-md-3 col-6">
      <div class="exec-stat">
        <div class="label">Revenue · MTD</div>
        <div class="value"><?= esc($fmt($revMtd)) ?></div>
        <div class="sub">since <?= esc(date('d-m', strtotime($monStart))) ?></div>
      </div>
    </div>
    <div class="col-md-3 col-6">
      <div class="exec-stat">
        <div class="label">Revenue · YTD</div>
        <div class="value"><?= esc($fmt($revYtd)) ?></div>
        <div class="sub">since <?= esc(date('01 Jan Y')) ?></div>
      </div>
    </div>
    <div class="col-md-3 col-6">
      <div class="exec-stat">
        <div class="label">Margin · MTD</div>
        <div class="value"><?= esc($fmt($marMtd)) ?></div>
        <div class="sub"><?= esc((string) $marMtdPct) ?>% of revenue</div>
      </div>
    </div>
    <div class="col-md-3 col-6">
      <div class="exec-stat">
        <div class="label">Margin · YTD</div>
        <div class="value"><?= esc($fmt($marYtd)) ?></div>
        <div class="sub"><?= esc((string) $marYtdPct) ?>% of revenue</div>
      </div>
    </div>
  </div>

  <div class="row g-3 mb-3">
    <div class="col-md-3 col-6">
      <div class="exec-stat">
        <div class="label">Active trips</div>
        <div class="value"><?= (int) $activeTrips ?></div>
        <div class="sub">on the road / loading / unloading</div>
      </div>
    </div>
    <div class="col-md-3 col-6">
      <div class="exec-stat <?= $overdueAr > 0 ? 'warn' : '' ?>">
        <div class="label">Receivables outstanding</div>
        <div class="value"><?= esc($fmt($arOutstanding)) ?></div>
        <div class="sub"><?php if ($overdueAr > 0): ?><span class="text-danger"><?= (int) $overdueAr ?> overdue invoice<?= $overdueAr === 1 ? '' : 's' ?></span><?php else: ?>all current<?php endif; ?></div>
      </div>
    </div>
    <div class="col-md-3 col-6">
      <div class="exec-stat">
        <div class="label">Payables outstanding</div>
        <div class="value"><?= esc($fmt($apOutstanding)) ?></div>
        <div class="sub">vendor bills pending</div>
      </div>
    </div>
    <div class="col-md-3 col-6">
      <div class="exec-stat <?= $feedbackAvg >= 4 ? 'ok' : '' ?>">
        <div class="label">Client feedback · 30 d</div>
        <div class="value">
          <?php if ($feedbackN > 0): ?>
            <span class="star-display" style="font-size:1.55rem;">
              <?php for ($i = 0; $i < (int) round($feedbackAvg); $i++) echo '★'; ?><?php for ($i = (int) round($feedbackAvg); $i < 5; $i++) echo '<span style="color:#d1d5db;">★</span>'; ?>
            </span>
          <?php else: ?>
            —
          <?php endif; ?>
        </div>
        <div class="sub"><?= $feedbackN > 0 ? esc((string) $feedbackAvg) . '/5 from ' . (int) $feedbackN . ' rating' . ($feedbackN === 1 ? '' : 's') : 'no feedback yet' ?></div>
      </div>
    </div>
  </div>

  <!-- ─── Top leaderboards ─── -->
  <div class="row g-3">
    <div class="col-lg-6">
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <span><i class="bi bi-trophy"></i> Top 5 clients · YTD revenue</span>
          <small class="text-muted">since <?= esc(date('01 Jan Y')) ?></small>
        </div>
        <div class="card-body">
          <?php if (empty($topClients)): ?>
            <div class="text-muted text-center py-3">No invoices yet this year.</div>
          <?php endif; ?>
          <?php foreach ($topClients as $i => $c): ?>
            <div class="leader-row">
              <span class="rank"><?= $i + 1 ?></span>
              <span class="name"><?= esc($c['company_name'] ?? '—') ?></span>
              <span class="val"><?= esc($fmt($c['revenue'])) ?></span>
              <span class="meta">· <?= (int) $c['invoices'] ?> inv</span>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <div class="col-lg-6">
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <span><i class="bi bi-truck"></i> Top 5 vendors · YTD buy spend</span>
          <small class="text-muted">since <?= esc(date('01 Jan Y')) ?></small>
        </div>
        <div class="card-body">
          <?php if (empty($topVendors)): ?>
            <div class="text-muted text-center py-3">No bookings yet this year.</div>
          <?php endif; ?>
          <?php foreach ($topVendors as $i => $v): ?>
            <div class="leader-row">
              <span class="rank"><?= $i + 1 ?></span>
              <span class="name"><?= esc($v['company_name'] ?? '—') ?></span>
              <span class="val"><?= esc($fmt($v['buy'])) ?></span>
              <span class="meta">· <?= (int) $v['bookings'] ?> trips</span>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ─── Recent activity ─── -->
<div class="gridwrap">
  <div class="table-responsive">
    <table class="table grid mb-0">
      <thead><tr><th>Trip</th><th>Client</th><th>Vendor</th><th>Route</th><th>Status</th><th>Updated</th></tr></thead>
      <tbody>
        <?php if (empty($recentTrips)): ?>
          <tr><td colspan="6" class="text-center text-muted py-3">No trip activity.</td></tr>
        <?php endif; ?>
        <?php foreach ($recentTrips as $t): ?>
          <tr>
            <td><a href="<?= site_url('trips/' . (int) $t['id']) ?>"><?= esc($t['trip_no']) ?></a></td>
            <td><?= esc($t['client_company'] ?? '—') ?></td>
            <td><?= esc($t['vendor_company'] ?? '—') ?></td>
            <td><?= esc($t['route_text'] ?? '—') ?></td>
            <td><span class="badge-soft"><?= esc($t['current_status']) ?></span></td>
            <td class="text-muted small"><?= esc(date('d-m · H:i', strtotime((string) $t['updated_at']))) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
