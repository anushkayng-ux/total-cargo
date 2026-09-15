<?php
$points = [];
foreach ($rows as $r) {
    if ($r['latitude'] && $r['longitude']) {
        $points[] = [
            'id'     => $r['trip_id'],
            'no'     => $r['trip_no'],
            'veh'    => $r['vehicle_number'],
            'lat'    => (float) $r['latitude'],
            'lng'    => (float) $r['longitude'],
            'speed'  => (float) ($r['speed'] ?? 0),
            'addr'   => (string) ($r['address'] ?? ''),
            'when'   => (string) ($r['gps_timestamp'] ?? ''),
            'client' => (string) ($r['client_company'] ?? ''),
            'route'  => (string) ($r['route_text'] ?? ''),
            'delay'  => (int) ($r['delay_flag'] ?? 0),
            'url'    => site_url('gps/trip/' . $r['trip_id']),
        ];
    }
}
?>
<?php
$extra = !$service->isConfigured() ? '<span class="badge-soft badge-warn">LocoNav API key not set — refresh will no-op</span>' : '';
$extra .= '<form method="post" action="' . site_url('gps/refresh-all') . '" class="d-inline">' . csrf_field() . '<button class="btn btn-sm btn-primary"><i class="bi bi-arrow-clockwise"></i> Refresh All</button></form>';
echo tpt_toolbar([
    'close_href' => site_url('dashboard'),
    'extra'      => $extra,
    'auth'       => $auth,
]);
?>
<div class="tabs" role="tablist">
  <div class="tab active">Fleet Tracker</div>
  <div class="spacer"></div>
  <div class="recordnav"><?= count($rows) ?> active trips</div>
</div>

<div class="formwrap" style="padding:0;flex:0 0 auto;">
  <div id="map" style="height: 540px; border-radius: 0;"></div>
</div>

<div class="gridwrap" style="margin-top:14px;">
  <div class="table-responsive">
    <table class="table grid mb-0">
      <thead>
        <tr><th>Trip</th><th>Vehicle</th><th>Client</th><th>Route</th><th>Last Fix</th><th>Speed</th><th>Location</th><th>Status</th><th></th></tr>
      </thead>
      <tbody>
        <?php if (empty($rows)): ?><tr><td colspan="9" class="text-center text-muted">No active trips.</td></tr><?php endif; ?>
        <?php foreach ($rows as $r): ?>
          <tr class="row-link" data-href="<?= site_url('gps/trip/' . $r['trip_id']) ?>">
            <td data-label="Trip"><a href="<?= site_url('gps/trip/' . $r['trip_id']) ?>"><code><?= esc($r['trip_no']) ?></code></a></td>
            <td data-label="Vehicle"><code><?= esc($r['vehicle_number']) ?></code></td>
            <td data-label="Client"><?= esc($r['client_company']) ?></td>
            <td data-label="Route"><?= esc($r['route_text']) ?></td>
            <td data-label="Last Fix"><?= esc($r['gps_timestamp'] ?? '—') ?></td>
            <td data-label="Speed"><?= $r['speed'] !== null ? esc($r['speed']) . ' km/h' : '—' ?></td>
            <td data-label="Location" style="max-width:320px;"><?= esc($r['address']) ?></td>
            <td data-label="Status">
              <span class="badge-soft"><?= esc($r['current_status']) ?></span>
              <?php if (!empty($r['delay_flag'])): ?><span class="badge-soft badge-danger ms-1">Delay</span><?php endif; ?>
            </td>
            <td class="text-end"><a class="btn btn-sm btn-light" href="<?= site_url('gps/trip/' . $r['trip_id']) ?>"><i class="bi bi-geo-alt"></i></a></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
(function () {
  const points = <?= json_encode($points, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
  const map = L.map('map', { zoomControl: true });
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap', maxZoom: 19,
  }).addTo(map);
  if (!points.length) {
    map.setView([22.9734, 78.6569], 5); // centre of India
    return;
  }
  const bounds = L.latLngBounds(points.map(p => [p.lat, p.lng]));
  map.fitBounds(bounds, { padding: [30, 30] });
  points.forEach(p => {
    const m = L.marker([p.lat, p.lng]).addTo(map);
    const html = `<div style="min-width:200px;font-family:Poppins,sans-serif;font-size:12px;">
      <div><strong>${p.no}</strong> · ${p.veh}${p.delay ? ' <span style="color:#d33;">· DELAY</span>' : ''}</div>
      <div style="color:#666;">${p.client || ''}</div>
      <div>${p.route || ''}</div>
      <div>${p.speed.toFixed(1)} km/h · ${p.when}</div>
      <div style="color:#666;">${p.addr || ''}</div>
      <a href="${p.url}">Open trip map</a>
    </div>`;
    m.bindPopup(html);
  });
})();
</script>
