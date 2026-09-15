<?php
$pageTitle = $pageTitle ?? 'Users';
$extra = '<form class="d-flex align-items-center gap-2 m-0" method="get" action="' . site_url('users') . '">'
    . '<input type="text" name="q" class="form-control form-control-sm" placeholder="Search name/email/mobile" value="' . esc($search) . '">'
    . '<button class="btn btn-sm btn-outline-dark">Search</button></form>';

echo tpt_toolbar([
    'new_href'       => site_url('users/create'),
    'new_item_label' => 'Add User',
    'close_href'     => site_url('dashboard'),
    'extra'          => $extra,
    'auth'           => $auth,
]);
?>
<div class="tabs" role="tablist">
  <div class="tab active">All Users</div>
  <div class="spacer"></div>
  <div class="recordnav"><?= (int) ($pager->getTotal() ?: count($users)) ?> total records</div>
</div>

<div class="gridwrap">
  <div class="table-responsive">
    <table class="table grid mb-0" data-tpt-cols="users">
      <thead>
        <tr>
          <th data-col="name">Name</th><th data-col="email">Email</th><th data-col="mobile">Mobile</th><th data-col="role">Role</th><th data-col="status">Status</th><th data-col="last-login">Last Login</th><th class="text-end" data-col="actions">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($users)): ?>
          <tr><td colspan="7" class="text-center text-muted">No users.</td></tr>
        <?php endif; ?>
        <?php foreach ($users as $u): ?>
          <tr class="row-link" data-href="<?= site_url('users/' . $u['id'] . '/edit') ?>">
            <td data-col="name" data-label="Name"><?= esc($u['name']) ?></td>
            <td data-col="email" data-label="Email"><?= esc($u['email']) ?></td>
            <td data-col="mobile" data-label="Mobile"><?= esc($u['mobile']) ?></td>
            <td data-col="role" data-label="Role"><?= esc($u['role_name'] ?? '') ?></td>
            <td data-col="status" data-label="Status">
              <?php if ((int)$u['status'] === 1): ?>
                <span class="badge-soft badge-ok">Active</span>
              <?php else: ?>
                <span class="badge-soft badge-danger">Disabled</span>
              <?php endif; ?>
            </td>
            <td data-col="last-login" data-label="Last Login"><?= esc($u['last_login_at'] ?? '—') ?></td>
            <td data-col="actions" data-label="Actions" class="text-end">
              <a class="btn btn-sm btn-light" href="<?= site_url('users/' . $u['id'] . '/edit') ?>"><i class="bi bi-pencil"></i></a>
              <?php if ((int)$u['id'] !== (int)($currentUser['id'] ?? 0)): ?>
                <form class="d-inline" method="post" action="<?= site_url('users/' . $u['id'] . '/delete') ?>" data-confirm="Delete this user?">
                  <?= csrf_field() ?>
                  <button class="btn btn-sm btn-light" type="submit"><i class="bi bi-trash"></i></button>
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
