<?php
// Map vendor_type to a badge colour class for fast visual scanning.
$typeBadgeClass = static function (?string $t): string {
    $k = strtolower((string) $t);
    if ($k === '') return '';
    if (str_contains($k, 'commission') && str_contains($k, 'fleet')) return 'badge-mixed';
    if (str_contains($k, 'broker') && str_contains($k, 'fleet'))     return 'badge-mixed';
    if (str_contains($k, 'broker'))      return 'badge-broker';
    if (str_contains($k, 'fleet'))       return 'badge-fleet';
    if (str_contains($k, 'contractor'))  return 'badge-contractor';
    if (str_contains($k, 'commission') || str_contains($k, 'agent')) return 'badge-agent';
    return '';
};
?>
<?php
$extra = '<a class="btn btn-sm btn-outline-dark" href="' . site_url('import/vendors') . '" title="Bulk import from Excel/CSV"><i class="bi bi-upload"></i> Import</a>'
    . '<form class="d-flex align-items-center gap-2 m-0" method="get" action="' . site_url('vendors') . '">'
    . '<input type="text" name="q" class="form-control form-control-sm" placeholder="Search name / mobile / code / type" value="' . esc($search) . '" style="min-width:220px;">'
    . '<button class="btn btn-sm btn-outline-dark"><i class="bi bi-search"></i></button></form>';

echo tpt_toolbar([
    'new_href'       => site_url('vendors/create'),
    'new_item_label' => 'New Vendor',
    'close_href'     => site_url('dashboard'),
    'extra'          => $extra,
    'auth'           => $auth,
]);
?>
<div class="tabs" role="tablist">
  <div class="tab active">All Vendors</div>
  <div class="spacer"></div>
  <div class="recordnav"><?= (int) ($pager->getTotal() ?: count($rows)) ?> total records</div>
</div>

<div class="gridwrap">
  <div class="table-responsive">
    <table class="table grid table-hover align-middle mb-0" data-tpt-cols="vendors">
      <thead class="table-light">
        <tr>
          <th data-col="code" style="width:130px;">Code</th>
          <th data-col="company">Company</th>
          <th data-col="type">Type</th>
          <th data-col="contact">Primary contact</th>
          <th data-col="mobile">Mobile / WA</th>
          <th data-col="city">City</th>
          <th data-col="flags">Flags</th>
          <th data-col="status">Status</th>
          <th data-col="actions" class="text-end" style="width:120px;">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($rows)): ?>
          <tr><td colspan="9" class="text-center text-muted py-4">
            <i class="bi bi-inbox" style="font-size:1.8rem;opacity:.4;"></i>
            <div class="mt-1">No vendors yet — add one or import a sheet.</div>
          </td></tr>
        <?php endif; ?>
        <?php foreach ($rows as $r):
          $primaryMobile = $r['primary_contact_mobile'] ?: $r['mobile'];
          $tCls = $typeBadgeClass($r['vendor_type'] ?? '');
        ?>
          <tr class="row-link" data-href="<?= site_url('vendors/' . $r['id']) ?>">
            <td data-col="code" data-label="Code"><code class="text-dark"><?= esc($r['vendor_code']) ?></code></td>

            <td data-col="company" data-label="Company" class="cell-stack">
              <strong><?= esc($r['company_name']) ?></strong>
              <?php if (!empty($r['owner_name'])): ?>
                <div class="secondary"><i class="bi bi-person"></i> <?= esc($r['owner_name']) ?></div>
              <?php endif; ?>
            </td>

            <td data-col="type" data-label="Type">
              <?php if (!empty($r['vendor_type'])): ?>
                <span class="badge-soft <?= $tCls ?>"><?= esc($r['vendor_type']) ?></span>
              <?php else: ?>
                <span class="text-muted">—</span>
              <?php endif; ?>
            </td>

            <td data-col="contact" data-label="Primary contact" class="cell-stack">
              <?php if (!empty($r['primary_contact_name'])): ?>
                <?= esc($r['primary_contact_name']) ?>
                <?php if (!empty($r['primary_contact_role'])): ?>
                  <div class="secondary"><?= esc($r['primary_contact_role']) ?></div>
                <?php endif; ?>
                <?php if ((int) ($r['contact_count'] ?? 0) > 1): ?>
                  <span class="badge-soft" style="font-size:.68rem;">+<?= (int) $r['contact_count'] - 1 ?> more</span>
                <?php endif; ?>
              <?php else: ?>
                <span class="text-muted">—</span>
              <?php endif; ?>
            </td>

            <td data-col="mobile" data-label="Mobile / WA" class="cell-stack">
              <?php if ($primaryMobile): ?>
                <a class="text-decoration-none text-dark" href="tel:<?= esc($primaryMobile) ?>">
                  <i class="bi bi-telephone"></i> <?= esc($primaryMobile) ?>
                </a>
              <?php else: ?>
                <span class="text-muted">—</span>
              <?php endif; ?>
              <?php if (!empty($r['whatsapp_no']) && $r['whatsapp_no'] !== $primaryMobile): ?>
                <div class="secondary"><i class="bi bi-whatsapp text-success"></i> <?= esc($r['whatsapp_no']) ?></div>
              <?php endif; ?>
            </td>

            <td data-col="city" data-label="City"><?= esc($r['city']) ?: '<span class="text-muted">—</span>' ?></td>

            <td data-col="flags" data-label="Flags">
              <?php if ((int) $r['is_preferred'] === 1): ?>
                <span class="badge-soft badge-ok" title="Preferred vendor"><i class="bi bi-star-fill"></i> Preferred</span>
              <?php endif; ?>
              <?php if ((int) $r['is_blacklisted'] === 1): ?>
                <span class="badge-soft badge-danger"><i class="bi bi-slash-circle"></i> Blacklisted</span>
              <?php endif; ?>
              <?php if ((int) $r['is_preferred'] === 0 && (int) $r['is_blacklisted'] === 0): ?>
                <span class="text-muted">—</span>
              <?php endif; ?>
            </td>

            <td data-col="status" data-label="Status">
              <?php if ((int) $r['status'] === 1): ?>
                <span class="badge-soft badge-ok">Active</span>
              <?php else: ?>
                <span class="badge-soft badge-danger">Inactive</span>
              <?php endif; ?>
            </td>

            <td data-col="actions" class="text-end" data-label="Actions">
              <a class="btn btn-sm btn-light" href="<?= site_url('vendors/' . $r['id'] . '/edit') ?>" title="Edit"><i class="bi bi-pencil"></i></a>
              <form class="d-inline" method="post" action="<?= site_url('vendors/' . $r['id'] . '/delete') ?>" data-confirm="Delete this vendor?">
                <?= csrf_field() ?>
                <button class="btn btn-sm btn-light" type="submit" title="Delete"><i class="bi bi-trash"></i></button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php if (!empty($pager)): ?><div class="mt-3"><?= $pager->links() ?></div><?php endif; ?>
</div>
