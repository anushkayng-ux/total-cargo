<?php
$appName = env('tpt.appName', 'TPT Aggregator');
helper('branding');
$logoUrl = tpt_logo_url();
?><!DOCTYPE html>
<html lang="en"><head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Forgot password · <?= esc($appName) ?></title>
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
      <div class="text-muted" style="font-size:.85rem;">Reset your password</div>
    </div>

    <?php if (!empty($error)): ?><div class="alert alert-danger mb-3"><?= esc($error) ?></div><?php endif; ?>
    <?php $success = session()->getFlashdata('success'); if ($success): ?><div class="alert alert-success mb-3"><?= esc($success) ?></div><?php endif; ?>

    <p class="text-muted" style="font-size:.88rem;">Enter the email you sign in with — we'll send a one-time link to reset your password.</p>

    <form method="post" action="<?= site_url('forgot') ?>" novalidate>
      <?= csrf_field() ?>
      <div class="mb-3">
        <label class="form-label">Work email</label>
        <input class="form-control" type="email" name="email" required autofocus>
      </div>
      <button class="btn btn-primary w-100" type="submit"><i class="bi bi-envelope-arrow-up"></i> Send reset link</button>
    </form>

    <div class="text-center mt-3" style="font-size:.85rem;">
      <a href="<?= site_url('login') ?>"><i class="bi bi-arrow-left"></i> Back to sign in</a>
    </div>
  </div>
</div>
</body></html>
