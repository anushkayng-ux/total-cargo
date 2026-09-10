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
<h5 class="mb-3"><?= esc($pageTitle) ?></h5>

<form method="post" action="<?= $action ?>">
  <?= csrf_field() ?>
  <?php if (!empty($pre['lead_id'])): ?><input type="hidden" name="lead_id" value="<?= (int) $pre['lead_id'] ?>"><?php endif; ?>
  <?php if (!empty($pre['rfq_id'])):  ?><input type="hidden" name="rfq_id"  value="<?= (int) $pre['rfq_id'] ?>"><?php  endif; ?>

  <!-- Hidden name fields for consignor/consignee (autofilled by JS from party <select>) -->
  <input type="hidden" id="consignor-name-hidden" name="consignor_name" value="<?= esc($v('consignor_name')) ?>">
  <input type="hidden" id="consignee-name-hidden" name="consignee_name" value="<?= esc($v('consignee_name')) ?>">

  <ul class="nav nav-tabs mb-0" id="bookingTabs" role="tablist">
    <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-basics"  type="button">1 · Basics</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-parties" type="button">2 · Consignor / Consignee</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-cargo"   type="button">3 · Cargo &amp; Docket</button></li>
  </ul>

  <div class="card" style="border-top-left-radius:0;">
    <div class="card-body">
      <div class="tab-content">

        <!-- ══════════ TAB 1 — Basics: who, where, when, freight ══════════ -->
        <div class="tab-pane fade show active" id="tab-basics" role="tabpanel">
          <div class="row g-3">
            <div class="col-md-6"><label class="form-label">Client <span class="text-danger">*</span></label>
              <select class="form-select" name="client_id" required>
                <option value="">— Select —</option>
                <?php foreach ($clients as $c): ?>
                  <option value="<?= $c['id'] ?>" <?= (int)$v('client_id') === (int)$c['id'] ? 'selected' : '' ?>><?= esc($c['company_name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6"><label class="form-label">Vendor</label>
              <select class="form-select" name="vendor_id">
                <option value="">— Select —</option>
                <?php foreach ($vendors as $vn): ?>
                  <option value="<?= $vn['id'] ?>" <?= (int)$v('vendor_id') === (int)$vn['id'] ? 'selected' : '' ?>><?= esc($vn['company_name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="col-md-3"><label class="form-label">Pickup City <span class="text-danger">*</span></label>
              <input class="form-control" name="pickup_city" data-tpt-city value="<?= esc($v('pickup_city')) ?>" placeholder="Start typing…" required autocomplete="off"></div>
            <div class="col-md-3"><label class="form-label">Drop City <span class="text-danger">*</span></label>
              <input class="form-control" name="drop_city" data-tpt-city value="<?= esc($v('drop_city')) ?>" placeholder="Start typing…" required autocomplete="off"></div>
            <div class="col-md-3"><label class="form-label">Loading Date</label>
              <input type="date" class="form-control" name="loading_date" value="<?= esc($v('loading_date')) ?>"></div>
            <div class="col-md-3"><label class="form-label">Vehicle Type</label>
              <input class="form-control" name="vehicle_type" value="<?= esc($v('vehicle_type')) ?>"></div>

            <div class="col-md-3"><label class="form-label">Charge Weight (kg) <span class="text-danger">*</span></label>
              <input type="number" step="0.01" min="0" class="form-control" name="charge_weight_kg" value="<?= esc($v('charge_weight_kg')) ?>" required></div>
            <div class="col-md-3"><label class="form-label">Freight Mode <span class="text-danger">*</span></label>
              <select class="form-select" name="freight_mode" required>
                <option value="">— Select —</option>
                <?php foreach (['To Pay','Paid','To Be Billed'] as $fm): ?>
                  <option value="<?= $fm ?>" <?= $v('freight_mode') === $fm ? 'selected' : '' ?>><?= $fm ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-3"><label class="form-label">Buy Rate (₹)</label>
              <input type="number" step="0.01" class="form-control" name="final_buy_rate" value="<?= esc($v('final_buy_rate', '')) ?>" placeholder="Pay vendor"></div>
            <div class="col-md-3"><label class="form-label">Sell Rate (₹)</label>
              <input type="number" step="0.01" class="form-control" name="final_sell_rate" value="<?= esc($v('final_sell_rate', '')) ?>" placeholder="Charge client"></div>

            <div class="col-md-6"><label class="form-label">Load Details</label>
              <input class="form-control" name="load_details" value="<?= esc($v('load_details')) ?>"></div>
            <div class="col-md-6"><label class="form-label">Billing Party</label>
              <input class="form-control" name="billing_party" value="<?= esc($v('billing_party')) ?>"></div>

            <div class="col-12"><label class="form-label">Instructions</label>
              <textarea class="form-control" name="instructions" rows="2"><?= esc($v('instructions')) ?></textarea></div>
          </div>
        </div>

        <!-- ══════════ TAB 2 — Consignor / Consignee ══════════ -->
        <div class="tab-pane fade" id="tab-parties" role="tabpanel">
          <div class="row g-3">
            <div class="col-12"><h6 class="mb-2 text-muted" style="font-size:.85rem;text-transform:uppercase;letter-spacing:.5px;">Consignor</h6></div>
            <div class="col-md-6"><label class="form-label">Consignor</label>
              <select class="form-select party-select" name="consignor_client_id" data-target="consignor">
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
            <div class="col-md-3"><label class="form-label">Mobile</label>
              <input class="form-control" id="consignor-mobile-view" name="consignor_mobile" value="<?= esc($v('consignor_mobile')) ?>" readonly></div>
            <div class="col-md-3"><label class="form-label">GSTIN</label>
              <input class="form-control" id="consignor-gstin-view" name="consignor_gstin" value="<?= esc($v('consignor_gstin')) ?>" readonly></div>
            <div class="col-md-9"><label class="form-label">Address</label>
              <input class="form-control" id="consignor-address-view" name="consignor_address" value="<?= esc($v('consignor_address')) ?>" readonly></div>
            <div class="col-md-3"><label class="form-label">State</label>
              <input class="form-control" id="consignor-state-view" name="consignor_state" value="<?= esc($v('consignor_state')) ?>" readonly></div>

            <div class="col-12"><hr class="my-1"></div>

            <div class="col-12"><h6 class="mb-2 text-muted" style="font-size:.85rem;text-transform:uppercase;letter-spacing:.5px;">Consignee</h6></div>
            <div class="col-md-6"><label class="form-label">Consignee</label>
              <select class="form-select party-select" name="consignee_client_id" data-target="consignee">
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
            <div class="col-md-3"><label class="form-label">Mobile</label>
              <input class="form-control" id="consignee-mobile-view" name="consignee_mobile" value="<?= esc($v('consignee_mobile')) ?>" readonly></div>
            <div class="col-md-3"><label class="form-label">GSTIN</label>
              <input class="form-control" id="consignee-gstin-view" name="consignee_gstin" value="<?= esc($v('consignee_gstin')) ?>" readonly></div>
            <div class="col-12"><label class="form-label">Address</label>
              <input class="form-control" id="consignee-address-view" name="consignee_address" value="<?= esc($v('consignee_address')) ?>" readonly></div>
          </div>
        </div>

        <!-- ══════════ TAB 3 — Cargo & Docket details for LR print ══════════ -->
        <div class="tab-pane fade" id="tab-cargo" role="tabpanel">
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label"><strong>Particulars</strong></label>
              <textarea class="form-control" name="particulars_text" rows="2" placeholder="e.g. Home appliances — 24× refrigerators, wooden-crated"><?= esc($v('particulars_text')) ?></textarea>
            </div>

            <div class="col-md-2"><label class="form-label">No. of Packages</label>
              <input type="number" min="0" class="form-control" name="packages_count" value="<?= esc($v('packages_count')) ?>"></div>
            <div class="col-md-4"><label class="form-label">Method of Packing</label>
              <input class="form-control" name="packing_method" value="<?= esc($v('packing_method')) ?>" placeholder="e.g. Wooden crate / Loose / Palletized"></div>
            <div class="col-md-3"><label class="form-label">Actual Weight (kg)</label>
              <input type="number" step="0.01" min="0" class="form-control" name="actual_weight_kg" value="<?= esc($v('actual_weight_kg')) ?>"></div>
            <div class="col-md-3"><label class="form-label">Person Liable for GST</label>
              <select class="form-select" name="person_liable_gst">
                <?php foreach (['','Consignor','Consignee','TCE'] as $opt): ?>
                  <option value="<?= esc($opt) ?>" <?= (string) $v('person_liable_gst') === $opt ? 'selected' : '' ?>><?= $opt === '' ? '—' : esc($opt) ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="col-md-2"><label class="form-label">Length (cm)</label>
              <input type="number" step="0.01" min="0" class="form-control" name="dim_length_cm" value="<?= esc($v('dim_length_cm')) ?>"></div>
            <div class="col-md-2"><label class="form-label">Width (cm)</label>
              <input type="number" step="0.01" min="0" class="form-control" name="dim_width_cm" value="<?= esc($v('dim_width_cm')) ?>"></div>
            <div class="col-md-2"><label class="form-label">Height (cm)</label>
              <input type="number" step="0.01" min="0" class="form-control" name="dim_height_cm" value="<?= esc($v('dim_height_cm')) ?>"></div>
            <div class="col-md-6"><label class="form-label">Cargo Value (₹)</label>
              <input type="number" step="0.01" min="0" class="form-control" name="cargo_value_inr" value="<?= esc($v('cargo_value_inr')) ?>"></div>

            <div class="col-12"><h6 class="mb-1 mt-2 text-muted" style="font-size:.82rem;text-transform:uppercase;letter-spacing:.5px;">Charges</h6></div>
            <div class="col-md-3"><label class="form-label">Additional Charges (₹)</label>
              <input type="number" step="0.01" min="0" class="form-control" name="additional_charges" value="<?= esc($v('additional_charges')) ?>"></div>
            <div class="col-md-3"><label class="form-label">Other Charges (₹)</label>
              <input type="number" step="0.01" min="0" class="form-control" name="other_charges" value="<?= esc($v('other_charges')) ?>"></div>
            <div class="col-md-3"><label class="form-label">GST Amount (₹)</label>
              <input type="number" step="0.01" min="0" class="form-control" name="gst_amount" value="<?= esc($v('gst_amount')) ?>"></div>
            <div class="col-md-3"><label class="form-label">Service Tax (₹)</label>
              <input type="number" step="0.01" min="0" class="form-control" name="service_tax_amount" value="<?= esc($v('service_tax_amount')) ?>"></div>

            <div class="col-12"><h6 class="mb-1 mt-2 text-muted" style="font-size:.82rem;text-transform:uppercase;letter-spacing:.5px;">References</h6></div>
            <div class="col-md-3"><label class="form-label">Driver Mobile</label>
              <input class="form-control" name="driver_mobile" value="<?= esc($v('driver_mobile')) ?>" placeholder="10-digit"></div>
            <div class="col-md-3"><label class="form-label">E-Way Bill No.</label>
              <input class="form-control" name="ewb_no" value="<?= esc($v('ewb_no')) ?>" placeholder="12-digit EWB"></div>
            <div class="col-md-3"><label class="form-label">Bill of Entry</label>
              <input class="form-control" name="bill_of_entry" value="<?= esc($v('bill_of_entry')) ?>"></div>
            <div class="col-md-3"><label class="form-label">B/L No.</label>
              <input class="form-control" name="bl_number" value="<?= esc($v('bl_number')) ?>"></div>
            <div class="col-md-3"><label class="form-label">Container No.</label>
              <input class="form-control" name="container_number" value="<?= esc($v('container_number')) ?>"></div>
            <div class="col-md-3"><label class="form-label">Seal No.</label>
              <input class="form-control" name="seal_number" value="<?= esc($v('seal_number')) ?>"></div>

            <div class="col-12"><h6 class="mb-1 mt-2 text-muted" style="font-size:.82rem;text-transform:uppercase;letter-spacing:.5px;">Shipper Invoices</h6>
              <table class="table table-sm mb-1 mt-2" id="shipperInvoiceTable" style="max-width:720px;">
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
                      <td><input class="form-control form-control-sm" name="shipper_invoices[<?= $idx ?>][no]" value="<?= esc($ri['no'] ?? '') ?>" placeholder="e.g. HO/LS01142"></td>
                      <td><input type="number" step="0.01" min="0" class="form-control form-control-sm si-value" name="shipper_invoices[<?= $idx ?>][value]" value="<?= esc($ri['value'] ?? '') ?>"></td>
                      <td><button type="button" class="btn btn-sm btn-outline-danger si-remove" title="Remove">&times;</button></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
              <button type="button" class="btn btn-sm btn-outline-primary" id="siAddRow"><i class="bi bi-plus-lg"></i> Add another invoice</button>
              <div class="text-muted mt-1" style="font-size:.8rem;">Total: ₹<span id="siTotal">0.00</span></div>
            </div>
          </div>
        </div>

      </div><!-- /.tab-content -->

      <div class="mt-3 d-flex gap-2 border-top pt-3">
        <button class="btn btn-primary" type="submit"><i class="bi bi-save"></i> Save</button>
        <a class="btn btn-light" href="<?= site_url('bookings') ?>">Cancel</a>
      </div>
    </div>
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
