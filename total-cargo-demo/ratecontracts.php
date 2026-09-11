<?php
require __DIR__ . '/inc/db.php';
require __DIR__ . '/inc/shell.php';
$c = db();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id > 0) {
    $stmt = $c->prepare("SELECT rc.*, cl.company_name FROM rate_contracts rc LEFT JOIN clients cl ON cl.id = rc.client_id WHERE rc.id = ? LIMIT 1");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $rc = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $prevRow = $c->query("SELECT id FROM rate_contracts WHERE id < " . (int)$id . " ORDER BY id DESC LIMIT 1")->fetch_assoc();
    $nextRow = $c->query("SELECT id FROM rate_contracts WHERE id > " . (int)$id . " ORDER BY id ASC LIMIT 1")->fetch_assoc();
    $prevId  = $prevRow['id'] ?? null;
    $nextId  = $nextRow['id'] ?? null;
    $totalCount = $c->query("SELECT COUNT(*) c FROM rate_contracts")->fetch_assoc()['c'] ?? 0;

    shell_start('Accounts', 'Rate Contract [Accounts]');
    ?>
    <div class="tabs">
      <div class="tab active">Contract Details</div>
      <div class="spacer"></div>
      <div class="recordnav">
        <a href="ratecontracts.php">&#9776; List</a> &middot;
        Record <?= $rc ? $id : 0 ?> &middot; <?= (int)$totalCount ?> total
        <a class="<?= $prevId ? '' : 'disabled' ?>" href="?id=<?= (int)$prevId ?>">&#9664; Prev</a>
        <a class="<?= $nextId ? '' : 'disabled' ?>" href="?id=<?= (int)$nextId ?>">Next &#9654;</a>
      </div>
    </div>
    <?php if (!$rc): ?>
      <div class="notfound">Rate contract not found.</div>
    <?php else: ?>
    <div class="content">
      <div class="formwrap">
        <div class="row">
          <div class="field"><label>Contract No. :</label><div class="box"><?= v($rc['contract_no']) ?></div></div>
          <div class="field" style="margin-left:auto;"><label>Status :</label><div class="box"><?= v($rc['status']) ?></div></div>
        </div>
        <div class="row">
          <div class="field" style="width:100%;"><label>Client :</label><div class="box xwide" style="min-width:340px;"><?= v($rc['company_name']) ?></div></div>
        </div>
        <div class="row">
          <div class="field"><label>Valid From :</label><div class="box"><?= v(d($rc['valid_from'])) ?></div></div>
          <div class="field"><label>Valid To :</label><div class="box"><?= v(d($rc['valid_to'])) ?></div></div>
        </div>
        <div class="row">
          <div class="field"><label>TDS Rate % :</label><div class="box narrow"><?= v($rc['tds_rate']) ?></div></div>
          <div class="field"><label>GST Treatment :</label><div class="box"><?= v(strtoupper($rc['gst_treatment'])) ?></div></div>
        </div>
      </div>
      <div class="sidepanel">
        <h4>Notes :</h4>
        <div class="remarksbox"><?= v($rc['notes'], 'No notes recorded.') ?></div>
        <div class="scenic"><div class="sun"></div><div class="box1"></div><div class="box2"></div><div class="box3"></div></div>
      </div>
    </div>
    <?php endif; ?>
    <?php
} else {
    $total = $c->query("SELECT COUNT(*) c FROM rate_contracts")->fetch_assoc()['c'];
    $rows = $c->query("SELECT rc.id, rc.contract_no, cl.company_name, rc.valid_from, rc.valid_to, rc.status
                        FROM rate_contracts rc LEFT JOIN clients cl ON cl.id = rc.client_id ORDER BY rc.id DESC");

    shell_start('Accounts', 'Rate Contract [Accounts] — List');
    ?>
    <div class="tabs">
      <div class="tab active">All Rate Contracts</div>
      <div class="spacer"></div>
      <div class="recordnav"><?= (int)$total ?> total records</div>
    </div>
    <div class="gridwrap">
      <table class="grid">
        <thead><tr><th>Contract No.</th><th>Client</th><th>Valid From</th><th>Valid To</th><th>Status</th></tr></thead>
        <tbody>
        <?php while ($r = $rows->fetch_assoc()):
            $statusClass = match($r['status']) { 'Active' => 'green', 'Expired' => 'gray', 'Suspended' => 'amber', default => 'blue' };
        ?>
          <tr onclick="location.href='?id=<?= (int)$r['id'] ?>'">
            <td><?= v($r['contract_no']) ?></td>
            <td><?= v($r['company_name']) ?></td>
            <td><?= v(d($r['valid_from'])) ?></td>
            <td><?= v(d($r['valid_to'])) ?></td>
            <td><span class="pill <?= $statusClass ?>"><?= v($r['status']) ?></span></td>
          </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
    </div>
    <?php
}
shell_end();
