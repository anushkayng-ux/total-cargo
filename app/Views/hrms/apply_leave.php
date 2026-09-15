<?= tpt_toolbar([
    'save_form'   => 'applyLeaveForm',
    'close_href'  => site_url('hrms/leaves'),
    'auth'        => $auth,
]) ?>

<div class="tabs" role="tablist">
  <div class="tab active">Apply for Leave</div>
  <div class="spacer"></div>
</div>

<div class="formwrap">
  <form id="applyLeaveForm" method="post" action="<?= site_url('hrms/leaves/apply') ?>">
    <?= csrf_field() ?>
    <div class="retro-row">
      <div class="retro-field" style="width:46%;"><label>Leave Type <span class="retro-required">*</span> :</label>
        <select class="retro-box xwide" style="width:100%;" name="leave_type_id" required>
          <option value="">— select —</option>
          <?php foreach ($types as $t): ?>
            <option value="<?= (int) $t['id'] ?>"><?= esc($t['code']) ?> — <?= esc($t['name']) ?> (<?= esc((string) $t['default_annual_quota']) ?>/yr)</option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="retro-field"><label>From Date <span class="retro-required">*</span> :</label><input type="date" class="retro-box" name="from_date" required></div>
      <div class="retro-field"><label>To Date <span class="retro-required">*</span> :</label><input type="date" class="retro-box" name="to_date" required></div>
    </div>
    <div class="retro-row">
      <label class="retro-checkline"><input type="checkbox" id="is_half_day" name="is_half_day" value="1"> Half-day leave</label>
    </div>
    <div class="retro-row" style="align-items:flex-start;">
      <div class="retro-field" style="width:100%;"><label style="white-space:nowrap;">Reason :</label>
        <textarea class="retro-box retro-particulars" style="width:100%;" rows="3" name="reason" placeholder="Briefly explain the reason for this leave"></textarea>
      </div>
    </div>

    <div class="retro-toolbar mt-3" style="position:static;">
      <button type="submit" class="retro-tbtn retro-primary"><i class="bi bi-send"></i>Submit Application</button>
      <a class="retro-tbtn" href="<?= site_url('hrms/leaves') ?>"><i class="bi bi-x-circle"></i>Close</a>
    </div>
  </form>
</div>
