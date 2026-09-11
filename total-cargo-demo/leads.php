<?php
require __DIR__ . '/inc/db.php';
require __DIR__ . '/inc/shell.php';
$c = db();

$id     = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$search = isset($_GET['q']) ? trim($_GET['q']) : '';

if ($search !== '' && $id === 0) {
    $stmt = $c->prepare("SELECT id FROM leads WHERE lead_no = ? OR mobile LIKE CONCAT('%',?,'%') ORDER BY id DESC LIMIT 1");
    $stmt->bind_param('ss', $search, $search);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    if ($res) $id = (int) $res['id'];
    $stmt->close();
}

shell_search('Lead No. / Mobile', $search);

if ($id > 0) {
    $stmt = $c->prepare("SELECT * FROM leads WHERE id = ? LIMIT 1");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $ld = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $prevRow = $c->query("SELECT id FROM leads WHERE id < " . (int)$id . " ORDER BY id DESC LIMIT 1")->fetch_assoc();
    $nextRow = $c->query("SELECT id FROM leads WHERE id > " . (int)$id . " ORDER BY id ASC LIMIT 1")->fetch_assoc();
    $prevId  = $prevRow['id'] ?? null;
    $nextId  = $nextRow['id'] ?? null;
    $totalCount = $c->query("SELECT COUNT(*) c FROM leads")->fetch_assoc()['c'] ?? 0;

    shell_start('Transportation', 'Lead / Enquiry [Transport]');
    ?>
    <div class="tabs">
      <div class="tab active">Lead Details</div>
      <div class="tab">Follow-ups</div>
      <div class="spacer"></div>
      <div class="recordnav">
        <a href="leads.php">&#9776; List</a> &middot;
        Record <?= $ld ? $id : 0 ?> &middot; <?= (int)$totalCount ?> total
        <a class="<?= $prevId ? '' : 'disabled' ?>" href="?id=<?= (int)$prevId ?>">&#9664; Prev</a>
        <a class="<?= $nextId ? '' : 'disabled' ?>" href="?id=<?= (int)$nextId ?>">Next &#9654;</a>
      </div>
    </div>
    <?php if (!$ld): ?>
      <div class="notfound">Lead not found.</div>
    <?php else: ?>
    <div class="content">
      <div class="formwrap">
        <div class="row">
          <div class="field"><label>Lead No. :</label><div class="box"><?= v($ld['lead_no']) ?></div></div>
          <div class="field"><label>Date/Time :</label><div class="box wide"><?= v($ld['lead_datetime']) ?></div></div>
          <div class="field" style="margin-left:auto;"><label>Priority :</label><div class="box"><?= v($ld['priority']) ?></div></div>
        </div>
        <div class="row">
          <div class="field"><label>Client Name :</label><div class="box wide empty"><?= v($ld['client_name']) ?></div></div>
          <div class="field"><label>Company :</label><div class="box wide empty"><?= v($ld['company_name']) ?></div></div>
        </div>
        <div class="row">
          <div class="field"><label>Mobile :</label><div class="box empty"><?= v($ld['mobile']) ?></div></div>
          <div class="field"><label>Alt. Mobile :</label><div class="box empty"><?= v($ld['alt_mobile']) ?></div></div>
          <div class="field"><label>Email :</label><div class="box wide empty"><?= v($ld['email']) ?></div></div>
        </div>
        <div class="row">
          <div class="field"><label>Pickup City :</label><div class="box wide"><?= v($ld['pickup_city']) ?></div></div>
          <div class="field"><label>Drop City :</label><div class="box wide"><?= v($ld['drop_city']) ?></div></div>
        </div>
        <div class="row">
          <div class="field"><label>Material Type :</label><div class="box wide empty"><?= v($ld['material_type']) ?></div></div>
          <div class="field"><label>Vehicle Type Req. :</label><div class="box wide"><?= v($ld['vehicle_type_required']) ?></div></div>
        </div>
        <div class="row">
          <div class="field"><label>Weight :</label><div class="box"><?= v($ld['weight']) ?></div></div>
          <div class="field"><label>Vehicle Count :</label><div class="box narrow"><?= v($ld['vehicle_count'],'1') ?></div></div>
          <div class="field"><label>Expected Dispatch :</label><div class="box"><?= v(d($ld['expected_dispatch_date'])) ?></div></div>
        </div>
        <div class="row">
          <div class="field"><label>Current Status :</label><div class="box wide"><?= v($ld['current_status']) ?></div></div>
        </div>
        <?php if ($ld['lost_reason']): ?>
        <div class="row">
          <div class="field"><label>Lost Reason :</label><div class="box xwide"><?= v($ld['lost_reason']) ?></div></div>
        </div>
        <?php endif; ?>
      </div>
      <div class="sidepanel">
        <h4>Remarks :</h4>
        <div class="remarksbox"><?= v($ld['remarks'], 'No remarks recorded.') ?></div>
        <div class="scenic"><div class="sun"></div><div class="box1"></div><div class="box2"></div><div class="box3"></div></div>
      </div>
    </div>
    <?php endif; ?>
    <?php
} else {
    $total = $c->query("SELECT COUNT(*) c FROM leads")->fetch_assoc()['c'];
    $rows = $c->query("SELECT id, lead_no, lead_datetime, pickup_city, drop_city, material_type, vehicle_type_required, priority, current_status FROM leads ORDER BY id DESC");

    shell_start('Transportation', 'Lead / Enquiry [Transport] — List');
    ?>
    <div class="tabs">
      <div class="tab active">All Leads</div>
      <div class="spacer"></div>
      <div class="recordnav"><?= (int)$total ?> total records</div>
    </div>
    <div class="gridwrap">
      <table class="grid">
        <thead><tr><th>Lead No.</th><th>Date</th><th>From</th><th>To</th><th>Material</th><th>Vehicle Req.</th><th>Priority</th><th>Status</th></tr></thead>
        <tbody>
        <?php while ($r = $rows->fetch_assoc()):
            $statusClass = match($r['current_status']) { 'Won' => 'green', 'Lost' => 'gray', 'New' => 'blue', default => 'amber' };
            $prClass = match($r['priority']) { 'Urgent','High' => 'amber', default => 'gray' };
        ?>
          <tr onclick="location.href='?id=<?= (int)$r['id'] ?>'">
            <td><?= v($r['lead_no']) ?></td>
            <td><?= v(d($r['lead_datetime'])) ?></td>
            <td><?= v($r['pickup_city']) ?></td>
            <td><?= v($r['drop_city']) ?></td>
            <td><?= v($r['material_type']) ?></td>
            <td><?= v($r['vehicle_type_required']) ?></td>
            <td><span class="pill <?= $prClass ?>"><?= v($r['priority']) ?></span></td>
            <td><span class="pill <?= $statusClass ?>"><?= v($r['current_status']) ?></span></td>
          </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
    </div>
    <?php
}
shell_end();
