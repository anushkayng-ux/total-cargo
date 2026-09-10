<?php
$badgeFor = fn ($s) => [
    'Open' => 'badge-warn', 'In Progress' => 'badge-warn',
    'Resolved' => 'badge-ok', 'Closed' => 'badge-ok',
    'Reopened' => 'badge-danger',
][$s] ?? 'badge-soft';
?>
<div class="d-flex align-items-center mb-3 gap-2 flex-wrap">
  <h5 class="m-0">Support</h5>
  <a href="<?= site_url('support/create') ?>" class="btn btn-sm btn-primary ms-auto"><i class="bi bi-plus-lg"></i> Raise ticket</a>
</div>

<?php if ($isAgent && $ratingEnabled && !empty($ratingsSummary['count'])): ?>
  <div class="card mb-3"><div class="card-body d-flex align-items-center gap-3 flex-wrap">
    <div>
      <div style="font-size:1.4rem;font-weight:600;"><?= esc($ratingsSummary['avg']) ?> ★</div>
      <small class="text-muted"><?= (int) $ratingsSummary['count'] ?> rating<?= (int) $ratingsSummary['count'] === 1 ? '' : 's' ?></small>
    </div>
    <div class="ms-3" style="font-size:.85rem;">
      <?php foreach ([5,4,3,2,1] as $star): $c = $ratingsSummary['breakdown'][$star] ?? 0; ?>
        <div><?= $star ?>★ <span class="text-muted"><?= $c ?></span></div>
      <?php endforeach; ?>
    </div>
  </div></div>
<?php endif; ?>

<form method="get" class="row g-2 mb-3">
  <div class="col-md-4">
    <input class="form-control form-control-sm" name="q" placeholder="Search subject / ticket no" value="<?= esc($search) ?>">
  </div>
  <div class="col-6 col-md-2">
    <select class="form-select form-select-sm" name="status">
      <option value="">All statuses</option>
      <?php foreach (\App\Models\SupportTicketModel::STATUSES as $s): ?>
        <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= $s ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-6 col-md-2">
    <select class="form-select form-select-sm" name="type">
      <option value="">All types</option>
      <?php foreach (\App\Models\SupportTicketModel::TYPES as $t): ?>
        <option value="<?= $t ?>" <?= $type === $t ? 'selected' : '' ?>><?= $t ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <?php if ($isAgent): ?>
  <div class="col-md-2">
    <select class="form-select form-select-sm" name="mine">
      <option value="">All tickets</option>
      <option value="1" <?= $mine === '1' ? 'selected' : '' ?>>Only mine</option>
    </select>
  </div>
  <?php endif; ?>
  <div class="col-md-2"><button class="btn btn-sm btn-light w-100">Filter</button></div>
</form>

<div class="card">
  <div class="table-responsive">
    <table class="table table-sm align-middle mb-0">
      <thead>
        <tr><th>#</th><th>Subject</th><th>Type</th><th>Priority</th><th>Status</th>
        <?php if ($isAgent): ?><th>Reporter</th><th>Assignee</th><?php endif; ?>
        <th>Updated</th><?php if ($ratingEnabled): ?><th>Rating</th><?php endif; ?></tr>
      </thead>
      <tbody>
      <?php if (empty($rows)): ?>
        <tr><td colspan="9" class="text-center text-muted py-4">No tickets yet. <a href="<?= site_url('support/create') ?>">Raise one →</a></td></tr>
      <?php endif; ?>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><a href="<?= site_url('support/' . $r['id']) ?>"><code><?= esc($r['ticket_no']) ?></code></a></td>
          <td><?= esc($r['subject']) ?></td>
          <td><span class="badge-soft"><?= esc($r['type']) ?></span></td>
          <td><?= esc($r['priority']) ?></td>
          <td><span class="badge-soft <?= $badgeFor($r['status']) ?>"><?= esc($r['status']) ?></span></td>
          <?php if ($isAgent): ?>
            <td><?= esc($r['reporter_name'] ?? '—') ?></td>
            <td><?= esc($r['assignee_name'] ?? '—') ?></td>
          <?php endif; ?>
          <td><?= esc(date('d-m H:i', strtotime($r['updated_at']))) ?></td>
          <?php if ($ratingEnabled): ?>
            <td><?= !empty($r['rating']) ? str_repeat('★', (int) $r['rating']) . str_repeat('☆', 5 - (int) $r['rating']) : '—' ?></td>
          <?php endif; ?>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php if (!empty($pager)): ?><div class="mt-3"><?= $pager->links() ?></div><?php endif; ?>
