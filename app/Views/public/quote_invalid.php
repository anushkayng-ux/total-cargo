<?php $appName = env('tpt.appName', 'TPT Aggregator'); ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title>Link not valid · <?= esc($appName) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
  body { margin:0; background:#f4f6fa; color:#0f172a; font-family:'Inter', -apple-system, "Segoe UI", Roboto, sans-serif; min-height:100vh; display:flex; align-items:center; justify-content:center; padding:2rem 1rem; }
  .card { background:#fff; border:1px solid #e3e7ee; border-radius:14px; padding:2rem 1.6rem; max-width:420px; width:100%; text-align:center; }
  .card .ic { width:64px; height:64px; border-radius:50%; background:#fee2e2; color:#991b1b; display:inline-flex; align-items:center; justify-content:center; font-size:1.8rem; margin-bottom:1rem; }
  h1 { font-size:1.15rem; font-weight:700; margin:0 0 .6rem; }
  p  { color:#6b7280; font-size:.92rem; line-height:1.6; margin:0; }
</style>
</head>
<body>
<div class="card">
  <div class="ic"><i class="bi bi-exclamation-octagon"></i></div>
  <h1>Link not valid</h1>
  <p>This quote link is expired, revoked or invalid. Please contact the dispatch team for a fresh link.</p>
</div>
</body>
</html>
