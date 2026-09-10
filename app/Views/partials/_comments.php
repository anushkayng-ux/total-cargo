<?php
/**
 * Reusable internal-team comment thread.
 * Required vars: $threadType ('booking'|'trip'|'lead'), $threadId (int), $threadComments (array)
 * Optional: $currentUser
 */
$me = $currentUser ?? ($auth ? $auth->user() : null);
?>
<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span><i class="bi bi-chat-dots"></i> Internal team thread</span>
    <small class="text-muted">Visible to staff only · not shown to client</small>
  </div>
  <div class="card-body">
    <?php if (empty($threadComments)): ?>
      <div class="text-muted text-center py-3" style="font-size:.9rem;">No comments yet. Use @firstname.lastname to mention a colleague.</div>
    <?php else: ?>
      <ul class="list-unstyled mb-3" style="font-size:.92rem;">
        <?php foreach ($threadComments as $c): ?>
          <li class="mb-3" style="border-left:3px solid #ddd; padding-left:.8rem;">
            <div class="d-flex justify-content-between">
              <strong><?= esc($c['author_name'] ?: 'User #' . (int) $c['user_id']) ?></strong>
              <small class="text-muted"><?= esc(date('d-m-Y H:i', strtotime($c['created_at']))) ?></small>
            </div>
            <?php
              // Highlight @mentions
              $body = esc($c['body']);
              $body = preg_replace('/@([\w.-]{2,30})/', '<span style="background:#fff3cd;padding:0 .2em;border-radius:3px;">@$1</span>', $body);
              $body = nl2br($body);
            ?>
            <div><?= $body ?></div>
            <?php
              $canDelete = $me && (
                (int) $me['id'] === (int) $c['user_id']
                || ($auth && $auth->can('comments', 'can_delete'))
              );
            ?>
            <?php if ($canDelete): ?>
              <form method="post" action="<?= site_url('comments/' . $threadType . '/' . $threadId . '/' . $c['id'] . '/delete') ?>" style="display:inline;" onsubmit="return confirm('Remove this comment?');">
                <?= csrf_field() ?>
                <button class="btn btn-sm btn-link text-danger p-0" style="font-size:.78rem;">Delete</button>
              </form>
            <?php endif; ?>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>

    <form method="post" action="<?= site_url('comments/' . $threadType . '/' . $threadId) ?>">
      <?= csrf_field() ?>
      <textarea name="body" class="form-control mb-2" rows="2" placeholder="Add a comment for the team. Use @firstname.lastname to mention." required maxlength="4000"></textarea>
      <button class="btn btn-sm btn-primary"><i class="bi bi-send"></i> Post</button>
    </form>
  </div>
</div>
