<?php
require __DIR__ . '/inc/db.php';
require __DIR__ . '/inc/shell.php';
$c = db();

$id     = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$search = isset($_GET['q']) ? trim($_GET['q']) : '';

if ($search !== '' && $id === 0) {
    $stmt = $c->prepare("SELECT id FROM vehicles WHERE vehicle_number LIKE CONCAT('%',?,'%') ORDER BY id DESC LIMIT 1");
    $stmt->bind_param('s', $search);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    if ($res) $id = (int) $res['id'];
    $stmt->close();
}

shell_search('Vehicle No.', $search);

if ($id > 0) {
    // ---------- DETAIL VIEW ----------
    $stmt = $c->prepare("SELECT * FROM vehicles WHERE id = ? LIMIT 1");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $veh = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $prevRow = $c->query("SELECT id FROM vehicles WHERE id < " . (int)$id . " ORDER BY id DESC LIMIT 1")->fetch_assoc();
    $nextRow = $c->query("SELECT id FROM vehicles WHERE id > " . (int)$id . " ORDER BY id ASC LIMIT 1")->fetch_assoc();
    $prevId  = $prevRow['id'] ?? null;
    $nextId  = $nextRow['id'] ?? null;
    $totalCount = $c->query("SELECT COUNT(*) c FROM vehicles")->fetch_assoc()['c'] ?? 0;

    $tripCount = 0;
    if ($veh) {
        $tc = $c->prepare("SELECT COUNT(*) n FROM bookings WHERE vehicle_number = ?");
        $tc->bind_param('s', $veh['vehicle_number']);
        $tc->execute();
        $tripCount = $tc->get_result()->fetch_assoc()['n'] ?? 0;
        $tc->close();
    }

    shell_start('General Masters', 'Vehicle Master [General Masters]');
    ?>
    <div class="tabs">
      <div class="tab active">Vehicle Details</div>
      <div class="tab">Compliance / Expiry</div>
      <div class="tab">GPS Tracking</div>
      <div class="spacer"></div>
      <div class="recordnav">
        <a href="vehicles.php">&#9776; List</a> &middot;
        Record <?= $veh ? $id : 0 ?> &middot; <?= (int)$totalCount ?> total
        <a class="<?= $prevId ? '' : 'disabled' ?>" href="?id=<?= (int)$prevId ?>">&#9664; Prev</a>
        <a class="<?= $nextId ? '' : 'disabled' ?>" href="?id=<?= (int)$nextId ?>">Next &#9654;</a>
      </div>
    </div>
    <?php if (!$veh): ?>
      <div class="notfound">Vehicle not found.</div>
    <?php else: ?>
    <div class="content">
      <div class="formwrap">
        <div class="row">
          <div class="field"><label>Vehicle No :</label><div class="box wide"><?= v($veh['vehicle_number']) ?></div></div>
          <div class="field" style="margin-left:auto;"><label>Status :</label><div class="box"><?= $veh['status'] ? 'Active' : 'Inactive' ?></div></div>
        </div>
        <div class="row">
          <div class="field"><label>Vehicle Type :</label><div class="box wide"><?= v($veh['vehicle_type']) ?></div></div>
          <div class="field"><label>Total Bookings Carried :</label><div class="box"><?= (int)$tripCount ?></div></div>
        </div>
        <div class="row">
          <div class="field"><label>RC No. :</label><div class="box wide empty"><?= v($veh['rc_no']) ?></div></div>
          <div class="field"><label>RC Expiry :</label><div class="box empty"><?= v(d($veh['rc_expiry']), '—') ?></div></div>
        </div>
        <div class="row">
          <div class="field"><label>Permit No. :</label><div class="box wide empty"><?= v($veh['permit_no']) ?></div></div>
          <div class="field"><label>Permit Expiry :</label><div class="box empty"><?= v(d($veh['permit_expiry']), '—') ?></div></div>
        </div>
        <div class="row">
          <div class="field"><label>Insurance No. :</label><div class="box wide empty"><?= v($veh['insurance_no']) ?></div></div>
          <div class="field"><label>Insurance Expiry :</label><div class="box empty"><?= v(d($veh['insurance_expiry']), '—') ?></div></div>
        </div>
        <div class="row">
          <div class="field"><label>PUC No. :</label><div class="box wide empty"><?= v($veh['puc_no']) ?></div></div>
          <div class="field"><label>PUC Expiry :</label><div class="box empty"><?= v(d($veh['puc_expiry']), '—') ?></div></div>
        </div>
        <div class="row">
          <div class="field"><label>Fitness Expiry :</label><div class="box empty"><?= v(d($veh['fitness_expiry']), '—') ?></div></div>
          <div class="field"><label>Tax Expiry :</label><div class="box empty"><?= v(d($veh['tax_expiry']), '—') ?></div></div>
        </div>
        <div class="row">
          <div class="field"><label>GPS Provider :</label><div class="box wide"><?= v(str_replace('_',' ', $veh['gps_provider'])) ?></div></div>
          <div class="field"><label>GPS Device IMEI :</label><div class="box empty"><?= v($veh['gps_device_imei']) ?></div></div>
        </div>
      </div>
      <div class="sidepanel">
        <h4>GPS Notes :</h4>
        <div class="remarksbox"><?= v($veh['gps_notes'], 'No notes recorded.') ?></div>
        <div class="scenic"><div class="sun"></div><div class="box1"></div><div class="box2"></div><div class="box3"></div></div>
      </div>
    </div>
    <?php endif; ?>
    <?php
} else {
    // ---------- LIST VIEW ----------
    $total = $c->query("SELECT COUNT(*) c FROM vehicles")->fetch_assoc()['c'];
    $rows = $c->query("SELECT id, vehicle_number, vehicle_type, rc_expiry, insurance_expiry, fitness_expiry, gps_provider, status FROM vehicles ORDER BY id DESC");

    shell_start('General Masters', 'Vehicle Master [General Masters] — List');
    ?>
    <div class="tabs">
      <div class="tab active">All Vehicles</div>
      <div class="spacer"></div>
      <div class="recordnav"><?= (int)$total ?> total records</div>
    </div>
    <div class="gridwrap">
      <table class="grid">
        <thead><tr>
          <th>Vehicle No</th><th>Type</th><th>RC Expiry</th><th>Insurance Expiry</th><th>Fitness Expiry</th><th>GPS</th><th>Status</th>
        </tr></thead>
        <tbody>
        <?php while ($r = $rows->fetch_assoc()): ?>
          <tr onclick="location.href='?id=<?= (int)$r['id'] ?>'">
            <td><?= v($r['vehicle_number']) ?></td>
            <td><?= v($r['vehicle_type']) ?></td>
            <td><?= v(d($r['rc_expiry']), '—') ?></td>
            <td><?= v(d($r['insurance_expiry']), '—') ?></td>
            <td><?= v(d($r['fitness_expiry']), '—') ?></td>
            <td><?= v(str_replace('_',' ', $r['gps_provider'])) ?></td>
            <td><span class="pill <?= $r['status'] ? 'blue' : 'gray' ?>"><?= $r['status'] ? 'Active' : 'Inactive' ?></span></td>
          </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
    </div>
    <?php
}
shell_end();
