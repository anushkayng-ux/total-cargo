<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
  <h5 class="m-0"><i class="bi bi-person-gear"></i> <?= esc($user['name']) ?></h5>
  <small class="text-muted">· <?= esc($user['email']) ?></small>
  <a class="btn btn-sm btn-light ms-auto" href="<?= site_url('hrms/team') ?>"><i class="bi bi-arrow-left"></i> Team</a>
</div>

<form method="post" action="<?= site_url('hrms/team/' . (int) $user['id']) ?>" class="mb-3">
  <?= csrf_field() ?>
  <div class="card">
    <div class="card-header"><i class="bi bi-card-text"></i> HR-managed fields</div>
    <div class="card-body">
      <div class="row g-3">
        <div class="col-md-3"><label class="form-label">Employee code</label><input class="form-control" name="employee_code" value="<?= esc($profile['employee_code'] ?? '') ?>"></div>
        <div class="col-md-3"><label class="form-label">Date of joining</label><input class="form-control" type="date" name="date_of_joining" value="<?= esc($profile['date_of_joining'] ?? '') ?>"></div>
        <div class="col-md-3"><label class="form-label">Designation</label><input class="form-control" name="designation" value="<?= esc($profile['designation'] ?? '') ?>"></div>
        <div class="col-md-3"><label class="form-label">Department</label><input class="form-control" name="department" value="<?= esc($profile['department'] ?? '') ?>"></div>
        <div class="col-md-3"><label class="form-label">Reporting manager (user ID)</label><input class="form-control" type="number" name="reporting_manager_id" value="<?= esc((string) ($profile['reporting_manager_id'] ?? '')) ?>"></div>
        <div class="col-md-3"><label class="form-label">Date of birth</label><input class="form-control" type="date" name="date_of_birth" value="<?= esc($profile['date_of_birth'] ?? '') ?>"></div>
        <div class="col-md-3"><label class="form-label">Gender</label>
          <select class="form-select" name="gender">
            <option value="">—</option>
            <?php foreach (['Male','Female','Other'] as $g): ?><option <?= ($profile['gender'] ?? '') === $g ? 'selected' : '' ?>><?= $g ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3"><label class="form-label">Blood group</label><input class="form-control" name="blood_group" maxlength="5" value="<?= esc($profile['blood_group'] ?? '') ?>"></div>
        <div class="col-md-12"><label class="form-label">Permanent address</label><textarea class="form-control" rows="2" name="permanent_address"><?= esc($profile['permanent_address'] ?? '') ?></textarea></div>
        <div class="col-md-12"><label class="form-label">Current address</label><textarea class="form-control" rows="2" name="current_address"><?= esc($profile['current_address'] ?? '') ?></textarea></div>
        <div class="col-md-4"><label class="form-label">Emergency contact name</label><input class="form-control" name="emergency_contact_name" value="<?= esc($profile['emergency_contact_name'] ?? '') ?>"></div>
        <div class="col-md-3"><label class="form-label">Emergency contact phone</label><input class="form-control" name="emergency_contact_phone" value="<?= esc($profile['emergency_contact_phone'] ?? '') ?>"></div>
        <div class="col-md-12">
          <hr class="my-2">
          <h6 class="text-muted text-uppercase" style="font-size:.74rem;letter-spacing:.06em;font-weight:600;margin:.6rem 0 .8rem;">
            <i class="bi bi-people"></i> Next of kin · family contacts
          </h6>
          <?= view('hrms/_kin_rows', ['kin' => $kin ?? []]) ?>
        </div>
        <div class="col-md-3"><label class="form-label">PAN</label><input class="form-control" name="pan_no" value="<?= esc($profile['pan_no'] ?? '') ?>"></div>
        <div class="col-md-2"><label class="form-label">Aadhaar L4</label><input class="form-control" name="aadhaar_last_4" maxlength="4" value="<?= esc($profile['aadhaar_last_4'] ?? '') ?>"></div>
        <div class="col-md-3"><label class="form-label">UAN</label><input class="form-control" name="uan_no" value="<?= esc($profile['uan_no'] ?? '') ?>"></div>
        <div class="col-md-3"><label class="form-label">Bank name</label><input class="form-control" name="bank_name" value="<?= esc($profile['bank_name'] ?? '') ?>"></div>
        <div class="col-md-3"><label class="form-label">Account no.</label><input class="form-control" name="bank_account_no" value="<?= esc($profile['bank_account_no'] ?? '') ?>"></div>
        <div class="col-md-3"><label class="form-label">IFSC</label><input class="form-control" name="bank_ifsc" value="<?= esc($profile['bank_ifsc'] ?? '') ?>"></div>
      </div>
    </div>
    <div class="card-footer"><button class="btn btn-primary"><i class="bi bi-check2"></i> Save HR fields</button></div>
  </div>
</form>

<form method="post" action="<?= site_url('hrms/team/' . (int) $user['id'] . '/salary') ?>" class="mb-3">
  <?= csrf_field() ?>
  <div class="card">
    <div class="card-header"><i class="bi bi-currency-rupee"></i> Salary structure (per month)</div>
    <div class="card-body">
      <div class="row g-3">
        <div class="col-md-3"><label class="form-label">Effective from</label><input class="form-control" type="date" name="effective_from" value="<?= esc($components['effective_from'] ?? date('Y-m-d')) ?>"></div>
        <div class="col-md-3"><label class="form-label">Basic ₹</label><input class="form-control" type="number" step="0.01" name="basic" value="<?= esc((string) ($components['basic'] ?? 0)) ?>"></div>
        <div class="col-md-3"><label class="form-label">HRA ₹</label><input class="form-control" type="number" step="0.01" name="hra" value="<?= esc((string) ($components['hra'] ?? 0)) ?>"></div>
        <div class="col-md-3"><label class="form-label">Special allowance ₹</label><input class="form-control" type="number" step="0.01" name="special_allowance" value="<?= esc((string) ($components['special_allowance'] ?? 0)) ?>"></div>
        <div class="col-md-3"><label class="form-label">Conveyance ₹</label><input class="form-control" type="number" step="0.01" name="conveyance" value="<?= esc((string) ($components['conveyance'] ?? 0)) ?>"></div>
        <div class="col-md-3"><label class="form-label">Medical ₹</label><input class="form-control" type="number" step="0.01" name="medical" value="<?= esc((string) ($components['medical'] ?? 0)) ?>"></div>
        <div class="col-md-3"><label class="form-label">Other earnings ₹</label><input class="form-control" type="number" step="0.01" name="other_earnings" value="<?= esc((string) ($components['other_earnings'] ?? 0)) ?>"></div>
        <div class="col-md-3"><label class="form-label">PF ₹</label><input class="form-control" type="number" step="0.01" name="pf_deduction" value="<?= esc((string) ($components['pf_deduction'] ?? 0)) ?>"></div>
        <div class="col-md-3"><label class="form-label">ESI ₹</label><input class="form-control" type="number" step="0.01" name="esi_deduction" value="<?= esc((string) ($components['esi_deduction'] ?? 0)) ?>"></div>
        <div class="col-md-3"><label class="form-label">PT ₹</label><input class="form-control" type="number" step="0.01" name="pt_deduction" value="<?= esc((string) ($components['pt_deduction'] ?? 0)) ?>"></div>
        <div class="col-md-3"><label class="form-label">TDS ₹</label><input class="form-control" type="number" step="0.01" name="tds_deduction" value="<?= esc((string) ($components['tds_deduction'] ?? 0)) ?>"></div>
        <div class="col-md-3"><label class="form-label">Other deductions ₹</label><input class="form-control" type="number" step="0.01" name="other_deductions" value="<?= esc((string) ($components['other_deductions'] ?? 0)) ?>"></div>
      </div>
    </div>
    <div class="card-footer">
      <button class="btn btn-primary"><i class="bi bi-check2"></i> Save salary structure</button>
      <small class="text-muted ms-2">Used for monthly payroll runs.</small>
    </div>
  </div>
</form>

<?php if (!empty($balances)): ?>
<div class="card">
  <div class="card-header"><i class="bi bi-pie-chart"></i> Leave balances · <?= date('Y') ?></div>
  <div class="table-responsive">
    <table class="table table-sm mb-0">
      <thead><tr><th>Type</th><th>Allocated</th><th>Used</th><th>Balance</th></tr></thead>
      <tbody>
        <?php foreach ($balances as $b): ?>
          <tr><td><?= esc($b['code']) ?> · <?= esc($b['name']) ?></td><td><?= esc((string) $b['allocated']) ?></td><td><?= esc((string) $b['used']) ?></td><td><strong><?= esc((string) $b['balance']) ?></strong></td></tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>
