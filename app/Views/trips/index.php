<?php
$exportQs   = http_build_query(array_filter(['q' => $search, 'status' => $status, 'pod' => $pod]));
$tripStatuses = $statuses ?? [];

$extra = '<a class="btn btn-sm btn-outline-dark" href="' . site_url('trips/export') . ($exportQs ? '?' . $exportQs : '') . '" title="Download CSV"><i class="bi bi-download"></i> Export</a>'
    . '<form class="d-flex align-items-center gap-2 m-0 flex-wrap" method="get" action="' . site_url('trips') . '">'
    . '<input type="text" name="q" class="form-control form-control-sm" placeholder="Search no/vehicle/driver/client" value="' . esc($search) . '" style="width:190px;">'
    . '<select name="status" class="form-select form-select-sm" style="width:auto;"><option value="">All</option>';
foreach ($tripStatuses as $s) {
    $extra .= '<option value="' . esc($s) . '"' . ($status === $s ? ' selected' : '') . '>' . esc($s) . '</option>';
}
$extra .= '</select>'
    . '<label class="form-check form-check-inline m-0 align-self-center" style="font-size:.85rem;">'
    . '<input type="checkbox" class="form-check-input" name="pod" value="pending" ' . ($pod === 'pending' ? 'checked' : '') . '> POD pending</label>'
    . '<button class="btn btn-sm btn-outline-dark">Filter</button></form>';

echo tpt_toolbar([
    'close_href' => site_url('dashboard'),
    'extra'      => $extra,
    'auth'       => $auth,
]);
?>
<div class="tabs">
  <div class="tab active">All Trips</div>
  <div data-tpt-saved-views="trips" style="margin-left:10px;"></div>
  <div class="spacer"></div>
  <div class="recordnav"><?= (int) ($pager->getTotal() ?: count($rows)) ?> total records</div>
</div>

<form method="post" action="<?= site_url('trips/bulk') ?>" id="bulkForm">
  <?= csrf_field() ?>
  <div class="d-none mb-2 mt-3 mx-3" id="bulkBar">
    <div class="alert alert-info py-2 d-flex flex-wrap align-items-center gap-2 mb-2">
      <span><strong id="bulkCount">0</strong> selected</span>
      <select name="action" class="form-select form-select-sm" style="width:auto;">
        <option value="">With selected…</option>
        <?php foreach ($tripStatuses as $s): ?>
          <option value="status::<?= esc($s) ?>">Set status → <?= esc($s) ?></option>
        <?php endforeach; ?>
      </select>
      <button class="btn btn-sm btn-primary" type="submit" data-confirm="Apply to selected trips?">Apply</button>
    </div>
  </div>

  <div class="gridwrap">
    <div class="table-responsive">
      <table class="table grid mobile-cards mb-0" data-tpt-cols="trips">
        <thead>
          <tr>
            <th style="width:32px;"><input type="checkbox" id="selAllBulk"></th>
            <th data-col="trip">Trip</th>
            <th data-col="booking">Booking</th>
            <th data-col="client">Client</th>
            <th data-col="vendor">Vendor</th>
            <th data-col="vehicle">Vehicle</th>
            <th data-col="driver">Driver</th>
            <th data-col="status">Status</th>
            <th data-col="pod">POD</th>
            <th data-col="actions"></th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($rows)): ?><tr><td colspan="10" class="text-center text-muted">No trips.</td></tr><?php endif; ?>
          <?php foreach ($rows as $r): ?>
            <tr class="row-link" data-href="<?= site_url('trips/' . $r['id']) ?>">
              <td><input type="checkbox" name="ids[]" value="<?= (int) $r['id'] ?>" class="bulk-chk"></td>
              <td data-col="trip" data-label="Trip"><a href="<?= site_url('trips/' . $r['id']) ?>"><code><?= esc($r['trip_no']) ?></code></a></td>
              <td data-col="booking" data-label="Booking"><a href="<?= site_url('bookings/' . $r['booking_id']) ?>"><code><?= esc($r['booking_no']) ?></code></a></td>
              <td data-col="client" data-label="Client"><?= esc($r['client_company'] ?? '—') ?></td>
              <td data-col="vendor" data-label="Vendor"><?= esc($r['vendor_company'] ?? '—') ?></td>
              <td data-col="vehicle" data-label="Vehicle"><code><?= esc($r['vehicle_number']) ?></code></td>
              <td data-col="driver" data-label="Driver"><?= esc($r['driver_name']) ?><?= $r['driver_mobile'] ? '<br><small class="text-muted">' . esc($r['driver_mobile']) . '</small>' : '' ?></td>
              <td data-col="status" data-label="Status">
                <span class="badge-soft"
                      data-tpt-inline-select
                      data-url="<?= site_url('trips/' . (int) $r['id'] . '/status') ?>"
                      data-field="new_status"
                      data-value="<?= esc($r['current_status']) ?>"
                      data-options='<?= esc(json_encode($tripStatuses), 'attr') ?>'><?= esc($r['current_status']) ?></span>
              </td>
              <td data-col="pod" data-label="POD">
                <?php if ($r['pod_status'] === 'Received'): ?>
                  <span class="badge-soft badge-ok">Received</span>
                <?php else: ?>
                  <span class="badge-soft badge-warn">Pending</span>
                <?php endif; ?>
              </td>
              <td data-col="actions" class="text-end" style="white-space:nowrap;">
                <?php if (!empty($r['lr_no'])): ?>
                  <span class="badge-soft" title="Docket / LR"><code><?= esc($r['lr_no']) ?></code></span>
                <?php endif; ?>
                <a class="btn btn-sm btn-primary" href="<?= site_url('trips/' . $r['id']) ?>" title="Open trip"><i class="bi bi-arrow-right"></i> Open</a>
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
