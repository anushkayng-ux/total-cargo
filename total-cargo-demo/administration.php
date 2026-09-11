<?php
require __DIR__ . '/inc/db.php';
require __DIR__ . '/inc/shell.php';
$c = db();

$stats = [
    'tickets' => $c->query("SELECT COUNT(*) n FROM support_tickets")->fetch_assoc()['n'],
    'open'    => $c->query("SELECT COUNT(*) n FROM support_tickets WHERE status IN ('Open','In Progress')")->fetch_assoc()['n'],
    'portal'  => $c->query("SELECT COUNT(*) n FROM client_users")->fetch_assoc()['n'],
];

shell_start('Administration', 'Administration [Menu]', 'Total Cargo Express — Administration (Demo UI)');
?>
<div class="tabs">
  <div class="tab active">Administration</div>
  <div class="spacer"></div>
</div>
<div class="content">
  <div class="formwrap">
    <div class="kpirow">
      <div class="kpi"><div class="num"><?= (int)$stats['tickets'] ?></div><div class="lbl">Support Tickets</div></div>
      <div class="kpi"><div class="num"><?= (int)$stats['open'] ?></div><div class="lbl">Open / In Progress</div></div>
      <div class="kpi"><div class="num"><?= (int)$stats['portal'] ?></div><div class="lbl">Client Portal Users</div></div>
    </div>

    <div class="row"><div class="field"><label style="font-size:13.5px;">Select a module to open :</label></div></div>
    <div class="masterlinks">
      <a class="mastercard" href="supporttickets.php">
        <div class="ic">🎫</div>
        <div class="t">Support Tickets</div>
        <div class="s">Internal help desk — <?= (int)$stats['tickets'] ?> records</div>
      </a>
      <a class="mastercard" href="clientusers.php">
        <div class="ic">👤</div>
        <div class="t">Client Portal Users</div>
        <div class="s">Client-side logins — <?= (int)$stats['portal'] ?> records</div>
      </a>
    </div>
  </div>
</div>
<?php shell_end(); ?>
