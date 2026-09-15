<?php
$exportQs = http_build_query(array_filter(['q' => $search, 'status' => $status, 'due' => $due]));

$extra = '<a class="btn btn-sm btn-outline-dark" href="' . site_url('invoices/export') . ($exportQs ? '?' . $exportQs : '') . '" title="Download CSV"><i class="bi bi-download"></i> Export</a>'
    . '<a class="btn btn-sm btn-outline-success" href="' . site_url('invoices/consolidated') . '" title="Bill multiple LRs together"><i class="bi bi-collection"></i> Combined</a>'
    . '<form class="d-flex align-items-center gap-2 m-0 flex-wrap" method="get" action="' . site_url('invoices') . '">'
    . '<input type="text" name="q" class="form-control form-control-sm" placeholder="Search no/client/IRN" value="' . esc($search) . '" style="width:170px;">'
    . '<select name="status" class="form-select form-select-sm" style="width:auto;"><option value="">All</option>';
foreach ($statuses as $s) {
    $extra .= '<option value="' . esc($s) . '"' . ($status === $s ? ' selected' : '') . '>' . esc($s) . '</option>';
}
$extra .= '</select>'
    . '<label class="form-check form-check-inline m-0 align-self-center" style="font-size:.85rem;">'
    . '<input type="checkbox" class="form-check-input" name="due" value="overdue" ' . ($due === 'overdue' ? 'checked' : '') . '> Overdue</label>'
    . '<button class="btn btn-sm btn-outline-dark">Filter</button></form>';

echo tpt_toolbar([
    'new_href'       => site_url('invoices/create'),
    'new_item_label' => 'New Invoice',
    'close_href'     => site_url('dashboard'),
    'extra'          => $extra,
    'auth'           => $auth,
]);
?>
<div class="tabs" role="tablist">
  <div class="tab active">All Invoices</div>
  <div data-tpt-saved-views="invoices" style="margin-left:10px;"></div>
  <div class="spacer"></div>
  <div class="recordnav"><?= (int) ($pager->getTotal() ?: count($rows)) ?> total records</div>
</div>

<form method="post" action="<?= site_url('invoices/bulk') ?>" id="bulkForm">
  <?= csrf_field() ?>
  <div class="d-none mb-2 mt-3 mx-3" id="bulkBar">
    <div class="alert alert-info py-2 d-flex flex-wrap align-items-center gap-2 mb-2">
      <span><strong id="bulkCount">0</strong> selected</span>
      <select name="action" class="form-select form-select-sm" style="width:auto;">
        <option value="">With selected…</option>
        <option value="remind">Send payment reminder (WhatsApp)</option>
        <option value="remind_email">Send payment reminder (Email)</option>
      </select>
      <button class="btn btn-sm btn-primary" type="submit" data-confirm="Apply to selected invoices?">Apply</button>
    </div>
  </div>

  <div class="gridwrap">
    <div class="table-responsive">
      <table class="table grid mobile-cards mb-0" data-tpt-cols="invoices">
        <thead>
          <tr>
            <th style="width:32px;"><input type="checkbox" id="selAllBulk"></th>
            <th data-col="no">No</th>
            <th data-col="date">Date</th>
            <th data-col="client">Client</th>
            <th data-col="booking">Booking</th>
            <th data-col="trip" data-col-default="hidden">Trip</th>
            <th data-col="total" class="text-end">Total</th>
            <th data-col="received" class="text-end" data-col-default="hidden">Received</th>
            <th data-col="balance" class="text-end">Balance</th>
            <th data-col="due">Due</th>
            <th data-col="status">Status</th>
            <th data-col="irn" data-col-default="hidden">IRN</th>
            <th data-col="actions" data-col-default="hidden"></th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($rows)): ?><tr><td colspan="13" class="text-center text-muted">No invoices.</td></tr><?php endif; ?>
          <?php foreach ($rows as $r):
            $overdue = !empty($r['due_date']) && $r['due_date'] < date('Y-m-d') && (float) $r['balance_due'] > 0.01;
          ?>
            <tr class="row-link" data-href="<?= site_url('invoices/' . $r['id']) ?>">
              <td><input type="checkbox" name="ids[]" value="<?= (int) $r['id'] ?>" class="bulk-chk"></td>
              <td data-col="no" data-label="No"><a href="<?= site_url('invoices/' . $r['id']) ?>"><code><?= esc($r['invoice_no']) ?></code></a></td>
              <td data-col="date" data-label="Date"><?= esc($r['invoice_date']) ?></td>
              <td data-col="client" data-label="Client"><?= esc($r['client_company']) ?></td>
              <td data-col="booking" data-label="Booking"><?= $r['booking_no'] ? '<code>' . esc($r['booking_no']) . '</code>' : '—' ?></td>
              <td data-col="trip" data-label="Trip"><?= $r['trip_no'] ? '<code>' . esc($r['trip_no']) . '</code>' : '—' ?></td>
              <td data-col="total" class="text-end" data-label="Total">₹<?= number_format((float) $r['total_amount'], 2) ?></td>
              <td data-col="received" class="text-end" data-label="Received">₹<?= number_format((float) $r['amount_received'], 2) ?></td>
              <td data-col="balance" class="text-end" data-label="Balance"><strong>₹<?= number_format((float) $r['balance_due'], 2) ?></strong></td>
              <td data-col="due" data-label="Due">
                <?= esc($r['due_date'] ?? '—') ?>
                <?= $overdue ? ' <span class="badge-soft badge-danger">Overdue</span>' : '' ?>
              </td>
              <td data-col="status" data-label="Status">
                <?php
                  $cls = match ((string) $r['invoice_status']) {
                      'Paid'           => 'badge-soft badge-ok',
                      'Partially Paid' => 'badge-soft badge-warn',
                      'Cancelled'      => 'badge-soft badge-danger',
                      default          => 'badge-soft',
                  };
                ?>
                <span class="<?= $cls ?>"><?= esc($r['invoice_status']) ?></span>
              </td>
              <td data-col="irn" data-label="IRN"><?= $r['irn_no'] ? '<span class="badge-soft badge-ok">Generated</span>' : '—' ?></td>
              <td data-col="actions" class="text-end"><a class="btn btn-sm btn-light" href="<?= site_url('invoices/' . $r['id']) ?>"><i class="bi bi-eye"></i></a></td>
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
