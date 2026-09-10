<?php
$appName = env('tpt.appName', 'TPT Aggregator');
$fmtDate = fn($d) => $d ? date('d-m-Y', strtotime($d)) : '—';
$alreadyQuoted = !empty($existing);
$readOnly = $expired || $closed;
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="#0f172a">
<meta name="robots" content="noindex,nofollow">
<title>Submit Rate · <?= esc($company) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
  :root { --pri:#2563eb; --pri-hover:#1d4ed8; --bg:#f4f6fa; --card:#ffffff; --br:#e3e7ee; --txt:#0f172a; --txt-soft:#374151; --muted:#6b7280; --r:12px; --ok-bg:#dcfce7; --ok-br:#86efac; --ok-fg:#065f46; --warn-bg:#fef3c7; --warn-br:#fcd34d; --warn-fg:#92400e; --err-bg:#fee2e2; --err-br:#fca5a5; --err-fg:#991b1b; }
  * { box-sizing:border-box; -webkit-tap-highlight-color:transparent; }
  body { margin:0; background:var(--bg); color:var(--txt); font-family:'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; font-size:15px; line-height:1.45; padding:1.5rem 1rem 3rem; -webkit-font-smoothing:antialiased; }
  .wrap { max-width:620px; margin:0 auto; }
  .brand { text-align:center; font-weight:700; font-size:1.1rem; margin-bottom:1.5rem; color:var(--txt); }
  .brand small { display:block; color:var(--muted); font-weight:400; font-size:.78rem; margin-top:.2rem; letter-spacing:.04em; text-transform:uppercase; }
  .card { background:var(--card); border:1px solid var(--br); border-radius:var(--r); padding:1.3rem 1.4rem; margin-bottom:1rem; box-shadow:0 1px 2px rgba(15,23,42,.04); }
  .card h2 { font-size:1.05rem; font-weight:600; margin:0 0 .85rem; color:var(--txt); letter-spacing:-.01em; }
  .meta { font-size:.92rem; line-height:1.85; }
  .meta strong { color:var(--muted); font-weight:600; font-size:.74rem; text-transform:uppercase; letter-spacing:.05em; min-width:90px; display:inline-block; }
  .alert { padding:.85rem 1.05rem; border-radius:var(--r); margin-bottom:1rem; font-size:.92rem; display:flex; gap:.6rem; align-items:flex-start; }
  .alert .bi { font-size:1.15rem; margin-top:.05rem; flex-shrink:0; }
  .alert-ok   { background:var(--ok-bg);   color:var(--ok-fg);   border:1px solid var(--ok-br); }
  .alert-warn { background:var(--warn-bg); color:var(--warn-fg); border:1px solid var(--warn-br); }
  .alert-err  { background:var(--err-bg);  color:var(--err-fg);  border:1px solid var(--err-br); }
  label { display:block; font-weight:600; font-size:.85rem; margin-bottom:.35rem; color:var(--txt-soft); }
  .field { margin-bottom:1rem; }
  .row2 { display:grid; grid-template-columns:1fr 1fr; gap:.8rem; }
  input[type="text"], input[type="number"], input[type="date"], textarea {
    width:100%; padding:.7rem .85rem;
    border:1px solid var(--br); border-radius:8px;
    background:#fff; color:var(--txt);
    font-size:.96rem; font-family:inherit;
    transition:border-color .15s, box-shadow .15s;
  }
  textarea { min-height:84px; resize:vertical; }
  input:focus, textarea:focus { outline:0; border-color:var(--pri); box-shadow:0 0 0 3px rgba(37,99,235,.15); }
  .hint { font-size:.78rem; color:var(--muted); margin-top:.3rem; line-height:1.5; }
  .amount-wrap { position:relative; }
  .amount-wrap .currency { position:absolute; top:50%; left:.85rem; transform:translateY(-50%); color:var(--muted); font-weight:600; pointer-events:none; }
  .amount-wrap input { padding-left:1.75rem; font-size:1.05rem; font-weight:600; }
  .btn { display:inline-flex; align-items:center; justify-content:center; gap:.5rem; width:100%; padding:.95rem 1rem; border-radius:10px; border:0; font-size:1rem; font-weight:600; cursor:pointer; transition:background .15s, transform .15s; }
  .btn-primary { background:var(--pri); color:#fff; }
  .btn-primary:hover { background:var(--pri-hover); }
  .btn-primary:active { transform:scale(.99); }
  .footer { text-align:center; color:var(--muted); font-size:.78rem; margin-top:2rem; }
</style>
</head>
<body>
<div class="wrap">

  <div class="brand">
    <i class="bi bi-truck" style="color:var(--pri);"></i> <?= esc($company) ?>
    <small>Submit your rate</small>
  </div>

  <?php if ($success): ?>
    <div class="alert alert-ok"><i class="bi bi-check-circle-fill"></i><div><?= esc($success) ?></div></div>
  <?php endif; ?>
  <?php if ($error): ?>
    <div class="alert alert-err"><i class="bi bi-exclamation-octagon-fill"></i><div><?= esc($error) ?></div></div>
  <?php endif; ?>

  <div class="card">
    <h2><i class="bi bi-file-earmark-text" style="color:var(--muted);"></i> RFQ <?= esc($rfq['rfq_no']) ?></h2>
    <div class="meta">
      <div><strong>Route</strong> <?= esc(trim(($rfq['pickup_city'] ?? '') . ' → ' . ($rfq['drop_city'] ?? ''), ' →')) ?: '—' ?></div>
      <div><strong>Vehicle</strong> <?= esc($rfq['vehicle_type'] ?: '—') ?></div>
      <div><strong>Material</strong> <?= esc($rfq['material_category'] ?: '—') ?></div>
      <div><strong>Weight</strong> <?= esc(($rfq['weight'] ?: '—') . ' ' . ($rfq['weight_unit'] ?: '')) ?></div>
      <div><strong>Loading</strong> <?= esc($fmtDate($rfq['loading_date'])) ?></div>
      <div><strong>Reference</strong> <?= esc($rfq['masked_reference'] ?: $rfq['rfq_no']) ?></div>
    </div>
  </div>

  <?php if ($closed): ?>
    <div class="alert alert-warn"><i class="bi bi-lock-fill"></i><div>This RFQ is closed and no longer accepting quotes. If you have a query, please contact us directly.</div></div>
  <?php elseif ($expired): ?>
    <div class="alert alert-warn"><i class="bi bi-hourglass-bottom"></i><div>This quote link has expired. Please contact us to receive a fresh link.</div></div>
  <?php elseif ($alreadyQuoted): ?>
    <div class="alert alert-ok"><i class="bi bi-check2-circle"></i>
      <div>
        <strong>Quote already submitted</strong> — ₹<?= esc(number_format((float) $existing['quote_amount'], 0)) ?>
        <?php if (!empty($existing['transit_days'])): ?> · <?= esc($existing['transit_days']) ?> days transit<?php endif; ?>.
        You can update it below if needed.
      </div>
    </div>
  <?php endif; ?>

  <?php if (!$closed && !$expired): ?>
  <form method="post" action="<?= site_url('quote/' . $token) ?>" class="card" novalidate>
    <h2><i class="bi bi-cash-coin" style="color:var(--muted);"></i> <?= $alreadyQuoted ? 'Update your rate' : 'Your rate' ?></h2>

    <div class="field">
      <label for="quote_amount">Total rate <span style="color:var(--muted);font-weight:500;">(₹, all-inclusive)</span></label>
      <div class="amount-wrap">
        <span class="currency">₹</span>
        <input id="quote_amount" name="quote_amount" type="number" min="1" step="1" inputmode="numeric"
               value="<?= esc($existing['quote_amount'] ?? '') ?>" placeholder="e.g. 45000" required>
      </div>
    </div>

    <div class="row2">
      <div class="field">
        <label for="transit_days">Transit days</label>
        <input id="transit_days" name="transit_days" type="number" min="0" max="60" inputmode="numeric"
               value="<?= esc($existing['transit_days'] ?? '') ?>" placeholder="e.g. 3">
      </div>
      <div class="field">
        <label for="quote_valid_till">Rate valid till</label>
        <input id="quote_valid_till" name="quote_valid_till" type="date"
               value="<?= esc($existing['quote_valid_till'] ?? '') ?>">
      </div>
    </div>

    <div class="field">
      <label for="availability_notes">Availability / placement notes</label>
      <input id="availability_notes" name="availability_notes" type="text" maxlength="240"
             value="<?= esc($existing['availability_notes'] ?? '') ?>"
             placeholder="e.g. Can place vehicle by 8 AM at loading point">
    </div>

    <div class="field">
      <label for="remarks">Remarks (optional)</label>
      <textarea id="remarks" name="remarks" maxlength="2000" placeholder="Any other terms or notes for the dispatch team"><?= esc($existing['remarks'] ?? '') ?></textarea>
    </div>

    <button class="btn btn-primary" type="submit">
      <i class="bi bi-send-fill"></i> <?= $alreadyQuoted ? 'Update rate' : 'Submit rate' ?>
    </button>
    <p class="hint" style="margin-top:.85rem;text-align:center;">This link is unique to your firm — please don't share it.</p>
  </form>
  <?php endif; ?>

  <div class="footer">Sent by <strong><?= esc($company) ?></strong></div>
</div>
</body>
</html>
