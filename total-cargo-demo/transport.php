<?php
require __DIR__ . '/inc/db.php';
require __DIR__ . '/inc/shell.php';
$c = db();

$stats = [
    'bookings'    => $c->query("SELECT COUNT(*) n FROM bookings")->fetch_assoc()['n'],
    'this_month'  => $c->query("SELECT COUNT(*) n FROM bookings WHERE loading_date >= DATE_FORMAT(NOW(),'%Y-%m-01')")->fetch_assoc()['n'],
    'trips'       => $c->query("SELECT COUNT(*) n FROM trips")->fetch_assoc()['n'],
    'in_transit'  => $c->query("SELECT COUNT(*) n FROM trips WHERE pod_status != 'Received'")->fetch_assoc()['n'],
    'leads'       => $c->query("SELECT COUNT(*) n FROM leads")->fetch_assoc()['n'],
    'meetings'    => $c->query("SELECT COUNT(*) n FROM meetings")->fetch_assoc()['n'],
    'rfqs'        => $c->query("SELECT COUNT(*) n FROM rfq_master")->fetch_assoc()['n'],
    'whatsapp'    => $c->query("SELECT COUNT(*) n FROM whatsapp_logs")->fetch_assoc()['n'],
    'emails'      => $c->query("SELECT COUNT(*) n FROM email_logs")->fetch_assoc()['n'],
    'advances'    => $c->query("SELECT COUNT(*) n FROM trip_advances")->fetch_assoc()['n'],
    'documents'   => $c->query("SELECT COUNT(*) n FROM documents")->fetch_assoc()['n'],
    'expenses'    => $c->query("SELECT COUNT(*) n FROM trip_expenses")->fetch_assoc()['n'],
    'stops'       => $c->query("SELECT COUNT(*) n FROM trip_stops")->fetch_assoc()['n'],
    'slots'       => $c->query("SELECT COUNT(*) n FROM loading_slots")->fetch_assoc()['n'],
    'gps'         => $c->query("SELECT COUNT(*) n FROM gps_logs")->fetch_assoc()['n'],
    'epod'        => $c->query("SELECT COUNT(*) n FROM epod_signatures")->fetch_assoc()['n'],
];

shell_start('Transportation', 'Transportation [Menu]', 'Total Cargo Express — Transportation (Demo UI)');
?>
<div class="tabs">
  <div class="tab active">Transportation</div>
  <div class="spacer"></div>
</div>
<div class="content">
  <div class="formwrap">
    <div class="kpirow">
      <div class="kpi"><div class="num"><?= (int)$stats['bookings'] ?></div><div class="lbl">Total GR/LR Bookings</div></div>
      <div class="kpi"><div class="num"><?= (int)$stats['this_month'] ?></div><div class="lbl">Bookings This Month</div></div>
      <div class="kpi"><div class="num"><?= (int)$stats['trips'] ?></div><div class="lbl">Total Trips</div></div>
      <div class="kpi"><div class="num"><?= (int)$stats['in_transit'] ?></div><div class="lbl">POD Pending</div></div>
    </div>

    <div class="row"><div class="field"><label style="font-size:13.5px;">Select a module to open :</label></div></div>
    <div class="masterlinks">
      <a class="mastercard" href="bookings.php">
        <div class="ic">🚚</div>
        <div class="t">GR/LR Booking</div>
        <div class="s">Booking register — <?= (int)$stats['bookings'] ?> records</div>
      </a>
      <a class="mastercard" href="trips.php">
        <div class="ic">🗺️</div>
        <div class="t">Trip Sheet</div>
        <div class="s">Dispatch / delivery tracking — <?= (int)$stats['trips'] ?> records</div>
      </a>
      <a class="mastercard" href="leads.php">
        <div class="ic">📞</div>
        <div class="t">Lead / Enquiry</div>
        <div class="s">Pre-booking pipeline — <?= (int)$stats['leads'] ?> records</div>
      </a>
      <a class="mastercard" href="meetings.php">
        <div class="ic">🗓️</div>
        <div class="t">Meeting Log</div>
        <div class="s">Sales visits &amp; client meetings — <?= (int)$stats['meetings'] ?> records</div>
      </a>
      <a class="mastercard" href="rfq.php">
        <div class="ic">📨</div>
        <div class="t">RFQ / Vendor Enquiry</div>
        <div class="s">Rate requests &amp; quotations — <?= (int)$stats['rfqs'] ?> records</div>
      </a>
      <a class="mastercard" href="whatsapp.php">
        <div class="ic">💬</div>
        <div class="t">WhatsApp Log</div>
        <div class="s">Client/driver messages — <?= (int)$stats['whatsapp'] ?> records</div>
      </a>
      <a class="mastercard" href="emails.php">
        <div class="ic">✉️</div>
        <div class="t">Email Log</div>
        <div class="s">Outbound mail — <?= (int)$stats['emails'] ?> records</div>
      </a>
      <a class="mastercard" href="tripadvances.php">
        <div class="ic">💵</div>
        <div class="t">Trip Advance</div>
        <div class="s">Cash advances to drivers — <?= (int)$stats['advances'] ?> records</div>
      </a>
      <a class="mastercard" href="documents.php">
        <div class="ic">📎</div>
        <div class="t">Document</div>
        <div class="s">Uploads &amp; attachments — <?= (int)$stats['documents'] ?> records</div>
      </a>
      <a class="mastercard" href="tripexpenses.php">
        <div class="ic">🧮</div>
        <div class="t">Trip Expense</div>
        <div class="s">Toll, fuel &amp; other costs — <?= (int)$stats['expenses'] ?> records</div>
      </a>
      <a class="mastercard" href="tripstops.php">
        <div class="ic">📍</div>
        <div class="t">Trip Stop</div>
        <div class="s">Pickup / drop waypoints — <?= (int)$stats['stops'] ?> records</div>
      </a>
      <a class="mastercard" href="loadingslots.php">
        <div class="ic">🕓</div>
        <div class="t">Loading Slot</div>
        <div class="s">Plant gate scheduling — <?= (int)$stats['slots'] ?> records</div>
      </a>
      <a class="mastercard" href="gpslogs.php">
        <div class="ic">📡</div>
        <div class="t">GPS Tracking</div>
        <div class="s">Vehicle route pings — <?= (int)$stats['gps'] ?> records</div>
      </a>
      <a class="mastercard" href="epod.php">
        <div class="ic">✍️</div>
        <div class="t">E-POD Signature</div>
        <div class="s">Delivery sign-off — <?= (int)$stats['epod'] ?> records</div>
      </a>
    </div>
  </div>
</div>
<?php shell_end(); ?>
