<?php
require __DIR__ . '/inc/db.php';
require __DIR__ . '/inc/shell.php';
$c = db();

$id     = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$search = isset($_GET['q']) ? trim($_GET['q']) : '';

if ($search !== '' && $id === 0) {
    $stmt = $c->prepare("SELECT id FROM drivers WHERE driver_name LIKE CONCAT('%',?,'%') OR mobile LIKE CONCAT('%',?,'%') ORDER BY id DESC LIMIT 1");
    $stmt->bind_param('ss', $search, $search);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    if ($res) $id = (int) $res['id'];
    $stmt->close();
}

shell_search('Driver Name / Mobile', $search);

if ($id > 0) {
    $stmt = $c->prepare("SELECT d.*, v.company_name AS vendor_name FROM drivers d LEFT JOIN vendors v ON v.id = d.vendor_id WHERE d.id = ? LIMIT 1");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $dr = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $prevRow = $c->query("SELECT id FROM drivers WHERE id < " . (int)$id . " ORDER BY id DESC LIMIT 1")->fetch_assoc();
    $nextRow = $c->query("SELECT id FROM drivers WHERE id > " . (int)$id . " ORDER BY id ASC LIMIT 1")->fetch_assoc();
    $prevId  = $prevRow['id'] ?? null;
    $nextId  = $nextRow['id'] ?? null;
    $totalCount = $c->query("SELECT COUNT(*) c FROM drivers")->fetch_assoc()['c'] ?? 0;

    shell_start('General Masters', 'Driver Master [General Masters]');
    ?>
    <div class="tabs">
      <div class="tab active">Driver Details</div>
      <div class="tab">License / KYC</div>
      <div class="spacer"></div>
      <div class="recordnav">
        <a href="drivers.php">&#9776; List</a> &middot;
        Record <?= $dr ? $id : 0 ?> &middot; <?= (int)$totalCount ?> total
        <a class="<?= $prevId ? '' : 'disabled' ?>" href="?id=<?= (int)$prevId ?>">&#9664; Prev</a>
        <a class="<?= $nextId ? '' : 'disabled' ?>" href="?id=<?= (int)$nextId ?>">Next &#9654;</a>
      </div>
    </div>
    <?php if (!$dr): ?>
      <div class="notfound">Driver not found.</div>
    <?php else: ?>
    <div class="content">
      <div class="formwrap">
        <div class="row">
          <div class="field"><label>Driver Name :</label><div class="box wide"><?= v($dr['driver_name']) ?></div></div>
          <div class="field" style="margin-left:auto;"><label>Status :</label><div class="box"><?= $dr['status'] ? 'Active' : 'Inactive' ?></div></div>
        </div>
        <div class="row">
          <div class="field"><label>Mobile :</label><div class="box empty"><?= v($dr['mobile']) ?></div></div>
          <div class="field"><label>Alt. Mobile :</label><div class="box empty"><?= v($dr['alt_mobile']) ?></div></div>
        </div>
        <div class="row">
          <div class="field"><label>Attached Vendor :</label><div class="box wide empty"><?= v($dr['vendor_name']) ?></div></div>
        </div>
        <div class="row">
          <div class="field"><label>License No. :</label><div class="box wide empty"><?= v($dr['license_no']) ?></div></div>
          <div class="field"><label>License Expiry :</label><div class="box empty"><?= v(d($dr['license_expiry'])) ?></div></div>
        </div>
        <div class="row">
          <div class="field"><label>Aadhaar (last 4) :</label><div class="box narrow empty"><?= v($dr['aadhaar_last_4']) ?></div></div>
          <div class="field"><label>DL Verified :</label><div class="box narrow"><?= $dr['dl_verified'] ? 'Yes' : 'No' ?></div></div>
          <div class="field"><label>KYC Status :</label><div class="box"><?= v($dr['kyc_status']) ?></div></div>
        </div>
      </div>
      <div class="sidepanel">
        <h4>KYC Notes :</h4>
        <div class="remarksbox"><?= v($dr['kyc_notes'], 'No notes recorded.') ?></div>
        <div class="scenic"><div class="sun"></div><div class="box1"></div><div class="box2"></div><div class="box3"></div></div>
      </div>
    </div>
    <?php endif; ?>
    <?php
} else {
    $total = $c->query("SELECT COUNT(*) c FROM drivers")->fetch_assoc()['c'];
    $rows = $c->query("SELECT id, driver_name, mobile, license_no, license_expiry, kyc_status, status FROM drivers ORDER BY id DESC");

    shell_start('General Masters', 'Driver Master [General Masters] — List');
    ?>
    <div class="tabs">
      <div class="tab active">All Drivers</div>
      <div class="spacer"></div>
      <div class="recordnav"><?= (int)$total ?> total records</div>
    </div>
    <div class="gridwrap">
      <table class="grid">
        <thead><tr><th>Driver Name</th><th>Mobile</th><th>License No.</th><th>License Expiry</th><th>KYC Status</th><th>Status</th></tr></thead>
        <tbody>
        <?php while ($r = $rows->fetch_assoc()):
            $kycClass = match($r['kyc_status']) { 'Verified' => 'green', 'Rejected' => 'gray', default => 'amber' };
        ?>
          <tr onclick="location.href='?id=<?= (int)$r['id'] ?>'">
            <td><?= v($r['driver_name']) ?></td>
            <td><?= v($r['mobile']) ?></td>
            <td><?= v($r['license_no']) ?></td>
            <td><?= v(d($r['license_expiry'])) ?></td>
            <td><span class="pill <?= $kycClass ?>"><?= v($r['kyc_status']) ?></span></td>
            <td><span class="pill <?= $r['status'] ? 'blue' : 'gray' ?>"><?= $r['status'] ? 'Active' : 'Inactive' ?></span></td>
          </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
    </div>
    <?php
}
shell_end();
