<?php
$appName = env('tpt.appName', 'TPT Aggregator');
helper('branding');
$logoUrl = tpt_logo_url();
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title>Client Sign in · <?= esc($appName) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="<?= base_url('assets/css/app.css') ?>" rel="stylesheet">
<style>
  body { font-family: 'Poppins', sans-serif; }
  .auth-wrap { min-height:100vh; display:flex; align-items:center; justify-content:center; background:#f4f4f4; padding:1rem; }
  .auth-card { background:#fff; border:1px solid #eee; border-radius:10px; padding:1.5rem; max-width:400px; width:100%; }
</style>
</head>
<body>
<div class="auth-wrap">
  <div class="auth-card">
    <div class="text-center mb-3">
      <?php if ($logoUrl): ?>
        <img src="<?= esc($logoUrl) ?>" alt="" style="max-height:48px;max-width:200px;margin-bottom:.5rem;">
      <?php endif; ?>
      <div style="font-weight:700;font-size:1.1rem;">
        <?php if (!$logoUrl): ?><i class="bi bi-truck"></i><?php endif; ?> <?= esc($appName) ?>
      </div>
      <div class="text-muted" style="font-size:.85rem;">Client Portal · Sign in</div>
    </div>

    <?php if (!empty($error)): ?>
      <div class="alert alert-danger mb-3"><?= esc($error) ?></div>
    <?php endif; ?>
    <?php $flashSuccess = session()->getFlashdata('success'); if ($flashSuccess): ?>
      <div class="alert alert-success mb-3"><?= esc($flashSuccess) ?></div>
    <?php endif; ?>

    <form method="post" action="<?= site_url('portal/login') ?>" novalidate>
      <?= csrf_field() ?>
      <div class="mb-3">
        <label class="form-label">Email</label>
        <input type="email" class="form-control" name="email" value="<?= esc(old('email')) ?>" autocomplete="username" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Password</label>
        <input type="password" class="form-control" name="password" autocomplete="current-password" required>
      </div>
      <button class="btn btn-primary w-100" type="submit">Sign in</button>
    </form>

    <div class="text-center mt-2" style="font-size:.8rem;">
      <a href="<?= site_url('portal/forgot') ?>">Forgot password?</a>
    </div>
    <div class="text-center text-muted mt-3" style="font-size:.8rem;">
      Don't have an account? Ask your account manager to send an invite.
    </div>
    <div class="text-center mt-2" style="font-size:.8rem;">
      <a href="<?= site_url('login') ?>" class="text-muted">Staff sign-in &rarr;</a>
    </div>

  </div>
</div>
</body>
</html>
