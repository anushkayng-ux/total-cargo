<?php
require __DIR__ . '/inc/db.php';
require __DIR__ . '/inc/shell.php';
$c = db();

$id     = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$search = isset($_GET['q']) ? trim($_GET['q']) : '';

if ($search !== '' && $id === 0) {
    $stmt = $c->prepare("SELECT id FROM invoices WHERE invoice_no LIKE CONCAT('%',?,'%') ORDER BY id DESC LIMIT 1");
    $stmt->bind_param('s', $search);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    if ($res) $id = (int) $res['id'];
    $stmt->close();
}

shell_search('Invoice No.', $search);

if ($id > 0) {
    $stmt = $c->prepare("SELECT i.*, cl.company_name, cl.client_code, bk.lr_no, bk.booking_no FROM invoices i LEFT JOIN clients cl ON cl.id = i.client_id LEFT JOIN bookings bk ON bk.id = i.booking_id WHERE i.id = ? LIMIT 1");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $inv = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $prevRow = $c->query("SELECT id FROM invoices WHERE id < " . (int)$id . " ORDER BY id DESC LIMIT 1")->fetch_assoc();
    $nextRow = $c->query("SELECT id FROM invoices WHERE id > " . (int)$id . " ORDER BY id ASC LIMIT 1")->fetch_assoc();
    $prevId  = $prevRow['id'] ?? null;
    $nextId  = $nextRow['id'] ?? null;
    $totalCount = $c->query("SELECT COUNT(*) c FROM invoices")->fetch_assoc()['c'] ?? 0;

    shell_start('Accounts', 'Invoice [Accounts]');
    ?>
    <div class="tabs">
      <div class="tab active">Invoice Details</div>
      <div class="tab">Tax Breakup</div>
      <div class="tab">Payment / Receipt</div>
      <div class="spacer"></div>
      <div class="recordnav">
        <a href="invoices.php">&#9776; List</a> &middot;
        Record <?= $inv ? $id : 0 ?> &middot; <?= (int)$totalCount ?> total
        <a class="<?= $prevId ? '' : 'disabled' ?>" href="?id=<?= (int)$prevId ?>">&#9664; Prev</a>
        <a class="<?= $nextId ? '' : 'disabled' ?>" href="?id=<?= (int)$nextId ?>">Next &#9654;</a>
      </div>
    </div>
    <?php if (!$inv): ?>
      <div class="notfound">Invoice not found.</div>
    <?php else: ?>
    <div class="content">
      <div class="formwrap">
        <div class="row">
          <div class="field"><label>Invoice No. :</label><div class="box wide"><?= v($inv['invoice_no']) ?></div></div>
          <div class="field"><label>Invoice Date :</label><div class="box"><?= v(d($inv['invoice_date'])) ?></div></div>
          <div class="field" style="margin-left:auto;"><label>Status :</label><div class="box"><?= v($inv['invoice_status']) ?></div></div>
        </div>
        <div class="row">
          <div class="field" style="width:100%;"><label>Billed To :</label><div class="box xwide" style="min-width:340px;"><?= v($inv['company_name']) ?></div><div class="addbtn">+</div></div>
        </div>
        <div class="row">
          <div class="field"><label>GR/LR No. :</label><div class="box empty"><?= v($inv['lr_no'] ?: $inv['booking_no']) ?></div></div>
          <div class="field"><label>Due Date :</label><div class="box empty"><?= v(d($inv['due_date'])) ?></div></div>
        </div>
        <div class="row">
          <div class="field"><label>Taxable Amount :</label><div class="box"><?= money($inv['taxable_amount']) ?></div></div>
          <div class="field"><label>CGST :</label><div class="box narrow"><?= money($inv['cgst_amount']) ?></div></div>
          <div class="field"><label>SGST :</label><div class="box narrow"><?= money($inv['sgst_amount']) ?></div></div>
          <div class="field"><label>IGST :</label><div class="box narrow"><?= money($inv['igst_amount']) ?></div></div>
        </div>
        <div class="row">
          <div class="field"><label>Detention Amt :</label><div class="box"><?= money($inv['detention_amount']) ?></div></div>
          <div class="field"><label>TDS Rate % :</label><div class="box narrow"><?= v($inv['tds_rate'],'0') ?></div></div>
          <div class="field"><label>TDS Amount :</label><div class="box"><?= money($inv['tds_amount']) ?></div></div>
        </div>
        <div class="row">
          <div class="field"><label>Round Off :</label><div class="box narrow"><?= money($inv['round_off']) ?></div></div>
          <div class="field"><label>Total Amount :</label><div class="box wide" style="font-weight:700;"><?= money($inv['total_amount']) ?></div></div>
        </div>
        <div class="row">
          <div class="field"><label>Amount Received :</label><div class="box"><?= money($inv['amount_received']) ?></div></div>
          <div class="field"><label>Balance Due :</label><div class="box" style="font-weight:700; color:#b3261e;"><?= money($inv['balance_due']) ?></div></div>
        </div>
        <div class="row">
          <div class="field"><label>IRN No. :</label><div class="box wide empty"><?= v($inv['irn_no']) ?></div></div>
          <div class="field"><label>Ack No. :</label><div class="box empty"><?= v($inv['ack_no']) ?></div></div>
        </div>
      </div>
      <div class="sidepanel">
        <h4>Notes :</h4>
        <div class="remarksbox"><?= v($inv['notes'], 'No notes recorded.') ?></div>
        <div class="scenic"><div class="sun"></div><div class="box1"></div><div class="box2"></div><div class="box3"></div></div>
      </div>
    </div>
    <?php endif; ?>
    <?php
} else {
    $page = max(1, (int)($_GET['p'] ?? 1));
    $per  = 25;
    $off  = ($page - 1) * $per;
    $total = $c->query("SELECT COUNT(*) c FROM invoices")->fetch_assoc()['c'];
    $rows = $c->query("SELECT i.id, i.invoice_no, i.invoice_date, cl.company_name, i.total_amount, i.amount_received, i.balance_due, i.invoice_status
                        FROM invoices i LEFT JOIN clients cl ON cl.id = i.client_id
                        ORDER BY i.id DESC LIMIT $per OFFSET $off");

    shell_start('Accounts', 'Invoice [Accounts] — List');
    ?>
    <div class="tabs">
      <div class="tab active">All Invoices</div>
      <div class="spacer"></div>
      <div class="recordnav"><?= (int)$total ?> total records</div>
    </div>
    <div class="gridwrap">
      <table class="grid">
        <thead><tr><th>Invoice No.</th><th>Date</th><th>Client</th><th>Total Amount</th><th>Received</th><th>Balance Due</th><th>Status</th></tr></thead>
        <tbody>
        <?php while ($r = $rows->fetch_assoc()):
            $statusClass = match($r['invoice_status']) { 'Paid' => 'green', 'Issued' => 'blue', 'Draft' => 'gray', default => 'amber' };
        ?>
          <tr onclick="location.href='?id=<?= (int)$r['id'] ?>'">
            <td><?= v($r['invoice_no']) ?></td>
            <td><?= v(d($r['invoice_date'])) ?></td>
            <td><?= v($r['company_name']) ?></td>
            <td><?= money($r['total_amount']) ?></td>
            <td><?= money($r['amount_received']) ?></td>
            <td><?= money($r['balance_due']) ?></td>
            <td><span class="pill <?= $statusClass ?>"><?= v($r['invoice_status']) ?></span></td>
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
