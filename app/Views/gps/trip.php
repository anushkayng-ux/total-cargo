<?php
$trail = [];
foreach ($logs as $l) {
    if ($l['latitude'] && $l['longitude']) {
        $trail[] = [(float) $l['latitude'], (float) $l['longitude']];
    }
}
$latestPt = null;
if ($latest && $latest['latitude'] && $latest['longitude']) {
    $latestPt = ['lat' => (float) $latest['latitude'], 'lng' => (float) $latest['longitude']];
}
?>
<div class="retro-toolbar is-sticky">
  <form method="post" action="<?= site_url('gps/trip/' . $trip['id'] . '/refresh') ?>" class="d-inline">
    <?= csrf_field() ?>
    <button type="submit" class="retro-tbtn retro-primary"><i class="bi bi-arrow-clockwise"></i>Refresh Now</button>
  </form>
  <a class="retro-tbtn" href="<?= site_url('trips/' . $trip['id']) ?>"><i class="bi bi-truck"></i>Trip Detail</a>
  <a class="retro-tbtn" href="<?= site_url('gps') ?>"><i class="bi bi-x-lg"></i>Close</a>
</div>

<div class="tabs" role="tablist">
  <div class="tab active">Live Map</div>
  <div class="spacer"></div>
  <div class="recordnav">
    <a href="<?= site_url('gps') ?>"><i class="bi bi-list"></i> Fleet</a> &middot;
    Trip <?= esc($trip['trip_no']) ?>
    <?php if (!empty($latest['delay_flag'])): ?> &middot; <span class="badge-soft badge-danger">Delay flagged</span><?php endif; ?>
  </div>
</div>

<div class="retro-detail">
  <div class="retro-detail-main" style="padding:0;">
    <div id="tripmap" style="height: 100%; min-height:540px;"></div>
  </div>
  <div class="retro-detail-side">
    <h4>Vehicle :</h4>
    <div class="remarksbox">
      <code><?= esc($trip['vehicle_number'] ?? '—') ?></code><br>
      <span style="color:var(--v2-fg-muted);">Driver</span><br><?= esc($trip['driver_name'] ?? '—') ?> · <?= esc($trip['driver_mobile'] ?? '') ?><br>
      <span style="color:var(--v2-fg-muted);">Route</span><br><?= esc($trip['route_text'] ?? '—') ?><br>
      <span style="color:var(--v2-fg-muted);">Status</span><br><span class="badge-soft"><?= esc($trip['current_status']) ?></span>
    </div>
    <h4 style="margin-top:14px;">Last Fix :</h4>
    <div class="remarksbox">
      <?= esc($latest['gps_timestamp'] ?? '—') ?><br>
      <span style="color:var(--v2-fg-muted);">Coordinates</span><br><?= $latest ? esc($latest['latitude']) . ', ' . esc($latest['longitude']) : '—' ?><br>
      <span style="color:var(--v2-fg-muted);">Speed</span><br><?= $latest && $latest['speed'] !== null ? esc($latest['speed']) . ' km/h' : '—' ?><br>
      <span style="color:var(--v2-fg-muted);">Address</span><br><?= esc($latest['address'] ?? '—') ?>
    </div>
    <?php if (!$service->isConfigured()): ?>
      <div class="alert alert-danger mt-2 mb-0" style="font-size:.8rem;">
        LocoNav API key not set. Fill <code>loconav.apiKey</code> in <code>.env</code> for live updates.
      </div>
    <?php endif; ?>
  </div>
</div>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
(function () {
  const trail = <?= json_encode($trail) ?>;
  const latest = <?= $latestPt ? json_encode($latestPt) : 'null' ?>;
  const map = L.map('tripmap', { zoomControl: true });
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap', maxZoom: 19,
  }).addTo(map);

  if (!trail.length && !latest) {
    map.setView([22.9734, 78.6569], 5);
    return;
  }
  if (trail.length > 1) {
    const poly = L.polyline(trail, { weight: 4, color: '#333' }).addTo(map);
    map.fitBounds(poly.getBounds(), { padding: [30, 30] });
    L.circleMarker(trail[0], { radius: 6, color: '#166c3b', fillColor: '#166c3b', fillOpacity: 1 })
      .addTo(map).bindPopup('Start');
  }
  if (latest) {
    L.marker([latest.lat, latest.lng]).addTo(map).bindPopup('Last known position');
    if (trail.length <= 1) map.setView([latest.lat, latest.lng], 10);
  }
  setTimeout(() => map.invalidateSize(), 50);
})();
</script>
