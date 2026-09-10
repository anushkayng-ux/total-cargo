<?php
$fmtDate = fn($d) => $d ? date('d-m-Y', strtotime($d)) : '—';
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#0f172a">
<meta name="robots" content="noindex,nofollow">
<title>Trip Feedback · <?= esc($company) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
  :root { --pri:#2563eb; --pri-hover:#1d4ed8; --bg:#f4f6fa; --card:#fff; --br:#e3e7ee; --txt:#0f172a; --txt-soft:#374151; --muted:#6b7280; --r:12px; --ok-bg:#dcfce7; --ok-br:#86efac; --ok-fg:#065f46; --err-bg:#fee2e2; --err-br:#fca5a5; --err-fg:#991b1b; --warn-bg:#fef3c7; --warn-br:#fcd34d; --warn-fg:#92400e; --gold:#f59e0b; }
  * { box-sizing:border-box; -webkit-tap-highlight-color:transparent; }
  body { margin:0; background:var(--bg); color:var(--txt); font-family:'Inter',-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif; font-size:15px; line-height:1.5; padding:1.5rem 1rem 3rem; -webkit-font-smoothing:antialiased; }
  .wrap { max-width:560px; margin:0 auto; }
  .brand { text-align:center; font-weight:700; font-size:1.1rem; margin-bottom:1.5rem; color:var(--txt); }
  .brand i { color:var(--pri); }
  .brand small { display:block; color:var(--muted); font-weight:400; font-size:.78rem; margin-top:.2rem; letter-spacing:.04em; text-transform:uppercase; }
  .card { background:var(--card); border:1px solid var(--br); border-radius:var(--r); padding:1.4rem; margin-bottom:1rem; box-shadow:0 1px 2px rgba(15,23,42,.04); }
  .card h2 { font-size:1rem; font-weight:600; margin:0 0 .8rem; color:var(--txt); letter-spacing:-.005em; }
  .meta { font-size:.9rem; line-height:1.85; }
  .meta strong { color:var(--muted); font-weight:600; font-size:.72rem; text-transform:uppercase; letter-spacing:.05em; min-width:80px; display:inline-block; }
  .alert { padding:.85rem 1.05rem; border-radius:var(--r); margin-bottom:1rem; font-size:.94rem; display:flex; gap:.6rem; align-items:flex-start; }
  .alert .bi { font-size:1.2rem; flex-shrink:0; }
  .alert-ok   { background:var(--ok-bg);   color:var(--ok-fg);   border:1px solid var(--ok-br); }
  .alert-err  { background:var(--err-bg);  color:var(--err-fg);  border:1px solid var(--err-br); }
  .alert-warn { background:var(--warn-bg); color:var(--warn-fg); border:1px solid var(--warn-br); }
  label { display:block; font-weight:600; font-size:.88rem; margin-bottom:.5rem; color:var(--txt-soft); }
  .field { margin-bottom:1.25rem; }

  /* Star rating */
  .stars { display:inline-flex; gap:.25rem; direction:rtl; }
  .stars input { display:none; }
  .stars label {
    margin:0; padding:0; display:inline-block;
    font-size:2rem; line-height:1; color:#d1d5db; cursor:pointer;
    transition:color .12s, transform .08s;
  }
  .stars label:hover, .stars label:hover ~ label,
  .stars input:checked ~ label { color:var(--gold); }
  .stars label:active { transform:scale(1.12); }
  .stars-sm label { font-size:1.6rem; }
  .star-meta { display:inline-flex; align-items:center; gap:.55rem; flex-wrap:wrap; }
  .star-meta .star-label { font-size:.92rem; color:var(--txt); font-weight:500; }

  /* NPS row */
  .nps { display:grid; grid-template-columns:repeat(11, 1fr); gap:4px; }
  .nps input { display:none; }
  .nps label {
    margin:0; cursor:pointer; padding:.55rem .2rem; text-align:center; font-weight:600;
    background:#f3f4f6; border:1px solid #e5e7eb; border-radius:6px; font-size:.82rem;
    color:var(--txt-soft); transition:all .15s;
  }
  .nps label:hover { background:#e5e7eb; }
  .nps input:checked + label { background:var(--pri); color:#fff; border-color:var(--pri); }
  .nps-scale { display:flex; justify-content:space-between; font-size:.7rem; color:var(--muted); margin-top:.35rem; }

  textarea, input[type="text"], input[type="email"], input[type="tel"] {
    width:100%; padding:.7rem .85rem; border:1px solid var(--br); border-radius:8px;
    background:#fff; color:var(--txt); font-family:inherit; font-size:.95rem;
    transition:border-color .15s, box-shadow .15s;
  }
  textarea:focus, input:focus { outline:0; border-color:var(--pri); box-shadow:0 0 0 3px rgba(37,99,235,.15); }
  textarea { min-height:90px; resize:vertical; }

  .btn-primary {
    display:inline-flex; align-items:center; justify-content:center; gap:.5rem;
    width:100%; padding:1rem; border-radius:10px; border:0;
    font-size:1rem; font-weight:600; background:var(--pri); color:#fff; cursor:pointer;
    transition:background .15s, transform .1s;
  }
  .btn-primary:hover { background:var(--pri-hover); }
  .btn-primary:active { transform:scale(.99); }
  .hint { font-size:.78rem; color:var(--muted); margin-top:.3rem; line-height:1.5; }
  .footer { text-align:center; color:var(--muted); font-size:.78rem; margin-top:2rem; }
  .row2 { display:grid; grid-template-columns:1fr 1fr; gap:.8rem; }
  @media (max-width:480px) { .row2 { grid-template-columns:1fr; } }
</style>
</head>
<body>
<div class="wrap">

  <div class="brand">
    <i class="bi bi-truck"></i> <?= esc($company) ?>
    <small>Trip feedback</small>
  </div>

  <?php if ($success): ?>
    <div class="alert alert-ok"><i class="bi bi-check-circle-fill"></i>
      <div>
        <strong>Thank you!</strong> <?= esc($success) ?>
        <div class="hint" style="margin-top:.4rem;">Your feedback helps us improve. The dispatch team will see your rating immediately.</div>
      </div>
    </div>
  <?php endif; ?>
  <?php if ($error): ?>
    <div class="alert alert-err"><i class="bi bi-exclamation-octagon-fill"></i><div><?= esc($error) ?></div></div>
  <?php endif; ?>

  <div class="card">
    <h2><i class="bi bi-file-earmark-text" style="color:var(--muted);"></i> Trip <?= esc($trip['trip_no']) ?></h2>
    <div class="meta">
      <div><strong>Route</strong> <?= esc($trip['route_text'] ?? '—') ?></div>
      <div><strong>LR no.</strong> <?= esc($trip['lr_no'] ?: '—') ?></div>
      <?php if (!empty($trip['vehicle_number'])): ?><div><strong>Vehicle</strong> <?= esc($trip['vehicle_number']) ?></div><?php endif; ?>
      <?php if (!empty($trip['driver_name'])): ?><div><strong>Driver</strong> <?= esc($trip['driver_name']) ?></div><?php endif; ?>
      <?php if (!empty($trip['delivery_datetime'])): ?><div><strong>Delivered</strong> <?= esc($fmtDate($trip['delivery_datetime'])) ?></div><?php endif; ?>
    </div>
  </div>

  <?php if ($submitted): ?>
    <div class="card" style="text-align:center;">
      <div style="font-size:3rem;color:var(--gold);margin-bottom:.5rem;">
        <?php for ($i = 0; $i < (int) $fb['rating_overall']; $i++) echo '★'; ?>
        <?php for ($i = (int) $fb['rating_overall']; $i < 5; $i++) echo '<span style="color:#d1d5db;">★</span>'; ?>
      </div>
      <div style="font-weight:600;color:var(--ok-fg);font-size:1.05rem;">Feedback already received — thank you!</div>
      <div class="hint" style="margin-top:.5rem;">Submitted on <?= esc(date('d-m-Y · H:i', strtotime($fb['submitted_at']))) ?></div>
    </div>
  <?php elseif ($expired): ?>
    <div class="alert alert-warn"><i class="bi bi-hourglass-bottom"></i><div>This feedback link has expired. If you'd still like to share your thoughts, please reply to the original email or contact us directly.</div></div>
  <?php else: ?>

    <form method="post" action="<?= site_url('feedback/' . $token) ?>" class="card" novalidate>
      <h2><i class="bi bi-star" style="color:var(--gold);"></i> How was your experience?</h2>

      <div class="field">
        <label>Overall rating *</label>
        <div class="stars" id="stars-overall">
          <?php for ($i = 5; $i >= 1; $i--): ?>
            <input type="radio" name="rating_overall" id="overall-<?= $i ?>" value="<?= $i ?>" required>
            <label for="overall-<?= $i ?>" title="<?= $i ?> star<?= $i > 1 ? 's' : '' ?>">★</label>
          <?php endfor; ?>
        </div>
      </div>

      <details style="margin-bottom:1.25rem;">
        <summary style="cursor:pointer;color:var(--pri);font-size:.9rem;font-weight:500;">Rate specific aspects (optional)</summary>
        <div style="margin-top:1rem;">
          <?php
          $aspects = [
              'rating_on_time'         => ['On-time delivery',  'bi-clock'],
              'rating_goods_condition' => ['Goods in good condition','bi-box-seam'],
              'rating_driver'          => ['Driver behaviour',   'bi-person-badge'],
              'rating_communication'   => ['Communication',      'bi-chat-dots'],
          ];
          foreach ($aspects as $name => [$label, $icon]): ?>
            <div class="field">
              <div class="star-meta">
                <i class="bi <?= $icon ?>" style="color:var(--muted);"></i>
                <span class="star-label"><?= esc($label) ?></span>
              </div>
              <div class="stars stars-sm" style="margin-top:.4rem;">
                <?php for ($i = 5; $i >= 1; $i--): ?>
                  <input type="radio" name="<?= $name ?>" id="<?= $name . '-' . $i ?>" value="<?= $i ?>">
                  <label for="<?= $name . '-' . $i ?>">★</label>
                <?php endfor; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </details>

      <div class="field">
        <label>How likely are you to recommend us? (0 = not at all, 10 = absolutely)</label>
        <div class="nps">
          <?php for ($i = 0; $i <= 10; $i++): ?>
            <input type="radio" name="nps_score" id="nps-<?= $i ?>" value="<?= $i ?>">
            <label for="nps-<?= $i ?>"><?= $i ?></label>
          <?php endfor; ?>
        </div>
        <div class="nps-scale"><span>Not at all</span><span>Absolutely</span></div>
      </div>

      <div class="field">
        <label>Comments (optional)</label>
        <textarea name="comments" maxlength="4000" placeholder="What went well? What can we improve?"></textarea>
      </div>

      <details style="margin-bottom:1.25rem;">
        <summary style="cursor:pointer;color:var(--pri);font-size:.9rem;font-weight:500;">Add your contact (optional)</summary>
        <div style="margin-top:1rem;">
          <div class="field">
            <label>Your name</label>
            <input type="text" name="submitter_name" maxlength="150">
          </div>
          <div class="row2">
            <div class="field">
              <label>Email</label>
              <input type="email" name="submitter_email" maxlength="150">
            </div>
            <div class="field">
              <label>Phone</label>
              <input type="tel" name="submitter_phone" maxlength="30">
            </div>
          </div>
        </div>
      </details>

      <button class="btn-primary" type="submit"><i class="bi bi-send-fill"></i> Submit feedback</button>
      <p class="hint" style="margin-top:.85rem;text-align:center;">Your feedback is kept confidential and goes straight to the operations team.</p>
    </form>

  <?php endif; ?>

  <div class="footer">Sent by <strong><?= esc($company) ?></strong></div>
</div>
</body>
</html>
