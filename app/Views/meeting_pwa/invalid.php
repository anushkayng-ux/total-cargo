<!doctype html>
<html><head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Cannot open meeting</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<style>body{font-family:'Inter',system-ui,-apple-system,sans-serif;margin:0;background:#f4f6fa;color:#0f172a;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:2rem 1rem;}.box{background:#fff;border:1px solid #e3e7ee;border-radius:14px;padding:2rem 1.6rem;max-width:440px;width:100%;text-align:center;}.ic{width:64px;height:64px;border-radius:50%;background:#fee2e2;color:#991b1b;display:inline-flex;align-items:center;justify-content:center;font-size:1.8rem;margin-bottom:1rem;}h1{font-size:1.15rem;font-weight:700;margin:0 0 .6rem;}p{color:#6b7280;font-size:.92rem;line-height:1.6;margin:0 0 1rem;}a.btn{display:inline-block;background:#2563eb;color:#fff;border-radius:8px;padding:.55rem 1rem;text-decoration:none;font-weight:600;font-size:.88rem;}</style>
</head><body>
<div class="box">
  <div class="ic"><i class="bi bi-exclamation-octagon"></i></div>
  <h1>Can't open meeting</h1>
  <p><?= isset($message) && $message !== '' ? esc($message) : 'This meeting link is invalid, cancelled, or revoked.' ?></p>
  <a class="btn" href="<?= site_url('meetings') ?>">Back to my meetings</a>
</div></body></html>
