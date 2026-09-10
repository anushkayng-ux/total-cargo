<?php $appName = env('tpt.appName', 'TPT Aggregator'); ?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title>Accept Invite · <?= esc($appName) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<style>body { font-family:'Poppins',sans-serif;background:#f4f4f4; } .auth-wrap{min-height:100vh;display:flex;align-items:center;justify-content:center;padding:1rem;} .auth-card{background:#fff;border:1px solid #eee;border-radius:10px;padding:1.5rem;max-width:440px;width:100%;}</style>
</head>
<body>
<div class="auth-wrap">
  <div class="auth-card">
    <h5 class="mb-3"><i class="bi bi-truck"></i> <?= esc($appName) ?> &mdash; Set your password</h5>
    <p class="text-muted" style="font-size:.9rem;">
      Hi <strong><?= esc($invite['name']) ?></strong>, set a password to activate your portal access for
      <strong><?= esc($invite['email']) ?></strong>.
    </p>
    <?php if (!empty($error)): ?>
      <div class="alert alert-danger"><?= esc($error) ?></div>
    <?php endif; ?>
    <form method="post" action="<?= site_url('portal/invite/' . $token) ?>" novalidate>
      <?= csrf_field() ?>
      <div class="mb-3">
        <label class="form-label">New password</label>
        <input type="password" class="form-control" name="password" minlength="8" required autocomplete="new-password">
      </div>
      <div class="mb-3">
        <label class="form-label">Confirm password</label>
        <input type="password" class="form-control" name="password_confirm" minlength="8" required autocomplete="new-password">
      </div>
      <button class="btn btn-primary w-100" type="submit">Activate account</button>
    </form>
  </div>
</div>
</body>
</html>
