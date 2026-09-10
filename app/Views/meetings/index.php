<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
  <h5 class="m-0"><i class="bi bi-people"></i> Meetings</h5>
  <?php if ($isManager): ?>
    <div class="btn-group btn-group-sm ms-2">
      <a class="btn <?= $scope === 'mine' ? 'btn-dark' : 'btn-light' ?>" href="?scope=mine">Mine</a>
      <a class="btn <?= $scope === 'team' ? 'btn-dark' : 'btn-light' ?>" href="?scope=team">Team</a>
    </div>
  <?php endif; ?>
  <a class="btn btn-primary btn-sm ms-auto" href="<?= site_url('meetings/create') ?>"><i class="bi bi-plus-circle"></i> Plan meeting</a>
</div>

<div class="card">
  <div class="table-responsive">
    <table class="table table-sm align-middle mb-0" data-tpt-cols="meetings">
      <thead>
        <tr><th data-col="title">Title</th><?php if ($scope === 'team'): ?><th data-col="owner">Owner</th><?php endif; ?><th data-col="when">When</th><th data-col="with">With</th><th data-col="location">Location</th><th data-col="events">Events</th><th data-col="status">Status</th></tr>
      </thead>
      <tbody>
        <?php if (empty($rows)): ?>
          <tr><td colspan="7" class="text-center text-muted py-3">No meetings yet. Plan one to get started.</td></tr>
        <?php endif; ?>
        <?php foreach ($rows as $r):
          $cls = match($r['status']) {
            'Planned'    => 'secondary', 'InProgress' => 'primary',
            'Completed'  => 'success',   'Cancelled'  => 'dark',
            default      => 'light',
          };
        ?>
          <tr>
            <td data-col="title"><a href="<?= site_url('meetings/' . (int) $r['id']) ?>"><?= esc($r['title']) ?></a></td>
            <?php if ($scope === 'team'): ?><td data-col="owner"><?= esc($r['owner_name'] ?? '—') ?></td><?php endif; ?>
            <td data-col="when"><?= esc(date('d-m · H:i', strtotime($r['scheduled_at']))) ?></td>
            <td data-col="with"><?= esc($r['with_company'] ?? $r['with_contact_name'] ?? '—') ?></td>
            <td data-col="location"><?= esc(mb_strimwidth($r['location'] ?? '', 0, 40, '…')) ?></td>
            <td data-col="events"><span class="badge bg-light text-dark"><?= (int) $r['event_count'] ?>/4</span></td>
            <td data-col="status"><span class="badge bg-<?= $cls ?>"><?= esc($r['status']) ?></span></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
