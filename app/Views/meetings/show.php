<?php
$val = fn ($v, $empty = '—') => ($v === null || $v === '') ? $empty : esc((string) $v);
$eventLabels = [
    'depart_office'   => ['🚗', 'Departed office'],
    'arrive_meeting'  => ['📍', 'Arrived at meeting'],
    'leave_meeting'   => ['👋', 'Left meeting'],
    'return_office'   => ['🏢', 'Back at office'],
];
$byType = [];
foreach ($events as $e) $byType[$e['event_type']] = $e;
$cls = match($row['status']) { 'Planned'=>'secondary','InProgress'=>'primary','Completed'=>'success','Cancelled'=>'dark', default=>'light' };
?>
<?= tpt_toolbar([
    'new_href'       => site_url('meetings/create'),
    'new_item_label' => 'Plan Meeting',
    'close_href'     => site_url('meetings'),
    'auth'           => $auth,
]) ?>
<div class="tabs">
  <div class="tab active">Meeting Details</div>
  <div class="spacer"></div>
  <div class="recordnav">
    <a href="<?= site_url('meetings') ?>"><i class="bi bi-list"></i> All Meetings</a> &middot;
    <span class="badge bg-<?= $cls ?>"><?= esc($row['status']) ?></span>
  </div>
</div>

<div class="retro-detail">
  <div class="retro-detail-main formwrap">
    <div class="retro-row">
      <div class="retro-field" style="width:100%;"><label>Title :</label><div class="retro-box xwide"><?= esc($row['title']) ?></div></div>
    </div>
    <div class="retro-row">
      <div class="retro-field"><label>When :</label><div class="retro-box wide"><?= $val(date('D, d-m-Y · H:i', strtotime($row['scheduled_at']))) ?></div></div>
      <div class="retro-field"><label>Owner :</label><div class="retro-box wide"><?= $val($owner['name'] ?? null) ?></div></div>
    </div>
    <div class="retro-row">
      <div class="retro-field"><label>With Company :</label><div class="retro-box wide"><?= $val($row['with_company'] ?? null) ?></div></div>
      <div class="retro-field"><label>Contact :</label><div class="retro-box wide"><?= $val(trim(($row['with_contact_name'] ?? '') . ' · ' . ($row['with_contact_phone'] ?? ''), ' ·') ?: null) ?></div></div>
    </div>
    <div class="retro-row" style="align-items:flex-start;">
      <div class="retro-field" style="width:100%;"><label style="white-space:nowrap;">Location :</label>
        <div class="retro-particulars"><?= $val($row['location'] ?? null) ?></div>
      </div>
    </div>

    <h6 class="mb-2 mt-3 text-muted" style="font-size:.82rem;text-transform:uppercase;letter-spacing:.5px;">Outcome &amp; Next Steps</h6>
    <form method="post" action="<?= site_url('meetings/' . (int) $row['id']) ?>">
      <?= csrf_field() ?>
      <div class="retro-row">
        <div class="retro-field"><label>Status :</label>
          <select class="retro-box wide" name="status">
            <?php foreach (['Planned','InProgress','Completed','Cancelled'] as $s): ?>
              <option <?= $row['status'] === $s ? 'selected' : '' ?>><?= $s ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="retro-row" style="align-items:flex-start;">
        <div class="retro-field" style="width:100%;"><label style="white-space:nowrap;">Outcome / Notes :</label>
          <textarea class="retro-box retro-particulars" style="width:100%;" rows="4" name="outcome"><?= esc($row['outcome'] ?? '') ?></textarea>
        </div>
      </div>
      <div class="retro-row" style="align-items:flex-start;">
        <div class="retro-field" style="width:100%;"><label style="white-space:nowrap;">Next Steps :</label>
          <textarea class="retro-box retro-particulars" style="width:100%;" rows="3" name="next_steps"><?= esc($row['next_steps'] ?? '') ?></textarea>
        </div>
      </div>
      <button class="btn btn-primary btn-sm mt-1"><i class="bi bi-check2"></i> Save Notes</button>
    </form>
  </div>

  <div class="retro-detail-side">
    <?php if ($pwaUrl): ?>
      <h4>Mobile PWA :</h4>
      <div class="remarksbox">
        <p class="small text-muted mb-2">Open this URL on your phone before leaving for the meeting. Tap each milestone as you go — GPS is captured automatically.</p>
        <div class="input-group input-group-sm mb-2">
          <input class="form-control" type="text" value="<?= esc($pwaUrl) ?>" readonly onclick="this.select();">
          <a class="btn btn-light" href="<?= esc($pwaUrl) ?>" target="_blank" rel="noopener"><i class="bi bi-box-arrow-up-right"></i></a>
        </div>
        <a class="btn btn-sm btn-outline-dark" href="https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=<?= rawurlencode($pwaUrl) ?>" target="_blank"><i class="bi bi-qr-code"></i> Scan QR with phone</a>
      </div>
    <?php endif; ?>

    <h4 style="margin-top:14px;">Travel Timeline :</h4>
    <div class="remarksbox">
      <?php foreach (['depart_office','arrive_meeting','leave_meeting','return_office'] as $et):
          [$icon, $label] = $eventLabels[$et];
          $e = $byType[$et] ?? null;
      ?>
        <div class="d-flex align-items-center gap-2 py-1" style="border-bottom:1px solid #f1f3f5;">
          <span style="font-size:1.2rem;"><?= $icon ?></span>
          <span class="flex-fill"><?= esc($label) ?></span>
          <?php if ($e): ?>
            <small class="text-muted">
              <?= esc(date('d-m · H:i', strtotime($e['occurred_at']))) ?>
              <?php if (!empty($e['latitude'])): ?>
                · <a href="https://maps.google.com/?q=<?= esc((string) $e['latitude']) ?>,<?= esc((string) $e['longitude']) ?>" target="_blank">map</a>
              <?php endif; ?>
            </small>
          <?php else: ?>
            <span class="text-muted small">— pending —</span>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>
