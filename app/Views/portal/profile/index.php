<h5 class="mb-3">My Profile</h5>

<div class="row g-3">
  <div class="col-lg-6">
    <div class="card mb-3">
      <div class="card-header">Account</div>
      <div class="card-body">
        <dl class="row mb-0" style="font-size:.92rem;">
          <dt class="col-4">Name</dt><dd class="col-8"><?= esc($user['name']) ?></dd>
          <dt class="col-4">Email</dt><dd class="col-8"><?= esc($user['email']) ?></dd>
          <dt class="col-4">Mobile</dt><dd class="col-8"><?= esc($user['mobile'] ?: '—') ?></dd>
          <dt class="col-4">Role</dt><dd class="col-8"><?= esc($user['portal_role']) ?></dd>
          <dt class="col-4">Last login</dt><dd class="col-8"><?= !empty($user['last_login_at']) ? esc(date('d-m-Y H:i', strtotime($user['last_login_at']))) : '—' ?></dd>
          <dt class="col-4">Last IP</dt><dd class="col-8"><?= esc($user['last_login_ip'] ?: '—') ?></dd>
        </dl>
        <div class="mt-3">
          <a href="<?= site_url('portal/profile/change-password') ?>" class="btn btn-sm btn-light">
            <i class="bi bi-key"></i> Change password
          </a>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-header">Notification preferences</div>
      <div class="card-body">
        <form method="post" action="<?= site_url('portal/profile/notifications') ?>">
          <?= csrf_field() ?>
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="notify_whatsapp" id="nwa" <?= $user['notify_whatsapp'] ? 'checked' : '' ?>>
            <label class="form-check-label" for="nwa">WhatsApp notifications (booking, dispatch, POD, invoice)</label>
          </div>
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="notify_email" id="nem" <?= $user['notify_email'] ? 'checked' : '' ?>>
            <label class="form-check-label" for="nem">Email notifications</label>
          </div>
          <button class="btn btn-sm btn-primary mt-3">Save</button>
        </form>
      </div>
    </div>
  </div>

  <div class="col-lg-6">
    <div class="card mb-3">
      <div class="card-header">Company</div>
      <div class="card-body">
        <dl class="row mb-0" style="font-size:.92rem;">
          <dt class="col-4">Company</dt><dd class="col-8"><?= esc($client['company_name']) ?></dd>
          <dt class="col-4">Code</dt><dd class="col-8"><?= esc($client['client_code']) ?></dd>
          <dt class="col-4">GSTIN</dt><dd class="col-8"><?= esc($client['gst_no'] ?: '—') ?></dd>
          <dt class="col-4">PAN</dt><dd class="col-8"><?= esc($client['pan_no'] ?: '—') ?></dd>
          <dt class="col-4">Address</dt><dd class="col-8"><?= esc(($client['address'] ? $client['address'] . ', ' : '') . $client['city'] . ' ' . $client['pincode']) ?></dd>
          <dt class="col-4">KYC</dt><dd class="col-8"><span class="badge-status <?= ($client['kyc_status'] ?? 'Pending') === 'Verified' ? 'badge-paid' : 'badge-pending' ?>"><?= esc($client['kyc_status'] ?? 'Pending') ?></span></dd>
          <dt class="col-4">Credit limit</dt><dd class="col-8">₹<?= number_format((float) ($client['credit_limit'] ?? 0), 0) ?></dd>
        </dl>
        <div class="text-muted mt-2" style="font-size:.8rem;">To update company details, please contact your account manager.</div>
      </div>
    </div>

    <div class="card">
      <div class="card-header">Recent sign-ins</div>
      <div class="table-responsive">
        <table class="table table-sm mb-0">
          <thead><tr><th>When</th><th>IP</th><th>Result</th></tr></thead>
          <tbody>
          <?php if (empty($loginLogs)): ?>
            <tr><td colspan="3" class="text-center text-muted py-3">No history yet.</td></tr>
          <?php endif; ?>
          <?php foreach ($loginLogs as $l): ?>
            <tr>
              <td><?= esc(date('d-m-Y H:i', strtotime($l['created_at']))) ?></td>
              <td><?= esc($l['ip_address'] ?: '—') ?></td>
              <td>
                <?php if ($l['success']): ?>
                  <span class="badge-status badge-paid">Success</span>
                <?php else: ?>
                  <span class="badge-status badge-cancelled">Failed</span>
                  <?php if (!empty($l['reason'])): ?>
                    <small class="text-muted ms-1"><?= esc($l['reason']) ?></small>
                  <?php endif; ?>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
