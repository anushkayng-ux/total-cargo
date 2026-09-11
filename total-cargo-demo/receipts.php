<?php
require __DIR__ . '/inc/db.php';
require __DIR__ . '/inc/shell.php';
$c = db();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id > 0) {
    $stmt = $c->prepare("SELECT r.*, cl.company_name, i.invoice_no FROM receipts r LEFT JOIN clients cl ON cl.id = r.client_id LEFT JOIN invoices i ON i.id = r.invoice_id WHERE r.id = ? LIMIT 1");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $rc = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $prevRow = $c->query("SELECT id FROM receipts WHERE id < " . (int)$id . " ORDER BY id DESC LIMIT 1")->fetch_assoc();
    $nextRow = $c->query("SELECT id FROM receipts WHERE id > " . (int)$id . " ORDER BY id ASC LIMIT 1")->fetch_assoc();
    $prevId  = $prevRow['id'] ?? null;
    $nextId  = $nextRow['id'] ?? null;
    $totalCount = $c->query("SELECT COUNT(*) c FROM receipts")->fetch_assoc()['c'] ?? 0;

    shell_start('Accounts', 'Receipt [Accounts]');
    ?>
    <div class="tabs">
      <div class="tab active">Receipt Details</div>
      <div class="spacer"></div>
      <div class="recordnav">
        <a href="receipts.php">&#9776; List</a> &middot;
        Record <?= $rc ? $id : 0 ?> &middot; <?= (int)$totalCount ?> total
        <a class="<?= $prevId ? '' : 'disabled' ?>" href="?id=<?= (int)$prevId ?>">&#9664; Prev</a>
        <a class="<?= $nextId ? '' : 'disabled' ?>" href="?id=<?= (int)$nextId ?>">Next &#9654;</a>
      </div>
    </div>
    <?php if (!$rc): ?>
      <div class="notfound">Receipt not found.</div>
    <?php else: ?>
    <div class="content">
      <div class="formwrap">
        <div class="row">
          <div class="field"><label>Client :</label><div class="box xwide"><?= v($rc['company_name']) ?></div></div>
          <div class="field"><label>Receipt Date :</label><div class="box"><?= v(d($rc['receipt_date'])) ?></div></div>
        </div>
        <div class="row">
          <div class="field"><label>Against Invoice :</label><div class="box wide empty"><?= v($rc['invoice_no']) ?></div></div>
          <div class="field"><label>Payment Mode :</label><div class="box"><?= v($rc['payment_mode']) ?></div></div>
        </div>
        <div class="row">
          <div class="field"><label>Amount Received :</label><div class="box" style="font-weight:700;"><?= money($rc['amount_received']) ?></div></div>
          <div class="field"><label>Reference No. :</label><div class="box wide empty"><?= v($rc['reference_no']) ?></div></div>
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
    $total = $c->query("SELECT COUNT(*) c FROM receipts")->fetch_assoc()['c'];
    $rows = $c->query("SELECT r.id, cl.company_name, i.invoice_no, r.receipt_date, r.payment_mode, r.amount_received
                        FROM receipts r LEFT JOIN clients cl ON cl.id = r.client_id LEFT JOIN invoices i ON i.id = r.invoice_id
                        ORDER BY r.id DESC");

    shell_start('Accounts', 'Receipt [Accounts] — List');
    ?>
    <div class="tabs">
      <div class="tab active">All Receipts</div>
      <div class="spacer"></div>
      <div class="recordnav"><?= (int)$total ?> total records</div>
    </div>
    <div class="gridwrap">
      <table class="grid">
        <thead><tr><th>Client</th><th>Invoice No.</th><th>Date</th><th>Mode</th><th>Amount</th></tr></thead>
        <tbody>
        <?php while ($r = $rows->fetch_assoc()): ?>
          <tr onclick="location.href='?id=<?= (int)$r['id'] ?>'">
            <td><?= v($r['company_name']) ?></td>
            <td><?= v($r['invoice_no']) ?></td>
            <td><?= v(d($r['receipt_date'])) ?></td>
            <td><?= v($r['payment_mode']) ?></td>
            <td><?= money($r['amount_received']) ?></td>
          </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
    </div>
    <?php
}
shell_end();
