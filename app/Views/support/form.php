<?= tpt_toolbar([
    'save_form'   => 'supportForm',
    'close_href'  => site_url('support'),
    'auth'        => $auth,
]) ?>

<div class="tabs" role="tablist" id="supportFormTabs">
  <button type="button" class="tab active" data-bs-toggle="tab" data-bs-target="#suppf-details">Raise a Ticket</button>
  <div class="spacer"></div>
</div>

<form id="supportForm" method="post" action="<?= site_url('support/store') ?>">
  <?= csrf_field() ?>
  <div class="tab-content">
    <div class="tab-pane fade show active" id="suppf-details">
      <div class="formwrap">
        <div class="retro-row">
          <div class="retro-field"><label>Type <span class="retro-required">*</span> :</label>
            <select class="retro-box" name="type" required>
              <?php foreach (\App\Models\SupportTicketModel::TYPES as $t): ?>
                <option value="<?= $t ?>" <?= old('type') === $t ? 'selected' : '' ?>><?= $t ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="retro-field"><label>Priority :</label>
            <select class="retro-box" name="priority">
              <?php foreach (\App\Models\SupportTicketModel::PRIORITIES as $p): ?>
                <option value="<?= $p ?>" <?= old('priority', 'Normal') === $p ? 'selected' : '' ?>><?= $p ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="retro-row">
          <div class="retro-field" style="width:100%;"><label>Where did you see this? <small class="text-muted">(optional URL)</small> :</label>
            <input class="retro-box xwide" style="width:100%;" name="screen_url" value="<?= esc(old('screen_url')) ?>" placeholder="e.g. /tpt/public/bookings/123">
          </div>
        </div>
        <div class="retro-row">
          <div class="retro-field" style="width:100%;"><label>Subject <span class="retro-required">*</span> :</label>
            <input class="retro-box xwide" style="width:100%;" name="subject" required maxlength="200" value="<?= esc(old('subject')) ?>"
                   placeholder="One-line summary of what you need help with">
          </div>
        </div>
        <div class="retro-row" style="align-items:flex-start;">
          <div class="retro-field" style="width:100%;"><label style="white-space:nowrap;">Describe the issue / suggestion <span class="retro-required">*</span> :</label>
            <textarea class="retro-box retro-particulars" style="width:100%;" name="body" rows="6" required maxlength="5000"
                      placeholder="Steps to reproduce, what you expected, what actually happened, screenshots/links if any."><?= esc(old('body')) ?></textarea>
            <div style="font-size:11px;color:var(--v2-fg-muted);margin-top:3px;">Tip: For bugs, include the steps you took and the screen URL. For improvements, tell us the outcome you want.</div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="retro-toolbar mt-3" style="position:static;">
    <button type="submit" class="retro-tbtn retro-primary"><i class="bi bi-send"></i>Submit</button>
    <a class="retro-tbtn" href="<?= site_url('support') ?>"><i class="bi bi-x-circle"></i>Close</a>
  </div>
</form>
