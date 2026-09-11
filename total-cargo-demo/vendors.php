<?php
require __DIR__ . '/inc/db.php';
require __DIR__ . '/inc/shell.php';
$c = db();

$id     = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$search = isset($_GET['q']) ? trim($_GET['q']) : '';

if ($search !== '' && $id === 0) {
    $stmt = $c->prepare("SELECT id FROM vendors WHERE vendor_code = ? OR company_name LIKE CONCAT('%',?,'%') ORDER BY id DESC LIMIT 1");
    $stmt->bind_param('ss', $search, $search);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    if ($res) $id = (int) $res['id'];
    $stmt->close();
}

shell_search('Vendor Code / Name', $search);

if ($id > 0) {
    $stmt = $c->prepare("SELECT * FROM vendors WHERE id = ? LIMIT 1");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $vd = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $prevRow = $c->query("SELECT id FROM vendors WHERE id < " . (int)$id . " ORDER BY id DESC LIMIT 1")->fetch_assoc();
    $nextRow = $c->query("SELECT id FROM vendors WHERE id > " . (int)$id . " ORDER BY id ASC LIMIT 1")->fetch_assoc();
    $prevId  = $prevRow['id'] ?? null;
    $nextId  = $nextRow['id'] ?? null;
    $totalCount = $c->query("SELECT COUNT(*) c FROM vendors")->fetch_assoc()['c'] ?? 0;

    $driverCount = 0;
    if ($vd) {
        $dc = $c->prepare("SELECT COUNT(*) n FROM drivers WHERE vendor_id = ?");
        $dc->bind_param('i', $id);
        $dc->execute();
        $driverCount = $dc->get_result()->fetch_assoc()['n'] ?? 0;
        $dc->close();
    }

    shell_start('General Masters', 'Vendor Master [General Masters]');
    ?>
    <div class="tabs">
      <div class="tab active">Vendor Details</div>
      <div class="tab">Bank Details</div>
      <div class="spacer"></div>
      <div class="recordnav">
        <a href="vendors.php">&#9776; List</a> &middot;
        Record <?= $vd ? $id : 0 ?> &middot; <?= (int)$totalCount ?> total
        <a class="<?= $prevId ? '' : 'disabled' ?>" href="?id=<?= (int)$prevId ?>">&#9664; Prev</a>
        <a class="<?= $nextId ? '' : 'disabled' ?>" href="?id=<?= (int)$nextId ?>">Next &#9654;</a>
      </div>
    </div>
    <?php if (!$vd): ?>
      <div class="notfound">Vendor not found.</div>
    <?php else: ?>
    <div class="content">
      <div class="formwrap">
        <div class="row">
          <div class="field"><label>Vendor Code :</label><div class="box"><?= v($vd['vendor_code']) ?></div></div>
          <div class="field" style="margin-left:auto;"><label>Status :</label><div class="box"><?= $vd['status'] ? 'Active' : 'Inactive' ?></div></div>
        </div>
        <div class="row">
          <div class="field" style="width:100%;"><label>Company Name :</label><div class="box xwide" style="min-width:400px;"><?= v($vd['company_name']) ?></div></div>
        </div>
        <div class="row">
          <div class="field"><label>Owner Name :</label><div class="box wide empty"><?= v($vd['owner_name']) ?></div></div>
          <div class="field"><label>Vendor Type :</label><div class="box"><?= v($vd['vendor_type']) ?></div></div>
        </div>
        <div class="row">
          <div class="field"><label>Mobile :</label><div class="box empty"><?= v($vd['mobile']) ?></div></div>
          <div class="field"><label>WhatsApp No. :</label><div class="box empty"><?= v($vd['whatsapp_no']) ?></div></div>
          <div class="field"><label>Email :</label><div class="box wide empty"><?= v($vd['email']) ?></div></div>
        </div>
        <div class="row" style="align-items:flex-start;">
          <div class="field" style="width:100%;"><label style="white-space:nowrap;">Address :</label>
            <div class="particulars"><?= v($vd['address']) ?></div>
          </div>
        </div>
        <div class="row">
          <div class="field"><label>City :</label><div class="box"><?= v($vd['city']) ?></div></div>
          <div class="field"><label>State :</label><div class="box wide"><?= v($vd['state']) ?></div></div>
        </div>
        <div class="row">
          <div class="field"><label>GST No. :</label><div class="box wide empty"><?= v($vd['gst_no']) ?></div></div>
          <div class="field"><label>PAN No. :</label><div class="box empty"><?= v($vd['pan_no']) ?></div></div>
        </div>
        <div class="row">
          <div class="field"><label>Bank Name :</label><div class="box wide empty"><?= v($vd['bank_name']) ?></div></div>
          <div class="field"><label>Account No. :</label><div class="box empty"><?= v($vd['account_no']) ?></div></div>
          <div class="field"><label>IFSC :</label><div class="box empty"><?= v($vd['ifsc_code']) ?></div></div>
        </div>
        <div class="row">
          <div class="field"><label>Rating :</label><div class="box narrow"><?= v($vd['rating'],'0.00') ?></div></div>
          <div class="field"><label>Preferred :</label><div class="box narrow"><?= $vd['is_preferred'] ? 'Yes' : 'No' ?></div></div>
          <div class="field"><label>Blacklisted :</label><div class="box narrow"><?= $vd['is_blacklisted'] ? 'Yes' : 'No' ?></div></div>
          <div class="field"><label>Drivers Attached :</label><div class="box narrow"><?= (int)$driverCount ?></div></div>
        </div>
      </div>
      <div class="sidepanel">
        <h4>Remarks :</h4>
        <div class="remarksbox">No remarks recorded.</div>
        <div class="scenic"><div class="sun"></div><div class="box1"></div><div class="box2"></div><div class="box3"></div></div>
      </div>
    </div>
    <?php endif; ?>
    <?php
} else {
    $page = max(1, (int)($_GET['p'] ?? 1));
    $per  = 25;
    $off  = ($page - 1) * $per;
    $total = $c->query("SELECT COUNT(*) c FROM vendors")->fetch_assoc()['c'];
    $rows = $c->query("SELECT id, vendor_code, company_name, owner_name, mobile, city, vendor_type, rating, is_preferred, status FROM vendors ORDER BY id DESC LIMIT $per OFFSET $off");

    shell_start('General Masters', 'Vendor Master [General Masters] — List');
    ?>
    <div class="tabs">
      <div class="tab active">All Vendors</div>
      <div class="spacer"></div>
      <div class="recordnav"><?= (int)$total ?> total records</div>
    </div>
    <div class="gridwrap">
      <table class="grid">
        <thead><tr><th>Vendor Code</th><th>Company Name</th><th>Owner</th><th>Mobile</th><th>City</th><th>Type</th><th>Rating</th><th>Status</th></tr></thead>
        <tbody>
        <?php while ($r = $rows->fetch_assoc()): ?>
          <tr onclick="location.href='?id=<?= (int)$r['id'] ?>'">
            <td><?= v($r['vendor_code']) ?></td>
            <td><?= v($r['company_name']) ?></td>
            <td><?= v($r['owner_name']) ?></td>
            <td><?= v($r['mobile']) ?></td>
            <td><?= v($r['city']) ?></td>
            <td><?= v($r['vendor_type']) ?></td>
            <td><?= v($r['rating'],'0.00') ?></td>
            <td><span class="pill <?= $r['status'] ? 'blue' : 'gray' ?>"><?= $r['status'] ? 'Active' : 'Inactive' ?></span></td>
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
