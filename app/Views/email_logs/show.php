<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h5 class="mb-1">Email Log #<?= (int) $row['id'] ?></h5>
    <span class="text-muted" style="font-size:.9rem;">
      <?= esc(date('d-m-Y H:i:s', strtotime($row['created_at']))) ?>
      &middot; <?= esc($row['status']) ?>
      &middot; <?= esc($row['provider'] ?: 'no driver') ?>
    </span>
  </div>
  <div>
    <?php if (in_array($row['status'], ['Queued','Failed'], true)): ?>
      <form method="post" action="<?= site_url('email-logs/' . $row['id'] . '/retry') ?>" style="display:inline;">
        <?= csrf_field() ?><button class="btn btn-sm btn-primary"><i class="bi bi-arrow-clockwise"></i> Retry</button>
      </form>
    <?php endif; ?>
    <a href="<?= site_url('email-logs') ?>" class="btn btn-sm btn-light ms-1">&larr; All logs</a>
  </div>
</div>

<div class="row g-3">
  <div class="col-lg-7">
    <div class="card mb-3">
      <div class="card-header">Headers</div>
      <div class="card-body">
        <dl class="row mb-0" style="font-size:.92rem;">
          <dt class="col-3">To</dt><dd class="col-9"><?= esc($row['to_email']) ?> <?= $row['to_name'] ? '<small class="text-muted">' . esc($row['to_name']) . '</small>' : '' ?></dd>
          <?php if ($row['cc']): ?><dt class="col-3">CC</dt><dd class="col-9"><?= esc($row['cc']) ?></dd><?php endif; ?>
          <?php if ($row['bcc']): ?><dt class="col-3">BCC</dt><dd class="col-9"><?= esc($row['bcc']) ?></dd><?php endif; ?>
          <?php if ($row['reply_to']): ?><dt class="col-3">Reply-to</dt><dd class="col-9"><?= esc($row['reply_to']) ?></dd><?php endif; ?>
          <dt class="col-3">Subject</dt><dd class="col-9"><?= esc($row['subject']) ?></dd>
          <dt class="col-3">Template</dt><dd class="col-9"><code><?= esc($row['template_key'] ?: '—') ?></code></dd>
          <dt class="col-3">Provider id</dt><dd class="col-9"><code style="font-size:.78rem;"><?= esc($row['provider_msg_id'] ?: '—') ?></code></dd>
          <?php if ($row['error']): ?>
            <dt class="col-3">Error</dt><dd class="col-9 text-danger"><?= esc($row['error']) ?></dd>
          <?php endif; ?>
        </dl>
      </div>
    </div>

    <div class="card">
      <div class="card-header d-flex align-items-center">
        <span>Rendered HTML body</span>
        <span class="badge bg-secondary ms-2" title="The tracking pixel is stripped and click-tracking links unwrapped before this preview renders. Sandbox blocks scripts/forms.">
          <i class="bi bi-shield-check"></i> Preview · tracking disabled
        </span>
      </div>
      <iframe srcdoc="<?= esc($row['body_html'] ?? '') ?>"
              style="width:100%; min-height:520px; border:0;"
              sandbox="allow-popups allow-popups-to-escape-sandbox"
              loading="lazy"
              referrerpolicy="no-referrer"></iframe>
    </div>
  </div>

  <div class="col-lg-5">
    <div class="card mb-3">
      <div class="card-header">Lifecycle</div>
      <div class="card-body" style="font-size:.92rem;">
        <?php
          $points = [
            ['Queued',   $row['queued_at'] ?: $row['created_at']],
            ['Sent',     $row['sent_at']],
            ['Delivered',$row['delivered_at']],
            ['Opened',   $row['opened_at']],
            ['Clicked',  $row['first_clicked_at']],
            ['Bounced',  $row['bounced_at']],
            ['Spam',     $row['complained_at']],
            ['Unsub',    $row['unsubscribed_at']],
          ];
        ?>
        <ol class="list-unstyled mb-0">
        <?php foreach ($points as [$lbl, $when]): ?>
          <li style="padding:.3rem 0; border-left:3px solid <?= $when ? '#166c3b' : '#ddd' ?>; padding-left:.7rem; margin-left:.4rem;">
            <strong style="<?= $when ? '' : 'color:#aaa;' ?>"><?= esc($lbl) ?></strong>
            <?php if ($when): ?><span class="text-muted ms-2" style="font-size:.85rem;"><?= esc(date('d-m-Y H:i:s', strtotime($when))) ?></span><?php endif; ?>
          </li>
        <?php endforeach; ?>
        </ol>
        <hr>
        <div class="d-flex gap-3 flex-wrap">
          <div><div class="text-muted" style="font-size:.72rem;">OPENS</div><strong><?= (int) $row['open_count'] ?></strong></div>
          <div><div class="text-muted" style="font-size:.72rem;">CLICKS</div><strong><?= (int) $row['click_count'] ?></strong></div>
          <div><div class="text-muted" style="font-size:.72rem;">ATTEMPTS</div><strong><?= (int) $row['attempts'] ?></strong></div>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-header">Events</div>
      <div class="table-responsive">
        <table class="table table-sm mb-0">
          <thead><tr><th>When</th><th>Event</th><th>IP</th></tr></thead>
          <tbody>
          <?php if (empty($events)): ?>
            <tr><td colspan="3" class="text-center text-muted py-3">No events yet.</td></tr>
          <?php endif; ?>
          <?php foreach ($events as $e): ?>
            <tr>
              <td><?= esc(date('d-m H:i:s', strtotime($e['created_at']))) ?></td>
              <td><?= esc($e['event_type']) ?></td>
              <td><?= esc($e['ip_address'] ?: '—') ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
