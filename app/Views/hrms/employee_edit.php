<?= tpt_toolbar([
    'close_href'  => site_url('hrms/team'),
    'auth'        => $auth,
]) ?>

<div class="tabs" role="tablist" id="empTabs">
  <button type="button" class="tab active" data-bs-toggle="tab" data-bs-target="#emp-details">Employee Details</button>
  <button type="button" class="tab" data-bs-toggle="tab" data-bs-target="#emp-salary">Salary Structure</button>
  <button type="button" class="tab" data-bs-toggle="tab" data-bs-target="#emp-leaves">Leave Balances</button>
  <div class="spacer"></div>
  <div class="recordnav"><?= esc($user['name']) ?> &middot; <?= esc($user['email']) ?></div>
</div>

<div class="tab-content">

  <div class="tab-pane fade show active" id="emp-details">
    <div class="formwrap">
      <form id="employeeForm" method="post" action="<?= site_url('hrms/team/' . (int) $user['id']) ?>">
        <?= csrf_field() ?>
        <div class="retro-row">
          <div class="retro-field"><label>Employee Code :</label><input class="retro-box" name="employee_code" value="<?= esc($profile['employee_code'] ?? '') ?>"></div>
          <div class="retro-field"><label>Date of Joining :</label><input class="retro-box" type="date" name="date_of_joining" value="<?= esc($profile['date_of_joining'] ?? '') ?>"></div>
          <div class="retro-field"><label>Designation :</label><input class="retro-box wide" name="designation" value="<?= esc($profile['designation'] ?? '') ?>"></div>
          <div class="retro-field"><label>Department :</label><input class="retro-box wide" name="department" value="<?= esc($profile['department'] ?? '') ?>"></div>
        </div>
        <div class="retro-row">
          <div class="retro-field"><label>Reporting Manager (user ID) :</label><input class="retro-box narrow" type="number" name="reporting_manager_id" value="<?= esc((string) ($profile['reporting_manager_id'] ?? '')) ?>"></div>
          <div class="retro-field"><label>Date of Birth :</label><input class="retro-box" type="date" name="date_of_birth" value="<?= esc($profile['date_of_birth'] ?? '') ?>"></div>
          <div class="retro-field"><label>Gender :</label>
            <select class="retro-box" name="gender">
              <option value="">—</option>
              <?php foreach (['Male','Female','Other'] as $g): ?><option value="<?= $g ?>" <?= ($profile['gender'] ?? '') === $g ? 'selected' : '' ?>><?= $g ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="retro-field"><label>Blood Group :</label><input class="retro-box narrow" name="blood_group" maxlength="5" value="<?= esc($profile['blood_group'] ?? '') ?>"></div>
        </div>

        <div class="retro-row" style="align-items:flex-start;">
          <div class="retro-field" style="width:48%;"><label style="white-space:nowrap;">Permanent Address :</label>
            <textarea class="retro-box retro-particulars" style="width:100%;" rows="2" name="permanent_address"><?= esc($profile['permanent_address'] ?? '') ?></textarea>
          </div>
          <div class="retro-field" style="width:48%;margin-left:auto;"><label style="white-space:nowrap;">Current Address :</label>
            <textarea class="retro-box retro-particulars" style="width:100%;" rows="2" name="current_address"><?= esc($profile['current_address'] ?? '') ?></textarea>
          </div>
        </div>

        <div class="retro-row">
          <div class="retro-field"><label>Emergency Contact Name :</label><input class="retro-box wide" name="emergency_contact_name" value="<?= esc($profile['emergency_contact_name'] ?? '') ?>"></div>
          <div class="retro-field"><label>Emergency Contact Phone :</label><input class="retro-box wide" name="emergency_contact_phone" value="<?= esc($profile['emergency_contact_phone'] ?? '') ?>"></div>
        </div>

        <div class="retro-row" style="margin-top:6px;">
          <div class="retro-field" style="width:100%;">
            <h6 class="text-muted text-uppercase" style="font-size:.74rem;letter-spacing:.06em;font-weight:600;margin:0;">
              <i class="bi bi-people"></i> Next of kin · family contacts
            </h6>
          </div>
        </div>
        <?= view('hrms/_kin_rows', ['kin' => $kin ?? []]) ?>

        <div class="retro-row" style="margin-top:14px;">
          <div class="retro-field"><label>PAN :</label><input class="retro-box wide" name="pan_no" value="<?= esc($profile['pan_no'] ?? '') ?>"></div>
          <div class="retro-field"><label>Aadhaar L4 :</label><input class="retro-box narrow" name="aadhaar_last_4" maxlength="4" value="<?= esc($profile['aadhaar_last_4'] ?? '') ?>"></div>
          <div class="retro-field"><label>UAN :</label><input class="retro-box wide" name="uan_no" value="<?= esc($profile['uan_no'] ?? '') ?>"></div>
        </div>
        <div class="retro-row">
          <div class="retro-field"><label>Bank Name :</label><input class="retro-box wide" name="bank_name" value="<?= esc($profile['bank_name'] ?? '') ?>"></div>
          <div class="retro-field"><label>Account No. :</label><input class="retro-box wide" name="bank_account_no" value="<?= esc($profile['bank_account_no'] ?? '') ?>"></div>
          <div class="retro-field"><label>IFSC :</label><input class="retro-box" name="bank_ifsc" value="<?= esc($profile['bank_ifsc'] ?? '') ?>"></div>
        </div>

        <div class="retro-toolbar mt-3" style="position:static;">
          <button type="submit" class="retro-tbtn retro-primary"><i class="bi bi-save-fill"></i>Save HR Fields</button>
        </div>
      </form>
    </div>
  </div>

  <div class="tab-pane fade" id="emp-salary">
    <div class="formwrap">
      <form method="post" action="<?= site_url('hrms/team/' . (int) $user['id'] . '/salary') ?>">
        <?= csrf_field() ?>
        <div class="retro-row">
          <div class="retro-field"><label>Effective From :</label><input class="retro-box" type="date" name="effective_from" value="<?= esc($components['effective_from'] ?? date('Y-m-d')) ?>"></div>
        </div>
        <div class="retro-row">
          <div class="retro-field"><label>Basic ₹ :</label><input class="retro-box" type="number" step="0.01" name="basic" value="<?= esc((string) ($components['basic'] ?? 0)) ?>"></div>
          <div class="retro-field"><label>HRA ₹ :</label><input class="retro-box" type="number" step="0.01" name="hra" value="<?= esc((string) ($components['hra'] ?? 0)) ?>"></div>
          <div class="retro-field"><label>Special Allowance ₹ :</label><input class="retro-box" type="number" step="0.01" name="special_allowance" value="<?= esc((string) ($components['special_allowance'] ?? 0)) ?>"></div>
          <div class="retro-field"><label>Conveyance ₹ :</label><input class="retro-box" type="number" step="0.01" name="conveyance" value="<?= esc((string) ($components['conveyance'] ?? 0)) ?>"></div>
        </div>
        <div class="retro-row">
          <div class="retro-field"><label>Medical ₹ :</label><input class="retro-box" type="number" step="0.01" name="medical" value="<?= esc((string) ($components['medical'] ?? 0)) ?>"></div>
          <div class="retro-field"><label>Other Earnings ₹ :</label><input class="retro-box" type="number" step="0.01" name="other_earnings" value="<?= esc((string) ($components['other_earnings'] ?? 0)) ?>"></div>
        </div>
        <div class="retro-row">
          <div class="retro-field"><label>PF ₹ :</label><input class="retro-box" type="number" step="0.01" name="pf_deduction" value="<?= esc((string) ($components['pf_deduction'] ?? 0)) ?>"></div>
          <div class="retro-field"><label>ESI ₹ :</label><input class="retro-box" type="number" step="0.01" name="esi_deduction" value="<?= esc((string) ($components['esi_deduction'] ?? 0)) ?>"></div>
          <div class="retro-field"><label>PT ₹ :</label><input class="retro-box" type="number" step="0.01" name="pt_deduction" value="<?= esc((string) ($components['pt_deduction'] ?? 0)) ?>"></div>
          <div class="retro-field"><label>TDS ₹ :</label><input class="retro-box" type="number" step="0.01" name="tds_deduction" value="<?= esc((string) ($components['tds_deduction'] ?? 0)) ?>"></div>
          <div class="retro-field"><label>Other Deductions ₹ :</label><input class="retro-box" type="number" step="0.01" name="other_deductions" value="<?= esc((string) ($components['other_deductions'] ?? 0)) ?>"></div>
        </div>

        <div class="retro-toolbar mt-3" style="position:static;">
          <button type="submit" class="retro-tbtn retro-primary"><i class="bi bi-save-fill"></i>Save Salary Structure</button>
          <div class="retro-field" style="color:var(--v2-fg-muted);font-size:11.5px;align-self:center;margin-left:10px;">Used for monthly payroll runs.</div>
        </div>
      </form>
    </div>
  </div>

  <div class="tab-pane fade" id="emp-leaves">
    <div class="gridwrap">
      <?php if (empty($balances)): ?>
        <div class="text-center text-muted py-4">No leave balances on record for <?= date('Y') ?> yet.</div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table grid mb-0">
            <thead><tr><th>Type</th><th>Allocated</th><th>Used</th><th>Balance</th></tr></thead>
            <tbody>
              <?php foreach ($balances as $b): ?>
                <tr><td><?= esc($b['code']) ?> · <?= esc($b['name']) ?></td><td><?= esc((string) $b['allocated']) ?></td><td><?= esc((string) $b['used']) ?></td><td><strong><?= esc((string) $b['balance']) ?></strong></td></tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>

</div>
