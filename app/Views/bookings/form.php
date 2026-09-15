<?php
$isEdit = !empty($row);
$action = $isEdit ? site_url('bookings/' . $row['id']) : site_url('bookings/store');

// Prefill from RFQ + final quotation if coming from Convert-to-Booking
$pre = [
    'client_id'       => $lead['client_id'] ?? ($row['client_id'] ?? ''),
    'vendor_id'       => $quote['vendor_id'] ?? ($row['vendor_id'] ?? ''),
    'final_buy_rate'  => $quote['quote_amount'] ?? ($row['final_buy_rate'] ?? ''),
    'final_sell_rate' => $row['final_sell_rate'] ?? '',
    'pickup_city'     => $rfq['pickup_city'] ?? ($row['pickup_city'] ?? ''),
    'drop_city'       => $rfq['drop_city']   ?? ($row['drop_city']   ?? ''),
    'route_text'      => $rfq ? trim(($rfq['pickup_city'] ?? '') . ' - ' . ($rfq['drop_city'] ?? ''), ' -') : ($row['route_text'] ?? ''),
    'vehicle_type'    => $rfq['vehicle_type'] ?? ($row['vehicle_type'] ?? ''),
    'load_details'    => $rfq ? trim(($rfq['material_category'] ?? '') . ' · ' . ($rfq['weight'] ?? '') . ' ' . ($rfq['weight_unit'] ?? ''), ' · ') : ($row['load_details'] ?? ''),
    'loading_date'    => $rfq['loading_date'] ?? ($row['loading_date'] ?? ''),
    'billing_party'   => $row['billing_party'] ?? '',
    'instructions'    => $row['instructions'] ?? '',
    'lead_id'         => $rfq['lead_id']  ?? ($row['lead_id'] ?? ''),
    'rfq_id'          => $rfq['id']       ?? '',
];
$v = function ($k, $d = '') use ($pre) { return old($k, $pre[$k] ?? $d); };

// Existing shipper invoices (or 1 blank row)
$existingRows = [];
$rawJson = (string) ($v('shipper_invoices_json', '') ?: '');
if ($rawJson !== '') {
    $decoded = json_decode($rawJson, true);
    if (is_array($decoded)) $existingRows = $decoded;
}
if (empty($existingRows) && trim((string) $v('invoice_number', '')) !== '') {
    $legacyNos = array_map('trim', explode(',', (string) $v('invoice_number', '')));
    foreach ($legacyNos as $ln) if ($ln !== '') $existingRows[] = ['no' => $ln, 'value' => ''];
}
if (empty($existingRows)) $existingRows = [['no' => '', 'value' => '']];
?>
<?= tpt_toolbar([
    'save_form'   => 'bookingForm',
    'close_href'  => site_url('bookings'),
    'auth'        => $auth,
]) ?>

<div class="tabs" role="tablist" id="bookingFormTabs">
  <button type="button" class="tab active" data-bs-toggle="tab" data-bs-target="#tab-basics">1 · Basics</button>
  <button type="button" class="tab" data-bs-toggle="tab" data-bs-target="#tab-parties">2 · Consignor / Consignee</button>
  <button type="button" class="tab" data-bs-toggle="tab" data-bs-target="#tab-cargo">3 · Cargo &amp; Docket</button>
  <div class="spacer"></div>
</div>

<form id="bookingForm" method="post" action="<?= $action ?>">
  <?= csrf_field() ?>
  <?php if (!empty($pre['lead_id'])): ?><input type="hidden" name="lead_id" value="<?= (int) $pre['lead_id'] ?>"><?php endif; ?>
  <?php if (!empty($pre['rfq_id'])):  ?><input type="hidden" name="rfq_id"  value="<?= (int) $pre['rfq_id'] ?>"><?php  endif; ?>

  <!-- Hidden name fields for consignor/consignee (autofilled by JS from party <select>) -->
  <input type="hidden" id="consignor-name-hidden" name="consignor_name" value="<?= esc($v('consignor_name')) ?>">
  <input type="hidden" id="consignee-name-hidden" name="consignee_name" value="<?= esc($v('consignee_name')) ?>">

  <div class="tab-content">

    <!-- ══════════ TAB 1 — Basics: who, where, when, freight ══════════ -->
    <div class="tab-pane fade show active" id="tab-basics">
      <div class="formwrap">
        <div class="retro-row">
          <div class="retro-field" style="width:100%;"><label>Client <span class="retro-required">*</span> :</label>
            <select class="retro-box xwide" name="client_id" required>
              <option value="">— Select —</option>
              <?php foreach ($clients as $c): ?>
                <option value="<?= $c['id'] ?>" <?= (int) $v('client_id') === (int) $c['id'] ? 'selected' : '' ?>><?= esc($c['company_name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="retro-row">
          <div class="retro-field" style="width:100%;"><label>Vendor :</label>
            <select class="retro-box xwide" name="vendor_id">
              <option value="">— Select —</option>
              <?php foreach ($vendors as $vn): ?>
                <option value="<?= $vn['id'] ?>" <?= (int) $v('vendor_id') === (int) $vn['id'] ? 'selected' : '' ?>><?= esc($vn['company_name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="retro-row">
          <div class="retro-field"><label>Pickup City <span class="retro-required">*</span> :</label><input class="retro-box wide" name="pickup_city" data-tpt-city value="<?= esc($v('pickup_city')) ?>" placeholder="Start typing…" required autocomplete="off"></div>
          <div class="retro-field"><label>Drop City <span class="retro-required">*</span> :</label><input class="retro-box wide" name="drop_city" data-tpt-city value="<?= esc($v('drop_city')) ?>" placeholder="Start typing…" required autocomplete="off"></div>
        </div>
        <div class="retro-row">
          <div class="retro-field"><label>Loading Date :</label><input type="date" class="retro-box" name="loading_date" value="<?= esc($v('loading_date')) ?>"></div>
          <div class="retro-field"><label>Vehicle Type :</label><input class="retro-box wide" name="vehicle_type" value="<?= esc($v('vehicle_type')) ?>"></div>
        </div>
        <div class="retro-row">
          <div class="retro-field"><label>Charge Weight (kg) <span class="retro-required">*</span> :</label><input type="number" step="0.01" min="0" class="retro-box" name="charge_weight_kg" value="<?= esc($v('charge_weight_kg')) ?>" required></div>
          <div class="retro-field"><label>Freight Mode <span class="retro-required">*</span> :</label>
            <select class="retro-box wide" name="freight_mode" required>
              <option value="">— Select —</option>
              <?php foreach (['To Pay','Paid','To Be Billed'] as $fm): ?>
                <option value="<?= $fm ?>" <?= $v('freight_mode') === $fm ? 'selected' : '' ?>><?= $fm ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="retro-row">
          <div class="retro-field"><label>Buy Rate (₹) :</label><input type="number" step="0.01" class="retro-box" name="final_buy_rate" value="<?= esc($v('final_buy_rate', '')) ?>" placeholder="Pay vendor"></div>
          <div class="retro-field"><label>Sell Rate (₹) :</label><input type="number" step="0.01" class="retro-box" name="final_sell_rate" value="<?= esc($v('final_sell_rate', '')) ?>" placeholder="Charge client"></div>
        </div>
        <div class="retro-row">
          <div class="retro-field" style="width:100%;"><label>Load Details :</label><input class="retro-box xwide" style="min-width:300px;" name="load_details" value="<?= esc($v('load_details')) ?>"></div>
        </div>
        <div class="retro-row">
          <div class="retro-field" style="width:100%;"><label>Billing Party :</label><input class="retro-box xwide" style="min-width:300px;" name="billing_party" value="<?= esc($v('billing_party')) ?>"></div>
        </div>
        <div class="retro-row" style="align-items:flex-start;">
          <div class="retro-field" style="width:100%;"><label style="white-space:nowrap;">Instructions :</label>
            <textarea class="retro-particulars" name="instructions" rows="2"><?= esc($v('instructions')) ?></textarea>
          </div>
        </div>
      </div>
    </div>

    <!-- ══════════ TAB 2 — Consignor / Consignee ══════════ -->
    <div class="tab-pane fade" id="tab-parties">
      <div class="formwrap">
        <h6 class="mb-2 text-muted" style="font-size:.85rem;text-transform:uppercase;letter-spacing:.5px;">Consignor</h6>
        <div class="retro-row">
          <div class="retro-field" style="width:100%;"><label>Consignor :</label>
            <select class="retro-box xwide party-select" name="consignor_client_id" data-target="consignor">
              <option value="">— Select consignor —</option>
              <?php foreach ($clients as $c): ?>
                <option value="<?= (int) $c['id'] ?>"
                        data-name="<?= esc($c['company_name']) ?>"
                        data-mobile="<?= esc($c['mobile'] ?? '') ?>"
                        data-address="<?= esc(trim(($c['address'] ?? '') . ' ' . ($c['city'] ?? '') . ' ' . ($c['pincode'] ?? ''))) ?>"
                        data-gstin="<?= esc($c['gst_no'] ?? '') ?>"
                        data-state="<?= esc($c['state'] ?? '') ?>"
                        <?= (int) $v('consignor_client_id') === (int) $c['id'] ? 'selected' : '' ?>>
                  <?= esc($c['company_name']) ?><?= !empty($c['city']) ? ' · ' . esc($c['city']) : '' ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="retro-row">
          <div class="retro-field"><label>Mobile :</label><input class="retro-box" id="consignor-mobile-view" name="consignor_mobile" value="<?= esc($v('consignor_mobile')) ?>" readonly></div>
          <div class="retro-field"><label>GSTIN :</label><input class="retro-box wide" id="consignor-gstin-view" name="consignor_gstin" value="<?= esc($v('consignor_gstin')) ?>" readonly></div>
          <div class="retro-field"><label>State :</label><input class="retro-box" id="consignor-state-view" name="consignor_state" value="<?= esc($v('consignor_state')) ?>" readonly></div>
        </div>
        <div class="retro-row">
          <div class="retro-field" style="width:100%;"><label>Address :</label><input class="retro-box xwide" style="min-width:400px;" id="consignor-address-view" name="consignor_address" value="<?= esc($v('consignor_address')) ?>" readonly></div>
        </div>

        <hr>

        <h6 class="mb-2 text-muted" style="font-size:.85rem;text-transform:uppercase;letter-spacing:.5px;">Consignee</h6>
        <div class="retro-row">
          <div class="retro-field" style="width:100%;"><label>Consignee :</label>
            <select class="retro-box xwide party-select" name="consignee_client_id" data-target="consignee">
              <option value="">— Select consignee —</option>
              <?php foreach ($clients as $c): ?>
                <option value="<?= (int) $c['id'] ?>"
                        data-name="<?= esc($c['company_name']) ?>"
                        data-mobile="<?= esc($c['mobile'] ?? '') ?>"
                        data-address="<?= esc(trim(($c['address'] ?? '') . ' ' . ($c['city'] ?? '') . ' ' . ($c['pincode'] ?? ''))) ?>"
                        data-gstin="<?= esc($c['gst_no'] ?? '') ?>"
                        data-state="<?= esc($c['state'] ?? '') ?>"
                        <?= (int) $v('consignee_client_id') === (int) $c['id'] ? 'selected' : '' ?>>
                  <?= esc($c['company_name']) ?><?= !empty($c['city']) ? ' · ' . esc($c['city']) : '' ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="retro-row">
          <div class="retro-field"><label>Mobile :</label><input class="retro-box" id="consignee-mobile-view" name="consignee_mobile" value="<?= esc($v('consignee_mobile')) ?>" readonly></div>
          <div class="retro-field"><label>GSTIN :</label><input class="retro-box wide" id="consignee-gstin-view" name="consignee_gstin" value="<?= esc($v('consignee_gstin')) ?>" readonly></div>
        </div>
        <div class="retro-row">
          <div class="retro-field" style="width:100%;"><label>Address :</label><input class="retro-box xwide" style="min-width:400px;" id="consignee-address-view" name="consignee_address" value="<?= esc($v('consignee_address')) ?>" readonly></div>
        </div>
      </div>
    </div>

    <!-- ══════════ TAB 3 — Cargo & Docket details for LR print ══════════ -->
    <div class="tab-pane fade" id="tab-cargo">
      <div class="formwrap">
        <div class="retro-row" style="align-items:flex-start;">
          <div class="retro-field" style="width:100%;"><label style="white-space:nowrap;"><strong>Particulars</strong> :</label>
            <textarea class="retro-particulars" name="particulars_text" rows="2" placeholder="e.g. Home appliances — 24× refrigerators, wooden-crated"><?= esc($v('particulars_text')) ?></textarea>
          </div>
        </div>
        <div class="retro-row">
          <div class="retro-field"><label>No. of Packages :</label><input type="number" min="0" class="retro-box narrow" name="packages_count" value="<?= esc($v('packages_count')) ?>"></div>
          <div class="retro-field"><label>Method of Packing :</label><input class="retro-box wide" name="packing_method" value="<?= esc($v('packing_method')) ?>" placeholder="e.g. Wooden crate / Loose / Palletized"></div>
          <div class="retro-field"><label>Actual Weight (kg) :</label><input type="number" step="0.01" min="0" class="retro-box" name="actual_weight_kg" value="<?= esc($v('actual_weight_kg')) ?>"></div>
        </div>
        <div class="retro-row">
          <div class="retro-field"><label>Person Liable for GST :</label>
            <select class="retro-box wide" name="person_liable_gst">
              <?php foreach (['','Consignor','Consignee','TCE'] as $opt): ?>
                <option value="<?= esc($opt) ?>" <?= (string) $v('person_liable_gst') === $opt ? 'selected' : '' ?>><?= $opt === '' ? '—' : esc($opt) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="retro-field"><label>Cargo Value (₹) :</label><input type="number" step="0.01" min="0" class="retro-box wide" name="cargo_value_inr" value="<?= esc($v('cargo_value_inr')) ?>"></div>
        </div>
        <div class="retro-row">
          <div class="retro-field"><label>Length (cm) :</label><input type="number" step="0.01" min="0" class="retro-box narrow" name="dim_length_cm" value="<?= esc($v('dim_length_cm')) ?>"></div>
          <div class="retro-field"><label>Width (cm) :</label><input type="number" step="0.01" min="0" class="retro-box narrow" name="dim_width_cm" value="<?= esc($v('dim_width_cm')) ?>"></div>
          <div class="retro-field"><label>Height (cm) :</label><input type="number" step="0.01" min="0" class="retro-box narrow" name="dim_height_cm" value="<?= esc($v('dim_height_cm')) ?>"></div>
        </div>

        <h6 class="mb-1 mt-3 text-muted" style="font-size:.82rem;text-transform:uppercase;letter-spacing:.5px;">Charges</h6>
        <div class="retro-row">
          <div class="retro-field"><label>Additional Charges (₹) :</label><input type="number" step="0.01" min="0" class="retro-box" name="additional_charges" value="<?= esc($v('additional_charges')) ?>"></div>
          <div class="retro-field"><label>Other Charges (₹) :</label><input type="number" step="0.01" min="0" class="retro-box" name="other_charges" value="<?= esc($v('other_charges')) ?>"></div>
        </div>
        <div class="retro-row">
          <div class="retro-field"><label>GST Amount (₹) :</label><input type="number" step="0.01" min="0" class="retro-box" name="gst_amount" value="<?= esc($v('gst_amount')) ?>"></div>
          <div class="retro-field"><label>Service Tax (₹) :</label><input type="number" step="0.01" min="0" class="retro-box" name="service_tax_amount" value="<?= esc($v('service_tax_amount')) ?>"></div>
        </div>

        <h6 class="mb-1 mt-3 text-muted" style="font-size:.82rem;text-transform:uppercase;letter-spacing:.5px;">References</h6>
        <div class="retro-row">
          <div class="retro-field"><label>Driver Mobile :</label><input class="retro-box" name="driver_mobile" value="<?= esc($v('driver_mobile')) ?>" placeholder="10-digit"></div>
          <div class="retro-field"><label>E-Way Bill No. :</label><input class="retro-box wide" name="ewb_no" value="<?= esc($v('ewb_no')) ?>" placeholder="12-digit EWB"></div>
          <div class="retro-field"><label>Bill of Entry :</label><input class="retro-box" name="bill_of_entry" value="<?= esc($v('bill_of_entry')) ?>"></div>
        </div>
        <div class="retro-row">
          <div class="retro-field"><label>B/L No. :</label><input class="retro-box" name="bl_number" value="<?= esc($v('bl_number')) ?>"></div>
          <div class="retro-field"><label>Container No. :</label><input class="retro-box" name="container_number" value="<?= esc($v('container_number')) ?>"></div>
          <div class="retro-field"><label>Seal No. :</label><input class="retro-box" name="seal_number" value="<?= esc($v('seal_number')) ?>"></div>
        </div>

        <h6 class="mb-1 mt-3 text-muted" style="font-size:.82rem;text-transform:uppercase;letter-spacing:.5px;">Shipper Invoices</h6>
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
        <div class="text-muted mt-1" style="font-size:.8rem;">Total: ₹<span id="siTotal">0.00</span></div>
      </div>
    </div>

  </div><!-- /.tab-content -->

  <div class="retro-toolbar mt-3" style="position:static;">
    <button type="submit" class="retro-tbtn retro-primary"><i class="bi bi-save-fill"></i>Save</button>
    <a class="retro-tbtn" href="<?= site_url('bookings') ?>"><i class="bi bi-x-circle"></i>Close</a>
  </div>
</form>

<script>
// Auto-fill Consignor / Consignee address/mobile/GSTIN/state when picked
(function () {
  function fill(target, opt) {
    var pref = target;
    var name    = opt ? (opt.getAttribute('data-name')    || '') : '';
    var mobile  = opt ? (opt.getAttribute('data-mobile')  || '') : '';
    var address = opt ? (opt.getAttribute('data-address') || '') : '';
    var gstin   = opt ? (opt.getAttribute('data-gstin')   || '') : '';
    var state   = opt ? (opt.getAttribute('data-state')   || '') : '';
    var byId = function (id) { return document.getElementById(id); };
    if (byId(pref + '-mobile-view'))  byId(pref + '-mobile-view').value  = mobile;
    if (byId(pref + '-gstin-view'))   byId(pref + '-gstin-view').value   = gstin;
    if (byId(pref + '-address-view')) byId(pref + '-address-view').value = address;
    if (byId(pref + '-state-view'))   byId(pref + '-state-view').value   = state;
    if (byId(pref + '-name-hidden'))  byId(pref + '-name-hidden').value  = name;
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
  var table   = document.getElementById('shipperInvoiceTable');
  var addBtn  = document.getElementById('siAddRow');
  var totalEl = document.getElementById('siTotal');
  if (!table || !addBtn) return;
  var tbody = table.querySelector('tbody');

  function renumber() {
    var rows = tbody.querySelectorAll('tr');
    var total = 0;
    rows.forEach(function (tr, i) {
      tr.cells[0].textContent = i + 1;
      var inNo = tr.querySelector('input[name^="shipper_invoices"]');
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

// If validation failed on a tab other than Basics, activate it so the user sees the error.
(function () {
  var f = document.querySelector('form[action="<?= $action ?>"]');
  if (!f) return;
  f.addEventListener('submit', function (e) {
    // Native HTML5 validation — find the first invalid field and jump to its tab.
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
