<?php
$co = $settings['company'] ?? [];
$isReverse = (strtoupper((string) ($co['company_state'] ?? '')) === strtoupper((string) ($client['state'] ?? '')));
// State of Supply — GSTIN's first 2 digits are the state code (e.g. "09" = UP).
$gstinPrefix   = substr((string) ($client['gst_no'] ?? ''), 0, 2);
$stateOfSupply = trim(($gstinPrefix !== '' ? $gstinPrefix . '-' : '') . ($client['state'] ?? '—'), ' -');
$amountToWords = static function (float $n): string {
    $n = (int) round($n);
    if ($n === 0) return 'ZERO';
    $ones = ['', 'ONE', 'TWO', 'THREE', 'FOUR', 'FIVE', 'SIX', 'SEVEN', 'EIGHT', 'NINE',
             'TEN', 'ELEVEN', 'TWELVE', 'THIRTEEN', 'FOURTEEN', 'FIFTEEN', 'SIXTEEN', 'SEVENTEEN', 'EIGHTEEN', 'NINETEEN'];
    $tens = ['', '', 'TWENTY', 'THIRTY', 'FORTY', 'FIFTY', 'SIXTY', 'SEVENTY', 'EIGHTY', 'NINETY'];
    $words = static function ($num) use (&$words, $ones, $tens) {
        if ($num < 20) return $ones[$num];
        if ($num < 100) return $tens[(int) ($num / 10)] . ($num % 10 ? '-' . $ones[$num % 10] : '');
        if ($num < 1000) return $ones[(int) ($num / 100)] . ' HUNDRED' . ($num % 100 ? ' ' . $words($num % 100) : '');
        return '';
    };
    $out = '';
    if ($n >= 10000000) { $out .= $words((int) ($n / 10000000)) . ' CRORE '; $n %= 10000000; }
    if ($n >= 100000)   { $out .= $words((int) ($n / 100000))   . ' LAKH ';  $n %= 100000; }
    if ($n >= 1000)     { $out .= $words((int) ($n / 1000))     . ' THOUSAND '; $n %= 1000; }
    if ($n > 0)         { $out .= $words($n); }
    return trim($out);
};
$totalWords = $amountToWords((float) $row['total_amount']) . ' ONLY';

// Resolve uploaded company logo as a data URI (bypasses dompdf chroot).
$logoDataUri = '';
try {
    $db  = \Config\Database::connect();
    $rowLogo = $db->table('settings')->select('setting_value')
        ->where('setting_key', 'company_logo_path')->get()->getRow();
    $rel = $rowLogo && !empty($rowLogo->setting_value) ? (string) $rowLogo->setting_value : '';
    if ($rel !== '') {
        $fs = FCPATH . $rel;
        if (file_exists($fs) && is_readable($fs)) {
            $ext  = strtolower(pathinfo($fs, PATHINFO_EXTENSION));
            $mime = ['png'=>'image/png','jpg'=>'image/jpeg','jpeg'=>'image/jpeg',
                     'webp'=>'image/webp','gif'=>'image/gif','svg'=>'image/svg+xml'][$ext] ?? 'image/png';
            $logoDataUri = 'data:' . $mime . ';base64,' . base64_encode((string) file_get_contents($fs));
        }
    }
} catch (\Throwable $e) { $logoDataUri = ''; }

// Auto-detect Reverse Charge Mechanism: if the invoice has no tax (CGST + SGST + IGST all zero),
// the bill is presumed RCM and the mandatory "GST UNDER RCM - YES" line must appear on the face
// of the invoice. Consignor pays GST directly to govt in that case (matches TCE current format).
$totalTaxOnInvoice = (float) ($row['cgst_amount'] ?? 0)
                   + (float) ($row['sgst_amount'] ?? 0)
                   + (float) ($row['igst_amount'] ?? 0);
$isRcmInvoice = !empty($row['rcm_flag']) || $totalTaxOnInvoice <= 0.0;
?><!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
  * { box-sizing: border-box; }
  body { font-family: 'Helvetica','Arial',sans-serif; color:#111; font-size:10px; margin:18px; }
  h1,h2,h3 { margin:0; }
  .top-band { border:1px solid #000; padding:8px; }
  .top-band table { width:100%; border-collapse:collapse; }
  .top-band td { vertical-align:top; }
  .co-name { font-size:14px; font-weight:bold; }
  .muted { color:#555; }
  .strip { border:1px solid #000; border-top:0; padding:5px 8px; font-size:10px; }
  .strip td { vertical-align:top; padding:2px 4px; }
  .lbl { color:#333; font-weight:bold; }
  .items { width:100%; border-collapse:collapse; margin-top:6px; }
  .items th, .items td { border:1px solid #000; padding:4px 5px; font-size:9.5px; vertical-align:top; }
  .items th { background:#eaeaea; text-align:center; font-weight:bold; }
  .r { text-align:right; }
  .c { text-align:center; }
  .totals { width:100%; border-collapse:collapse; margin-top:0; }
  .totals td { border:1px solid #000; padding:4px 6px; }
  .totals .label { text-align:right; width:70%; font-weight:bold; background:#f2f2f2; }
  .totals .val   { text-align:right; width:30%; }
  .totals .grand td { font-weight:bold; font-size:11px; background:#eaeaea; }
  .words { border:1px solid #000; padding:6px 8px; margin-top:0; }
  .tcs { border:1px solid #000; border-top:0; padding:6px 8px; font-size:9px; }
  .tcs ol { margin:2px 0 0 14px; padding:0; }
  .tcs li { margin-bottom:2px; }
  .bank { border:1px solid #000; border-top:0; padding:6px 8px; font-size:9.5px; }
  .signblock { text-align:right; padding:20px 8px 6px; font-weight:bold; }
</style>
</head>
<body>

<!-- HEADER: Logo | Company block | INVOICE title -->
<div class="top-band">
  <table>
    <tr>
      <td style="width:18%;vertical-align:middle;text-align:center;padding-right:8px;">
        <?php if ($logoDataUri !== ''): ?>
          <img src="<?= $logoDataUri ?>" alt="Logo" style="max-width:100%;max-height:70px;">
        <?php else: ?>
          <div style="font-size:8px;color:#888;">[Logo not uploaded]</div>
        <?php endif; ?>
      </td>
      <td style="width:52%;">
        <div class="co-name"><?= esc($co['company_name'] ?? 'Total Cargo Express Private Limited') ?></div>
        <div class="muted"><?= nl2br(esc($co['company_address'] ?? 'J – 119 VIKASPURI, NEW DELHI, 110018')) ?></div>
        <div class="muted" style="margin-top:4px;">
          Phone No.: <?= esc($co['company_phone'] ?? '01145638110 - 8287704541') ?><br>
          E-mail : <?= esc($co['company_email'] ?? 'info@totalcargo.co.in') ?>
        </div>
        <div style="margin-top:4px;">
          <strong>GST No.:</strong> <?= esc($co['company_gstin'] ?? '07AAICT9876G1ZN') ?>
          <strong>PAN No.:</strong> <?= esc($co['company_pan'] ?? 'AAICT9876G') ?><br>
          <strong>MSME No.:</strong> <?= esc($co['company_msme'] ?? 'UDYAM-DL-10-0021169') ?>
        </div>
      </td>
      <td style="width:30%;text-align:right;vertical-align:top;">
        <div style="font-size:22px;color:#c9c9c9;font-weight:bold;letter-spacing:2px;">INVOICE</div>
      </td>
    </tr>
  </table>
</div>

<!-- Row: TAX INVOICE NO + INVOICE DATE -->
<div class="strip">
  <table>
    <tr>
      <td style="width:50%;"><span class="lbl">TAX INVOICE NO :</span> <?= esc($row['invoice_no']) ?></td>
      <td style="width:50%;text-align:right;">
        <?php
          // Fall back through invoice_date → created_at → today so this
          // cell always prints something (previously empty invoice_date
          // rendered as "01-01-1970" or blank on some invoices).
          $invDate = $row['invoice_date'] ?? '';
          if (empty($invDate) || $invDate === '0000-00-00' || strtotime((string) $invDate) === false) {
              $invDate = $row['created_at'] ?? date('Y-m-d');
          }
        ?>
        <span class="lbl">INVOICE DATE :</span> <?= esc(date('d-m-Y', strtotime((string) $invDate))) ?>
      </td>
    </tr>
  </table>
</div>

<!-- Row: M/S (billing party) + Client GST/PAN/State of Supply -->
<div class="strip">
  <table>
    <tr>
      <td style="width:60%;">
        <span class="lbl">M/S :</span> <strong><?= esc($client['company_name'] ?? $row['client_company'] ?? '—') ?></strong><br>
        <?= nl2br(esc($client['address'] ?? '')) ?><?= !empty($client['address']) ? '<br>' : '' ?>
        <?= esc(trim((string) ($client['city'] ?? '') . ' ' . ($client['state'] ?? '') . ' ' . ($client['pincode'] ?? ''))) ?>
        <?php if (!empty($row['consignor_name'])): ?>
          <br><br>
          <span class="lbl">Consignor :</span> <?= esc($row['consignor_name']) ?>
          <?php if (!empty($row['consignor_gstin'])): ?>
            &nbsp;·&nbsp; <span class="lbl">GSTIN :</span> <?= esc($row['consignor_gstin']) ?>
          <?php endif; ?>
        <?php endif; ?>
      </td>
      <td style="width:40%;">
        <span class="lbl">Consignee :</span> <?= esc($row['consignee_name'] ?? 'N/A') ?><br>
        <?php if (!empty($row['consignee_gstin'])): ?>
          <span class="lbl">CONSIGNEE GST No. :</span> <?= esc($row['consignee_gstin']) ?><br>
        <?php endif; ?>
        <span class="lbl">CLIENT GST No. :</span> <?= esc($client['gst_no'] ?? ($row['client_gstin'] ?? 'N/A')) ?><br>
        <span class="lbl">CLIENT PAN No. :</span> <?= esc($client['pan_no'] ?? 'N/A') ?><br>
        <span class="lbl">STATE OF SUPPLY :</span> <?= esc($stateOfSupply) ?>
        <?php if (!empty($row['lr_no']) || !empty($row['vehicle_number'])): ?>
          <br><span class="lbl">LR / DOCKET No. :</span> <?= esc($row['lr_no'] ?? '—') ?>
          <?php if (!empty($row['vehicle_number'])): ?>
            &nbsp;·&nbsp; <span class="lbl">TRUCK :</span> <?= esc($row['vehicle_number']) ?>
          <?php endif; ?>
        <?php endif; ?>
      </td>
    </tr>
  </table>
</div>

<!-- RCM Notice (auto-shown when total tax = 0; TCE is registered under RCM,
     so most invoices carry this bold notice). Custom Remarks (if any) rendered separately. -->
<?php if ($isRcmInvoice): ?>
<div class="strip" style="background:#fff7d6;font-weight:bold;">
  <span class="lbl">GST UNDER RCM :</span> YES
  <span style="margin-left:14px;font-weight:normal;font-size:9px;color:#555;">
    (Reverse Charge applicable — GST payable by recipient. TCE is registered under RCM.)
  </span>
</div>
<?php endif; ?>
<?php
  // Payment terms — per-invoice override wins; else the default set in
  // Settings → Invoice; else a sensible fallback.
  $paymentTerms = trim((string) ($row['payment_terms'] ?? ''));
  if ($paymentTerms === '') {
      $paymentTerms = trim((string) ($settings['invoice']['payment_terms'] ?? ''));
  }
  if ($paymentTerms === '') {
      $paymentTerms = 'Payment due within 30 days of invoice date. All disputes subject to '
                    . ($co['company_state'] ?? 'Delhi') . ' jurisdiction only.';
  }
?>
<div class="strip"><span class="lbl">PAYMENT TERMS :</span> <?= nl2br(esc($paymentTerms)) ?></div>
<?php if (!empty($row['notes'])): ?>
<div class="strip"><span class="lbl">Remarks :</span> <?= esc($row['notes']) ?></div>
<?php endif; ?>

<!-- Line Items Table -->
<table class="items">
  <thead>
    <tr>
      <th style="width:4%;">SL NO</th>
      <th style="width:7%;">GR/LR No</th>
      <th style="width:8%;">Date</th>
      <th style="width:10%;">Party Invoice No</th>
      <th style="width:9%;">Vehicle No</th>
      <th>Destination</th>
      <th style="width:14%;">Charge Heads</th>
      <th style="width:5%;">SAC</th>
      <th class="r" style="width:7%;">Amount</th>
      <th class="c" style="width:5%;">Tax Rate</th>
      <th class="r" style="width:5%;">CGST</th>
      <th class="r" style="width:5%;">SGST</th>
      <th class="r" style="width:5%;">IGST</th>
      <th class="r" style="width:8%;">Total Amount</th>
    </tr>
  </thead>
  <tbody>
    <?php
      // Shipper invoices from linked booking (JSON list) — printed under
      // "Party Invoice No" so each customer invoice number appears on its own line.
      $bkShipperInvoices = [];
      $rawBSI = (string) ($row['shipper_invoices_json'] ?? '');
      if ($rawBSI !== '') {
          $dec = json_decode($rawBSI, true);
          if (is_array($dec)) $bkShipperInvoices = $dec;
      }
      $shipperInvoiceNos = $bkShipperInvoices
          ? implode("\n", array_map(static fn ($r) => (string) ($r['no'] ?? ''), $bkShipperInvoices))
          : ($row['booking_invoice_number'] ?? '');
    ?>
    <?php foreach ($items as $i => $it): ?>
      <?php
        // Prefer per-item overrides, then trip/booking fields joined onto $row.
        $lrNo    = $it['lr_no']            ?? ($row['lr_no']              ?? '—');
        $lrDate  = $it['lr_date']          ?? ($row['lr_generated_at']    ?? ($row['lr_date'] ?? $row['invoice_date']));
        $pInv    = $it['party_invoice_no'] ?? ($shipperInvoiceNos          ?: '—');
        $vehNo   = $it['vehicle_no']       ?? ($row['vehicle_number']     ?? '—');
        $dest    = tpt_route($it['destination'] ?? ($row['route_text']    ?? ($it['description'] ?? '')), '');
        $charge  = tpt_route($it['charge_head'] ?? ($it['description']    ?? ''), '');
        $sac     = $it['hsn_sac']          ?? '996791';
      ?>
      <tr>
        <td class="c"><?= sprintf('%02d', $i + 1) ?></td>
        <td class="c"><?= esc($lrNo) ?></td>
        <td class="c"><?= esc(date('d-m-Y', strtotime((string) $lrDate))) ?></td>
        <td class="c"><?= esc($pInv) ?></td>
        <td class="c"><?= esc($vehNo) ?></td>
        <td><?= esc($dest) ?></td>
        <td><?= esc($charge) ?></td>
        <td class="c"><?= esc($sac) ?></td>
        <td class="r"><?= number_format((float) ($it['taxable_amount'] ?? $it['rate']), 0) ?></td>
        <td class="c"><?= (float) ($it['gst_percent'] ?? 0) > 0 ? esc($it['gst_percent']) . '%' : '' ?></td>
        <td class="r"><?= (float) ($it['cgst_amount'] ?? 0) > 0 ? number_format((float) $it['cgst_amount'], 2) : '' ?></td>
        <td class="r"><?= (float) ($it['sgst_amount'] ?? 0) > 0 ? number_format((float) $it['sgst_amount'], 2) : '' ?></td>
        <td class="r"><?= (float) ($it['igst_amount'] ?? 0) > 0 ? number_format((float) $it['igst_amount'], 2) : '' ?></td>
        <td class="r"><?= number_format((float) $it['total_amount'], 2) ?></td>
      </tr>
    <?php endforeach; ?>
    <?php for ($p = count($items); $p < 4; $p++): ?>
      <tr><td>&nbsp;</td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
    <?php endfor; ?>
  </tbody>
</table>

<!-- Amount in words + T&Cs (left) fills the empty space next to Totals (right) -->
<table style="width:100%;border-collapse:collapse;">
  <tr>
    <td style="width:65%;vertical-align:top;padding-right:6px;">
      <div class="words" style="margin-bottom:6px;">
        <span class="lbl">Amount in words :</span> <?= esc($totalWords) ?>
      </div>
      <div class="tcs" style="margin:0;">
        <ol style="margin:0;padding-left:18px;font-size:9px;line-height:1.5;">
          <li>All payments to be made by Demand Draft in favor of <strong><?= esc(strtoupper((string) ($co['company_name'] ?? 'TOTAL CARGO EXPRESS PRIVATE LIMITED'))) ?></strong></li>
          <li>The Company is not responsible for any Cash Settlement without official Cash Receipt</li>
          <li>Freight amount is subject to change due to fluctuation in the foreign exchange rate.</li>
          <li>Any discrepancies in this invoice must be brought into the notice of the company within 7 days.</li>
          <li>We are registered under MSME — UDYAM Registration number: <?= esc($co['company_msme'] ?? 'DL-10-0021169') ?></li>
        </ol>
      </div>
    </td>
    <td style="width:35%;vertical-align:top;">
      <table class="totals">
        <tr><td class="label">SUB TOTAL :</td><td class="val"><?= number_format((float) $row['taxable_amount'], 2) ?></td></tr>
        <tr><td class="label">ADD CGST :</td><td class="val"><?= $isRcmInvoice ? '<span style="color:#8a6d1e;">RCM</span>' : ((float) $row['cgst_amount'] > 0 ? number_format((float) $row['cgst_amount'], 2) : '0.00') ?></td></tr>
        <tr><td class="label">ADD SGST :</td><td class="val"><?= $isRcmInvoice ? '<span style="color:#8a6d1e;">RCM</span>' : ((float) $row['sgst_amount'] > 0 ? number_format((float) $row['sgst_amount'], 2) : '0.00') ?></td></tr>
        <tr><td class="label">ADD IGST :</td><td class="val"><?= $isRcmInvoice ? '<span style="color:#8a6d1e;">RCM</span>' : ((float) $row['igst_amount'] > 0 ? number_format((float) $row['igst_amount'], 2) : '0.00') ?></td></tr>
        <tr class="grand"><td class="label">TOTAL INR :</td><td class="val"><?= number_format((float) $row['total_amount'], 2) ?></td></tr>
      </table>
    </td>
  </tr>
</table>

<!-- Bank details + Sign block -->
<?php
  $bankName    = trim((string) ($co['bank_name']    ?? ''));
  $bankAc      = trim((string) ($co['bank_ac']      ?? ''));
  $bankIfsc    = trim((string) ($co['bank_ifsc']    ?? ''));
  $bankAddress = trim((string) ($co['bank_address'] ?? ''));
  $hasBank     = $bankName !== '' || $bankAc !== '' || $bankIfsc !== '';
?>
<div class="bank">
  <table style="width:100%;border-collapse:collapse;">
    <tr>
      <td style="width:65%;vertical-align:top;padding-right:10px;">
        <div class="lbl" style="font-weight:700;margin-bottom:4px;letter-spacing:.3px;">BANK DETAILS</div>
        <?php if ($hasBank): ?>
          <table style="width:100%;font-size:9.5px;border-collapse:collapse;">
            <tr>
              <td style="padding:1px 0;width:70px;"><strong>Bank Name</strong></td>
              <td style="padding:1px 4px;">:</td>
              <td style="padding:1px 0;"><?= esc($bankName ?: '—') ?></td>
            </tr>
            <tr>
              <td style="padding:1px 0;"><strong>A/C No.</strong></td>
              <td style="padding:1px 4px;">:</td>
              <td style="padding:1px 0;"><?= esc($bankAc ?: '—') ?></td>
            </tr>
            <tr>
              <td style="padding:1px 0;"><strong>IFSC Code</strong></td>
              <td style="padding:1px 4px;">:</td>
              <td style="padding:1px 0;"><?= esc(strtoupper($bankIfsc) ?: '—') ?></td>
            </tr>
            <?php if ($bankAddress !== ''): ?>
              <tr>
                <td style="padding:1px 0;vertical-align:top;"><strong>Branch</strong></td>
                <td style="padding:1px 4px;vertical-align:top;">:</td>
                <td style="padding:1px 0;"><?= esc($bankAddress) ?></td>
              </tr>
            <?php endif; ?>
          </table>
        <?php else: ?>
          <div style="color:#888;font-style:italic;font-size:9px;">
            Bank details not set — Settings → Company Bank Details.
          </div>
        <?php endif; ?>

        <?php if (!empty($row['irn_no'])): ?>
          <div style="margin-top:8px;padding-top:6px;border-top:1px dashed #999;font-size:9px;">
            <strong>IRN:</strong> <?= esc($row['irn_no']) ?>
            &nbsp;&nbsp;<strong>ACK:</strong> <?= esc($row['ack_no']) ?> / <?= esc($row['ack_date']) ?>
          </div>
        <?php endif; ?>
      </td>
      <td style="width:35%;vertical-align:bottom;text-align:center;padding-left:10px;border-left:1px solid #ccc;">
        <div class="signblock" style="line-height:1.4;">
          <div style="font-size:9.5px;text-align:right;">For <strong><?= esc($co['company_name'] ?? 'Total Cargo Express Private Limited') ?></strong></div>
          <div style="height:44px;"></div>
          <div style="border-top:1px solid #000;padding-top:3px;font-size:9.5px;font-weight:700;letter-spacing:.3px;">
            Authorised Signatory
          </div>
        </div>
      </td>
    </tr>
  </table>
  <div style="text-align:center;font-style:italic;color:#666;font-size:8.5px;margin-top:6px;border-top:1px solid #eee;padding-top:4px;">
    This invoice is computer generated, hence no signature and stamp required.
  </div>
</div>

</body>
</html>
