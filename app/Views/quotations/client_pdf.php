<?php
/** @var array $lead @var array $client @var array $company @var array $items @var string $quoteNo */
$co = $company;
$quoteDate = date('d-m-Y');

// Logo as data URI (bypasses dompdf chroot restriction).
$logoDataUri = '';
try {
    $db = \Config\Database::connect();
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

// Compute totals
$subTotal    = 0.0;
foreach ($items as $it) { $subTotal += (float) ($it['total'] ?? 0); }
$docketCharge = (float) ($company['docket_charge'] ?? 200);
$grandTotal   = $subTotal + $docketCharge;
?><!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
  * { box-sizing: border-box; }
  body { font-family: 'Helvetica','Arial',sans-serif; color:#111; font-size:11px; margin:18px; }
  .hdr { display:table; width:100%; margin-bottom:8px; }
  .hdr .l, .hdr .r { display:table-cell; vertical-align:top; }
  .hdr .r { text-align:right; }
  .co-name { font-size:16px; font-weight:bold; letter-spacing:.5px; }
  .co-sub  { font-size:10px; color:#333; line-height:1.5; }
  .bar-title { background:#e78d3f; color:#fff; padding:14px 18px; font-weight:bold; font-size:18px; letter-spacing:1px; margin-top:6px; }
  .bar-title .qright { float:right; font-size:12px; font-weight:normal; padding-top:4px; }
  .party { display:table; width:100%; margin-top:10px; border:1px solid #333; }
  .party .cell { display:table-cell; width:50%; vertical-align:top; padding:8px 12px; }
  .party .cell + .cell { border-left:1px solid #333; }
  .lbl { font-weight:bold; text-transform:uppercase; font-size:9.5px; color:#333; }
  .val { font-weight:bold; font-size:12px; }
  .greet { margin:14px 0 10px; font-size:11px; }
  .items { width:100%; border-collapse:collapse; margin-top:4px; }
  .items th, .items td { border:1px solid #333; padding:6px 8px; font-size:10.5px; vertical-align:top; }
  .items th { background:#e78d3f; color:#fff; text-align:left; font-weight:bold; }
  .items .r { text-align:right; }
  .items .c { text-align:center; }
  .totals { width:45%; margin-left:55%; border-collapse:collapse; margin-top:0; }
  .totals td { border:1px solid #333; padding:5px 8px; font-size:11px; }
  .totals .label { text-align:right; background:#f5f5f5; }
  .totals .amt { text-align:right; }
  .totals .grand td { font-weight:bold; background:#e78d3f; color:#fff; }
  .close { margin-top:16px; font-size:11px; }
  .tcs   { margin-top:16px; border-top:2px solid #e78d3f; padding-top:8px; }
  .tcs h3 { font-size:13px; margin:0 0 6px; color:#e78d3f; }
  .tcs ol { margin:0; padding-left:20px; font-size:10px; line-height:1.55; }
  .tcs li { margin-bottom:3px; }
  .sig { margin-top:20px; padding:10px 0; text-align:right; }
  .sig .sig-name { font-weight:bold; }
  .footer { margin-top:20px; border-top:1px solid #ccc; padding-top:6px; text-align:center; font-size:9.5px; color:#666; }
</style>
</head>
<body>

<!-- HEADER: Logo | Company block | "To," -->
<table style="width:100%;border-collapse:collapse;margin-bottom:8px;">
  <tr>
    <td style="width:18%;vertical-align:middle;padding-right:10px;">
      <?php if ($logoDataUri !== ''): ?>
        <img src="<?= $logoDataUri ?>" alt="Logo" style="max-width:100%;max-height:70px;">
      <?php else: ?>
        <div style="font-size:8px;color:#888;">[Logo not uploaded]</div>
      <?php endif; ?>
    </td>
    <td style="vertical-align:top;">
      <div class="co-name"><?= esc($co['company_name'] ?? 'TOTAL CARGO EXPRESS PVT LTD') ?></div>
      <div class="co-sub">
        <?= nl2br(esc($co['company_address'] ?? 'J-119 VIKASPURI, NEW DELHI, 110018')) ?><br>
        <?= esc($co['company_website'] ?? 'www.totalcargo.co.in') ?> ·
        <?= esc($co['company_phone'] ?? '+91-8178645923') ?><br>
        <?= esc($co['company_email'] ?? 'info@totalcargo.co.in') ?><br>
        GSTIN <strong><?= esc($co['company_gstin'] ?? '07AAICT9876G1ZN') ?></strong>
      </div>
    </td>
    <td style="width:8%;text-align:right;vertical-align:top;">
      <div class="co-sub" style="font-size:12px;">To,</div>
    </td>
  </tr>
</table>

<!-- Big orange Quotation banner -->
<div class="bar-title">
  Quotation
  <span class="qright">Quotation# <strong><?= esc($quoteNo) ?></strong></span>
</div>

<!-- Client + Payment Method -->
<div class="party">
  <div class="cell">
    <div class="lbl">Client</div>
    <div class="val" style="margin-top:2px;"><?= esc(strtoupper((string) ($client['company_name'] ?? '—'))) ?></div>
    <?php if (!empty($client['contact_name'])): ?><div>MR. <?= esc(strtoupper((string) $client['contact_name'])) ?></div><?php endif; ?>
    <div style="font-size:10.5px;line-height:1.45;margin-top:4px;">
      <?= nl2br(esc($client['address'] ?? '')) ?>
      <?php if (!empty($client['city']) || !empty($client['state'])): ?>
        <br><?= esc(trim(($client['city'] ?? '') . ', ' . ($client['state'] ?? ''), ', ')) ?>
      <?php endif; ?>
      <?php if (!empty($client['mobile'])): ?>  <br>+91-<?= esc($client['mobile']) ?><?php endif; ?>
      <?php if (!empty($client['email'])): ?>   <br><?= esc($client['email']) ?><?php endif; ?>
      <?php if (!empty($client['gst_no'])): ?>  <br>GSTIN <strong><?= esc($client['gst_no']) ?></strong><?php endif; ?>
    </div>
  </div>
  <div class="cell">
    <div class="lbl">Payment Method</div>
    <div class="val" style="margin-top:2px;">ADVANCE</div>
    <div style="margin-top:12px;">
      <span class="lbl">Date:</span>
      <span class="val"><?= esc($quoteDate) ?></span>
    </div>
  </div>
</div>

<!-- Greeting -->
<div class="greet">
  Dear Sir/Ma'am,<br>
  Thank you for your valuable inquiry. We are pleased to quote as below:
</div>

<!-- Line items -->
<table class="items">
  <thead>
    <tr>
      <th style="width:5%;" class="c">#</th>
      <th>Description</th>
      <th style="width:12%;" class="c">Qty</th>
      <th style="width:15%;" class="r">Price</th>
      <th style="width:15%;" class="r">Total</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($items as $i => $it): ?>
      <tr>
        <td class="c"><?= $i + 1 ?></td>
        <td>
          <strong><?= esc($it['title'] ?? 'Transportation Services') ?></strong>
          <?php if (!empty($it['detail'])): ?><br><small><?= nl2br(esc($it['detail'])) ?></small><?php endif; ?>
        </td>
        <td class="c"><?= esc($it['qty'] ?? 1) ?></td>
        <td class="r">Rs.<?= number_format((float) ($it['price'] ?? 0), 2) ?></td>
        <td class="r">Rs.<?= number_format((float) ($it['total'] ?? 0), 2) ?></td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<!-- Totals -->
<table class="totals">
  <tr><td class="label">SUB TOTAL</td><td class="amt">Rs.<?= number_format($subTotal, 2) ?></td></tr>
  <tr><td class="label">Docket Charges</td><td class="amt">Rs.<?= number_format($docketCharge, 2) ?></td></tr>
  <tr class="grand"><td class="label">GRAND TOTAL</td><td class="amt">Rs.<?= number_format($grandTotal, 2) ?></td></tr>
</table>

<div class="close">
  We hope you find our offer to be in line with your requirement.
</div>

<!-- Terms & Conditions -->
<?php
  // Prefer operator-defined terms from Settings → Quotation. Fall back to
  // the built-in defaults so a brand-new install still prints a real T&C block.
  $defaultTerms = [
    'Payment shall be made after dispatch of vehicle.',
    'Loading point single and unloading point single will be charged; multi-point will be extra as per distance minimum INR 2500.',
    'Weight will be as mentioned above. If more than that it\'s subject to situation and vehicle — same vehicle will be taking or have to offload material; no labour charges will be borne by us in that scenario.',
    'Payment terms will be same as mentioned above.',
    'In case of holding of vehicle at loading or unloading point, detention will be charged additional to freight INR 2000 PER DAY up to 2 days, thereafter INR 2500 PER DAY.',
    'In case of vehicle hold due to documentation issues, party has to ensure prompt action for release of vehicle.',
    'In case of any damages to sealed and packed goods we will not be able to take any responsibility for the same.',
    'If the vehicle returns without loading, dead-freight / cancellation charge as per distance will apply.',
    'All disputes subject to ' . ($co['company_state'] ?? 'Delhi') . ' jurisdiction only.',
  ];
  $termsToPrint = !empty($quotationTerms) ? $quotationTerms : $defaultTerms;
?>
<div class="tcs">
  <h3>Terms &amp; Conditions:</h3>
  <ol>
    <?php foreach ($termsToPrint as $t): ?>
      <li><?= esc($t) ?></li>
    <?php endforeach; ?>
  </ol>
</div>

<!-- Signature -->
<div class="sig">
  For <strong><?= esc($co['company_name'] ?? 'Total Cargo Express Pvt Ltd') ?></strong><br>
  <span class="sig-name">Authorized Signatory</span>
</div>

<div class="footer">
  This quotation is computer generated. Valid for 15 days from the date above unless otherwise agreed in writing.
</div>

</body>
</html>
