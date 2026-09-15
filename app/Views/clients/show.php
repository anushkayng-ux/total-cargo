<?php
$r = $row;
$val = fn ($v, $empty = '') => ($v === null || $v === '') ? $empty : esc((string) $v);
?>
<?= tpt_toolbar([
    'new_href'       => site_url('clients/create'),
    'new_item_label' => 'New Client',
    'edit_href'      => site_url('clients/' . $r['id'] . '/edit'),
    'delete_href'    => site_url('clients/' . $r['id'] . '/delete'),
    'delete_confirm' => 'Delete this client? It can be restored later if needed.',
    'close_href'     => site_url('clients'),
    'auth'           => $auth,
]) ?>

<div class="tabs" role="tablist" id="clientTabs">
  <button type="button" class="tab active" data-bs-toggle="tab" data-bs-target="#tab-details">Client Details</button>
  <button type="button" class="tab" data-bs-toggle="tab" data-bs-target="#tab-credit">Credit &amp; Billing</button>
  <button type="button" class="tab" data-bs-toggle="tab" data-bs-target="#tab-kyc">KYC / Documents</button>
  <div class="spacer"></div>
  <div class="recordnav">
    <a href="<?= site_url('clients') ?>"><i class="bi bi-list"></i> List</a> &middot;
    Record <?= esc($r['client_code']) ?> &middot; <?= (int) $total ?> total
    <a class="<?= $prevId ? '' : 'disabled' ?>" href="<?= $prevId ? site_url('clients/' . $prevId) : '#' ?>">&#9664; Prev</a>
    <a class="<?= $nextId ? '' : 'disabled' ?>" href="<?= $nextId ? site_url('clients/' . $nextId) : '#' ?>">Next &#9654;</a>
  </div>
</div>

<div class="tab-content">
      <div class="tab-pane fade show active" id="tab-details">
        <div class="retro-detail">
          <div class="retro-detail-main formwrap">
            <div class="retro-row">
              <div class="retro-field"><label>Client Code :</label><div class="retro-box"><?= $val($r['client_code']) ?></div></div>
              <div class="retro-field" style="margin-left:auto;"><label>Status :</label><div class="retro-box wide"><?= (int) $r['status'] === 1 ? 'Active' : 'Inactive' ?></div></div>
            </div>
            <div class="retro-row">
              <div class="retro-field" style="width:100%;"><label>Company Name :</label><div class="retro-box xwide" style="min-width:400px;"><?= $val($r['company_name']) ?></div></div>
            </div>
            <div class="retro-row">
              <div class="retro-field"><label>Contact Person :</label><div class="retro-box wide"><?= $val($r['contact_name'], '—') ?></div></div>
              <div class="retro-field"><label>KYC Status :</label><div class="retro-box"><?= $val($r['kyc_status'], 'Pending') ?></div></div>
            </div>
            <div class="retro-row">
              <div class="retro-field"><label>Mobile :</label><div class="retro-box"><?= $val($r['mobile'], '—') ?></div></div>
              <div class="retro-field"><label>Alt. Mobile :</label><div class="retro-box empty"><?= $val($r['alt_mobile'], '—') ?></div></div>
              <div class="retro-field"><label>Email :</label><div class="retro-box wide empty"><?= $val($r['email'], '—') ?></div></div>
            </div>
            <div class="retro-row" style="align-items:flex-start;">
              <div class="retro-field" style="width:100%;"><label style="white-space:nowrap;">Address :</label>
                <div class="retro-particulars"><?= $val($r['address'], '—') ?></div>
              </div>
            </div>
            <div class="retro-row">
              <div class="retro-field"><label>City :</label><div class="retro-box"><?= $val($r['city'], '—') ?></div></div>
              <div class="retro-field"><label>State :</label><div class="retro-box wide"><?= $val($r['state'], '—') ?></div></div>
              <div class="retro-field"><label>Pincode :</label><div class="retro-box narrow"><?= $val($r['pincode'], '—') ?></div></div>
            </div>
            <div class="retro-row">
              <div class="retro-field"><label>Portal Access :</label><div class="retro-box narrow"><?= (int) ($r['portal_enabled'] ?? 0) === 1 ? 'Enabled' : 'Disabled' ?></div></div>
              <div class="retro-field"><label>Total Bookings :</label><div class="retro-box"><?= (int) $bookingCount ?></div></div>
            </div>
          </div>
          <div class="retro-detail-side">
            <h4>Portal Notes :</h4>
            <div class="remarksbox"><?= $val($r['portal_notes'], 'No notes recorded.') ?></div>
            <div class="scenic"><div class="sun"></div><div class="box1"></div><div class="box2"></div><div class="box3"></div></div>
          </div>
        </div>
      </div>

      <div class="tab-pane fade" id="tab-credit">
        <div class="formwrap">
        <div class="retro-row">
          <div class="retro-field"><label>GST No. :</label><div class="retro-box wide"><?= $val($r['gst_no'], '—') ?></div></div>
          <div class="retro-field"><label>PAN No. :</label><div class="retro-box"><?= $val($r['pan_no'], '—') ?></div></div>
        </div>
        <div class="retro-row">
          <div class="retro-field"><label>GST Treatment :</label><div class="retro-box"><?= esc(strtoupper((string) ($r['gst_treatment'] ?? ''))) ?></div></div>
          <div class="retro-field"><label>TDS Rate (%) :</label><div class="retro-box narrow"><?= $val($r['tds_rate'], '0') ?></div></div>
        </div>
        <div class="retro-row">
          <div class="retro-field"><label>Credit Limit :</label><div class="retro-box">₹<?= number_format((float) ($r['credit_limit'] ?? 0), 2) ?></div></div>
          <div class="retro-field"><label>Credit Days :</label><div class="retro-box narrow"><?= $val($r['credit_days'], '0') ?></div></div>
          <div class="retro-field"><label>MSME :</label><div class="retro-box narrow"><?= (int) ($r['is_msme'] ?? 0) === 1 ? 'Yes' : 'No' ?></div></div>
        </div>
        <div class="retro-row">
          <div class="retro-field"><label>Detention Free (Loading) hrs :</label><div class="retro-box narrow"><?= $val($r['detention_free_hours_loading'], '0') ?></div></div>
          <div class="retro-field"><label>Detention Free (Unloading) hrs :</label><div class="retro-box narrow"><?= $val($r['detention_free_hours_unloading'], '0') ?></div></div>
          <div class="retro-field"><label>Rate/hr :</label><div class="retro-box">₹<?= number_format((float) ($r['detention_rate_per_hour'] ?? 0), 2) ?></div></div>
        </div>
        </div>
      </div>

      <div class="tab-pane fade" id="tab-kyc">
        <div class="formwrap">
        <div class="retro-row">
          <div class="retro-field"><label>KYC Status :</label><div class="retro-box"><?= $val($r['kyc_status'], 'Pending') ?></div></div>
          <div class="retro-field"><label>Portal Access :</label><div class="retro-box narrow"><?= (int) ($r['portal_enabled'] ?? 0) === 1 ? 'Enabled' : 'Disabled' ?></div></div>
        </div>
        <div class="retro-row" style="align-items:flex-start;">
          <div class="retro-field" style="width:100%;"><label style="white-space:nowrap;">Portal Notes :</label>
            <div class="retro-particulars"><?= $val($r['portal_notes'], 'No notes recorded.') ?></div>
          </div>
        </div>
        </div>
      </div>
    </div>
