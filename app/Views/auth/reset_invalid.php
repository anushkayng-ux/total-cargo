<!DOCTYPE html>
<html><head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Reset link not valid</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
</head><body style="background:#f4f4f4;min-height:100vh;display:flex;align-items:center;justify-content:center;font-family:system-ui;">
<div class="card" style="max-width:440px;width:90%;border-radius:14px;">
  <div class="card-body text-center p-4">
    <div style="width:60px;height:60px;border-radius:50%;background:#fee2e2;color:#991b1b;display:inline-flex;align-items:center;justify-content:center;font-size:1.8rem;margin-bottom:.8rem;"><i class="bi bi-exclamation-octagon"></i></div>
    <h5>Reset link not valid</h5>
    <p class="text-muted">This password reset link has expired, been used, or is invalid. Request a fresh one below.</p>
    <a class="btn btn-primary mt-2" href="<?= site_url('forgot') ?>">Request a new reset link</a>
  </div>
</div>
</body></html>
