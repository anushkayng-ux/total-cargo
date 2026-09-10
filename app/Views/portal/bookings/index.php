<?php
$badgeFor = function ($status) {
    return [
        'Pending' => 'badge-pending', 'Approved' => 'badge-approved',
        'Handed Over' => 'badge-loading', 'Completed' => 'badge-paid',
        'Cancelled' => 'badge-cancelled',
    ][$status] ?? 'badge-pending';
};
?>
<div class="d-flex flex-wrap gap-2 align-items-center mb-3">
  <h5 class="mb-0">My Bookings</h5>
  <?php if ($clientAuth->can('request_booking')): ?>
    <a href="<?= site_url('portal/request') ?>" class="btn btn-sm btn-primary ms-auto">
      <i class="bi bi-plus-circle"></i> Request a truck
    </a>
  <?php endif; ?>
</div>

<form method="get" class="row g-2 mb-3">
  <div class="col-12 col-md-6">
    <input class="form-control form-control-sm" name="q" placeholder="Search booking no / route" value="<?= esc($search) ?>">
  </div>
  <div class="col-8 col-md-4">
    <select name="status" class="form-select form-select-sm">
      <option value="">All statuses</option>
      <?php foreach ($statuses as $s): ?>
        <option value="<?= esc($s) ?>" <?= $s === $status ? 'selected' : '' ?>><?= esc($s) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-4 col-md-2"><button class="btn btn-sm btn-light w-100">Filter</button></div>
</form>

<div class="card">
  <div class="table-responsive">
    <table class="table table-sm align-middle mb-0">
      <thead>
        <tr><th>Booking</th><th>Route</th><th>Vehicle</th><th>Loading</th><th class="text-end">Sell</th><th>Status</th><th></th></tr>
      </thead>
      <tbody>
      <?php if (empty($rows)): ?>
        <tr><td colspan="7" class="text-center text-muted py-4">No bookings yet.</td></tr>
      <?php endif; ?>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><a href="<?= site_url('portal/bookings/' . $r['id']) ?>"><?= esc($r['booking_no']) ?></a></td>
          <td><?= esc(tpt_route($r['route_text'] ?? "")) ?></td>
          <td><?= esc($r['vehicle_type']) ?></td>
          <td><?= !empty($r['loading_date']) ? esc(date('d-m-Y', strtotime($r['loading_date']))) : '—' ?></td>
          <td class="text-end">₹<?= number_format((float) $r['final_sell_rate'], 0) ?></td>
          <td><span class="badge-status <?= $badgeFor($r['booking_status']) ?>"><?= esc($r['booking_status']) ?></span></td>
          <td>
            <?php if ($clientAuth->can('request_booking')): ?>
              <a href="<?= site_url('portal/bookings/' . $r['id'] . '/rebook') ?>" class="btn btn-sm btn-light" title="Rebook same details">
                <i class="bi bi-arrow-clockwise"></i> Rebook
              </a>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php if ($pager): ?>
  <div class="mt-3"><?= $pager->links() ?></div>
<?php endif; ?>
