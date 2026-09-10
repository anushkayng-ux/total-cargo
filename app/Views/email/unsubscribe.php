<!doctype html>
<html><head><meta charset="utf-8">
<title>Unsubscribe</title>
<style>body{font-family:system-ui,sans-serif;padding:3rem 1rem;text-align:center;color:#333;}.card{max-width:480px;margin:0 auto;background:#fff;border:1px solid #eee;border-radius:10px;padding:2rem;}button{padding:.6rem 1.4rem;border:0;background:#1a1a1a;color:#fff;border-radius:6px;cursor:pointer;}</style>
</head><body>
<div class="card">
  <h2>Unsubscribe?</h2>
  <p>You're about to unsubscribe <strong><?= esc($email) ?></strong> from future emails.</p>
  <form method="post" action="<?= site_url('email-track/unsub/' . $token) ?>?sig=<?= esc($sig ?? '') ?>">
    <button type="submit">Confirm unsubscribe</button>
  </form>
</div>
</body></html>
