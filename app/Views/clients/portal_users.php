<?= tpt_toolbar([
    'close_href' => site_url('clients/' . $client['id'] . '/edit'),
    'auth'       => $auth,
]) ?>
<div class="tabs" role="tablist">
  <div class="tab active">Portal Users</div>
  <div class="spacer"></div>
  <div class="recordnav">
    <a href="<?= site_url('clients/' . $client['id'] . '/edit') ?>"><i class="bi bi-arrow-left"></i> Back to client</a> &middot;
    <?= esc($client['company_name']) ?>
    <?php if ((int) ($client['portal_enabled'] ?? 0) !== 1): ?> &middot; <span class="badge-soft badge-warn">Portal disabled</span><?php endif; ?>
  </div>
</div>

<div class="retro-detail">
  <div class="retro-detail-main formwrap">
    <?php if ((int) ($client['portal_enabled'] ?? 0) !== 1): ?>
      <div class="alert alert-warning">Enable <strong>Portal access</strong> on the client first to allow invited users to sign in.</div>
    <?php endif; ?>

    <h6 class="mb-2 text-muted" style="font-size:.82rem;text-transform:uppercase;letter-spacing:.5px;">Existing Users</h6>
    <div class="gridwrap" style="padding:0;margin-bottom:16px;">
      <table class="table grid mb-0">
        <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Last login</th><th></th></tr></thead>
        <tbody>
          <?php if (empty($users)): ?>
            <tr><td colspan="6" class="text-center text-muted py-3">No portal users yet.</td></tr>
          <?php endif; ?>
          <?php foreach ($users as $u): ?>
            <tr>
              <td><?= esc($u['name']) ?></td>
              <td><?= esc($u['email']) ?></td>
              <td><?= esc($u['portal_role']) ?></td>
              <td><?= (int) $u['status'] === 1 ? '<span class="badge-soft badge-ok">Active</span>' : '<span class="badge-soft badge-danger">Disabled</span>' ?></td>
              <td><?= !empty($u['last_login_at']) ? esc(date('d-m H:i', strtotime($u['last_login_at']))) : '—' ?></td>
              <td>
                <form method="post" action="<?= site_url('clients/' . $client['id'] . '/portal-users/' . $u['id'] . '/toggle') ?>" style="display:inline;">
                  <?= csrf_field() ?>
                  <button class="btn btn-sm btn-light" title="Enable/Disable"><i class="bi bi-power"></i></button>
                </form>
                <form method="post" action="<?= site_url('clients/' . $client['id'] . '/portal-users/' . $u['id'] . '/reset') ?>" style="display:inline;">
                  <?= csrf_field() ?>
                  <button class="btn btn-sm btn-light" title="Reset password"><i class="bi bi-key"></i></button>
                </form>
                <form method="post" action="<?= site_url('clients/' . $client['id'] . '/portal-users/' . $u['id'] . '/delete') ?>" style="display:inline;" onsubmit="return confirm('Remove this portal user?');">
                  <?= csrf_field() ?>
                  <button class="btn btn-sm btn-outline-danger" title="Remove"><i class="bi bi-trash"></i></button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <?php if (!empty($invites)): ?>
      <h6 class="mb-2 text-muted" style="font-size:.82rem;text-transform:uppercase;letter-spacing:.5px;">Pending Invites</h6>
      <div class="gridwrap" style="padding:0;">
        <table class="table grid mb-0">
          <thead><tr><th>Email</th><th>Role</th><th>Expires</th><th>Link</th></tr></thead>
          <tbody>
            <?php foreach ($invites as $i): ?>
              <tr>
                <td><?= esc($i['email']) ?></td>
                <td><?= esc($i['portal_role']) ?></td>
                <td><?= !empty($i['expires_at']) ? esc(date('d-m-Y', strtotime($i['expires_at']))) : '—' ?></td>
                <td><code style="font-size:.78rem;"><?= esc(site_url('portal/invite/' . $i['token'])) ?></code></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>

  <div class="retro-detail-side">
    <h4>Invite a new user :</h4>
    <form method="post" action="<?= site_url('clients/' . $client['id'] . '/portal-users/invite') ?>" novalidate>
      <?= csrf_field() ?>
      <div class="mb-2">
        <label class="form-label">Name</label>
        <input class="form-control form-control-sm" name="name" required>
      </div>
      <div class="mb-2">
        <label class="form-label">Email</label>
        <input class="form-control form-control-sm" type="email" name="email" required>
      </div>
      <div class="mb-2">
        <label class="form-label">Mobile</label>
        <input class="form-control form-control-sm" name="mobile">
      </div>
      <div class="mb-2">
        <label class="form-label">Role</label>
        <select class="form-select form-select-sm" name="portal_role">
          <?php foreach ($roles as $r): ?>
            <option value="<?= $r ?>"><?= $r ?></option>
          <?php endforeach; ?>
        </select>
        <div class="form-text" style="font-size:.78rem;">Owner = full · Booker = book + view ops · Accounts = invoices + ledger · Viewer = read-only.</div>
      </div>
      <button class="btn btn-sm btn-primary mt-2">Send invite</button>
    </form>
  </div>
</div>
