<div class="doc-page">
  <div class="header">
    <div class="left">
      <h2><?= esc($company['company_name'] ?? 'TPT Logistics') ?></h2>
      <div class="muted">To be signed &amp; stamped by Consignee at the time of delivery</div>
    </div>
    <div class="right">
      <h1>PROOF OF DELIVERY</h1>
      <div class="big-no">#<?= esc($trip['lr_no'] ?? $trip['trip_no']) ?></div>
      <div class="muted">Trip: <?= esc($trip['trip_no']) ?></div>
    </div>
  </div>

  <table class="grid">
    <tr>
      <th style="width:25%;">Consignee</th>
      <td colspan="3"><?= esc($booking['consignee_name'] ?? '—') ?><br>
        <?= esc($booking['consignee_address'] ?? '') ?></td>
    </tr>
    <tr>
      <th>Consignor</th><td><?= esc($client['company_name'] ?? '—') ?></td>
      <th style="width:20%;">LR Date</th><td><?= esc(date('d-m-Y', strtotime((string) ($trip['lr_generated_at'] ?? $trip['created_at'] ?? date('Y-m-d'))))) ?></td>
    </tr>
    <tr>
      <th>Vehicle</th><td><?= esc($trip['vehicle_number'] ?? '—') ?></td>
      <th>Driver</th><td><?= esc($trip['driver_name'] ?? '—') ?> · <?= esc($trip['driver_mobile'] ?? '') ?></td>
    </tr>
    <tr>
      <th>Route</th><td colspan="3"><?= esc(trim(($trip['loading_point'] ?? '') . ' - ' . ($trip['unloading_point'] ?? ''), ' -')) ?></td>
    </tr>
    <tr>
      <th>Goods Description</th><td colspan="3"><?= esc($booking['load_details'] ?? '—') ?></td>
    </tr>
  </table>

  <div class="block" style="margin-top:12px;">
    <div class="label">Consignee Acknowledgement</div>
    <p style="margin:6px 0;">The goods described above have been received in good order and condition except as noted below.</p>

    <div class="panels">
      <div class="p">
        <div class="label">Shortage / Damage (if any)</div>
        <div style="border-bottom:1px solid #777; height:28px;"></div>
        <div style="border-bottom:1px solid #777; height:28px;"></div>
      </div>
      <div class="p">
        <div class="label">Unloading Date &amp; Time</div>
        <div style="border-bottom:1px solid #777; height:28px;"></div>
        <div class="label" style="margin-top:8px;">Remarks</div>
        <div style="border-bottom:1px solid #777; height:28px;"></div>
      </div>
    </div>
  </div>

  <div class="sign-row" style="margin-top:70px;">
    <div class="sig"><div class="line">Consignee Signature &amp; Stamp</div></div>
    <div class="sig"><div class="line">Name of Receiver (BLOCK LETTERS)</div></div>
    <div class="sig"><div class="line">Driver's Signature</div></div>
  </div>

  <div class="terms" style="margin-top:14px;">
    This signed POD is the basis for release of freight payment. Consignor / Consignee is requested to return the signed copy
    (physical or clear photo on WhatsApp at <?= esc($company['company_phone'] ?? '') ?>) within 48 hours of unloading.
  </div>
</div>
