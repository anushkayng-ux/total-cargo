<?php
require __DIR__ . '/inc/db.php';
require __DIR__ . '/inc/shell.php';
$c = db();

$id     = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$search = isset($_GET['q']) ? trim($_GET['q']) : '';

if ($search !== '' && $id === 0) {
    $stmt = $c->prepare("SELECT id FROM whatsapp_logs WHERE recipient_no LIKE CONCAT('%',?,'%') ORDER BY id DESC LIMIT 1");
    $stmt->bind_param('s', $search);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    if ($res) $id = (int) $res['id'];
    $stmt->close();
}

shell_search('Recipient No.', $search);

if ($id > 0) {
    $stmt = $c->prepare("SELECT * FROM whatsapp_logs WHERE id = ? LIMIT 1");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $w = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $prevRow = $c->query("SELECT id FROM whatsapp_logs WHERE id < " . (int)$id . " ORDER BY id DESC LIMIT 1")->fetch_assoc();
    $nextRow = $c->query("SELECT id FROM whatsapp_logs WHERE id > " . (int)$id . " ORDER BY id ASC LIMIT 1")->fetch_assoc();
    $prevId  = $prevRow['id'] ?? null;
    $nextId  = $nextRow['id'] ?? null;
    $totalCount = $c->query("SELECT COUNT(*) c FROM whatsapp_logs")->fetch_assoc()['c'] ?? 0;

    shell_start('Transportation', 'WhatsApp Message Log [Transport]');
    ?>
    <div class="tabs">
      <div class="tab active">Message Details</div>
      <div class="spacer"></div>
      <div class="recordnav">
        <a href="whatsapp.php">&#9776; List</a> &middot;
        Record <?= $w ? $id : 0 ?> &middot; <?= (int)$totalCount ?> total
        <a class="<?= $prevId ? '' : 'disabled' ?>" href="?id=<?= (int)$prevId ?>">&#9664; Prev</a>
        <a class="<?= $nextId ? '' : 'disabled' ?>" href="?id=<?= (int)$nextId ?>">Next &#9654;</a>
      </div>
    </div>
    <?php if (!$w): ?>
      <div class="notfound">Message not found.</div>
    <?php else: ?>
    <div class="content">
      <div class="formwrap">
        <div class="row">
          <div class="field"><label>Recipient No. :</label><div class="box wide"><?= v($w['recipient_no']) ?></div></div>
          <div class="field" style="margin-left:auto;"><label>Status :</label><div class="box"><?= v($w['delivery_status']) ?></div></div>
        </div>
        <div class="row">
          <div class="field"><label>Related Module :</label><div class="box"><?= v($w['module_name']) ?></div></div>
          <div class="field"><label>Related Record ID :</label><div class="box empty"><?= v($w['module_ref_id']) ?></div></div>
        </div>
        <div class="row">
          <div class="field"><label>Template :</label><div class="box wide empty"><?= v($w['template_key']) ?></div></div>
          <div class="field"><label>Message Type :</label><div class="box"><?= v($w['message_type']) ?></div></div>
        </div>
        <div class="row">
          <div class="field"><label>Sent At :</label><div class="box wide empty"><?= v($w['sent_at']) ?></div></div>
          <div class="field"><label>Delivered At :</label><div class="box wide empty"><?= v($w['delivered_at']) ?></div></div>
        </div>
        <div class="row">
          <div class="field"><label>Read At :</label><div class="box wide empty"><?= v($w['read_at']) ?></div></div>
          <div class="field"><label>Retry Count :</label><div class="box narrow"><?= v($w['retry_count'],'0') ?></div></div>
        </div>
      </div>
      <div class="sidepanel">
        <h4>Error / Notes :</h4>
        <div class="remarksbox"><?= v($w['error_message'], 'No errors — delivered cleanly.') ?></div>
        <div class="scenic"><div class="sun"></div><div class="box1"></div><div class="box2"></div><div class="box3"></div></div>
      </div>
    </div>
    <?php endif; ?>
    <?php
} else {
    $page = max(1, (int)($_GET['p'] ?? 1));
    $per  = 25;
    $off  = ($page - 1) * $per;
    $total = $c->query("SELECT COUNT(*) c FROM whatsapp_logs")->fetch_assoc()['c'];
    $rows = $c->query("SELECT id, module_name, recipient_no, template_key, delivery_status, sent_at FROM whatsapp_logs ORDER BY id DESC LIMIT $per OFFSET $off");

    shell_start('Transportation', 'WhatsApp Message Log [Transport] — List');
    ?>
    <div class="tabs">
      <div class="tab active">All Messages</div>
      <div class="spacer"></div>
      <div class="recordnav"><?= (int)$total ?> total records</div>
    </div>
    <div class="gridwrap">
      <table class="grid">
        <thead><tr><th>Module</th><th>Recipient No.</th><th>Template</th><th>Sent At</th><th>Status</th></tr></thead>
        <tbody>
        <?php while ($r = $rows->fetch_assoc()):
            $statusClass = match($r['delivery_status']) { 'Sent','Delivered' => 'green', 'Failed' => 'gray', 'Queued' => 'amber', default => 'blue' };
        ?>
          <tr onclick="location.href='?id=<?= (int)$r['id'] ?>'">
            <td><?= v($r['module_name']) ?></td>
            <td><?= v($r['recipient_no']) ?></td>
            <td><?= v($r['template_key']) ?></td>
            <td><?= v($r['sent_at']) ?></td>
            <td><span class="pill <?= $statusClass ?>"><?= v($r['delivery_status']) ?></span></td>
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
