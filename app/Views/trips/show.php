<?php
$statusCls = match ((string) $row['current_status']) {
    'Delivered', 'POD Received', 'Closed' => 'badge-soft badge-ok',
    'Cancelled'                            => 'badge-soft badge-danger',
    default                                => 'badge-soft',
};
$val = fn ($v, $empty = '—') => ($v === null || $v === '') ? $empty : esc((string) $v);
$quickAdd = tpt_quick_add_items($auth);
?>
<div class="retro-toolbar is-sticky">
  <?php if ($quickAdd): ?>
    <div class="dropdown d-inline-block">
      <button type="button" class="retro-tbtn retro-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false"><i class="bi bi-plus-circle-fill"></i>New</button>
      <ul class="dropdown-menu shadow-sm" style="font-size:.85rem;">
        <?php foreach ($quickAdd as $qa): ?>
          <li><a class="dropdown-item d-flex align-items-center gap-2" href="<?= esc($qa['url']) ?>"><i class="bi bi-<?= esc($qa['icon']) ?>"></i> <?= esc($qa['label']) ?></a></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php else: ?>
    <div class="retro-tbtn retro-tbtn-off"><i class="bi bi-plus-circle-fill"></i>New</div>
  <?php endif; ?>
  <div class="retro-tbtn retro-tbtn-off"><i class="bi bi-pencil-fill"></i>Edit</div>
  <div class="retro-tbtn retro-tbtn-off"><i class="bi bi-save-fill"></i>Save</div>

  <?php if (!empty($row['lr_no'])):
    $lrPdf = site_url('trips/' . $row['id'] . '/dispatch/lr.pdf');
    $waMsg = rawurlencode('LR ' . $row['lr_no'] . ' for trip ' . $row['trip_no'] . ': ' . $lrPdf);
  ?>
    <a class="retro-tbtn retro-primary" href="<?= $lrPdf ?>" target="_blank"><i class="bi bi-file-earmark-ruled"></i>LR <?= esc($row['lr_no']) ?></a>
    <div class="dropdown d-inline-block">
      <button type="button" class="retro-tbtn dropdown-toggle" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" title="Choose copies + download"><i class="bi bi-download"></i>Copies</button>
      <div class="dropdown-menu dropdown-menu-end p-2 lr-copy-menu" style="min-width:230px;">
        <?php foreach (['consignee'=>'Consignee','consignor'=>'Consignor','driver'=>'Driver','record'=>'Record'] as $k => $lbl): ?>
          <label class="d-flex align-items-center gap-2 px-2 py-1" style="cursor:pointer;font-size:.85rem;user-select:none;" onclick="event.stopPropagation();">
            <input type="checkbox" class="form-check-input m-0 lr-copy-chk" data-copy="<?= $k ?>" <?= $k === 'consignee' ? 'checked' : '' ?>>
            <span><?= $lbl ?> Copy</span>
          </label>
        <?php endforeach; ?>
        <hr class="my-1">
        <button type="button" class="btn btn-sm btn-primary w-100 lr-copy-dl" data-lrurl="<?= $lrPdf ?>">
          <i class="bi bi-download"></i> Download <span class="lr-copy-count">1</span>
        </button>
        <a class="btn btn-sm btn-light w-100 mt-1" href="https://wa.me/?text=<?= $waMsg ?>" target="_blank"><i class="bi bi-whatsapp"></i> Share on WhatsApp</a>
      </div>
    </div>
    <a class="retro-tbtn" href="<?= site_url('dockets/from-trip/' . $row['id']) ?>" title="Edit docket"><i class="bi bi-pencil"></i>Edit LR</a>
  <?php else: ?>
    <a class="retro-tbtn retro-primary" href="<?= site_url('dockets/from-trip/' . $row['id']) ?>"><i class="bi bi-file-earmark-plus"></i>Create Docket</a>
  <?php endif; ?>

  <a class="retro-tbtn" href="<?= site_url('trips/' . $row['id'] . '/dispatch') ?>" title="Dispatch Pack (all LR variants)"><i class="bi bi-collection"></i>Pack</a>
  <a class="retro-tbtn" href="<?= site_url('gps/trip/' . $row['id']) ?>"><i class="bi bi-geo-alt"></i>Live Map</a>
  <a class="retro-tbtn" href="<?= site_url('invoices/from-trip/' . $row['id']) ?>"><i class="bi bi-receipt"></i>Invoice</a>
  <a class="retro-tbtn" href="<?= site_url('vendor-bills/from-trip/' . $row['id']) ?>"><i class="bi bi-journal-text"></i>Vendor Bill</a>

  <div class="retro-tbtn retro-tbtn-off"><i class="bi bi-trash-fill"></i>Delete</div>
  <button type="button" class="retro-tbtn" onclick="window.print()"><i class="bi bi-printer"></i>Print</button>
  <a class="retro-tbtn" href="<?= site_url('trips') ?>"><i class="bi bi-x-lg"></i>Close</a>

  <form method="post" action="<?= site_url('trips/' . $row['id'] . '/assign-staff') ?>" class="d-inline-flex align-items-center gap-1" style="margin-left:auto;">
    <?= csrf_field() ?>
    <i class="bi bi-person-check text-muted"></i>
    <select name="assigned_to" class="form-select form-select-sm" style="width:auto;" onchange="this.form.submit()" title="Assign this trip to a team member">
      <option value="">— Assign to —</option>
      <?php foreach (($staff ?? []) as $s): ?>
        <option value="<?= (int) $s['id'] ?>" <?= (int) ($row['assigned_to'] ?? 0) === (int) $s['id'] ? 'selected' : '' ?>>
          <?= esc($s['name']) ?><?= !empty($s['role_name']) ? ' (' . esc($s['role_name']) . ')' : '' ?>
        </option>
      <?php endforeach; ?>
    </select>
  </form>
</div>

<script>
(function () {
  document.querySelectorAll('.lr-copy-menu').forEach(function (menu) {
    var chks = menu.querySelectorAll('.lr-copy-chk');
    var cnt  = menu.querySelector('.lr-copy-count');
    var btn  = menu.querySelector('.lr-copy-dl');
    function refresh() {
      var n = Array.from(chks).filter(function (c) { return c.checked; }).length;
      if (cnt) cnt.textContent = n;
      if (btn) btn.disabled = n === 0;
    }
    chks.forEach(function (c) { c.addEventListener('change', refresh); });
    refresh();
    if (btn) btn.addEventListener('click', function () {
      var base = btn.dataset.lrurl;
      Array.from(chks).filter(function (c) { return c.checked; }).forEach(function (c, i) {
        setTimeout(function () {
          var a = document.createElement('a');
          a.href = base + '?copy=' + encodeURIComponent(c.dataset.copy) + '&download=1';
          a.download = '';
          a.style.display = 'none';
          document.body.appendChild(a);
          a.click();
          setTimeout(function () { a.remove(); }, 500);
        }, i * 300);
      });
    });
  });
})();
</script>

<?php
  // Shared values used in multiple tabs; compute once up top.
  $driverActive = !empty($row['driver_track_token']);
  $driverUrl    = $driverActive ? site_url('d/' . $row['driver_track_token']) : '';
  $waMsg = $driverActive ? rawurlencode("Hi " . ($row['driver_name'] ?? 'driver') . ", please tap this link to share your live location for trip " . $row['trip_no'] . " (" . ($row['lr_no'] ?? '') . "). Keep it open while driving:\n" . $driverUrl) : '';
  $waPhone = preg_replace('/[^0-9]/', '', (string) ($row['driver_mobile'] ?? ''));
  if ($waPhone !== '' && strlen($waPhone) === 10) $waPhone = '91' . $waPhone;
  $tripClosed = in_array($row['current_status'], ['Closed','Cancelled'], true);
  $fmtAmt    = fn($n) => '₹' . number_format((float) $n, 0);
?>

<div class="tabs" role="tablist" id="tripTabs">
  <button type="button" class="tab active" data-bs-toggle="tab" data-bs-target="#tab-overview">Overview</button>
  <?php if (!empty($booking)): ?>
    <button type="button" class="tab" data-bs-toggle="tab" data-bs-target="#tab-parties">Parties</button>
  <?php endif; ?>
  <button type="button" class="tab" data-bs-toggle="tab" data-bs-target="#tab-dispatch">Dispatch</button>
  <button type="button" class="tab" data-bs-toggle="tab" data-bs-target="#tab-tracking">Tracking &amp; POD</button>
  <button type="button" class="tab" data-bs-toggle="tab" data-bs-target="#tab-expenses">Expenses</button>
  <div class="spacer"></div>
  <div class="recordnav">
    <a href="<?= site_url('trips') ?>"><i class="bi bi-list"></i> List</a> &middot;
    Trip <?= esc($row['trip_no']) ?>
  </div>
</div>

<div class="tab-content">

  <!-- ══════════ TAB 1 — Overview: details + driver/vehicle assign + status + timeline ══════════ -->
  <div class="tab-pane fade show active" id="tab-overview" role="tabpanel">
    <div class="retro-detail">
      <div class="retro-detail-main formwrap">
        <div class="retro-row">
          <div class="retro-field"><label>Trip No :</label><div class="retro-box wide"><?= $val($row['trip_no']) ?></div></div>
          <div class="retro-field" style="margin-left:auto;"><label>Status :</label><div class="retro-box"><span class="<?= $statusCls ?>"><?= esc($row['current_status']) ?></span></div></div>
        </div>
        <?php if (!empty($row['booking_id'])): ?>
        <div class="retro-row">
          <div class="retro-field" style="width:100%;"><label>Booking :</label><div class="retro-box wide"><a href="<?= site_url('bookings/' . $row['booking_id']) ?>"><?= esc($row['booking_no']) ?></a></div></div>
        </div>
        <?php endif; ?>
        <div class="retro-row">
          <div class="retro-field"><label>Client :</label><div class="retro-box wide"><?= $val($row['client_company'] ?? null) ?></div></div>
          <div class="retro-field"><label>Vendor :</label><div class="retro-box wide"><?= $val($row['vendor_company'] ?? null) ?></div></div>
        </div>
        <div class="retro-row">
          <div class="retro-field"><label>Vehicle :</label>
            <div class="retro-box wide" style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
              <code><?= esc($row['vehicle_number'] ?? '—') ?></code>
              <?php
                if (!empty($row['vehicle_id'])) {
                  $veh = (new \App\Models\VehicleModel())->find((int) $row['vehicle_id']);
                  if ($veh) {
                    if (!empty($veh['gps_tracking_url'])):
              ?>
                <a href="<?= esc($veh['gps_tracking_url']) ?>" target="_blank" rel="noopener" class="btn btn-sm btn-outline-dark ms-2" style="font-size:.7rem;padding:.1rem .4rem;">
                  <i class="bi bi-box-arrow-up-right"></i> Vendor tracker
                </a>
              <?php endif; ?>
              <?php if (!empty($veh['gps_provider']) && $veh['gps_provider'] !== 'none'): ?>
                <span class="badge-soft" style="font-size:.7rem;"><?= esc(\App\Models\VehicleModel::GPS_PROVIDERS[$veh['gps_provider']] ?? $veh['gps_provider']) ?></span>
              <?php
                    endif;
                  }
                }
              ?>
            </div>
          </div>
          <div class="retro-field"><label>Driver :</label><div class="retro-box wide"><?= $val($row['driver_name'] ?? null) ?></div></div>
          <div class="retro-field"><label>Driver Mobile :</label><div class="retro-box"><?= $val($row['driver_mobile'] ?? null) ?></div></div>
        </div>
        <div class="retro-row">
          <div class="retro-field"><label>Loading Point :</label><div class="retro-box xwide"><?= $val($row['loading_point'] ?? null) ?></div></div>
          <div class="retro-field"><label>Unloading Point :</label><div class="retro-box xwide"><?= $val($row['unloading_point'] ?? null) ?></div></div>
        </div>
        <div class="retro-row">
          <div class="retro-field"><label>Loading Date :</label><div class="retro-box"><?= $val($row['loading_date'] ?? null) ?></div></div>
          <div class="retro-field"><label>Dispatch :</label><div class="retro-box wide"><?= $val($row['dispatch_datetime'] ?? null) ?></div></div>
          <div class="retro-field"><label>Delivered :</label><div class="retro-box wide"><?= $val($row['delivery_datetime'] ?? null) ?></div></div>
        </div>
        <div class="retro-row">
          <div class="retro-field"><label>POD :</label><div class="retro-box wide"><?= esc($row['pod_status']) ?><?= $row['pod_received_at'] ? ' · ' . esc($row['pod_received_at']) : '' ?></div></div>
          <?php if (!empty($booking['billing_party'])): ?>
            <div class="retro-field"><label>Billing Party :</label><div class="retro-box xwide"><?= esc($booking['billing_party']) ?></div></div>
          <?php endif; ?>
        </div>
        <?php if (!empty($row['delay_reason'])): ?>
        <div class="retro-row" style="align-items:flex-start;">
          <div class="retro-field" style="width:100%;"><label style="white-space:nowrap;">Delay Reason :</label>
            <div class="retro-particulars"><?= esc($row['delay_reason']) ?></div>
          </div>
        </div>
        <?php endif; ?>
      </div>
      <div class="retro-detail-side">
        <h4>Notes :</h4>
        <div class="remarksbox"><?= !empty($row['remarks']) ? nl2br(esc($row['remarks'])) : 'No remarks recorded.' ?></div>
        <div class="scenic"><div class="sun"></div><div class="box1"></div><div class="box2"></div><div class="box3"></div></div>
      </div>
    </div>

    <div class="formwrap">
      <div class="card mb-3">
        <div class="card-header">Assign Driver &amp; Vehicle</div>
        <div class="card-body">
          <form method="post" action="<?= site_url('trips/' . $row['id'] . '/assign') ?>">
            <?= csrf_field() ?>
            <div class="row g-2">
              <div class="col-md-4">
                <label class="form-label">Existing Driver</label>
                <select class="form-select form-select-sm" name="driver_id">
                  <option value="">— Use fields below —</option>
                  <?php foreach ($drivers as $d): ?>
                    <option value="<?= $d['id'] ?>" <?= (int)$row['driver_id'] === (int)$d['id'] ? 'selected' : '' ?>><?= esc($d['driver_name']) ?> (<?= esc($d['mobile']) ?>)</option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-4">
                <label class="form-label">Driver Name</label>
                <input class="form-control form-control-sm" name="driver_name" value="<?= esc($row['driver_name']) ?>">
              </div>
              <div class="col-md-4">
                <label class="form-label">Driver Mobile</label>
                <input class="form-control form-control-sm" name="driver_mobile" value="<?= esc($row['driver_mobile']) ?>">
              </div>

              <div class="col-md-4">
                <label class="form-label">Existing Vehicle</label>
                <select class="form-select form-select-sm" name="vehicle_id">
                  <option value="">— Use field below —</option>
                  <?php foreach ($vehicles as $v): ?>
                    <option value="<?= $v['id'] ?>" <?= (int)$row['vehicle_id'] === (int)$v['id'] ? 'selected' : '' ?>><?= esc($v['vehicle_number']) ?> <?= $v['vehicle_type'] ? '· ' . esc($v['vehicle_type']) : '' ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-4">
                <label class="form-label">Vehicle Number</label>
                <input class="form-control form-control-sm" name="vehicle_number" value="<?= esc($row['vehicle_number']) ?>">
              </div>
              <div class="col-md-4 d-flex align-items-end">
                <button class="btn btn-primary btn-sm w-100" type="submit">Save Assignment</button>
              </div>
            </div>
          </form>
        </div>
      </div>

      <?php
        $allowedStatuses = \App\Models\TripModel::allowedNextStatuses((string) $row['current_status']);
        $isTerminalNow   = \App\Models\TripModel::isTerminal((string) $row['current_status']);
      ?>
      <div class="card mb-3">
        <div class="card-header d-flex align-items-center gap-2">
          Change Status
          <small class="text-muted ms-auto">Forward-only</small>
        </div>
        <div class="card-body">
          <?php if ($isTerminalNow): ?>
            <div class="alert alert-secondary py-2 mb-0" style="font-size:.85rem;">
              Trip is <strong><?= esc($row['current_status']) ?></strong> — status is locked.
            </div>
          <?php else: ?>
            <form method="post" action="<?= site_url('trips/' . $row['id'] . '/status') ?>">
              <?= csrf_field() ?>
              <div class="mb-2"><label class="form-label">New Status</label>
                <select class="form-select form-select-sm" name="new_status">
                  <?php foreach ($allowedStatuses as $s): ?>
                    <option value="<?= esc($s) ?>" <?= $row['current_status'] === $s ? 'selected' : '' ?>><?= esc($s) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="mb-2"><label class="form-label">Notes</label><input class="form-control form-control-sm" name="notes"></div>
              <div class="mb-2"><label class="form-label">Delay Reason (if any)</label><input class="form-control form-control-sm" name="delay_reason"></div>
              <button class="btn btn-primary btn-sm w-100">Save Status</button>
            </form>
          <?php endif; ?>
        </div>
      </div>

      <div class="card">
        <div class="card-header">Timeline</div>
        <div class="card-body">
          <?php if (empty($history)): ?>
            <div class="text-muted">No history yet.</div>
          <?php else: ?>
            <?php
              $events = $history;
              usort($events, fn($a, $b) => strcmp((string) $a['changed_at'], (string) $b['changed_at']));
              $palette = [
                  'Pending'        => ['#9ca3af', 'circle'],
                  'Assigned'       => ['#6366f1', 'person-check'],
                  'Confirmed'      => ['#3b82f6', 'check2-circle'],
                  'In Transit'     => ['#0ea5e9', 'truck'],
                  'At Pickup'      => ['#0ea5e9', 'box-arrow-in-down'],
                  'Loaded'         => ['#0ea5e9', 'boxes'],
                  'At Drop'        => ['#10b981', 'box-arrow-in-up'],
                  'Delivered'      => ['#10b981', 'check-circle-fill'],
                  'POD Received'   => ['#059669', 'file-earmark-check'],
                  'Settled'        => ['#0f766e', 'cash-coin'],
                  'Cancelled'      => ['#dc2626', 'x-circle-fill'],
                  'On Hold'        => ['#f59e0b', 'pause-circle'],
              ];
            ?>
            <div class="tpt-timeline" style="position:relative;padding-left:1.75rem;">
              <span style="position:absolute;left:.6rem;top:.25rem;bottom:.25rem;width:2px;background:linear-gradient(to bottom,#e5e7eb,#f3f4f6);"></span>
              <?php foreach ($events as $h):
                $key   = (string) $h['new_status'];
                $color = $palette[$key][0] ?? '#6b7280';
                $icon  = $palette[$key][1] ?? 'circle-fill';
              ?>
                <div class="mb-3" style="position:relative;">
                  <span style="position:absolute;left:-1.45rem;top:.1rem;width:1.5rem;height:1.5rem;background:#fff;border:2px solid <?= $color ?>;border-radius:999px;display:inline-flex;align-items:center;justify-content:center;color:<?= $color ?>;">
                    <i class="bi bi-<?= $icon ?>" style="font-size:.78rem;"></i>
                  </span>
                  <div style="font-size:.92rem;">
                    <strong style="color:<?= $color ?>;"><?= esc($key) ?></strong>
                    <?php if (!empty($h['old_status'])): ?>
                      <span class="text-muted" style="font-size:.78rem;">from <?= esc($h['old_status']) ?></span>
                    <?php endif; ?>
                  </div>
                  <div class="text-muted" style="font-size:.78rem;">
                    <?= esc(tpt_dt($h['changed_at'])) ?>
                    <?php if (!empty($h['changed_by_name'])): ?>
                      · <?= esc($h['changed_by_name']) ?>
                    <?php endif; ?>
                  </div>
                  <?php if (!empty($h['notes'])): ?>
                    <div class="mt-1" style="font-size:.85rem;background:#f9fafb;border-left:2px solid <?= $color ?>;padding:.4rem .6rem;border-radius:4px;">
                      <?= esc($h['notes']) ?>
                    </div>
                  <?php endif; ?>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <div class="mt-3">
        <?= view('partials/_comments', [
              'threadType'     => $threadType ?? 'trip',
              'threadId'       => $threadId ?? ($row['id'] ?? 0),
              'threadComments' => $threadComments ?? [],
              'currentUser'    => $currentUser ?? null,
              'auth'           => $auth ?? null,
        ]) ?>
      </div>
    </div>
  </div>

  <!-- ══════════ TAB 2 — Parties (consignor / consignee / billing) ══════════ -->
  <?php if (!empty($booking)): ?>
  <div class="tab-pane fade" id="tab-parties" role="tabpanel">
    <div class="formwrap">
    <div class="card mb-3">
      <div class="card-header d-flex align-items-center gap-2">
        <i class="bi bi-people"></i> Consignor &amp; Consignee · Billing Party
        <small class="text-muted ms-auto">Saved on Booking <?= esc($booking['booking_no'] ?? '') ?></small>
      </div>
      <div class="card-body">
        <form method="post" action="<?= site_url('trips/' . $row['id'] . '/parties') ?>">
          <?= csrf_field() ?>
          <div class="row g-2">
            <?php
              $bpRaw   = (string) ($booking['billing_party'] ?? '');
              $bpMode  = $bpRaw === 'Consignor' ? 'Consignor'
                       : ($bpRaw === 'Consignee' ? 'Consignee'
                       : ($bpRaw !== '' ? 'Others' : ''));
              $bpOther = $bpMode === 'Others' ? $bpRaw : '';
            ?>
            <div class="col-md-4">
              <label class="form-label">Billing Party</label>
              <select class="form-select tpt-no-search" name="billing_party_type" id="bpType">
                <option value=""          <?= $bpMode === ''          ? 'selected' : '' ?>>— Select —</option>
                <option value="Consignor" <?= $bpMode === 'Consignor' ? 'selected' : '' ?>>Consignor</option>
                <option value="Consignee" <?= $bpMode === 'Consignee' ? 'selected' : '' ?>>Consignee</option>
                <option value="Others"    <?= $bpMode === 'Others'    ? 'selected' : '' ?>>Others…</option>
              </select>
            </div>
            <div class="col-md-8" id="bpOtherWrap" style="<?= $bpMode === 'Others' ? '' : 'display:none;' ?>">
              <label class="form-label">Other Billing Party</label>
              <input class="form-control" name="billing_party_other" id="bpOtherInput" value="<?= esc($bpOther) ?>" placeholder="Company / person to bill">
            </div>
            <script>
            (function () {
              var sel   = document.getElementById('bpType');
              var wrap  = document.getElementById('bpOtherWrap');
              var input = document.getElementById('bpOtherInput');
              if (!sel || !wrap) return;
              sel.addEventListener('change', function () {
                if (sel.value === 'Others') {
                  wrap.style.display = '';
                  setTimeout(function () { input && input.focus(); }, 0);
                } else {
                  wrap.style.display = 'none';
                  if (input) input.value = '';
                }
              });
            })();
            </script>
            <div class="col-md-6">
              <label class="form-label">Consignor</label>
              <select class="form-select party-select-trip" name="consignor_client_id" data-target="consignor">
                <option value="">— None yet —</option>
                <?php foreach ($clientsForParty as $c): ?>
                  <option value="<?= (int) $c['id'] ?>"
                          data-name="<?= esc($c['company_name']) ?>"
                          data-mobile="<?= esc($c['mobile'] ?? '') ?>"
                          data-address="<?= esc(trim(($c['address'] ?? '') . ' ' . ($c['city'] ?? '') . ' ' . ($c['pincode'] ?? ''))) ?>"
                          data-gstin="<?= esc($c['gst_no'] ?? '') ?>"
                          data-state="<?= esc($c['state'] ?? '') ?>"
                          <?= (int) ($booking['consignor_client_id'] ?? 0) === (int) $c['id'] ? 'selected' : '' ?>>
                    <?= esc($c['company_name']) ?><?= !empty($c['city']) ? ' · ' . esc($c['city']) : '' ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Consignee</label>
              <select class="form-select party-select-trip" name="consignee_client_id" data-target="consignee">
                <option value="">— None yet —</option>
                <?php foreach ($clientsForParty as $c): ?>
                  <option value="<?= (int) $c['id'] ?>"
                          data-name="<?= esc($c['company_name']) ?>"
                          data-mobile="<?= esc($c['mobile'] ?? '') ?>"
                          data-address="<?= esc(trim(($c['address'] ?? '') . ' ' . ($c['city'] ?? '') . ' ' . ($c['pincode'] ?? ''))) ?>"
                          data-gstin="<?= esc($c['gst_no'] ?? '') ?>"
                          data-state="<?= esc($c['state'] ?? '') ?>"
                          <?= (int) ($booking['consignee_client_id'] ?? 0) === (int) $c['id'] ? 'selected' : '' ?>>
                    <?= esc($c['company_name']) ?><?= !empty($c['city']) ? ' · ' . esc($c['city']) : '' ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="col-md-3"><label class="form-label">Consignor Mobile</label>
              <input class="form-control form-control-sm" id="trip-consignor-mobile"  name="consignor_mobile"  value="<?= esc($booking['consignor_mobile']  ?? '') ?>"></div>
            <div class="col-md-3"><label class="form-label">Consignor GSTIN</label>
              <input class="form-control form-control-sm" id="trip-consignor-gstin"   name="consignor_gstin"   value="<?= esc($booking['consignor_gstin']   ?? '') ?>"></div>
            <div class="col-md-3"><label class="form-label">Consignee Mobile</label>
              <input class="form-control form-control-sm" id="trip-consignee-mobile"  name="consignee_mobile"  value="<?= esc($booking['consignee_mobile']  ?? '') ?>"></div>
            <div class="col-md-3"><label class="form-label">Consignee GSTIN</label>
              <input class="form-control form-control-sm" id="trip-consignee-gstin"   name="consignee_gstin"   value="<?= esc($booking['consignee_gstin']   ?? '') ?>"></div>

            <div class="col-md-6"><label class="form-label">Consignor Address</label>
              <input class="form-control form-control-sm" id="trip-consignor-address" name="consignor_address" value="<?= esc($booking['consignor_address'] ?? '') ?>"></div>
            <div class="col-md-6"><label class="form-label">Consignee Address</label>
              <input class="form-control form-control-sm" id="trip-consignee-address" name="consignee_address" value="<?= esc($booking['consignee_address'] ?? '') ?>"></div>

            <div class="col-12">
              <label class="form-label"><strong>Particulars</strong></label>
              <textarea class="form-control form-control-sm" name="particulars_text" rows="2" placeholder="e.g. Home appliances — 24× refrigerators, wooden-crated"><?= esc($booking['particulars_text'] ?? '') ?></textarea>
            </div>

            <input type="hidden" id="trip-consignor-name"  name="consignor_name"  value="<?= esc($booking['consignor_name']  ?? '') ?>">
            <input type="hidden" id="trip-consignor-state" name="consignor_state" value="<?= esc($booking['consignor_state'] ?? '') ?>">
            <input type="hidden" id="trip-consignee-name"  name="consignee_name"  value="<?= esc($booking['consignee_name']  ?? '') ?>">
          </div>
          <div class="mt-2"><button class="btn btn-sm btn-primary"><i class="bi bi-save"></i> Save Parties &amp; Billing</button></div>
        </form>

        <script>
        (function () {
          function fillTrip(target, opt) {
            var name    = opt ? (opt.getAttribute('data-name')    || '') : '';
            var mobile  = opt ? (opt.getAttribute('data-mobile')  || '') : '';
            var address = opt ? (opt.getAttribute('data-address') || '') : '';
            var gstin   = opt ? (opt.getAttribute('data-gstin')   || '') : '';
            var state   = opt ? (opt.getAttribute('data-state')   || '') : '';
            var b = function (id) { return document.getElementById(id); };
            if (b('trip-' + target + '-mobile'))  b('trip-' + target + '-mobile').value  = mobile;
            if (b('trip-' + target + '-gstin'))   b('trip-' + target + '-gstin').value   = gstin;
            if (b('trip-' + target + '-address')) b('trip-' + target + '-address').value = address;
            if (b('trip-' + target + '-name'))    b('trip-' + target + '-name').value    = name;
            if (b('trip-' + target + '-state'))   b('trip-' + target + '-state').value   = state;
          }
          document.querySelectorAll('.party-select-trip').forEach(function (sel) {
            sel.addEventListener('change', function () {
              var opt = sel.options[sel.selectedIndex];
              fillTrip(sel.getAttribute('data-target'), (opt && opt.value) ? opt : null);
            });
          });
        })();
        </script>
      </div>
    </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- ══════════ TAB 3 — Dispatch: stops, detention, EWB ══════════ -->
  <div class="tab-pane fade" id="tab-dispatch" role="tabpanel">
    <div class="formwrap">

    <?= view('trips/_ewb', ['row' => $row, 'service' => new \App\Libraries\EwayBillService()]) ?>

    <div class="card mb-3">
      <div class="card-header"><i class="bi bi-signpost-split"></i> Stops (multi-pickup / multi-drop)</div>
      <div class="card-body">
        <?php if (!empty($stops)): ?>
          <div class="table-responsive mb-2">
            <table class="table table-sm align-middle mb-0">
              <thead><tr><th>#</th><th>Type</th><th>Address</th><th>Contact</th><th>Planned</th><th>Arr / Dep</th><th></th></tr></thead>
              <tbody>
                <?php foreach ($stops as $s): ?>
                  <tr>
                    <td><?= (int) $s['sequence'] ?></td>
                    <td><span class="badge-soft <?= $s['stop_type'] === 'pickup' ? 'badge-warn' : 'badge-ok' ?>"><?= esc($s['stop_type']) ?></span></td>
                    <td>
                      <?= esc($s['address']) ?>
                      <?php if (!empty($s['city'])): ?><br><small class="text-muted"><?= esc($s['city']) ?></small><?php endif; ?>
                    </td>
                    <td><?= esc($s['contact_name'] ?: '—') ?><?= !empty($s['contact_mobile']) ? '<br><small class="text-muted">' . esc($s['contact_mobile']) . '</small>' : '' ?></td>
                    <td><?= !empty($s['planned_at']) ? esc(date('d-m H:i', strtotime($s['planned_at']))) : '—' ?></td>
                    <td>
                      <?php if (empty($s['arrived_at'])): ?>
                        <form method="post" action="<?= site_url('trips/' . $row['id'] . '/stops/' . $s['id']) ?>" class="d-inline">
                          <?= csrf_field() ?><input type="hidden" name="action" value="arrive">
                          <button class="btn btn-sm btn-light" type="submit">Mark arrived</button>
                        </form>
                      <?php else: ?>
                        <small>Arrived <?= esc(date('d-m H:i', strtotime($s['arrived_at']))) ?></small>
                      <?php endif; ?>
                      <?php if (!empty($s['arrived_at']) && empty($s['departed_at'])): ?>
                        <form method="post" action="<?= site_url('trips/' . $row['id'] . '/stops/' . $s['id']) ?>" class="d-inline">
                          <?= csrf_field() ?><input type="hidden" name="action" value="depart">
                          <button class="btn btn-sm btn-light" type="submit">Mark departed</button>
                        </form>
                      <?php endif; ?>
                      <?php if (!empty($s['departed_at'])): ?>
                        <small class="text-muted">Departed <?= esc(date('d-m H:i', strtotime($s['departed_at']))) ?></small>
                      <?php endif; ?>
                    </td>
                    <td>
                      <form method="post" action="<?= site_url('trips/' . $row['id'] . '/stops/' . $s['id'] . '/delete') ?>" onsubmit="return confirm('Remove stop?');">
                        <?= csrf_field() ?><button class="btn btn-sm btn-light"><i class="bi bi-trash"></i></button>
                      </form>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>

        <form method="post" action="<?= site_url('trips/' . $row['id'] . '/stops') ?>" class="row g-2 align-items-end">
          <?= csrf_field() ?>
          <div class="col-1"><label class="form-label">#</label><input class="form-control form-control-sm" type="number" min="1" name="sequence" value="<?= count($stops ?? []) + 1 ?>"></div>
          <div class="col-2"><label class="form-label">Type</label>
            <select class="form-select form-select-sm" name="stop_type"><option value="pickup">pickup</option><option value="drop">drop</option></select>
          </div>
          <div class="col-3"><label class="form-label">Address</label><input class="form-control form-control-sm" name="address" required></div>
          <div class="col-1"><label class="form-label">City</label><input class="form-control form-control-sm" name="city"></div>
          <div class="col-2"><label class="form-label">Contact</label><input class="form-control form-control-sm" name="contact_name" placeholder="name"></div>
          <div class="col-2"><label class="form-label">Planned</label><input type="datetime-local" class="form-control form-control-sm" name="planned_at"></div>
          <div class="col-1"><button class="btn btn-sm btn-primary w-100">Add</button></div>
        </form>
      </div>
    </div>

    <div class="card mb-3">
      <div class="card-header"><i class="bi bi-stopwatch"></i> Detention &amp; dwell</div>
      <div class="card-body">
        <form method="post" action="<?= site_url('trips/' . $row['id'] . '/dwell') ?>" class="row g-2 align-items-end">
          <?= csrf_field() ?>
          <div class="col-md-3">
            <label class="form-label">Loading arrived</label>
            <input type="datetime-local" class="form-control form-control-sm" name="loading_arrived_at" value="<?= esc(!empty($row['loading_arrived_at']) ? date('Y-m-d\TH:i', strtotime($row['loading_arrived_at'])) : '') ?>">
          </div>
          <div class="col-md-3">
            <label class="form-label">Loading departed</label>
            <input type="datetime-local" class="form-control form-control-sm" name="loading_departed_at" value="<?= esc(!empty($row['loading_departed_at']) ? date('Y-m-d\TH:i', strtotime($row['loading_departed_at'])) : '') ?>">
          </div>
          <div class="col-md-3">
            <label class="form-label">Unloading arrived</label>
            <input type="datetime-local" class="form-control form-control-sm" name="unloading_arrived_at" value="<?= esc(!empty($row['unloading_arrived_at']) ? date('Y-m-d\TH:i', strtotime($row['unloading_arrived_at'])) : '') ?>">
          </div>
          <div class="col-md-3">
            <label class="form-label">Unloading departed</label>
            <input type="datetime-local" class="form-control form-control-sm" name="unloading_departed_at" value="<?= esc(!empty($row['unloading_departed_at']) ? date('Y-m-d\TH:i', strtotime($row['unloading_departed_at'])) : '') ?>">
          </div>
          <div class="col-md-12 d-flex gap-3 align-items-center">
            <button class="btn btn-sm btn-primary"><i class="bi bi-arrow-clockwise"></i> Recalculate</button>
            <span class="ms-auto">
              <strong><?= number_format((float) ($row['detention_billable_hours'] ?? 0), 2) ?> hrs</strong>
              · Detention <strong><?= $fmtAmt($row['detention_amount'] ?? 0) ?></strong>
            </span>
          </div>
        </form>
      </div>
    </div>

    <?php if (!empty($row['ewb_no'])): ?>
    <div class="card mb-3" id="ewb-actions">
      <div class="card-header"><i class="bi bi-file-earmark-check"></i> EWB advanced actions</div>
      <div class="card-body">
        <details class="mb-3">
          <summary><strong>Update Part-B (vehicle change)</strong></summary>
          <form method="post" action="<?= site_url('trips/' . $row['id'] . '/ewb/partb') ?>" class="row g-2 align-items-end mt-2">
            <?= csrf_field() ?>
            <div class="col-md-4"><label class="form-label">New vehicle no</label><input class="form-control form-control-sm" name="new_vehicle_no" required></div>
            <div class="col-md-6"><label class="form-label">Reason / remark</label><input class="form-control form-control-sm" name="reason" placeholder="Breakdown / Transhipment"></div>
            <div class="col-md-2"><button class="btn btn-sm btn-primary w-100">Update Part-B</button></div>
          </form>
        </details>
        <details class="mb-3">
          <summary><strong>Extend validity</strong></summary>
          <form method="post" action="<?= site_url('trips/' . $row['id'] . '/ewb/extend') ?>" class="row g-2 align-items-end mt-2">
            <?= csrf_field() ?>
            <div class="col-md-3"><label class="form-label">Reason code</label>
              <select class="form-select form-select-sm" name="reason_code">
                <option value="1">Natural Calamity</option>
                <option value="2">Law &amp; Order</option>
                <option value="3">Transhipment</option>
                <option value="4">Accident</option>
                <option value="99" selected>Others</option>
              </select>
            </div>
            <div class="col-md-3"><label class="form-label">Remaining km</label><input class="form-control form-control-sm" type="number" name="remaining_distance"></div>
            <div class="col-md-4"><label class="form-label">Reason remark</label><input class="form-control form-control-sm" name="reason_remark" placeholder="Trip delayed at toll plaza"></div>
            <div class="col-md-2"><button class="btn btn-sm btn-primary w-100">Extend</button></div>
          </form>
        </details>
        <form method="post" action="<?= site_url('trips/' . $row['id'] . '/ewb/refresh-status') ?>" class="d-inline">
          <?= csrf_field() ?>
          <button class="btn btn-sm btn-light"><i class="bi bi-arrow-clockwise"></i> Refresh status from NIC</button>
        </form>
      </div>
    </div>
    <?php endif; ?>
    </div>
  </div>

  <!-- ══════════ TAB 4 — Tracking + POD upload + e-POD signing + notify + driver chat ══════════ -->
  <div class="tab-pane fade" id="tab-tracking" role="tabpanel">
    <div class="formwrap">

    <div class="card mb-3">
      <div class="card-header"><i class="bi bi-phone-vibrate"></i> Driver-phone tracking</div>
      <div class="card-body">
        <?php if (!$driverActive): ?>
          <?php if ($tripClosed): ?>
            <span class="badge-soft badge-warn">Trip is <?= esc($row['current_status']) ?> — tracking unavailable.</span>
          <?php else: ?>
            <form method="post" action="<?= site_url('trips/' . $row['id'] . '/driver-track/start') ?>">
              <?= csrf_field() ?>
              <button class="btn btn-sm btn-primary"><i class="bi bi-link-45deg"></i> Generate driver tracking link</button>
            </form>
          <?php endif; ?>
        <?php else: ?>
          <div class="mb-2">
            <span class="badge-soft badge-ok"><i class="bi bi-broadcast"></i> Active</span>
            <?php if (!empty($row['driver_track_last_ping'])): ?>
              <small class="text-muted ms-2">Last ping: <?= esc(date('d-m H:i', strtotime($row['driver_track_last_ping']))) ?></small>
            <?php else: ?>
              <small class="text-muted ms-2">Awaiting first ping…</small>
            <?php endif; ?>
          </div>

          <div class="input-group input-group-sm mb-2">
            <input class="form-control" id="driverUrl" value="<?= esc($driverUrl) ?>" readonly onclick="this.select();">
            <button class="btn btn-outline-dark" type="button" onclick="navigator.clipboard.writeText(document.getElementById('driverUrl').value).then(()=>this.innerText='Copied');">Copy</button>
          </div>

          <div class="d-flex gap-2 flex-wrap">
            <?php if ($waPhone !== ''): ?>
              <a class="btn btn-sm btn-outline-dark" target="_blank" href="https://wa.me/<?= esc($waPhone) ?>?text=<?= $waMsg ?>"><i class="bi bi-whatsapp"></i> Send via WhatsApp</a>
            <?php else: ?>
              <a class="btn btn-sm btn-outline-dark" target="_blank" href="https://wa.me/?text=<?= $waMsg ?>"><i class="bi bi-whatsapp"></i> Share via WhatsApp</a>
            <?php endif; ?>
            <a class="btn btn-sm btn-outline-dark" target="_blank" href="https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=<?= rawurlencode($driverUrl) ?>"><i class="bi bi-qr-code"></i> QR for driver to scan</a>
            <form method="post" action="<?= site_url('trips/' . $row['id'] . '/driver-track/stop') ?>" class="d-inline" onsubmit="return confirm('Revoke this tracking link? The driver will lose access immediately.');">
              <?= csrf_field() ?>
              <button class="btn btn-sm btn-light"><i class="bi bi-x-circle"></i> Revoke link</button>
            </form>
          </div>

          <?php if (!empty($row['client_track_token'])):
            $clientTrackUrl = site_url('track/' . $row['client_track_token']);
            $clientWaMsg = rawurlencode("Hi " . ($row['client_company'] ?? 'team') . ", track your shipment " . $row['trip_no'] . " live: " . $clientTrackUrl);
          ?>
            <hr class="my-3">
            <h6 class="text-uppercase text-muted" style="font-size:.72rem;letter-spacing:.06em;font-weight:600;"><i class="bi bi-eye"></i> Client live-tracking link</h6>
            <div class="input-group input-group-sm mb-2">
              <input class="form-control" value="<?= esc($clientTrackUrl) ?>" readonly onclick="this.select();">
              <button class="btn btn-outline-dark" type="button" onclick="navigator.clipboard.writeText('<?= esc($clientTrackUrl) ?>').then(()=>this.innerText='Copied');">Copy</button>
              <a class="btn btn-outline-dark" target="_blank" href="<?= esc($clientTrackUrl) ?>"><i class="bi bi-box-arrow-up-right"></i></a>
              <a class="btn btn-outline-dark" target="_blank" href="https://wa.me/?text=<?= $clientWaMsg ?>"><i class="bi bi-whatsapp"></i></a>
            </div>
            <small class="text-muted">Read-only map for the client. Expires <?= esc(date('d-m-Y', strtotime((string) $row['client_track_expires_at']))) ?>.</small>
          <?php endif; ?>
        <?php endif; ?>
      </div>
    </div>

    <?php if (function_exists('tpt_feature_enabled') && tpt_feature_enabled('epod')):
        $epodActive = !empty($row['epod_token']);
        $epodUrl    = $epodActive ? site_url('epod/' . $row['epod_token']) : '';
        $epodMsg    = $epodActive ? rawurlencode("Hi, please sign the digital POD for trip " . $row['trip_no'] . ":\n" . $epodUrl) : '';
    ?>
    <div class="card mb-3">
      <div class="card-header"><i class="bi bi-pen"></i> e-POD signing link</div>
      <div class="card-body">
        <?php if (!$epodActive): ?>
          <form method="post" action="<?= site_url('trips/' . $row['id'] . '/epod/start') ?>">
            <?= csrf_field() ?>
            <button class="btn btn-sm btn-primary"><i class="bi bi-link-45deg"></i> Generate e-POD link</button>
          </form>
        <?php else: ?>
          <span class="badge-soft badge-ok">Active</span>
          <div class="input-group input-group-sm mt-2 mb-2">
            <input class="form-control" id="epodUrl" value="<?= esc($epodUrl) ?>" readonly onclick="this.select();">
            <button class="btn btn-outline-dark" type="button" onclick="navigator.clipboard.writeText(document.getElementById('epodUrl').value).then(()=>this.innerText='Copied');">Copy</button>
          </div>
          <div class="d-flex gap-2 flex-wrap">
            <a class="btn btn-sm btn-outline-dark" target="_blank" href="https://wa.me/?text=<?= $epodMsg ?>"><i class="bi bi-whatsapp"></i> Share via WhatsApp</a>
            <a class="btn btn-sm btn-outline-dark" target="_blank" href="https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=<?= rawurlencode($epodUrl) ?>"><i class="bi bi-qr-code"></i> QR for consignee</a>
            <form method="post" action="<?= site_url('trips/' . $row['id'] . '/epod/revoke') ?>" class="d-inline" onsubmit="return confirm('Revoke this e-POD link?');">
              <?= csrf_field() ?><button class="btn btn-sm btn-light">Revoke</button>
            </form>
          </div>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>

    <div class="card mb-3">
      <div class="card-header">Upload POD</div>
      <div class="card-body">
        <form method="post" action="<?= site_url('trips/' . $row['id'] . '/pod') ?>" enctype="multipart/form-data">
          <?= csrf_field() ?>
          <div class="row g-2">
            <div class="col-md-8"><input type="file" class="form-control" name="pod_file" accept="image/*,.pdf" required></div>
            <div class="col-md-4"><button class="btn btn-primary w-100"><i class="bi bi-upload"></i> Upload POD</button></div>
          </div>
        </form>

        <?php if (!empty($documents)): ?>
          <hr>
          <table class="table mb-0">
            <thead><tr><th>Type</th><th>File</th><th>Uploaded</th><th class="text-end"></th></tr></thead>
            <tbody>
              <?php foreach ($documents as $d): ?>
                <tr>
                  <td data-label="Type"><span class="badge-soft"><?= esc($d['document_type']) ?></span></td>
                  <td data-label="File"><?= esc($d['original_file_name']) ?> <small class="text-muted"><?= esc(number_format(((int) $d['file_size']) / 1024, 1)) ?> KB</small></td>
                  <td data-label="Uploaded"><?= esc($d['created_at']) ?></td>
                  <td class="text-end"><a class="btn btn-sm btn-light" href="<?= site_url('trips/' . $row['id'] . '/documents/' . $d['id']) ?>"><i class="bi bi-download"></i></a></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>
      </div>
    </div>

    <div class="card mb-3">
      <div class="card-header">Notify Client on WhatsApp</div>
      <div class="card-body">
        <?php if (!$service->isConfigured()): ?>
          <div class="alert alert-danger" style="font-size:.82rem;">WA not configured — will queue only.</div>
        <?php endif; ?>
        <form method="post" action="<?= site_url('trips/' . $row['id'] . '/notify-client') ?>" class="d-grid gap-2">
          <?= csrf_field() ?>
          <button class="btn btn-outline-dark btn-sm" name="kind" value="placed"><i class="bi bi-truck"></i> Vehicle Placed</button>
          <button class="btn btn-outline-dark btn-sm" name="kind" value="in_transit"><i class="bi bi-geo-alt"></i> In Transit</button>
          <button class="btn btn-outline-dark btn-sm" name="kind" value="delivered"><i class="bi bi-check2-circle"></i> Delivered</button>
        </form>
      </div>
    </div>

    <?php
    // ─────────────────── Driver chat (collapsible) ───────────────────
    $msgs = $driverMessages ?? [];
    $unreadFromDriver = 0;
    foreach ($msgs as $m) if ($m['direction'] === 'driver' && (int) $m['is_read_by_staff'] === 0) $unreadFromDriver++;
    $fmtTime = function ($ts) {
        if (!$ts) return '';
        $t = strtotime($ts);
        return date('Y-m-d', $t) === date('Y-m-d') ? date('H:i', $t) : date('d-m · H:i', $t);
    };
    ?>
    <?php
    // Client feedback moved to the Arrival page — this trip's feedback shows
    // up there in the expandable detail row.
    ?>
    <a id="driver-chat"></a>
    <div class="card mb-3 dchat-card">
      <div class="card-header dchat-header d-flex align-items-center gap-2"
           role="button"
           data-bs-toggle="collapse" data-bs-target="#dchat-body"
           aria-expanded="true" aria-controls="dchat-body">
        <i class="bi bi-chat-dots"></i>
        <span class="fw-semibold">Driver chat</span>
        <?php if ($unreadFromDriver > 0): ?>
          <span class="badge bg-danger ms-1"><?= (int) $unreadFromDriver ?> new</span>
        <?php endif; ?>
        <i class="bi bi-chevron-down ms-auto chev"></i>
      </div>
      <div class="collapse show" id="dchat-body">
        <div class="card-body">
          <style>
            .dchat-header { cursor:pointer; user-select:none; }
            .dchat-header .chev { transition: transform .2s ease; font-size:.95rem; opacity:.7; }
            .dchat-header.collapsed .chev { transform: rotate(-90deg); }
            .dchat-thread { max-height:360px; overflow-y:auto; padding:.4rem; display:flex; flex-direction:column; gap:.45rem; background:#fafbfc; border:1px solid #e5e7eb; border-radius:8px; margin-bottom:.8rem; }
            .dchat-empty  { text-align:center; color:#9aa3b2; font-size:.85rem; padding:1.6rem .75rem; }
            .dchat-empty .bi { font-size:1.5rem; display:block; opacity:.45; margin-bottom:.3rem; }
            .dchat-bubble { max-width:88%; padding:.5rem .75rem; border-radius:14px; font-size:.9rem; line-height:1.4; box-shadow:0 1px 1px rgba(15,23,42,.05); word-wrap:break-word; }
            .dchat-bubble .b-meta { font-size:.65rem; opacity:.65; margin-top:.25rem; text-align:right; font-weight:500; }
            .dchat-bubble .b-attach { margin-top:.4rem; }
            .dchat-bubble .b-attach img { max-width:100%; border-radius:8px; display:block; }
            .dchat-bubble .b-attach a { display:inline-flex; align-items:center; gap:.3rem; padding:.3rem .55rem; border-radius:6px; background:rgba(0,0,0,.05); color:inherit; font-size:.78rem; font-weight:600; text-decoration:none; }
            .dchat-driver { background:#fff; border:1px solid #e5e7eb; align-self:flex-start; border-bottom-left-radius:4px; color:#0f172a; }
            .dchat-staff  { background:#2563eb; color:#fff; align-self:flex-end; border-bottom-right-radius:4px; }
            .dchat-staff .b-attach a { background:rgba(255,255,255,.18); color:#fff; }
            .dchat-system { background:transparent; color:#6b7280; align-self:center; font-size:.72rem; text-align:center; max-width:90%; padding:.2rem .5rem; box-shadow:none; }
            .dchat-compose textarea { font-size:.88rem; }
            .dchat-compose input[type="file"] { font-size:.78rem; }
          </style>

          <div class="dchat-thread" id="dchatThread">
            <?php if (empty($msgs)): ?>
              <div class="dchat-empty"><i class="bi bi-chat-square-dots"></i>No messages yet. Send the first message below.</div>
            <?php else: foreach ($msgs as $m):
                $dir = $m['direction'];
                $cls = 'dchat-' . ($dir === 'driver' ? 'driver' : ($dir === 'staff' ? 'staff' : 'system'));
            ?>
              <?php if ($dir === 'system'): ?>
                <div class="dchat-bubble dchat-system"><?= esc($m['body']) ?></div>
              <?php else: ?>
                <div class="dchat-bubble <?= $cls ?>">
                  <?php if ($m['body']): ?>
                    <div class="b-body"><?= nl2br(esc($m['body'])) ?></div>
                  <?php endif; ?>
                  <?php if (!empty($m['attachment_path'])):
                      $url = site_url('trips/' . $row['id'] . '/driver-chat/attach/' . (int) $m['id']);
                      $isImg = strpos((string) $m['attachment_mime'], 'image/') === 0;
                  ?>
                    <div class="b-attach">
                      <?php if ($isImg): ?>
                        <a href="<?= esc($url) ?>" target="_blank" rel="noopener"><img src="<?= esc($url) ?>" alt="<?= esc($m['attachment_orig'] ?: 'Attachment') ?>"></a>
                      <?php else: ?>
                        <a href="<?= esc($url) ?>" target="_blank" rel="noopener"><i class="bi bi-paperclip"></i> <?= esc($m['attachment_orig'] ?: 'Attachment') ?></a>
                      <?php endif; ?>
                    </div>
                  <?php endif; ?>
                  <div class="b-meta"><?= esc($fmtTime($m['created_at'])) ?></div>
                </div>
              <?php endif; ?>
            <?php endforeach; endif; ?>
          </div>

          <form class="dchat-compose" method="post" action="<?= site_url('trips/' . $row['id'] . '/driver-chat/reply') ?>" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <textarea name="body" class="form-control form-control-sm mb-2" rows="2" placeholder="Reply to the driver…" maxlength="4000"></textarea>
            <div class="d-flex gap-2 align-items-center">
              <input type="file" name="attachment" class="form-control form-control-sm" accept="image/*,application/pdf,video/mp4,audio/*">
              <button class="btn btn-sm btn-primary" type="submit" style="flex-shrink:0;"><i class="bi bi-send-fill"></i> Send</button>
            </div>
            <small class="text-muted d-block mt-1" style="font-size:.72rem;">Driver sees replies within ~15 s (PWA polling).</small>
          </form>
        </div>
      </div>
    </div>
    <script>
      (function () {
        var STORAGE_KEY = 'tpt_dchat_collapsed_<?= (int) $row['id'] ?>';
        var head = document.querySelector('.dchat-header');
        var body = document.getElementById('dchat-body');
        if (!head || !body) return;

        try {
          if (localStorage.getItem(STORAGE_KEY) === '1') {
            body.classList.remove('show');
            head.classList.add('collapsed');
            head.setAttribute('aria-expanded', 'false');
          }
        } catch (e) {}

        body.addEventListener('shown.bs.collapse', function () {
          try { localStorage.setItem(STORAGE_KEY, '0'); } catch (e) {}
          var t = document.getElementById('dchatThread');
          if (t) t.scrollTop = t.scrollHeight;
        });
        body.addEventListener('hidden.bs.collapse', function () {
          try { localStorage.setItem(STORAGE_KEY, '1'); } catch (e) {}
        });

        if (body.classList.contains('show')) {
          var t = document.getElementById('dchatThread');
          if (t) t.scrollTop = t.scrollHeight;
        }
      })();
    </script>
    </div>
  </div>

  <!-- ══════════ TAB 5 — Expenses ══════════ -->
  <div class="tab-pane fade" id="tab-expenses" role="tabpanel">
    <div class="formwrap">
    <?= view('trips/_expenses', [
        'row' => $row,
        'expenses' => $expenses,
        'expTotals' => $expTotals,
        'expCategories' => $expCategories,
    ]) ?>
    </div>
  </div>

</div><!-- /.tab-content -->

<script>
// The trip's own controllers redirect back here with #driver-chat (staff
// reply) after posting — that widget now lives inside the "Tracking & POD"
// tab-pane instead of an always-visible sidebar, so switch to that tab first
// or the browser has nothing visible to scroll to.
(function () {
  if (window.location.hash === '#driver-chat') {
    var tabBtn = document.querySelector('[data-bs-target="#tab-tracking"]');
    if (tabBtn) tabBtn.click();
    var el = document.getElementById('driver-chat');
    if (el) setTimeout(function () { el.scrollIntoView({ block: 'start' }); }, 50);
  }
})();
</script>
