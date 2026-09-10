<?php
/** @var array $trip @var array $booking @var array $company @var array $client @var array $vendor @var ?string $copy */
$copy = $copy ?? '';

// Consignor: prefer explicit booking.consignor_*, fall back to client (billing party)
$consignorName    = $booking['consignor_name']    ?: ($client['company_name'] ?? '');
$consignorAddress = $booking['consignor_address'] ?: ($client['address']      ?? '');
$consignorMobile  = $booking['consignor_mobile']  ?: ($client['mobile']       ?? '');
$consignorGstin   = $booking['consignor_gstin']   ?: ($client['gst_no']       ?? '');
$consignorState   = $booking['consignor_state']   ?: ($client['state']        ?? '');

$consigneeName    = $booking['consignee_name']    ?? '';
$consigneeAddress = $booking['consignee_address'] ?? '';
$consigneeMobile  = $booking['consignee_mobile']  ?? '';
$consigneeGstin   = $booking['consignee_gstin']   ?? '';

$pickup = $booking['pickup_city'] ?? ($trip['loading_point']   ?? '');
$drop   = $booking['drop_city']   ?? ($trip['unloading_point'] ?? '');

$lrNo    = $trip['lr_no'] ?? '';
$lrDate  = $trip['lr_generated_at'] ?? ($trip['created_at'] ?? date('Y-m-d'));

$bookingNo = $booking['booking_no'] ?? '';
$truckNo   = $trip['vehicle_number'] ?? '';
$vehType   = $booking['vehicle_type'] ?? '';

$driverName   = $trip['driver_name']   ?? '';
$driverMobile = $trip['driver_mobile'] ?? '';
$vendorMobile = $vendor['mobile']      ?? '';

// Particulars column on the LR: prefer the dedicated Particulars text
// (set on the booking form or trip page), fall back to the older Load
// Details note so pre-Particulars bookings still print the goods description.
$loadDetails  = trim((string) ($booking['particulars_text'] ?? '')) !== ''
    ? (string) $booking['particulars_text']
    : ($booking['load_details'] ?? '');
$freightMode  = (string) ($booking['freight_mode'] ?? '');
$ewbNo        = (string) ($trip['ewb_no'] ?? ($booking['ewb_no'] ?? ''));
$instructions = $booking['instructions'] ?? '';

// Charge breakdown — every line pulls from booking, adds up to Grand Total.
$freightRate  = (float) ($booking['final_sell_rate']    ?? 0);
$addCh        = (float) ($booking['additional_charges'] ?? 0);
$otherCh      = (float) ($booking['other_charges']      ?? 0);
$gstAmt       = (float) ($booking['gst_amount']         ?? 0);
$serviceTax   = (float) ($booking['service_tax_amount'] ?? 0);
$grandTotal   = $freightRate + $addCh + $otherCh + $gstAmt + $serviceTax;

$createdBy    = $booking['created_by_name'] ?? '';
$billingCust  = $booking['billing_party']   ?: ($client['company_name'] ?? '');
$ftlLtl       = ($vehType !== '' ? $vehType : 'FTL');

// Docket-specific fields
$pkgCount     = $booking['packages_count']   ?? '';
$packMethod   = $booking['packing_method']   ?? '';
$actWt        = $booking['actual_weight_kg'] ?? '';
$chgWt        = $booking['charge_weight_kg'] ?? '';
$dimL         = $booking['dim_length_cm']    ?? '';
$dimW         = $booking['dim_width_cm']     ?? '';
$dimH         = $booking['dim_height_cm']    ?? '';
$invoiceNum   = $booking['invoice_number']   ?? '';

// Structured shipper-invoice list (new): every invoice tied to this docket
// with its own value. Falls back to the legacy comma-string above when the
// booking pre-dates this feature.
$shipperInvoices = [];
$rawSI = (string) ($booking['shipper_invoices_json'] ?? '');
if ($rawSI !== '') {
    $decoded = json_decode($rawSI, true);
    if (is_array($decoded)) $shipperInvoices = $decoded;
}
$billEntry    = $booking['bill_of_entry']    ?? '';
$blNum        = $booking['bl_number']        ?? '';
$containerNum = $booking['container_number'] ?? '';
$sealNum      = $booking['seal_number']      ?? '';
$personLiable = $booking['person_liable_gst']?? '';
$cargoValue   = (float) ($booking['cargo_value_inr'] ?? 0);

$coName    = $company['company_name']    ?: 'Total Cargo Express Pvt. Ltd.';
$coAddress = $company['company_address'] ?: 'J-119, VIKAS PURI, NEW DELHI-110018';
$coPhone   = $company['company_phone']   ?: '011-45638110, +91-8178645923';
$coEmail   = $company['company_email']   ?: 'info@totalcargo.co.in';
$coGstin   = $company['company_gstin']   ?: '07AAICT9876G1ZN';
$coPan     = $company['company_pan']     ?: 'AAICT9876G';
$coState   = $company['company_state']   ?: 'Delhi';
$issuingOffice = $company['issuing_office'] ?: 'HEAD OFFICE';

// Resolve uploaded company logo as a data URI. dompdf's default `chroot`
// blocks image access outside its vendor dir, so passing a filesystem path
// silently fails on Hostinger. Base64-embedding sidesteps that entirely.
$logoDataUri = '';
try {
    $db  = \Config\Database::connect();
    $row = $db->table('settings')->select('setting_value')
        ->where('setting_key', 'company_logo_path')->get()->getRow();
    $rel = $row && !empty($row->setting_value) ? (string) $row->setting_value : '';
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
?>
<style>
  /* Scoped docket styles — printed inline so the shared dispatch _styles
     don't clobber the table cell borders. */
  .tce-docket { color:#000; font-family:'Helvetica','Arial',sans-serif; font-size:10px; }
  .tce-docket table { border-collapse: collapse; width:100%; }
  .tce-docket td { border:1px solid #000; padding:4px 6px; vertical-align:top; }
  .tce-docket .no-border { border:0 !important; }
  .tce-docket .lbl { font-weight:bold; font-size:9px; color:#000; }
  .tce-docket .val { font-size:11px; font-weight:bold; }
  .tce-docket .co-name { font-size:20px; font-weight:900; text-align:center; letter-spacing:1px; }
  .tce-docket .co-sub  { text-align:center; font-size:9.5px; line-height:1.35; }
  .tce-docket .th { background:#f2f2f2; font-weight:bold; font-size:9.5px; text-align:center; }
  .tce-docket .center { text-align:center; }
  .tce-docket .right  { text-align:right; }
  .tce-docket .cn-no  { font-size:22px; font-weight:900; color:#000; text-align:right; }
  .tce-docket .big-to { font-size:18px; font-weight:bold; letter-spacing:1px; text-align:center; padding-top:3px; }
  .tce-docket .cell-lg { min-height:34px; }
  .tce-docket .charge-tbl td { padding:3px 5px; font-size:9.5px; }
  .tce-docket .foot-strip { font-size:8.5px; padding:4px 6px; }
  .tce-docket .decl { font-size:8px; line-height:1.35; padding:5px 6px; }
  .tce-docket .decl b { display:block; text-align:center; margin-bottom:2px; font-size:9px; }
  .tce-docket .sig-line { border-top:1px solid #000; margin-top:14px; padding-top:2px; font-size:9px; }
</style>

<div class="tce-docket">
<table>
  <!-- ============ TOP HEADER: logo | company block | issuing office ============ -->
  <tr>
    <td style="width:20%;text-align:center;vertical-align:middle;padding:4px;">
      <?php if ($logoDataUri !== ''): ?>
        <img src="<?= $logoDataUri ?>" alt="Logo" style="max-width:100%;max-height:110px;">
      <?php else: ?>
        <div style="font-size:9px;color:#666;">[Logo not uploaded]</div>
      <?php endif; ?>
    </td>
    <td style="width:50%;padding:8px;">
      <div class="co-name"><?= esc(strtoupper((string) $coName)) ?></div>
      <div class="co-sub"><?= esc($coAddress) ?></div>
      <div class="co-sub">Phone : <?= esc($coPhone) ?></div>
      <div class="co-sub">Email : <?= esc($coEmail) ?></div>
    </td>
    <td style="width:30%;padding:6px;">
      <div class="lbl">Issuing Office :</div>
      <div class="val" style="margin-bottom:6px;"><?= esc($issuingOffice) ?></div>
      <div class="lbl">GST No. : <span class="val" style="font-size:10px;"><?= esc($coGstin) ?></span></div>
      <div class="lbl" style="margin-top:2px;">Pan No. : <span class="val" style="font-size:10px;"><?= esc($coPan) ?></span></div>
    </td>
  </tr>

  <!-- ============ From | To | Date / CN No ============
       Nested table so From + To can be equal-width without disturbing the
       top row's logo / company / issuing-office widths.  ============ -->
  <tr>
    <td colspan="3" style="padding:0;">
      <table style="width:100%;border-collapse:collapse;margin:0;">
        <tr>
          <td style="width:35%;height:34px;vertical-align:middle;border:0;border-right:1px solid #000;padding:4px 6px;">
            <span class="lbl">From :</span> <span class="val" style="font-size:11px;"><?= esc($pickup) ?></span>
          </td>
          <td style="width:35%;height:34px;vertical-align:middle;border:0;border-right:1px solid #000;padding:4px 6px;">
            <span class="lbl">To :</span> <span class="val" style="font-size:11px;"><?= esc($drop) ?></span>
          </td>
          <td style="width:30%;border:0;padding:0;">
            <table style="border:0;width:100%;">
              <tr>
                <td class="no-border" style="width:35%;padding:2px 4px;"><span class="lbl">Date :</span></td>
                <td class="no-border" style="padding:2px 4px;"><span class="val"><?= esc(date('d-m-Y', strtotime((string) $lrDate))) ?></span></td>
              </tr>
              <tr>
                <td class="no-border" style="padding:2px 4px;"><span class="lbl">CN No :</span></td>
                <td class="no-border" style="padding:2px 4px;"><span class="cn-no"><?= esc($lrNo ?: '—') ?></span></td>
              </tr>
            </table>
          </td>
        </tr>
      </table>
    </td>
  </tr>

  <!-- ============ CONSIGNOR (50%) | CONSIGNEE (50%) — nested table so this block
       ignores the 20/50/30 top-row grid and gets a clean 50/50 split. ============ -->
  <tr>
    <td colspan="3" style="padding:0;">
      <table style="width:100%;border-collapse:collapse;margin:0;">
        <tr>
          <td style="width:50%;border:0;border-right:1px solid #000;padding:4px 6px;vertical-align:top;height:60px;">
            <div class="lbl">CONSIGNOR :</div>
            <div class="val" style="margin-top:2px;"><?= esc($consignorName) ?></div>
            <div style="font-size:9.5px;line-height:1.35;"><?= nl2br(esc($consignorAddress)) ?></div>
            <?php if ($consignorState !== ''): ?><div style="font-size:9.5px;"><?= esc($consignorState) ?></div><?php endif; ?>
          </td>
          <td style="width:50%;border:0;padding:4px 6px;vertical-align:top;height:60px;">
            <div class="lbl">CONSIGNEE :</div>
            <div class="val" style="margin-top:2px;"><?= esc($consigneeName) ?></div>
            <div style="font-size:9.5px;line-height:1.35;"><?= nl2br(esc($consigneeAddress)) ?></div>
          </td>
        </tr>
        <tr>
          <td style="border:0;border-top:1px solid #000;border-right:1px solid #000;padding:4px 6px;">
            <span class="lbl">GST NO. :</span> <span class="val"><?= esc($consignorGstin) ?></span>
          </td>
          <td style="border:0;border-top:1px solid #000;padding:4px 6px;">
            <span class="lbl">GST NO. :</span> <span class="val"><?= esc($consigneeGstin) ?></span>
          </td>
        </tr>
        <tr>
          <td style="border:0;border-top:1px solid #000;border-right:1px solid #000;padding:4px 6px;">
            <span class="lbl">Truck No.</span> <span class="val"><?= esc($truckNo) ?></span>
          </td>
          <td style="border:0;border-top:1px solid #000;padding:4px 6px;">
            <span class="lbl">C.H.A. Job No./Booking No.</span> <span class="val"><?= esc($bookingNo) ?></span>
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>

<!-- ============ Main particulars + charges grid ============ -->
<table style="margin-top:0;">
  <tr>
    <td class="th" style="width:8%;">No. of<br>Packages</td>
    <td class="th" style="width:11%;">Method of<br>Packing</td>
    <td class="th" style="width:26%;">Particulars</td>
    <td class="th" style="width:11%;">Actual Weight</td>
    <td class="th" style="width:10%;">Rate</td>
    <td class="th" style="width:14%;">Amount</td>
    <td class="th" style="width:20%;">Remarks</td>
  </tr>
  <tr>
    <td rowspan="6" class="center cell-lg val"><?= esc((string) $pkgCount) ?></td>
    <td rowspan="6" class="cell-lg"><?= esc($packMethod) ?></td>
    <td rowspan="6" class="cell-lg"><?= nl2br(esc($loadDetails)) ?></td>
    <td class="right"><?= $actWt !== '' && (float) $actWt > 0 ? number_format((float) $actWt, 2) . ' kg' : '' ?></td>
    <td>&nbsp;</td>
    <td class="right">&nbsp;</td>
    <!-- Remarks (rowspan 6) — carries the freight-mode / payment option prominently.
         Anything else lives on the row below the header block. -->
    <td rowspan="6" class="cell-lg" style="vertical-align:top;">
      <?php if ($freightMode !== ''): ?>
        <div class="lbl" style="font-size:9.5px;">Payment / Freight Mode :</div>
        <div class="val" style="font-size:14px;margin-top:2px;text-transform:uppercase;
                                background:#fff7d6;padding:4px 6px;border:1px solid #d4a017;
                                display:inline-block;letter-spacing:.5px;">
          <?= esc($freightMode) ?>
        </div>
      <?php endif; ?>
    </td>
  </tr>
  <tr>
    <td class="lbl" style="background:#fafafa;">Charge Weight</td>
    <td class="lbl">Freight Rate</td>
    <td class="right"><?= $freightRate > 0 ? number_format($freightRate, 2) : '' ?></td>
  </tr>
  <tr>
    <td class="right"><?= $chgWt !== '' && (float) $chgWt > 0 ? number_format((float) $chgWt, 2) . ' kg' : '' ?></td>
    <td class="lbl">Add. Ch.</td>
    <td class="right"><?= $addCh > 0 ? number_format($addCh, 2) : '' ?></td>
  </tr>
  <tr>
    <td class="lbl" style="background:#fafafa;">Vehicle Type</td>
    <td class="lbl">Other Ch.</td>
    <td class="right"><?= $otherCh > 0 ? number_format($otherCh, 2) : '' ?></td>
  </tr>
  <tr>
    <td><?= esc($vehType) ?></td>
    <td class="lbl">GST</td>
    <td class="right"><?= $gstAmt > 0 ? number_format($gstAmt, 2) : ($serviceTax > 0 ? number_format($serviceTax, 2) : '') ?></td>
  </tr>
  <tr>
    <td>&nbsp;</td>
    <td class="lbl">Grand Total</td>
    <td class="right val"><?= $grandTotal > 0 ? number_format($grandTotal, 2) : '' ?></td>
  </tr>

  <!-- Invoice / Bill of Entry / B/L row -->
  <tr>
    <td>&nbsp;</td>
    <td colspan="2">
      <?php if (!empty($shipperInvoices)): ?>
        <span class="lbl">Shipper Invoices :</span>
        <table style="width:100%;border:0;margin-top:2px;">
          <?php foreach ($shipperInvoices as $siRow): ?>
            <tr>
              <td class="no-border" style="padding:1px 4px;font-size:9.5px;"><?= esc($siRow['no'] ?? '') ?></td>
              <td class="no-border" style="padding:1px 4px;font-size:9.5px;text-align:right;">
                <?php $sv = (float) ($siRow['value'] ?? 0); ?>
                <?= $sv > 0 ? 'Rs. ' . number_format($sv, 2) : '' ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </table>
      <?php else: ?>
        <span class="lbl">Invoice No.</span> <?= esc($invoiceNum) ?>
      <?php endif; ?>
      &nbsp;&nbsp;<span class="lbl">Bill of Entry</span> <?= esc($billEntry) ?>
      &nbsp;&nbsp;<span class="lbl">B/L No.</span> <?= esc($blNum) ?>
      <?php if ($ewbNo !== ''): ?>
        <br><span class="lbl">E-Way Bill No.</span> <span class="val"><?= esc($ewbNo) ?></span>
      <?php endif; ?>
    </td>
    <td rowspan="3" class="lbl center" style="vertical-align:middle;">L<br>W<br>H</td>
    <td rowspan="3" class="center" style="vertical-align:middle;">
      <?= $dimL !== '' && (float) $dimL > 0 ? number_format((float) $dimL, 2) : '' ?><br>
      <?= $dimW !== '' && (float) $dimW > 0 ? number_format((float) $dimW, 2) : '' ?><br>
      <?= $dimH !== '' && (float) $dimH > 0 ? number_format((float) $dimH, 2) : '' ?>
    </td>
    <td><span class="lbl">Owner Mob. No. :</span> <?= esc($vendorMobile) ?></td>
    <td>&nbsp;</td>
  </tr>
  <tr>
    <td>&nbsp;</td>
    <td colspan="2">
      <span class="lbl">Container No. :</span> <?= esc($containerNum) ?>
      <?php if ($sealNum !== ''): ?>&nbsp;&nbsp;<span class="lbl">Seal No. :</span> <?= esc($sealNum) ?><?php endif; ?>
    </td>
    <td><span class="lbl">Driver Mob. No. :</span> <?= esc($driverMobile) ?></td>
    <td>&nbsp;</td>
  </tr>
  <tr>
    <td>&nbsp;</td>
    <td colspan="2"><span class="lbl">Value Rs. :</span> <?= $cargoValue > 0 ? number_format($cargoValue, 2) : number_format($grandTotal, 2) ?></td>
    <td><span class="lbl">GST No. :</span> <?= esc($consigneeGstin ?: $consignorGstin) ?></td>
    <td>&nbsp;</td>
  </tr>
</table>

<!-- ============ Person liable + declaration + FTL/LTL + Cenvat ============ -->
<table>
  <tr>
    <td style="width:20%;"><span class="lbl">Person Liable for GST :</span> <span class="val"><?= esc($personLiable) ?></span></td>
    <td style="width:35%;"></td>
    <td style="width:45%;padding:0;">
      <table style="border:0;">
        <tr><td class="no-border decl"><b>DECLARATION FOR CENVAT CREDIT</b></td></tr>
      </table>
    </td>
  </tr>
  <tr>
    <td rowspan="3" class="decl" style="width:20%;">
      The Consignor hereby expressly declare that the above particulars furnished by bill of this agent correct No prohibited article are included and he is aware of and accepts the conditions of carriage
      <div class="sig-line">Consignor</div>
    </td>
    <td style="height:22px;"><span class="lbl">FTL/LTL :</span> <?= esc($ftlLtl) ?></td>
    <td rowspan="3" class="decl">
      We hereby certify that we have not availed credit of duly paid on inputs of capital goods under the provisions of cenvat credit rules, 2004 nor we availed the benefit of Notification no. 12/2003-3T Dated 20-05-2002
    </td>
  </tr>
  <tr><td style="height:22px;"><span class="lbl">Booking Executive :</span> <?= esc($createdBy) ?></td></tr>
  <tr><td style="height:22px;"><span class="lbl">Billing Customer :</span> <?= esc($billingCust) ?></td></tr>
</table>

<!-- ============ Footer bar: copies legend + at owner's risk + For TCE ============ -->
<table>
  <tr>
    <td style="width:32%;padding:6px 8px;font-size:9px;">
      <table style="border:0;">
        <tr><td class="no-border" style="padding:1px 4px;"><b>WHITE THICK</b></td><td class="no-border" style="padding:1px 4px;">: POD</td></tr>
        <tr><td class="no-border" style="padding:1px 4px;"><b>PINK</b></td><td class="no-border" style="padding:1px 4px;">: CONSIGNOR</td></tr>
        <tr><td class="no-border" style="padding:1px 4px;"><b>YELLOW</b></td><td class="no-border" style="padding:1px 4px;">: CONSIGNEE</td></tr>
        <tr><td class="no-border" style="padding:1px 4px;"><b>WHITE</b></td><td class="no-border" style="padding:1px 4px;">: RECORD</td></tr>
      </table>
    </td>
    <td class="center" style="width:30%;font-weight:bold;font-size:14px;letter-spacing:1px;">AT OWNER'S RISK</td>
    <td class="center" style="width:38%;font-weight:bold;font-size:12px;">
      For : <?= esc(strtoupper((string) $coName)) ?>
      <div class="sig-line" style="margin-top:20px;">Authorized Signatory</div>
    </td>
  </tr>
  <tr>
    <td colspan="3" class="foot-strip" style="text-align:left;">
      Subject to <?= esc($coState) ?> Jurisdiction only
    </td>
  </tr>
</table>

<!-- ============ Copy label + computer-generated disclaimer ============ -->
<div style="margin-top:6px;text-align:center;">
  <?php if ($copy !== ''): ?>
    <div style="font-weight:bold;font-size:14px;letter-spacing:1px;padding:4px 0;">
      <?= esc($copy) ?>
    </div>
  <?php endif; ?>
  <div style="font-style:italic;font-size:9.5px;color:#333;">
    This LR is computer generated, hence no need to signature and stamp.
  </div>
</div>
</div>
