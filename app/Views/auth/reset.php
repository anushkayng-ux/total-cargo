<?php
$appName = env('tpt.appName', 'TPT Aggregator');
helper('branding');
$logoUrl = tpt_logo_url();
?><!DOCTYPE html>
<html lang="en"><head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Set new password · <?= esc($appName) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="<?= base_url('assets/css/app.css') ?>" rel="stylesheet">
</head><body>
<div class="auth-wrap">
  <div class="auth-card">
    <div class="text-center mb-3">
      <?php if ($logoUrl): ?><img src="<?= esc($logoUrl) ?>" alt="" style="max-height:48px;max-width:200px;margin-bottom:.5rem;"><?php endif; ?>
      <div style="font-weight:700;font-size:1.1rem;"><?= esc($appName) ?></div>
      <div class="text-muted" style="font-size:.85rem;">Set a new password</div>
    </div>

    <?php if (!empty($error)): ?><div class="alert alert-danger mb-3"><?= esc($error) ?></div><?php endif; ?>

    <form method="post" action="<?= site_url('reset/' . $token) ?>" novalidate>
      <?= csrf_field() ?>
      <div class="mb-3">
        <label class="form-label">New password</label>
        <input class="form-control" type="password" name="password" minlength="8" required autofocus>
        <div class="form-text" style="font-size:.78rem;">Minimum 8 characters.</div>
      </div>
      <div class="mb-3">
        <label class="form-label">Confirm password</label>
        <input class="form-control" type="password" name="password_confirm" minlength="8" required>
      </div>
      <button class="btn btn-primary w-100" type="submit"><i class="bi bi-check2"></i> Update password</button>
    </form>
  </div>
</div>
</body></html>
