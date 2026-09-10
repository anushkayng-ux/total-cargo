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
// When launched from a trip page, $trip + $booking are pre-set. Values in
// existing records take priority over `old()` (which is empty on a fresh GET).
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
?>
<div class="d-flex align-items-center flex-wrap gap-2 mb-3">
  <h5 class="m-0"><i class="bi bi-file-earmark-ruled"></i> <?= esc($pageTitle) ?></h5>
  <?php $pendingCount = count($pending ?? []); ?>
  <?php if ($pendingCount > 0 && empty($t['id'])): ?>
    <a href="<?= site_url('dockets/pending') ?>" class="btn btn-sm btn-outline-primary ms-auto">
      <i class="bi bi-list-check"></i> See all <?= $pendingCount >= 20 ? '20+' : $pendingCount ?> trips awaiting docket
    </a>
  <?php endif; ?>
</div>

<?php if (empty($t['id']) && $pendingCount > 0): ?>
  <!-- Pending picker — shown only when the operator opened this page cold (not from a trip page)
       and there are trips still waiting for LRs. Picking a row = jump into the pre-filled form. -->
  <div class="card mb-3" style="border-left:4px solid var(--v2-primary, #2f5eff);">
    <div class="card-body py-3">
      <div class="d-flex align-items-center gap-2 mb-2">
        <strong style="font-size:.95rem;">Pick a trip</strong>
      </div>
      <select id="pendingPicker" class="form-select" data-tpt-search>
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
      <script>
        (function () {
          const sel = document.getElementById('pendingPicker');
          if (!sel) return;
          sel.addEventListener('change', () => { if (sel.value) window.location.href = sel.value; });
        })();
      </script>
    </div>
  </div>
<?php endif; ?>

<div class="card"><div class="card-body">
<form method="post" action="<?= site_url('dockets/store') ?>">
  <?= csrf_field() ?>
  <?php if (!empty($t['id'])): ?>
    <input type="hidden" name="trip_id" value="<?= (int) $t['id'] ?>">
  <?php endif; ?>
  <div class="row g-3">
    <!-- Row 1: From / To / Date / Docket Number -->
    <div class="col-md-3"><label class="form-label">From <span class="text-danger">*</span></label>
      <input class="form-control" name="pickup_city" data-tpt-city value="<?= esc($g('pickup_city', 'pickup_city', 'loading_point')) ?>" placeholder="Loading city…" required autocomplete="off"></div>
    <div class="col-md-3"><label class="form-label">To <span class="text-danger">*</span></label>
      <input class="form-control" name="drop_city" data-tpt-city value="<?= esc($g('drop_city', 'drop_city', 'unloading_point')) ?>" placeholder="Delivery city…" required autocomplete="off"></div>
    <div class="col-md-3"><label class="form-label">Date <span class="text-danger">*</span></label>
      <input type="date" class="form-control" name="loading_date" value="<?= esc($g('loading_date', 'loading_date', 'loading_date', date('Y-m-d'))) ?>" required></div>
    <div class="col-md-3"><label class="form-label">Docket / LR Number <span class="text-danger">*</span></label>
      <input class="form-control" name="lr_no" value="<?= esc($g('lr_no', 'lr_no', 'lr_no')) ?>" placeholder="e.g. 5064" required></div>

    <div class="col-12"><hr class="mt-2 mb-0"></div>
    <div class="col-md-6"><label class="form-label">Consignor <span class="text-danger">*</span></label>
      <select class="form-select party-select" name="consignor_client_id" data-target="consignor" required>
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
    <div class="col-md-6"><label class="form-label">Consignee <span class="text-danger">*</span></label>
      <select class="form-select party-select" name="consignee_client_id" data-target="consignee" required>
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

    <div class="col-md-3"><label class="form-label">Consignor Mobile</label>
      <input class="form-control" id="consignor-mobile-view" name="consignor_mobile" value="<?= esc($g('consignor_mobile')) ?>" readonly style="background:#f6f8fa;"></div>
    <div class="col-md-3"><label class="form-label">Consignor GSTIN</label>
      <input class="form-control" id="consignor-gstin-view" name="consignor_gstin" value="<?= esc($g('consignor_gstin')) ?>" readonly style="background:#f6f8fa;"></div>
    <div class="col-md-3"><label class="form-label">Consignee Mobile</label>
      <input class="form-control" id="consignee-mobile-view" name="consignee_mobile" value="<?= esc($g('consignee_mobile')) ?>" readonly style="background:#f6f8fa;"></div>
    <div class="col-md-3"><label class="form-label">Consignee GSTIN</label>
      <input class="form-control" id="consignee-gstin-view" name="consignee_gstin" value="<?= esc($g('consignee_gstin')) ?>" readonly style="background:#f6f8fa;"></div>

    <div class="col-md-6"><label class="form-label">Consignor Address</label>
      <input class="form-control" id="consignor-address-view" name="consignor_address" value="<?= esc($g('consignor_address')) ?>" readonly style="background:#f6f8fa;"></div>
    <div class="col-md-6"><label class="form-label">Consignee Address</label>
      <input class="form-control" id="consignee-address-view" name="consignee_address" value="<?= esc($g('consignee_address')) ?>" readonly style="background:#f6f8fa;"></div>

    <input type="hidden" id="consignor-name-hidden"  name="consignor_name"  value="<?= esc($g('consignor_name')) ?>">
    <input type="hidden" id="consignor-state-view"   name="consignor_state" value="<?= esc($g('consignor_state')) ?>">
    <input type="hidden" id="consignee-name-hidden"  name="consignee_name"  value="<?= esc($g('consignee_name')) ?>">

    <div class="col-12"><hr class="mt-2 mb-0"></div>
    <div class="col-md-2"><label class="form-label">No. of Packages</label>
      <input type="number" min="0" class="form-control" name="packages_count" value="<?= esc($g('packages_count')) ?>"></div>
    <div class="col-md-3"><label class="form-label">Method of Packaging</label>
      <input class="form-control" name="packing_method" value="<?= esc($g('packing_method')) ?>" placeholder="e.g. Wooden crate / Loose"></div>
    <div class="col-md-7"><label class="form-label">Particulars</label>
      <input class="form-control" name="particulars_text" value="<?= esc($g('particulars_text')) ?>" placeholder="e.g. Home appliances — 24× refrigerators"></div>

    <div class="col-md-3"><label class="form-label">Actual Weight (kg)</label>
      <input type="number" step="0.01" min="0" class="form-control" name="actual_weight_kg" value="<?= esc($g('actual_weight_kg')) ?>"></div>
    <div class="col-md-3"><label class="form-label">Chargeable Weight (kg) <span class="text-danger">*</span></label>
      <input type="number" step="0.01" min="0" class="form-control" name="charge_weight_kg" value="<?= esc($g('charge_weight_kg')) ?>" required></div>
    <div class="col-md-3"><label class="form-label">Vehicle Type</label>
      <input class="form-control" name="vehicle_type" value="<?= esc($g('vehicle_type')) ?>" placeholder="e.g. 32 FT MXL"></div>
    <div class="col-md-3"><label class="form-label">Vehicle Number</label>
      <input class="form-control" name="vehicle_number" value="<?= esc($g('vehicle_number', 'vehicle_number', 'vehicle_number')) ?>" placeholder="e.g. HR 55 AR 7375"
             style="text-transform:uppercase;"></div>
    <div class="col-md-3"><label class="form-label">Driver Mobile Number</label>
      <input class="form-control" name="driver_mobile" value="<?= esc($g('driver_mobile', 'driver_mobile', 'driver_mobile')) ?>" placeholder="10-digit"></div>

    <div class="col-12"><hr class="mt-2 mb-0"></div>
    <div class="col-md-4"><label class="form-label">Bill of Entry Number</label>
      <input class="form-control" name="bill_of_entry" value="<?= esc($g('bill_of_entry')) ?>"></div>
    <div class="col-md-4"><label class="form-label">Container Number</label>
      <input class="form-control" name="container_number" value="<?= esc($g('container_number')) ?>"></div>
    <div class="col-md-4"><label class="form-label">E-Way Bill Number</label>
      <input class="form-control" name="ewb_no" value="<?= esc($g('ewb_no', 'ewb_no', 'ewb_no')) ?>" placeholder="12-digit EWB"></div>

    <div class="col-12">
      <label class="form-label"><strong>Shipper Invoice(s) &amp; Value</strong></label>
      <table class="table table-sm mb-1" id="shipperInvoiceTable" style="max-width:720px;">
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
              <td><input class="form-control form-control-sm" name="shipper_invoices[<?= $idx ?>][no]" value="" placeholder="e.g. HO/LS01142"></td>
              <td><input type="number" step="0.01" min="0" class="form-control form-control-sm si-value" name="shipper_invoices[<?= $idx ?>][value]" value=""></td>
              <td><button type="button" class="btn btn-sm btn-outline-danger si-remove" title="Remove">×</button></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <button type="button" class="btn btn-sm btn-outline-primary" id="siAddRow"><i class="bi bi-plus-lg"></i> Add another invoice</button>
      <div class="text-muted mt-1" style="font-size:.8rem;">Total invoice value: ₹<span id="siTotal">0.00</span></div>
    </div>

    <div class="col-12"><hr class="mt-2 mb-0"></div>
    <div class="col-md-4"><label class="form-label">Remarks / Payment Method <span class="text-danger">*</span></label>
      <select class="form-select" name="freight_mode" required>
        <option value="">— Select —</option>
        <?php foreach (['To Pay','Paid','To Be Billed'] as $fm): ?>
          <option value="<?= $fm ?>" <?= $g('freight_mode') === $fm ? 'selected' : '' ?>><?= $fm ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>

  <div class="mt-3 d-flex gap-2">
    <button class="btn btn-primary" type="submit"><i class="bi bi-check2-circle"></i> Save &amp; open Dispatch Pack</button>
    <a class="btn btn-light" href="<?= site_url('trips') ?>">Cancel</a>
  </div>
</form>
</div></div>

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
      '<td><input class="form-control form-control-sm" name="shipper_invoices[' + i + '][no]" placeholder="e.g. HO/LS01142"></td>' +
      '<td><input type="number" step="0.01" min="0" class="form-control form-control-sm si-value" name="shipper_invoices[' + i + '][value]"></td>' +
      '<td><button type="button" class="btn btn-sm btn-outline-danger si-remove" title="Remove">&times;</button></td>';
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
</script>
