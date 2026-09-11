<?php
require __DIR__ . '/inc/db.php';
require __DIR__ . '/inc/shell.php';
$c = db();

$stats = [
    'clients'  => $c->query("SELECT COUNT(*) n FROM clients WHERE status=1")->fetch_assoc()['n'],
    'vehicles' => $c->query("SELECT COUNT(*) n FROM vehicles WHERE status=1")->fetch_assoc()['n'],
    'drivers'  => $c->query("SELECT COUNT(*) n FROM drivers WHERE status=1")->fetch_assoc()['n'],
    'vendors'  => $c->query("SELECT COUNT(*) n FROM vendors WHERE status=1")->fetch_assoc()['n'],
];

shell_start('General Masters', 'General Masters [Home]', 'Total Cargo Express — General Masters (Demo UI)');
?>
<div class="tabs">
  <div class="tab active">Master Records</div>
  <div class="spacer"></div>
</div>
<div class="content">
  <div class="formwrap">
    <div class="kpirow">
      <div class="kpi"><div class="num"><?= (int)$stats['clients'] ?></div><div class="lbl">Active Clients</div></div>
      <div class="kpi"><div class="num"><?= (int)$stats['vehicles'] ?></div><div class="lbl">Active Vehicles</div></div>
      <div class="kpi"><div class="num"><?= (int)$stats['drivers'] ?></div><div class="lbl">Active Drivers</div></div>
      <div class="kpi"><div class="num"><?= (int)$stats['vendors'] ?></div><div class="lbl">Active Vendors</div></div>
    </div>

    <div class="row"><div class="field"><label style="font-size:13.5px;">Select a master to open :</label></div></div>
    <div class="masterlinks">
      <a class="mastercard" href="clients.php">
        <div class="ic">🏢</div>
        <div class="t">Clients Master</div>
        <div class="s">Consignors / Consignees — <?= (int)$stats['clients'] ?> active</div>
      </a>
      <a class="mastercard" href="vehicles.php">
        <div class="ic">🚛</div>
        <div class="t">Vehicles Master</div>
        <div class="s">Fleet register — <?= (int)$stats['vehicles'] ?> active</div>
      </a>
      <a class="mastercard" href="drivers.php">
        <div class="ic">🧑‍✈️</div>
        <div class="t">Drivers Master</div>
        <div class="s">Driver / license records — <?= (int)$stats['drivers'] ?> active</div>
      </a>
      <a class="mastercard" href="vendors.php">
        <div class="ic">🤝</div>
        <div class="t">Vendors Master</div>
        <div class="s">Transport vendors / brokers — <?= (int)$stats['vendors'] ?> active</div>
      </a>
    </div>

    <div class="row" style="margin-top:22px;"><div class="field"><label style="font-size:13.5px;">Other modules :</label></div></div>
    <div class="masterlinks">
      <a class="mastercard" href="transport.php">
        <div class="ic">📦</div>
        <div class="t">Transportation</div>
        <div class="s">GR/LR Booking &amp; Trip Sheets</div>
      </a>
      <a class="mastercard" href="accounts.php">
        <div class="ic">🧾</div>
        <div class="t">Accounts</div>
        <div class="s">Client invoices &amp; vendor bills</div>
      </a>
      <a class="mastercard" href="attendance.php">
        <div class="ic">🕘</div>
        <div class="t">Attendance (HRMS)</div>
        <div class="s">Daily punch-in / punch-out log</div>
      </a>
      <a class="mastercard" href="cities.php">
        <div class="ic">🗺️</div>
        <div class="t">Cities &amp; Lead Sources</div>
        <div class="s">Lookup masters for forms &amp; reports</div>
      </a>
      <a class="mastercard" href="leaves.php">
        <div class="ic">🏖️</div>
        <div class="t">Leave (HRMS)</div>
        <div class="s">Employee leave applications</div>
      </a>
      <a class="mastercard" href="payrollruns.php">
        <div class="ic">💼</div>
        <div class="t">Payroll Run (HRMS)</div>
        <div class="s">Monthly payroll summary</div>
      </a>
      <a class="mastercard" href="vendordeposits.php">
        <div class="ic">🏦</div>
        <div class="t">Vendor Deposit</div>
        <div class="s">Security deposit ledger</div>
      </a>
      <a class="mastercard" href="administration.php">
        <div class="ic">⚙️</div>
        <div class="t">Administration</div>
        <div class="s">Support tickets &amp; portal users</div>
      </a>
    </div>
  </div>
</div>
<?php shell_end(); ?>
