<?php
require __DIR__ . '/inc/db.php';
require __DIR__ . '/inc/shell.php';
$c = db();

$id     = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$search = isset($_GET['q']) ? trim($_GET['q']) : '';

if ($search !== '' && $id === 0) {
    $stmt = $c->prepare("SELECT a.id FROM attendance a LEFT JOIN users u ON u.id=a.user_id WHERE u.name LIKE CONCAT('%',?,'%') ORDER BY a.id DESC LIMIT 1");
    $stmt->bind_param('s', $search);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    if ($res) $id = (int) $res['id'];
    $stmt->close();
}

shell_search('Employee Name', $search);

if ($id > 0) {
    $stmt = $c->prepare("SELECT a.*, u.name AS employee_name FROM attendance a LEFT JOIN users u ON u.id = a.user_id WHERE a.id = ? LIMIT 1");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $at = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $prevRow = $c->query("SELECT id FROM attendance WHERE id < " . (int)$id . " ORDER BY id DESC LIMIT 1")->fetch_assoc();
    $nextRow = $c->query("SELECT id FROM attendance WHERE id > " . (int)$id . " ORDER BY id ASC LIMIT 1")->fetch_assoc();
    $prevId  = $prevRow['id'] ?? null;
    $nextId  = $nextRow['id'] ?? null;
    $totalCount = $c->query("SELECT COUNT(*) c FROM attendance")->fetch_assoc()['c'] ?? 0;

    shell_start('General Masters', 'Attendance [HRMS]');
    ?>
    <div class="tabs">
      <div class="tab active">Attendance Details</div>
      <div class="spacer"></div>
      <div class="recordnav">
        <a href="attendance.php">&#9776; List</a> &middot;
        Record <?= $at ? $id : 0 ?> &middot; <?= (int)$totalCount ?> total
        <a class="<?= $prevId ? '' : 'disabled' ?>" href="?id=<?= (int)$prevId ?>">&#9664; Prev</a>
        <a class="<?= $nextId ? '' : 'disabled' ?>" href="?id=<?= (int)$nextId ?>">Next &#9654;</a>
      </div>
    </div>
    <?php if (!$at): ?>
      <div class="notfound">Attendance record not found.</div>
    <?php else: ?>
    <div class="content">
      <div class="formwrap">
        <div class="row">
          <div class="field"><label>Employee :</label><div class="box wide empty"><?= v($at['employee_name']) ?></div></div>
          <div class="field"><label>Date :</label><div class="box"><?= v(d($at['attendance_date'])) ?></div></div>
          <div class="field" style="margin-left:auto;"><label>Status :</label><div class="box"><?= v($at['status']) ?></div></div>
        </div>
        <div class="row">
          <div class="field"><label>Punch In :</label><div class="box wide empty"><?= v($at['punch_in_at']) ?></div></div>
          <div class="field"><label>Punch In Source :</label><div class="box"><?= v($at['punch_in_source']) ?></div></div>
        </div>
        <div class="row">
          <div class="field"><label>Punch Out :</label><div class="box wide empty"><?= v($at['punch_out_at']) ?></div></div>
          <div class="field"><label>Hours Worked :</label><div class="box narrow"><?= v($at['hours_worked'],'0.00') ?></div></div>
        </div>
      </div>
      <div class="sidepanel">
        <h4>Notes :</h4>
        <div class="remarksbox"><?= v($at['notes'], 'No notes recorded.') ?></div>
        <div class="scenic"><div class="sun"></div><div class="box1"></div><div class="box2"></div><div class="box3"></div></div>
      </div>
    </div>
    <?php endif; ?>
    <?php
} else {
    $page = max(1, (int)($_GET['p'] ?? 1));
    $per  = 25;
    $off  = ($page - 1) * $per;
    $total = $c->query("SELECT COUNT(*) c FROM attendance")->fetch_assoc()['c'];
    $rows = $c->query("SELECT a.id, u.name AS employee_name, a.attendance_date, a.punch_in_at, a.punch_out_at, a.hours_worked, a.status
                        FROM attendance a LEFT JOIN users u ON u.id = a.user_id
                        ORDER BY a.id DESC LIMIT $per OFFSET $off");

    shell_start('General Masters', 'Attendance [HRMS] — List');
    ?>
    <div class="tabs">
      <div class="tab active">All Attendance</div>
      <div class="spacer"></div>
      <div class="recordnav"><?= (int)$total ?> total records</div>
    </div>
    <div class="gridwrap">
      <table class="grid">
        <thead><tr><th>Employee</th><th>Date</th><th>Punch In</th><th>Punch Out</th><th>Hours</th><th>Status</th></tr></thead>
        <tbody>
        <?php while ($r = $rows->fetch_assoc()):
            $statusClass = match($r['status']) { 'Present' => 'green', 'Absent' => 'gray', 'HalfDay' => 'amber', 'Leave' => 'blue', default => 'gray' };
        ?>
          <tr onclick="location.href='?id=<?= (int)$r['id'] ?>'">
            <td><?= v($r['employee_name']) ?></td>
            <td><?= v(d($r['attendance_date'])) ?></td>
            <td><?= v($r['punch_in_at']) ?></td>
            <td><?= v($r['punch_out_at']) ?></td>
            <td><?= v($r['hours_worked'],'0.00') ?></td>
            <td><span class="pill <?= $statusClass ?>"><?= v($r['status']) ?></span></td>
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
