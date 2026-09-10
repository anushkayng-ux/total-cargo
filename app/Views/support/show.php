<?php
$badgeFor = fn ($s) => [
    'Open' => 'badge-warn', 'In Progress' => 'badge-warn',
    'Resolved' => 'badge-ok', 'Closed' => 'badge-ok',
    'Reopened' => 'badge-danger',
][$s] ?? 'badge-soft';
$canRate    = $ratingEnabled && (int) $row['user_id'] === (int) ($currentUser['id'] ?? 0)
              && in_array($row['status'], ['Resolved', 'Closed'], true) && empty($row['rating']);
$showRating = $ratingEnabled && !empty($row['rating']);
?>
<div class="d-flex align-items-center mb-3 gap-2 flex-wrap">
  <a href="<?= site_url('support') ?>" class="btn btn-sm btn-light"><i class="bi bi-arrow-left"></i></a>
  <h5 class="m-0">
    <code><?= esc($row['ticket_no']) ?></code>
    <span class="badge-soft <?= $badgeFor($row['status']) ?>"><?= esc($row['status']) ?></span>
    <span class="badge-soft"><?= esc($row['type']) ?></span>
    <span class="badge-soft"><?= esc($row['priority']) ?></span>
  </h5>
  <small class="text-muted ms-auto">Raised <?= esc(date('d-m-Y H:i', strtotime($row['created_at']))) ?> by <?= esc($row['reporter_name'] ?? '—') ?></small>
</div>

<div class="row g-3">
  <div class="col-lg-8">
    <div class="card mb-3"><div class="card-body">
      <h6 class="mb-1"><?= esc($row['subject']) ?></h6>
      <?php if (!empty($row['screen_url'])): ?>
        <div class="small text-muted mb-2">URL: <code><?= esc($row['screen_url']) ?></code></div>
      <?php endif; ?>
      <div style="white-space:pre-wrap;"><?= esc($row['body']) ?></div>
    </div></div>

    <?php if (!empty($row['resolution'])): ?>
      <div class="card mb-3 border-success"><div class="card-body">
        <h6 class="mb-1 text-success"><i class="bi bi-check-circle"></i> Resolution</h6>
        <div style="white-space:pre-wrap;"><?= esc($row['resolution']) ?></div>
      </div></div>
    <?php endif; ?>

    <h6 class="mt-3 mb-2">Conversation</h6>
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
      <form method="post" action="<?= site_url('support/' . $row['id'] . '/reply') ?>" class="card mt-3"><div class="card-body">
        <?= csrf_field() ?>
        <label class="form-label">Add a reply</label>
        <textarea class="form-control" name="body" rows="3" required maxlength="5000"></textarea>
        <div class="d-flex align-items-center gap-3 mt-2">
          <?php if ($isAgent): ?>
            <div class="form-check"><input type="checkbox" class="form-check-input" name="is_internal" value="1" id="intnote">
              <label class="form-check-label small" for="intnote">Internal note (not visible to reporter)</label>
            </div>
          <?php endif; ?>
          <button class="btn btn-sm btn-primary ms-auto"><i class="bi bi-send"></i> Post reply</button>
        </div>
      </div></form>
    <?php endif; ?>

    <?php if ($canRate): ?>
      <form method="post" action="<?= site_url('support/' . $row['id'] . '/rate') ?>" class="card mt-3 border-warning"><div class="card-body">
        <?= csrf_field() ?>
        <h6 class="mb-2">How was the support?</h6>
        <div class="d-flex gap-3 align-items-center mb-2">
          <?php for ($i = 1; $i <= 5; $i++): ?>
            <label class="d-flex align-items-center gap-1">
              <input type="radio" name="rating" value="<?= $i ?>" required>
              <span><?= str_repeat('★', $i) ?></span>
            </label>
          <?php endfor; ?>
        </div>
        <textarea class="form-control mb-2" name="rating_comment" rows="2" placeholder="Optional comment"></textarea>
        <button class="btn btn-sm btn-warning"><i class="bi bi-star-fill"></i> Submit rating</button>
      </div></form>
    <?php elseif ($showRating): ?>
      <div class="card mt-3"><div class="card-body">
        <strong>Rating:</strong> <?= str_repeat('★', (int) $row['rating']) . str_repeat('☆', 5 - (int) $row['rating']) ?>
        <?php if (!empty($row['rating_comment'])): ?>
          <div class="text-muted small mt-1">"<?= esc($row['rating_comment']) ?>"</div>
        <?php endif; ?>
      </div></div>
    <?php endif; ?>
  </div>

  <div class="col-lg-4">
    <div class="card mb-3"><div class="card-body">
      <div class="small text-muted">Reporter</div>
      <div><?= esc($row['reporter_name'] ?? '—') ?> <?php if (!empty($row['reporter_email'])): ?><br><small class="text-muted"><?= esc($row['reporter_email']) ?></small><?php endif; ?></div>
      <hr class="my-2">
      <div class="small text-muted">Assignee</div>
      <div><?= esc($row['assignee_name'] ?? '— Unassigned —') ?></div>
      <hr class="my-2">
      <div class="small text-muted">Updated</div>
      <div><?= esc(date('d-m-Y H:i', strtotime($row['updated_at']))) ?></div>
      <?php if (!empty($row['resolved_at'])): ?>
        <hr class="my-2">
        <div class="small text-muted">Resolved</div>
        <div><?= esc(date('d-m-Y H:i', strtotime($row['resolved_at']))) ?></div>
      <?php endif; ?>
      <?php if (!empty($row['closed_at'])): ?>
        <hr class="my-2">
        <div class="small text-muted">Closed</div>
        <div><?= esc(date('d-m-Y H:i', strtotime($row['closed_at']))) ?></div>
      <?php endif; ?>
    </div></div>

    <?php if ($isAgent): ?>
      <div class="card mb-3"><div class="card-body">
        <h6 class="mb-2">Agent actions</h6>
        <form method="post" action="<?= site_url('support/' . $row['id'] . '/update') ?>" class="mb-2">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="assign">
          <label class="form-label small">Assign to</label>
          <div class="d-flex gap-1">
            <select class="form-select form-select-sm" name="assigned_to">
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
          <label class="form-label small">Set status</label>
          <div class="d-flex gap-1">
            <select class="form-select form-select-sm" name="status">
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
            <label class="form-label small">Resolve with note</label>
            <textarea class="form-control form-control-sm mb-1" name="resolution" rows="2" required></textarea>
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
      </div></div>
    <?php endif; ?>
  </div>
</div>
