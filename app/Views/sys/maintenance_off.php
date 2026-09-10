<!doctype html>
<html lang="en"><head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Down for maintenance</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
  body { font-family: 'Poppins', sans-serif; background:#f4f4f4; color:#333; margin:0; min-height:100vh; display:flex; align-items:center; justify-content:center; padding:1rem; }
  .card { background:#fff; border:1px solid #eee; border-radius:10px; padding:2rem; max-width:520px; text-align:center; }
  .icon { font-size:2.4rem; margin-bottom:1rem; }
  .msg { color:#555; font-size:.95rem; line-height:1.6; }
</style>
</head><body>
<div class="card">
  <div class="icon">🛠️</div>
  <h2 style="margin:0 0 .8rem 0;">We'll be right back</h2>
  <p class="msg"><?= esc($message ?? 'The system is undergoing maintenance. Please check back shortly.') ?></p>
</div>
</body></html>
