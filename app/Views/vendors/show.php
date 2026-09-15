<?php
$val = fn ($v, $empty = '—') => ($v === null || $v === '') ? $empty : esc((string) $v);
$dropCities = []; $pickupCities = []; $pairs = [];
foreach ($routes as $r) {
    if (empty($r['pickup_city']) && !empty($r['drop_city']))       $dropCities[]   = $r['drop_city'];
    elseif (!empty($r['pickup_city']) && empty($r['drop_city']))   $pickupCities[] = $r['pickup_city'];
    elseif (!empty($r['pickup_city']) && !empty($r['drop_city']))  $pairs[] = $r;
}
?>
<?= tpt_toolbar([
    'new_href'       => site_url('vendors/create'),
    'new_item_label' => 'New Vendor',
    'edit_href'      => site_url('vendors/' . $row['id'] . '/edit'),
    'delete_href'    => site_url('vendors/' . $row['id'] . '/delete'),
    'delete_confirm' => 'Delete this vendor?',
    'close_href'     => site_url('vendors'),
    'auth'           => $auth,
]) ?>

<div class="tabs" id="vendorTabs">
  <button type="button" class="tab active" data-bs-toggle="tab" data-bs-target="#ven-details">Vendor Details</button>
  <button type="button" class="tab" data-bs-toggle="tab" data-bs-target="#ven-contacts">Contacts</button>
  <button type="button" class="tab" data-bs-toggle="tab" data-bs-target="#ven-bank">Bank &amp; Compliance</button>
  <button type="button" class="tab" data-bs-toggle="tab" data-bs-target="#ven-routes">Route Coverage</button>
  <div class="spacer"></div>
  <div class="recordnav">
    <a href="<?= site_url('vendors') ?>"><i class="bi bi-list"></i> List</a> &middot;
    Record <?= esc($row['vendor_code']) ?> &middot; <?= (int) $total ?> total
    <a class="<?= $prevId ? '' : 'disabled' ?>" href="<?= $prevId ? site_url('vendors/' . $prevId) : '#' ?>">&#9664; Prev</a>
    <a class="<?= $nextId ? '' : 'disabled' ?>" href="<?= $nextId ? site_url('vendors/' . $nextId) : '#' ?>">Next &#9654;</a>
  </div>
</div>

<div class="tab-content">
  <div class="tab-pane fade show active" id="ven-details">
    <div class="retro-detail">
      <div class="retro-detail-main formwrap">
        <div class="retro-row">
          <div class="retro-field"><label>Vendor Code :</label><div class="retro-box"><?= $val($row['vendor_code']) ?></div></div>
          <div class="retro-field" style="margin-left:auto;"><label>Status :</label><div class="retro-box wide"><?= (int) ($row['status'] ?? 1) === 1 ? 'Active' : 'Inactive' ?></div></div>
        </div>
        <div class="retro-row">
          <div class="retro-field" style="width:100%;"><label>Company Name :</label><div class="retro-box xwide" style="min-width:400px;"><?= $val($row['company_name']) ?></div></div>
        </div>
        <div class="retro-row">
          <div class="retro-field"><label>Owner :</label><div class="retro-box wide"><?= $val($row['owner_name'] ?? null) ?></div></div>
          <div class="retro-field"><label>Vendor Type :</label><div class="retro-box wide"><?= $val($row['vendor_type'] ?? null) ?></div></div>
        </div>
        <div class="retro-row">
          <div class="retro-field"><label>Mobile :</label><div class="retro-box"><?= $val($row['mobile']) ?></div></div>
          <div class="retro-field"><label>WhatsApp :</label><div class="retro-box empty"><?= $val($row['whatsapp_no'] ?? null) ?></div></div>
          <div class="retro-field"><label>Email :</label><div class="retro-box wide empty"><?= $val($row['email'] ?? null) ?></div></div>
        </div>
        <div class="retro-row" style="align-items:flex-start;">
          <div class="retro-field" style="width:100%;"><label style="white-space:nowrap;">Address :</label>
            <div class="retro-particulars"><?= $val($row['address'] ?? null) ?></div>
          </div>
        </div>
        <div class="retro-row">
          <div class="retro-field"><label>City :</label><div class="retro-box"><?= $val($row['city'] ?? null) ?></div></div>
          <div class="retro-field"><label>State :</label><div class="retro-box wide"><?= $val($row['state'] ?? null) ?></div></div>
          <div class="retro-field"><label>Pincode :</label><div class="retro-box narrow"><?= $val($row['pincode'] ?? null) ?></div></div>
        </div>
        <div class="retro-row">
          <div class="retro-field"><label>Rating :</label><div class="retro-box narrow"><?= $val($row['rating'] ?? null, '0') ?> / 5</div></div>
          <div class="retro-field"><label>Preferred :</label><div class="retro-box"><?= (int) ($row['is_preferred'] ?? 0) === 1 ? 'Yes' : 'No' ?></div></div>
          <div class="retro-field"><label>Blacklisted :</label><div class="retro-box"><?= (int) ($row['is_blacklisted'] ?? 0) === 1 ? 'Yes' : 'No' ?></div></div>
        </div>
      </div>
      <div class="retro-detail-side">
        <h4>Notes :</h4>
        <div class="remarksbox">No notes recorded.</div>
        <div class="scenic"><div class="sun"></div><div class="box1"></div><div class="box2"></div><div class="box3"></div></div>
      </div>
    </div>
  </div>

  <div class="tab-pane fade" id="ven-contacts">
    <div class="gridwrap" style="padding:18px 20px;">
      <?php if (empty($contacts)): ?>
        <div class="text-muted">No contacts recorded.</div>
      <?php else: ?>
        <table class="table grid mb-0">
          <thead><tr><th>Name</th><th>Designation</th><th>Mobile</th><th>Email</th><th>Primary</th></tr></thead>
          <tbody>
            <?php foreach ($contacts as $c): ?>
              <tr>
                <td><?= $val($c['contact_name']) ?></td>
                <td><?= $val($c['designation'] ?? null) ?></td>
                <td><a href="tel:<?= esc($c['mobile']) ?>"><?= $val($c['mobile']) ?></a></td>
                <td><?= $val($c['email'] ?? null) ?></td>
                <td><?= (int) ($c['is_primary'] ?? 0) === 1 ? '<span class="badge-soft badge-ok">Primary</span>' : '' ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
  </div>

  <div class="tab-pane fade" id="ven-bank">
    <div class="formwrap">
      <div class="retro-row">
        <div class="retro-field"><label>GSTIN :</label><div class="retro-box wide"><?= $val($row['gst_no'] ?? null) ?></div></div>
        <div class="retro-field"><label>PAN :</label><div class="retro-box"><?= $val($row['pan_no'] ?? null) ?></div></div>
      </div>
      <div class="retro-row">
        <div class="retro-field"><label>Bank Name :</label><div class="retro-box wide"><?= $val($row['bank_name'] ?? null) ?></div></div>
        <div class="retro-field"><label>Account No :</label><div class="retro-box wide"><?= $val($row['account_no'] ?? null) ?></div></div>
        <div class="retro-field"><label>IFSC :</label><div class="retro-box"><?= $val($row['ifsc_code'] ?? null) ?></div></div>
      </div>
    </div>
  </div>

  <div class="tab-pane fade" id="ven-routes">
    <div class="formwrap">
      <div class="retro-row" style="align-items:flex-start;">
        <div class="retro-field" style="width:100%;"><label style="white-space:nowrap;">Destination cities served :</label>
          <div class="retro-particulars"><?= $dropCities ? esc(implode(', ', $dropCities)) : 'Any' ?></div>
        </div>
      </div>
      <div class="retro-row" style="align-items:flex-start;">
        <div class="retro-field" style="width:100%;"><label style="white-space:nowrap;">Origin cities :</label>
          <div class="retro-particulars"><?= $pickupCities ? esc(implode(', ', $pickupCities)) : 'Any' ?></div>
        </div>
      </div>
      <?php if ($pairs): ?>
        <div class="retro-row" style="margin-top:10px;">
          <div class="retro-field" style="width:100%;"><label>Specific lanes :</label></div>
        </div>
        <?php foreach ($pairs as $p): ?>
          <div class="retro-row">
            <div class="retro-field"><div class="retro-box wide"><?= $val($p['pickup_city']) ?> &rarr; <?= $val($p['drop_city']) ?></div></div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</div>
