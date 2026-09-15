<?= tpt_toolbar([
    'save_form'  => 'profileForm',
    'close_href' => site_url('dashboard'),
    'auth'       => $auth,
]) ?>

<div class="tabs" id="profileTabs">
  <button type="button" class="tab active" data-bs-toggle="tab" data-bs-target="#prof-personal">Personal Details</button>
  <button type="button" class="tab" data-bs-toggle="tab" data-bs-target="#prof-kin">Next of Kin</button>
  <div class="spacer"></div>
  <div class="recordnav">
    <?php if (!empty($profile['profile_completed'])): ?>
      <span class="badge-soft badge-ok"><i class="bi bi-check-circle-fill"></i> Profile complete</span>
    <?php else: ?>
      <span class="badge-soft badge-warn"><i class="bi bi-exclamation-circle-fill"></i> Incomplete</span>
    <?php endif; ?>
  </div>
</div>

<form id="profileForm" method="post" action="<?= site_url('hrms/profile') ?>">
  <?= csrf_field() ?>
  <div class="tab-content">

    <div class="tab-pane fade show active" id="prof-personal">
      <div class="formwrap">
        <div class="retro-row">
          <div class="retro-field"><label>Full Name :</label><div class="retro-box wide empty"><?= esc($user['name']) ?></div></div>
          <div class="retro-field"><label>Work Email :</label><div class="retro-box wide empty"><?= esc($user['email']) ?></div></div>
        </div>
        <div class="retro-row" style="margin-top:-6px;">
          <div class="retro-field" style="color:var(--v2-fg-muted);font-size:11.5px;">Name and email are managed by HR — contact admin to update.</div>
        </div>

        <div class="retro-row">
          <div class="retro-field"><label>Date of Birth :</label><input type="date" class="retro-box" name="date_of_birth" value="<?= esc($profile['date_of_birth'] ?? '') ?>"></div>
          <div class="retro-field"><label>Gender :</label>
            <select class="retro-box" name="gender">
              <option value="">—</option>
              <?php foreach (['Male','Female','Other'] as $g): ?>
                <option value="<?= $g ?>" <?= ($profile['gender'] ?? '') === $g ? 'selected' : '' ?>><?= $g ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="retro-field"><label>Marital Status :</label>
            <select class="retro-box" name="marital_status">
              <option value="">—</option>
              <?php foreach (['Single','Married','Divorced','Widowed'] as $g): ?>
                <option value="<?= $g ?>" <?= ($profile['marital_status'] ?? '') === $g ? 'selected' : '' ?>><?= $g ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="retro-field"><label>Blood Group :</label><input class="retro-box narrow" type="text" name="blood_group" maxlength="5" value="<?= esc($profile['blood_group'] ?? '') ?>"></div>
        </div>

        <div class="retro-row" style="align-items:flex-start;">
          <div class="retro-field" style="width:48%;"><label style="white-space:nowrap;">Permanent Address :</label>
            <textarea class="retro-box retro-particulars" style="width:100%;" rows="3" name="permanent_address"><?= esc($profile['permanent_address'] ?? '') ?></textarea>
          </div>
          <div class="retro-field" style="width:48%;margin-left:auto;"><label style="white-space:nowrap;">Current Address :</label>
            <textarea class="retro-box retro-particulars" style="width:100%;" rows="3" name="current_address"><?= esc($profile['current_address'] ?? '') ?></textarea>
          </div>
        </div>

        <div class="retro-row">
          <div class="retro-field"><label>Emergency Contact Name :</label><input class="retro-box wide" type="text" name="emergency_contact_name" value="<?= esc($profile['emergency_contact_name'] ?? '') ?>"></div>
          <div class="retro-field"><label>Emergency Contact Phone :</label><input class="retro-box wide" type="tel" name="emergency_contact_phone" maxlength="20" value="<?= esc($profile['emergency_contact_phone'] ?? '') ?>"></div>
        </div>

        <div class="retro-row">
          <div class="retro-field"><label>PAN :</label><input class="retro-box wide" type="text" name="pan_no" maxlength="20" value="<?= esc($profile['pan_no'] ?? '') ?>"></div>
          <div class="retro-field"><label>Aadhaar (last 4) :</label><input class="retro-box narrow" type="text" name="aadhaar_last_4" maxlength="4" value="<?= esc($profile['aadhaar_last_4'] ?? '') ?>"></div>
          <div class="retro-field"><label>UAN (PF) :</label><input class="retro-box wide" type="text" name="uan_no" maxlength="20" value="<?= esc($profile['uan_no'] ?? '') ?>"></div>
        </div>
        <div class="retro-row">
          <div class="retro-field"><label>Bank Name :</label><input class="retro-box wide" type="text" name="bank_name" value="<?= esc($profile['bank_name'] ?? '') ?>"></div>
          <div class="retro-field"><label>Account Number :</label><input class="retro-box wide" type="text" name="bank_account_no" value="<?= esc($profile['bank_account_no'] ?? '') ?>"></div>
          <div class="retro-field"><label>IFSC :</label><input class="retro-box" type="text" name="bank_ifsc" maxlength="20" value="<?= esc($profile['bank_ifsc'] ?? '') ?>"></div>
        </div>
      </div>
    </div>

    <div class="tab-pane fade" id="prof-kin">
      <div class="formwrap">
        <div class="retro-row" style="margin-bottom:14px;">
          <div class="retro-field" style="color:var(--v2-fg-muted);font-size:11.5px;max-width:640px;">Used in case of an emergency. Add as many family contacts as you like.</div>
        </div>
        <?= view('hrms/_kin_rows', ['kin' => $kin ?? []]) ?>
      </div>
    </div>

  </div>

  <div class="retro-toolbar mt-3" style="position:static;">
    <button type="submit" class="retro-tbtn retro-primary"><i class="bi bi-save-fill"></i>Save Profile</button>
  </div>
</form>
