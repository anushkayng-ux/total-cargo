<?= tpt_toolbar([
    'save_form'   => 'meetingForm',
    'close_href'  => site_url('meetings'),
    'auth'        => $auth,
]) ?>
<div class="tabs">
  <div class="tab active">Plan a Meeting</div>
  <div class="spacer"></div>
</div>

<form id="meetingForm" method="post" action="<?= site_url('meetings') ?>">
  <?= csrf_field() ?>
  <div class="formwrap">
    <div class="retro-row">
      <div class="retro-field" style="width:65%;"><label>Meeting Title <span class="retro-required">*</span> :</label>
        <input class="retro-box" style="width:100%;" type="text" name="title" maxlength="200" required placeholder="e.g. Rate negotiation — Blue Horizon Textiles">
      </div>
      <div class="retro-field" style="width:30%;margin-left:auto;"><label>Scheduled At <span class="retro-required">*</span> :</label>
        <input class="retro-box" style="width:100%;" type="datetime-local" name="scheduled_at" required value="<?= esc(date('Y-m-d\TH:i', strtotime('+1 hour'))) ?>">
      </div>
    </div>
    <div class="retro-row">
      <div class="retro-field"><label>With (company) :</label><input class="retro-box wide" type="text" name="with_company" maxlength="200" placeholder="Client / vendor company"></div>
      <div class="retro-field"><label>Contact Name :</label><input class="retro-box wide" type="text" name="with_contact_name" maxlength="150"></div>
      <div class="retro-field"><label>Contact Phone :</label><input class="retro-box" type="tel" name="with_contact_phone" maxlength="30"></div>
    </div>
    <div class="retro-row" style="align-items:flex-start;">
      <div class="retro-field" style="width:100%;"><label style="white-space:nowrap;">Location :</label>
        <textarea class="retro-box retro-particulars" style="width:100%;" rows="2" name="location" maxlength="400" placeholder="Address or landmark"></textarea>
      </div>
    </div>
    <div style="font-size:11px;color:var(--v2-fg-muted);margin-top:8px;">After saving you'll get a phone-friendly PWA link to punch your travel events.</div>
  </div>

  <div class="retro-toolbar mt-3" style="position:static;">
    <button type="submit" class="retro-tbtn retro-primary"><i class="bi bi-save-fill"></i>Save Meeting</button>
    <a class="retro-tbtn" href="<?= site_url('meetings') ?>"><i class="bi bi-x-circle"></i>Close</a>
  </div>
</form>
