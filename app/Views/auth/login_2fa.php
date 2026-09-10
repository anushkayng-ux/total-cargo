<?php
$appName = env('tpt.appName', 'TPT Aggregator');
helper('branding');
$logoUrl = function_exists('tpt_logo_url') ? tpt_logo_url() : null;
?><!DOCTYPE html>
<html lang="en"><head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title>Two-factor · <?= esc($appName) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=JetBrains+Mono&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="<?= base_url('assets/css/app.css') ?>" rel="stylesheet">
<style>body{font-family:'Poppins',sans-serif;background:#f4f4f4;}.auth-wrap{min-height:100vh;display:flex;align-items:center;justify-content:center;padding:1rem;}.auth-card{background:#fff;border:1px solid #eee;border-radius:10px;padding:1.5rem;max-width:380px;width:100%;text-align:center;}</style>
</head><body>
<div class="auth-wrap">
  <div class="auth-card">
    <?php if ($logoUrl): ?><img src="<?= esc($logoUrl) ?>" alt="<?= esc($appName) ?>" style="max-height:48px;max-width:200px;margin-bottom:.5rem;"><?php endif; ?>
    <div style="font-weight:700;"><?= esc($appName) ?></div>
    <div class="text-muted mb-3" style="font-size:.85rem;">Enter the 6-digit code from your authenticator app</div>
    <?php if (!empty($error)): ?><div class="alert alert-danger"><?= esc($error) ?></div><?php endif; ?>
    <form method="post" action="<?= site_url('login/2fa') ?>" novalidate>
      <?= csrf_field() ?>
      <input type="text" inputmode="numeric" pattern="\d{6}" maxlength="6" name="code" autofocus autocomplete="one-time-code"
             class="form-control" style="text-align:center;font-family:'JetBrains Mono',monospace;font-size:1.4rem;letter-spacing:.4em;" required>
      <button class="btn btn-primary w-100 mt-3">Verify</button>
    </form>
    <div class="text-muted mt-3" style="font-size:.78rem;">Lost your device? Contact your installation support.</div>
  </div>
</div>
</body></html>
