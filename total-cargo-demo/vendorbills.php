<?php
require __DIR__ . '/inc/db.php';
require __DIR__ . '/inc/shell.php';
$c = db();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id > 0) {
    $stmt = $c->prepare("SELECT vb.*, v.company_name, v.vendor_code FROM vendor_bills vb LEFT JOIN vendors v ON v.id = vb.vendor_id WHERE vb.id = ? LIMIT 1");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $vb = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $payments = [];
    if ($vb) {
        $ps = $c->prepare("SELECT * FROM vendor_payments WHERE vendor_bill_id = ? ORDER BY payment_date");
        $ps->bind_param('i', $id);
        $ps->execute();
        $payments = $ps->get_result()->fetch_all(MYSQLI_ASSOC);
        $ps->close();
    }

    $prevRow = $c->query("SELECT id FROM vendor_bills WHERE id < " . (int)$id . " ORDER BY id DESC LIMIT 1")->fetch_assoc();
    $nextRow = $c->query("SELECT id FROM vendor_bills WHERE id > " . (int)$id . " ORDER BY id ASC LIMIT 1")->fetch_assoc();
    $prevId  = $prevRow['id'] ?? null;
    $nextId  = $nextRow['id'] ?? null;
    $totalCount = $c->query("SELECT COUNT(*) c FROM vendor_bills")->fetch_assoc()['c'] ?? 0;

    shell_start('Accounts', 'Vendor Bill [Accounts]');
    ?>
    <div class="tabs">
      <div class="tab active">Bill Details</div>
      <div class="tab">Payments</div>
      <div class="spacer"></div>
      <div class="recordnav">
        <a href="vendorbills.php">&#9776; List</a> &middot;
        Record <?= $vb ? $id : 0 ?> &middot; <?= (int)$totalCount ?> total
        <a class="<?= $prevId ? '' : 'disabled' ?>" href="?id=<?= (int)$prevId ?>">&#9664; Prev</a>
        <a class="<?= $nextId ? '' : 'disabled' ?>" href="?id=<?= (int)$nextId ?>">Next &#9654;</a>
      </div>
    </div>
    <?php if (!$vb): ?>
      <div class="notfound">Vendor bill not found.</div>
    <?php else: ?>
    <div class="content">
      <div class="formwrap">
        <div class="row">
          <div class="field"><label>Vendor :</label><div class="box xwide"><?= v($vb['company_name'] ?: $vb['vendor_code']) ?></div></div>
          <div class="field" style="margin-left:auto;"><label>Status :</label><div class="box"><?= v($vb['status']) ?></div></div>
        </div>
        <div class="row">
          <div class="field"><label>Bill No. :</label><div class="box empty"><?= v($vb['bill_no']) ?></div></div>
          <div class="field"><label>Bill Date :</label><div class="box"><?= v(d($vb['bill_date'])) ?></div></div>
          <div class="field"><label>Due Date :</label><div class="box empty"><?= v(d($vb['due_date'])) ?></div></div>
        </div>
        <div class="row">
          <div class="field"><label>Bill Amount :</label><div class="box"><?= money($vb['bill_amount']) ?></div></div>
          <div class="field"><label>Amount Paid :</label><div class="box"><?= money($vb['amount_paid']) ?></div></div>
          <div class="field"><label>Balance Due :</label><div class="box" style="font-weight:700;color:#b3261e;"><?= money($vb['balance_due']) ?></div></div>
        </div>

        <div class="row" style="margin-top:8px;"><div class="field"><label style="font-size:13px;">Payments Against This Bill :</label></div></div>
        <?php if (!$payments): ?>
          <div class="particulars">No payments recorded yet.</div>
        <?php else: ?>
        <table class="grid" style="min-width:0;">
          <thead><tr><th>Date</th><th>Amount</th><th>Mode</th><th>Reference No.</th></tr></thead>
          <tbody>
          <?php foreach ($payments as $p): ?>
            <tr>
              <td><?= v(d($p['payment_date'])) ?></td>
              <td><?= money($p['amount_paid']) ?></td>
              <td><?= v($p['payment_mode']) ?></td>
              <td><?= v($p['reference_no']) ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
        <?php endif; ?>
      </div>
      <div class="sidepanel">
        <h4>Notes :</h4>
        <div class="remarksbox"><?= v($vb['notes'], 'No notes recorded.') ?></div>
        <div class="scenic"><div class="sun"></div><div class="box1"></div><div class="box2"></div><div class="box3"></div></div>
      </div>
    </div>
    <?php endif; ?>
    <?php
} else {
    $total = $c->query("SELECT COUNT(*) c FROM vendor_bills")->fetch_assoc()['c'];
    $rows = $c->query("SELECT vb.id, v.company_name, vb.bill_no, vb.bill_date, vb.bill_amount, vb.amount_paid, vb.balance_due, vb.status
                        FROM vendor_bills vb LEFT JOIN vendors v ON v.id = vb.vendor_id ORDER BY vb.id DESC");

    shell_start('Accounts', 'Vendor Bill [Accounts] — List');
    ?>
    <div class="tabs">
      <div class="tab active">All Vendor Bills</div>
      <div class="spacer"></div>
      <div class="recordnav"><?= (int)$total ?> total records</div>
    </div>
    <div class="gridwrap">
      <table class="grid">
        <thead><tr><th>Vendor</th><th>Bill No.</th><th>Bill Date</th><th>Bill Amount</th><th>Paid</th><th>Balance Due</th><th>Status</th></tr></thead>
        <tbody>
        <?php while ($r = $rows->fetch_assoc()):
            $statusClass = match($r['status']) { 'Paid' => 'green', 'Cancelled' => 'gray', 'Open' => 'amber', default => 'blue' };
        ?>
          <tr onclick="location.href='?id=<?= (int)$r['id'] ?>'">
            <td><?= v($r['company_name']) ?></td>
            <td><?= v($r['bill_no']) ?></td>
            <td><?= v(d($r['bill_date'])) ?></td>
            <td><?= money($r['bill_amount']) ?></td>
            <td><?= money($r['amount_paid']) ?></td>
            <td><?= money($r['balance_due']) ?></td>
            <td><span class="pill <?= $statusClass ?>"><?= v($r['status']) ?></span></td>
          </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
    </div>
    <?php
}
shell_end();
