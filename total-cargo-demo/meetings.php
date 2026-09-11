<?php
require __DIR__ . '/inc/db.php';
require __DIR__ . '/inc/shell.php';
$c = db();

$id     = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$search = isset($_GET['q']) ? trim($_GET['q']) : '';

if ($search !== '' && $id === 0) {
    $stmt = $c->prepare("SELECT id FROM meetings WHERE title LIKE CONCAT('%',?,'%') OR with_company LIKE CONCAT('%',?,'%') ORDER BY id DESC LIMIT 1");
    $stmt->bind_param('ss', $search, $search);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    if ($res) $id = (int) $res['id'];
    $stmt->close();
}

shell_search('Title / Company', $search);

if ($id > 0) {
    $stmt = $c->prepare("SELECT m.*, u.name AS scheduled_by FROM meetings m LEFT JOIN users u ON u.id = m.user_id WHERE m.id = ? LIMIT 1");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $mt = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $prevRow = $c->query("SELECT id FROM meetings WHERE id < " . (int)$id . " ORDER BY id DESC LIMIT 1")->fetch_assoc();
    $nextRow = $c->query("SELECT id FROM meetings WHERE id > " . (int)$id . " ORDER BY id ASC LIMIT 1")->fetch_assoc();
    $prevId  = $prevRow['id'] ?? null;
    $nextId  = $nextRow['id'] ?? null;
    $totalCount = $c->query("SELECT COUNT(*) c FROM meetings")->fetch_assoc()['c'] ?? 0;

    shell_start('Transportation', 'Meeting Log [Transport]');
    ?>
    <div class="tabs">
      <div class="tab active">Meeting Details</div>
      <div class="tab">Outcome / Next Steps</div>
      <div class="spacer"></div>
      <div class="recordnav">
        <a href="meetings.php">&#9776; List</a> &middot;
        Record <?= $mt ? $id : 0 ?> &middot; <?= (int)$totalCount ?> total
        <a class="<?= $prevId ? '' : 'disabled' ?>" href="?id=<?= (int)$prevId ?>">&#9664; Prev</a>
        <a class="<?= $nextId ? '' : 'disabled' ?>" href="?id=<?= (int)$nextId ?>">Next &#9654;</a>
      </div>
    </div>
    <?php if (!$mt): ?>
      <div class="notfound">Meeting not found.</div>
    <?php else: ?>
    <div class="content">
      <div class="formwrap">
        <div class="row">
          <div class="field" style="width:100%;"><label>Title :</label><div class="box xwide" style="min-width:340px;"><?= v($mt['title']) ?></div></div>
        </div>
        <div class="row">
          <div class="field"><label>Scheduled At :</label><div class="box wide"><?= v($mt['scheduled_at']) ?></div></div>
          <div class="field"><label>Status :</label><div class="box"><?= v($mt['status']) ?></div></div>
          <div class="field"><label>Scheduled By :</label><div class="box wide empty"><?= v($mt['scheduled_by']) ?></div></div>
        </div>
        <div class="row">
          <div class="field"><label>With Company :</label><div class="box wide empty"><?= v($mt['with_company']) ?></div></div>
          <div class="field"><label>Contact Name :</label><div class="box wide empty"><?= v($mt['with_contact_name']) ?></div></div>
        </div>
        <div class="row">
          <div class="field"><label>Contact Phone :</label><div class="box empty"><?= v($mt['with_contact_phone']) ?></div></div>
          <div class="field"><label>Location :</label><div class="box wide empty"><?= v($mt['location']) ?></div></div>
        </div>
        <div class="row" style="align-items:flex-start;">
          <div class="field" style="width:100%;"><label style="white-space:nowrap;">Outcome :</label>
            <div class="particulars"><?= v($mt['outcome'], 'Not recorded yet.') ?></div>
          </div>
        </div>
        <div class="row" style="align-items:flex-start;">
          <div class="field" style="width:100%;"><label style="white-space:nowrap;">Next Steps :</label>
            <div class="particulars"><?= v($mt['next_steps'], 'Not recorded yet.') ?></div>
          </div>
        </div>
      </div>
      <div class="sidepanel">
        <h4>Meeting Status :</h4>
        <div class="remarksbox"><?= v($mt['status']) ?> — created <?= v($mt['created_at']) ?></div>
        <div class="scenic"><div class="sun"></div><div class="box1"></div><div class="box2"></div><div class="box3"></div></div>
      </div>
    </div>
    <?php endif; ?>
    <?php
} else {
    $page = max(1, (int)($_GET['p'] ?? 1));
    $per  = 25;
    $off  = ($page - 1) * $per;
    $total = $c->query("SELECT COUNT(*) c FROM meetings")->fetch_assoc()['c'];
    $rows = $c->query("SELECT m.id, m.title, m.with_company, m.with_contact_name, m.scheduled_at, m.status, u.name AS scheduled_by
                        FROM meetings m LEFT JOIN users u ON u.id = m.user_id
                        ORDER BY m.id DESC LIMIT $per OFFSET $off");

    shell_start('Transportation', 'Meeting Log [Transport] — List');
    ?>
    <div class="tabs">
      <div class="tab active">All Meetings</div>
      <div class="spacer"></div>
      <div class="recordnav"><?= (int)$total ?> total records</div>
    </div>
    <div class="gridwrap">
      <table class="grid">
        <thead><tr><th>Title</th><th>With Company</th><th>Contact</th><th>Scheduled At</th><th>Scheduled By</th><th>Status</th></tr></thead>
        <tbody>
        <?php while ($r = $rows->fetch_assoc()):
            $statusClass = match($r['status']) { 'Completed' => 'green', 'Cancelled' => 'gray', 'InProgress' => 'amber', default => 'blue' };
        ?>
          <tr onclick="location.href='?id=<?= (int)$r['id'] ?>'">
            <td><?= v($r['title']) ?></td>
            <td><?= v($r['with_company']) ?></td>
            <td><?= v($r['with_contact_name']) ?></td>
            <td><?= v($r['scheduled_at']) ?></td>
            <td><?= v($r['scheduled_by']) ?></td>
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
