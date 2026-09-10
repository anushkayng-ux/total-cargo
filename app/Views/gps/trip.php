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
<div class="d-flex align-items-center mb-3 gap-2 flex-wrap">
  <h5 class="m-0">GPS · <code><?= esc($trip['trip_no']) ?></code></h5>
  <?php if (!empty($latest['delay_flag'])): ?><span class="badge-soft badge-danger">Delay flagged</span><?php endif; ?>
  <a class="ms-auto btn btn-sm btn-light" href="<?= site_url('gps') ?>"><i class="bi bi-arrow-left"></i> Fleet</a>
  <a class="btn btn-sm btn-light" href="<?= site_url('trips/' . $trip['id']) ?>"><i class="bi bi-truck"></i> Trip Detail</a>
  <form method="post" action="<?= site_url('gps/trip/' . $trip['id'] . '/refresh') ?>" class="d-inline">
    <?= csrf_field() ?><button class="btn btn-sm btn-primary"><i class="bi bi-arrow-clockwise"></i> Refresh Now</button>
  </form>
</div>

<div class="row g-3">
  <div class="col-lg-9">
    <div class="card"><div class="card-body p-0">
      <div id="tripmap" style="height: 540px; border-radius: 10px;"></div>
    </div></div>
  </div>

  <div class="col-lg-3">
    <div class="card mb-3"><div class="card-body" style="font-size:.9rem;">
      <div class="text-muted">Vehicle</div><div><code><?= esc($trip['vehicle_number'] ?? '—') ?></code></div>
      <div class="text-muted mt-2">Driver</div><div><?= esc($trip['driver_name'] ?? '—') ?> · <?= esc($trip['driver_mobile'] ?? '') ?></div>
      <div class="text-muted mt-2">Route</div><div><?= esc($trip['route_text'] ?? '—') ?></div>
      <div class="text-muted mt-2">Status</div><div><span class="badge-soft"><?= esc($trip['current_status']) ?></span></div>
    </div></div>

    <div class="card"><div class="card-body" style="font-size:.9rem;">
      <div class="text-muted">Last Fix</div>
      <div><?= esc($latest['gps_timestamp'] ?? '—') ?></div>
      <div class="text-muted mt-2">Coordinates</div>
      <div><?= $latest ? esc($latest['latitude']) . ', ' . esc($latest['longitude']) : '—' ?></div>
      <div class="text-muted mt-2">Speed</div>
      <div><?= $latest && $latest['speed'] !== null ? esc($latest['speed']) . ' km/h' : '—' ?></div>
      <div class="text-muted mt-2">Address</div>
      <div><?= esc($latest['address'] ?? '—') ?></div>
      <?php if (!$service->isConfigured()): ?>
        <div class="alert alert-danger mt-2 mb-0" style="font-size:.8rem;">
          LocoNav API key not set. Fill <code>loconav.apiKey</code> in <code>.env</code> for live updates.
        </div>
      <?php endif; ?>
    </div></div>
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
})();
</script>
