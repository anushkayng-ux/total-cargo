<?php
$f = $filters;
$extra = '<form method="get" class="d-flex align-items-center gap-2 flex-wrap m-0">'
    . '<input class="form-control form-control-sm" style="width:160px;" type="text" name="q" value="' . esc($f['search'] ?? '') . '" placeholder="Vendor, RFQ no, route…">'
    . '<input class="form-control form-control-sm" style="width:110px;" type="text" name="rfq_no" value="' . esc($f['rfqNo'] ?? '') . '" placeholder="RFQ no">'
    . '<select class="form-select form-select-sm" style="width:auto;" name="vendor_id"><option value="">All Vendors</option>';
foreach (($vendors ?? []) as $v) {
    $extra .= '<option value="' . (int) $v['id'] . '"' . ((int) ($f['vendorId'] ?? 0) === (int) $v['id'] ? ' selected' : '') . '>' . esc($v['company_name']) . '</option>';
}
$extra .= '</select>'
    . '<select class="form-select form-select-sm" style="width:auto;" name="selected"><option value="">All Status</option>'
    . '<option value="shortlisted"' . (($f['selected'] ?? '') === 'shortlisted' ? ' selected' : '') . '>Shortlisted</option>'
    . '<option value="final"' . (($f['selected'] ?? '') === 'final' ? ' selected' : '') . '>Awarded</option>'
    . '</select>'
    . '<input class="form-control form-control-sm" style="width:90px;" type="number" step="100" name="min_amount" value="' . esc($f['minAmt'] ?? '') . '" placeholder="Min ₹">'
    . '<input class="form-control form-control-sm" style="width:90px;" type="number" step="100" name="max_amount" value="' . esc($f['maxAmt'] ?? '') . '" placeholder="Max ₹">'
    . '<button class="btn btn-sm btn-outline-dark"><i class="bi bi-funnel"></i> Apply</button>'
    . '<a class="btn btn-sm btn-light" href="' . site_url('quotations') . '"><i class="bi bi-x-circle"></i></a>'
    . '</form>';

echo tpt_toolbar([
    'close_href' => site_url('dashboard'),
    'extra'      => $extra,
    'auth'       => $auth,
]);
?>
<div class="tabs" role="tablist">
  <div class="tab active">All Quotations</div>
  <div class="spacer"></div>
  <div class="recordnav"><?= (int) ($pager->getTotal() ?: count($rows)) ?> total records</div>
</div>

<div class="gridwrap">
  <div class="table-responsive">
    <table class="table grid mobile-cards mb-0" data-tpt-cols="quotations">
      <thead>
        <tr><th data-col="rfq">RFQ</th><th data-col="vendor">Vendor</th><th class="text-end" data-col="amount">Amount</th><th data-col="transit">Transit</th><th data-col="source">Source</th><th data-col="status">Status</th><th data-col="created">Created</th></tr>
      </thead>
      <tbody>
        <?php if (empty($rows)): ?>
          <tr><td colspan="7" class="text-center text-muted py-3">No quotations match the filters.</td></tr>
        <?php endif; ?>
        <?php foreach ($rows as $q): ?>
          <tr class="row-link" data-href="<?= site_url('rfq/' . $q['rfq_id']) ?>">
            <td data-col="rfq" data-label="RFQ"><a href="<?= site_url('rfq/' . $q['rfq_id']) ?>"><code><?= esc($q['rfq_no']) ?></code></a><br>
              <small class="text-muted"><?= esc(trim(($q['pickup_city'] ?? '') . ' - ' . ($q['drop_city'] ?? ''), ' -')) ?></small>
            </td>
            <td data-col="vendor" data-label="Vendor"><?= esc($q['vendor_name']) ?>
              <?= !empty($q['is_preferred'])   ? ' <span class="badge-soft badge-ok">Preferred</span>' : '' ?>
              <?= !empty($q['is_blacklisted']) ? ' <span class="badge-soft badge-danger">Blacklisted</span>' : '' ?>
            </td>
            <td class="text-end" data-col="amount" data-label="Amount">₹<?= number_format((float) $q['quote_amount'], 2) ?></td>
            <td data-col="transit" data-label="Transit"><?= esc($q['transit_days'] ?? '—') ?> d</td>
            <td data-col="source" data-label="Source"><span class="badge-soft"><?= esc($q['response_source']) ?></span></td>
            <td data-col="status" data-label="Status">
              <?php if (!empty($q['is_final_selected'])): ?><span class="badge-soft badge-ok">Awarded</span>
              <?php elseif (!empty($q['is_shortlisted'])): ?><span class="badge-soft badge-warn">Shortlisted</span>
              <?php else: ?><span class="text-muted">—</span><?php endif; ?>
            </td>
            <td data-col="created" data-label="Created"><?= esc(tpt_dt($q['created_at'])) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php if (!empty($pager)): ?><div class="mt-3"><?= $pager->links() ?></div><?php endif; ?>
</div>
