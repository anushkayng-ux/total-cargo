<?php
$statusCls = match ((string) $row['booking_status']) {
    'Approved'    => 'badge-soft badge-ok',
    'Handed Over' => 'badge-soft badge-warn',
    'Completed'   => 'badge-soft badge-ok',
    'Cancelled'   => 'badge-soft badge-danger',
    default       => 'badge-soft',
};
?>
<div class="d-flex align-items-center mb-3 gap-2 flex-wrap">
  <h5 class="m-0">Booking <code><?= esc($row['booking_no']) ?></code></h5>
  <span class="<?= $statusCls ?>"><?= esc($row['booking_status']) ?></span>
  <form method="post" action="<?= site_url('bookings/' . $row['id'] . '/assign') ?>" class="d-inline-flex align-items-center gap-1 ms-auto">
    <?= csrf_field() ?>
    <i class="bi bi-person-check text-muted"></i>
    <select name="assigned_to" class="form-select form-select-sm" style="width:auto;" onchange="this.form.submit()" title="Assign this booking to a team member">
      <option value="">— Assign to —</option>
      <?php foreach (($staff ?? []) as $s): ?>
        <option value="<?= (int) $s['id'] ?>" <?= (int) ($row['assigned_to'] ?? 0) === (int) $s['id'] ? 'selected' : '' ?>>
          <?= esc($s['name']) ?><?= !empty($s['role_name']) ? ' (' . esc($s['role_name']) . ')' : '' ?>
        </option>
      <?php endforeach; ?>
    </select>
  </form>
  <a class="btn btn-sm btn-light" href="<?= site_url('bookings') ?>"><i class="bi bi-arrow-left"></i> Back</a>
  <?php if ($row['booking_status'] === 'Pending'): ?>
    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#confirmBookingModal">
      <i class="bi bi-check2-circle"></i> Confirm Booking
    </button>
  <?php endif; ?>
  <?php if ($row['booking_status'] === 'Approved'): ?>
    <form method="post" action="<?= site_url('bookings/' . $row['id'] . '/handover') ?>" class="d-inline" data-confirm="Create trip and hand over to Operations?">
      <?= csrf_field() ?><button class="btn btn-sm btn-primary"><i class="bi bi-arrow-right-circle"></i> Handover → Trip</button>
    </form>
  <?php endif; ?>
  <a class="btn btn-sm btn-outline-dark" href="<?= site_url('bookings/' . $row['id'] . '/edit') ?>"><i class="bi bi-pencil"></i> Edit</a>
  <?php if (!in_array($row['booking_status'], ['Completed','Cancelled'], true)): ?>
    <form method="post" action="<?= site_url('bookings/' . $row['id'] . '/cancel') ?>" class="d-inline" data-confirm="Cancel this booking?">
      <?= csrf_field() ?><button class="btn btn-sm btn-light"><i class="bi bi-x-octagon"></i> Cancel</button>
    </form>
  <?php endif; ?>
</div>

<?php if ($row['booking_status'] === 'Pending'): ?>
<!-- Confirm Booking: capture vendor + rate + vehicle before approving -->
<div class="modal fade" id="confirmBookingModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <form method="post" action="<?= site_url('bookings/' . $row['id'] . '/approve') ?>">
        <?= csrf_field() ?>
        <div class="modal-header">
          <h6 class="modal-title">Confirm Booking <code><?= esc($row['booking_no']) ?></code></h6>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <p class="text-muted small mb-3">Add the vendor, rate and vehicle, then confirm. All fields are optional — you can fill in whatever is finalised.</p>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Vendor</label>
              <select class="form-select" name="vendor_id">
                <option value="">— Select —</option>
                <?php foreach (($vendors ?? []) as $vn): ?>
                  <option value="<?= $vn['id'] ?>" <?= (int) ($row['vendor_id'] ?? 0) === (int) $vn['id'] ? 'selected' : '' ?>><?= esc($vn['company_name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label">Buy Rate (INR)</label>
              <input type="number" step="0.01" class="form-control" name="final_buy_rate" value="<?= esc((float) $row['final_buy_rate'] ?: '') ?>" placeholder="Pay vendor">
            </div>
            <div class="col-md-3">
              <label class="form-label">Sell Rate (INR)</label>
              <input type="number" step="0.01" class="form-control" name="final_sell_rate" value="<?= esc((float) $row['final_sell_rate'] ?: '') ?>" placeholder="Charge client">
            </div>
            <div class="col-md-6">
              <label class="form-label">Vehicle Type</label>
              <input class="form-control" name="vehicle_type" value="<?= esc($row['vehicle_type'] ?? '') ?>" placeholder="e.g. 32ft MXL, Trailer">
            </div>
            <div class="col-md-6">
              <label class="form-label">Vehicle Number</label>
              <input class="form-control text-uppercase" name="vehicle_number" value="<?= esc($row['vehicle_number'] ?? '') ?>" placeholder="e.g. MH12AB1234">
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary"><i class="bi bi-check2-circle"></i> Confirm Booking</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>

<div class="row g-3">
  <div class="col-lg-8">
    <div class="card mb-3">
      <div class="card-header">Booking Details</div>
      <div class="card-body">
        <div class="row g-3" style="font-size:.92rem;">
          <div class="col-md-6"><div class="text-muted">Client</div><div><?= esc($row['client_company'] ?? '—') ?></div></div>
          <div class="col-md-6"><div class="text-muted">Vendor</div><div><?= esc($row['vendor_company'] ?? '—') ?></div></div>
          <div class="col-md-3"><div class="text-muted">Pickup</div><div><?= esc($row['pickup_city'] ?? '') ?: '—' ?></div></div>
          <div class="col-md-3"><div class="text-muted">Drop</div><div><?= esc($row['drop_city'] ?? '') ?: '—' ?></div></div>
          <div class="col-md-3"><div class="text-muted">Vehicle Type</div><div><?= esc($row['vehicle_type']) ?: '—' ?></div></div>
          <div class="col-md-3"><div class="text-muted">Vehicle No</div><div><?= !empty($row['vehicle_number']) ? '<code>' . esc($row['vehicle_number']) . '</code>' : '—' ?></div></div>
          <div class="col-md-3"><div class="text-muted">Loading Date</div><div><?= esc($row['loading_date']) ?: '—' ?></div></div>
          <div class="col-md-4"><div class="text-muted">Buy Rate</div><div>₹<?= number_format((float) $row['final_buy_rate'], 2) ?></div></div>
          <div class="col-md-4"><div class="text-muted">Sell Rate</div><div>₹<?= number_format((float) $row['final_sell_rate'], 2) ?></div></div>
          <div class="col-md-4"><div class="text-muted">Margin</div><div><strong>₹<?= number_format((float) $row['margin_amount'], 2) ?></strong></div></div>
          <div class="col-md-12"><div class="text-muted">Load Details</div><div><?= esc($row['load_details']) ?></div></div>
          <?php if (!empty($row['instructions'])): ?>
            <div class="col-md-12"><div class="text-muted">Instructions</div><div><?= nl2br(esc($row['instructions'])) ?></div></div>
          <?php endif; ?>

          <?php if (!empty($row['consignor_name']) || !empty($row['consignor_mobile']) || !empty($row['consignor_address'])): ?>
            <div class="col-12"><hr class="my-1"><small class="text-muted">Consignor (Shipper)</small></div>
            <div class="col-md-6"><div class="text-muted">Consignor Name</div><div><?= esc($row['consignor_name'] ?? '—') ?></div></div>
            <div class="col-md-3"><div class="text-muted">Mobile</div><div><?= esc($row['consignor_mobile'] ?? '—') ?></div></div>
            <div class="col-md-3"><div class="text-muted">GSTIN</div><div><?= !empty($row['consignor_gstin']) ? '<code>' . esc($row['consignor_gstin']) . '</code>' : '—' ?></div></div>
            <div class="col-md-9"><div class="text-muted">Address</div><div><?= esc($row['consignor_address'] ?? '—') ?></div></div>
            <div class="col-md-3"><div class="text-muted">State</div><div><?= esc($row['consignor_state'] ?? '—') ?></div></div>
          <?php endif; ?>

          <?php if (!empty($row['consignee_name']) || !empty($row['consignee_mobile']) || !empty($row['consignee_address'])): ?>
            <div class="col-12"><hr class="my-1"><small class="text-muted">Consignee (Recipient)</small></div>
            <div class="col-md-6"><div class="text-muted">Consignee Name</div><div><?= esc($row['consignee_name'] ?? '—') ?></div></div>
            <div class="col-md-3"><div class="text-muted">Mobile</div><div><?= esc($row['consignee_mobile'] ?? '—') ?></div></div>
            <div class="col-md-3"><div class="text-muted">GSTIN</div><div><?= !empty($row['consignee_gstin']) ? '<code>' . esc($row['consignee_gstin']) . '</code>' : '—' ?></div></div>
            <div class="col-md-12"><div class="text-muted">Address</div><div><?= esc($row['consignee_address'] ?? '—') ?></div></div>
          <?php endif; ?>
          <div class="col-md-4"><div class="text-muted">Lead</div><div><?= $row['lead_no'] ? '<a href="' . site_url('leads/' . $row['lead_id']) . '"><code>' . esc($row['lead_no']) . '</code></a>' : '—' ?></div></div>
          <div class="col-md-4"><div class="text-muted">Created By</div><div><?= esc($row['created_by_name'] ?? '—') ?></div></div>
          <div class="col-md-4"><div class="text-muted">Approved</div>
            <div><?= $row['approved_at'] ? esc($row['approved_at']) : '—' ?></div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="card">
      <div class="card-header">Operations Trip</div>
      <div class="card-body">
        <?php if ($trip): ?>
          <p class="mb-1">Trip: <a href="<?= site_url('trips/' . $trip['id']) ?>"><code><?= esc($trip['trip_no']) ?></code></a></p>
          <p class="mb-1">Status: <span class="badge-soft"><?= esc($trip['current_status']) ?></span></p>
          <p class="mb-0">POD: <span class="badge-soft <?= $trip['pod_status'] === 'Received' ? 'badge-ok' : 'badge-warn' ?>"><?= esc($trip['pod_status']) ?></span></p>
        <?php else: ?>
          <p class="text-muted mb-0">No trip yet. Approve the booking and click <strong>Handover → Trip</strong>.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php if (function_exists('tpt_feature_enabled') && tpt_feature_enabled('loading_slots')):
    $slots = (new \App\Models\LoadingSlotModel())->forBooking((int) $row['id']);
?>
<div class="mt-3 card">
  <div class="card-header"><i class="bi bi-calendar-check"></i> Loading slots</div>
  <div class="card-body">
    <?php if (!empty($slots)): ?>
      <div class="table-responsive mb-2">
        <table class="table table-sm align-middle mb-0">
          <thead><tr><th>Plant</th><th>Date</th><th>Window</th><th>Gate pass</th><th>Status</th><th></th></tr></thead>
          <tbody>
            <?php foreach ($slots as $s): ?>
              <tr>
                <td><?= esc($s['plant_name']) ?></td>
                <td><?= esc(date('d-m-Y', strtotime($s['slot_date']))) ?></td>
                <td><?= esc(($s['slot_window_start'] ?? '') . ($s['slot_window_end'] ? ' – ' . $s['slot_window_end'] : '')) ?></td>
                <td><?= esc($s['gate_pass_no'] ?: '—') ?></td>
                <td><span class="badge-soft <?= $s['status']==='Confirmed' ? 'badge-ok' : ($s['status']==='Used'?'badge-ok':($s['status']==='Cancelled'||$s['status']==='Missed'?'badge-danger':'badge-warn')) ?>"><?= esc($s['status']) ?></span></td>
                <td>
                  <?php foreach ([['Requested','confirm','Confirm'], ['Confirmed','use','Mark used'], ['Confirmed','miss','Mark missed'], ['Requested','cancel','Cancel']] as $act): if ($s['status'] !== $act[0]) continue; ?>
                    <form method="post" action="<?= site_url('bookings/' . $row['id'] . '/slots/' . $s['id']) ?>" class="d-inline">
                      <?= csrf_field() ?><input type="hidden" name="action" value="<?= $act[1] ?>">
                      <button class="btn btn-sm btn-light"><?= esc($act[2]) ?></button>
                    </form>
                  <?php endforeach; ?>
                  <form method="post" action="<?= site_url('bookings/' . $row['id'] . '/slots/' . $s['id'] . '/delete') ?>" class="d-inline" onsubmit="return confirm('Remove slot?');">
                    <?= csrf_field() ?><button class="btn btn-sm btn-light"><i class="bi bi-trash"></i></button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>

    <form method="post" action="<?= site_url('bookings/' . $row['id'] . '/slots') ?>" class="row g-2 align-items-end">
      <?= csrf_field() ?>
      <div class="col-md-3"><label class="form-label">Plant *</label><input class="form-control form-control-sm" name="plant_name" required></div>
      <div class="col-md-2"><label class="form-label">Date *</label><input type="date" class="form-control form-control-sm" name="slot_date" required></div>
      <div class="col-md-2"><label class="form-label">From</label><input type="time" class="form-control form-control-sm" name="slot_window_start"></div>
      <div class="col-md-2"><label class="form-label">To</label><input type="time" class="form-control form-control-sm" name="slot_window_end"></div>
      <div class="col-md-2"><label class="form-label">Gate pass</label><input class="form-control form-control-sm" name="gate_pass_no"></div>
      <div class="col-md-1"><button class="btn btn-sm btn-primary w-100">Add</button></div>
    </form>
  </div>
</div>
<?php endif; ?>

<div class="mt-3">
  <?= view('partials/_comments', [
        'threadType'     => $threadType ?? 'booking',
        'threadId'       => $threadId ?? ($row['id'] ?? 0),
        'threadComments' => $threadComments ?? [],
        'currentUser'    => $currentUser ?? null,
        'auth'           => $auth ?? null,
  ]) ?>
</div>
