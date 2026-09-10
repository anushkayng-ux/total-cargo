<?php
$fmtDate = fn($d) => $d ? date('d-m-Y · H:i', strtotime($d)) : null;
$milestones = [
    'Vehicle Placed'  => ['Vehicle placed at pickup',  'bi-truck'],
    'Loading'         => ['Loading at pickup',          'bi-box-arrow-in-up'],
    'In Transit'      => ['In transit',                 'bi-broadcast-pin'],
    'Arrived'         => ['Arrived at destination',     'bi-geo-alt-fill'],
    'Unloading'       => ['Unloading',                  'bi-box-arrow-down'],
    'Delivered'       => ['Delivered',                  'bi-check-circle-fill'],
    'POD Received'    => ['POD received',               'bi-clipboard-check'],
];
$currentIndex = array_search($trip['current_status'], array_keys($milestones), true);
?><!DOCTYPE html>
<html lang="en"><head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#2563eb">
<meta name="robots" content="noindex,nofollow">
<title>Track <?= esc($trip['trip_no']) ?> · <?= esc($company) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" rel="stylesheet">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
  :root { --pri:#2563eb; --bg:#f4f6fa; --card:#fff; --br:#e3e7ee; --txt:#0f172a; --muted:#6b7280; }
  * { box-sizing:border-box; -webkit-tap-highlight-color:transparent; }
  body { margin:0; background:var(--bg); color:var(--txt); font-family:'Inter',-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif; font-size:15px; line-height:1.45; -webkit-font-smoothing:antialiased; }
  .wrap { max-width:560px; margin:0 auto; padding:1.2rem .9rem 2rem; }
  .brand { text-align:center; font-weight:700; font-size:1.05rem; color:var(--txt); margin-bottom:1.2rem; }
  .brand i { color:var(--pri); }
  .brand small { display:block; color:var(--muted); font-weight:400; font-size:.75rem; margin-top:.2rem; letter-spacing:.04em; text-transform:uppercase; }
  .card { background:var(--card); border:1px solid var(--br); border-radius:12px; padding:1rem 1.1rem; margin-bottom:.9rem; box-shadow:0 1px 2px rgba(15,23,42,.04); }
  .card h2 { font-size:.92rem; font-weight:600; color:#374151; margin:0 0 .8rem; letter-spacing:.005em; display:flex; align-items:center; gap:.45rem; }
  .card h2 .ic { color:var(--muted); font-size:1rem; }

  .hero { text-align:center; padding:1.3rem 1rem 1.1rem; background:linear-gradient(180deg, #f3f5f9 0%, #fff 100%); border:1px solid var(--br); border-radius:14px; box-shadow:0 1px 2px rgba(15,23,42,.04); margin-bottom:.9rem; }
  .hero h1 { font-size:1.05rem; font-weight:700; margin:0 0 .5rem; letter-spacing:-.01em; }
  .hero .ic { font-size:2.4rem; color:var(--pri); margin-bottom:.5rem; }
  .pill { display:inline-flex; align-items:center; gap:.4rem; padding:.42rem .8rem; border-radius:999px; font-size:.78rem; font-weight:600; background:#dbeafe; color:#1e40af; border:1px solid #bfdbfe; }
  .pill .blip { width:7px; height:7px; border-radius:50%; background:#2563eb; box-shadow:0 0 0 3px rgba(37,99,235,.18); animation:pulse 1.7s infinite; }
  .pill.delivered { background:#dcfce7; color:#065f46; border-color:#86efac; }
  .pill.delivered .blip { background:#22c55e; box-shadow:0 0 0 3px rgba(34,197,94,.2); animation:none; }
  @keyframes pulse { 0%,100%{opacity:.55;} 50%{opacity:1;} }

  .meta-row { display:flex; align-items:baseline; padding:.18rem 0; font-size:.88rem; }
  .meta-row .lbl { color:var(--muted); font-size:.66rem; text-transform:uppercase; letter-spacing:.06em; font-weight:600; min-width:90px; }
  .meta-row .val { color:var(--txt); font-weight:500; flex:1; }

  #map { width:100%; height:340px; border-radius:10px; }
  .last-fix { font-size:.78rem; color:var(--muted); margin-top:.5rem; }
  .last-fix .pulse { color:#22c55e; }

  .step { display:flex; align-items:flex-start; gap:.85rem; padding:.55rem 0; border-bottom:1px solid #f1f3f5; }
  .step:last-child { border-bottom:0; }
  .step .ic { width:28px; height:28px; border-radius:50%; background:#e5e7eb; color:#9ca3af; display:inline-flex; align-items:center; justify-content:center; font-size:.85rem; flex-shrink:0; }
  .step.done .ic { background:#22c55e; color:#fff; }
  .step.current .ic { background:var(--pri); color:#fff; box-shadow:0 0 0 3px rgba(37,99,235,.2); }
  .step .body { flex:1; }
  .step .body .lbl { font-size:.88rem; font-weight:600; color:var(--txt); }
  .step .body .ts { font-size:.74rem; color:var(--muted); margin-top:.1rem; }

  .footer { text-align:center; color:var(--muted); font-size:.78rem; margin-top:1.5rem; }
</style>
</head><body>
<div class="wrap">

  <div class="brand">
    <i class="bi bi-truck"></i> <?= esc($company) ?>
    <small>Live trip tracking</small>
  </div>

  <div class="hero">
    <i class="bi bi-geo-alt-fill ic"></i>
    <h1><?= esc($trip['trip_no']) ?> <?php if (!empty($trip['lr_no'])): ?><span style="color:var(--muted);font-weight:500;">· LR <?= esc($trip['lr_no']) ?></span><?php endif; ?></h1>
    <?php $delivered = in_array($trip['current_status'], ['Delivered','POD Received','Closed'], true); ?>
    <span class="pill <?= $delivered ? 'delivered' : '' ?>" id="statusPill"><span class="blip"></span><span id="statusText"><?= esc($trip['current_status']) ?></span></span>
  </div>

  <div class="card">
    <h2><i class="bi bi-info-circle ic"></i> Trip details</h2>
    <div class="meta-row"><span class="lbl">Route</span><span class="val"><?= esc($trip['route_text'] ?: '—') ?></span></div>
    <?php if (!empty($trip['vehicle_number'])): ?><div class="meta-row"><span class="lbl">Vehicle</span><span class="val"><?= esc($trip['vehicle_number']) ?></span></div><?php endif; ?>
    <?php if (!empty($trip['driver_name'])): ?><div class="meta-row"><span class="lbl">Driver</span><span class="val"><?= esc($trip['driver_name']) ?></span></div><?php endif; ?>
    <?php if ($trip['dispatch_datetime']): ?><div class="meta-row"><span class="lbl">Dispatched</span><span class="val"><?= esc($fmtDate($trip['dispatch_datetime'])) ?></span></div><?php endif; ?>
    <?php if ($trip['delivery_datetime']): ?><div class="meta-row"><span class="lbl">Delivered</span><span class="val"><?= esc($fmtDate($trip['delivery_datetime'])) ?></span></div><?php endif; ?>
  </div>

  <div class="card">
    <h2><i class="bi bi-map ic"></i> Live location</h2>
    <div id="map"></div>
    <div class="last-fix" id="lastFix">Waiting for the first GPS fix…</div>
  </div>

  <div class="card">
    <h2><i class="bi bi-list-check ic"></i> Status timeline</h2>
    <?php
    $byStatus = [];
    foreach ($history as $h) if (!isset($byStatus[$h['new_status']])) $byStatus[$h['new_status']] = $h;
    $keys = array_keys($milestones);
    $curIdx = array_search($trip['current_status'], $keys, true);
    foreach ($milestones as $st => [$label, $icon]):
        $hi = $byStatus[$st] ?? null;
        $idx = array_search($st, $keys, true);
        $done    = $hi !== null || ($curIdx !== false && $idx !== false && $idx < $curIdx);
        $current = $st === $trip['current_status'];
    ?>
      <div class="step <?= $current ? 'current' : ($done ? 'done' : '') ?>">
        <div class="ic"><i class="bi <?= esc($icon) ?>"></i></div>
        <div class="body">
          <div class="lbl"><?= esc($label) ?></div>
          <?php if ($hi): ?>
            <div class="ts"><?= esc($fmtDate($hi['changed_at'])) ?></div>
          <?php elseif ($current): ?>
            <div class="ts">in progress…</div>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="footer">Sent by <strong><?= esc($company) ?></strong> · <span style="color:#9ca3af;">link expires <?= esc($trip['client_track_expires_at'] ? date('d-m-Y', strtotime($trip['client_track_expires_at'])) : '—') ?></span></div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
<script>
(function () {
  const DATA_URL = <?= json_encode(site_url('track/' . $token . '/data')) ?>;
  const initial = <?= json_encode([
      'lat' => $latest['latitude']  ?? null,
      'lng' => $latest['longitude'] ?? null,
  ]) ?>;
  const map = L.map('map', { zoomControl: true, attributionControl: true });
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19, attribution: '© OpenStreetMap',
  }).addTo(map);

  // Default view: India center if no fix
  if (initial.lat && initial.lng) map.setView([initial.lat, initial.lng], 12);
  else map.setView([22.3511, 78.6677], 5);

  let marker = null;
  let polyline = null;

  async function refresh() {
    try {
      const r = await fetch(DATA_URL, { credentials: 'same-origin' });
      if (!r.ok) return;
      const j = await r.json();
      if (!j.ok) return;

      // Update status pill
      const pill = document.getElementById('statusPill');
      const txt  = document.getElementById('statusText');
      if (txt && j.status) {
        txt.textContent = j.status;
        const delivered = ['Delivered','POD Received','Closed'].includes(j.status);
        pill.classList.toggle('delivered', delivered);
      }

      // Polyline of recent path
      if (j.pings && j.pings.length > 1) {
        const latlngs = j.pings.map(p => [p.lat, p.lng]);
        if (polyline) map.removeLayer(polyline);
        polyline = L.polyline(latlngs, { color: '#2563eb', weight: 4, opacity: .65 }).addTo(map);
      }

      // Current marker
      if (j.latest) {
        const pos = [j.latest.lat, j.latest.lng];
        if (!marker) {
          marker = L.marker(pos).addTo(map);
          map.setView(pos, 14);
        } else {
          marker.setLatLng(pos);
        }
        const ts = j.latest.ts ? new Date(j.latest.ts.replace(' ', 'T')).toLocaleString([], { dateStyle:'medium', timeStyle:'short' }) : '—';
        const speed = j.latest.speed !== null && j.latest.speed !== undefined ? Math.round(j.latest.speed) + ' km/h' : '';
        document.getElementById('lastFix').innerHTML =
          '<span class="pulse">●</span> Last update ' + ts + (speed ? ' · ' + speed : '') + (j.latest.source ? ' · via ' + j.latest.source : '');
      } else {
        document.getElementById('lastFix').textContent = 'No GPS fix yet — the driver app may not be running.';
      }
    } catch (e) {}
  }

  refresh();
  setInterval(refresh, 30000);
})();
</script>
</body></html>
