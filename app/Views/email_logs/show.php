<?= tpt_toolbar([
    'close_href' => site_url('email-logs'),
    'extra'      => in_array($row['status'], ['Queued','Failed'], true)
        ? '<form method="post" action="' . site_url('email-logs/' . $row['id'] . '/retry') . '" class="d-inline">' . csrf_field() . '<button class="btn btn-sm btn-primary"><i class="bi bi-arrow-clockwise"></i> Retry</button></form>'
        : '',
    'auth'       => $auth,
]) ?>
<div class="tabs">
  <div class="tab active">Email Log</div>
  <div class="spacer"></div>
  <div class="recordnav">
    <a href="<?= site_url('email-logs') ?>"><i class="bi bi-list"></i> List</a> &middot;
    #<?= (int) $row['id'] ?> &middot; <?= esc($row['status']) ?> &middot; <?= esc($row['provider'] ?: 'no driver') ?>
    &middot; <?= esc(date('d-m-Y H:i:s', strtotime($row['created_at']))) ?>
  </div>
</div>

<div class="retro-detail">
  <div class="retro-detail-main formwrap">
    <h6 class="mb-2 text-muted" style="font-size:.82rem;text-transform:uppercase;letter-spacing:.5px;">Headers</h6>
    <div class="retro-row">
      <div class="retro-field" style="width:100%;"><label>To :</label><div class="retro-box xwide"><?= esc($row['to_email']) ?> <?= $row['to_name'] ? esc($row['to_name']) : '' ?></div></div>
    </div>
    <?php if ($row['cc']): ?><div class="retro-row"><div class="retro-field" style="width:100%;"><label>CC :</label><div class="retro-box xwide"><?= esc($row['cc']) ?></div></div></div><?php endif; ?>
    <?php if ($row['bcc']): ?><div class="retro-row"><div class="retro-field" style="width:100%;"><label>BCC :</label><div class="retro-box xwide"><?= esc($row['bcc']) ?></div></div></div><?php endif; ?>
    <?php if ($row['reply_to']): ?><div class="retro-row"><div class="retro-field" style="width:100%;"><label>Reply-to :</label><div class="retro-box xwide"><?= esc($row['reply_to']) ?></div></div></div><?php endif; ?>
    <div class="retro-row">
      <div class="retro-field" style="width:100%;"><label>Subject :</label><div class="retro-box xwide"><?= esc($row['subject']) ?></div></div>
    </div>
    <div class="retro-row">
      <div class="retro-field"><label>Template :</label><div class="retro-box wide"><?= esc($row['template_key'] ?: '—') ?></div></div>
      <div class="retro-field"><label>Provider id :</label><div class="retro-box wide empty"><?= esc($row['provider_msg_id'] ?: '—') ?></div></div>
    </div>
    <?php if ($row['error']): ?>
      <div class="retro-row" style="align-items:flex-start;">
        <div class="retro-field" style="width:100%;"><label style="white-space:nowrap;">Error :</label>
          <div class="retro-particulars" style="color:var(--v2-danger);"><?= esc($row['error']) ?></div>
        </div>
      </div>
    <?php endif; ?>

    <h6 class="mb-2 mt-3 text-muted" style="font-size:.82rem;text-transform:uppercase;letter-spacing:.5px;">Rendered HTML body <span class="badge bg-secondary ms-1" title="The tracking pixel is stripped and click-tracking links unwrapped before this preview renders. Sandbox blocks scripts/forms."><i class="bi bi-shield-check"></i> tracking disabled</span></h6>
    <iframe srcdoc="<?= esc($row['body_html'] ?? '') ?>"
            style="width:100%; min-height:420px; border:1px solid var(--tpt-border);border-radius:6px;"
            sandbox="allow-popups allow-popups-to-escape-sandbox"
            loading="lazy"
            referrerpolicy="no-referrer"></iframe>
  </div>

  <div class="retro-detail-side">
    <h4>Lifecycle :</h4>
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
    <ol class="list-unstyled mb-0" style="font-size:.88rem;">
    <?php foreach ($points as [$lbl, $when]): ?>
      <li style="padding:.3rem 0; border-left:3px solid <?= $when ? '#166c3b' : '#ddd' ?>; padding-left:.7rem; margin-left:.4rem;">
        <strong style="<?= $when ? '' : 'color:#aaa;' ?>"><?= esc($lbl) ?></strong>
        <?php if ($when): ?><span class="text-muted ms-2" style="font-size:.8rem;"><?= esc(date('d-m-Y H:i:s', strtotime($when))) ?></span><?php endif; ?>
      </li>
    <?php endforeach; ?>
    </ol>
    <div class="d-flex gap-3 flex-wrap mt-2">
      <div><div class="text-muted" style="font-size:.72rem;">OPENS</div><strong><?= (int) $row['open_count'] ?></strong></div>
      <div><div class="text-muted" style="font-size:.72rem;">CLICKS</div><strong><?= (int) $row['click_count'] ?></strong></div>
      <div><div class="text-muted" style="font-size:.72rem;">ATTEMPTS</div><strong><?= (int) $row['attempts'] ?></strong></div>
    </div>

    <h4 style="margin-top:14px;">Events :</h4>
    <?php if (empty($events)): ?>
      <div class="remarksbox">No events yet.</div>
    <?php else: ?>
      <div class="table-responsive">
        <table class="table table-sm mb-0" style="font-size:.82rem;">
          <thead><tr><th>When</th><th>Event</th><th>IP</th></tr></thead>
          <tbody>
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
    <?php endif; ?>
  </div>
</div>
