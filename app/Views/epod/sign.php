<!doctype html>
<html lang="en"><head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#1d6cb1">
<meta name="robots" content="noindex,nofollow">
<title>e-POD · <?= esc($appName) ?></title>
<style>
  body { font-family:-apple-system,system-ui,'Segoe UI',Roboto,sans-serif; background:#f4f4f4; color:#222; margin:0; }
  .wrap { padding:1rem; max-width:560px; margin:0 auto; }
  .card { background:#fff; border:1px solid #e5e5e5; border-radius:10px; padding:1.1rem; margin-bottom:1rem; }
  .card h1 { margin:0 0 .8rem; font-size:1.1rem; font-weight:600; }
  label { display:block; font-size:.78rem; color:#666; margin-bottom:.2rem; margin-top:.6rem; }
  input[type=text], input[type=tel], textarea { width:100%; padding:.6rem .7rem; border:1px solid #ccc; border-radius:6px; font-size:1rem; box-sizing:border-box; }
  textarea { resize:vertical; min-height:60px; }
  .pad-wrap { background:#fff; border:2px dashed #ccc; border-radius:8px; touch-action:none; }
  canvas { width:100%; height:240px; display:block; }
  .btn { display:inline-block; padding:1rem 1.2rem; border-radius:8px; border:0; font-weight:600; cursor:pointer; -webkit-tap-highlight-color:transparent; }
  .btn-primary { background:#1d6cb1; color:#fff; width:100%; font-size:1.05rem; }
  .btn-light { background:#eee; color:#222; }
  .alert { padding:.6rem .8rem; border-radius:6px; margin-bottom:.8rem; font-size:.92rem; }
  .alert-error   { background:#fde8e8; color:#a01d22; border:1px solid #f5bcbc; }
  .alert-warn    { background:#fff3cd; color:#856404; border:1px solid #ffeeba; }
  .alert-success { background:#e6f4ea; color:#166c3b; border:1px solid #bfe0c9; }
  .check { display:flex; gap:.5rem; align-items:center; margin-top:.5rem; font-size:.92rem; }
  .meta { color:#666; font-size:.85rem; line-height:1.55; }
  .meta strong { color:#222; font-weight:600; }
</style>
</head><body>
<div class="wrap">

  <div class="card">
    <h1>📋 Confirm delivery</h1>
    <div class="meta">
      <div><strong>Trip:</strong> <?= esc($trip['trip_no']) ?> <?= !empty($trip['lr_no']) ? '· LR ' . esc($trip['lr_no']) : '' ?></div>
      <?php if (!empty($route)): ?><div><strong>Route:</strong> <?= esc($route) ?></div><?php endif; ?>
      <?php if (!empty($trip['vehicle_number'])): ?><div><strong>Vehicle:</strong> <?= esc($trip['vehicle_number']) ?></div><?php endif; ?>
    </div>
  </div>

  <?php if (!empty($existing)): ?>
    <div class="alert alert-warn">
      Already signed by <strong><?= esc($existing['consignee_name']) ?></strong> on <?= esc(date('d-m-Y H:i', strtotime($existing['signed_at']))) ?>.
      You can submit again to add a fresh signature.
    </div>
  <?php endif; ?>

  <?php $err = session()->getFlashdata('error'); if ($err): ?>
    <div class="alert alert-error"><?= esc($err) ?></div>
  <?php endif; ?>

  <form method="post" action="<?= site_url('epod/' . $token . '/submit') ?>" id="sigForm">
    <?= csrf_field() ?>
    <div class="card">
      <label for="cn">Receiver / consignee name *</label>
      <input id="cn" type="text" name="consignee_name" value="<?= esc($consignee_name_hint ?? '') ?>" required>

      <label for="cm">Mobile</label>
      <input id="cm" type="tel" name="consignee_mobile" inputmode="tel" pattern="[0-9+\- ]*">

      <label>Signature *</label>
      <div class="pad-wrap"><canvas id="pad"></canvas></div>
      <div style="display:flex;gap:.5rem;margin-top:.4rem;">
        <button type="button" class="btn btn-light" onclick="clearPad();">Clear</button>
      </div>

      <label for="rm">Remarks</label>
      <textarea id="rm" name="remarks" placeholder="Any notes about the delivery"></textarea>

      <label class="check"><input type="checkbox" name="damage_noted" value="1"> Damage noted on delivery</label>
      <label class="check"><input type="checkbox" name="shortage_noted" value="1"> Shortage noted on delivery</label>

      <input type="hidden" name="signature_data" id="sigData">
      <input type="hidden" name="geo_lat" id="geoLat">
      <input type="hidden" name="geo_lng" id="geoLng">

      <button type="submit" class="btn btn-primary" style="margin-top:1rem;">Confirm delivery</button>
    </div>
  </form>

</div>

<script>
(function(){
  var pad = document.getElementById('pad');
  var ctx = pad.getContext('2d');
  function resize() {
    var rect = pad.getBoundingClientRect();
    var dpr = window.devicePixelRatio || 1;
    pad.width  = rect.width  * dpr;
    pad.height = rect.height * dpr;
    ctx.scale(dpr, dpr);
    ctx.lineWidth   = 2;
    ctx.lineCap     = 'round';
    ctx.lineJoin    = 'round';
    ctx.strokeStyle = '#111';
  }
  resize();
  window.addEventListener('resize', resize);

  var drawing = false;
  function pos(e) {
    var rect = pad.getBoundingClientRect();
    var t = e.touches ? e.touches[0] : e;
    return [t.clientX - rect.left, t.clientY - rect.top];
  }
  function down(e) { drawing = true; ctx.beginPath(); var p = pos(e); ctx.moveTo(p[0], p[1]); e.preventDefault(); }
  function move(e) { if (!drawing) return; var p = pos(e); ctx.lineTo(p[0], p[1]); ctx.stroke(); e.preventDefault(); }
  function up()    { drawing = false; }
  pad.addEventListener('mousedown', down);
  pad.addEventListener('mousemove', move);
  pad.addEventListener('mouseup',   up);
  pad.addEventListener('mouseleave',up);
  pad.addEventListener('touchstart', down, {passive:false});
  pad.addEventListener('touchmove',  move, {passive:false});
  pad.addEventListener('touchend',   up);
  window.clearPad = function () { ctx.clearRect(0, 0, pad.width, pad.height); };

  document.getElementById('sigForm').addEventListener('submit', function(e){
    document.getElementById('sigData').value = pad.toDataURL('image/png');
  });

  if ('geolocation' in navigator) {
    navigator.geolocation.getCurrentPosition(function(p){
      document.getElementById('geoLat').value = p.coords.latitude.toFixed(7);
      document.getElementById('geoLng').value = p.coords.longitude.toFixed(7);
    }, function(){}, {timeout:5000, maximumAge:60000});
  }
})();
</script>
</body></html>
