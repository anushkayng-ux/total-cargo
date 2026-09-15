<?php
$val = fn ($v, $empty = '—') => ($v === null || $v === '') ? $empty : esc((string) $v);
$otherQuickAdd = array_values(array_filter(
    tpt_quick_add_items($auth),
    fn ($qa) => rtrim($qa['url'], '/') !== rtrim(site_url('bookings/create'), '/')
));
?>
<div class="retro-toolbar is-sticky">
  <?php if ($otherQuickAdd): ?>
    <div class="dropdown d-inline-block">
      <button type="button" class="retro-tbtn retro-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false"><i class="bi bi-plus-circle-fill"></i>New</button>
      <ul class="dropdown-menu shadow-sm" style="font-size:.85rem;">
        <li><a class="dropdown-item d-flex align-items-center gap-2" href="<?= site_url('bookings/create') ?>"><i class="bi bi-plus-circle"></i> New Booking</a></li>
        <li><hr class="dropdown-divider"></li>
        <li><h6 class="dropdown-header">Other…</h6></li>
        <?php foreach ($otherQuickAdd as $qa): ?>
          <li><a class="dropdown-item d-flex align-items-center gap-2" href="<?= esc($qa['url']) ?>"><i class="bi bi-<?= esc($qa['icon']) ?>"></i> <?= esc($qa['label']) ?></a></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php else: ?>
    <a class="retro-tbtn retro-primary" href="<?= site_url('bookings/create') ?>"><i class="bi bi-plus-circle-fill"></i>New</a>
  <?php endif; ?>
  <a class="retro-tbtn" href="<?= site_url('bookings/' . $row['id'] . '/edit') ?>"><i class="bi bi-pencil-fill"></i>Edit</a>
  <div class="retro-tbtn retro-tbtn-off"><i class="bi bi-save-fill"></i>Save</div>
  <?php if ($row['booking_status'] === 'Pending'): ?>
    <button type="button" class="retro-tbtn retro-primary" data-bs-toggle="modal" data-bs-target="#confirmBookingModal"><i class="bi bi-check2-circle"></i>Confirm</button>
  <?php endif; ?>
  <?php if ($row['booking_status'] === 'Approved'): ?>
    <form method="post" action="<?= site_url('bookings/' . $row['id'] . '/handover') ?>" class="d-inline" data-confirm="Create trip and hand over to Operations?">
      <?= csrf_field() ?><button type="submit" class="retro-tbtn retro-primary"><i class="bi bi-arrow-right-circle"></i>Handover</button>
    </form>
  <?php endif; ?>
  <?php if (!in_array($row['booking_status'], ['Completed','Cancelled'], true)): ?>
    <form method="post" action="<?= site_url('bookings/' . $row['id'] . '/cancel') ?>" class="d-inline" data-confirm="Cancel this booking?">
      <?= csrf_field() ?><button type="submit" class="retro-tbtn retro-danger"><i class="bi bi-x-octagon"></i>Cancel</button>
    </form>
  <?php else: ?>
    <div class="retro-tbtn retro-tbtn-off"><i class="bi bi-x-octagon"></i>Cancel</div>
  <?php endif; ?>
  <div class="retro-tbtn retro-tbtn-off"><i class="bi bi-trash-fill"></i>Delete</div>
  <button type="button" class="retro-tbtn" onclick="window.print()"><i class="bi bi-printer"></i>Print</button>
  <a class="retro-tbtn" href="<?= site_url('bookings') ?>"><i class="bi bi-x-lg"></i>Close</a>

  <form method="post" action="<?= site_url('bookings/' . $row['id'] . '/assign') ?>" class="d-inline-flex align-items-center gap-1" style="margin-left:auto;">
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

<div class="tabs" role="tablist" id="bookingTabs">
  <button type="button" class="tab active" data-bs-toggle="tab" data-bs-target="#tab-grlr">GR/LR Details</button>
  <button type="button" class="tab" data-bs-toggle="tab" data-bs-target="#tab-status">Shipment Status Update</button>
  <button type="button" class="tab" data-bs-toggle="tab" data-bs-target="#tab-charges">Charges Details / Document Tagging</button>
  <div class="spacer"></div>
  <div class="recordnav">
    <a href="<?= site_url('bookings') ?>"><i class="bi bi-list"></i> List</a> &middot;
    Record <?= (int) $row['id'] ?> &middot; <?= (int) $total ?> total
    <a class="<?= $prevId ? '' : 'disabled' ?>" href="<?= $prevId ? site_url('bookings/' . $prevId) : '#' ?>">&#9664; Prev</a>
    <a class="<?= $nextId ? '' : 'disabled' ?>" href="<?= $nextId ? site_url('bookings/' . $nextId) : '#' ?>">Next &#9654;</a>
  </div>
</div>

<div class="tab-content">

      <div class="tab-pane fade show active" id="tab-grlr">
        <div class="retro-detail">
          <div class="retro-detail-main formwrap">
            <div class="retro-row">
              <div class="retro-field"><label>Issuing Office :</label><div class="retro-box wide">DELHI</div></div>
              <div class="retro-field" style="margin-left:auto;"><label>GR/LR Booking Status :</label><div class="retro-box wide"><?= $val($row['booking_status']) ?></div></div>
            </div>
            <div class="retro-row">
              <div class="retro-field"><label>GR/LR No. :</label><div class="retro-box"><?= $val($row['lr_no'] ?: $row['booking_no']) ?></div></div>
              <div class="retro-field"><label>Date :</label><div class="retro-box"><?= $val($row['loading_date']) ?></div></div>
            </div>
            <div class="retro-row">
              <div class="retro-field"><label>Client :</label><div class="retro-box wide"><?= $val($row['client_company'] ?? null) ?></div></div>
              <div class="retro-field"><label>Vendor :</label><div class="retro-box wide"><?= $val($row['vendor_company'] ?? null) ?></div></div>
            </div>
            <div class="retro-row">
              <div class="retro-field"><label>Dest. From :</label><div class="retro-box wide"><?= $val($row['pickup_city']) ?></div></div>
              <div class="retro-field"><label>Dest. To :</label><div class="retro-box wide"><?= $val($row['drop_city']) ?></div></div>
            </div>
            <div class="retro-row">
              <div class="retro-field"><label>Consignor :</label><div class="retro-box xwide"><?= $val($row['consignor_name']) ?></div></div>
              <div class="retro-field"><label>Consignee :</label><div class="retro-box xwide"><?= $val($row['consignee_name']) ?></div></div>
            </div>
            <?php if (!empty($row['consignor_mobile']) || !empty($row['consignor_address']) || !empty($row['consignor_gstin'])): ?>
            <div class="retro-row">
              <div class="retro-field"><label>Consignor Mobile :</label><div class="retro-box"><?= $val($row['consignor_mobile'] ?? null) ?></div></div>
              <div class="retro-field"><label>Consignor GSTIN :</label><div class="retro-box wide"><?= $val($row['consignor_gstin'] ?? null) ?></div></div>
              <div class="retro-field"><label>State :</label><div class="retro-box"><?= $val($row['consignor_state'] ?? null) ?></div></div>
            </div>
            <?php endif; ?>
            <?php if (!empty($row['consignee_mobile']) || !empty($row['consignee_address']) || !empty($row['consignee_gstin'])): ?>
            <div class="retro-row">
              <div class="retro-field"><label>Consignee Mobile :</label><div class="retro-box"><?= $val($row['consignee_mobile'] ?? null) ?></div></div>
              <div class="retro-field"><label>Consignee GSTIN :</label><div class="retro-box wide"><?= $val($row['consignee_gstin'] ?? null) ?></div></div>
            </div>
            <?php endif; ?>
            <div class="retro-row">
              <div class="retro-field"><label>Vehicle No :</label><div class="retro-box wide"><?= $val($row['vehicle_number']) ?></div></div>
              <div class="retro-field"><label>Vehicle Type :</label><div class="retro-box wide"><?= $val($row['vehicle_type']) ?></div></div>
            </div>
            <div class="retro-row">
              <div class="retro-field"><label>No of Packages :</label><div class="retro-box"><?= $val($row['packages_count']) ?></div></div>
              <div class="retro-field"><label>Method of Packing :</label><div class="retro-box wide"><?= $val($row['packing_method']) ?></div></div>
            </div>
            <div class="retro-row" style="align-items:flex-start;">
              <div class="retro-field" style="width:100%;"><label style="white-space:nowrap;">Particulars :</label>
                <div class="retro-particulars"><?= $val($row['particulars_text'] ?: $row['load_details']) ?></div>
              </div>
            </div>
            <div class="retro-row">
              <div class="retro-field"><label>Gross Weight :</label><div class="retro-box"><?= $val($row['actual_weight_kg']) ?></div></div>
              <div class="retro-field"><label>Chargeable Weight :</label><div class="retro-box"><?= $val($row['charge_weight_kg']) ?></div></div>
            </div>
          </div>
          <div class="retro-detail-side">
            <h4>Remarks :</h4>
            <div class="remarksbox"><?= $val($row['instructions'], 'No remarks recorded.') ?></div>
            <div class="scenic"><div class="sun"></div><div class="box1"></div><div class="box2"></div><div class="box3"></div></div>
          </div>
        </div>
      </div>

      <div class="tab-pane fade" id="tab-status">
        <div class="formwrap">
        <div class="retro-row">
          <div class="retro-field"><label>GR/LR Booking Status :</label><div class="retro-box wide"><?= $val($row['booking_status']) ?></div></div>
          <div class="retro-field"><label>Approved :</label><div class="retro-box wide empty"><?= $val($row['approved_at'] ?? null) ?></div></div>
        </div>
        <div class="retro-row">
          <div class="retro-field"><label>Lead :</label><div class="retro-box wide empty"><?= $val($row['lead_no'] ?? null) ?></div></div>
          <div class="retro-field"><label>Created By :</label><div class="retro-box wide"><?= $val($row['created_by_name'] ?? null) ?></div></div>
        </div>
        <div class="retro-row" style="align-items:flex-start;">
          <div class="retro-field" style="width:100%;"><label style="white-space:nowrap;">Operations Trip :</label>
            <div class="retro-particulars">
              <?php if ($trip): ?>
                Trip <a href="<?= site_url('trips/' . $trip['id']) ?>"><?= esc($trip['trip_no']) ?></a> &middot;
                Status: <?= esc($trip['current_status']) ?> &middot;
                POD: <?= esc($trip['pod_status']) ?>
              <?php else: ?>
                No trip yet. Approve the booking and click <strong>Handover → Trip</strong>.
              <?php endif; ?>
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
        </div>
      </div>

      <div class="tab-pane fade" id="tab-charges">
        <div class="formwrap">
        <div class="retro-row">
          <div class="retro-field"><label>Buy Rate :</label><div class="retro-box">₹<?= number_format((float) $row['final_buy_rate'], 2) ?></div></div>
          <div class="retro-field"><label>Sell Rate :</label><div class="retro-box">₹<?= number_format((float) $row['final_sell_rate'], 2) ?></div></div>
          <div class="retro-field"><label>Margin :</label><div class="retro-box">₹<?= number_format((float) $row['margin_amount'], 2) ?></div></div>
        </div>
        <div class="retro-row">
          <div class="retro-field"><label>Invoice No. :</label><div class="retro-box"><?= $val($row['invoice_number'] ?? null) ?></div></div>
          <div class="retro-field"><label>Invoice Value :</label><div class="retro-box">₹<?= number_format((float) ($row['cargo_value_inr'] ?? 0), 2) ?></div></div>
        </div>
        <div class="retro-row">
          <div class="retro-field"><label>B.E./S.B. No. :</label><div class="retro-box empty"><?= $val($row['bill_of_entry'] ?? null) ?></div></div>
          <div class="retro-field"><label>Eway Bill No :</label><div class="retro-box empty"><?= $val($row['ewb_no'] ?? null) ?></div></div>
        </div>
        <div class="retro-row">
          <div class="retro-field"><label>B/L No. :</label><div class="retro-box empty"><?= $val($row['bl_number'] ?? null) ?></div></div>
          <div class="retro-field"><label>Container No. :</label><div class="retro-box empty"><?= $val($row['container_number'] ?? null) ?></div></div>
          <div class="retro-field"><label>Seal No :</label><div class="retro-box empty"><?= $val($row['seal_number'] ?? null) ?></div></div>
        </div>
        <div class="retro-row">
          <div class="retro-field"><label>Length :</label><div class="retro-box narrow"><?= $val($row['dim_length_cm'] ?? null, '0') ?></div></div>
          <div class="retro-field"><label>Width :</label><div class="retro-box narrow"><?= $val($row['dim_width_cm'] ?? null, '0') ?></div></div>
          <div class="retro-field"><label>Height :</label><div class="retro-box narrow"><?= $val($row['dim_height_cm'] ?? null, '0') ?></div></div>
        </div>
        <div class="retro-row">
          <div class="retro-field"><label>Billing Client :</label><div class="retro-box xwide"><?= $val($row['billing_party'] ?? null) ?></div></div>
        </div>
        <div class="retro-row">
          <div class="retro-field"><label>Payment Type :</label><div class="retro-box wide"><?= $val($row['freight_mode'] ?? null) ?></div></div>
          <div class="retro-field"><label>GST Amount :</label><div class="retro-box">₹<?= number_format((float) ($row['gst_amount'] ?? 0), 2) ?></div></div>
          <div class="retro-field"><label>Other Charges :</label><div class="retro-box">₹<?= number_format((float) ($row['other_charges'] ?? 0), 2) ?></div></div>
        </div>
        </div>
      </div>

    </div>
