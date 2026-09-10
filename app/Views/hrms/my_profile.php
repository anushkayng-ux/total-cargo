<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
  <h5 class="m-0"><i class="bi bi-person-badge"></i> My Profile</h5>
  <?php if (!empty($profile['profile_completed'])): ?>
    <span class="badge bg-success ms-2"><i class="bi bi-check-circle-fill"></i> Profile complete</span>
  <?php else: ?>
    <span class="badge bg-warning text-dark ms-2"><i class="bi bi-exclamation-circle-fill"></i> Incomplete</span>
  <?php endif; ?>
</div>

<form method="post" action="<?= site_url('hrms/profile') ?>">
  <?= csrf_field() ?>

  <div class="card mb-3">
    <div class="card-header"><i class="bi bi-card-text"></i> Identity</div>
    <div class="card-body">
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Full name</label>
          <input class="form-control" type="text" value="<?= esc($user['name']) ?>" disabled>
          <div class="form-text">Managed by HR — contact admin to update.</div>
        </div>
        <div class="col-md-6">
          <label class="form-label">Work email</label>
          <input class="form-control" type="email" value="<?= esc($user['email']) ?>" disabled>
        </div>
        <div class="col-md-4">
          <label class="form-label">Date of birth</label>
          <input class="form-control" type="date" name="date_of_birth" value="<?= esc($profile['date_of_birth'] ?? '') ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label">Gender</label>
          <select class="form-select" name="gender">
            <option value="">—</option>
            <?php foreach (['Male','Female','Other'] as $g): ?>
              <option value="<?= $g ?>" <?= ($profile['gender'] ?? '') === $g ? 'selected' : '' ?>><?= $g ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label">Marital status</label>
          <select class="form-select" name="marital_status">
            <option value="">—</option>
            <?php foreach (['Single','Married','Divorced','Widowed'] as $g): ?>
              <option value="<?= $g ?>" <?= ($profile['marital_status'] ?? '') === $g ? 'selected' : '' ?>><?= $g ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">Blood group</label>
          <input class="form-control" type="text" name="blood_group" maxlength="5" value="<?= esc($profile['blood_group'] ?? '') ?>">
        </div>
      </div>
    </div>
  </div>

  <div class="card mb-3">
    <div class="card-header"><i class="bi bi-geo-alt"></i> Address &amp; emergency contact</div>
    <div class="card-body">
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Permanent address</label>
          <textarea class="form-control" rows="3" name="permanent_address"><?= esc($profile['permanent_address'] ?? '') ?></textarea>
        </div>
        <div class="col-md-6">
          <label class="form-label">Current address</label>
          <textarea class="form-control" rows="3" name="current_address"><?= esc($profile['current_address'] ?? '') ?></textarea>
        </div>
        <div class="col-md-6">
          <label class="form-label">Emergency contact name</label>
          <input class="form-control" type="text" name="emergency_contact_name" value="<?= esc($profile['emergency_contact_name'] ?? '') ?>">
        </div>
        <div class="col-md-6">
          <label class="form-label">Emergency contact phone</label>
          <input class="form-control" type="tel" name="emergency_contact_phone" maxlength="20" value="<?= esc($profile['emergency_contact_phone'] ?? '') ?>">
        </div>
      </div>
    </div>
  </div>

  <div class="card mb-3">
    <div class="card-header d-flex align-items-center">
      <i class="bi bi-people"></i> <span class="ms-1">Next of kin · family</span>
      <small class="text-muted ms-auto">Used in case of an emergency. Add as many as you like.</small>
    </div>
    <div class="card-body">
      <?= view('hrms/_kin_rows', ['kin' => $kin ?? []]) ?>
    </div>
  </div>

  <div class="card mb-3">
    <div class="card-header"><i class="bi bi-shield-lock"></i> Government IDs &amp; bank</div>
    <div class="card-body">
      <div class="row g-3">
        <div class="col-md-4">
          <label class="form-label">PAN</label>
          <input class="form-control" type="text" name="pan_no" maxlength="20" value="<?= esc($profile['pan_no'] ?? '') ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label">Aadhaar (last 4)</label>
          <input class="form-control" type="text" name="aadhaar_last_4" maxlength="4" value="<?= esc($profile['aadhaar_last_4'] ?? '') ?>">
          <div class="form-text" style="font-size:.78rem;">Last 4 digits only.</div>
        </div>
        <div class="col-md-4">
          <label class="form-label">UAN (PF)</label>
          <input class="form-control" type="text" name="uan_no" maxlength="20" value="<?= esc($profile['uan_no'] ?? '') ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label">Bank name</label>
          <input class="form-control" type="text" name="bank_name" value="<?= esc($profile['bank_name'] ?? '') ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label">Account number</label>
          <input class="form-control" type="text" name="bank_account_no" value="<?= esc($profile['bank_account_no'] ?? '') ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label">IFSC</label>
          <input class="form-control" type="text" name="bank_ifsc" maxlength="20" value="<?= esc($profile['bank_ifsc'] ?? '') ?>">
        </div>
      </div>
    </div>
  </div>

  <button class="btn btn-primary"><i class="bi bi-check2"></i> Save profile</button>
</form>
