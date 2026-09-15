<?php
$f = $filters;
$extra = '<form method="get" class="d-flex align-items-center gap-2 flex-wrap m-0">'
    . '<input class="form-control form-control-sm" style="width:180px;" type="text" name="q" value="' . esc($f['search'] ?? '') . '" placeholder="Reference, vendor, bill no…">'
    . '<select class="form-select form-select-sm" style="width:auto;" name="mode"><option value="">All Modes</option>';
foreach (['NEFT','RTGS','IMPS','UPI','Cheque','Cash','Other'] as $m) {
    $extra .= '<option' . (($f['mode'] ?? '') === $m ? ' selected' : '') . '>' . $m . '</option>';
}
$extra .= '</select>'
    . '<input class="form-control form-control-sm" style="width:140px;" type="date" name="from" value="' . esc($f['from'] ?? '') . '">'
    . '<input class="form-control form-control-sm" style="width:140px;" type="date" name="to" value="' . esc($f['to'] ?? '') . '">'
    . '<button class="btn btn-sm btn-outline-dark">Apply</button>'
    . '<a class="btn btn-sm btn-light" href="' . site_url('vendor-payments') . '">Clear</a>'
    . '</form>';

echo tpt_toolbar([
    'close_href' => site_url('dashboard'),
    'extra'      => $extra,
    'auth'       => $auth,
]);
?>
<div class="tabs" role="tablist">
  <div class="tab active">All Vendor Payments</div>
  <div class="spacer"></div>
  <div class="recordnav"><?= (int) ($pager->getTotal() ?: count($rows)) ?> total records</div>
</div>

<div class="gridwrap">
  <div class="table-responsive">
    <table class="table grid mobile-cards mb-0" data-tpt-cols="vendor-payments">
      <thead><tr><th data-col="date">Date</th><th data-col="vendor">Vendor</th><th data-col="bill">Bill</th><th class="text-end" data-col="amount">Amount</th><th data-col="mode">Mode</th><th data-col="reference">Reference</th><th data-col="actions"></th></tr></thead>
      <tbody>
        <?php if (empty($rows)): ?><tr><td colspan="7" class="text-center text-muted">No payments.</td></tr><?php endif; ?>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td data-col="date" data-label="Date"><?= esc($r['payment_date']) ?></td>
            <td data-col="vendor" data-label="Vendor"><?= esc($r['vendor_company']) ?></td>
            <td data-col="bill" data-label="Bill"><?= $r['bill_no'] ? '<a href="' . site_url('vendor-bills/' . $r['vendor_bill_id']) . '"><code>' . esc($r['bill_no']) . '</code></a>' : '—' ?></td>
            <td class="text-end" data-col="amount" data-label="Amount">₹<?= number_format((float) $r['amount_paid'], 2) ?></td>
            <td data-col="mode" data-label="Mode"><?= esc($r['payment_mode']) ?></td>
            <td data-col="reference" data-label="Ref"><?= esc($r['reference_no']) ?></td>
            <td class="text-end" data-col="actions">
              <form method="post" action="<?= site_url('vendor-payments/' . $r['id'] . '/delete') ?>" class="d-inline" data-confirm="Delete?">
                <?= csrf_field() ?><button class="btn btn-sm btn-light"><i class="bi bi-trash"></i></button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php if (!empty($pager)): ?><div class="mt-3"><?= $pager->links() ?></div><?php endif; ?>
</div>
