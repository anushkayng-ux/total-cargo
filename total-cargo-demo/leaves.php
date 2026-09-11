<?php
require __DIR__ . '/inc/db.php';
require __DIR__ . '/inc/shell.php';
$c = db();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id > 0) {
    $stmt = $c->prepare("SELECT lv.*, u.name AS employee_name, lt.name AS leave_type_name, ap.name AS approver_name
                          FROM leaves lv LEFT JOIN users u ON u.id=lv.user_id LEFT JOIN leave_types lt ON lt.id=lv.leave_type_id LEFT JOIN users ap ON ap.id=lv.approver_user_id
                          WHERE lv.id = ? LIMIT 1");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $lv = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $prevRow = $c->query("SELECT id FROM leaves WHERE id < " . (int)$id . " ORDER BY id DESC LIMIT 1")->fetch_assoc();
    $nextRow = $c->query("SELECT id FROM leaves WHERE id > " . (int)$id . " ORDER BY id ASC LIMIT 1")->fetch_assoc();
    $prevId  = $prevRow['id'] ?? null;
    $nextId  = $nextRow['id'] ?? null;
    $totalCount = $c->query("SELECT COUNT(*) c FROM leaves")->fetch_assoc()['c'] ?? 0;

    shell_start('General Masters', 'Leave Application [HRMS]');
    ?>
    <div class="tabs">
      <div class="tab active">Leave Details</div>
      <div class="spacer"></div>
      <div class="recordnav">
        <a href="leaves.php">&#9776; List</a> &middot;
        Record <?= $lv ? $id : 0 ?> &middot; <?= (int)$totalCount ?> total
        <a class="<?= $prevId ? '' : 'disabled' ?>" href="?id=<?= (int)$prevId ?>">&#9664; Prev</a>
        <a class="<?= $nextId ? '' : 'disabled' ?>" href="?id=<?= (int)$nextId ?>">Next &#9654;</a>
      </div>
    </div>
    <?php if (!$lv): ?>
      <div class="notfound">Leave application not found.</div>
    <?php else: ?>
    <div class="content">
      <div class="formwrap">
        <div class="row">
          <div class="field"><label>Employee :</label><div class="box wide empty"><?= v($lv['employee_name']) ?></div></div>
          <div class="field" style="margin-left:auto;"><label>Status :</label><div class="box"><?= v($lv['status']) ?></div></div>
        </div>
        <div class="row">
          <div class="field"><label>Leave Type :</label><div class="box wide"><?= v($lv['leave_type_name']) ?></div></div>
          <div class="field"><label>Half Day :</label><div class="box narrow"><?= $lv['is_half_day'] ? 'Yes' : 'No' ?></div></div>
        </div>
        <div class="row">
          <div class="field"><label>From :</label><div class="box"><?= v(d($lv['from_date'])) ?></div></div>
          <div class="field"><label>To :</label><div class="box"><?= v(d($lv['to_date'])) ?></div></div>
          <div class="field"><label>Days :</label><div class="box narrow"><?= v($lv['days']) ?></div></div>
        </div>
        <div class="row">
          <div class="field"><label>Approver :</label><div class="box wide empty"><?= v($lv['approver_name']) ?></div></div>
          <div class="field"><label>Applied At :</label><div class="box wide empty"><?= v($lv['applied_at']) ?></div></div>
        </div>
        <div class="row" style="align-items:flex-start;">
          <div class="field" style="width:100%;"><label style="white-space:nowrap;">Reason :</label>
            <div class="particulars"><?= v($lv['reason']) ?></div>
          </div>
        </div>
      </div>
      <div class="sidepanel">
        <h4>Approver Notes :</h4>
        <div class="remarksbox"><?= v($lv['approver_notes'], 'No notes recorded.') ?></div>
        <div class="scenic"><div class="sun"></div><div class="box1"></div><div class="box2"></div><div class="box3"></div></div>
      </div>
    </div>
    <?php endif; ?>
    <?php
} else {
    $total = $c->query("SELECT COUNT(*) c FROM leaves")->fetch_assoc()['c'];
    $rows = $c->query("SELECT lv.id, u.name AS employee_name, lt.name AS leave_type_name, lv.from_date, lv.to_date, lv.days, lv.status
                        FROM leaves lv LEFT JOIN users u ON u.id = lv.user_id LEFT JOIN leave_types lt ON lt.id = lv.leave_type_id
                        ORDER BY lv.id DESC");

    shell_start('General Masters', 'Leave Application [HRMS] — List');
    ?>
    <div class="tabs">
      <div class="tab active">All Leaves</div>
      <div class="spacer"></div>
      <div class="recordnav"><?= (int)$total ?> total records</div>
    </div>
    <div class="gridwrap">
      <table class="grid">
        <thead><tr><th>Employee</th><th>Leave Type</th><th>From</th><th>To</th><th>Days</th><th>Status</th></tr></thead>
        <tbody>
        <?php while ($r = $rows->fetch_assoc()):
            $statusClass = match($r['status']) { 'Approved' => 'green', 'Rejected','Cancelled' => 'gray', default => 'amber' };
        ?>
          <tr onclick="location.href='?id=<?= (int)$r['id'] ?>'">
            <td><?= v($r['employee_name']) ?></td>
            <td><?= v($r['leave_type_name']) ?></td>
            <td><?= v(d($r['from_date'])) ?></td>
            <td><?= v(d($r['to_date'])) ?></td>
            <td><?= v($r['days']) ?></td>
            <td><span class="pill <?= $statusClass ?>"><?= v($r['status']) ?></span></td>
          </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
    </div>
    <?php
}
shell_end();
