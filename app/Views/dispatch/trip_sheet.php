<?php
$buy    = (float) ($booking['final_buy_rate']  ?? 0);
$sell   = (float) ($booking['final_sell_rate'] ?? 0);
$margin = (float) ($booking['margin_amount']   ?? ($sell - $buy));
?>
<div class="doc-page">
  <div class="header">
    <div class="left">
      <h2><?= esc($company['company_name'] ?? 'TPT Logistics') ?></h2>
      <div class="muted">Dispatch Sheet — Internal copy (not for client / consignee)</div>
    </div>
    <div class="right">
      <h1>TRIP SHEET</h1>
      <div class="big-no">#<?= esc($trip['trip_no']) ?></div>
      <div class="muted"><?= esc(date('d-m-Y', strtotime((string) ($trip['created_at'] ?? date('Y-m-d'))))) ?></div>
    </div>
  </div>

  <table class="grid">
    <tr>
      <th style="width:25%;">Booking No</th><td><?= esc($booking['booking_no']) ?></td>
      <th style="width:25%;">LR No</th><td><?= esc($trip['lr_no'] ?? '—') ?></td>
    </tr>
    <tr>
      <th>Client</th><td><?= esc($client['company_name'] ?? '—') ?></td>
      <th>Vendor</th><td><?= esc($vendor['company_name'] ?? '—') ?></td>
    </tr>
    <tr>
      <th>Vehicle</th><td><strong><?= esc($trip['vehicle_number'] ?? '—') ?></strong> · <?= esc($booking['vehicle_type'] ?? '') ?></td>
      <th>Driver</th><td><?= esc($trip['driver_name'] ?? '—') ?> · <?= esc($trip['driver_mobile'] ?? '') ?></td>
    </tr>
    <tr>
      <th>Loading Date</th><td><?= esc(date('d-m-Y', strtotime((string) ($booking['loading_date'] ?? date('Y-m-d'))))) ?></td>
      <th>Freight Mode</th><td><?= esc($booking['freight_mode'] ?? '—') ?></td>
    </tr>
  </table>

  <div class="panels" style="margin-top:10px;">
    <div class="p">
      <div class="label">Pickup · Consignor</div>
      <div class="block">
        <strong><?= esc($client['company_name'] ?? '—') ?></strong><br>
        <?= nl2br(esc($client['address'] ?? '')) ?><br>
        <?= esc($client['city'] ?? '') ?><?= !empty($client['state']) ? ', ' . esc($client['state']) : '' ?> <?= esc($client['pincode'] ?? '') ?><br>
        <span class="label">Contact:</span> <?= esc($client['contact_name'] ?? '—') ?> · <?= esc($client['mobile'] ?? '') ?>
      </div>
    </div>
    <div class="p">
      <div class="label">Drop · Consignee</div>
      <div class="block">
        <strong><?= esc($booking['consignee_name'] ?? '—') ?></strong><br>
        <?= nl2br(esc($booking['consignee_address'] ?? '')) ?><br>
        <span class="label">Contact:</span> <?= esc($booking['consignee_mobile'] ?? '—') ?>
      </div>
    </div>
  </div>

  <table class="grid" style="margin-top:8px;">
    <tr>
      <th style="width:40%;">Load Details</th>
      <td><?= esc($booking['load_details'] ?? '—') ?></td>
    </tr>
    <?php if (!empty($booking['instructions'])): ?>
    <tr>
      <th>Special Instructions</th>
      <td><?= nl2br(esc($booking['instructions'])) ?></td>
    </tr>
    <?php endif; ?>
    <tr>
      <th>Agreed Vendor Rate (Buy)</th>
      <td><strong>₹<?= number_format($buy, 2) ?></strong> <span class="muted">— payable to <?= esc($vendor['company_name'] ?? '—') ?></span></td>
    </tr>
    <tr>
      <th>Emergency Escalation</th>
      <td>
        Ops desk: <?= esc($company['company_phone'] ?? '—') ?> · Email: <?= esc($company['company_email'] ?? '—') ?>
      </td>
    </tr>
  </table>

  <div class="sign-row">
    <div class="sig"><div class="line">Dispatched by (Ops)</div></div>
    <div class="sig"><div class="line">Driver's Acknowledgement</div></div>
    <div class="sig"><div class="line">Vehicle checked &amp; out</div></div>
  </div>
</div>
