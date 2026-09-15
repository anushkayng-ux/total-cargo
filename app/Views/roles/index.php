<?php $pageTitle = $pageTitle ?? 'Roles'; ?>
<?= tpt_toolbar([
    'new_href'       => site_url('roles/create'),
    'new_item_label' => 'Add Role',
    'close_href'     => site_url('dashboard'),
    'auth'           => $auth,
]) ?>
<div class="tabs">
  <div class="tab active">All Roles</div>
  <div class="spacer"></div>
  <div class="recordnav"><?= count($roles) ?> total records</div>
</div>

<div class="gridwrap">
  <div class="table-responsive">
    <table class="table grid mb-0">
      <thead>
        <tr><th>Role</th><th>Key</th><th>Status</th><th class="text-end">Actions</th></tr>
      </thead>
      <tbody>
        <?php foreach ($roles as $r): ?>
          <tr class="row-link" data-href="<?= site_url('roles/' . $r['id'] . '/edit') ?>">
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
