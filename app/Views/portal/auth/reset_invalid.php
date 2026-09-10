<?php $appName = env('tpt.appName', 'TPT Aggregator'); ?><!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><title>Reset link invalid · <?= esc($appName) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>body{font-family:'Poppins',sans-serif;background:#f4f4f4;}</style>
</head><body>
<div style="min-height:100vh;display:flex;align-items:center;justify-content:center;padding:1rem;">
  <div style="background:#fff;border:1px solid #eee;border-radius:10px;padding:1.5rem;max-width:440px;text-align:center;">
    <h5>Reset link invalid</h5>
    <p class="text-muted">This password-reset link has expired or already been used. Request a new one to continue.</p>
    <a class="btn btn-primary" href="<?= site_url('portal/forgot') ?>">Request new link</a>
  </div>
</div>
</body></html>
