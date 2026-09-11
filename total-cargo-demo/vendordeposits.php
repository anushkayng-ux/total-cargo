<?php
require __DIR__ . '/inc/db.php';
require __DIR__ . '/inc/shell.php';
$c = db();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id > 0) {
    $stmt = $c->prepare("SELECT vd.*, v.company_name, t.trip_no FROM vendor_deposits vd LEFT JOIN vendors v ON v.id = vd.vendor_id LEFT JOIN trips t ON t.id = vd.reference_trip_id WHERE vd.id = ? LIMIT 1");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $vd = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $prevRow = $c->query("SELECT id FROM vendor_deposits WHERE id < " . (int)$id . " ORDER BY id DESC LIMIT 1")->fetch_assoc();
    $nextRow = $c->query("SELECT id FROM vendor_deposits WHERE id > " . (int)$id . " ORDER BY id ASC LIMIT 1")->fetch_assoc();
    $prevId  = $prevRow['id'] ?? null;
    $nextId  = $nextRow['id'] ?? null;
    $totalCount = $c->query("SELECT COUNT(*) c FROM vendor_deposits")->fetch_assoc()['c'] ?? 0;

    shell_start('General Masters', 'Vendor Deposit [General Masters]');
    ?>
    <div class="tabs">
      <div class="tab active">Transaction Details</div>
      <div class="spacer"></div>
      <div class="recordnav">
        <a href="vendordeposits.php">&#9776; List</a> &middot;
        Record <?= $vd ? $id : 0 ?> &middot; <?= (int)$totalCount ?> total
        <a class="<?= $prevId ? '' : 'disabled' ?>" href="?id=<?= (int)$prevId ?>">&#9664; Prev</a>
        <a class="<?= $nextId ? '' : 'disabled' ?>" href="?id=<?= (int)$nextId ?>">Next &#9654;</a>
      </div>
    </div>
    <?php if (!$vd): ?>
      <div class="notfound">Deposit transaction not found.</div>
    <?php else: ?>
    <div class="content">
      <div class="formwrap">
        <div class="row">
          <div class="field" style="width:100%;"><label>Vendor :</label><div class="box xwide" style="min-width:340px;"><?= v($vd['company_name']) ?></div></div>
        </div>
        <div class="row">
          <div class="field"><label>Transaction Type :</label><div class="box"><?= v($vd['txn_type']) ?></div></div>
          <div class="field"><label>Date :</label><div class="box"><?= v(d($vd['txn_date'])) ?></div></div>
          <div class="field"><label>Reference No. :</label><div class="box empty"><?= v($vd['reference_no']) ?></div></div>
        </div>
        <div class="row">
          <div class="field"><label>Amount :</label><div class="box"><?= money($vd['amount']) ?></div></div>
          <div class="field"><label>Balance After :</label><div class="box" style="font-weight:700;"><?= money($vd['balance_after']) ?></div></div>
          <div class="field"><label>Related Trip :</label><div class="box empty"><?= v($vd['trip_no']) ?></div></div>
        </div>
        <div class="row" style="align-items:flex-start;">
          <div class="field" style="width:100%;"><label style="white-space:nowrap;">Reason :</label>
            <div class="particulars"><?= v($vd['reason']) ?></div>
          </div>
        </div>
      </div>
      <div class="sidepanel">
        <h4>Notes :</h4>
        <div class="remarksbox">Recorded <?= v($vd['created_at']) ?></div>
        <div class="scenic"><div class="sun"></div><div class="box1"></div><div class="box2"></div><div class="box3"></div></div>
      </div>
    </div>
    <?php endif; ?>
    <?php
} else {
    $total = $c->query("SELECT COUNT(*) c FROM vendor_deposits")->fetch_assoc()['c'];
    $rows = $c->query("SELECT vd.id, v.company_name, vd.txn_type, vd.amount, vd.balance_after, vd.txn_date
                        FROM vendor_deposits vd LEFT JOIN vendors v ON v.id = vd.vendor_id ORDER BY vd.id DESC");

    shell_start('General Masters', 'Vendor Deposit [General Masters] — List');
    ?>
    <div class="tabs">
      <div class="tab active">All Deposit Transactions</div>
      <div class="spacer"></div>
      <div class="recordnav"><?= (int)$total ?> total records</div>
    </div>
    <div class="gridwrap">
      <table class="grid">
        <thead><tr><th>Vendor</th><th>Type</th><th>Amount</th><th>Balance After</th><th>Date</th></tr></thead>
        <tbody>
        <?php while ($r = $rows->fetch_assoc()):
            $typeClass = match($r['txn_type']) { 'Deposit' => 'blue', 'Release' => 'green', default => 'gray' };
        ?>
          <tr onclick="location.href='?id=<?= (int)$r['id'] ?>'">
            <td><?= v($r['company_name']) ?></td>
            <td><span class="pill <?= $typeClass ?>"><?= v($r['txn_type']) ?></span></td>
            <td><?= money($r['amount']) ?></td>
            <td><?= money($r['balance_after']) ?></td>
            <td><?= v(d($r['txn_date'])) ?></td>
          </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
    </div>
    <?php
}
shell_end();
