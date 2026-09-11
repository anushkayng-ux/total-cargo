<?php
require __DIR__ . '/inc/db.php';
require __DIR__ . '/inc/shell.php';
$c = db();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id > 0) {
    $stmt = $c->prepare("SELECT ts.*, t.trip_no FROM trip_stops ts LEFT JOIN trips t ON t.id = ts.trip_id WHERE ts.id = ? LIMIT 1");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $ts = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $prevRow = $c->query("SELECT id FROM trip_stops WHERE id < " . (int)$id . " ORDER BY id DESC LIMIT 1")->fetch_assoc();
    $nextRow = $c->query("SELECT id FROM trip_stops WHERE id > " . (int)$id . " ORDER BY id ASC LIMIT 1")->fetch_assoc();
    $prevId  = $prevRow['id'] ?? null;
    $nextId  = $nextRow['id'] ?? null;
    $totalCount = $c->query("SELECT COUNT(*) c FROM trip_stops")->fetch_assoc()['c'] ?? 0;

    shell_start('Transportation', 'Trip Stop [Transport]');
    ?>
    <div class="tabs">
      <div class="tab active">Stop Details</div>
      <div class="spacer"></div>
      <div class="recordnav">
        <a href="tripstops.php">&#9776; List</a> &middot;
        Record <?= $ts ? $id : 0 ?> &middot; <?= (int)$totalCount ?> total
        <a class="<?= $prevId ? '' : 'disabled' ?>" href="?id=<?= (int)$prevId ?>">&#9664; Prev</a>
        <a class="<?= $nextId ? '' : 'disabled' ?>" href="?id=<?= (int)$nextId ?>">Next &#9654;</a>
      </div>
    </div>
    <?php if (!$ts): ?>
      <div class="notfound">Trip stop not found.</div>
    <?php else: ?>
    <div class="content">
      <div class="formwrap">
        <div class="row">
          <div class="field"><label>Trip No. :</label><div class="box"><?= v($ts['trip_no']) ?></div></div>
          <div class="field"><label>Sequence :</label><div class="box narrow"><?= v($ts['sequence']) ?></div></div>
          <div class="field" style="margin-left:auto;"><label>Stop Type :</label><div class="box"><?= v(ucfirst($ts['stop_type'])) ?></div></div>
        </div>
        <div class="row">
          <div class="field" style="width:100%;"><label>Address :</label><div class="box xwide" style="min-width:340px;"><?= v($ts['address']) ?></div></div>
        </div>
        <div class="row">
          <div class="field"><label>City :</label><div class="box wide"><?= v($ts['city']) ?></div></div>
        </div>
        <div class="row">
          <div class="field"><label>Contact Name :</label><div class="box wide empty"><?= v($ts['contact_name']) ?></div></div>
          <div class="field"><label>Contact Mobile :</label><div class="box empty"><?= v($ts['contact_mobile']) ?></div></div>
        </div>
        <div class="row">
          <div class="field"><label>Planned At :</label><div class="box wide empty"><?= v($ts['planned_at']) ?></div></div>
          <div class="field"><label>Arrived At :</label><div class="box wide empty"><?= v($ts['arrived_at']) ?></div></div>
          <div class="field"><label>Departed At :</label><div class="box wide empty"><?= v($ts['departed_at']) ?></div></div>
        </div>
      </div>
      <div class="sidepanel">
        <h4>Notes :</h4>
        <div class="remarksbox"><?= v($ts['notes'], 'No notes recorded.') ?></div>
        <div class="scenic"><div class="sun"></div><div class="box1"></div><div class="box2"></div><div class="box3"></div></div>
      </div>
    </div>
    <?php endif; ?>
    <?php
} else {
    $total = $c->query("SELECT COUNT(*) c FROM trip_stops")->fetch_assoc()['c'];
    $rows = $c->query("SELECT ts.id, t.trip_no, ts.sequence, ts.stop_type, ts.address, ts.city, ts.contact_name, ts.planned_at
                        FROM trip_stops ts LEFT JOIN trips t ON t.id = ts.trip_id ORDER BY t.trip_no, ts.sequence");

    shell_start('Transportation', 'Trip Stop [Transport] — List');
    ?>
    <div class="tabs">
      <div class="tab active">All Trip Stops</div>
      <div class="spacer"></div>
      <div class="recordnav"><?= (int)$total ?> total records</div>
    </div>
    <div class="gridwrap">
      <table class="grid">
        <thead><tr><th>Trip No.</th><th>#</th><th>Type</th><th>City</th><th>Contact</th><th>Planned At</th></tr></thead>
        <tbody>
        <?php while ($r = $rows->fetch_assoc()): ?>
          <tr onclick="location.href='?id=<?= (int)$r['id'] ?>'">
            <td><?= v($r['trip_no']) ?></td>
            <td><?= v($r['sequence']) ?></td>
            <td><span class="pill <?= $r['stop_type']=='pickup'?'blue':'green' ?>"><?= v(ucfirst($r['stop_type'])) ?></span></td>
            <td><?= v($r['city']) ?></td>
            <td><?= v($r['contact_name']) ?></td>
            <td><?= v($r['planned_at']) ?></td>
          </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
    </div>
    <?php
}
shell_end();
