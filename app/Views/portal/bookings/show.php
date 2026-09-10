<?php
$fmt = fn($n) => '₹' . number_format((float) $n, 0);
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h5 class="mb-1">Booking <?= esc($row['booking_no']) ?></h5>
    <span class="badge-status badge-<?= strtolower(str_replace(' ', '', $row['booking_status'])) ?>">
      <?= esc($row['booking_status']) ?>
    </span>
  </div>
  <div>
    <?php if ($clientAuth->can('request_booking')): ?>
      <a href="<?= site_url('portal/bookings/' . $row['id'] . '/rebook') ?>" class="btn btn-sm btn-primary">
        <i class="bi bi-arrow-clockwise"></i> Rebook
      </a>
    <?php endif; ?>
    <a href="<?= site_url('portal/bookings') ?>" class="btn btn-sm btn-light ms-1">&larr; All bookings</a>
  </div>
</div>

<div class="row g-3">
  <div class="col-lg-7">
    <div class="card">
      <div class="card-header">Booking details</div>
      <div class="card-body">
        <dl class="row mb-0" style="font-size:.92rem;">
          <dt class="col-sm-4">Route</dt><dd class="col-sm-8"><?= esc(tpt_route($row['route_text'] ?? "")) ?></dd>
          <dt class="col-sm-4">Vehicle</dt><dd class="col-sm-8"><?= esc($row['vehicle_type']) ?></dd>
          <dt class="col-sm-4">Loading date</dt><dd class="col-sm-8"><?= !empty($row['loading_date']) ? esc(date('d-m-Y', strtotime($row['loading_date']))) : '—' ?></dd>
          <dt class="col-sm-4">Material / load</dt><dd class="col-sm-8"><?= esc($row['load_details'] ?? '—') ?></dd>
          <dt class="col-sm-4">Consignee</dt><dd class="col-sm-8"><?= esc($row['consignee_name'] ?? '—') ?></dd>
          <dt class="col-sm-4">Consignee mobile</dt><dd class="col-sm-8"><?= esc($row['consignee_mobile'] ?? '—') ?></dd>
          <dt class="col-sm-4">Sell rate</dt><dd class="col-sm-8"><strong><?= $fmt($row['final_sell_rate']) ?></strong></dd>
          <?php if (!empty($row['instructions'])): ?>
            <dt class="col-sm-4">Instructions</dt><dd class="col-sm-8"><?= esc($row['instructions']) ?></dd>
          <?php endif; ?>
        </dl>
      </div>
    </div>
  </div>

  <div class="col-lg-5">
    <div class="card mb-3">
      <div class="card-header">Trips</div>
      <div class="table-responsive">
        <table class="table table-sm mb-0">
          <thead><tr><th>Trip</th><th>Vehicle</th><th>Status</th></tr></thead>
          <tbody>
          <?php if (empty($trips)): ?>
            <tr><td colspan="3" class="text-center text-muted py-3">No trips yet.</td></tr>
          <?php endif; ?>
          <?php foreach ($trips as $t): ?>
            <tr>
              <td><a href="<?= site_url('portal/trips/' . $t['id']) ?>"><?= esc($t['trip_no']) ?></a></td>
              <td><?= esc($t['vehicle_number'] ?? '—') ?></td>
              <td><?= esc($t['current_status']) ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <div class="card">
      <div class="card-header">Invoices</div>
      <div class="table-responsive">
        <table class="table table-sm mb-0">
          <thead><tr><th>Invoice</th><th class="text-end">Total</th><th>Status</th></tr></thead>
          <tbody>
          <?php if (empty($invoices)): ?>
            <tr><td colspan="3" class="text-center text-muted py-3">No invoices yet.</td></tr>
          <?php endif; ?>
          <?php foreach ($invoices as $i): ?>
            <tr>
              <td><a href="<?= site_url('portal/invoices/' . $i['id']) ?>"><?= esc($i['invoice_no']) ?></a></td>
              <td class="text-end"><?= $fmt($i['total_amount']) ?></td>
              <td><?= esc($i['invoice_status']) ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
