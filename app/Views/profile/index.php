<?php
$enabled = (int) ($user['totp_enabled'] ?? 0) === 1;
$qrLink  = $provisioning ? 'https://chart.googleapis.com/chart?chs=200x200&cht=qr&chl=' . rawurlencode($provisioning) : null;
?>
<h5 class="mb-3"><i class="bi bi-person-circle"></i> My Profile</h5>

<div class="row g-3">
  <div class="col-lg-6">
    <div class="card"><div class="card-body">
      <h6 class="mb-3">Account details</h6>
      <dl class="row mb-0" style="font-size:.9rem;">
        <dt class="col-sm-4 text-muted">Name</dt>            <dd class="col-sm-8"><?= esc($user['name']) ?></dd>
        <dt class="col-sm-4 text-muted">Email</dt>           <dd class="col-sm-8"><?= esc($user['email']) ?></dd>
        <dt class="col-sm-4 text-muted">Role</dt>            <dd class="col-sm-8"><?= esc($currentUser['role_name'] ?? '') ?></dd>
        <dt class="col-sm-4 text-muted">Last sign-in</dt>    <dd class="col-sm-8"><?= esc($user['last_login_at'] ?? '—') ?></dd>
      </dl>
    </div></div>
  </div>

  <div class="col-lg-6">
    <div class="card"><div class="card-body">
      <h6 class="mb-3"><i class="bi bi-shield-lock"></i> Two-factor authentication
        <?php if ($enabled): ?>
          <span class="badge bg-success ms-2">Enabled</span>
        <?php else: ?>
          <span class="badge bg-warning text-dark ms-2">Off</span>
        <?php endif; ?>
      </h6>

      <?php if ($enabled): ?>
        <p class="small text-muted">2FA is active. You'll be asked for a code on every new sign-in.</p>
        <form method="post" action="<?= site_url('profile/totp/disable') ?>" class="d-flex gap-2" data-confirm="Disable 2FA?">
          <?= csrf_field() ?>
          <input class="form-control form-control-sm" name="code" placeholder="Current 6-digit code" pattern="\d{6}" required>
          <button class="btn btn-sm btn-outline-danger" type="submit">Disable</button>
        </form>

      <?php elseif ($newSecret && $qrLink): ?>
        <p class="small text-muted">Scan the QR code below in Google Authenticator / 1Password / Authy, then enter the 6-digit code to confirm.</p>
        <div class="text-center mb-2"><img src="<?= esc($qrLink) ?>" alt="2FA QR" style="max-width:200px;"></div>
        <div class="small text-muted mb-2">Or paste this secret manually: <code><?= esc($newSecret) ?></code></div>
        <form method="post" action="<?= site_url('profile/totp/enable') ?>" class="d-flex gap-2">
          <?= csrf_field() ?>
          <input class="form-control form-control-sm" name="code" placeholder="6-digit code" pattern="\d{6}" required>
          <button class="btn btn-sm btn-primary" type="submit"><i class="bi bi-check2"></i> Confirm</button>
        </form>

      <?php else: ?>
        <p class="small text-muted">Add a second factor to protect your account against password reuse and phishing.</p>
        <form method="post" action="<?= site_url('profile/totp/start') ?>">
          <?= csrf_field() ?>
          <button class="btn btn-sm btn-primary" type="submit"><i class="bi bi-plus-lg"></i> Start 2FA setup</button>
        </form>
      <?php endif; ?>
    </div></div>
  </div>

  <div class="col-12">
    <div class="card"><div class="card-body">
      <h6 class="mb-3"><i class="bi bi-key"></i> Change password</h6>
      <form method="post" action="<?= site_url('profile/change-password') ?>" class="row g-2">
        <?= csrf_field() ?>
        <div class="col-md-4"><label class="form-label small">Current password</label>
          <input class="form-control form-control-sm" type="password" name="current_password" required autocomplete="current-password"></div>
        <div class="col-md-4"><label class="form-label small">New password</label>
          <input class="form-control form-control-sm" type="password" name="new_password" minlength="10" required autocomplete="new-password"></div>
        <div class="col-md-4"><label class="form-label small">Confirm new password</label>
          <input class="form-control form-control-sm" type="password" name="confirm_password" minlength="10" required autocomplete="new-password"></div>
        <div class="col-12">
          <small class="text-muted">Minimum 10 characters. Passwords found in public breaches will be rejected.</small>
        </div>
        <div class="col-12 mt-2">
          <button class="btn btn-sm btn-primary"><i class="bi bi-save"></i> Update password</button>
        </div>
      </form>
    </div></div>
  </div>
</div>
