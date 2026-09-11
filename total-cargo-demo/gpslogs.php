<?php
require __DIR__ . '/inc/db.php';
require __DIR__ . '/inc/shell.php';
$c = db();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id > 0) {
    $stmt = $c->prepare("SELECT g.*, t.trip_no FROM gps_logs g LEFT JOIN trips t ON t.id = g.trip_id WHERE g.id = ? LIMIT 1");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $g = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $prevRow = $c->query("SELECT id FROM gps_logs WHERE id < " . (int)$id . " ORDER BY id DESC LIMIT 1")->fetch_assoc();
    $nextRow = $c->query("SELECT id FROM gps_logs WHERE id > " . (int)$id . " ORDER BY id ASC LIMIT 1")->fetch_assoc();
    $prevId  = $prevRow['id'] ?? null;
    $nextId  = $nextRow['id'] ?? null;
    $totalCount = $c->query("SELECT COUNT(*) c FROM gps_logs")->fetch_assoc()['c'] ?? 0;

    shell_start('Transportation', 'GPS Tracking [Transport]');
    ?>
    <div class="tabs">
      <div class="tab active">Ping Details</div>
      <div class="spacer"></div>
      <div class="recordnav">
        <a href="gpslogs.php">&#9776; List</a> &middot;
        Record <?= $g ? $id : 0 ?> &middot; <?= (int)$totalCount ?> total
        <a class="<?= $prevId ? '' : 'disabled' ?>" href="?id=<?= (int)$prevId ?>">&#9664; Prev</a>
        <a class="<?= $nextId ? '' : 'disabled' ?>" href="?id=<?= (int)$nextId ?>">Next &#9654;</a>
      </div>
    </div>
    <?php if (!$g): ?>
      <div class="notfound">GPS ping not found.</div>
    <?php else: ?>
    <div class="content">
      <div class="formwrap">
        <div class="row">
          <div class="field"><label>Trip No. :</label><div class="box"><?= v($g['trip_no']) ?></div></div>
          <div class="field"><label>Vehicle No :</label><div class="box wide"><?= v($g['vehicle_number']) ?></div></div>
          <div class="field" style="margin-left:auto;"><label>Source :</label><div class="box"><?= v($g['source']) ?></div></div>
        </div>
        <div class="row">
          <div class="field"><label>Latitude :</label><div class="box"><?= v($g['latitude']) ?></div></div>
          <div class="field"><label>Longitude :</label><div class="box"><?= v($g['longitude']) ?></div></div>
          <div class="field"><label>Speed (km/h) :</label><div class="box narrow"><?= v($g['speed'],'0') ?></div></div>
        </div>
        <div class="row">
          <div class="field" style="width:100%;"><label>Address :</label><div class="box xwide" style="min-width:340px;"><?= v($g['address']) ?></div></div>
        </div>
        <div class="row">
          <div class="field"><label>Timestamp :</label><div class="box wide"><?= v($g['gps_timestamp']) ?></div></div>
        </div>
      </div>
      <div class="sidepanel">
        <h4>Map Reference :</h4>
        <div class="remarksbox">
          <a href="https://www.google.com/maps?q=<?= urlencode($g['latitude'].','.$g['longitude']) ?>" target="_blank" rel="noopener" style="color:#2f6fe0;text-decoration:none;font-weight:600;">Open in Google Maps &#8599;</a>
        </div>
        <div class="scenic"><div class="sun"></div><div class="box1"></div><div class="box2"></div><div class="box3"></div></div>
      </div>
    </div>
    <?php endif; ?>
    <?php
} else {
    $total = $c->query("SELECT COUNT(*) c FROM gps_logs")->fetch_assoc()['c'];
    $rows = $c->query("SELECT g.id, t.trip_no, g.vehicle_number, g.latitude, g.longitude, g.speed, g.address, g.gps_timestamp
                        FROM gps_logs g LEFT JOIN trips t ON t.id = g.trip_id ORDER BY g.gps_timestamp ASC");

    shell_start('Transportation', 'GPS Tracking [Transport] — List');
    ?>
    <div class="tabs">
      <div class="tab active">Route Pings</div>
      <div class="spacer"></div>
      <div class="recordnav"><?= (int)$total ?> total records</div>
    </div>
    <div class="gridwrap">
      <table class="grid">
        <thead><tr><th>Trip No.</th><th>Vehicle No</th><th>Timestamp</th><th>Address</th><th>Speed</th><th>Lat, Lng</th></tr></thead>
        <tbody>
        <?php while ($r = $rows->fetch_assoc()): ?>
          <tr onclick="location.href='?id=<?= (int)$r['id'] ?>'">
            <td><?= v($r['trip_no']) ?></td>
            <td><?= v($r['vehicle_number']) ?></td>
            <td><?= v($r['gps_timestamp']) ?></td>
            <td><?= v($r['address']) ?></td>
            <td><?= v($r['speed'],'0') ?> km/h</td>
            <td><?= v($r['latitude']) ?>, <?= v($r['longitude']) ?></td>
          </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
    </div>
    <?php
}
shell_end();
