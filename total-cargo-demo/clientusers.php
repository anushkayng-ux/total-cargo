<?php
require __DIR__ . '/inc/db.php';
require __DIR__ . '/inc/shell.php';
$c = db();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id > 0) {
    $stmt = $c->prepare("SELECT cu.*, cl.company_name FROM client_users cu LEFT JOIN clients cl ON cl.id = cu.client_id WHERE cu.id = ? LIMIT 1");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $cu = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $prevRow = $c->query("SELECT id FROM client_users WHERE id < " . (int)$id . " ORDER BY id DESC LIMIT 1")->fetch_assoc();
    $nextRow = $c->query("SELECT id FROM client_users WHERE id > " . (int)$id . " ORDER BY id ASC LIMIT 1")->fetch_assoc();
    $prevId  = $prevRow['id'] ?? null;
    $nextId  = $nextRow['id'] ?? null;
    $totalCount = $c->query("SELECT COUNT(*) c FROM client_users")->fetch_assoc()['c'] ?? 0;

    shell_start('Administration', 'Client Portal User [Administration]');
    ?>
    <div class="tabs">
      <div class="tab active">User Details</div>
      <div class="spacer"></div>
      <div class="recordnav">
        <a href="clientusers.php">&#9776; List</a> &middot;
        Record <?= $cu ? $id : 0 ?> &middot; <?= (int)$totalCount ?> total
        <a class="<?= $prevId ? '' : 'disabled' ?>" href="?id=<?= (int)$prevId ?>">&#9664; Prev</a>
        <a class="<?= $nextId ? '' : 'disabled' ?>" href="?id=<?= (int)$nextId ?>">Next &#9654;</a>
      </div>
    </div>
    <?php if (!$cu): ?>
      <div class="notfound">Portal user not found.</div>
    <?php else: ?>
    <div class="content">
      <div class="formwrap">
        <div class="row">
          <div class="field" style="width:100%;"><label>Name :</label><div class="box xwide" style="min-width:340px;"><?= v($cu['name']) ?></div></div>
        </div>
        <div class="row">
          <div class="field"><label>Client :</label><div class="box wide"><?= v($cu['company_name']) ?></div></div>
          <div class="field"><label>Portal Role :</label><div class="box"><?= v($cu['portal_role']) ?></div></div>
        </div>
        <div class="row">
          <div class="field"><label>Email :</label><div class="box wide"><?= v($cu['email']) ?></div></div>
          <div class="field"><label>Mobile :</label><div class="box empty"><?= v($cu['mobile']) ?></div></div>
        </div>
        <div class="row">
          <div class="field"><label>Status :</label><div class="box narrow"><?= $cu['status'] ? 'Active' : 'Inactive' ?></div></div>
          <div class="field"><label>Must Change Password :</label><div class="box narrow"><?= $cu['must_change_password'] ? 'Yes' : 'No' ?></div></div>
          <div class="field"><label>Last Login :</label><div class="box wide empty"><?= v($cu['last_login_at']) ?></div></div>
        </div>
        <div class="row">
          <div class="field"><label>Notify WhatsApp :</label><div class="box narrow"><?= $cu['notify_whatsapp'] ? 'Yes' : 'No' ?></div></div>
          <div class="field"><label>Notify Email :</label><div class="box narrow"><?= $cu['notify_email'] ? 'Yes' : 'No' ?></div></div>
        </div>
      </div>
      <div class="sidepanel">
        <h4>Account Notes :</h4>
        <div class="remarksbox">Created <?= v($cu['created_at']) ?></div>
        <div class="scenic"><div class="sun"></div><div class="box1"></div><div class="box2"></div><div class="box3"></div></div>
      </div>
    </div>
    <?php endif; ?>
    <?php
} else {
    $total = $c->query("SELECT COUNT(*) c FROM client_users")->fetch_assoc()['c'];
    $rows = $c->query("SELECT cu.id, cu.name, cl.company_name, cu.email, cu.portal_role, cu.status
                        FROM client_users cu LEFT JOIN clients cl ON cl.id = cu.client_id ORDER BY cu.id DESC");

    shell_start('Administration', 'Client Portal User [Administration] — List');
    ?>
    <div class="tabs">
      <div class="tab active">All Portal Users</div>
      <div class="spacer"></div>
      <div class="recordnav"><?= (int)$total ?> total records</div>
    </div>
    <div class="gridwrap">
      <table class="grid">
        <thead><tr><th>Name</th><th>Client</th><th>Email</th><th>Role</th><th>Status</th></tr></thead>
        <tbody>
        <?php while ($r = $rows->fetch_assoc()): ?>
          <tr onclick="location.href='?id=<?= (int)$r['id'] ?>'">
            <td><?= v($r['name']) ?></td>
            <td><?= v($r['company_name']) ?></td>
            <td><?= v($r['email']) ?></td>
            <td><span class="pill blue"><?= v($r['portal_role']) ?></span></td>
            <td><span class="pill <?= $r['status'] ? 'green' : 'gray' ?>"><?= $r['status'] ? 'Active' : 'Inactive' ?></span></td>
          </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
    </div>
    <?php
}
shell_end();
