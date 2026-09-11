<?php
require __DIR__ . '/inc/db.php';
require __DIR__ . '/inc/shell.php';
$c = db();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id > 0) {
    $stmt = $c->prepare("SELECT * FROM documents WHERE id = ? LIMIT 1");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $doc = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $prevRow = $c->query("SELECT id FROM documents WHERE id < " . (int)$id . " ORDER BY id DESC LIMIT 1")->fetch_assoc();
    $nextRow = $c->query("SELECT id FROM documents WHERE id > " . (int)$id . " ORDER BY id ASC LIMIT 1")->fetch_assoc();
    $prevId  = $prevRow['id'] ?? null;
    $nextId  = $nextRow['id'] ?? null;
    $totalCount = $c->query("SELECT COUNT(*) c FROM documents")->fetch_assoc()['c'] ?? 0;

    shell_start('Transportation', 'Document [Attachment]');
    ?>
    <div class="tabs">
      <div class="tab active">Document Details</div>
      <div class="spacer"></div>
      <div class="recordnav">
        <a href="documents.php">&#9776; List</a> &middot;
        Record <?= $doc ? $id : 0 ?> &middot; <?= (int)$totalCount ?> total
        <a class="<?= $prevId ? '' : 'disabled' ?>" href="?id=<?= (int)$prevId ?>">&#9664; Prev</a>
        <a class="<?= $nextId ? '' : 'disabled' ?>" href="?id=<?= (int)$nextId ?>">Next &#9654;</a>
      </div>
    </div>
    <?php if (!$doc): ?>
      <div class="notfound">Document not found.</div>
    <?php else: ?>
    <div class="content">
      <div class="formwrap">
        <div class="row">
          <div class="field" style="width:100%;"><label>File Name :</label><div class="box xwide" style="min-width:340px;"><?= v($doc['original_file_name']) ?></div></div>
        </div>
        <div class="row">
          <div class="field"><label>Related Module :</label><div class="box"><?= v($doc['module_name']) ?></div></div>
          <div class="field"><label>Related ID :</label><div class="box empty"><?= v($doc['module_ref_id']) ?></div></div>
          <div class="field"><label>Document Type :</label><div class="box wide"><?= v($doc['document_type']) ?></div></div>
        </div>
        <div class="row">
          <div class="field"><label>Source :</label><div class="box"><?= v($doc['source_channel']) ?></div></div>
          <div class="field"><label>File Size :</label><div class="box empty"><?= $doc['file_size'] ? number_format($doc['file_size']/1024,1).' KB' : '—' ?></div></div>
          <div class="field"><label>MIME Type :</label><div class="box wide empty"><?= v($doc['mime_type']) ?></div></div>
        </div>
        <div class="row">
          <div class="field"><label>Verification Status :</label><div class="box"><?= v($doc['verification_status']) ?></div></div>
          <div class="field"><label>Uploaded At :</label><div class="box wide empty"><?= v($doc['created_at']) ?></div></div>
        </div>
      </div>
      <div class="sidepanel">
        <h4>Remarks :</h4>
        <div class="remarksbox"><?= v($doc['remarks'], 'No remarks recorded.') ?></div>
        <div class="scenic"><div class="sun"></div><div class="box1"></div><div class="box2"></div><div class="box3"></div></div>
      </div>
    </div>
    <?php endif; ?>
    <?php
} else {
    $total = $c->query("SELECT COUNT(*) c FROM documents")->fetch_assoc()['c'];
    $rows = $c->query("SELECT id, module_name, module_ref_id, document_type, original_file_name, source_channel, verification_status, created_at FROM documents ORDER BY id DESC");

    shell_start('Transportation', 'Document [Attachment] — List');
    ?>
    <div class="tabs">
      <div class="tab active">All Documents</div>
      <div class="spacer"></div>
      <div class="recordnav"><?= (int)$total ?> total records</div>
    </div>
    <div class="gridwrap">
      <table class="grid">
        <thead><tr><th>File Name</th><th>Module</th><th>Type</th><th>Source</th><th>Uploaded</th><th>Status</th></tr></thead>
        <tbody>
        <?php while ($r = $rows->fetch_assoc()):
            $statusClass = match($r['verification_status']) { 'Verified' => 'green', 'Rejected' => 'gray', default => 'amber' };
        ?>
          <tr onclick="location.href='?id=<?= (int)$r['id'] ?>'">
            <td><?= v($r['original_file_name']) ?></td>
            <td><?= v($r['module_name']) ?> #<?= v($r['module_ref_id']) ?></td>
            <td><?= v($r['document_type']) ?></td>
            <td><?= v($r['source_channel']) ?></td>
            <td><?= v($r['created_at']) ?></td>
            <td><span class="pill <?= $statusClass ?>"><?= v($r['verification_status']) ?></span></td>
          </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
    </div>
    <?php
}
shell_end();
