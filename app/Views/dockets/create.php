<?php
/**
 * Fast one-shot Docket / LR creation form — matches the 18-field spec
 * agreed with Anushka. Behind the scenes it creates a booking + trip with
 * the LR number pre-set, so the operator can hit the Dispatch Pack print
 * button immediately after saving.
 *
 * For accounting-heavy entries (buy/sell rate, vendor, expenses, invoice
 * flow) the full Bookings form at /bookings/create is still available.
 */
$b = $booking ?? [];
$t = $trip    ?? [];
$g = function (string $k, string $bookingKey = null, string $tripKey = null, $d = '') use ($b, $t) {
    $bk = $bookingKey ?? $k;
    $tk = $tripKey    ?? $k;
    return old($k, $b[$bk] ?? $t[$tk] ?? $d);
};

$existingRows = [];
$rawJson = (string) ($b['shipper_invoices_json'] ?? '');
if ($rawJson !== '') {
    $decoded = json_decode($rawJson, true);
    if (is_array($decoded)) $existingRows = $decoded;
}
if (empty($existingRows)) $existingRows = [['no' => '', 'value' => '']];

$pendingCount = count($pending ?? []);
$extra = '';
if ($pendingCount > 0 && empty($t['id'])) {
    $extra = '<a class="btn btn-sm btn-outline-primary" href="' . site_url('dockets/pending') . '"><i class="bi bi-list-check"></i> See all ' . ($pendingCount >= 20 ? '20+' : $pendingCount) . ' trips awaiting docket</a>';
}
?>
<?= tpt_toolbar([
    'save_form'   => 'docketForm',
    'close_href'  => site_url('trips'),
    'extra'       => $extra,
    'auth'        => $auth,
]) ?>

<div class="tabs" role="tablist" id="docketFormTabs">
  <button type="button" class="tab active" data-bs-toggle="tab" data-bs-target="#dkt-route">Route &amp; Docket</button>
  <button type="button" class="tab" data-bs-toggle="tab" data-bs-target="#dkt-parties">Parties</button>
  <button type="button" class="tab" data-bs-toggle="tab" data-bs-target="#dkt-cargo">Cargo &amp; Details</button>
  <div class="spacer"></div>
  <?php if (!empty($t['id'])): ?><div class="recordnav">Trip <?= esc($t['trip_no']) ?></div><?php endif; ?>
</div>

<form id="docketForm" method="post" action="<?= site_url('dockets/store') ?>">
  <?= csrf_field() ?>
  <?php if (!empty($t['id'])): ?>
    <input type="hidden" name="trip_id" value="<?= (int) $t['id'] ?>">
  <?php endif; ?>

  <div class="tab-content">

    <div class="tab-pane fade show active" id="dkt-route">
      <div class="formwrap">
        <?php if (empty($t['id']) && $pendingCount > 0): ?>
          <div class="retro-row" style="margin-bottom:14px;">
            <div class="retro-field" style="width:100%;"><label style="white-space:nowrap;">Pick a trip :</label>
              <select id="pendingPicker" class="retro-box xwide" style="min-width:400px;" data-tpt-search>
                <option value="">— Select trip —</option>
                <?php foreach ($pending as $p):
                  $bits = array_filter([
                    $p['trip_no'] ?? null,
                    $p['client_company'] ?? null,
                    trim(($p['loading_point'] ?? '') . ' → ' . ($p['unloading_point'] ?? ''), ' →'),
                    !empty($p['loading_date']) ? date('d M', strtotime($p['loading_date'])) : null,
                    !empty($p['vehicle_number']) ? 'Veh ' . $p['vehicle_number'] : null,
                  ]);
                  $label = implode(' · ', $bits);
                ?>
                  <option value="<?= site_url('dockets/create/' . (int) $p['id']) ?>"><?= esc($label) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <script>
            (function () {
              const sel = document.getElementById('pendingPicker');
              if (!sel) return;
              sel.addEventListener('change', () => { if (sel.value) window.location.href = sel.value; });
            })();
          </script>
        <?php endif; ?>

        <div class="retro-row">
          <div class="retro-field"><label>From <span class="retro-required">*</span> :</label><input class="retro-box wide" name="pickup_city" data-tpt-city value="<?= esc($g('pickup_city', 'pickup_city', 'loading_point')) ?>" placeholder="Loading city…" required autocomplete="off"></div>
          <div class="retro-field"><label>To <span class="retro-required">*</span> :</label><input class="retro-box wide" name="drop_city" data-tpt-city value="<?= esc($g('drop_city', 'drop_city', 'unloading_point')) ?>" placeholder="Delivery city…" required autocomplete="off"></div>
        </div>
        <div class="retro-row">
          <div class="retro-field"><label>Date <span class="retro-required">*</span> :</label><input type="date" class="retro-box" name="loading_date" value="<?= esc($g('loading_date', 'loading_date', 'loading_date', date('Y-m-d'))) ?>" required></div>
          <div class="retro-field"><label>Docket / LR Number <span class="retro-required">*</span> :</label><input class="retro-box wide" name="lr_no" value="<?= esc($g('lr_no', 'lr_no', 'lr_no')) ?>" placeholder="e.g. 5064" required></div>
        </div>
      </div>
    </div>

    <div class="tab-pane fade" id="dkt-parties">
      <div class="formwrap">
        <h6 class="mb-2 text-muted" style="font-size:.85rem;text-transform:uppercase;letter-spacing:.5px;">Consignor</h6>
        <div class="retro-row">
          <div class="retro-field" style="width:100%;"><label>Consignor <span class="retro-required">*</span> :</label>
            <select class="retro-box xwide party-select" name="consignor_client_id" data-target="consignor" required>
              <option value="">— Select consignor —</option>
              <?php foreach ($clients as $c): ?>
                <option value="<?= (int) $c['id'] ?>"
                        data-name="<?= esc($c['company_name']) ?>"
                        data-mobile="<?= esc($c['mobile'] ?? '') ?>"
                        data-address="<?= esc(trim(($c['address'] ?? '') . ' ' . ($c['city'] ?? '') . ' ' . ($c['pincode'] ?? ''))) ?>"
                        data-gstin="<?= esc($c['gst_no'] ?? '') ?>"
                        data-state="<?= esc($c['state'] ?? '') ?>"
                        <?= (int) $g('consignor_client_id') === (int) $c['id'] ? 'selected' : '' ?>>
                  <?= esc($c['company_name']) ?><?= !empty($c['city']) ? ' · ' . esc($c['city']) : '' ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="retro-row">
          <div class="retro-field"><label>Mobile :</label><input class="retro-box" id="consignor-mobile-view" name="consignor_mobile" value="<?= esc($g('consignor_mobile')) ?>" readonly></div>
          <div class="retro-field"><label>GSTIN :</label><input class="retro-box wide" id="consignor-gstin-view" name="consignor_gstin" value="<?= esc($g('consignor_gstin')) ?>" readonly></div>
        </div>
        <div class="retro-row">
          <div class="retro-field" style="width:100%;"><label>Address :</label><input class="retro-box xwide" style="min-width:400px;" id="consignor-address-view" name="consignor_address" value="<?= esc($g('consignor_address')) ?>" readonly></div>
        </div>
        <input type="hidden" id="consignor-name-hidden"  name="consignor_name"  value="<?= esc($g('consignor_name')) ?>">
        <input type="hidden" id="consignor-state-view"   name="consignor_state" value="<?= esc($g('consignor_state')) ?>">

        <hr>

        <h6 class="mb-2 text-muted" style="font-size:.85rem;text-transform:uppercase;letter-spacing:.5px;">Consignee</h6>
        <div class="retro-row">
          <div class="retro-field" style="width:100%;"><label>Consignee <span class="retro-required">*</span> :</label>
            <select class="retro-box xwide party-select" name="consignee_client_id" data-target="consignee" required>
              <option value="">— Select consignee —</option>
              <?php foreach ($clients as $c): ?>
                <option value="<?= (int) $c['id'] ?>"
                        data-name="<?= esc($c['company_name']) ?>"
                        data-mobile="<?= esc($c['mobile'] ?? '') ?>"
                        data-address="<?= esc(trim(($c['address'] ?? '') . ' ' . ($c['city'] ?? '') . ' ' . ($c['pincode'] ?? ''))) ?>"
                        data-gstin="<?= esc($c['gst_no'] ?? '') ?>"
                        data-state="<?= esc($c['state'] ?? '') ?>"
                        <?= (int) $g('consignee_client_id') === (int) $c['id'] ? 'selected' : '' ?>>
                  <?= esc($c['company_name']) ?><?= !empty($c['city']) ? ' · ' . esc($c['city']) : '' ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="retro-row">
          <div class="retro-field"><label>Mobile :</label><input class="retro-box" id="consignee-mobile-view" name="consignee_mobile" value="<?= esc($g('consignee_mobile')) ?>" readonly></div>
          <div class="retro-field"><label>GSTIN :</label><input class="retro-box wide" id="consignee-gstin-view" name="consignee_gstin" value="<?= esc($g('consignee_gstin')) ?>" readonly></div>
        </div>
        <div class="retro-row">
          <div class="retro-field" style="width:100%;"><label>Address :</label><input class="retro-box xwide" style="min-width:400px;" id="consignee-address-view" name="consignee_address" value="<?= esc($g('consignee_address')) ?>" readonly></div>
        </div>
        <input type="hidden" id="consignee-name-hidden"  name="consignee_name"  value="<?= esc($g('consignee_name')) ?>">
      </div>
    </div>

    <div class="tab-pane fade" id="dkt-cargo">
      <div class="formwrap">
        <div class="retro-row">
          <div class="retro-field"><label>No. of Packages :</label><input type="number" min="0" class="retro-box narrow" name="packages_count" value="<?= esc($g('packages_count')) ?>"></div>
          <div class="retro-field"><label>Method of Packing :</label><input class="retro-box wide" name="packing_method" value="<?= esc($g('packing_method')) ?>" placeholder="e.g. Wooden crate / Loose"></div>
        </div>
        <div class="retro-row" style="align-items:flex-start;">
          <div class="retro-field" style="width:100%;"><label style="white-space:nowrap;">Particulars :</label>
            <input class="retro-box xwide" style="min-width:400px;" name="particulars_text" value="<?= esc($g('particulars_text')) ?>" placeholder="e.g. Home appliances — 24× refrigerators">
          </div>
        </div>
        <div class="retro-row">
          <div class="retro-field"><label>Actual Weight (kg) :</label><input type="number" step="0.01" min="0" class="retro-box" name="actual_weight_kg" value="<?= esc($g('actual_weight_kg')) ?>"></div>
          <div class="retro-field"><label>Chargeable Weight (kg) <span class="retro-required">*</span> :</label><input type="number" step="0.01" min="0" class="retro-box" name="charge_weight_kg" value="<?= esc($g('charge_weight_kg')) ?>" required></div>
        </div>
        <div class="retro-row">
          <div class="retro-field"><label>Vehicle Type :</label><input class="retro-box wide" name="vehicle_type" value="<?= esc($g('vehicle_type')) ?>" placeholder="e.g. 32 FT MXL"></div>
          <div class="retro-field"><label>Vehicle Number :</label><input class="retro-box wide" style="text-transform:uppercase;" name="vehicle_number" value="<?= esc($g('vehicle_number', 'vehicle_number', 'vehicle_number')) ?>" placeholder="e.g. HR 55 AR 7375"></div>
          <div class="retro-field"><label>Driver Mobile :</label><input class="retro-box" name="driver_mobile" value="<?= esc($g('driver_mobile', 'driver_mobile', 'driver_mobile')) ?>" placeholder="10-digit"></div>
        </div>
        <div class="retro-row">
          <div class="retro-field"><label>Bill of Entry :</label><input class="retro-box wide" name="bill_of_entry" value="<?= esc($g('bill_of_entry')) ?>"></div>
          <div class="retro-field"><label>Container No. :</label><input class="retro-box wide" name="container_number" value="<?= esc($g('container_number')) ?>"></div>
          <div class="retro-field"><label>E-Way Bill No. :</label><input class="retro-box wide" name="ewb_no" value="<?= esc($g('ewb_no', 'ewb_no', 'ewb_no')) ?>" placeholder="12-digit EWB"></div>
        </div>

        <h6 class="mb-1 mt-3 text-muted" style="font-size:.82rem;text-transform:uppercase;letter-spacing:.5px;">Shipper Invoice(s) &amp; Value</h6>
        <table class="table table-sm grid mb-1 mt-2" id="shipperInvoiceTable" style="max-width:720px;">
          <thead>
            <tr>
              <th style="width:40px;">#</th>
              <th>Invoice No.</th>
              <th style="width:180px;">Invoice Value (₹)</th>
              <th style="width:60px;"></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($existingRows as $idx => $ri): ?>
              <tr>
                <td class="text-muted"><?= (int) $idx + 1 ?></td>
                <td><input class="retro-box" style="width:100%;" name="shipper_invoices[<?= $idx ?>][no]" value="<?= esc($ri['no'] ?? '') ?>" placeholder="e.g. HO/LS01142"></td>
                <td><input type="number" step="0.01" min="0" class="retro-box si-value" style="width:100%;" name="shipper_invoices[<?= $idx ?>][value]" value="<?= esc($ri['value'] ?? '') ?>"></td>
                <td><button type="button" class="retro-tbtn retro-danger si-remove" style="width:auto;flex-direction:row;padding:4px 8px !important;" title="Remove">&times;</button></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <button type="button" class="retro-tbtn retro-primary" style="width:auto;flex-direction:row;padding:5px 10px !important;" id="siAddRow"><i class="bi bi-plus-lg"></i> Add another invoice</button>
        <div class="text-muted mt-1" style="font-size:.8rem;">Total invoice value: ₹<span id="siTotal">0.00</span></div>

        <div class="retro-row" style="margin-top:14px;">
          <div class="retro-field"><label>Remarks / Payment Method <span class="retro-required">*</span> :</label>
            <select class="retro-box wide" name="freight_mode" required>
              <option value="">— Select —</option>
              <?php foreach (['To Pay','Paid','To Be Billed'] as $fm): ?>
                <option value="<?= $fm ?>" <?= $g('freight_mode') === $fm ? 'selected' : '' ?>><?= $fm ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      </div>
    </div>

  </div>

  <div class="retro-toolbar mt-3" style="position:static;">
    <button type="submit" class="retro-tbtn retro-primary"><i class="bi bi-check2-circle"></i>Save &amp; Dispatch Pack</button>
    <a class="retro-tbtn" href="<?= site_url('trips') ?>"><i class="bi bi-x-circle"></i>Close</a>
  </div>
</form>

<script>
// Consignor / Consignee autofill
(function () {
  function fill(target, opt) {
    var name    = opt ? (opt.getAttribute('data-name')    || '') : '';
    var mobile  = opt ? (opt.getAttribute('data-mobile')  || '') : '';
    var address = opt ? (opt.getAttribute('data-address') || '') : '';
    var gstin   = opt ? (opt.getAttribute('data-gstin')   || '') : '';
    var state   = opt ? (opt.getAttribute('data-state')   || '') : '';
    var byId = function (id) { return document.getElementById(id); };
    if (byId(target + '-mobile-view'))  byId(target + '-mobile-view').value  = mobile;
    if (byId(target + '-gstin-view'))   byId(target + '-gstin-view').value   = gstin;
    if (byId(target + '-address-view')) byId(target + '-address-view').value = address;
    if (byId(target + '-state-view'))   byId(target + '-state-view').value   = state;
    if (byId(target + '-name-hidden'))  byId(target + '-name-hidden').value  = name;
  }
  document.querySelectorAll('.party-select').forEach(function (sel) {
    sel.addEventListener('change', function () {
      var opt = sel.options[sel.selectedIndex];
      fill(sel.getAttribute('data-target'), (opt && opt.value) ? opt : null);
    });
    if (sel.value) fill(sel.getAttribute('data-target'), sel.options[sel.selectedIndex]);
  });
})();

// Shipper Invoices — add / remove rows + live total
(function () {
  var table = document.getElementById('shipperInvoiceTable');
  var addBtn = document.getElementById('siAddRow');
  var totalEl = document.getElementById('siTotal');
  if (!table || !addBtn) return;
  var tbody = table.querySelector('tbody');
  function renumber() {
    var rows = tbody.querySelectorAll('tr');
    var total = 0;
    rows.forEach(function (tr, i) {
      tr.cells[0].textContent = i + 1;
      var inNo  = tr.querySelector('input[name^="shipper_invoices"]');
      var inVal = tr.querySelector('input.si-value');
      if (inNo)  inNo.name  = 'shipper_invoices[' + i + '][no]';
      if (inVal) { inVal.name = 'shipper_invoices[' + i + '][value]'; total += parseFloat(inVal.value) || 0; }
    });
    totalEl.textContent = total.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
  }
  addBtn.addEventListener('click', function () {
    var i = tbody.querySelectorAll('tr').length;
    var tr = document.createElement('tr');
    tr.innerHTML =
      '<td class="text-muted">' + (i + 1) + '</td>' +
      '<td><input class="retro-box" style="width:100%;" name="shipper_invoices[' + i + '][no]" placeholder="e.g. HO/LS01142"></td>' +
      '<td><input type="number" step="0.01" min="0" class="retro-box si-value" style="width:100%;" name="shipper_invoices[' + i + '][value]"></td>' +
      '<td><button type="button" class="retro-tbtn retro-danger si-remove" style="width:auto;flex-direction:row;padding:4px 8px !important;" title="Remove">&times;</button></td>';
    tbody.appendChild(tr);
    renumber();
  });
  tbody.addEventListener('click', function (e) {
    if (e.target && e.target.classList.contains('si-remove')) {
      var tr = e.target.closest('tr');
      if (tbody.querySelectorAll('tr').length > 1) { tr.remove(); renumber(); }
      else { tr.querySelectorAll('input').forEach(function (i) { i.value = ''; }); renumber(); }
    }
  });
  tbody.addEventListener('input', function (e) {
    if (e.target && e.target.classList.contains('si-value')) renumber();
  });
  renumber();
})();

// If validation failed on a tab other than Route & Docket, activate it so the user sees the error.
(function () {
  var f = document.getElementById('docketForm');
  if (!f) return;
  f.addEventListener('submit', function (e) {
    var invalid = f.querySelector(':invalid');
    if (!invalid) return;
    var pane = invalid.closest('.tab-pane');
    if (!pane) return;
    var btn = document.querySelector('[data-bs-target="#' + pane.id + '"]');
    if (btn && !pane.classList.contains('active')) {
      new bootstrap.Tab(btn).show();
    }
  }, true);
})();
</script>
