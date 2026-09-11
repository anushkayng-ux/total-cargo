<?php
require __DIR__ . '/inc/db.php';
require __DIR__ . '/inc/shell.php';
$c = db();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id > 0) {
    $stmt = $c->prepare("SELECT tc.*, cl.company_name FROM tds_certificates tc LEFT JOIN clients cl ON cl.id = tc.client_id WHERE tc.id = ? LIMIT 1");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $tc = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $prevRow = $c->query("SELECT id FROM tds_certificates WHERE id < " . (int)$id . " ORDER BY id DESC LIMIT 1")->fetch_assoc();
    $nextRow = $c->query("SELECT id FROM tds_certificates WHERE id > " . (int)$id . " ORDER BY id ASC LIMIT 1")->fetch_assoc();
    $prevId  = $prevRow['id'] ?? null;
    $nextId  = $nextRow['id'] ?? null;
    $totalCount = $c->query("SELECT COUNT(*) c FROM tds_certificates")->fetch_assoc()['c'] ?? 0;

    shell_start('Accounts', 'TDS Certificate [Accounts]');
    ?>
    <div class="tabs">
      <div class="tab active">Certificate Details</div>
      <div class="spacer"></div>
      <div class="recordnav">
        <a href="tdscertificates.php">&#9776; List</a> &middot;
        Record <?= $tc ? $id : 0 ?> &middot; <?= (int)$totalCount ?> total
        <a class="<?= $prevId ? '' : 'disabled' ?>" href="?id=<?= (int)$prevId ?>">&#9664; Prev</a>
        <a class="<?= $nextId ? '' : 'disabled' ?>" href="?id=<?= (int)$nextId ?>">Next &#9654;</a>
      </div>
    </div>
    <?php if (!$tc): ?>
      <div class="notfound">Certificate not found.</div>
    <?php else: ?>
    <div class="content">
      <div class="formwrap">
        <div class="row">
          <div class="field" style="width:100%;"><label>Client :</label><div class="box xwide" style="min-width:340px;"><?= v($tc['company_name']) ?></div></div>
        </div>
        <div class="row">
          <div class="field"><label>Financial Year :</label><div class="box"><?= v($tc['financial_year']) ?></div></div>
          <div class="field"><label>Quarter :</label><div class="box narrow"><?= v($tc['quarter']) ?></div></div>
          <div class="field" style="margin-left:auto;"><label>Status :</label><div class="box"><?= v($tc['status']) ?></div></div>
        </div>
        <div class="row">
          <div class="field"><label>Expected Amount :</label><div class="box"><?= money($tc['expected_amount']) ?></div></div>
          <div class="field"><label>Received Amount :</label><div class="box"><?= money($tc['received_amount']) ?></div></div>
        </div>
        <div class="row">
          <div class="field"><label>Certificate No. :</label><div class="box wide empty"><?= v($tc['certificate_no']) ?></div></div>
          <div class="field"><label>Received Date :</label><div class="box empty"><?= v(d($tc['received_date'])) ?></div></div>
        </div>
      </div>
      <div class="sidepanel">
        <h4>Notes :</h4>
        <div class="remarksbox"><?= v($tc['notes'], 'No notes recorded.') ?></div>
        <div class="scenic"><div class="sun"></div><div class="box1"></div><div class="box2"></div><div class="box3"></div></div>
      </div>
    </div>
    <?php endif; ?>
    <?php
} else {
    $total = $c->query("SELECT COUNT(*) c FROM tds_certificates")->fetch_assoc()['c'];
    $rows = $c->query("SELECT tc.id, cl.company_name, tc.financial_year, tc.quarter, tc.expected_amount, tc.received_amount, tc.status
                        FROM tds_certificates tc LEFT JOIN clients cl ON cl.id = tc.client_id ORDER BY tc.id DESC");

    shell_start('Accounts', 'TDS Certificate [Accounts] — List');
    ?>
    <div class="tabs">
      <div class="tab active">All Certificates</div>
      <div class="spacer"></div>
      <div class="recordnav"><?= (int)$total ?> total records</div>
    </div>
    <div class="gridwrap">
      <table class="grid">
        <thead><tr><th>Client</th><th>FY</th><th>Quarter</th><th>Expected</th><th>Received</th><th>Status</th></tr></thead>
        <tbody>
        <?php while ($r = $rows->fetch_assoc()):
            $statusClass = match($r['status']) { 'Received','Reconciled' => 'green', 'Disputed' => 'gray', default => 'amber' };
        ?>
          <tr onclick="location.href='?id=<?= (int)$r['id'] ?>'">
            <td><?= v($r['company_name']) ?></td>
            <td><?= v($r['financial_year']) ?></td>
            <td><?= v($r['quarter']) ?></td>
            <td><?= money($r['expected_amount']) ?></td>
            <td><?= money($r['received_amount']) ?></td>
            <td><span class="pill <?= $statusClass ?>"><?= v($r['status']) ?></span></td>
          </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
    </div>
    <?php
}
shell_end();
