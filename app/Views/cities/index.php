<div class="d-flex align-items-center mb-3 gap-2 flex-wrap">
  <h5 class="m-0"><?= esc($pageTitle) ?></h5>
  <?php if (!empty($pager)): ?><span class="badge bg-secondary ms-2"><?= $pager->getTotal() ?> total</span><?php endif; ?>
  <span class="text-muted ms-2" style="font-size:.85rem;">Canonical city names used by clients, vendors, RFQs &amp; vendor route coverage.</span>
  <?php if ($auth->can('cities', 'can_add')): ?>
    <a class="btn btn-sm btn-primary ms-auto" href="<?= site_url('cities/create') ?>"><i class="bi bi-plus-lg"></i> Add City</a>
  <?php endif; ?>
</div>

<form method="get" class="card mb-3"><div class="card-body p-3">
  <div class="row g-2 align-items-end">
    <div class="col-md-3">
      <label class="form-label" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.04em;color:#6b7280;font-weight:600;">Search</label>
      <input class="form-control form-control-sm" type="text" name="q" value="<?= esc($filters['q'] ?? '') ?>" placeholder="Name, alias, state…">
    </div>
    <div class="col-md-2">
      <label class="form-label" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.04em;color:#6b7280;font-weight:600;">State</label>
      <select class="form-select form-select-sm" name="state">
        <option value="">All</option>
        <?php foreach ($states as $s): ?>
          <option value="<?= esc($s) ?>" <?= ($filters['state'] ?? '') === $s ? 'selected' : '' ?>><?= esc($s) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2">
      <label class="form-label" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.04em;color:#6b7280;font-weight:600;">Region</label>
      <select class="form-select form-select-sm" name="region">
        <option value="">All</option>
        <?php foreach ($regions as $r): ?>
          <option value="<?= $r ?>" <?= ($filters['region'] ?? '') === $r ? 'selected' : '' ?>><?= $r ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-1">
      <label class="form-label" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.04em;color:#6b7280;font-weight:600;">Tier</label>
      <select class="form-select form-select-sm" name="tier">
        <option value="">All</option>
        <?php foreach ($tiers as $t): ?>
          <option value="<?= $t ?>" <?= ($filters['tier'] ?? '') === $t ? 'selected' : '' ?>>Tier <?= $t ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-1">
      <label class="form-label" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.04em;color:#6b7280;font-weight:600;">Per Page</label>
      <select class="form-select form-select-sm" name="per_page">
        <?php foreach ([25, 50, 100] as $n): ?>
          <option value="<?= $n ?>" <?= (int) $perPage === $n ? 'selected' : '' ?>><?= $n ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-3 d-flex gap-1">
      <button class="btn btn-sm btn-primary"><i class="bi bi-funnel"></i> Apply</button>
      <a class="btn btn-sm btn-light" href="<?= site_url('cities') ?>">Clear</a>
    </div>
  </div>
</div></form>

<div class="card">
  <div class="table-responsive">
    <table class="table mobile-cards mb-0" data-tpt-cols="cities">
      <thead><tr>
        <th data-col="name">Name</th><th data-col="state">State</th><th data-col="gst">GST</th><th data-col="region">Region</th><th data-col="tier">Tier</th><th data-col="aliases">Aliases</th><th data-col="status">Status</th><th class="text-end" data-col="actions"></th>
      </tr></thead>
      <tbody>
        <?php if (empty($rows)): ?>
          <tr><td colspan="8" class="text-center text-muted py-3">No cities match your filters.</td></tr>
        <?php endif; ?>
        <?php foreach ($rows as $r): ?>
          <tr>
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
</div>
<?php if (!empty($pager)): ?><div class="mt-3"><?= $pager->links() ?></div><?php endif; ?>
