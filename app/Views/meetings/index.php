<?php
$extra = '';
if ($isManager) {
    $extra = '<div class="btn-group btn-group-sm">'
        . '<a class="btn ' . ($scope === 'mine' ? 'btn-dark' : 'btn-light') . '" href="?scope=mine">Mine</a>'
        . '<a class="btn ' . ($scope === 'team' ? 'btn-dark' : 'btn-light') . '" href="?scope=team">Team</a>'
        . '</div>';
}
echo tpt_toolbar([
    'new_href'       => site_url('meetings/create'),
    'new_item_label' => 'Plan Meeting',
    'close_href'     => site_url('dashboard'),
    'extra'          => $extra,
    'auth'           => $auth,
]);
?>
<div class="tabs">
  <div class="tab active">Meetings</div>
  <div class="spacer"></div>
  <div class="recordnav"><?= count($rows) ?> total records</div>
</div>

<div class="gridwrap">
  <div class="table-responsive">
    <table class="table grid mb-0" data-tpt-cols="meetings">
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
          <tr class="row-link" data-href="<?= site_url('meetings/' . (int) $r['id']) ?>">
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
