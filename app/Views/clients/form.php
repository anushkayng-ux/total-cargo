<?php
$isEdit = !empty($row);
$action = $isEdit ? site_url('clients/' . $row['id']) : site_url('clients/store');
$v = function ($k, $d = '') use ($row) { return old($k, $row[$k] ?? $d); };
?>
<?= tpt_toolbar([
    'save_form'   => 'clientForm',
    'delete_href' => $isEdit ? site_url('clients/' . $row['id'] . '/delete') : null,
    'close_href'  => site_url('clients'),
    'auth'        => $auth,
]) ?>

<div class="tabs" id="clientFormTabs">
  <button type="button" class="tab active" data-bs-toggle="tab" data-bs-target="#cf-details">Client Details</button>
  <button type="button" class="tab" data-bs-toggle="tab" data-bs-target="#cf-credit">Credit &amp; Billing</button>
  <button type="button" class="tab" data-bs-toggle="tab" data-bs-target="#cf-kyc">KYC / Documents</button>
  <div class="spacer"></div>
</div>

<form id="clientForm" method="post" action="<?= $action ?>">
  <?= csrf_field() ?>
  <div class="tab-content">

    <div class="tab-pane fade show active" id="cf-details">
      <div class="formwrap">
        <div class="retro-row">
          <div class="retro-field"><label>Client Code :</label><input class="retro-box" name="client_code" value="<?= esc($v('client_code')) ?>" placeholder="Auto if blank"></div>
          <div class="retro-field" style="margin-left:auto;"><label>Status :</label>
            <select class="retro-box wide" name="status">
              <option value="1" <?= (int) ($row['status'] ?? 1) === 1 ? 'selected' : '' ?>>Active</option>
              <option value="0" <?= (int) ($row['status'] ?? 1) === 0 ? 'selected' : '' ?>>Inactive</option>
            </select>
          </div>
        </div>
        <div class="retro-row">
          <div class="retro-field" style="width:100%;"><label>Company Name <span class="retro-required">*</span> :</label>
            <input class="retro-box xwide" style="min-width:400px;" name="company_name" required value="<?= esc($v('company_name')) ?>">
          </div>
        </div>
        <div class="retro-row">
          <div class="retro-field"><label>Contact Person :</label><input class="retro-box wide" name="contact_name" value="<?= esc($v('contact_name')) ?>"></div>
          <div class="retro-field"><label>KYC Status :</label>
            <select class="retro-box" name="kyc_status">
              <?php foreach (['Pending','Verified','Rejected'] as $k): ?>
                <option value="<?= $k ?>" <?= ($row['kyc_status'] ?? 'Pending') === $k ? 'selected' : '' ?>><?= $k ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="retro-row">
          <div class="retro-field"><label>Mobile :</label><input class="retro-box" name="mobile" value="<?= esc($v('mobile')) ?>"></div>
          <div class="retro-field"><label>Alt. Mobile :</label><input class="retro-box" name="alt_mobile" value="<?= esc($v('alt_mobile')) ?>"></div>
          <div class="retro-field"><label>Email :</label><input type="email" class="retro-box wide" name="email" value="<?= esc($v('email')) ?>"></div>
        </div>
        <div class="retro-row" style="align-items:flex-start;">
          <div class="retro-field" style="width:100%;"><label style="white-space:nowrap;">Address :</label>
            <textarea class="retro-particulars" name="address" rows="2"><?= esc($v('address')) ?></textarea>
          </div>
        </div>
        <div class="retro-row">
          <div class="retro-field"><label>City :</label><input class="retro-box" name="city" data-tpt-city value="<?= esc($v('city')) ?>"></div>
          <div class="retro-field"><label>State :</label><input class="retro-box wide" name="state" value="<?= esc($v('state')) ?>"></div>
          <div class="retro-field"><label>Pincode :</label><input class="retro-box narrow" name="pincode" value="<?= esc($v('pincode')) ?>"></div>
        </div>
        <div class="retro-row">
          <div class="retro-field"><label>Credit Limit :</label><input type="number" step="0.01" class="retro-box" name="credit_limit" value="<?= esc($v('credit_limit', '0')) ?>"></div>
          <div class="retro-field"><label>Credit Days :</label><input type="number" class="retro-box narrow" name="credit_days" value="<?= esc($v('credit_days', '0')) ?>"></div>
          <div class="retro-field"><label>Account Manager :</label>
            <select class="retro-box wide" name="account_manager_user_id">
              <option value="">— Unassigned —</option>
              <?php foreach (($managers ?? []) as $m): ?>
                <option value="<?= (int) $m['id'] ?>" <?= (int) ($v('account_manager_user_id') ?: 0) === (int) $m['id'] ? 'selected' : '' ?>><?= esc($m['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      </div>
    </div>

    <div class="tab-pane fade" id="cf-credit">
      <div class="formwrap">
        <div class="retro-row">
          <div class="retro-field"><label>GST No. :</label><input class="retro-box wide" name="gst_no" value="<?= esc($v('gst_no')) ?>"></div>
          <div class="retro-field"><label>PAN No. :</label><input class="retro-box" name="pan_no" value="<?= esc($v('pan_no')) ?>"></div>
        </div>
        <div class="retro-row">
          <div class="retro-field"><label>GST Treatment <span class="retro-required">*</span> :</label>
            <select class="retro-box wide" name="gst_treatment">
              <?php foreach (\App\Models\ClientModel::GST_TREATMENTS as $k => $label): ?>
                <option value="<?= $k ?>" <?= ($v('gst_treatment', 'fcm5') === $k) ? 'selected' : '' ?>><?= esc($label) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="retro-field"><label>TDS Rate (%) :</label><input class="retro-box narrow" type="number" step="0.01" name="tds_rate" value="<?= esc($v('tds_rate', '2')) ?>"></div>
        </div>
        <div class="retro-row">
          <div class="retro-field"><label>Detention Free (Loading) hrs :</label><input class="retro-box narrow" type="number" name="detention_free_hours_loading" value="<?= esc($v('detention_free_hours_loading', '4')) ?>"></div>
          <div class="retro-field"><label>Detention Free (Unloading) hrs :</label><input class="retro-box narrow" type="number" name="detention_free_hours_unloading" value="<?= esc($v('detention_free_hours_unloading', '4')) ?>"></div>
          <div class="retro-field"><label>Rate/hr :</label><input class="retro-box" type="number" step="0.01" name="detention_rate_per_hour" value="<?= esc($v('detention_rate_per_hour', '150')) ?>"></div>
        </div>
        <div class="retro-row">
          <label class="retro-checkline"><input type="checkbox" name="is_msme" value="1" <?= (int) ($row['is_msme'] ?? 0) === 1 ? 'checked' : '' ?>> MSME registered (45-day payment rule applies)</label>
        </div>
      </div>
    </div>

    <div class="tab-pane fade" id="cf-kyc">
      <div class="formwrap">
        <div class="retro-row">
          <label class="retro-checkline"><input type="checkbox" name="portal_enabled" value="1" <?= (int) ($row['portal_enabled'] ?? 0) === 1 ? 'checked' : '' ?>> Portal access enabled</label>
          <div class="retro-field" style="margin-left:auto;color:var(--v2-fg-muted);font-size:11.5px;">(KYC status is set on the Client Details tab)</div>
        </div>
        <div class="retro-row" style="align-items:flex-start;">
          <div class="retro-field" style="width:100%;"><label style="white-space:nowrap;">Portal Notes :</label>
            <textarea class="retro-particulars" name="portal_notes" placeholder="Visible to staff only"><?= esc($v('portal_notes')) ?></textarea>
          </div>
        </div>
        <?php if ($isEdit): ?>
        <div class="retro-row">
          <a href="<?= site_url('clients/' . $row['id'] . '/portal-users') ?>" class="retro-tbtn" style="width:auto;flex-direction:row;padding:6px 10px !important;"><i class="bi bi-people"></i> Manage portal users</a>
        </div>
        <?php endif; ?>
      </div>
    </div>

  </div>

  <div class="retro-toolbar mt-3" style="position:static;">
    <button type="submit" class="retro-tbtn retro-primary"><i class="bi bi-save-fill"></i>Save</button>
    <a class="retro-tbtn" href="<?= site_url('clients') ?>"><i class="bi bi-x-circle"></i>Close</a>
  </div>
</form>

