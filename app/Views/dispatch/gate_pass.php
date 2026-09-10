<div class="doc-page">
  <div class="header">
    <div class="left">
      <h2><?= esc($company['company_name'] ?? 'TPT Logistics') ?></h2>
      <div class="muted">Vehicle Gate Pass — present at security / checkpost</div>
    </div>
    <div class="right">
      <h1>GATE PASS</h1>
      <div class="big-no">#GP-<?= esc($trip['trip_no']) ?></div>
      <div class="muted"><?= esc(date('d-m-Y H:i', strtotime((string) ($trip['created_at'] ?? date('Y-m-d H:i'))))) ?></div>
    </div>
  </div>

  <table class="grid">
    <tr>
      <th style="width:25%;">Vehicle No</th>
      <td style="width:25%;"><strong><?= esc($trip['vehicle_number'] ?? '—') ?></strong></td>
      <th style="width:25%;">Vehicle Type</th>
      <td style="width:25%;"><?= esc($booking['vehicle_type'] ?? '—') ?></td>
    </tr>
    <tr>
      <th>Driver Name</th><td><?= esc($trip['driver_name'] ?? '—') ?></td>
      <th>Driver Mobile</th><td><?= esc($trip['driver_mobile'] ?? '—') ?></td>
    </tr>
    <tr>
      <th>LR No</th><td><?= esc($trip['lr_no'] ?? '—') ?></td>
      <th>Booking</th><td><?= esc($booking['booking_no']) ?></td>
    </tr>
    <tr>
      <th>From</th><td><?= esc($trip['loading_point'] ?? '—') ?></td>
      <th>To</th><td><?= esc($trip['unloading_point'] ?? '—') ?></td>
    </tr>
    <tr>
      <th>Load Details</th><td colspan="3"><?= esc($booking['load_details'] ?? '—') ?></td>
    </tr>
  </table>

  <div class="panels" style="margin-top:12px;">
    <div class="p">
      <div class="block">
        <div class="label">Vehicle IN</div>
        <table style="margin-top:4px;">
          <tr><td style="padding:4px 6px;">Date / Time</td><td style="border-bottom:1px solid #777;height:22px;"></td></tr>
          <tr><td style="padding:4px 6px;">KMs</td><td style="border-bottom:1px solid #777;height:22px;"></td></tr>
          <tr><td style="padding:4px 6px;">Security Sign</td><td style="border-bottom:1px solid #777;height:22px;"></td></tr>
        </table>
      </div>
    </div>
    <div class="p">
      <div class="block">
        <div class="label">Vehicle OUT</div>
        <table style="margin-top:4px;">
          <tr><td style="padding:4px 6px;">Date / Time</td><td style="border-bottom:1px solid #777;height:22px;"></td></tr>
          <tr><td style="padding:4px 6px;">KMs</td><td style="border-bottom:1px solid #777;height:22px;"></td></tr>
          <tr><td style="padding:4px 6px;">Security Sign</td><td style="border-bottom:1px solid #777;height:22px;"></td></tr>
        </table>
      </div>
    </div>
  </div>

  <div class="terms" style="margin-top:14px;">
    The above-named driver and vehicle are authorised by <?= esc($company['company_name'] ?? 'TPT Logistics') ?> to carry the consignment
    described in LR #<?= esc($trip['lr_no'] ?? '—') ?>. Any deviation should be reported to the ops desk at
    <?= esc($company['company_phone'] ?? '') ?>.
  </div>

  <div class="sign-row">
    <div class="sig"><div class="line">Authorised Signatory</div></div>
    <div class="sig"><div class="line">Driver</div></div>
    <div class="sig"><div class="line">Gate Security</div></div>
  </div>
</div>
