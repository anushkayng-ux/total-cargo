<?php
require __DIR__ . '/inc/db.php';
require __DIR__ . '/inc/shell.php';
$c = db();

$id     = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$search = isset($_GET['q']) ? trim($_GET['q']) : '';

if ($search !== '' && $id === 0) {
    $stmt = $c->prepare("SELECT id FROM clients WHERE client_code = ? OR company_name LIKE CONCAT('%',?,'%') ORDER BY id DESC LIMIT 1");
    $stmt->bind_param('ss', $search, $search);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    if ($res) $id = (int) $res['id'];
    $stmt->close();
}

shell_search('Client Code / Name', $search);

if ($id > 0) {
    // ---------- DETAIL VIEW ----------
    $stmt = $c->prepare("SELECT * FROM clients WHERE id = ? LIMIT 1");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $cl = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $prevRow = $c->query("SELECT id FROM clients WHERE id < " . (int)$id . " ORDER BY id DESC LIMIT 1")->fetch_assoc();
    $nextRow = $c->query("SELECT id FROM clients WHERE id > " . (int)$id . " ORDER BY id ASC LIMIT 1")->fetch_assoc();
    $prevId  = $prevRow['id'] ?? null;
    $nextId  = $nextRow['id'] ?? null;
    $totalCount = $c->query("SELECT COUNT(*) c FROM clients")->fetch_assoc()['c'] ?? 0;

    $bookingCount = 0;
    if ($cl) {
        $bc = $c->prepare("SELECT COUNT(*) n FROM bookings WHERE consignor_client_id = ? OR consignee_client_id = ?");
        $bc->bind_param('ii', $id, $id);
        $bc->execute();
        $bookingCount = $bc->get_result()->fetch_assoc()['n'] ?? 0;
        $bc->close();
    }

    shell_start('General Masters', 'Client Master [General Masters]');
    ?>
    <div class="tabs">
      <div class="tab active">Client Details</div>
      <div class="tab">Credit &amp; Billing</div>
      <div class="tab">KYC / Documents</div>
      <div class="spacer"></div>
      <div class="recordnav">
        <a href="clients.php">&#9776; List</a> &middot;
        Record <?= $cl ? $id : 0 ?> &middot; <?= (int)$totalCount ?> total
        <a class="<?= $prevId ? '' : 'disabled' ?>" href="?id=<?= (int)$prevId ?>">&#9664; Prev</a>
        <a class="<?= $nextId ? '' : 'disabled' ?>" href="?id=<?= (int)$nextId ?>">Next &#9654;</a>
      </div>
    </div>
    <?php if (!$cl): ?>
      <div class="notfound">Client not found.</div>
    <?php else: ?>
    <div class="content">
      <div class="formwrap">
        <div class="row">
          <div class="field"><label>Client Code :</label><div class="box"><?= v($cl['client_code']) ?></div></div>
          <div class="field" style="margin-left:auto;"><label>Status :</label><div class="box wide"><?= $cl['status'] ? 'Active' : 'Inactive' ?></div></div>
        </div>
        <div class="row">
          <div class="field" style="width:100%;"><label>Company Name :</label><div class="box xwide" style="min-width:400px;"><?= v($cl['company_name']) ?></div><div class="addbtn">+</div></div>
        </div>
        <div class="row">
          <div class="field"><label>Contact Person :</label><div class="box wide"><?= v($cl['contact_name']) ?></div></div>
          <div class="field"><label>KYC Status :</label><div class="box"><?= v($cl['kyc_status']) ?></div></div>
        </div>
        <div class="row">
          <div class="field"><label>Mobile :</label><div class="box"><?= v($cl['mobile']) ?></div></div>
          <div class="field"><label>Alt. Mobile :</label><div class="box empty"><?= v($cl['alt_mobile']) ?></div></div>
          <div class="field"><label>Email :</label><div class="box wide empty"><?= v($cl['email']) ?></div></div>
        </div>
        <div class="row" style="align-items:flex-start;">
          <div class="field" style="width:100%;"><label style="white-space:nowrap;">Address :</label>
            <div class="particulars"><?= v($cl['address']) ?></div>
          </div>
        </div>
        <div class="row">
          <div class="field"><label>City :</label><div class="box"><?= v($cl['city']) ?></div></div>
          <div class="field"><label>State :</label><div class="box wide"><?= v($cl['state']) ?></div></div>
          <div class="field"><label>Pincode :</label><div class="box narrow"><?= v($cl['pincode']) ?></div></div>
        </div>
        <div class="row">
          <div class="field"><label>GST No. :</label><div class="box wide"><?= v($cl['gst_no']) ?></div></div>
          <div class="field"><label>PAN No. :</label><div class="box"><?= v($cl['pan_no']) ?></div></div>
        </div>
        <div class="row">
          <div class="field"><label>GST Treatment :</label><div class="box"><?= v(strtoupper($cl['gst_treatment'])) ?></div></div>
          <div class="field"><label>TDS Rate (%) :</label><div class="box narrow"><?= v($cl['tds_rate']) ?></div></div>
        </div>
        <div class="row">
          <div class="field"><label>Credit Limit :</label><div class="box"><?= money($cl['credit_limit']) ?></div></div>
          <div class="field"><label>Credit Days :</label><div class="box narrow"><?= v($cl['credit_days'],'0') ?></div></div>
          <div class="field"><label>MSME :</label><div class="box narrow"><?= $cl['is_msme'] ? 'Yes' : 'No' ?></div></div>
        </div>
        <div class="row">
          <div class="field"><label>Detention Free (Loading) hrs :</label><div class="box narrow"><?= v($cl['detention_free_hours_loading']) ?></div></div>
          <div class="field"><label>Detention Free (Unloading) hrs :</label><div class="box narrow"><?= v($cl['detention_free_hours_unloading']) ?></div></div>
          <div class="field"><label>Rate/hr :</label><div class="box"><?= money($cl['detention_rate_per_hour']) ?></div></div>
        </div>
        <div class="row">
          <div class="field"><label>Portal Access :</label><div class="box narrow"><?= $cl['portal_enabled'] ? 'Enabled' : 'Disabled' ?></div></div>
          <div class="field"><label>Total Bookings :</label><div class="box"><?= (int)$bookingCount ?></div></div>
        </div>
      </div>
      <div class="sidepanel">
        <h4>Portal Notes :</h4>
        <div class="remarksbox"><?= v($cl['portal_notes'], 'No notes recorded.') ?></div>
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
    $total = $c->query("SELECT COUNT(*) c FROM clients")->fetch_assoc()['c'];
    $rows = $c->query("SELECT id, client_code, company_name, contact_name, mobile, city, state, gst_no, credit_limit, status, kyc_status FROM clients ORDER BY id DESC LIMIT $per OFFSET $off");

    shell_start('General Masters', 'Client Master [General Masters] — List');
    ?>
    <div class="tabs">
      <div class="tab active">All Clients</div>
      <div class="spacer"></div>
      <div class="recordnav"><?= (int)$total ?> total records</div>
    </div>
    <div class="gridwrap">
      <table class="grid">
        <thead><tr>
          <th>Client Code</th><th>Company Name</th><th>Contact</th><th>Mobile</th><th>City</th><th>State</th><th>GST No.</th><th>Credit Limit</th><th>KYC</th><th>Status</th>
        </tr></thead>
        <tbody>
        <?php while ($r = $rows->fetch_assoc()):
            $kycClass = match($r['kyc_status']) { 'Verified' => 'green', 'Rejected' => 'gray', default => 'amber' };
        ?>
          <tr onclick="location.href='?id=<?= (int)$r['id'] ?>'">
            <td><?= v($r['client_code']) ?></td>
            <td><?= v($r['company_name']) ?></td>
            <td><?= v($r['contact_name']) ?></td>
            <td><?= v($r['mobile']) ?></td>
            <td><?= v($r['city']) ?></td>
            <td><?= v($r['state']) ?></td>
            <td><?= v($r['gst_no']) ?></td>
            <td><?= money($r['credit_limit']) ?></td>
            <td><span class="pill <?= $kycClass ?>"><?= v($r['kyc_status']) ?></span></td>
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
