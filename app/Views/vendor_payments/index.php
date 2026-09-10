<div class="d-flex align-items-center mb-3 gap-2 flex-wrap">
  <h5 class="m-0"><?= esc($pageTitle) ?></h5>
  <?php if (!empty($pager)): ?><span class="badge bg-secondary ms-2"><?= $pager->getTotal() ?> total</span><?php endif; ?>
</div>

<form method="get" class="card mb-3"><div class="card-body p-3">
  <div class="row g-2 align-items-end">
    <div class="col-md-4"><label class="form-label" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.04em;color:#6b7280;font-weight:600;">Search</label>
      <input class="form-control form-control-sm" type="text" name="q" value="<?= esc($filters['search'] ?? '') ?>" placeholder="Reference, vendor, bill no, notes…"></div>
    <div class="col-md-2"><label class="form-label" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.04em;color:#6b7280;font-weight:600;">Mode</label>
      <select class="form-select form-select-sm" name="mode">
        <option value="">All</option>
        <?php foreach (['NEFT','RTGS','IMPS','UPI','Cheque','Cash','Other'] as $m): ?>
          <option <?= ($filters['mode'] ?? '') === $m ? 'selected' : '' ?>><?= $m ?></option>
        <?php endforeach; ?>
      </select></div>
    <div class="col-md-2"><label class="form-label" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.04em;color:#6b7280;font-weight:600;">From</label>
      <input class="form-control form-control-sm" type="date" name="from" value="<?= esc($filters['from'] ?? '') ?>"></div>
    <div class="col-md-2"><label class="form-label" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.04em;color:#6b7280;font-weight:600;">To</label>
      <input class="form-control form-control-sm" type="date" name="to" value="<?= esc($filters['to'] ?? '') ?>"></div>
    <div class="col-md-2 d-flex gap-1">
      <button class="btn btn-sm btn-primary"><i class="bi bi-funnel"></i> Apply</button>
      <a class="btn btn-sm btn-light" href="<?= site_url('vendor-payments') ?>">Clear</a>
    </div>
  </div>
</div></form>

<div class="card">
  <div class="table-responsive">
    <table class="table mobile-cards mb-0" data-tpt-cols="vendor-payments">
      <thead><tr><th data-col="date">Date</th><th data-col="vendor">Vendor</th><th data-col="bill">Bill</th><th class="text-end" data-col="amount">Amount</th><th data-col="mode">Mode</th><th data-col="reference">Reference</th><th data-col="actions"></th></tr></thead>
      <tbody>
        <?php if (empty($rows)): ?><tr><td colspan="7" class="text-center text-muted">No payments.</td></tr><?php endif; ?>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td data-col="date" data-label="Date"><?= esc($r['payment_date']) ?></td>
            <td data-col="vendor" data-label="Vendor"><?= esc($r['vendor_company']) ?></td>
            <td data-col="bill" data-label="Bill"><?= $r['bill_no'] ? '<a href="' . site_url('vendor-bills/' . $r['vendor_bill_id']) . '"><code>' . esc($r['bill_no']) . '</code></a>' : '—' ?></td>
            <td class="text-end" data-col="amount" data-label="Amount">₹<?= number_format((float) $r['amount_paid'], 2) ?></td>
            <td data-col="mode" data-label="Mode"><?= esc($r['payment_mode']) ?></td>
            <td data-col="reference" data-label="Ref"><?= esc($r['reference_no']) ?></td>
            <td class="text-end" data-col="actions">
              <form method="post" action="<?= site_url('vendor-payments/' . $r['id'] . '/delete') ?>" class="d-inline" data-confirm="Delete?">
                <?= csrf_field() ?><button class="btn btn-sm btn-light"><i class="bi bi-trash"></i></button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php if (!empty($pager)): ?><div class="mt-3"><?= $pager->links() ?></div><?php endif; ?>
