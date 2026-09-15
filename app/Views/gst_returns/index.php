<?php
$extra = '<form method="get" class="d-flex align-items-center gap-2 m-0">'
    . '<label class="text-muted" style="font-size:.8rem;">From</label>'
    . '<input class="form-control form-control-sm" style="width:150px;" type="date" name="from" value="' . esc($from) . '">'
    . '<label class="text-muted" style="font-size:.8rem;">To</label>'
    . '<input class="form-control form-control-sm" style="width:150px;" type="date" name="to" value="' . esc($to) . '">'
    . '<button class="btn btn-sm btn-outline-dark"><i class="bi bi-funnel"></i> Refresh</button>'
    . '</form>';

echo tpt_toolbar([
    'close_href' => site_url('dashboard'),
    'extra'      => $extra,
    'auth'       => $auth,
]);
?>
<div class="tabs">
  <div class="tab active">GST Returns</div>
  <div class="spacer"></div>
</div>

<div class="formwrap">
  <div class="row g-3 mb-3">
    <div class="col-6 col-md-3"><div class="stat"><span class="label">Invoices</span><span class="value"><?= number_format((int) $summary['count']) ?></span></div></div>
    <div class="col-6 col-md-3"><div class="stat"><span class="label">Taxable value</span><span class="value">₹<?= number_format((float) $summary['taxable_taxable'], 0) ?></span></div></div>
    <div class="col-6 col-md-3"><div class="stat"><span class="label">CGST + SGST</span><span class="value">₹<?= number_format((float) $summary['cgst'] + (float) $summary['sgst'], 0) ?></span></div></div>
    <div class="col-6 col-md-3"><div class="stat"><span class="label">IGST</span><span class="value">₹<?= number_format((float) $summary['igst'], 0) ?></span></div></div>
  </div>

  <div class="row g-3">
    <div class="col-md-6">
      <div class="card"><div class="card-body">
        <h6 class="mb-2"><i class="bi bi-file-earmark-arrow-down"></i> GSTR-1 — outward supplies</h6>
        <p class="text-muted small">Per-invoice rows in the format expected by the GSTN Returns Offline Tool / Tally / ClearTax. Includes CGST/SGST split for intrastate and IGST for interstate.</p>
        <a class="btn btn-primary" href="<?= site_url('gst-returns/gstr1.csv') . '?' . http_build_query(['from' => $from, 'to' => $to]) ?>">
          <i class="bi bi-download"></i> Download GSTR-1 CSV
        </a>
      </div></div>
    </div>
    <div class="col-md-6">
      <div class="card"><div class="card-body">
        <h6 class="mb-2"><i class="bi bi-file-earmark-bar-graph"></i> GSTR-3B — summary</h6>
        <p class="text-muted small">Summary numbers per GSTR-3B section. Hand to your CA, or use as a reference when filing on GSTN directly.</p>
        <a class="btn btn-primary" href="<?= site_url('gst-returns/gstr3b.csv') . '?' . http_build_query(['from' => $from, 'to' => $to]) ?>">
          <i class="bi bi-download"></i> Download GSTR-3B CSV
        </a>
      </div></div>
    </div>
  </div>

  <div class="alert alert-warning mt-3 small">
    <strong>Heads up:</strong> set your business's home state in <a href="<?= site_url('settings') ?>">Settings</a> as <code>our_state</code> so intrastate vs interstate is computed correctly. Without it the export assumes intrastate and may misallocate IGST.
  </div>
</div>
