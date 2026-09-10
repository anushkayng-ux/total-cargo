<div class="d-flex align-items-center mb-3 gap-2 flex-wrap">
  <h5 class="m-0"><i class="bi bi-building"></i> <?= esc($pageTitle) ?></h5>
  <form class="ms-auto d-flex gap-2" method="get" action="<?= site_url('clients') ?>">
    <input type="text" name="q" class="form-control form-control-sm" placeholder="Search name / mobile / code / GST" value="<?= esc($search) ?>" style="min-width:240px;">
    <button class="btn btn-sm btn-outline-dark"><i class="bi bi-search"></i></button>
  </form>
  <a class="btn btn-sm btn-outline-dark" href="<?= site_url('import/clients') ?>" title="Bulk import from Excel/CSV"><i class="bi bi-upload"></i> Import</a>
  <?php if ($auth->isSuperAdmin() || \App\Libraries\Notify::isAdminUser((int) $auth->id())): ?>
    <a class="btn btn-sm btn-outline-primary" href="<?= site_url('clients/assign-owners') ?>" title="Bulk assign clients to account managers"><i class="bi bi-people-fill"></i> Assign Owners</a>
  <?php endif; ?>
  <a class="btn btn-sm btn-primary" href="<?= site_url('clients/create') ?>"><i class="bi bi-plus-lg"></i> Add Client</a>
</div>

<div class="card">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0" data-tpt-cols="clients">
      <thead class="table-light">
        <tr>
          <th data-col="code" style="width:130px;">Code</th>
          <th data-col="company">Company</th>
          <th data-col="contact">Contact</th>
          <th data-col="mobile">Mobile</th>
          <th data-col="city">City</th>
          <th data-col="gstin">GSTIN</th>
          <th data-col="status">Status</th>
          <th data-col="portal">Portal</th>
          <th data-col="actions" class="text-end" style="width:120px;">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($rows)): ?>
          <tr><td colspan="9" class="text-center text-muted py-4">
            <i class="bi bi-inbox" style="font-size:1.8rem;opacity:.4;"></i>
            <div class="mt-1">No clients yet — add one or import a sheet.</div>
          </td></tr>
        <?php endif; ?>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td data-col="code" data-label="Code"><code class="text-dark"><?= esc($r['client_code']) ?></code></td>

            <td data-col="company" data-label="Company" class="cell-stack">
              <strong><?= esc($r['company_name']) ?></strong>
              <?php if (!empty($r['email'])): ?>
                <div class="secondary"><i class="bi bi-envelope"></i> <?= esc($r['email']) ?></div>
              <?php endif; ?>
            </td>

            <td data-col="contact" data-label="Contact"><?= esc($r['contact_name']) ?: '<span class="text-muted">—</span>' ?></td>

            <td data-col="mobile" data-label="Mobile">
              <?php if (!empty($r['mobile'])): ?>
                <a class="text-decoration-none text-dark" href="tel:<?= esc($r['mobile']) ?>">
                  <i class="bi bi-telephone"></i> <?= esc($r['mobile']) ?>
                </a>
              <?php else: ?>
                <span class="text-muted">—</span>
              <?php endif; ?>
            </td>

            <td data-col="city" data-label="City"><?= esc($r['city']) ?: '<span class="text-muted">—</span>' ?></td>

            <td data-col="gstin" data-label="GSTIN">
              <?php if (!empty($r['gst_no'])): ?>
                <code style="font-size:.78rem;"><?= esc($r['gst_no']) ?></code>
              <?php else: ?>
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

            <td data-col="portal" data-label="Portal">
              <?php if ((int) ($r['portal_enabled'] ?? 0) === 1): ?>
                <a class="btn btn-sm btn-light" href="<?= site_url('clients/' . $r['id'] . '/portal-users') ?>" title="Manage portal users">
                  <i class="bi bi-people"></i> On
                </a>
              <?php else: ?>
                <span class="text-muted" style="font-size:.85rem;">Off</span>
              <?php endif; ?>
            </td>

            <td data-col="actions" class="text-end" data-label="Actions">
              <a class="btn btn-sm btn-light" href="<?= site_url('clients/' . $r['id'] . '/edit') ?>" title="Edit"><i class="bi bi-pencil"></i></a>
              <form class="d-inline" method="post" action="<?= site_url('clients/' . $r['id'] . '/delete') ?>" data-confirm="Delete this client?">
                <?= csrf_field() ?>
                <button class="btn btn-sm btn-light" type="submit" title="Delete"><i class="bi bi-trash"></i></button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php if (!empty($pager)): ?><div class="mt-3"><?= $pager->links() ?></div><?php endif; ?>
