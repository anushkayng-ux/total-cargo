<?php
$badgeFor = fn($s) => [
    'Queued'      => 'badge-pending',
    'Sending'     => 'badge-pending',
    'Sent'        => 'badge-issued',
    'Delivered'   => 'badge-paid',
    'Opened'      => 'badge-paid',
    'Clicked'     => 'badge-paid',
    'Failed'      => 'badge-cancelled',
    'Bounced'     => 'badge-cancelled',
    'Complained'  => 'badge-cancelled',
    'Suppressed'  => 'badge-cancelled',
][$s] ?? 'badge-pending';
?>
<h5 class="mb-3">Email Logs</h5>

<form method="get" class="row g-2 mb-3">
  <div class="col-12 col-md-5">
    <input class="form-control form-control-sm" name="q" placeholder="Search to / subject / provider id" value="<?= esc($search) ?>">
  </div>
  <div class="col-6 col-md-3">
    <select name="status" class="form-select form-select-sm">
      <option value="">All statuses</option>
      <?php foreach (['Queued','Sending','Sent','Delivered','Opened','Clicked','Bounced','Complained','Suppressed','Failed'] as $s): ?>
        <option value="<?= $s ?>" <?= $s === $status ? 'selected' : '' ?>><?= $s ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-6 col-md-3">
    <input class="form-control form-control-sm" name="template" placeholder="Template key" value="<?= esc($tpl) ?>">
  </div>
  <div class="col-md-1"><button class="btn btn-sm btn-light w-100">Filter</button></div>
</form>

<div class="card mb-3">
  <div class="table-responsive">
    <table class="table table-sm align-middle mb-0" data-tpt-cols="email-logs">
      <thead><tr><th data-col="id">#</th><th data-col="when">When</th><th data-col="template">Template</th><th data-col="to">To</th><th data-col="subject">Subject</th><th data-col="status">Status</th><th data-col="provider">Provider</th><th data-col="events">Events</th><th data-col="actions"></th></tr></thead>
      <tbody>
      <?php if (empty($rows)): ?>
        <tr><td colspan="9" class="text-center text-muted py-4">No emails logged yet.</td></tr>
      <?php endif; ?>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td data-col="id">#<?= (int) $r['id'] ?></td>
          <td data-col="when"><?= esc(date('d-m H:i', strtotime($r['created_at']))) ?></td>
          <td data-col="template"><code style="font-size:.78rem;"><?= esc($r['template_key'] ?: '—') ?></code></td>
          <td data-col="to"><?= esc($r['to_email']) ?></td>
          <td data-col="subject" style="max-width:300px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= esc($r['subject']) ?></td>
          <td data-col="status"><span class="badge-status <?= $badgeFor($r['status']) ?>"><?= esc($r['status']) ?></span></td>
          <td data-col="provider"><?= esc($r['provider'] ?: '—') ?></td>
          <td data-col="events" style="font-size:.8rem;">
            <?php
              $bits = [];
              if ((int) $r['open_count'] > 0)  $bits[] = '👁 ' . (int) $r['open_count'];
              if ((int) $r['click_count'] > 0) $bits[] = '🔗 ' . (int) $r['click_count'];
              if (!empty($r['bounced_at']))    $bits[] = '⚠ bounce';
              if (!empty($r['complained_at'])) $bits[] = '⛔ spam';
              echo $bits ? esc(implode(' · ', $bits)) : '—';
            ?>
          </td>
          <td data-col="actions"><a href="<?= site_url('email-logs/' . $r['id']) ?>" class="btn btn-sm btn-light">View</a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php if ($pager): ?><div class="mb-3"><?= $pager->links() ?></div><?php endif; ?>

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span>Suppression list (recent 20)</span>
    <form method="post" action="<?= site_url('email-logs/unsubscribes/add') ?>" class="d-flex gap-2">
      <?= csrf_field() ?>
      <input class="form-control form-control-sm" name="email" type="email" placeholder="email to suppress" required>
      <button class="btn btn-sm btn-light">Add</button>
    </form>
  </div>
  <div class="table-responsive">
    <table class="table table-sm mb-0">
      <thead><tr><th>Email</th><th>Source</th><th>Reason</th><th>When</th><th></th></tr></thead>
      <tbody>
      <?php if (empty($unsubs)): ?>
        <tr><td colspan="5" class="text-center text-muted py-3">No suppressed addresses.</td></tr>
      <?php endif; ?>
      <?php foreach ($unsubs as $u): ?>
        <tr>
          <td><?= esc($u['email']) ?></td>
          <td><?= esc($u['source']) ?></td>
          <td><?= esc($u['reason'] ?: '—') ?></td>
          <td><?= esc(date('d-m-Y H:i', strtotime($u['created_at']))) ?></td>
          <td>
            <form method="post" action="<?= site_url('email-logs/unsubscribes/' . $u['id'] . '/remove') ?>" onsubmit="return confirm('Remove suppression?');">
              <?= csrf_field() ?><button class="btn btn-sm btn-light"><i class="bi bi-x-circle"></i></button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
