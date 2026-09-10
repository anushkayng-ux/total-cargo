<?php
$fmt = function ($n) {
    $v = (float) $n;
    if ($v >= 10000000) return '₹' . number_format($v / 10000000, 2) . ' Cr';
    if ($v >= 100000)   return '₹' . number_format($v / 100000, 2) . ' L';
    if ($v >= 1000)     return '₹' . number_format($v / 1000, 1) . 'K';
    return '₹' . number_format($v, 0);
};
$badgeFor = function ($status) {
    $map = [
        'Pending' => 'badge-pending', 'Approved' => 'badge-approved',
        'Issued' => 'badge-issued', 'Paid' => 'badge-paid',
        'Partially Paid' => 'badge-issued', 'Cancelled' => 'badge-cancelled',
        'Delivered' => 'badge-delivered', 'POD Received' => 'badge-delivered',
        'Closed' => 'badge-closed', 'In Transit' => 'badge-transit',
        'Loading' => 'badge-loading', 'Unloading' => 'badge-loading',
        'Arrived' => 'badge-loading', 'Vehicle Placed' => 'badge-loading',
        'Booking Created' => 'badge-pending',
    ];
    return $map[$status] ?? 'badge-pending';
};
$greeting = (function () {
    $h = (int) date('G');
    if ($h < 12) return 'Good morning';
    if ($h < 17) return 'Good afternoon';
    return 'Good evening';
})();
$firstName = trim(explode(' ', (string) ($currentUser['name'] ?? ''))[0] ?? '');
?>
<div class="d-flex flex-wrap align-items-end justify-content-between mb-3 gap-2">
  <div>
    <h4 class="mb-1"><?= esc($greeting) ?><?= $firstName !== '' ? ', ' . esc($firstName) : '' ?></h4>
    <div class="text-muted" style="font-size:.85rem;">Here's a snapshot of your account today.</div>
  </div>
  <?php if (!empty($clientAuth) && $clientAuth->can('request_booking')): ?>
    <a href="<?= site_url('portal/request') ?>" class="btn btn-dark btn-sm">
      <i class="bi bi-plus-circle"></i> Request a truck
    </a>
  <?php endif; ?>
</div>

<div class="row g-3">
  <div class="col-6 col-lg-3">
    <div class="stat-card">
      <div class="stat-icon blue"><i class="bi bi-truck"></i></div>
      <div class="stat-body">
        <span class="label">Active trips</span>
        <span class="value"><?= (int) $activeTrips ?></span>
        <span class="sub">currently moving</span>
      </div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="stat-card">
      <div class="stat-icon green"><i class="bi bi-check2-circle"></i></div>
      <div class="stat-body">
        <span class="label">Delivered (MTD)</span>
        <span class="value"><?= (int) $deliveredThisMonth ?></span>
        <span class="sub">this month</span>
      </div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="stat-card">
      <div class="stat-icon amber"><i class="bi bi-hourglass-split"></i></div>
      <div class="stat-body">
        <span class="label">Pending bookings</span>
        <span class="value"><?= (int) $pendingBookings ?></span>
        <span class="sub">awaiting confirmation</span>
      </div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="stat-card">
      <div class="stat-icon <?= $overdueCount > 0 ? 'red' : 'slate' ?>"><i class="bi bi-cash-stack"></i></div>
      <div class="stat-body">
        <span class="label">Outstanding</span>
        <span class="value" style="<?= $overdueCount > 0 ? 'color:#b00020;' : '' ?>">
          <?= $fmt($outstanding) ?>
        </span>
        <?php if ($overdueCount > 0): ?>
          <span class="sub danger"><?= (int) $overdueCount ?> overdue</span>
        <?php else: ?>
          <span class="sub">all current</span>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<div class="row g-3 mt-1">
  <div class="col-lg-7">
    <div class="card swap-table-mobile">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-truck me-1 text-muted"></i> Recent trips</span>
        <a href="<?= site_url('portal/trips') ?>" class="btn btn-sm btn-light">View all</a>
      </div>

      <!-- Desktop table -->
      <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
          <thead><tr><th>Trip</th><th>Route</th><th>Status</th><th>Dispatch</th></tr></thead>
          <tbody>
          <?php if (empty($recentTrips)): ?>
            <tr><td colspan="4" class="text-center text-muted py-3">No trips yet.</td></tr>
          <?php endif; ?>
          <?php foreach ($recentTrips as $t): ?>
            <tr>
              <td>
                <a href="<?= site_url('portal/trips/' . $t['id']) ?>"><?= esc($t['trip_no']) ?></a>
                <?php if (!empty($t['lr_no'])): ?><br><small class="text-muted">LR <?= esc($t['lr_no']) ?></small><?php endif; ?>
              </td>
              <td><?= esc(tpt_route($t['route_text'] ?? "")) ?></td>
              <td><span class="badge-status <?= $badgeFor($t['current_status']) ?>"><?= esc($t['current_status']) ?></span></td>
              <td><?= !empty($t['dispatch_datetime']) ? esc(date('d-m', strtotime($t['dispatch_datetime']))) : '—' ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <!-- Mobile stacked list -->
      <div class="mobile-list">
        <?php if (empty($recentTrips)): ?>
          <div class="empty">No trips yet.</div>
        <?php endif; ?>
        <?php foreach ($recentTrips as $t): ?>
          <a href="<?= site_url('portal/trips/' . $t['id']) ?>" class="list-row" style="text-decoration:none;color:inherit;">
            <div class="body">
              <div class="title"><?= esc($t['trip_no']) ?>
                <?php if (!empty($t['lr_no'])): ?><span class="text-muted" style="font-weight:400;">· LR <?= esc($t['lr_no']) ?></span><?php endif; ?>
              </div>
              <div class="sub"><?= esc(tpt_route($t['route_text'] ?? "")) ?></div>
              <div class="sub" style="margin-top:.35rem;">
                <span class="badge-status <?= $badgeFor($t['current_status']) ?>"><?= esc($t['current_status']) ?></span>
                <?php if (!empty($t['dispatch_datetime'])): ?>
                  <span class="ms-2"><i class="bi bi-calendar3"></i> <?= esc(date('d-m', strtotime($t['dispatch_datetime']))) ?></span>
                <?php endif; ?>
              </div>
            </div>
            <div class="right"><i class="bi bi-chevron-right text-muted"></i></div>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <div class="col-lg-5">
    <div class="card swap-table-mobile">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-receipt me-1 text-muted"></i> Recent invoices</span>
        <a href="<?= site_url('portal/invoices') ?>" class="btn btn-sm btn-light">View all</a>
      </div>

      <!-- Desktop table -->
      <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
          <thead><tr><th>Invoice</th><th class="text-end">Total</th><th class="text-end">Balance</th></tr></thead>
          <tbody>
          <?php if (empty($recentInvoices)): ?>
            <tr><td colspan="3" class="text-center text-muted py-3">No invoices yet.</td></tr>
          <?php endif; ?>
          <?php foreach ($recentInvoices as $i): ?>
            <tr>
              <td>
                <a href="<?= site_url('portal/invoices/' . $i['id']) ?>"><?= esc($i['invoice_no']) ?></a>
                <br><small class="text-muted"><?= esc(date('d-m-Y', strtotime($i['invoice_date']))) ?></small>
              </td>
              <td class="text-end"><?= $fmt($i['total_amount']) ?></td>
              <td class="text-end">
                <strong><?= $fmt($i['balance_due']) ?></strong>
                <br><span class="badge-status <?= $badgeFor($i['invoice_status']) ?>"><?= esc($i['invoice_status']) ?></span>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <!-- Mobile stacked list -->
      <div class="mobile-list">
        <?php if (empty($recentInvoices)): ?>
          <div class="empty">No invoices yet.</div>
        <?php endif; ?>
        <?php foreach ($recentInvoices as $i): ?>
          <a href="<?= site_url('portal/invoices/' . $i['id']) ?>" class="list-row" style="text-decoration:none;color:inherit;">
            <div class="body">
              <div class="title"><?= esc($i['invoice_no']) ?></div>
              <div class="sub"><?= esc(date('d-m-Y', strtotime($i['invoice_date']))) ?></div>
              <div class="sub" style="margin-top:.35rem;">
                <span class="badge-status <?= $badgeFor($i['invoice_status']) ?>"><?= esc($i['invoice_status']) ?></span>
              </div>
            </div>
            <div class="right">
              <div class="amt"><?= $fmt($i['balance_due']) ?></div>
              <div class="sub">of <?= $fmt($i['total_amount']) ?></div>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>
