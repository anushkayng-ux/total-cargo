<?php
require __DIR__ . '/inc/db.php';
require __DIR__ . '/inc/shell.php';
$c = db();

$id     = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$search = isset($_GET['q']) ? trim($_GET['q']) : '';

if ($search !== '' && $id === 0) {
    $stmt = $c->prepare("SELECT id FROM trips WHERE trip_no = ? OR lr_no = ? ORDER BY id DESC LIMIT 1");
    $stmt->bind_param('ss', $search, $search);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    if ($res) $id = (int) $res['id'];
    $stmt->close();
}

shell_search('Trip No. / LR No.', $search);

if ($id > 0) {
    $stmt = $c->prepare("SELECT t.*, b.consignor_name, b.consignee_name, b.pickup_city, b.drop_city FROM trips t LEFT JOIN bookings b ON b.id = t.booking_id WHERE t.id = ? LIMIT 1");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $tr = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $prevRow = $c->query("SELECT id FROM trips WHERE id < " . (int)$id . " ORDER BY id DESC LIMIT 1")->fetch_assoc();
    $nextRow = $c->query("SELECT id FROM trips WHERE id > " . (int)$id . " ORDER BY id ASC LIMIT 1")->fetch_assoc();
    $prevId  = $prevRow['id'] ?? null;
    $nextId  = $nextRow['id'] ?? null;
    $totalCount = $c->query("SELECT COUNT(*) c FROM trips")->fetch_assoc()['c'] ?? 0;

    shell_start('Transportation', 'Trip Sheet [Transport]');
    ?>
    <div class="tabs">
      <div class="tab active">Trip Details</div>
      <div class="tab">POD / Delivery</div>
      <div class="tab">Detention &amp; Insurance</div>
      <div class="spacer"></div>
      <div class="recordnav">
        <a href="trips.php">&#9776; List</a> &middot;
        Record <?= $tr ? $id : 0 ?> &middot; <?= (int)$totalCount ?> total
        <a class="<?= $prevId ? '' : 'disabled' ?>" href="?id=<?= (int)$prevId ?>">&#9664; Prev</a>
        <a class="<?= $nextId ? '' : 'disabled' ?>" href="?id=<?= (int)$nextId ?>">Next &#9654;</a>
      </div>
    </div>
    <?php if (!$tr): ?>
      <div class="notfound">Trip not found.</div>
    <?php else: ?>
    <div class="content">
      <div class="formwrap">
        <div class="row">
          <div class="field"><label>Trip No. :</label><div class="box"><?= v($tr['trip_no']) ?></div></div>
          <div class="field"><label>LR No. :</label><div class="box"><?= v($tr['lr_no']) ?></div></div>
          <div class="field" style="margin-left:auto;"><label>Current Status :</label><div class="box wide"><?= v($tr['current_status']) ?></div></div>
        </div>
        <div class="row">
          <div class="field"><label>Consignor :</label><div class="box wide"><?= v($tr['consignor_name']) ?></div></div>
          <div class="field"><label>Consignee :</label><div class="box wide"><?= v($tr['consignee_name']) ?></div></div>
        </div>
        <div class="row">
          <div class="field"><label>Loading Point :</label><div class="box wide"><?= v($tr['loading_point'] ?: $tr['pickup_city']) ?></div></div>
          <div class="field"><label>Unloading Point :</label><div class="box wide"><?= v($tr['unloading_point'] ?: $tr['drop_city']) ?></div></div>
        </div>
        <div class="row">
          <div class="field"><label>Vehicle No :</label><div class="box wide"><?= v($tr['vehicle_number']) ?></div></div>
          <div class="field"><label>Driver :</label><div class="box wide empty"><?= v($tr['driver_name']) ?></div></div>
        </div>
        <div class="row">
          <div class="field"><label>Driver Mobile :</label><div class="box empty"><?= v($tr['driver_mobile']) ?></div></div>
        </div>
        <div class="row">
          <div class="field"><label>Dispatch Date/Time :</label><div class="box wide empty"><?= v($tr['dispatch_datetime']) ?></div></div>
          <div class="field"><label>Delivery Date/Time :</label><div class="box wide empty"><?= v($tr['delivery_datetime']) ?></div></div>
        </div>
        <div class="row">
          <div class="field"><label>POD Status :</label><div class="box"><?= v($tr['pod_status']) ?></div></div>
          <div class="field"><label>POD Received At :</label><div class="box empty"><?= v($tr['pod_received_at']) ?></div></div>
        </div>
        <div class="row">
          <div class="field"><label>E-Way Bill No :</label><div class="box empty"><?= v($tr['ewb_no']) ?></div></div>
          <div class="field"><label>EWB Status :</label><div class="box"><?= v($tr['ewb_status']) ?></div></div>
        </div>
        <div class="row">
          <div class="field"><label>Cargo Value (INR) :</label><div class="box"><?= money($tr['cargo_value_inr']) ?></div></div>
          <div class="field"><label>Detention Hours :</label><div class="box narrow"><?= v($tr['detention_billable_hours'],'0') ?></div></div>
          <div class="field"><label>Detention Amount :</label><div class="box"><?= money($tr['detention_amount']) ?></div></div>
        </div>
      </div>
      <div class="sidepanel">
        <h4>Remarks / Delay Reason :</h4>
        <div class="remarksbox"><?= v($tr['delay_reason'] ?: $tr['remarks'], 'No remarks recorded.') ?></div>
        <div class="scenic"><div class="sun"></div><div class="box1"></div><div class="box2"></div><div class="box3"></div></div>
      </div>
    </div>
    <?php endif; ?>
    <?php
} else {
    $page = max(1, (int)($_GET['p'] ?? 1));
    $per  = 25;
    $off  = ($page - 1) * $per;
    $total = $c->query("SELECT COUNT(*) c FROM trips")->fetch_assoc()['c'];
    $rows = $c->query("SELECT id, trip_no, lr_no, vehicle_number, driver_name, current_status, pod_status, dispatch_datetime, delivery_datetime FROM trips ORDER BY id DESC LIMIT $per OFFSET $off");

    shell_start('Transportation', 'Trip Sheet [Transport] — List');
    ?>
    <div class="tabs">
      <div class="tab active">All Trips</div>
      <div class="spacer"></div>
      <div class="recordnav"><?= (int)$total ?> total records</div>
    </div>
    <div class="gridwrap">
      <table class="grid">
        <thead><tr><th>Trip No.</th><th>LR No.</th><th>Vehicle No</th><th>Driver</th><th>Current Status</th><th>POD Status</th><th>Dispatch</th><th>Delivery</th></tr></thead>
        <tbody>
        <?php while ($r = $rows->fetch_assoc()):
            $podClass = match($r['pod_status']) { 'Received' => 'green', 'Pending' => 'amber', default => 'gray' };
        ?>
          <tr onclick="location.href='?id=<?= (int)$r['id'] ?>'">
            <td><?= v($r['trip_no']) ?></td>
            <td><?= v($r['lr_no']) ?></td>
            <td><?= v($r['vehicle_number']) ?></td>
            <td><?= v($r['driver_name']) ?></td>
            <td><span class="pill blue"><?= v($r['current_status']) ?></span></td>
            <td><span class="pill <?= $podClass ?>"><?= v($r['pod_status']) ?></span></td>
            <td><?= v($r['dispatch_datetime']) ?></td>
            <td><?= v($r['delivery_datetime']) ?></td>
          </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
      <div class="pager">
        <?php $pages = max(1, (int)ceil($total / $per)); ?>
        <a class="<?= $page<=1?'disabled':'' ?>" href="?p=<?= $page-1 ?>">&laquo; Prev</a>
        <span class="cur">Page <?= $page ?> / <?= $pages ?></span>
        <a class="<?= $page>=$pages?'disabled':'' ?>" href="?p=<?= $page+1 ?>">Next &raquo;</a>
      </div>
    </div>
    <?php
}
shell_end();
