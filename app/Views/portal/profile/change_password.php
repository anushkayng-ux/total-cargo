<?php
$user = $clientAuth->user();
$mustChange = !empty($user['must_change']);
?>
<h5 class="mb-3">Change password</h5>

<?php if ($mustChange): ?>
  <div class="alert alert-warning">
    <i class="bi bi-exclamation-triangle"></i>
    Please choose a new password to continue.
  </div>
<?php endif; ?>

<div class="card" style="max-width:520px;">
  <div class="card-body">
    <form method="post" action="<?= site_url('portal/profile/change-password') ?>" novalidate>
      <?= csrf_field() ?>
      <?php if (!$mustChange): ?>
        <div class="mb-3">
          <label class="form-label">Current password</label>
          <input type="password" class="form-control" name="current_password" required autocomplete="current-password">
        </div>
      <?php endif; ?>
      <div class="mb-3">
        <label class="form-label">New password</label>
        <input type="password" class="form-control" name="new_password" minlength="8" required autocomplete="new-password">
        <div class="form-text">Minimum 8 characters.</div>
      </div>
      <div class="mb-3">
        <label class="form-label">Confirm new password</label>
        <input type="password" class="form-control" name="new_password_confirm" minlength="8" required autocomplete="new-password">
      </div>
      <button class="btn btn-primary">Update password</button>
    </form>
  </div>
</div>
