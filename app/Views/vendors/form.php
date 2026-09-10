<?php
$isEdit = !empty($row);
$action = $isEdit ? site_url('vendors/' . $row['id']) : site_url('vendors/store');
$v = function ($k, $d = '') use ($row) { return old($k, $row[$k] ?? $d); };
?>
<h5 class="mb-3"><?= esc($pageTitle) ?></h5>
<div class="card"><div class="card-body">
<form method="post" action="<?= $action ?>">
  <?= csrf_field() ?>
  <div class="row g-3">
    <div class="col-md-3"><label class="form-label">Vendor Code</label>
      <input class="form-control" name="vendor_code" value="<?= esc($v('vendor_code')) ?>" placeholder="Auto if blank"></div>
    <div class="col-md-6"><label class="form-label">Company Name <span class="text-danger">*</span></label>
      <input class="form-control" name="company_name" required value="<?= esc($v('company_name')) ?>"></div>
    <div class="col-md-3"><label class="form-label">Owner</label>
      <input class="form-control" name="owner_name" value="<?= esc($v('owner_name')) ?>"></div>

    <div class="col-md-3"><label class="form-label">Vendor Type</label>
      <input class="form-control" name="vendor_type" list="vendor-type-options"
             value="<?= esc($v('vendor_type')) ?>" placeholder="Broker / Fleet Owner / …">
      <datalist id="vendor-type-options">
        <option value="Broker">
        <option value="Fleet Owner">
        <option value="Broker/Fleet Owner">
        <option value="Transport Contractor">
        <option value="Commission Agent">
        <option value="Fleet Owner & Commission Agent">
      </datalist>
    </div>

    <div class="col-md-3"><label class="form-label">Mobile</label>
      <input class="form-control" name="mobile" value="<?= esc($v('mobile')) ?>"></div>
    <div class="col-md-3"><label class="form-label">Alt Mobile</label>
      <input class="form-control" name="alt_mobile" value="<?= esc($v('alt_mobile')) ?>"></div>
    <div class="col-md-3"><label class="form-label">WhatsApp</label>
      <input class="form-control" name="whatsapp_no" value="<?= esc($v('whatsapp_no')) ?>"></div>
    <div class="col-md-3"><label class="form-label">Email</label>
      <input type="email" class="form-control" name="email" value="<?= esc($v('email')) ?>"></div>

    <div class="col-12"><label class="form-label">Address</label>
      <textarea class="form-control" name="address" rows="2"><?= esc($v('address')) ?></textarea></div>

    <div class="col-md-4"><label class="form-label">City</label>
      <input class="form-control" name="city" data-tpt-city value="<?= esc($v('city')) ?>"></div>
    <div class="col-md-4"><label class="form-label">State</label>
      <input class="form-control" name="state" value="<?= esc($v('state')) ?>"></div>
    <div class="col-md-4"><label class="form-label">Pincode</label>
      <input class="form-control" name="pincode" value="<?= esc($v('pincode')) ?>"></div>

    <div class="col-md-4"><label class="form-label">GSTIN</label>
      <input class="form-control" name="gst_no" value="<?= esc($v('gst_no')) ?>"></div>
    <div class="col-md-4"><label class="form-label">PAN</label>
      <input class="form-control" name="pan_no" value="<?= esc($v('pan_no')) ?>"></div>
    <div class="col-md-4"><label class="form-label">Rating (0-5)</label>
      <input type="number" step="0.1" min="0" max="5" class="form-control" name="rating" value="<?= esc($v('rating', '0')) ?>"></div>

    <div class="col-md-4"><label class="form-label">Bank Name</label>
      <input class="form-control" name="bank_name" value="<?= esc($v('bank_name')) ?>"></div>
    <div class="col-md-4"><label class="form-label">Account No</label>
      <input class="form-control" name="account_no" value="<?= esc($v('account_no')) ?>"></div>
    <div class="col-md-4"><label class="form-label">IFSC</label>
      <input class="form-control" name="ifsc_code" value="<?= esc($v('ifsc_code')) ?>"></div>

    <div class="col-md-4">
      <label class="form-label d-block">Preferred</label>
      <div class="form-check form-switch">
        <input class="form-check-input" type="checkbox" name="is_preferred" value="1" id="pref" <?= (int)($row['is_preferred'] ?? 0) === 1 ? 'checked' : '' ?>>
        <label class="form-check-label" for="pref">Yes</label>
      </div>
    </div>
    <div class="col-md-4">
      <label class="form-label d-block">Blacklisted</label>
      <div class="form-check form-switch">
        <input class="form-check-input" type="checkbox" name="is_blacklisted" value="1" id="bl" <?= (int)($row['is_blacklisted'] ?? 0) === 1 ? 'checked' : '' ?>>
        <label class="form-check-label" for="bl">Yes</label>
      </div>
    </div>
    <div class="col-md-4">
      <label class="form-label d-block">Status</label>
      <div class="form-check form-switch">
        <input class="form-check-input" type="checkbox" name="status" value="1" id="s" <?= (int)($row['status'] ?? 1) === 1 ? 'checked' : '' ?>>
        <label class="form-check-label" for="s">Active</label>
      </div>
    </div>
  </div>

  <hr class="my-4">
  <h6 class="mb-2"><i class="bi bi-people"></i> Contact people</h6>
  <p class="text-muted" style="font-size:.85rem;">Add the owner, manager, accountant, dispatch, etc. <strong>Mobile is required and must be unique</strong> within this vendor's list. Email is optional. Mark exactly one as primary — that's the contact shown across the app.</p>

  <datalist id="designations">
    <?php foreach (\App\Models\VendorContactModel::COMMON_DESIGNATIONS as $d): ?>
      <option value="<?= esc($d) ?>"></option>
    <?php endforeach; ?>
  </datalist>

  <div id="vendor-contacts">
    <?php
      $rows2 = !empty($contacts) ? $contacts : [['contact_name'=>'','designation'=>'','mobile'=>'','email'=>'','is_primary'=>1]];
      foreach ($rows2 as $i => $c):
    ?>
      <div class="row g-2 mb-2 contact-row align-items-end">
        <div class="col-md-3"><label class="form-label">Name *</label>
          <input class="form-control form-control-sm" name="contact_name[]" value="<?= esc($c['contact_name']) ?>"></div>
        <div class="col-md-2"><label class="form-label">Designation</label>
          <input class="form-control form-control-sm" name="contact_designation[]" list="designations" value="<?= esc($c['designation'] ?? '') ?>" placeholder="Owner / Manager / …"></div>
        <div class="col-md-2"><label class="form-label">Mobile *</label>
          <input class="form-control form-control-sm" name="contact_mobile[]" inputmode="tel" pattern="[0-9+\- ]{7,20}" value="<?= esc($c['mobile']) ?>"></div>
        <div class="col-md-3"><label class="form-label">Email</label>
          <input type="email" class="form-control form-control-sm" name="contact_email[]" value="<?= esc($c['email'] ?? '') ?>"></div>
        <div class="col-md-1 text-center">
          <label class="form-label d-block">Primary</label>
          <input type="radio" name="contact_primary" value="<?= $i ?>" <?= !empty($c['is_primary']) ? 'checked' : '' ?>>
        </div>
        <div class="col-md-1 text-end">
          <button type="button" class="btn btn-sm btn-light" onclick="this.closest('.contact-row').remove();" aria-label="Remove"><i class="bi bi-trash"></i></button>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
  <button type="button" class="btn btn-sm btn-outline-dark" onclick="addContact()"><i class="bi bi-plus-lg"></i> Add another contact</button>
  <script>
    function addContact() {
      var src = document.querySelector('#vendor-contacts .contact-row');
      if (!src) return;
      var clone = src.cloneNode(true);
      clone.querySelectorAll('input').forEach(function (i) {
        if (i.type === 'radio') i.checked = false;
        else i.value = '';
      });
      // Renumber the radio button so each row's "primary" toggle has a unique value
      var idx = document.querySelectorAll('#vendor-contacts .contact-row').length;
      var radio = clone.querySelector('input[type=radio]');
      if (radio) radio.value = idx;
      document.getElementById('vendor-contacts').appendChild(clone);
    }
  </script>

  <!-- ─────────────────────────── Route coverage ─────────────────────────── -->
  <?php if ($isEdit): ?>
    <hr class="my-4">
    <h5 class="mb-2"><i class="bi bi-geo-alt"></i> Route coverage <small class="text-muted" style="font-size:.78rem;font-weight:400;">— vendor is only suggested for RFQs whose destination is listed below</small></h5>

    <div class="row g-3 mt-1">
      <div class="col-md-6">
        <label class="form-label">Destination cities served</label>
        <input class="form-control" type="text" name="route_drop_cities" data-tpt-city="multi"
               value="<?= esc(implode(', ', $dropCities ?? [])) ?>"
               placeholder="e.g. Mumbai, Chennai, Bangalore">
        <div class="form-text" style="font-size:.78rem;">Comma-separated. Vendor appears for RFQs going TO any of these cities.</div>
      </div>
      <div class="col-md-6">
        <label class="form-label">Origin cities (optional)</label>
        <input class="form-control" type="text" name="route_pickup_cities" data-tpt-city="multi"
               value="<?= esc(implode(', ', $pickupCities ?? [])) ?>"
               placeholder="e.g. Pune, Delhi (leave blank if vendor pickups anywhere)">
        <div class="form-text" style="font-size:.78rem;">Restrict to specific origins, or leave blank for "any origin".</div>
      </div>
    </div>

    <details class="mt-3">
      <summary style="cursor:pointer;color:#374151;font-size:.88rem;">
        Advanced — specific pickup → drop lanes (<?= count($pairs ?? []) ?>)
      </summary>
      <div class="mt-2" id="route-pairs">
        <?php foreach (($pairs ?? []) as $i => $p): ?>
          <div class="route-pair-row d-flex gap-2 mb-2 align-items-center">
            <input class="form-control form-control-sm" type="text" name="route_pairs[<?= $i ?>][pickup_city]" data-tpt-city value="<?= esc($p['pickup_city']) ?>" placeholder="Pickup city">
            <i class="bi bi-arrow-right text-muted"></i>
            <input class="form-control form-control-sm" type="text" name="route_pairs[<?= $i ?>][drop_city]" data-tpt-city   value="<?= esc($p['drop_city']) ?>"   placeholder="Drop city">
            <button type="button" class="btn btn-sm btn-light text-danger" onclick="this.closest('.route-pair-row').remove()"><i class="bi bi-trash"></i></button>
          </div>
        <?php endforeach; ?>
      </div>
      <button type="button" class="btn btn-sm btn-outline-dark" id="addRoutePair"><i class="bi bi-plus-lg"></i> Add lane</button>
      <small class="text-muted d-block mt-2" style="font-size:.78rem;">Use this only when a vendor serves a specific pickup→drop combination but NOT either city on its own.</small>
      <script>
        document.getElementById('addRoutePair')?.addEventListener('click', function () {
          const c = document.getElementById('route-pairs');
          const i = c.querySelectorAll('.route-pair-row').length;
          const row = document.createElement('div');
          row.className = 'route-pair-row d-flex gap-2 mb-2 align-items-center';
          row.innerHTML =
            '<input class="form-control form-control-sm" type="text" data-tpt-city name="route_pairs[' + i + '][pickup_city]" placeholder="Pickup city">' +
            '<i class="bi bi-arrow-right text-muted"></i>' +
            '<input class="form-control form-control-sm" type="text" data-tpt-city name="route_pairs[' + i + '][drop_city]" placeholder="Drop city">' +
            '<button type="button" class="btn btn-sm btn-light text-danger" onclick="this.closest(\'.route-pair-row\').remove()"><i class="bi bi-trash"></i></button>';
          c.appendChild(row);
          if (window.tptCityScan) window.tptCityScan();
        });
      </script>
    </details>
  <?php endif; ?>

  <div class="mt-4 d-flex gap-2">
    <button class="btn btn-primary" type="submit">Save</button>
    <a class="btn btn-light" href="<?= site_url('vendors') ?>">Cancel</a>
  </div>
</form>
</div></div>
