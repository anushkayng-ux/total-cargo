<?php
require __DIR__ . '/inc/db.php';
require __DIR__ . '/inc/shell.php';
$c = db();

$stats = [
    'invoices'     => $c->query("SELECT COUNT(*) n FROM invoices")->fetch_assoc()['n'],
    'balance_due'  => $c->query("SELECT COALESCE(SUM(balance_due),0) n FROM invoices")->fetch_assoc()['n'],
    'vendor_bills' => $c->query("SELECT COUNT(*) n FROM vendor_bills")->fetch_assoc()['n'],
    'vendor_due'   => $c->query("SELECT COALESCE(SUM(balance_due),0) n FROM vendor_bills")->fetch_assoc()['n'],
    'receipts'     => $c->query("SELECT COUNT(*) n FROM receipts")->fetch_assoc()['n'],
    'contracts'    => $c->query("SELECT COUNT(*) n FROM rate_contracts")->fetch_assoc()['n'],
    'tds'          => $c->query("SELECT COUNT(*) n FROM tds_certificates")->fetch_assoc()['n'],
];

shell_start('Accounts', 'Accounts [Menu]', 'Total Cargo Express — Accounts (Demo UI)');
?>
<div class="tabs">
  <div class="tab active">Accounts</div>
  <div class="spacer"></div>
</div>
<div class="content">
  <div class="formwrap">
    <div class="kpirow">
      <div class="kpi"><div class="num"><?= (int)$stats['invoices'] ?></div><div class="lbl">Client Invoices</div></div>
      <div class="kpi"><div class="num"><?= money($stats['balance_due']) ?></div><div class="lbl">Receivable Balance</div></div>
      <div class="kpi"><div class="num"><?= (int)$stats['vendor_bills'] ?></div><div class="lbl">Vendor Bills</div></div>
      <div class="kpi"><div class="num"><?= money($stats['vendor_due']) ?></div><div class="lbl">Payable Balance</div></div>
    </div>

    <div class="row"><div class="field"><label style="font-size:13.5px;">Select a module to open :</label></div></div>
    <div class="masterlinks">
      <a class="mastercard" href="invoices.php">
        <div class="ic">🧾</div>
        <div class="t">Client Invoices</div>
        <div class="s">Receivables — <?= (int)$stats['invoices'] ?> records</div>
      </a>
      <a class="mastercard" href="vendorbills.php">
        <div class="ic">📄</div>
        <div class="t">Vendor Bills</div>
        <div class="s">Payables &amp; payments — <?= (int)$stats['vendor_bills'] ?> records</div>
      </a>
      <a class="mastercard" href="receipts.php">
        <div class="ic">💰</div>
        <div class="t">Receipt</div>
        <div class="s">Payments received from clients — <?= (int)$stats['receipts'] ?> records</div>
      </a>
      <a class="mastercard" href="ratecontracts.php">
        <div class="ic">📝</div>
        <div class="t">Rate Contract</div>
        <div class="s">Annual client rate agreements — <?= (int)$stats['contracts'] ?> records</div>
      </a>
      <a class="mastercard" href="tdscertificates.php">
        <div class="ic">🏷️</div>
        <div class="t">TDS Certificate</div>
        <div class="s">Form 16A tracking — <?= (int)$stats['tds'] ?> records</div>
      </a>
    </div>
  </div>
</div>
<?php shell_end(); ?>
