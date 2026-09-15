<?php
$badgeFor = fn ($s) => [
    'Open' => 'badge-warn', 'In Progress' => 'badge-warn',
    'Resolved' => 'badge-ok', 'Closed' => 'badge-ok',
    'Reopened' => 'badge-danger',
][$s] ?? 'badge-soft';

$extra = '<form method="get" action="' . site_url('support') . '" class="d-flex align-items-center gap-2 flex-wrap m-0">'
    . '<input type="text" name="q" class="form-control form-control-sm" style="width:200px;" placeholder="Search subject / ticket no" value="' . esc($search) . '">'
    . '<select name="status" class="form-select form-select-sm" style="width:auto;"><option value="">All statuses</option>';
foreach (\App\Models\SupportTicketModel::STATUSES as $s) {
    $extra .= '<option value="' . esc($s) . '"' . ($status === $s ? ' selected' : '') . '>' . esc($s) . '</option>';
}
$extra .= '</select><select name="type" class="form-select form-select-sm" style="width:auto;"><option value="">All types</option>';
foreach (\App\Models\SupportTicketModel::TYPES as $t) {
    $extra .= '<option value="' . esc($t) . '"' . ($type === $t ? ' selected' : '') . '>' . esc($t) . '</option>';
}
$extra .= '</select>';
if ($isAgent) {
    $extra .= '<select name="mine" class="form-select form-select-sm" style="width:auto;"><option value="">All tickets</option><option value="1"' . ($mine === '1' ? ' selected' : '') . '>Only mine</option></select>';
}
$extra .= '<button class="btn btn-sm btn-outline-dark">Filter</button></form>';
?>
<?= tpt_toolbar([
    'new_href'       => site_url('support/create'),
    'new_item_label' => 'Raise Ticket',
    'close_href'     => site_url('dashboard'),
    'extra'          => $extra,
    'auth'           => $auth,
]) ?>

<div class="tabs">
  <div class="tab active">All Tickets</div>
  <div class="spacer"></div>
  <div class="recordnav"><?= (int) ($pager->getTotal() ?: count($rows)) ?> total records</div>
</div>

<?php if ($isAgent && $ratingEnabled && !empty($ratingsSummary['count'])): ?>
<div class="formwrap" style="flex:0 0 auto;">
  <div class="d-flex align-items-center gap-3 flex-wrap">
    <div>
      <div style="font-size:1.4rem;font-weight:600;"><?= esc($ratingsSummary['avg']) ?> ★</div>
      <small class="text-muted"><?= (int) $ratingsSummary['count'] ?> rating<?= (int) $ratingsSummary['count'] === 1 ? '' : 's' ?></small>
    </div>
    <div class="ms-3" style="font-size:.85rem;">
      <?php foreach ([5,4,3,2,1] as $star): $c = $ratingsSummary['breakdown'][$star] ?? 0; ?>
        <div><?= $star ?>★ <span class="text-muted"><?= $c ?></span></div>
      <?php endforeach; ?>
    </div>
  </div>
</div>
<?php endif; ?>

<div class="gridwrap">
  <div class="table-responsive">
    <table class="table grid mb-0" data-tpt-cols="support">
      <thead>
        <tr>
          <th data-col="ticket">#</th>
          <th data-col="subject">Subject</th>
          <th data-col="type">Type</th>
          <th data-col="priority">Priority</th>
          <th data-col="status">Status</th>
          <?php if ($isAgent): ?><th data-col="reporter">Reporter</th><th data-col="assignee">Assignee</th><?php endif; ?>
          <th data-col="updated">Updated</th>
          <?php if ($ratingEnabled): ?><th data-col="rating">Rating</th><?php endif; ?>
        </tr>
      </thead>
      <tbody>
      <?php if (empty($rows)): ?>
        <tr><td colspan="9" class="text-center text-muted py-4">No tickets yet. <a href="<?= site_url('support/create') ?>">Raise one &rarr;</a></td></tr>
      <?php endif; ?>
      <?php foreach ($rows as $r): ?>
        <tr class="row-link" data-href="<?= site_url('support/' . $r['id']) ?>">
          <td data-col="ticket" data-label="#"><a href="<?= site_url('support/' . $r['id']) ?>"><code><?= esc($r['ticket_no']) ?></code></a></td>
          <td data-col="subject" data-label="Subject"><?= esc($r['subject']) ?></td>
          <td data-col="type" data-label="Type"><span class="badge-soft"><?= esc($r['type']) ?></span></td>
          <td data-col="priority" data-label="Priority"><?= esc($r['priority']) ?></td>
          <td data-col="status" data-label="Status"><span class="badge-soft <?= $badgeFor($r['status']) ?>"><?= esc($r['status']) ?></span></td>
          <?php if ($isAgent): ?>
            <td data-col="reporter" data-label="Reporter"><?= esc($r['reporter_name'] ?? '—') ?></td>
            <td data-col="assignee" data-label="Assignee"><?= esc($r['assignee_name'] ?? '—') ?></td>
          <?php endif; ?>
          <td data-col="updated" data-label="Updated"><?= esc(date('d-m H:i', strtotime($r['updated_at']))) ?></td>
          <?php if ($ratingEnabled): ?>
            <td data-col="rating" data-label="Rating"><?= !empty($r['rating']) ? str_repeat('★', (int) $r['rating']) . str_repeat('☆', 5 - (int) $r['rating']) : '—' ?></td>
          <?php endif; ?>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php if (!empty($pager)): ?><div class="mt-3"><?= $pager->links() ?></div><?php endif; ?>
</div>
