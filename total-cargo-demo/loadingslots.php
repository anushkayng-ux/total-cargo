<?php
require __DIR__ . '/inc/db.php';
require __DIR__ . '/inc/shell.php';
$c = db();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id > 0) {
    $stmt = $c->prepare("SELECT ls.*, b.booking_no, b.lr_no FROM loading_slots ls LEFT JOIN bookings b ON b.id = ls.booking_id WHERE ls.id = ? LIMIT 1");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $ls = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $prevRow = $c->query("SELECT id FROM loading_slots WHERE id < " . (int)$id . " ORDER BY id DESC LIMIT 1")->fetch_assoc();
    $nextRow = $c->query("SELECT id FROM loading_slots WHERE id > " . (int)$id . " ORDER BY id ASC LIMIT 1")->fetch_assoc();
    $prevId  = $prevRow['id'] ?? null;
    $nextId  = $nextRow['id'] ?? null;
    $totalCount = $c->query("SELECT COUNT(*) c FROM loading_slots")->fetch_assoc()['c'] ?? 0;

    shell_start('Transportation', 'Loading Slot [Transport]');
    ?>
    <div class="tabs">
      <div class="tab active">Slot Details</div>
      <div class="spacer"></div>
      <div class="recordnav">
        <a href="loadingslots.php">&#9776; List</a> &middot;
        Record <?= $ls ? $id : 0 ?> &middot; <?= (int)$totalCount ?> total
        <a class="<?= $prevId ? '' : 'disabled' ?>" href="?id=<?= (int)$prevId ?>">&#9664; Prev</a>
        <a class="<?= $nextId ? '' : 'disabled' ?>" href="?id=<?= (int)$nextId ?>">Next &#9654;</a>
      </div>
    </div>
    <?php if (!$ls): ?>
      <div class="notfound">Loading slot not found.</div>
    <?php else: ?>
    <div class="content">
      <div class="formwrap">
        <div class="row">
          <div class="field"><label>GR/LR No. :</label><div class="box"><?= v($ls['lr_no'] ?: $ls['booking_no']) ?></div></div>
          <div class="field" style="margin-left:auto;"><label>Status :</label><div class="box"><?= v($ls['status']) ?></div></div>
        </div>
        <div class="row">
          <div class="field" style="width:100%;"><label>Plant Name :</label><div class="box xwide" style="min-width:340px;"><?= v($ls['plant_name']) ?></div></div>
        </div>
        <div class="row">
          <div class="field"><label>Slot Date :</label><div class="box"><?= v(d($ls['slot_date'])) ?></div></div>
          <div class="field"><label>Window :</label><div class="box wide"><?= v($ls['slot_window_start']) ?> – <?= v($ls['slot_window_end']) ?></div></div>
        </div>
        <div class="row">
          <div class="field"><label>Gate Pass No. :</label><div class="box empty"><?= v($ls['gate_pass_no']) ?></div></div>
          <div class="field"><label>Requested At :</label><div class="box wide empty"><?= v($ls['requested_at']) ?></div></div>
          <div class="field"><label>Confirmed At :</label><div class="box wide empty"><?= v($ls['confirmed_at']) ?></div></div>
        </div>
      </div>
      <div class="sidepanel">
        <h4>Notes :</h4>
        <div class="remarksbox"><?= v($ls['notes'], 'No notes recorded.') ?></div>
        <div class="scenic"><div class="sun"></div><div class="box1"></div><div class="box2"></div><div class="box3"></div></div>
      </div>
    </div>
    <?php endif; ?>
    <?php
} else {
    $total = $c->query("SELECT COUNT(*) c FROM loading_slots")->fetch_assoc()['c'];
    $rows = $c->query("SELECT ls.id, b.lr_no, b.booking_no, ls.plant_name, ls.slot_date, ls.slot_window_start, ls.slot_window_end, ls.status
                        FROM loading_slots ls LEFT JOIN bookings b ON b.id = ls.booking_id ORDER BY ls.id DESC");

    shell_start('Transportation', 'Loading Slot [Transport] — List');
    ?>
    <div class="tabs">
      <div class="tab active">All Loading Slots</div>
      <div class="spacer"></div>
      <div class="recordnav"><?= (int)$total ?> total records</div>
    </div>
    <div class="gridwrap">
      <table class="grid">
        <thead><tr><th>GR/LR No.</th><th>Plant</th><th>Slot Date</th><th>Window</th><th>Status</th></tr></thead>
        <tbody>
        <?php while ($r = $rows->fetch_assoc()):
            $statusClass = match($r['status']) { 'Confirmed','Used' => 'green', 'Cancelled','Missed' => 'gray', default => 'amber' };
        ?>
          <tr onclick="location.href='?id=<?= (int)$r['id'] ?>'">
            <td><?= v($r['lr_no'] ?: $r['booking_no']) ?></td>
            <td><?= v($r['plant_name']) ?></td>
            <td><?= v(d($r['slot_date'])) ?></td>
            <td><?= v($r['slot_window_start']) ?> – <?= v($r['slot_window_end']) ?></td>
            <td><span class="pill <?= $statusClass ?>"><?= v($r['status']) ?></span></td>
          </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
    </div>
    <?php
}
shell_end();
