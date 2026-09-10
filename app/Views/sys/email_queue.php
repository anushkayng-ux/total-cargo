<div class="d-flex justify-content-between align-items-center mb-3">
  <h5 class="m-0"><i class="bi bi-envelope-paper"></i> Email Queue</h5>
  <form method="post" action="<?= site_url('sys/email-queue/flush') ?>">
    <?= csrf_field() ?>
    <button class="btn btn-sm btn-primary"><i class="bi bi-arrow-clockwise"></i> Flush queue + reap stuck</button>
  </form>
</div>

<div class="card">
  <div class="table-responsive">
    <table class="table table-sm align-middle mb-0">
      <thead><tr><th>#</th><th>To</th><th>Subject</th><th>Status</th><th>Attempts</th><th>Worker</th><th>Last event</th><th>Error</th><th></th></tr></thead>
      <tbody>
      <?php if (empty($logs)): ?>
        <tr><td colspan="9" class="text-center text-muted py-4">Queue is empty 🎉</td></tr>
      <?php endif; ?>
      <?php foreach ($logs as $l): ?>
        <tr>
          <td>#<?= (int) $l['id'] ?></td>
          <td><?= esc($l['to_email']) ?></td>
          <td style="max-width:340px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= esc($l['subject']) ?></td>
          <td><?= esc($l['status']) ?></td>
          <td><?= (int) $l['attempts'] ?></td>
          <td class="mono" style="font-size:.78rem;"><?= esc($l['worker_id'] ?: '—') ?></td>
          <td><?= esc($l['last_event_at'] ?: '—') ?></td>
          <td class="text-danger" style="font-size:.78rem;max-width:300px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= esc($l['error'] ?: '') ?></td>
          <td>
            <form method="post" action="<?= site_url('sys/email-queue/' . $l['id'] . '/retry') ?>" style="display:inline;">
              <?= csrf_field() ?><button class="btn btn-sm btn-light"><i class="bi bi-arrow-clockwise"></i> Retry</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
