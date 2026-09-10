<?php
$eventLabels = [
    'depart_office'   => ['🚗', 'Departed office'],
    'arrive_meeting'  => ['📍', 'Arrived at meeting'],
    'leave_meeting'   => ['👋', 'Left meeting'],
    'return_office'   => ['🏢', 'Back at office'],
];
$byType = [];
foreach ($events as $e) $byType[$e['event_type']] = $e;
?>
<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
  <h5 class="m-0"><i class="bi bi-people"></i> <?= esc($row['title']) ?></h5>
  <?php
  $cls = match($row['status']) { 'Planned'=>'secondary','InProgress'=>'primary','Completed'=>'success','Cancelled'=>'dark', default=>'light' };
  ?>
  <span class="badge bg-<?= $cls ?>"><?= esc($row['status']) ?></span>
  <a class="btn btn-sm btn-light ms-auto" href="<?= site_url('meetings') ?>"><i class="bi bi-arrow-left"></i> All meetings</a>
</div>

<div class="row g-3">
  <div class="col-lg-7">
    <div class="card mb-3">
      <div class="card-header"><i class="bi bi-card-text"></i> Details</div>
      <div class="card-body">
        <div class="row g-3" style="font-size:.92rem;">
          <div class="col-md-6"><div class="text-muted">When</div><div><?= esc(date('D, d-m-Y · H:i', strtotime($row['scheduled_at']))) ?></div></div>
          <div class="col-md-6"><div class="text-muted">Owner</div><div><?= esc($owner['name'] ?? '—') ?></div></div>
          <div class="col-md-6"><div class="text-muted">With company</div><div><?= esc($row['with_company'] ?? '—') ?></div></div>
          <div class="col-md-6"><div class="text-muted">Contact</div><div><?= esc(trim(($row['with_contact_name'] ?? '') . ' · ' . ($row['with_contact_phone'] ?? ''), ' ·')) ?: '—' ?></div></div>
          <div class="col-12"><div class="text-muted">Location</div><div><?= esc($row['location'] ?? '—') ?></div></div>
        </div>
      </div>
    </div>

    <form method="post" action="<?= site_url('meetings/' . (int) $row['id']) ?>" class="card mb-3">
      <?= csrf_field() ?>
      <div class="card-header"><i class="bi bi-journal-text"></i> Outcome &amp; next steps</div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-12">
            <label class="form-label">Status</label>
            <select class="form-select" name="status">
              <?php foreach (['Planned','InProgress','Completed','Cancelled'] as $s): ?>
                <option <?= $row['status'] === $s ? 'selected' : '' ?>><?= $s ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-12">
            <label class="form-label">Outcome / discussion notes</label>
            <textarea class="form-control" rows="4" name="outcome"><?= esc($row['outcome'] ?? '') ?></textarea>
          </div>
          <div class="col-12">
            <label class="form-label">Next steps</label>
            <textarea class="form-control" rows="3" name="next_steps"><?= esc($row['next_steps'] ?? '') ?></textarea>
          </div>
        </div>
      </div>
      <div class="card-footer"><button class="btn btn-primary"><i class="bi bi-check2"></i> Save notes</button></div>
    </form>
  </div>

  <div class="col-lg-5">
    <?php if ($pwaUrl): ?>
      <div class="card mb-3">
        <div class="card-header"><i class="bi bi-phone"></i> Mobile PWA</div>
        <div class="card-body">
          <p class="small text-muted">Open this URL on your phone before leaving for the meeting. Tap each milestone as you go — GPS is captured automatically.</p>
          <div class="input-group input-group-sm mb-2">
            <input class="form-control" type="text" value="<?= esc($pwaUrl) ?>" readonly onclick="this.select();">
            <a class="btn btn-light" href="<?= esc($pwaUrl) ?>" target="_blank" rel="noopener"><i class="bi bi-box-arrow-up-right"></i></a>
          </div>
          <a class="btn btn-sm btn-outline-dark" href="https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=<?= rawurlencode($pwaUrl) ?>" target="_blank"><i class="bi bi-qr-code"></i> Scan QR with phone</a>
        </div>
      </div>
    <?php endif; ?>

    <div class="card">
      <div class="card-header"><i class="bi bi-list-check"></i> Travel timeline</div>
      <div class="card-body">
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
</div>
