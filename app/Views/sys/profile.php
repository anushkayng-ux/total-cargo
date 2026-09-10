<h5 class="mb-3"><i class="bi bi-shield-lock"></i> Super-Admin Profile</h5>

<div class="row g-3">
  <div class="col-md-5">
    <div class="card">
      <div class="card-header">Account</div>
      <div class="card-body">
        <dl class="row mb-0" style="font-size:.92rem;">
          <dt class="col-4">Name</dt><dd class="col-8"><?= esc($user['name']) ?></dd>
          <dt class="col-4">Email</dt><dd class="col-8"><?= esc($user['email']) ?></dd>
          <dt class="col-4">Last login</dt><dd class="col-8"><?= !empty($user['last_login_at']) ? esc(date('d-m-Y H:i', strtotime($user['last_login_at']))) : '—' ?></dd>
          <dt class="col-4">Last IP</dt><dd class="col-8"><?= esc($user['last_login_ip'] ?: '—') ?></dd>
          <dt class="col-4">2FA</dt><dd class="col-8">
            <?php if ((int) $user['totp_enabled'] === 1): ?>
              <span class="badge bg-success">Enabled</span>
            <?php else: ?>
              <span class="badge bg-secondary">Off</span>
            <?php endif; ?>
          </dd>
        </dl>
      </div>
    </div>
  </div>

  <div class="col-md-7">
    <div class="card">
      <div class="card-header">Two-factor authentication</div>
      <div class="card-body">
        <?php if ((int) $user['totp_enabled'] === 1): ?>
          <p>2FA is currently <strong>enabled</strong>. To disable it, enter a current 6-digit code from your authenticator below.</p>
          <form method="post" action="<?= site_url('sys/profile/totp/disable') ?>" class="d-flex gap-2 align-items-end" onsubmit="return confirm('Disable 2FA on your super-admin account?');">
            <?= csrf_field() ?>
            <div>
              <label class="form-label">Current code</label>
              <input class="form-control mono" name="code" pattern="\d{6}" maxlength="6" required style="width:120px;" autocomplete="one-time-code">
            </div>
            <button class="btn btn-sm btn-light">Disable 2FA</button>
          </form>
        <?php elseif ($newSecret): ?>
          <p>Scan this QR with your authenticator app, or copy the secret manually, then enter the 6-digit code below to confirm and enable 2FA.</p>
          <div class="row g-3 align-items-center">
            <div class="col-md-5">
              <div id="qr" style="background:#fff;padding:.6rem;border-radius:6px;display:inline-block;"></div>
            </div>
            <div class="col-md-7">
              <div><span class="text-muted" style="font-size:.78rem;">Secret (manual entry)</span></div>
              <div class="mono" style="font-size:.95rem;background:#0a0d12;padding:.4rem .6rem;border-radius:4px;border:1px solid #232831;word-break:break-all;"><?= esc($newSecret) ?></div>
              <div class="mt-2 text-muted" style="font-size:.78rem;">Algorithm: SHA1 · Digits: 6 · Period: 30s</div>
            </div>
          </div>
          <form method="post" action="<?= site_url('sys/profile/totp/enable') ?>" class="d-flex gap-2 align-items-end mt-3">
            <?= csrf_field() ?>
            <div>
              <label class="form-label">Code from authenticator</label>
              <input class="form-control mono" name="code" pattern="\d{6}" maxlength="6" required style="width:120px;" autocomplete="one-time-code">
            </div>
            <button class="btn btn-sm btn-primary">Enable 2FA</button>
          </form>
          <script src="https://cdn.jsdelivr.net/npm/qrcode-generator@1.4.4/qrcode.min.js"></script>
          <script>
            (function () {
              var uri = <?= json_encode($provisioning) ?>;
              var qr = qrcode(0, 'M');
              qr.addData(uri); qr.make();
              document.getElementById('qr').innerHTML = qr.createImgTag(5, 0);
            })();
          </script>
        <?php else: ?>
          <p>2FA is currently <strong>off</strong>. Click below to start setup — you'll see a QR code to scan with your authenticator app, then confirm with a 6-digit code.</p>
          <form method="post" action="<?= site_url('sys/profile/totp/start') ?>">
            <?= csrf_field() ?>
            <button class="btn btn-sm btn-primary"><i class="bi bi-shield-plus"></i> Start 2FA setup</button>
          </form>
          <div class="mt-3 text-muted" style="font-size:.85rem;">
            Compatible with Google Authenticator, Authy, 1Password, Microsoft Authenticator, etc.
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
