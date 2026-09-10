<?php
$appName = env('tpt.appName', 'TPT Aggregator');
helper('branding');
$logoUrl = tpt_logo_url();
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Sign in · <?= esc($appName) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="<?= base_url('assets/css/app.css') ?>" rel="stylesheet">
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
      <div class="text-muted" style="font-size:.85rem;">Sign in to continue</div>
    </div>

    <?php if (!empty($error)): ?>
      <div class="alert alert-danger mb-3"><?= esc($error) ?></div>
    <?php endif; ?>

    <form method="post" action="<?= site_url('login') ?>" novalidate>
      <?= csrf_field() ?>
      <div class="mb-3">
        <label class="form-label">Email</label>
        <input type="email" class="form-control" name="email" value="<?= esc(old('email', 'admin@tpt.local')) ?>" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Password</label>
        <input type="password" class="form-control" name="password" required>
      </div>
      <button class="btn btn-primary w-100" type="submit">Sign in</button>
    </form>
    <div class="text-center mt-2" style="font-size:.85rem;">
      <a href="<?= site_url('forgot') ?>" class="text-muted">Forgot password?</a>
    </div>

  </div>
</div>
</body>
</html>
