<?php
require __DIR__ . '/inc/db.php';
require __DIR__ . '/inc/shell.php';
$c = db();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id > 0) {
    $stmt = $c->prepare("SELECT t.*, u.name AS raised_by, a.name AS assigned_name FROM support_tickets t LEFT JOIN users u ON u.id=t.user_id LEFT JOIN users a ON a.id=t.assigned_to WHERE t.id = ? LIMIT 1");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $tk = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $prevRow = $c->query("SELECT id FROM support_tickets WHERE id < " . (int)$id . " ORDER BY id DESC LIMIT 1")->fetch_assoc();
    $nextRow = $c->query("SELECT id FROM support_tickets WHERE id > " . (int)$id . " ORDER BY id ASC LIMIT 1")->fetch_assoc();
    $prevId  = $prevRow['id'] ?? null;
    $nextId  = $nextRow['id'] ?? null;
    $totalCount = $c->query("SELECT COUNT(*) c FROM support_tickets")->fetch_assoc()['c'] ?? 0;

    shell_start('Administration', 'Support Ticket [Help Desk]');
    ?>
    <div class="tabs">
      <div class="tab active">Ticket Details</div>
      <div class="tab">Resolution</div>
      <div class="spacer"></div>
      <div class="recordnav">
        <a href="supporttickets.php">&#9776; List</a> &middot;
        Record <?= $tk ? $id : 0 ?> &middot; <?= (int)$totalCount ?> total
        <a class="<?= $prevId ? '' : 'disabled' ?>" href="?id=<?= (int)$prevId ?>">&#9664; Prev</a>
        <a class="<?= $nextId ? '' : 'disabled' ?>" href="?id=<?= (int)$nextId ?>">Next &#9654;</a>
      </div>
    </div>
    <?php if (!$tk): ?>
      <div class="notfound">Ticket not found.</div>
    <?php else: ?>
    <div class="content">
      <div class="formwrap">
        <div class="row">
          <div class="field"><label>Ticket No. :</label><div class="box"><?= v($tk['ticket_no']) ?></div></div>
          <div class="field"><label>Type :</label><div class="box"><?= v($tk['type']) ?></div></div>
          <div class="field" style="margin-left:auto;"><label>Status :</label><div class="box"><?= v($tk['status']) ?></div></div>
        </div>
        <div class="row">
          <div class="field" style="width:100%;"><label>Subject :</label><div class="box xwide" style="min-width:340px;"><?= v($tk['subject']) ?></div></div>
        </div>
        <div class="row">
          <div class="field"><label>Raised By :</label><div class="box wide empty"><?= v($tk['raised_by']) ?></div></div>
          <div class="field"><label>Priority :</label><div class="box"><?= v($tk['priority']) ?></div></div>
          <div class="field"><label>Assigned To :</label><div class="box wide empty"><?= v($tk['assigned_name']) ?></div></div>
        </div>
        <div class="row" style="align-items:flex-start;">
          <div class="field" style="width:100%;"><label style="white-space:nowrap;">Description :</label>
            <div class="particulars"><?= v($tk['body']) ?></div>
          </div>
        </div>
        <div class="row" style="align-items:flex-start;">
          <div class="field" style="width:100%;"><label style="white-space:nowrap;">Resolution :</label>
            <div class="particulars"><?= v($tk['resolution'], 'Not resolved yet.') ?></div>
          </div>
        </div>
      </div>
      <div class="sidepanel">
        <h4>Timeline :</h4>
        <div class="remarksbox">Created <?= v($tk['created_at']) ?><br>Updated <?= v($tk['updated_at']) ?></div>
        <div class="scenic"><div class="sun"></div><div class="box1"></div><div class="box2"></div><div class="box3"></div></div>
      </div>
    </div>
    <?php endif; ?>
    <?php
} else {
    $total = $c->query("SELECT COUNT(*) c FROM support_tickets")->fetch_assoc()['c'];
    $rows = $c->query("SELECT t.id, t.ticket_no, u.name AS raised_by, t.subject, t.type, t.priority, t.status
                        FROM support_tickets t LEFT JOIN users u ON u.id = t.user_id ORDER BY t.id DESC");

    shell_start('Administration', 'Support Ticket [Help Desk] — List');
    ?>
    <div class="tabs">
      <div class="tab active">All Tickets</div>
      <div class="spacer"></div>
      <div class="recordnav"><?= (int)$total ?> total records</div>
    </div>
    <div class="gridwrap">
      <table class="grid">
        <thead><tr><th>Ticket No.</th><th>Raised By</th><th>Subject</th><th>Type</th><th>Priority</th><th>Status</th></tr></thead>
        <tbody>
        <?php while ($r = $rows->fetch_assoc()):
            $statusClass = match($r['status']) { 'Resolved','Closed' => 'green', 'Open' => 'blue', 'In Progress' => 'amber', default => 'gray' };
        ?>
          <tr onclick="location.href='?id=<?= (int)$r['id'] ?>'">
            <td><?= v($r['ticket_no']) ?></td>
            <td><?= v($r['raised_by']) ?></td>
            <td><?= v($r['subject']) ?></td>
            <td><?= v($r['type']) ?></td>
            <td><?= v($r['priority']) ?></td>
            <td><span class="pill <?= $statusClass ?>"><?= v($r['status']) ?></span></td>
          </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
    </div>
    <?php
}
shell_end();
