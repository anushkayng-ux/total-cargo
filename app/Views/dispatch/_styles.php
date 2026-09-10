<?php /* Shared print-friendly styles reused by every dispatch document */ ?>
<style>
  * { box-sizing: border-box; }
  body { font-family: 'Helvetica', 'Arial', sans-serif; color: #111; font-size: 11px; margin: 0; padding: 0; background: #f4f4f6; }
  .doc-page {
    background: #fff; color: #111;
    width: 210mm; min-height: 297mm;
    margin: 10mm auto; padding: 12mm;
    box-shadow: 0 1px 3px rgba(0,0,0,.08);
    page-break-after: always;
  }
  .doc-page:last-child { page-break-after: auto; }
  h1, h2, h3, h4 { margin: 0 0 6px; }
  h1 { font-size: 18px; letter-spacing: 2px; }
  h2 { font-size: 14px; }
  .muted { color: #666; font-size: 10px; }
  table { width: 100%; border-collapse: collapse; }
  table.grid th, table.grid td { border: 1px solid #888; padding: 5px 7px; vertical-align: top; }
  table.grid th { background: #eee; text-align: left; font-weight: bold; text-transform: uppercase; font-size: 10px; letter-spacing: .04em; }
  .header { border-bottom: 2px solid #111; padding-bottom: 8px; margin-bottom: 10px; display: table; width: 100%; }
  .header .left  { display: table-cell; vertical-align: top; width: 65%; }
  .header .right { display: table-cell; vertical-align: top; text-align: right; }
  .panels { display: table; width: 100%; margin-top: 8px; }
  .panels .p { display: table-cell; width: 50%; vertical-align: top; padding-right: 8px; }
  .label { font-weight: bold; text-transform: uppercase; font-size: 10px; letter-spacing: .04em; color: #555; }
  .block { border: 1px solid #888; padding: 7px 9px; margin-bottom: 6px; }
  .sign-row { display: table; width: 100%; margin-top: 40px; }
  .sign-row .sig { display: table-cell; width: 33%; vertical-align: bottom; text-align: center; font-size: 10px; padding: 0 6px; }
  .sign-row .sig .line { border-top: 1px solid #333; padding-top: 4px; }
  .terms { font-size: 9px; color: #333; line-height: 1.4; margin-top: 10px; }
  .big-no { font-size: 22px; font-weight: bold; letter-spacing: 2px; }
  .stamp { display: inline-block; border: 2px solid #111; padding: 4px 10px; font-weight: bold; transform: rotate(-3deg); font-size: 13px; }

  @media print {
    body { background: #fff; }
    .no-print { display: none !important; }
    .doc-page { box-shadow: none; margin: 0; padding: 12mm; width: 210mm; min-height: 297mm; }
  }
  @page { size: A4; margin: 0; }
</style>
