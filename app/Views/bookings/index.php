<?php
$exportQs = http_build_query(array_filter(['q' => $search, 'status' => $status]));
$extra = '<a class="btn btn-sm btn-outline-dark" href="' . site_url('bookings/export') . ($exportQs ? '?' . $exportQs : '') . '"><i class="bi bi-download"></i> Export</a>'
  . '<form class="d-flex align-items-center gap-2 m-0" method="get" action="' . site_url('bookings') . '">'
  . '<input type="text" name="q" class="form-control form-control-sm" placeholder="Search no/client/route" value="' . esc($search) . '">'
  . '<select name="status" class="form-select form-select-sm">'
  . '<option value="">All</option>';
foreach ($statuses as $s) {
    $extra .= '<option value="' . esc($s) . '"' . ($status === $s ? ' selected' : '') . '>' . esc($s) . '</option>';
}
$extra .= '</select><button class="btn btn-sm btn-outline-dark">Filter</button></form>';

echo tpt_toolbar([
    'new_href'       => site_url('bookings/create'),
    'new_item_label' => 'New Booking',
    'close_href'     => site_url('dashboard'),
    'extra'          => $extra,
    'auth'           => $auth,
]);
?>
<div class="tabs">
  <div class="tab active">All Bookings</div>
  <div data-tpt-saved-views="bookings" style="margin-left:10px;"></div>
  <div class="spacer"></div>
  <div class="recordnav"><?= (int) ($pager->getTotal() ?: count($rows)) ?> total records</div>
</div>

<form method="post" action="<?= site_url('bookings/bulk') ?>">
  <?= csrf_field() ?>
  <div class="d-none mb-2 mt-3 mx-3" id="bulkBar">
    <div class="alert alert-info py-2 d-flex flex-wrap align-items-center gap-2 mb-2">
      <span><strong id="bulkCount">0</strong> selected</span>
      <select name="action" class="form-select form-select-sm" style="width:auto;">
        <option value="">With selected…</option>
        <option value="cancel">Cancel bookings</option>
        <option value="delete">Delete bookings (soft)</option>
      </select>
      <button class="btn btn-sm btn-primary" type="submit" data-confirm="Apply to selected bookings?">Apply</button>
    </div>
  </div>

  <div class="gridwrap">
    <div class="table-responsive">
      <table class="table grid mobile-cards mb-0" data-tpt-cols="bookings">
        <thead>
          <tr>
            <th style="width:32px;"><input type="checkbox" id="selAllBulk"></th>
            <th data-col="booking">Booking</th>
            <th data-col="client">Client</th>
            <th data-col="vendor" data-col-default="hidden">Vendor</th>
            <th data-col="route">Route</th>
            <th data-col="vehicle" data-col-default="hidden">Vehicle</th>
            <th data-col="loading" data-col-default="hidden">Loading</th>
            <th data-col="buy" class="text-end" data-col-default="hidden">Buy</th>
            <th data-col="sell" class="text-end" data-col-default="hidden">Sell</th>
            <th data-col="margin" class="text-end">Margin</th>
            <th data-col="status">Status</th>
            <th data-col="actions" data-col-default="hidden"></th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($rows)): ?><tr><td colspan="12" class="text-center text-muted">No bookings.</td></tr><?php endif; ?>
          <?php foreach ($rows as $r): ?>
            <tr class="row-link" data-href="<?= site_url('bookings/' . $r['id']) ?>">
              <td><input type="checkbox" name="ids[]" value="<?= (int) $r['id'] ?>" class="bulk-chk"></td>
              <td data-col="booking" data-label="Booking"><a href="<?= site_url('bookings/' . $r['id']) ?>"><code><?= esc($r['booking_no']) ?></code></a>
                <?php if ($r['lead_no']): ?><br><small class="text-muted">Lead <?= esc($r['lead_no']) ?></small><?php endif; ?>
              </td>
              <td data-col="client" data-label="Client"><?= esc($r['client_company'] ?? '—') ?></td>
              <td data-col="vendor" data-label="Vendor"><?= esc($r['vendor_company'] ?? '—') ?></td>
              <td data-col="route" data-label="Route"><?= esc(tpt_route($r['route_text'] ?? "")) ?></td>
              <td data-col="vehicle" data-label="Vehicle"><?= esc($r['vehicle_type']) ?></td>
              <td data-col="loading" data-label="Loading"><?= esc($r['loading_date']) ?></td>
              <td data-col="buy" class="text-end" data-label="Buy">₹<?= number_format((float) $r['final_buy_rate'], 0) ?></td>
              <td data-col="sell" class="text-end" data-label="Sell">₹<?= number_format((float) $r['final_sell_rate'], 0) ?></td>
              <td data-col="margin" class="text-end" data-label="Margin">₹<?= number_format((float) $r['margin_amount'], 0) ?></td>
              <td data-col="status" data-label="Status"><span class="badge-soft"><?= esc($r['booking_status']) ?></span></td>
              <td data-col="actions" class="text-end" style="white-space:nowrap;">
                <?php $bs = (string) ($r['booking_status'] ?? ''); ?>
                <?php if (in_array($bs, ['Pending','Draft'], true)): ?>
                  <a class="btn btn-sm btn-outline-primary" href="<?= site_url('bookings/' . $r['id']) ?>" title="Open to confirm"><i class="bi bi-check2-circle"></i> Confirm</a>
                <?php elseif ($bs === 'Approved'): ?>
                  <form method="post" action="<?= site_url('bookings/' . $r['id'] . '/handover') ?>" class="d-inline" onsubmit="return confirm('Create trip and hand over?');">
                    <?= csrf_field() ?>
                    <button class="btn btn-sm btn-primary" title="Handover → Trip"><i class="bi bi-arrow-right-circle"></i> Trip</button>
                  </form>
                <?php endif; ?>
                <a class="btn btn-sm btn-light" href="<?= site_url('bookings/' . $r['id'] . '/edit') ?>" title="Edit"><i class="bi bi-pencil"></i></a>
                <a class="btn btn-sm btn-light" href="<?= site_url('bookings/' . $r['id']) ?>" title="Open"><i class="bi bi-arrow-right"></i></a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</form>

<?php if (!empty($pager)): ?><div class="mt-3"><?= $pager->links() ?></div><?php endif; ?>

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
