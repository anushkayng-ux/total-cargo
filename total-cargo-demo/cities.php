<?php
require __DIR__ . '/inc/db.php';
require __DIR__ . '/inc/shell.php';
$c = db();

$search = isset($_GET['q']) ? trim($_GET['q']) : '';
shell_search('City / State', $search);

$page = max(1, (int)($_GET['p'] ?? 1));
$per  = 30;
$off  = ($page - 1) * $per;

if ($search !== '') {
    $like = "%$search%";
    $stmt = $c->prepare("SELECT COUNT(*) c FROM cities WHERE name LIKE ? OR state LIKE ?");
    $stmt->bind_param('ss', $like, $like);
    $stmt->execute();
    $total = $stmt->get_result()->fetch_assoc()['c'];
    $stmt->close();

    $stmt = $c->prepare("SELECT id, name, state, region, tier, gst_state_code, status FROM cities WHERE name LIKE ? OR state LIKE ? ORDER BY name LIMIT $per OFFSET $off");
    $stmt->bind_param('ss', $like, $like);
    $stmt->execute();
    $rows = $stmt->get_result();
} else {
    $total = $c->query("SELECT COUNT(*) c FROM cities")->fetch_assoc()['c'];
    $rows = $c->query("SELECT id, name, state, region, tier, gst_state_code, status FROM cities ORDER BY name LIMIT $per OFFSET $off");
}

$leadSources = $c->query("SELECT * FROM lead_sources ORDER BY id");

shell_start('General Masters', 'Cities & Lead Sources [General Masters]');
?>
<div class="tabs">
  <div class="tab active">Cities</div>
  <div class="spacer"></div>
  <div class="recordnav"><?= (int)$total ?> total records</div>
</div>
<div class="content">
  <div class="gridwrap" style="flex:1;">
    <table class="grid">
      <thead><tr><th>City</th><th>State</th><th>Region</th><th>Tier</th><th>GST Code</th><th>Status</th></tr></thead>
      <tbody>
      <?php while ($r = $rows->fetch_assoc()): ?>
        <tr>
          <td><?= v($r['name']) ?></td>
          <td><?= v($r['state']) ?></td>
          <td><?= v($r['region']) ?></td>
          <td><?= $r['tier'] ? 'Tier ' . v($r['tier']) : '—' ?></td>
          <td><?= v($r['gst_state_code']) ?></td>
          <td><span class="pill <?= $r['status'] ? 'blue' : 'gray' ?>"><?= $r['status'] ? 'Active' : 'Inactive' ?></span></td>
        </tr>
      <?php endwhile; ?>
      </tbody>
    </table>
    <div class="pager">
      <?php $pages = max(1, (int)ceil($total / $per)); ?>
      <a class="<?= $page<=1?'disabled':'' ?>" href="?p=<?= $page-1 ?><?= $search? '&q='.urlencode($search):'' ?>">&laquo; Prev</a>
      <span class="cur">Page <?= $page ?> / <?= $pages ?></span>
      <a class="<?= $page>=$pages?'disabled':'' ?>" href="?p=<?= $page+1 ?><?= $search? '&q='.urlencode($search):'' ?>">Next &raquo;</a>
    </div>
  </div>
  <div class="sidepanel" style="width:280px;flex:0 0 280px;">
    <h4>Lead Sources :</h4>
    <table class="grid" style="min-width:0;">
      <thead><tr><th>Source</th><th>Status</th></tr></thead>
      <tbody>
      <?php while ($ls = $leadSources->fetch_assoc()): ?>
        <tr>
          <td><?= v($ls['source_name']) ?></td>
          <td><span class="pill <?= $ls['status'] ? 'blue' : 'gray' ?>"><?= $ls['status'] ? 'Active' : 'Inactive' ?></span></td>
        </tr>
      <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>
<?php shell_end(); ?>
