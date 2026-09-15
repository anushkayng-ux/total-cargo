<?php
$isEdit = !empty($row);
$action = $isEdit ? site_url('vendors/' . $row['id']) : site_url('vendors/store');
$v = function ($k, $d = '') use ($row) { return old($k, $row[$k] ?? $d); };
?>
<?= tpt_toolbar([
    'save_form'      => 'vendorForm',
    'delete_href'    => $isEdit ? site_url('vendors/' . $row['id'] . '/delete') : null,
    'delete_confirm' => 'Delete this vendor?',
    'close_href'     => site_url('vendors'),
    'auth'           => $auth,
]) ?>

<div class="tabs" id="vendorFormTabs">
  <button type="button" class="tab active" data-bs-toggle="tab" data-bs-target="#venf-details">Vendor Details</button>
  <button type="button" class="tab" data-bs-toggle="tab" data-bs-target="#venf-contacts">Contacts</button>
  <button type="button" class="tab" data-bs-toggle="tab" data-bs-target="#venf-bank">Bank &amp; Compliance</button>
  <?php if ($isEdit): ?>
    <button type="button" class="tab" data-bs-toggle="tab" data-bs-target="#venf-routes">Route Coverage</button>
  <?php endif; ?>
  <div class="spacer"></div>
</div>

<form id="vendorForm" method="post" action="<?= $action ?>">
  <?= csrf_field() ?>
  <div class="tab-content">

    <div class="tab-pane fade show active" id="venf-details">
      <div class="formwrap">
        <div class="retro-row">
          <div class="retro-field"><label>Vendor Code :</label><input class="retro-box" name="vendor_code" value="<?= esc($v('vendor_code')) ?>" placeholder="Auto if blank"></div>
          <div class="retro-field" style="margin-left:auto;">
            <label class="retro-checkline"><input type="checkbox" name="status" value="1" <?= (int) ($row['status'] ?? 1) === 1 ? 'checked' : '' ?>> Active</label>
          </div>
        </div>
        <div class="retro-row">
          <div class="retro-field" style="width:100%;"><label>Company Name <span class="retro-required">*</span> :</label><input class="retro-box xwide" style="min-width:400px;" name="company_name" required value="<?= esc($v('company_name')) ?>"></div>
        </div>
        <div class="retro-row">
          <div class="retro-field"><label>Owner :</label><input class="retro-box wide" name="owner_name" value="<?= esc($v('owner_name')) ?>"></div>
          <div class="retro-field"><label>Vendor Type :</label>
            <input class="retro-box wide" name="vendor_type" list="vendor-type-options" value="<?= esc($v('vendor_type')) ?>" placeholder="Broker / Fleet Owner / …">
            <datalist id="vendor-type-options">
              <option value="Broker">
              <option value="Fleet Owner">
              <option value="Broker/Fleet Owner">
              <option value="Transport Contractor">
              <option value="Commission Agent">
              <option value="Fleet Owner & Commission Agent">
            </datalist>
          </div>
        </div>
        <div class="retro-row">
          <div class="retro-field"><label>Mobile :</label><input class="retro-box" name="mobile" value="<?= esc($v('mobile')) ?>"></div>
          <div class="retro-field"><label>Alt Mobile :</label><input class="retro-box" name="alt_mobile" value="<?= esc($v('alt_mobile')) ?>"></div>
          <div class="retro-field"><label>WhatsApp :</label><input class="retro-box" name="whatsapp_no" value="<?= esc($v('whatsapp_no')) ?>"></div>
          <div class="retro-field"><label>Email :</label><input type="email" class="retro-box wide" name="email" value="<?= esc($v('email')) ?>"></div>
        </div>
        <div class="retro-row" style="align-items:flex-start;">
          <div class="retro-field" style="width:100%;"><label style="white-space:nowrap;">Address :</label>
            <textarea class="retro-box retro-particulars" name="address" rows="2" style="width:100%;"><?= esc($v('address')) ?></textarea>
          </div>
        </div>
        <div class="retro-row">
          <div class="retro-field"><label>City :</label><input class="retro-box" name="city" data-tpt-city value="<?= esc($v('city')) ?>"></div>
          <div class="retro-field"><label>State :</label><input class="retro-box wide" name="state" value="<?= esc($v('state')) ?>"></div>
          <div class="retro-field"><label>Pincode :</label><input class="retro-box narrow" name="pincode" value="<?= esc($v('pincode')) ?>"></div>
        </div>
        <div class="retro-row">
          <div class="retro-field"><label>Rating (0-5) :</label><input type="number" step="0.1" min="0" max="5" class="retro-box narrow" name="rating" value="<?= esc($v('rating', '0')) ?>"></div>
          <label class="retro-checkline"><input type="checkbox" name="is_preferred" value="1" <?= (int) ($row['is_preferred'] ?? 0) === 1 ? 'checked' : '' ?>> Preferred</label>
          <label class="retro-checkline"><input type="checkbox" name="is_blacklisted" value="1" <?= (int) ($row['is_blacklisted'] ?? 0) === 1 ? 'checked' : '' ?>> Blacklisted</label>
        </div>
      </div>
    </div>

    <div class="tab-pane fade" id="venf-contacts">
      <div class="formwrap">
        <div class="retro-row" style="margin-bottom:14px;">
          <div class="retro-field" style="color:var(--v2-fg-muted);font-size:11.5px;max-width:640px;">Add the owner, manager, accountant, dispatch, etc. Mobile is required and must be unique within this vendor's list. Email is optional. Mark exactly one as primary — that's the contact shown across the app.</div>
        </div>

        <datalist id="designations">
          <?php foreach (\App\Models\VendorContactModel::COMMON_DESIGNATIONS as $d): ?>
            <option value="<?= esc($d) ?>"></option>
          <?php endforeach; ?>
        </datalist>

        <div class="gridwrap" style="padding:0;">
          <table class="table grid mb-0">
            <thead><tr><th>Name *</th><th>Designation</th><th>Mobile *</th><th>Email</th><th style="width:70px;">Primary</th><th style="width:50px;"></th></tr></thead>
            <tbody id="vendor-contacts">
              <?php
                $rows2 = !empty($contacts) ? $contacts : [['contact_name'=>'','designation'=>'','mobile'=>'','email'=>'','is_primary'=>1]];
                foreach ($rows2 as $i => $c):
              ?>
                <tr class="contact-row">
                  <td><input class="retro-box" style="width:100%;" name="contact_name[]" value="<?= esc($c['contact_name']) ?>"></td>
                  <td><input class="retro-box" style="width:100%;" name="contact_designation[]" list="designations" value="<?= esc($c['designation'] ?? '') ?>" placeholder="Owner / Manager / …"></td>
                  <td><input class="retro-box" style="width:100%;" name="contact_mobile[]" inputmode="tel" pattern="[0-9+\- ]{7,20}" value="<?= esc($c['mobile']) ?>"></td>
                  <td><input type="email" class="retro-box" style="width:100%;" name="contact_email[]" value="<?= esc($c['email'] ?? '') ?>"></td>
                  <td class="text-center"><input type="radio" name="contact_primary" value="<?= $i ?>" <?= !empty($c['is_primary']) ? 'checked' : '' ?>></td>
                  <td class="text-center"><button type="button" class="retro-tbtn" style="width:auto;flex-direction:row;padding:2px 6px;" onclick="this.closest('.contact-row').remove();" aria-label="Remove"><i class="bi bi-trash"></i></button></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <div class="retro-row" style="margin-top:10px;">
          <button type="button" class="retro-tbtn" style="width:auto;flex-direction:row;" onclick="addContact()"><i class="bi bi-plus-lg"></i>Add another contact</button>
        </div>
        <script>
          function addContact() {
            var src = document.querySelector('#vendor-contacts .contact-row');
            if (!src) return;
            var clone = src.cloneNode(true);
            clone.querySelectorAll('input').forEach(function (i) {
              if (i.type === 'radio') i.checked = false;
              else i.value = '';
            });
            var idx = document.querySelectorAll('#vendor-contacts .contact-row').length;
            var radio = clone.querySelector('input[type=radio]');
            if (radio) radio.value = idx;
            document.getElementById('vendor-contacts').appendChild(clone);
          }
        </script>
      </div>
    </div>

    <div class="tab-pane fade" id="venf-bank">
      <div class="formwrap">
        <div class="retro-row">
          <div class="retro-field"><label>GSTIN :</label><input class="retro-box wide" name="gst_no" value="<?= esc($v('gst_no')) ?>"></div>
          <div class="retro-field"><label>PAN :</label><input class="retro-box" name="pan_no" value="<?= esc($v('pan_no')) ?>"></div>
        </div>
        <div class="retro-row">
          <div class="retro-field"><label>Bank Name :</label><input class="retro-box wide" name="bank_name" value="<?= esc($v('bank_name')) ?>"></div>
          <div class="retro-field"><label>Account No :</label><input class="retro-box wide" name="account_no" value="<?= esc($v('account_no')) ?>"></div>
          <div class="retro-field"><label>IFSC :</label><input class="retro-box" name="ifsc_code" value="<?= esc($v('ifsc_code')) ?>"></div>
        </div>
      </div>
    </div>

    <?php if ($isEdit): ?>
    <div class="tab-pane fade" id="venf-routes">
      <div class="formwrap">
        <div class="retro-row" style="margin-bottom:14px;">
          <div class="retro-field" style="color:var(--v2-fg-muted);font-size:11.5px;max-width:640px;">Vendor is only suggested for RFQs whose destination is listed below.</div>
        </div>
        <div class="retro-row" style="align-items:flex-start;">
          <div class="retro-field" style="width:48%;"><label style="white-space:nowrap;">Destination cities served :</label>
            <input class="retro-box" style="width:100%;" type="text" name="route_drop_cities" data-tpt-city="multi"
                   value="<?= esc(implode(', ', $dropCities ?? [])) ?>"
                   placeholder="e.g. Mumbai, Chennai, Bangalore">
            <div style="font-size:11px;color:var(--v2-fg-muted);margin-top:3px;">Comma-separated. Vendor appears for RFQs going TO any of these cities.</div>
          </div>
          <div class="retro-field" style="width:48%;margin-left:auto;"><label style="white-space:nowrap;">Origin cities (optional) :</label>
            <input class="retro-box" style="width:100%;" type="text" name="route_pickup_cities" data-tpt-city="multi"
                   value="<?= esc(implode(', ', $pickupCities ?? [])) ?>"
                   placeholder="e.g. Pune, Delhi (leave blank if vendor pickups anywhere)">
            <div style="font-size:11px;color:var(--v2-fg-muted);margin-top:3px;">Restrict to specific origins, or leave blank for "any origin".</div>
          </div>
        </div>

        <details class="mt-3">
          <summary style="cursor:pointer;color:#374151;font-size:.88rem;">
            Advanced — specific pickup &rarr; drop lanes (<?= count($pairs ?? []) ?>)
          </summary>
          <div class="mt-2" id="route-pairs">
            <?php foreach (($pairs ?? []) as $i => $p): ?>
              <div class="route-pair-row retro-row" style="align-items:center;">
                <input class="retro-box" type="text" name="route_pairs[<?= $i ?>][pickup_city]" data-tpt-city value="<?= esc($p['pickup_city']) ?>" placeholder="Pickup city">
                <i class="bi bi-arrow-right text-muted"></i>
                <input class="retro-box" type="text" name="route_pairs[<?= $i ?>][drop_city]" data-tpt-city   value="<?= esc($p['drop_city']) ?>"   placeholder="Drop city">
                <button type="button" class="retro-tbtn" style="width:auto;flex-direction:row;" onclick="this.closest('.route-pair-row').remove()"><i class="bi bi-trash"></i></button>
              </div>
            <?php endforeach; ?>
          </div>
          <button type="button" class="retro-tbtn mt-1" style="width:auto;flex-direction:row;" id="addRoutePair"><i class="bi bi-plus-lg"></i>Add lane</button>
          <small class="text-muted d-block mt-2" style="font-size:.78rem;">Use this only when a vendor serves a specific pickup→drop combination but NOT either city on its own.</small>
          <script>
            document.getElementById('addRoutePair')?.addEventListener('click', function () {
              const c = document.getElementById('route-pairs');
              const i = c.querySelectorAll('.route-pair-row').length;
              const row = document.createElement('div');
              row.className = 'route-pair-row retro-row';
              row.style.alignItems = 'center';
              row.innerHTML =
                '<input class="retro-box" type="text" data-tpt-city name="route_pairs[' + i + '][pickup_city]" placeholder="Pickup city">' +
                '<i class="bi bi-arrow-right text-muted"></i>' +
                '<input class="retro-box" type="text" data-tpt-city name="route_pairs[' + i + '][drop_city]" placeholder="Drop city">' +
                '<button type="button" class="retro-tbtn" style="width:auto;flex-direction:row;" onclick="this.closest(\'.route-pair-row\').remove()"><i class="bi bi-trash"></i></button>';
              c.appendChild(row);
              if (window.tptCityScan) window.tptCityScan();
            });
          </script>
        </details>
      </div>
    </div>
    <?php endif; ?>

  </div>

  <div class="retro-toolbar mt-3" style="position:static;">
    <button type="submit" class="retro-tbtn retro-primary"><i class="bi bi-save-fill"></i>Save</button>
    <a class="retro-tbtn" href="<?= site_url('vendors') ?>"><i class="bi bi-x-circle"></i>Close</a>
  </div>
</form>
