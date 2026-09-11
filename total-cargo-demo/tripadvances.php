<?php
require __DIR__ . '/inc/db.php';
require __DIR__ . '/inc/shell.php';
$c = db();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id > 0) {
    $stmt = $c->prepare("SELECT ta.*, t.trip_no, t.vehicle_number FROM trip_advances ta LEFT JOIN trips t ON t.id = ta.trip_id WHERE ta.id = ? LIMIT 1");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $ta = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $prevRow = $c->query("SELECT id FROM trip_advances WHERE id < " . (int)$id . " ORDER BY id DESC LIMIT 1")->fetch_assoc();
    $nextRow = $c->query("SELECT id FROM trip_advances WHERE id > " . (int)$id . " ORDER BY id ASC LIMIT 1")->fetch_assoc();
    $prevId  = $prevRow['id'] ?? null;
    $nextId  = $nextRow['id'] ?? null;
    $totalCount = $c->query("SELECT COUNT(*) c FROM trip_advances")->fetch_assoc()['c'] ?? 0;

    shell_start('Transportation', 'Trip Advance [Transport]');
    ?>
    <div class="tabs">
      <div class="tab active">Advance Details</div>
      <div class="spacer"></div>
      <div class="recordnav">
        <a href="tripadvances.php">&#9776; List</a> &middot;
        Record <?= $ta ? $id : 0 ?> &middot; <?= (int)$totalCount ?> total
        <a class="<?= $prevId ? '' : 'disabled' ?>" href="?id=<?= (int)$prevId ?>">&#9664; Prev</a>
        <a class="<?= $nextId ? '' : 'disabled' ?>" href="?id=<?= (int)$nextId ?>">Next &#9654;</a>
      </div>
    </div>
    <?php if (!$ta): ?>
      <div class="notfound">Trip advance not found.</div>
    <?php else: ?>
    <div class="content">
      <div class="formwrap">
        <div class="row">
          <div class="field"><label>Trip No. :</label><div class="box"><?= v($ta['trip_no']) ?></div></div>
          <div class="field"><label>Vehicle No :</label><div class="box wide"><?= v($ta['vehicle_number']) ?></div></div>
        </div>
        <div class="row">
          <div class="field"><label>Amount :</label><div class="box"><?= money($ta['amount']) ?></div></div>
          <div class="field"><label>Mode :</label><div class="box"><?= v($ta['mode']) ?></div></div>
          <div class="field"><label>Reference No. :</label><div class="box empty"><?= v($ta['reference_no']) ?></div></div>
        </div>
        <div class="row">
          <div class="field"><label>Given To :</label><div class="box wide"><?= v($ta['given_to']) ?></div></div>
          <div class="field"><label>Given At :</label><div class="box wide empty"><?= v($ta['given_at']) ?></div></div>
        </div>
        <div class="row">
          <div class="field"><label>Settled At :</label><div class="box wide empty"><?= v($ta['settled_at']) ?></div></div>
          <div class="field"><label>Settled Amount :</label><div class="box"><?= money($ta['settled_amount']) ?></div></div>
        </div>
      </div>
      <div class="sidepanel">
        <h4>Notes :</h4>
        <div class="remarksbox"><?= v($ta['notes'], 'No notes recorded.') ?></div>
        <div class="scenic"><div class="sun"></div><div class="box1"></div><div class="box2"></div><div class="box3"></div></div>
      </div>
    </div>
    <?php endif; ?>
    <?php
} else {
    $total = $c->query("SELECT COUNT(*) c FROM trip_advances")->fetch_assoc()['c'];
    $rows = $c->query("SELECT ta.id, t.trip_no, t.vehicle_number, ta.amount, ta.mode, ta.given_to, ta.given_at, ta.settled_amount
                        FROM trip_advances ta LEFT JOIN trips t ON t.id = ta.trip_id ORDER BY ta.id DESC");

    shell_start('Transportation', 'Trip Advance [Transport] — List');
    ?>
    <div class="tabs">
      <div class="tab active">All Advances</div>
      <div class="spacer"></div>
      <div class="recordnav"><?= (int)$total ?> total records</div>
    </div>
    <div class="gridwrap">
      <table class="grid">
        <thead><tr><th>Trip No.</th><th>Vehicle No</th><th>Amount</th><th>Mode</th><th>Given To</th><th>Given At</th><th>Settled</th></tr></thead>
        <tbody>
        <?php while ($r = $rows->fetch_assoc()): ?>
          <tr onclick="location.href='?id=<?= (int)$r['id'] ?>'">
            <td><?= v($r['trip_no']) ?></td>
            <td><?= v($r['vehicle_number']) ?></td>
            <td><?= money($r['amount']) ?></td>
            <td><?= v($r['mode']) ?></td>
            <td><?= v($r['given_to']) ?></td>
            <td><?= v($r['given_at']) ?></td>
            <td><?= money($r['settled_amount']) ?></td>
          </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
    </div>
    <?php
}
shell_end();
