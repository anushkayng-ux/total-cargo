<?php
$extra = '<form class="d-flex align-items-center gap-2 m-0" method="get" action="' . site_url('vehicles') . '">'
    . '<input type="text" name="q" class="form-control form-control-sm" placeholder="Search number/type/RC" value="' . esc($search) . '">'
    . '<button class="btn btn-sm btn-outline-dark"><i class="bi bi-search"></i></button></form>';

echo tpt_toolbar([
    'new_href'       => site_url('vehicles/create'),
    'new_item_label' => 'New Vehicle',
    'close_href'     => site_url('dashboard'),
    'extra'          => $extra,
    'auth'           => $auth,
]);
?>
<div class="tabs">
  <div class="tab active">All Vehicles</div>
  <div class="spacer"></div>
  <div class="recordnav"><?= (int) ($pager->getTotal() ?: count($rows)) ?> total records</div>
</div>

<div class="gridwrap">
  <div class="table-responsive">
    <table class="table grid mb-0" data-tpt-cols="vehicles">
      <thead>
        <tr><th data-col="number">Vehicle No</th><th data-col="type">Type</th><th data-col="vendor">Vendor</th><th data-col="gps">GPS</th><th data-col="insurance-expiry">Insurance Exp</th><th data-col="fitness-expiry">Fitness Exp</th><th data-col="status">Status</th><th class="text-end" data-col="actions">Actions</th></tr>
      </thead>
      <tbody>
        <?php if (empty($rows)): ?><tr><td colspan="8" class="text-center text-muted">No vehicles yet.</td></tr><?php endif; ?>
        <?php foreach ($rows as $r): ?>
          <tr class="row-link" data-href="<?= site_url('vehicles/' . $r['id']) ?>">
            <td data-col="number" data-label="Vehicle No"><code><?= esc($r['vehicle_number']) ?></code></td>
            <td data-col="type" data-label="Type"><?= esc($r['vehicle_type']) ?></td>
            <td data-col="vendor" data-label="Vendor"><?= esc($r['vendor_name'] ?? '') ?></td>
            <td data-col="gps" data-label="GPS">
              <?php
                $gpsP = $r['gps_provider'] ?? 'none';
                $cls  = $gpsP === 'none' ? 'badge-soft' : 'badge-soft badge-ok';
              ?>
              <span class="<?= $cls ?>" style="font-size:.7rem;"><?= esc(\App\Models\VehicleModel::GPS_PROVIDERS[$gpsP] ?? $gpsP) ?></span>
              <?php if (!empty($r['gps_tracking_url'])): ?>
                <a href="<?= esc($r['gps_tracking_url']) ?>" target="_blank" rel="noopener" title="Open vendor tracker"><i class="bi bi-box-arrow-up-right"></i></a>
              <?php endif; ?>
            </td>
            <td data-col="insurance-expiry" data-label="Insurance Exp"><?= esc($r['insurance_expiry']) ?></td>
            <td data-col="fitness-expiry" data-label="Fitness Exp"><?= esc($r['fitness_expiry']) ?></td>
            <td data-col="status" data-label="Status"><?= (int) $r['status'] === 1 ? '<span class="badge-soft badge-ok">Active</span>' : '<span class="badge-soft badge-danger">Inactive</span>' ?></td>
            <td class="text-end" data-col="actions" data-label="Actions">
              <a class="btn btn-sm btn-light" href="<?= site_url('vehicles/' . $r['id'] . '/edit') ?>"><i class="bi bi-pencil"></i></a>
              <form class="d-inline" method="post" action="<?= site_url('vehicles/' . $r['id'] . '/delete') ?>" data-confirm="Delete this vehicle?">
                <?= csrf_field() ?>
                <button class="btn btn-sm btn-light" type="submit"><i class="bi bi-trash"></i></button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php if (!empty($pager)): ?><div class="mt-3"><?= $pager->links() ?></div><?php endif; ?>
</div>
