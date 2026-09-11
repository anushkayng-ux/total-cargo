<?php
require __DIR__ . '/inc/db.php';
require __DIR__ . '/inc/shell.php';
$c = db();

$id     = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$search = isset($_GET['q']) ? trim($_GET['q']) : '';

if ($search !== '' && $id === 0) {
    $stmt = $c->prepare("SELECT id FROM rfq_master WHERE rfq_no = ? OR masked_reference = ? ORDER BY id DESC LIMIT 1");
    $stmt->bind_param('ss', $search, $search);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    if ($res) $id = (int) $res['id'];
    $stmt->close();
}

shell_search('RFQ No.', $search);

if ($id > 0) {
    $stmt = $c->prepare("SELECT * FROM rfq_master WHERE id = ? LIMIT 1");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $rf = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $quotes = [];
    if ($rf) {
        $qs = $c->prepare("SELECT q.*, v.company_name, v.vendor_code FROM quotations q LEFT JOIN vendors v ON v.id = q.vendor_id WHERE q.rfq_id = ? ORDER BY q.quote_amount ASC");
        $qs->bind_param('i', $id);
        $qs->execute();
        $quotes = $qs->get_result()->fetch_all(MYSQLI_ASSOC);
        $qs->close();

        $vs = $c->prepare("SELECT COUNT(*) n FROM rfq_vendors WHERE rfq_id = ?");
        $vs->bind_param('i', $id);
        $vs->execute();
        $vendorsSent = $vs->get_result()->fetch_assoc()['n'] ?? 0;
        $vs->close();
    }

    $prevRow = $c->query("SELECT id FROM rfq_master WHERE id < " . (int)$id . " ORDER BY id DESC LIMIT 1")->fetch_assoc();
    $nextRow = $c->query("SELECT id FROM rfq_master WHERE id > " . (int)$id . " ORDER BY id ASC LIMIT 1")->fetch_assoc();
    $prevId  = $prevRow['id'] ?? null;
    $nextId  = $nextRow['id'] ?? null;
    $totalCount = $c->query("SELECT COUNT(*) c FROM rfq_master")->fetch_assoc()['c'] ?? 0;

    shell_start('Transportation', 'RFQ / Vendor Enquiry [Transport]');
    ?>
    <div class="tabs">
      <div class="tab active">RFQ Details</div>
      <div class="tab">Vendor Quotations</div>
      <div class="spacer"></div>
      <div class="recordnav">
        <a href="rfq.php">&#9776; List</a> &middot;
        Record <?= $rf ? $id : 0 ?> &middot; <?= (int)$totalCount ?> total
        <a class="<?= $prevId ? '' : 'disabled' ?>" href="?id=<?= (int)$prevId ?>">&#9664; Prev</a>
        <a class="<?= $nextId ? '' : 'disabled' ?>" href="?id=<?= (int)$nextId ?>">Next &#9654;</a>
      </div>
    </div>
    <?php if (!$rf): ?>
      <div class="notfound">RFQ not found.</div>
    <?php else: ?>
    <div class="content">
      <div class="formwrap">
        <div class="row">
          <div class="field"><label>RFQ No. :</label><div class="box"><?= v($rf['rfq_no']) ?></div></div>
          <div class="field"><label>Reference :</label><div class="box wide empty"><?= v($rf['masked_reference']) ?></div></div>
          <div class="field" style="margin-left:auto;"><label>Status :</label><div class="box"><?= v($rf['status']) ?></div></div>
        </div>
        <div class="row">
          <div class="field"><label>Pickup City :</label><div class="box wide"><?= v($rf['pickup_city']) ?></div></div>
          <div class="field"><label>Drop City :</label><div class="box wide"><?= v($rf['drop_city']) ?></div></div>
        </div>
        <div class="row">
          <div class="field"><label>Vehicle Type :</label><div class="box wide"><?= v($rf['vehicle_type']) ?></div></div>
          <div class="field"><label>Material Category :</label><div class="box wide empty"><?= v($rf['material_category']) ?></div></div>
        </div>
        <div class="row">
          <div class="field"><label>Weight :</label><div class="box"><?= v($rf['weight']) ?></div></div>
          <div class="field"><label>Unit :</label><div class="box narrow"><?= v($rf['weight_unit']) ?></div></div>
          <div class="field"><label>Loading Date :</label><div class="box"><?= v(d($rf['loading_date'])) ?></div></div>
        </div>
        <div class="row">
          <div class="field"><label>Sent to Vendors :</label><div class="box narrow"><?= (int)$vendorsSent ?></div></div>
          <div class="field"><label>Quotes Received :</label><div class="box narrow"><?= count($quotes) ?></div></div>
        </div>

        <div class="row" style="margin-top:8px;"><div class="field"><label style="font-size:13px;">Vendor Quotations (lowest first) :</label></div></div>
        <?php if (!$quotes): ?>
          <div class="particulars">No quotations received yet for this RFQ.</div>
        <?php else: ?>
        <table class="grid" style="min-width:0;">
          <thead><tr><th>Vendor</th><th>Quote Amount</th><th>Transit Days</th><th>Source</th><th>Shortlisted</th><th>Final</th></tr></thead>
          <tbody>
          <?php foreach ($quotes as $q): ?>
            <tr>
              <td><?= v($q['company_name'] ?: $q['vendor_code']) ?></td>
              <td><?= money($q['quote_amount']) ?></td>
              <td><?= v($q['transit_days']) ?></td>
              <td><?= v($q['response_source']) ?></td>
              <td><?= $q['is_shortlisted'] ? '<span class="pill blue">Yes</span>' : '—' ?></td>
              <td><?= $q['is_final_selected'] ? '<span class="pill green">Selected</span>' : '—' ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
        <?php endif; ?>
      </div>
      <div class="sidepanel">
        <h4>Notes :</h4>
        <div class="remarksbox">Lead #<?= v($rf['lead_id'],'—') ?> · created <?= v($rf['created_at']) ?></div>
        <div class="scenic"><div class="sun"></div><div class="box1"></div><div class="box2"></div><div class="box3"></div></div>
      </div>
    </div>
    <?php endif; ?>
    <?php
} else {
    $total = $c->query("SELECT COUNT(*) c FROM rfq_master")->fetch_assoc()['c'];
    $rows = $c->query("SELECT r.id, r.rfq_no, r.pickup_city, r.drop_city, r.vehicle_type, r.weight, r.weight_unit, r.status, r.loading_date,
                        (SELECT COUNT(*) FROM quotations q WHERE q.rfq_id = r.id) AS quote_count
                        FROM rfq_master r ORDER BY r.id DESC");

    shell_start('Transportation', 'RFQ / Vendor Enquiry [Transport] — List');
    ?>
    <div class="tabs">
      <div class="tab active">All RFQs</div>
      <div class="spacer"></div>
      <div class="recordnav"><?= (int)$total ?> total records</div>
    </div>
    <div class="gridwrap">
      <table class="grid">
        <thead><tr><th>RFQ No.</th><th>From</th><th>To</th><th>Vehicle Type</th><th>Weight</th><th>Loading Date</th><th>Quotes</th><th>Status</th></tr></thead>
        <tbody>
        <?php while ($r = $rows->fetch_assoc()):
            $statusClass = match($r['status']) { 'Closed' => 'green', 'Open' => 'blue', 'In Progress' => 'amber', default => 'gray' };
        ?>
          <tr onclick="location.href='?id=<?= (int)$r['id'] ?>'">
            <td><?= v($r['rfq_no']) ?></td>
            <td><?= v($r['pickup_city']) ?></td>
            <td><?= v($r['drop_city']) ?></td>
            <td><?= v($r['vehicle_type']) ?></td>
            <td><?= v($r['weight']) ?> <?= v($r['weight_unit']) ?></td>
            <td><?= v(d($r['loading_date'])) ?></td>
            <td><?= (int)$r['quote_count'] ?></td>
            <td><span class="pill <?= $statusClass ?>"><?= v($r['status']) ?></span></td>
          </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
    </div>
    <?php
}
shell_end();
