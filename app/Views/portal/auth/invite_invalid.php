<?php $appName = env('tpt.appName', 'TPT Aggregator'); ?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Invite invalid · <?= esc($appName) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>body{font-family:'Poppins',sans-serif;background:#f4f4f4;}</style>
</head>
<body>
<div style="min-height:100vh;display:flex;align-items:center;justify-content:center;padding:1rem;">
  <div style="background:#fff;border:1px solid #eee;border-radius:10px;padding:1.5rem;max-width:440px;width:100%;text-align:center;">
    <h5>Invite <?= !empty($expired) ? 'expired' : 'invalid' ?></h5>
    <p class="text-muted">This invite link can no longer be used. Please ask your account manager to issue a new one.</p>
    <a class="btn btn-primary" href="<?= site_url('portal/login') ?>">Go to sign in</a>
  </div>
</div>
</body>
</html>
