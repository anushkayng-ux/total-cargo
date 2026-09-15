<?php
$extra = '<form method="get" class="d-flex align-items-center gap-2 flex-wrap m-0">'
    . '<input class="form-control form-control-sm" style="width:160px;" type="text" name="q" value="' . esc($filters['q'] ?? '') . '" placeholder="Name, alias, state…">'
    . '<select class="form-select form-select-sm" style="width:auto;" name="state"><option value="">All States</option>';
foreach ($states as $s) {
    $extra .= '<option value="' . esc($s) . '"' . (($filters['state'] ?? '') === $s ? ' selected' : '') . '>' . esc($s) . '</option>';
}
$extra .= '</select><select class="form-select form-select-sm" style="width:auto;" name="region"><option value="">All Regions</option>';
foreach ($regions as $r) {
    $extra .= '<option value="' . $r . '"' . (($filters['region'] ?? '') === $r ? ' selected' : '') . '>' . $r . '</option>';
}
$extra .= '</select><select class="form-select form-select-sm" style="width:auto;" name="tier"><option value="">All Tiers</option>';
foreach ($tiers as $t) {
    $extra .= '<option value="' . $t . '"' . (($filters['tier'] ?? '') === $t ? ' selected' : '') . '>Tier ' . $t . '</option>';
}
$extra .= '</select>'
    . '<button class="btn btn-sm btn-outline-dark">Apply</button>'
    . '<a class="btn btn-sm btn-light" href="' . site_url('cities') . '">Clear</a>'
    . '</form>';

echo tpt_toolbar([
    'new_href'       => $auth->can('cities', 'can_add') ? site_url('cities/create') : null,
    'new_item_label' => 'Add City',
    'close_href'     => site_url('dashboard'),
    'extra'          => $extra,
    'auth'           => $auth,
]);
?>
<div class="tabs">
  <div class="tab active">All Cities</div>
  <div class="spacer"></div>
  <div class="recordnav"><?= (int) ($pager->getTotal() ?: count($rows)) ?> total records</div>
</div>

<div class="formwrap" style="flex:0 0 auto;">
  <p class="text-muted mb-0" style="font-size:.85rem;">Canonical city names used by clients, vendors, RFQs &amp; vendor route coverage.</p>
</div>

<div class="gridwrap">
  <div class="table-responsive">
    <table class="table grid mobile-cards mb-0" data-tpt-cols="cities">
      <thead><tr>
        <th data-col="name">Name</th><th data-col="state">State</th><th data-col="gst">GST</th><th data-col="region">Region</th><th data-col="tier">Tier</th><th data-col="aliases">Aliases</th><th data-col="status">Status</th><th class="text-end" data-col="actions"></th>
      </tr></thead>
      <tbody>
        <?php if (empty($rows)): ?>
          <tr><td colspan="8" class="text-center text-muted py-3">No cities match your filters.</td></tr>
        <?php endif; ?>
        <?php foreach ($rows as $r): ?>
          <tr class="row-link" data-href="<?= $auth->can('cities', 'can_edit') ? site_url('cities/' . $r['id'] . '/edit') : '#' ?>">
            <td data-col="name" data-label="Name"><strong><?= esc($r['name']) ?></strong></td>
            <td data-col="state" data-label="State">
              <?php if ($r['state'] === '— Unknown —'): ?>
                <span class="badge-soft badge-warn">Needs review</span>
              <?php else: ?>
                <?= esc($r['state']) ?>
              <?php endif; ?>
            </td>
            <td data-col="gst" data-label="GST"><code><?= esc($r['gst_state_code']) ?></code></td>
            <td data-col="region" data-label="Region"><?= esc($r['region']) ?></td>
            <td data-col="tier" data-label="Tier"><?= $r['tier'] ? 'Tier ' . esc($r['tier']) : '<span class="text-muted">—</span>' ?></td>
            <td data-col="aliases" data-label="Aliases" class="text-muted" style="font-size:.85rem;"><?= esc($r['aliases']) ?: '—' ?></td>
            <td data-col="status" data-label="Status">
              <?= (int) $r['status'] === 1 ? '<span class="badge-soft badge-ok">Active</span>' : '<span class="badge-soft badge-danger">Inactive</span>' ?>
            </td>
            <td class="text-end" data-col="actions">
              <?php if ($auth->can('cities', 'can_edit')): ?>
                <a class="btn btn-sm btn-light" href="<?= site_url('cities/' . $r['id'] . '/edit') ?>"><i class="bi bi-pencil"></i></a>
              <?php endif; ?>
              <?php if ($auth->can('cities', 'can_delete')): ?>
                <form method="post" action="<?= site_url('cities/' . $r['id'] . '/delete') ?>" class="d-inline" data-confirm="Delete this city?">
                  <?= csrf_field() ?>
                  <button class="btn btn-sm btn-light"><i class="bi bi-trash"></i></button>
                </form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php if (!empty($pager)): ?><div class="mt-3"><?= $pager->links() ?></div><?php endif; ?>
</div>
