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
<title>Reset password · <?= esc($appName) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="<?= base_url('assets/css/app.css') ?>" rel="stylesheet">
<style>body{font-family:'Poppins',sans-serif;background:#f4f4f4;}.auth-wrap{min-height:100vh;display:flex;align-items:center;justify-content:center;padding:1rem;}.auth-card{background:#fff;border:1px solid #eee;border-radius:10px;padding:1.5rem;max-width:420px;width:100%;}</style>
</head>
<body>
<div class="auth-wrap">
  <div class="auth-card">
    <div class="text-center mb-3">
      <?php if ($logoUrl): ?><img src="<?= esc($logoUrl) ?>" alt="<?= esc($appName) ?>" style="max-height:48px;max-width:200px;margin-bottom:.5rem;"><?php endif; ?>
      <div style="font-weight:700;"><?= esc($appName) ?></div>
      <div class="text-muted" style="font-size:.85rem;">Choose a new password</div>
    </div>
    <?php if (!empty($error)): ?><div class="alert alert-danger"><?= esc($error) ?></div><?php endif; ?>
    <form method="post" action="<?= site_url('portal/reset/' . $token) ?>" novalidate>
      <?= csrf_field() ?>
      <div class="mb-3">
        <label class="form-label" for="pw">New password</label>
        <input id="pw" type="password" class="form-control" name="password" minlength="8" required autocomplete="new-password">
      </div>
      <div class="mb-3">
        <label class="form-label" for="pw2">Confirm new password</label>
        <input id="pw2" type="password" class="form-control" name="password_confirm" minlength="8" required autocomplete="new-password">
      </div>
      <button class="btn btn-primary w-100">Set new password</button>
    </form>
  </div>
</div>
</body>
</html>
