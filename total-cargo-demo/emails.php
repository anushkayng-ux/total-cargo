<?php
require __DIR__ . '/inc/db.php';
require __DIR__ . '/inc/shell.php';
$c = db();

$id     = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$search = isset($_GET['q']) ? trim($_GET['q']) : '';
$view   = $_GET['view'] ?? 'logs';

if ($search !== '' && $id === 0) {
    $stmt = $c->prepare("SELECT id FROM email_logs WHERE to_email LIKE CONCAT('%',?,'%') ORDER BY id DESC LIMIT 1");
    $stmt->bind_param('s', $search);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    if ($res) $id = (int) $res['id'];
    $stmt->close();
}

shell_search('Recipient Email', $search);

if ($id > 0) {
    $stmt = $c->prepare("SELECT * FROM email_logs WHERE id = ? LIMIT 1");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $e = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $prevRow = $c->query("SELECT id FROM email_logs WHERE id < " . (int)$id . " ORDER BY id DESC LIMIT 1")->fetch_assoc();
    $nextRow = $c->query("SELECT id FROM email_logs WHERE id > " . (int)$id . " ORDER BY id ASC LIMIT 1")->fetch_assoc();
    $prevId  = $prevRow['id'] ?? null;
    $nextId  = $nextRow['id'] ?? null;
    $totalCount = $c->query("SELECT COUNT(*) c FROM email_logs")->fetch_assoc()['c'] ?? 0;

    shell_start('Transportation', 'Email Log [Communication]');
    ?>
    <div class="tabs">
      <div class="tab active">Email Details</div>
      <div class="spacer"></div>
      <div class="recordnav">
        <a href="emails.php">&#9776; List</a> &middot;
        Record <?= $e ? $id : 0 ?> &middot; <?= (int)$totalCount ?> total
        <a class="<?= $prevId ? '' : 'disabled' ?>" href="?id=<?= (int)$prevId ?>">&#9664; Prev</a>
        <a class="<?= $nextId ? '' : 'disabled' ?>" href="?id=<?= (int)$nextId ?>">Next &#9654;</a>
      </div>
    </div>
    <?php if (!$e): ?>
      <div class="notfound">Email not found.</div>
    <?php else: ?>
    <div class="content">
      <div class="formwrap">
        <div class="row">
          <div class="field" style="width:100%;"><label>Subject :</label><div class="box xwide" style="min-width:400px;"><?= v($e['subject']) ?></div></div>
        </div>
        <div class="row">
          <div class="field"><label>To :</label><div class="box wide"><?= v($e['to_email']) ?></div></div>
          <div class="field" style="margin-left:auto;"><label>Status :</label><div class="box"><?= v($e['status']) ?></div></div>
        </div>
        <div class="row">
          <div class="field"><label>Related Module :</label><div class="box"><?= v($e['related_module']) ?></div></div>
          <div class="field"><label>Related ID :</label><div class="box empty"><?= v($e['related_id']) ?></div></div>
          <div class="field"><label>Template :</label><div class="box wide empty"><?= v($e['template_key']) ?></div></div>
        </div>
        <div class="row">
          <div class="field"><label>Sent At :</label><div class="box wide empty"><?= v($e['sent_at']) ?></div></div>
          <div class="field"><label>Delivered At :</label><div class="box wide empty"><?= v($e['delivered_at']) ?></div></div>
        </div>
        <div class="row">
          <div class="field"><label>Opens :</label><div class="box narrow"><?= v($e['open_count'],'0') ?></div></div>
          <div class="field"><label>Clicks :</label><div class="box narrow"><?= v($e['click_count'],'0') ?></div></div>
          <div class="field"><label>Attempts :</label><div class="box narrow"><?= v($e['attempts'],'0') ?></div></div>
        </div>
      </div>
      <div class="sidepanel">
        <h4>Error :</h4>
        <div class="remarksbox"><?= v($e['error'], 'No errors — sent cleanly.') ?></div>
        <div class="scenic"><div class="sun"></div><div class="box1"></div><div class="box2"></div><div class="box3"></div></div>
      </div>
    </div>
    <?php endif; ?>
    <?php
} elseif ($view === 'templates') {
    $total = $c->query("SELECT COUNT(*) c FROM email_templates")->fetch_assoc()['c'];
    $rows = $c->query("SELECT id, template_key, audience_type, subject, status FROM email_templates ORDER BY id");

    shell_start('Transportation', 'Email Templates [Communication]');
    ?>
    <div class="tabs">
      <div class="tab"><a href="emails.php" style="color:inherit;text-decoration:none;">Sent Log</a></div>
      <div class="tab active">Templates</div>
      <div class="spacer"></div>
      <div class="recordnav"><?= (int)$total ?> total records</div>
    </div>
    <div class="gridwrap">
      <table class="grid">
        <thead><tr><th>Template Key</th><th>Audience</th><th>Subject</th><th>Status</th></tr></thead>
        <tbody>
        <?php while ($r = $rows->fetch_assoc()): ?>
          <tr>
            <td><?= v($r['template_key']) ?></td>
            <td><span class="pill blue"><?= v($r['audience_type']) ?></span></td>
            <td><?= v($r['subject']) ?></td>
            <td><span class="pill <?= $r['status'] ? 'green' : 'gray' ?>"><?= $r['status'] ? 'Active' : 'Inactive' ?></span></td>
          </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
    </div>
    <?php
} else {
    $page = max(1, (int)($_GET['p'] ?? 1));
    $per  = 25;
    $off  = ($page - 1) * $per;
    $total = $c->query("SELECT COUNT(*) c FROM email_logs")->fetch_assoc()['c'];
    $rows = $c->query("SELECT id, to_email, subject, status, sent_at, related_module FROM email_logs ORDER BY id DESC LIMIT $per OFFSET $off");

    shell_start('Transportation', 'Email Log [Communication] — List');
    ?>
    <div class="tabs">
      <div class="tab active">Sent Log</div>
      <div class="tab"><a href="emails.php?view=templates" style="color:inherit;text-decoration:none;">Templates</a></div>
      <div class="spacer"></div>
      <div class="recordnav"><?= (int)$total ?> total records</div>
    </div>
    <div class="gridwrap">
      <table class="grid">
        <thead><tr><th>To</th><th>Subject</th><th>Related</th><th>Sent At</th><th>Status</th></tr></thead>
        <tbody>
        <?php while ($r = $rows->fetch_assoc()):
            $statusClass = match($r['status']) { 'Sent','Delivered','Opened','Clicked' => 'green', 'Failed','Bounced' => 'gray', 'Queued','Sending' => 'amber', default => 'blue' };
        ?>
          <tr onclick="location.href='?id=<?= (int)$r['id'] ?>'">
            <td><?= v($r['to_email']) ?></td>
            <td><?= v($r['subject']) ?></td>
            <td><?= v($r['related_module']) ?></td>
            <td><?= v($r['sent_at']) ?></td>
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
