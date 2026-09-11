<?php
require __DIR__ . '/inc/db.php';
require __DIR__ . '/inc/shell.php';
$c = db();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id > 0) {
    $stmt = $c->prepare("SELECT te.*, t.trip_no, t.vehicle_number FROM trip_expenses te LEFT JOIN trips t ON t.id = te.trip_id WHERE te.id = ? LIMIT 1");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $te = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $prevRow = $c->query("SELECT id FROM trip_expenses WHERE id < " . (int)$id . " ORDER BY id DESC LIMIT 1")->fetch_assoc();
    $nextRow = $c->query("SELECT id FROM trip_expenses WHERE id > " . (int)$id . " ORDER BY id ASC LIMIT 1")->fetch_assoc();
    $prevId  = $prevRow['id'] ?? null;
    $nextId  = $nextRow['id'] ?? null;
    $totalCount = $c->query("SELECT COUNT(*) c FROM trip_expenses")->fetch_assoc()['c'] ?? 0;

    shell_start('Transportation', 'Trip Expense [Transport]');
    ?>
    <div class="tabs">
      <div class="tab active">Expense Details</div>
      <div class="spacer"></div>
      <div class="recordnav">
        <a href="tripexpenses.php">&#9776; List</a> &middot;
        Record <?= $te ? $id : 0 ?> &middot; <?= (int)$totalCount ?> total
        <a class="<?= $prevId ? '' : 'disabled' ?>" href="?id=<?= (int)$prevId ?>">&#9664; Prev</a>
        <a class="<?= $nextId ? '' : 'disabled' ?>" href="?id=<?= (int)$nextId ?>">Next &#9654;</a>
      </div>
    </div>
    <?php if (!$te): ?>
      <div class="notfound">Expense not found.</div>
    <?php else: ?>
    <div class="content">
      <div class="formwrap">
        <div class="row">
          <div class="field"><label>Trip No. :</label><div class="box"><?= v($te['trip_no']) ?></div></div>
          <div class="field"><label>Vehicle No :</label><div class="box wide"><?= v($te['vehicle_number']) ?></div></div>
          <div class="field" style="margin-left:auto;"><label>Date :</label><div class="box"><?= v(d($te['expense_date'])) ?></div></div>
        </div>
        <div class="row">
          <div class="field"><label>Category :</label><div class="box"><?= v($te['category']) ?></div></div>
          <div class="field"><label>Amount :</label><div class="box"><?= money($te['amount']) ?></div></div>
          <div class="field"><label>Billable :</label><div class="box narrow"><?= $te['is_billable'] ? 'Yes' : 'No' ?></div></div>
        </div>
        <div class="row">
          <div class="field"><label>Paid To :</label><div class="box"><?= v($te['paid_to']) ?></div></div>
          <div class="field"><label>Payment Mode :</label><div class="box empty"><?= v($te['payment_mode']) ?></div></div>
          <div class="field"><label>Reference No. :</label><div class="box empty"><?= v($te['reference_no']) ?></div></div>
        </div>
        <div class="row">
          <div class="field" style="width:100%;"><label>Description :</label><div class="box xwide" style="min-width:300px;"><?= v($te['description']) ?></div></div>
        </div>
      </div>
      <div class="sidepanel">
        <h4>Remarks :</h4>
        <div class="remarksbox"><?= v($te['remarks'], 'No remarks recorded.') ?></div>
        <div class="scenic"><div class="sun"></div><div class="box1"></div><div class="box2"></div><div class="box3"></div></div>
      </div>
    </div>
    <?php endif; ?>
    <?php
} else {
    $total = $c->query("SELECT COUNT(*) c FROM trip_expenses")->fetch_assoc()['c'];
    $rows = $c->query("SELECT te.id, t.trip_no, te.expense_date, te.category, te.description, te.amount, te.is_billable, te.paid_to
                        FROM trip_expenses te LEFT JOIN trips t ON t.id = te.trip_id ORDER BY te.id DESC");

    shell_start('Transportation', 'Trip Expense [Transport] — List');
    ?>
    <div class="tabs">
      <div class="tab active">All Expenses</div>
      <div class="spacer"></div>
      <div class="recordnav"><?= (int)$total ?> total records</div>
    </div>
    <div class="gridwrap">
      <table class="grid">
        <thead><tr><th>Trip No.</th><th>Date</th><th>Category</th><th>Description</th><th>Amount</th><th>Billable</th><th>Paid To</th></tr></thead>
        <tbody>
        <?php while ($r = $rows->fetch_assoc()): ?>
          <tr onclick="location.href='?id=<?= (int)$r['id'] ?>'">
            <td><?= v($r['trip_no']) ?></td>
            <td><?= v(d($r['expense_date'])) ?></td>
            <td><?= v($r['category']) ?></td>
            <td><?= v($r['description']) ?></td>
            <td><?= money($r['amount']) ?></td>
            <td><?= $r['is_billable'] ? '<span class="pill blue">Yes</span>' : '—' ?></td>
            <td><?= v($r['paid_to']) ?></td>
          </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
    </div>
    <?php
}
shell_end();
