<?php
require __DIR__ . '/inc/db.php';
require __DIR__ . '/inc/shell.php';
$c = db();

$id     = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$search = isset($_GET['q']) ? trim($_GET['q']) : '';

if ($search !== '' && $id === 0) {
    $stmt = $c->prepare("SELECT id FROM bookings WHERE lr_no = ? OR booking_no = ? ORDER BY id DESC LIMIT 1");
    $stmt->bind_param('ss', $search, $search);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    if ($res) $id = (int) $res['id'];
    $stmt->close();
}

shell_search('GR/LR No.', $search);

if ($id > 0) {
    // ---------- DETAIL VIEW ----------
    $stmt = $c->prepare("SELECT * FROM bookings WHERE id = ? LIMIT 1");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $booking = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $prevRow = $c->query("SELECT id FROM bookings WHERE id < " . (int)$id . " ORDER BY id DESC LIMIT 1")->fetch_assoc();
    $nextRow = $c->query("SELECT id FROM bookings WHERE id > " . (int)$id . " ORDER BY id ASC LIMIT 1")->fetch_assoc();
    $prevId  = $prevRow['id'] ?? null;
    $nextId  = $nextRow['id'] ?? null;
    $totalCount = $c->query("SELECT COUNT(*) c FROM bookings")->fetch_assoc()['c'] ?? 0;

    shell_start('Transportation', 'GR/LR Booking [Transport]');
    ?>
    <div class="tabs">
      <div class="tab active">GR/LR Details</div>
      <div class="tab">Shipment Status Update</div>
      <div class="tab">Charges Details / Document Tagging</div>
      <div class="spacer"></div>
      <div class="recordnav">
        <a href="bookings.php">&#9776; List</a> &middot;
        Record <?= $booking ? $id : 0 ?> &middot; <?= (int)$totalCount ?> total
        <a class="<?= $prevId ? '' : 'disabled' ?>" href="?id=<?= (int)$prevId ?>">&#9664; Prev</a>
        <a class="<?= $nextId ? '' : 'disabled' ?>" href="?id=<?= (int)$nextId ?>">Next &#9654;</a>
      </div>
    </div>
    <?php if (!$booking): ?>
      <div class="notfound">Booking not found.</div>
    <?php else: ?>
    <div class="content">
      <div class="formwrap">
        <div class="row">
          <div class="field"><label>Issuing Office :</label><div class="box wide">DELHI</div><div class="addbtn">+</div></div>
          <div class="field" style="margin-left:auto;"><label>GR/LR Booking Status :</label><div class="box wide"><?= v($booking['booking_status']) ?></div></div>
        </div>
        <div class="row">
          <div class="field"><label>GR/LR No. :</label><div class="box"><?= v($booking['lr_no'] ?: $booking['booking_no']) ?></div></div>
          <div class="field"><label>Date :</label><div class="box"><?= v(d($booking['loading_date'])) ?></div></div>
        </div>
        <div class="row">
          <div class="field"><label>Dest. From :</label><div class="box wide"><?= v($booking['pickup_city']) ?></div><div class="addbtn">+</div></div>
          <div class="field"><label>Dest. To :</label><div class="box wide"><?= v($booking['drop_city']) ?></div></div>
        </div>
        <div class="row">
          <div class="field"><label>Consignor :</label><div class="box xwide"><?= v($booking['consignor_name']) ?></div><div class="addbtn">+</div></div>
          <div class="field"><label>Consignee :</label><div class="box xwide"><?= v($booking['consignee_name']) ?></div><div class="addbtn">+</div></div>
        </div>
        <div class="row">
          <div class="field"><label>Vehicle No :</label><div class="box wide"><?= v($booking['vehicle_number']) ?></div><div class="addbtn">+</div></div>
          <div class="field"><label>CHA Job No :</label><div class="box empty">—</div></div>
        </div>
        <div class="row">
          <div class="field"><label>No of Packages :</label><div class="box"><?= v($booking['packages_count']) ?></div></div>
          <div class="field"><label>Method of Packing :</label><div class="box wide"><?= v($booking['packing_method']) ?></div><div class="addbtn">+</div></div>
        </div>
        <div class="row" style="align-items:flex-start;">
          <div class="field" style="width:100%;"><label style="white-space:nowrap;">Particulars :</label>
            <div class="particulars"><?= v($booking['particulars_text']) ?></div>
          </div>
        </div>
        <div class="row">
          <div class="field"><label>Gross Weight :</label><div class="box"><?= v($booking['actual_weight_kg']) ?></div></div>
          <div class="field"><label>Chargeable Weight :</label><div class="box"><?= v($booking['charge_weight_kg']) ?></div></div>
          <div class="field"><label>Vehicle Type :</label><div class="box wide"><?= v($booking['vehicle_type']) ?></div></div>
        </div>
        <div class="row">
          <div class="field"><label>Invoice No. :</label><div class="box"><?= v($booking['invoice_number']) ?></div></div>
          <div class="field"><label>Invoice Value :</label><div class="box"><?= money($booking['cargo_value_inr']) ?></div></div>
        </div>
        <div class="row">
          <div class="field"><label>B.E./S.B. No. :</label><div class="box empty"><?= v($booking['bill_of_entry']) ?></div></div>
          <div class="field"><label>Eway Bill No :</label><div class="box empty"><?= v($booking['ewb_no']) ?></div></div>
        </div>
        <div class="row">
          <div class="field"><label>B/L No. :</label><div class="box empty"><?= v($booking['bl_number']) ?></div></div>
        </div>
        <div class="row">
          <div class="field"><label>Container No. :</label><div class="box empty"><?= v($booking['container_number']) ?></div></div>
          <div class="field"><label>Seal No :</label><div class="box empty"><?= v($booking['seal_number']) ?></div></div>
        </div>
        <div class="row">
          <div class="field"><label>Length :</label><div class="box narrow"><?= v($booking['dim_length_cm'],'0') ?></div></div>
          <div class="field"><label>Width :</label><div class="box narrow"><?= v($booking['dim_width_cm'],'0') ?></div></div>
          <div class="field"><label>Height :</label><div class="box narrow"><?= v($booking['dim_height_cm'],'0') ?></div></div>
        </div>
        <div class="row">
          <div class="field"><label>Billing Client :</label><div class="box xwide"><?= v($booking['billing_party']) ?></div><div class="addbtn">+</div></div>
          <div class="field"><label>Unbilled Amount :</label><div class="box">0</div></div>
        </div>
        <div class="row">
          <div class="field"><label>Shipment Type :</label><div class="box">ROAD</div></div>
          <div class="field"><label>Payment Type :</label><div class="box wide"><?= v($booking['freight_mode']) ?></div></div>
        </div>
        <div class="row">
          <div class="field"><label>Owner Mobile No :</label><div class="box empty"><?= v($booking['driver_mobile']) ?></div></div>
        </div>
      </div>
      <div class="sidepanel">
        <h4>Remarks :</h4>
        <div class="remarksbox"><?= v($booking['instructions'], 'No remarks recorded.') ?></div>
        <div class="scenic"><div class="sun"></div><div class="box1"></div><div class="box2"></div><div class="box3"></div></div>
      </div>
    </div>
    <?php endif; ?>
    <?php
} else {
    // ---------- LIST VIEW ----------
    $page = max(1, (int)($_GET['p'] ?? 1));
    $per  = 25;
    $off  = ($page - 1) * $per;
    $total = $c->query("SELECT COUNT(*) c FROM bookings")->fetch_assoc()['c'];
    $rows = $c->query("SELECT id, booking_no, lr_no, loading_date, pickup_city, drop_city, consignor_name, consignee_name, vehicle_number, booking_status, freight_mode FROM bookings ORDER BY id DESC LIMIT $per OFFSET $off");

    shell_start('Transportation', 'GR/LR Booking [Transport] — List');
    ?>
    <div class="tabs">
      <div class="tab active">All Bookings</div>
      <div class="spacer"></div>
      <div class="recordnav"><?= (int)$total ?> total records</div>
    </div>
    <div class="gridwrap">
      <table class="grid">
        <thead><tr>
          <th>GR/LR No.</th><th>Date</th><th>From</th><th>To</th><th>Consignor</th><th>Consignee</th><th>Vehicle No</th><th>Status</th><th>Payment</th>
        </tr></thead>
        <tbody>
        <?php while ($b = $rows->fetch_assoc()):
            $statusClass = match(true) {
                stripos($b['booking_status'],'Handed') !== false => 'green',
                stripos($b['booking_status'],'Pending') !== false => 'amber',
                default => 'gray',
            };
        ?>
          <tr onclick="location.href='?id=<?= (int)$b['id'] ?>'">
            <td><?= v($b['lr_no'] ?: $b['booking_no']) ?></td>
            <td><?= v(d($b['loading_date'])) ?></td>
            <td><?= v($b['pickup_city']) ?></td>
            <td><?= v($b['drop_city']) ?></td>
            <td><?= v($b['consignor_name']) ?></td>
            <td><?= v($b['consignee_name']) ?></td>
            <td><?= v($b['vehicle_number']) ?></td>
            <td><span class="pill <?= $statusClass ?>"><?= v($b['booking_status']) ?></span></td>
            <td><?= v($b['freight_mode']) ?></td>
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
