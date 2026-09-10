<?php
$appName = env('tpt.appName', 'TPT Aggregator');
helper('branding');
$logoUrl = tpt_logo_url();
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Forgot password · <?= esc($appName) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="<?= base_url('assets/css/app.css') ?>" rel="stylesheet">
<style>body{font-family:'Poppins',sans-serif;background:#f4f4f4;}.auth-wrap{min-height:100vh;display:flex;align-items:center;justify-content:center;padding:1rem;}.auth-card{background:#fff;border:1px solid #eee;border-radius:10px;padding:1.5rem;max-width:400px;width:100%;}</style>
</head>
<body>
<div class="auth-wrap">
  <div class="auth-card">
    <div class="text-center mb-3">
      <?php if ($logoUrl): ?><img src="<?= esc($logoUrl) ?>" style="max-height:48px;max-width:200px;margin-bottom:.5rem;"><?php endif; ?>
      <div style="font-weight:700;"><?= esc($appName) ?></div>
      <div class="text-muted" style="font-size:.85rem;">Reset your password</div>
    </div>
    <?php $err = session()->getFlashdata('error'); if ($err): ?><div class="alert alert-danger"><?= esc($err) ?></div><?php endif; ?>
    <?php $ok  = session()->getFlashdata('success'); if ($ok):  ?><div class="alert alert-success"><?= esc($ok)  ?></div><?php endif; ?>
    <form method="post" action="<?= site_url('portal/forgot') ?>">
      <?= csrf_field() ?>
      <div class="mb-3">
        <label class="form-label">Account email</label>
        <input type="email" class="form-control" name="email" required>
      </div>
      <button class="btn btn-primary w-100">Email me reset instructions</button>
    </form>
    <div class="text-center mt-3" style="font-size:.85rem;">
      <a href="<?= site_url('portal/login') ?>" class="text-muted">&larr; Back to sign in</a>
    </div>
  </div>
</div>
</body>
</html>
