<?php
$badgeFor = fn ($s) => [
    'Open' => 'badge-warn', 'In Progress' => 'badge-warn',
    'Resolved' => 'badge-ok', 'Closed' => 'badge-ok',
    'Reopened' => 'badge-danger',
][$s] ?? 'badge-soft';
$val        = fn ($v, $empty = '—') => ($v === null || $v === '') ? $empty : esc((string) $v);
$canRate    = $ratingEnabled && (int) $row['user_id'] === (int) ($currentUser['id'] ?? 0)
              && in_array($row['status'], ['Resolved', 'Closed'], true) && empty($row['rating']);
$showRating = $ratingEnabled && !empty($row['rating']);
?>
<?= tpt_toolbar([
    'new_href'       => site_url('support/create'),
    'new_item_label' => 'Raise Ticket',
    'close_href'     => site_url('support'),
    'auth'           => $auth,
]) ?>

<div class="tabs" id="supportTabs">
  <button type="button" class="tab active" data-bs-toggle="tab" data-bs-target="#supp-details">Ticket Details</button>
  <button type="button" class="tab" data-bs-toggle="tab" data-bs-target="#supp-thread">Conversation</button>
  <div class="spacer"></div>
  <div class="recordnav">
    <a href="<?= site_url('support') ?>"><i class="bi bi-list"></i> List</a> &middot;
    <code><?= esc($row['ticket_no']) ?></code> &middot;
    <span class="badge-soft <?= $badgeFor($row['status']) ?>"><?= esc($row['status']) ?></span>
  </div>
</div>

<div class="tab-content">

  <div class="tab-pane fade show active" id="supp-details">
    <div class="retro-detail">
      <div class="retro-detail-main formwrap">
        <div class="retro-row">
          <div class="retro-field"><label>Type :</label><div class="retro-box"><?= $val($row['type']) ?></div></div>
          <div class="retro-field"><label>Priority :</label><div class="retro-box"><?= $val($row['priority']) ?></div></div>
          <div class="retro-field"><label>Status :</label><div class="retro-box"><span class="badge-soft <?= $badgeFor($row['status']) ?>"><?= esc($row['status']) ?></span></div></div>
          <div class="retro-field"><label>Raised :</label><div class="retro-box wide"><?= $val(date('d-m-Y H:i', strtotime($row['created_at']))) ?></div></div>
        </div>
        <div class="retro-row">
          <div class="retro-field" style="width:100%;"><label>Subject :</label><div class="retro-box xwide"><?= $val($row['subject']) ?></div></div>
        </div>
        <?php if (!empty($row['screen_url'])): ?>
        <div class="retro-row">
          <div class="retro-field" style="width:100%;"><label>Screen URL :</label><div class="retro-box xwide"><code><?= esc($row['screen_url']) ?></code></div></div>
        </div>
        <?php endif; ?>
        <div class="retro-row" style="align-items:flex-start;">
          <div class="retro-field" style="width:100%;"><label style="white-space:nowrap;">Description :</label>
            <div class="retro-particulars"><?= nl2br(esc($row['body'])) ?></div>
          </div>
        </div>
        <?php if (!empty($row['resolution'])): ?>
        <div class="retro-row" style="align-items:flex-start;">
          <div class="retro-field" style="width:100%;"><label style="white-space:nowrap;">Resolution :</label>
            <div class="retro-particulars" style="color:var(--v2-success,#1a7f37);"><?= nl2br(esc($row['resolution'])) ?></div>
          </div>
        </div>
        <?php endif; ?>
      </div>

      <div class="retro-detail-side">
        <h4>Reporter :</h4>
        <div class="remarksbox">
          <?= $val($row['reporter_name'] ?? null) ?>
          <?php if (!empty($row['reporter_email'])): ?><br><small class="text-muted"><?= esc($row['reporter_email']) ?></small><?php endif; ?>
        </div>

        <h4 style="margin-top:14px;">Assignee :</h4>
        <div class="remarksbox"><?= $val($row['assignee_name'] ?? null, '— Unassigned —') ?></div>

        <h4 style="margin-top:14px;">Dates :</h4>
        <div class="remarksbox" style="font-size:11.5px;min-height:auto;">
          Updated: <?= $val(date('d-m-Y H:i', strtotime($row['updated_at']))) ?><br>
          <?php if (!empty($row['resolved_at'])): ?>Resolved: <?= $val(date('d-m-Y H:i', strtotime($row['resolved_at']))) ?><br><?php endif; ?>
          <?php if (!empty($row['closed_at'])): ?>Closed: <?= $val(date('d-m-Y H:i', strtotime($row['closed_at']))) ?><?php endif; ?>
        </div>

        <?php if ($isAgent): ?>
          <h4 style="margin-top:14px;">Agent Actions :</h4>

          <form method="post" action="<?= site_url('support/' . $row['id'] . '/update') ?>" class="mb-2">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="assign">
            <label class="form-label small mb-1">Assign to</label>
            <div class="d-flex gap-1">
              <select class="retro-box" style="flex:1;" name="assigned_to">
                <option value="">— Unassigned —</option>
                <?php foreach ($agents as $a): ?>
                  <option value="<?= $a['id'] ?>" <?= (int) ($row['assigned_to'] ?? 0) === (int) $a['id'] ? 'selected' : '' ?>><?= esc($a['name']) ?></option>
                <?php endforeach; ?>
              </select>
              <button class="btn btn-sm btn-light">Set</button>
            </div>
          </form>

          <form method="post" action="<?= site_url('support/' . $row['id'] . '/update') ?>" class="mb-2">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="status">
            <label class="form-label small mb-1">Set status</label>
            <div class="d-flex gap-1">
              <select class="retro-box" style="flex:1;" name="status">
                <?php foreach (\App\Models\SupportTicketModel::STATUSES as $s): ?>
                  <option value="<?= $s ?>" <?= $row['status'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                <?php endforeach; ?>
              </select>
              <button class="btn btn-sm btn-light">Apply</button>
            </div>
          </form>

          <?php if (!in_array($row['status'], ['Resolved','Closed'], true)): ?>
            <form method="post" action="<?= site_url('support/' . $row['id'] . '/update') ?>" class="mb-2">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="resolve">
              <label class="form-label small mb-1">Resolve with note</label>
              <textarea class="retro-box retro-particulars mb-1" style="width:100%;" name="resolution" rows="2" required></textarea>
              <button class="btn btn-sm btn-success w-100"><i class="bi bi-check2"></i> Mark Resolved</button>
            </form>
          <?php endif; ?>

          <?php if ($row['status'] !== 'Closed'): ?>
            <form method="post" action="<?= site_url('support/' . $row['id'] . '/update') ?>" class="d-inline">
              <?= csrf_field() ?><input type="hidden" name="action" value="close">
              <button class="btn btn-sm btn-light w-100 mb-1"><i class="bi bi-archive"></i> Close ticket</button>
            </form>
          <?php endif; ?>

          <?php if (in_array($row['status'], ['Resolved','Closed'], true)): ?>
            <form method="post" action="<?= site_url('support/' . $row['id'] . '/update') ?>" class="d-inline">
              <?= csrf_field() ?><input type="hidden" name="action" value="reopen">
              <button class="btn btn-sm btn-warning w-100"><i class="bi bi-arrow-counterclockwise"></i> Reopen</button>
            </form>
          <?php endif; ?>
        <?php endif; ?>

        <?php if ($canRate): ?>
          <h4 style="margin-top:14px;">Rate this ticket :</h4>
          <form method="post" action="<?= site_url('support/' . $row['id'] . '/rate') ?>">
            <?= csrf_field() ?>
            <div class="d-flex gap-2 align-items-center mb-2">
              <?php for ($i = 1; $i <= 5; $i++): ?>
                <label class="d-flex align-items-center gap-1">
                  <input type="radio" name="rating" value="<?= $i ?>" required>
                  <span><?= str_repeat('★', $i) ?></span>
                </label>
              <?php endfor; ?>
            </div>
            <textarea class="retro-box retro-particulars mb-2" style="width:100%;" name="rating_comment" rows="2" placeholder="Optional comment"></textarea>
            <button class="btn btn-sm btn-warning w-100"><i class="bi bi-star-fill"></i> Submit rating</button>
          </form>
        <?php elseif ($showRating): ?>
          <h4 style="margin-top:14px;">Rating :</h4>
          <div class="remarksbox">
            <?= str_repeat('★', (int) $row['rating']) . str_repeat('☆', 5 - (int) $row['rating']) ?>
            <?php if (!empty($row['rating_comment'])): ?>
              <div class="text-muted small mt-1">"<?= esc($row['rating_comment']) ?>"</div>
            <?php endif; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="tab-pane fade" id="supp-thread">
    <div class="formwrap">
      <h6 class="mb-2"><i class="bi bi-chat-dots"></i> Conversation</h6>
      <?php if (empty($replies)): ?>
        <div class="text-muted small mb-3">No replies yet.</div>
      <?php else: ?>
        <?php foreach ($replies as $rep): ?>
          <?php if (!$isAgent && (int) $rep['is_internal'] === 1) continue; ?>
          <div class="card mb-2 <?= (int) $rep['is_internal'] === 1 ? 'border-warning' : '' ?>"><div class="card-body py-2">
            <div class="d-flex align-items-center gap-2">
              <strong><?= esc($rep['author_name'] ?? '—') ?></strong>
              <?php if ((int) $rep['is_internal'] === 1): ?><span class="badge-soft badge-warn">Internal note</span><?php endif; ?>
              <small class="text-muted ms-auto"><?= esc(date('d-m H:i', strtotime($rep['created_at']))) ?></small>
            </div>
            <div class="mt-1" style="white-space:pre-wrap;"><?= esc($rep['body']) ?></div>
          </div></div>
        <?php endforeach; ?>
      <?php endif; ?>

      <?php if (!in_array($row['status'], ['Closed'], true)): ?>
        <hr>
        <h6 class="mb-2"><i class="bi bi-reply"></i> Add a reply</h6>
        <form method="post" action="<?= site_url('support/' . $row['id'] . '/reply') ?>">
          <?= csrf_field() ?>
          <textarea class="retro-box retro-particulars" style="width:100%;" name="body" rows="3" required maxlength="5000"></textarea>
          <div class="d-flex align-items-center gap-3 mt-2">
            <?php if ($isAgent): ?>
              <label class="retro-checkline"><input type="checkbox" name="is_internal" value="1" id="intnote"> Internal note (not visible to reporter)</label>
            <?php endif; ?>
            <button class="btn btn-sm btn-primary ms-auto"><i class="bi bi-send"></i> Post reply</button>
          </div>
        </form>
      <?php endif; ?>
    </div>
  </div>

</div>
