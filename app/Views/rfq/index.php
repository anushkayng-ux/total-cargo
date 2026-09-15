<?php
$extra = '<form class="d-flex align-items-center gap-2 m-0" method="get" action="' . site_url('rfq') . '">'
    . '<input type="text" name="q" class="form-control form-control-sm" placeholder="Search no/ref/route" value="' . esc($search) . '">'
    . '<select name="status" class="form-select form-select-sm" style="width:auto;"><option value="">All</option>';
foreach ($statuses as $s) {
    $extra .= '<option value="' . esc($s) . '"' . ($status === $s ? ' selected' : '') . '>' . esc($s) . '</option>';
}
$extra .= '</select><button class="btn btn-sm btn-outline-dark">Filter</button></form>';

echo tpt_toolbar([
    'new_href'       => site_url('rfq/create'),
    'new_item_label' => 'New RFQ',
    'close_href'     => site_url('dashboard'),
    'extra'          => $extra,
    'auth'           => $auth,
]);
?>
<div class="tabs" role="tablist">
  <div class="tab active">All RFQs</div>
  <a class="tab" href="<?= site_url('rfq/queue') ?>">Purchase Inbox</a>
  <div class="spacer"></div>
  <div class="recordnav"><?= (int) ($pager->getTotal() ?: count($rows)) ?> total records</div>
</div>

<div class="gridwrap">
  <div class="table-responsive">
    <table class="table grid mobile-cards mb-0" data-tpt-cols="rfq">
      <thead>
        <tr>
          <th data-col="rfq_no">RFQ No</th>
          <th data-col="ref">Masked Ref</th>
          <th data-col="lead">From Lead</th>
          <th data-col="route">Route</th>
          <th data-col="vehicle">Vehicle</th>
          <th data-col="loading">Loading</th>
          <th data-col="status">Status</th>
          <th data-col="actions" class="text-end">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($rows)): ?><tr><td colspan="8" class="text-center text-muted">No RFQs yet.</td></tr><?php endif; ?>
        <?php foreach ($rows as $r): ?>
          <tr class="row-link" data-href="<?= site_url('rfq/' . $r['id']) ?>">
            <td data-col="rfq_no" data-label="RFQ No"><a href="<?= site_url('rfq/' . $r['id']) ?>"><code><?= esc($r['rfq_no']) ?></code></a></td>
            <td data-col="ref" data-label="Masked Ref"><code><?= esc($r['masked_reference']) ?></code></td>
            <td data-col="lead" data-label="Lead"><?= $r['lead_no'] ? '<a href="' . site_url('leads/' . $r['lead_id']) . '"><code>' . esc($r['lead_no']) . '</code></a>' : '—' ?></td>
            <td data-col="route" data-label="Route"><?= esc(trim(($r['pickup_city'] ?? '') . ' → ' . ($r['drop_city'] ?? ''), ' →')) ?></td>
            <td data-col="vehicle" data-label="Vehicle"><?= esc($r['vehicle_type']) ?></td>
            <td data-col="loading" data-label="Loading"><?= esc($r['loading_date']) ?></td>
            <td data-col="status" data-label="Status"><span class="badge-soft"><?= esc($r['status']) ?></span></td>
            <td data-col="actions" class="text-end" data-label="Actions" style="white-space:nowrap;">
              <?php if (($r['status'] ?? '') === 'Selected' || ($r['status'] ?? '') === 'Won'): ?>
                <a class="btn btn-sm btn-outline-primary" href="<?= site_url('bookings/create?rfq_id=' . (int) $r['id']) ?>" title="Convert to Booking"><i class="bi bi-journal-plus"></i> Booking</a>
              <?php endif; ?>
              <a class="btn btn-sm btn-light" href="<?= site_url('rfq/' . $r['id']) ?>" title="Open"><i class="bi bi-arrow-right"></i></a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php if (!empty($pager)): ?><div class="mt-3"><?= $pager->links() ?></div><?php endif; ?>
</div>
