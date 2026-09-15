<?php
$statusBadge = function (string $s): string {
    $map = [
        'New'              => 'badge-soft',
        'Under Review'     => 'badge-soft',
        'Sent to Purchase' => 'badge-soft badge-warn',
        'Quote Received'   => 'badge-soft badge-warn',
        'Sent to Client'   => 'badge-soft badge-warn',
        'Negotiation'      => 'badge-soft badge-warn',
        'Won'              => 'badge-soft badge-ok',
        'Lost'             => 'badge-soft badge-danger',
        'Closed'           => 'badge-soft',
    ];
    $cls = $map[$s] ?? 'badge-soft';
    return '<span class="' . $cls . '">' . esc($s) . '</span>';
};
$exportQs = http_build_query(array_filter($filters));

$extra = '<a class="btn btn-sm btn-outline-dark" href="' . site_url('leads/export') . ($exportQs ? '?' . $exportQs : '') . '" title="Download CSV"><i class="bi bi-download"></i> Export</a>'
    . '<form class="d-flex align-items-center gap-2 m-0 flex-wrap" method="get" action="' . site_url('leads') . '">'
    . '<input type="text" name="q" class="form-control form-control-sm" placeholder="Search no/name/mobile/city" value="' . esc($filters['search']) . '" style="width:170px;">'
    . '<select name="status" class="form-select form-select-sm" style="width:auto;"><option value="">All Statuses</option>';
foreach ($statuses as $s) {
    $extra .= '<option value="' . esc($s) . '"' . ($filters['status'] === $s ? ' selected' : '') . '>' . esc($s) . '</option>';
}
$extra .= '</select><select name="assignee" class="form-select form-select-sm" style="width:auto;"><option value="">All Assignees</option>';
foreach ($users as $u) {
    $extra .= '<option value="' . (int) $u['id'] . '"' . ((int) $filters['assignee'] === (int) $u['id'] ? ' selected' : '') . '>' . esc($u['name']) . '</option>';
}
$extra .= '</select><select name="source" class="form-select form-select-sm" style="width:auto;"><option value="">All Sources</option>';
foreach ($sources as $s) {
    $extra .= '<option value="' . (int) $s['id'] . '"' . ((int) $filters['source'] === (int) $s['id'] ? ' selected' : '') . '>' . esc($s['source_name']) . '</option>';
}
$extra .= '</select>'
    . '<input type="date" name="from" class="form-control form-control-sm" value="' . esc($filters['from']) . '" style="width:140px;">'
    . '<input type="date" name="to" class="form-control form-control-sm" value="' . esc($filters['to']) . '" style="width:140px;">'
    . '<button class="btn btn-sm btn-outline-dark">Filter</button></form>';

echo tpt_toolbar([
    'new_href'       => site_url('leads/create'),
    'new_item_label' => 'New Lead',
    'close_href'     => site_url('dashboard'),
    'extra'          => $extra,
    'auth'           => $auth,
]);
?>
<div class="tabs" role="tablist">
  <div class="tab active">All Leads</div>
  <div data-tpt-saved-views="leads" style="margin-left:10px;"></div>
  <div class="spacer"></div>
  <div class="recordnav"><?= (int) ($pager->getTotal() ?: count($rows)) ?> total records</div>
</div>

<form method="post" action="<?= site_url('leads/bulk') ?>">
  <?= csrf_field() ?>
  <div class="d-none mb-2 mt-3 mx-3" id="bulkBar">
    <div class="alert alert-info py-2 d-flex flex-wrap align-items-center gap-2 mb-2">
      <span><strong id="bulkCount">0</strong> selected</span>
      <select name="action" class="form-select form-select-sm" style="width:auto;">
        <option value="">With selected…</option>
        <optgroup label="Assign to">
          <?php foreach ($users as $u): ?>
            <option value="assign::<?= (int) $u['id'] ?>">Assign → <?= esc($u['name']) ?></option>
          <?php endforeach; ?>
        </optgroup>
        <optgroup label="Status">
          <?php foreach ($statuses as $s): ?>
            <option value="status::<?= esc($s) ?>">Status → <?= esc($s) ?></option>
          <?php endforeach; ?>
        </optgroup>
      </select>
      <button class="btn btn-sm btn-primary" type="submit" data-confirm="Apply to selected leads?">Apply</button>
    </div>
  </div>

  <div class="gridwrap">
    <div class="table-responsive">
      <table class="table grid mobile-cards mb-0" data-tpt-cols="leads">
        <thead>
          <tr>
            <th style="width:32px;"><input type="checkbox" id="selAllBulk"></th>
            <th data-col="lead_no">Lead No</th>
            <th data-col="date">Date</th>
            <th data-col="client">Client / Company</th>
            <th data-col="mobile">Mobile</th>
            <th data-col="route">Route</th>
            <th data-col="vehicle">Vehicle</th>
            <th data-col="status">Status</th>
            <th data-col="assignee">Assignee</th>
            <th data-col="actions" class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($rows)): ?><tr><td colspan="10" class="text-center text-muted">No leads found.</td></tr><?php endif; ?>
          <?php foreach ($rows as $r): ?>
            <tr class="row-link" data-href="<?= site_url('leads/' . $r['id']) ?>">
              <td><input type="checkbox" name="ids[]" value="<?= (int) $r['id'] ?>" class="bulk-chk"></td>
              <td data-col="lead_no" data-label="Lead No"><a href="<?= site_url('leads/' . $r['id']) ?>"><code><?= esc($r['lead_no']) ?></code></a></td>
              <td data-col="date" data-label="Date"><?= esc(tpt_dt($r['lead_datetime'])) ?></td>
              <td data-col="client" data-label="Client"><?= esc($r['client_company'] ?? ($r['company_name'] ?? '')) ?><br><small class="text-muted"><?= esc($r['client_name']) ?></small></td>
              <td data-col="mobile" data-label="Mobile"><?= esc($r['mobile']) ?></td>
              <td data-col="route" data-label="Route"><?= esc(trim(($r['pickup_city'] ?? '') . ' - ' . ($r['drop_city'] ?? ''), ' -')) ?></td>
              <td data-col="vehicle" data-label="Vehicle"><?= esc($r['vehicle_type_required']) ?></td>
              <td data-col="status" data-label="Status">
                <span class="badge-soft"
                      data-tpt-inline-select
                      data-url="<?= site_url('leads/' . (int) $r['id'] . '/status') ?>"
                      data-field="new_status"
                      data-value="<?= esc($r['current_status']) ?>"
                      data-options='<?= esc(json_encode($statuses), 'attr') ?>'><?= esc($r['current_status']) ?></span>
              </td>
              <td data-col="assignee" data-label="Assignee">
                <?php $assigneeOptions = array_merge([['value' => '', 'label' => '— Unassigned —']], array_map(fn($u) => ['value' => (string)$u['id'], 'label' => $u['name']], $users)); ?>
                <span class="text-muted"
                      data-tpt-inline-select
                      data-url="<?= site_url('leads/' . (int) $r['id'] . '/assign') ?>"
                      data-field="assigned_crm_user_id"
                      data-value="<?= esc((string) ($r['assigned_crm_user_id'] ?? '')) ?>"
                      data-options='<?= esc(json_encode($assigneeOptions), 'attr') ?>'><?= esc($r['assignee_name'] ?? '—') ?></span>
              </td>
              <td data-col="actions" class="text-end" data-label="Actions" style="white-space:nowrap;">
                <a class="btn btn-sm btn-outline-primary" href="<?= site_url('rfq/create?lead_id=' . (int) $r['id']) ?>" title="Create RFQ from this lead"><i class="bi bi-file-earmark-plus"></i> RFQ</a>
                <a class="btn btn-sm btn-light" href="<?= site_url('leads/' . $r['id'] . '/edit') ?>" title="Edit"><i class="bi bi-pencil"></i></a>
                <a class="btn btn-sm btn-light" href="<?= site_url('leads/' . $r['id']) ?>" title="Open"><i class="bi bi-arrow-right"></i></a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php if (!empty($pager)): ?><div class="mt-3"><?= $pager->links() ?></div><?php endif; ?>
  </div>
</form>

<script>
(function () {
  const all = document.getElementById('selAllBulk');
  const bar = document.getElementById('bulkBar');
  const cnt = document.getElementById('bulkCount');
  const chks = () => Array.from(document.querySelectorAll('.bulk-chk'));
  function refresh() {
    const sel = chks().filter(c => c.checked).length;
    cnt.textContent = sel;
    bar.classList.toggle('d-none', sel === 0);
    if (all) all.checked = sel > 0 && sel === chks().length;
  }
  all?.addEventListener('change', () => { chks().forEach(c => c.checked = all.checked); refresh(); });
  chks().forEach(c => c.addEventListener('change', refresh));
})();
</script>
