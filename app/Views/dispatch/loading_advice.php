<div class="doc-page">
  <div class="header">
    <div class="left">
      <h2><?= esc($company['company_name'] ?? 'TPT Logistics') ?></h2>
      <div class="muted">To be presented at the Consignor's loading point</div>
    </div>
    <div class="right">
      <h1>LOADING ADVICE</h1>
      <div class="big-no">#<?= esc($trip['trip_no']) ?></div>
      <div class="muted">Date: <?= esc(date('d-m-Y', strtotime((string) ($trip['created_at'] ?? date('Y-m-d'))))) ?></div>
    </div>
  </div>

  <p style="margin:8px 0 6px;">To,</p>
  <div class="block">
    <strong><?= esc($client['company_name'] ?? '—') ?></strong><br>
    <?= nl2br(esc($client['address'] ?? '')) ?><br>
    <?= esc($client['city'] ?? '') ?><?= !empty($client['state']) ? ', ' . esc($client['state']) : '' ?>
  </div>

  <p style="margin:10px 0 6px;">Kindly load the following consignment on the vehicle identified below, under reference to
    booking <strong><?= esc($booking['booking_no']) ?></strong> and LR <strong><?= esc($trip['lr_no'] ?? '—') ?></strong>.</p>

  <table class="grid">
    <tr>
      <th style="width:30%;">Vehicle</th>
      <td><strong><?= esc($trip['vehicle_number'] ?? '—') ?></strong> · <?= esc($booking['vehicle_type'] ?? '') ?></td>
    </tr>
    <tr>
      <th>Driver</th>
      <td><?= esc($trip['driver_name'] ?? '—') ?> · <?= esc($trip['driver_mobile'] ?? '') ?></td>
    </tr>
    <tr>
      <th>Destination</th>
      <td><?= esc($booking['consignee_name'] ?? '—') ?><br><?= esc($booking['consignee_address'] ?? '') ?></td>
    </tr>
    <tr>
      <th>Expected Loading Date</th>
      <td><?= esc(date('d-m-Y', strtotime((string) ($booking['loading_date'] ?? date('Y-m-d'))))) ?></td>
    </tr>
    <tr>
      <th>Material / Load Details</th>
      <td><?= esc($booking['load_details'] ?? '—') ?></td>
    </tr>
    <?php if (!empty($booking['instructions'])): ?>
    <tr>
      <th>Special Instructions</th>
      <td><?= nl2br(esc($booking['instructions'])) ?></td>
    </tr>
    <?php endif; ?>
  </table>

  <div class="block" style="margin-top:10px;">
    <div class="label">Documents handed over to driver at loading</div>
    <div style="display:table; width:100%; margin-top:4px;">
      <div style="display:table-cell;">☐ Tax Invoice / Delivery Challan</div>
      <div style="display:table-cell;">☐ E-Way Bill</div>
      <div style="display:table-cell;">☐ Packing List</div>
      <div style="display:table-cell;">☐ Weighment Slip</div>
    </div>
  </div>

  <p style="margin-top:10px;">Thanking you,<br><strong>for <?= esc($company['company_name'] ?? 'TPT Logistics') ?></strong></p>

  <div class="sign-row">
    <div class="sig"><div class="line">Authorised Signatory (Transporter)</div></div>
    <div class="sig"><div class="line">Consignor's Loading Supervisor</div></div>
    <div class="sig"><div class="line">Driver</div></div>
  </div>
</div>
