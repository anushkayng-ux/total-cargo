<?php
require __DIR__ . '/inc/db.php';
require __DIR__ . '/inc/shell.php';
$c = db();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id > 0) {
    $stmt = $c->prepare("SELECT e.*, t.trip_no FROM epod_signatures e LEFT JOIN trips t ON t.id = e.trip_id WHERE e.id = ? LIMIT 1");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $e = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $prevRow = $c->query("SELECT id FROM epod_signatures WHERE id < " . (int)$id . " ORDER BY id DESC LIMIT 1")->fetch_assoc();
    $nextRow = $c->query("SELECT id FROM epod_signatures WHERE id > " . (int)$id . " ORDER BY id ASC LIMIT 1")->fetch_assoc();
    $prevId  = $prevRow['id'] ?? null;
    $nextId  = $nextRow['id'] ?? null;
    $totalCount = $c->query("SELECT COUNT(*) c FROM epod_signatures")->fetch_assoc()['c'] ?? 0;

    shell_start('Transportation', 'E-POD Signature [Transport]');
    ?>
    <div class="tabs">
      <div class="tab active">Signature Details</div>
      <div class="spacer"></div>
      <div class="recordnav">
        <a href="epod.php">&#9776; List</a> &middot;
        Record <?= $e ? $id : 0 ?> &middot; <?= (int)$totalCount ?> total
        <a class="<?= $prevId ? '' : 'disabled' ?>" href="?id=<?= (int)$prevId ?>">&#9664; Prev</a>
        <a class="<?= $nextId ? '' : 'disabled' ?>" href="?id=<?= (int)$nextId ?>">Next &#9654;</a>
      </div>
    </div>
    <?php if (!$e): ?>
      <div class="notfound">E-POD signature not found.</div>
    <?php else: ?>
    <div class="content">
      <div class="formwrap">
        <div class="row">
          <div class="field"><label>Trip No. :</label><div class="box"><?= v($e['trip_no']) ?></div></div>
          <div class="field" style="margin-left:auto;"><label>Signed At :</label><div class="box wide"><?= v($e['signed_at']) ?></div></div>
        </div>
        <div class="row">
          <div class="field"><label>Consignee :</label><div class="box wide"><?= v($e['consignee_name']) ?></div></div>
          <div class="field"><label>Mobile :</label><div class="box empty"><?= v($e['consignee_mobile']) ?></div></div>
        </div>
        <div class="row">
          <div class="field"><label>Damage Noted :</label><div class="box narrow"><?= $e['damage_noted'] ? 'Yes' : 'No' ?></div></div>
          <div class="field"><label>Shortage Noted :</label><div class="box narrow"><?= $e['shortage_noted'] ? 'Yes' : 'No' ?></div></div>
        </div>
        <div class="row" style="align-items:flex-start;">
          <div class="field" style="width:100%;"><label style="white-space:nowrap;">Remarks :</label>
            <div class="particulars"><?= v($e['remarks'], 'No remarks recorded.') ?></div>
          </div>
        </div>
        <div class="row" style="align-items:flex-start;">
          <div class="field" style="width:100%;">
            <label style="white-space:nowrap;">Signature :</label>
            <div class="box" style="background:#fff;padding:10px;min-width:240px;">
              <img src="<?= htmlspecialchars($e['signature_data'], ENT_QUOTES) ?>" alt="Signature" style="max-width:220px;display:block;">
            </div>
          </div>
        </div>
      </div>
      <div class="sidepanel">
        <h4>Capture Info :</h4>
        <div class="remarksbox">IP: <?= v($e['ip_address']) ?><br>Geo: <?= v($e['geo_lat']) ?>, <?= v($e['geo_lng']) ?></div>
        <div class="scenic"><div class="sun"></div><div class="box1"></div><div class="box2"></div><div class="box3"></div></div>
      </div>
    </div>
    <?php endif; ?>
    <?php
} else {
    $total = $c->query("SELECT COUNT(*) c FROM epod_signatures")->fetch_assoc()['c'];
    $rows = $c->query("SELECT e.id, t.trip_no, e.consignee_name, e.signed_at, e.damage_noted, e.shortage_noted
                        FROM epod_signatures e LEFT JOIN trips t ON t.id = e.trip_id ORDER BY e.id DESC");

    shell_start('Transportation', 'E-POD Signature [Transport] — List');
    ?>
    <div class="tabs">
      <div class="tab active">All Signatures</div>
      <div class="spacer"></div>
      <div class="recordnav"><?= (int)$total ?> total records</div>
    </div>
    <div class="gridwrap">
      <table class="grid">
        <thead><tr><th>Trip No.</th><th>Consignee</th><th>Signed At</th><th>Damage</th><th>Shortage</th></tr></thead>
        <tbody>
        <?php while ($r = $rows->fetch_assoc()): ?>
          <tr onclick="location.href='?id=<?= (int)$r['id'] ?>'">
            <td><?= v($r['trip_no']) ?></td>
            <td><?= v($r['consignee_name']) ?></td>
            <td><?= v($r['signed_at']) ?></td>
            <td><?= $r['damage_noted'] ? '<span class="pill amber">Yes</span>' : '—' ?></td>
            <td><?= $r['shortage_noted'] ? '<span class="pill amber">Yes</span>' : '—' ?></td>
          </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
    </div>
    <?php
}
shell_end();
