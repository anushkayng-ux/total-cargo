<?php
$extra = '<form class="d-flex align-items-center gap-2 m-0" method="get" action="' . site_url('drivers') . '">'
    . '<input type="text" name="q" class="form-control form-control-sm" placeholder="Search name/mobile/licence" value="' . esc($search) . '">'
    . '<button class="btn btn-sm btn-outline-dark"><i class="bi bi-search"></i></button></form>';

echo tpt_toolbar([
    'new_href'       => site_url('drivers/create'),
    'new_item_label' => 'New Driver',
    'close_href'     => site_url('dashboard'),
    'extra'          => $extra,
    'auth'           => $auth,
]);
?>
<div class="tabs">
  <div class="tab active">All Drivers</div>
  <div class="spacer"></div>
  <div class="recordnav"><?= (int) ($pager->getTotal() ?: count($rows)) ?> total records</div>
</div>

<div class="gridwrap">
  <div class="table-responsive">
    <table class="table grid mb-0" data-tpt-cols="drivers">
      <thead>
        <tr><th data-col="name">Name</th><th data-col="mobile">Mobile</th><th data-col="vendor">Vendor</th><th data-col="license">License</th><th data-col="expiry">Expiry</th><th data-col="status">Status</th><th class="text-end" data-col="actions">Actions</th></tr>
      </thead>
      <tbody>
        <?php if (empty($rows)): ?><tr><td colspan="7" class="text-center text-muted">No drivers yet.</td></tr><?php endif; ?>
        <?php foreach ($rows as $r): ?>
          <tr class="row-link" data-href="<?= site_url('drivers/' . $r['id']) ?>">
            <td data-col="name" data-label="Name"><?= esc($r['driver_name']) ?></td>
            <td data-col="mobile" data-label="Mobile"><?= esc($r['mobile']) ?></td>
            <td data-col="vendor" data-label="Vendor"><?= esc($r['vendor_name'] ?? '') ?></td>
            <td data-col="license" data-label="License"><code><?= esc($r['license_no']) ?></code></td>
            <td data-col="expiry" data-label="Expiry"><?= esc($r['license_expiry']) ?></td>
            <td data-col="status" data-label="Status">
              <?= (int) $r['status'] === 1 ? '<span class="badge-soft badge-ok">Active</span>' : '<span class="badge-soft badge-danger">Inactive</span>' ?>
            </td>
            <td class="text-end" data-col="actions" data-label="Actions">
              <a class="btn btn-sm btn-light" href="<?= site_url('drivers/' . $r['id'] . '/edit') ?>"><i class="bi bi-pencil"></i></a>
              <form class="d-inline" method="post" action="<?= site_url('drivers/' . $r['id'] . '/delete') ?>" data-confirm="Delete this driver?">
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
