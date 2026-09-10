<?php $pageTitle = $pageTitle ?? 'Roles'; ?>
<div class="d-flex align-items-center mb-3 gap-2 flex-wrap">
  <h5 class="m-0"><?= esc($pageTitle) ?></h5>
  <a class="ms-auto btn btn-sm btn-primary" href="<?= site_url('roles/create') ?>"><i class="bi bi-plus-lg"></i> Add Role</a>
</div>

<div class="card">
  <div class="table-responsive">
    <table class="table mb-0">
      <thead>
        <tr><th>Role</th><th>Key</th><th>Status</th><th class="text-end">Actions</th></tr>
      </thead>
      <tbody>
        <?php foreach ($roles as $r): ?>
          <tr>
            <td data-label="Role"><?= esc($r['role_name']) ?></td>
            <td data-label="Key"><code><?= esc($r['role_key']) ?></code></td>
            <td data-label="Status">
              <?php if ((int)$r['status'] === 1): ?>
                <span class="badge-soft badge-ok">Active</span>
              <?php else: ?>
                <span class="badge-soft badge-danger">Disabled</span>
              <?php endif; ?>
            </td>
            <td data-label="Actions" class="text-end">
              <a class="btn btn-sm btn-light" href="<?= site_url('roles/' . $r['id'] . '/edit') ?>"><i class="bi bi-pencil"></i></a>
              <a class="btn btn-sm btn-primary" href="<?= site_url('roles/' . $r['id'] . '/permissions') ?>"><i class="bi bi-shield-check"></i> Permissions</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
