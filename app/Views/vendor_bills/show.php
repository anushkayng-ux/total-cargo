<?php
$cls = match ((string) $row['status']) {
    'Paid'           => 'badge-soft badge-ok',
    'Partially Paid' => 'badge-soft badge-warn',
    'Cancelled'      => 'badge-soft badge-danger',
    default          => 'badge-soft',
};
?>
<div class="d-flex align-items-center mb-3 gap-2 flex-wrap">
  <h5 class="m-0">Vendor Bill <code><?= esc($row['bill_no'] ?: '#' . $row['id']) ?></code></h5>
  <span class="<?= $cls ?>"><?= esc($row['status']) ?></span>
  <a class="ms-auto btn btn-sm btn-light" href="<?= site_url('vendor-bills') ?>"><i class="bi bi-arrow-left"></i> Back</a>
  <a class="btn btn-sm btn-outline-dark" href="<?= site_url('vendor-bills/' . $row['id'] . '/edit') ?>"><i class="bi bi-pencil"></i> Edit</a>
  <?php if (!in_array($row['status'], ['Paid', 'Cancelled'], true)): ?>
    <form method="post" action="<?= site_url('vendor-bills/' . $row['id'] . '/cancel') ?>" class="d-inline" data-confirm="Cancel this bill?">
      <?= csrf_field() ?><button class="btn btn-sm btn-light"><i class="bi bi-x-octagon"></i> Cancel</button>
    </form>
  <?php endif; ?>
</div>

<div class="row g-3">
  <div class="col-lg-8">
    <div class="card"><div class="card-body">
      <div class="row g-3" style="font-size:.92rem;">
        <div class="col-md-6"><div class="text-muted">Vendor</div><div><?= esc($row['vendor_company']) ?></div></div>
        <div class="col-md-3"><div class="text-muted">Trip</div><div><?= $row['trip_no'] ? '<a href="' . site_url('trips/' . $row['trip_id']) . '"><code>' . esc($row['trip_no']) . '</code></a>' : '—' ?></div></div>
        <div class="col-md-3"><div class="text-muted">Vehicle</div><div><?= esc($row['vehicle_number'] ?? '—') ?></div></div>

        <div class="col-md-3"><div class="text-muted">Bill No</div><div><?= esc($row['bill_no']) ?></div></div>
        <div class="col-md-3"><div class="text-muted">Bill Date</div><div><?= esc($row['bill_date']) ?></div></div>
        <div class="col-md-3"><div class="text-muted">Due Date</div><div><?= esc($row['due_date']) ?></div></div>

        <div class="col-md-4"><div class="text-muted">Amount</div><div>₹<?= number_format((float) $row['bill_amount'], 2) ?></div></div>
        <div class="col-md-4"><div class="text-muted">Paid</div><div>₹<?= number_format((float) $row['amount_paid'], 2) ?></div></div>
        <div class="col-md-4"><div class="text-muted">Balance</div><div><strong>₹<?= number_format((float) $row['balance_due'], 2) ?></strong></div></div>

        <?php if (!empty($row['notes'])): ?><div class="col-12"><div class="text-muted">Notes</div><div><?= nl2br(esc($row['notes'])) ?></div></div><?php endif; ?>
      </div>
    </div></div>

    <div class="card mb-3">
      <div class="card-header">Bill Copy</div>
      <div class="card-body">
        <?php if (!empty($billDoc)): ?>
          <div class="d-flex align-items-center gap-2 flex-wrap" style="font-size:.9rem;">
            <i class="bi bi-file-earmark-pdf" style="font-size:1.8rem;"></i>
            <div>
              <div><strong><?= esc($billDoc['original_file_name']) ?></strong></div>
              <small class="text-muted">
                <?= esc(round(((int) $billDoc['file_size']) / 1024, 1)) ?> KB ·
                <?= esc($billDoc['mime_type']) ?> ·
                uploaded <?= esc(tpt_dt($billDoc['created_at'])) ?>
              </small>
            </div>
            <div class="ms-auto d-flex gap-2">
              <a class="btn btn-sm btn-outline-dark" target="_blank" href="<?= site_url('vendor-bills/' . $row['id'] . '/bill-file') ?>"><i class="bi bi-download"></i> Download</a>
              <a class="btn btn-sm btn-light" href="<?= site_url('vendor-bills/' . $row['id'] . '/edit') ?>"><i class="bi bi-arrow-repeat"></i> Replace</a>
              <form method="post" action="<?= site_url('vendor-bills/' . $row['id'] . '/bill-file/remove') ?>" class="d-inline" data-confirm="Remove the attached bill copy?">
                <?= csrf_field() ?>
                <button class="btn btn-sm btn-light"><i class="bi bi-trash"></i></button>
              </form>
            </div>
          </div>
        <?php else: ?>
          <div class="d-flex align-items-center gap-2">
            <span class="text-muted">No bill copy attached.</span>
            <a class="ms-auto btn btn-sm btn-outline-dark" href="<?= site_url('vendor-bills/' . $row['id'] . '/edit') ?>"><i class="bi bi-upload"></i> Upload Bill</a>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="col-lg-4">
    <?php if (!in_array($row['status'], ['Paid','Cancelled'], true) && (float) $row['balance_due'] > 0.01): ?>
    <div class="card mb-3">
      <div class="card-header">Record Payment</div>
      <div class="card-body">
        <form method="post" action="<?= site_url('vendor-payments/store') ?>">
          <?= csrf_field() ?>
          <input type="hidden" name="vendor_id" value="<?= (int) $row['vendor_id'] ?>">
          <input type="hidden" name="vendor_bill_id" value="<?= (int) $row['id'] ?>">
          <div class="mb-2"><label class="form-label">Date</label>
            <input type="date" class="form-control form-control-sm" name="payment_date" value="<?= date('Y-m-d') ?>" required></div>
          <div class="mb-2"><label class="form-label">Amount</label>
            <input type="number" step="0.01" class="form-control form-control-sm" name="amount_paid" max="<?= (float) $row['balance_due'] ?>" required></div>
          <div class="mb-2"><label class="form-label">Mode</label>
            <select class="form-select form-select-sm" name="payment_mode">
              <?php foreach (['Bank Transfer','UPI','Cheque','Cash','RTGS','NEFT','Other'] as $m): ?>
                <option><?= $m ?></option>
              <?php endforeach; ?>
            </select></div>
          <div class="mb-2"><label class="form-label">Reference</label>
            <input class="form-control form-control-sm" name="reference_no"></div>
          <div class="mb-2"><label class="form-label">Notes</label>
            <input class="form-control form-control-sm" name="notes"></div>
          <button class="btn btn-primary btn-sm w-100">Save Payment</button>
        </form>
      </div>
    </div>
    <?php endif; ?>

    <div class="card">
      <div class="card-header">Payments</div>
      <div class="card-body">
        <?php if (empty($payments)): ?>
          <div class="text-muted">None yet.</div>
        <?php else: ?>
          <ul class="list-unstyled m-0" style="font-size:.88rem;">
            <?php foreach ($payments as $p): ?>
              <li class="mb-2 pb-2" style="border-bottom:1px solid var(--tpt-border);">
                <div class="d-flex align-items-center gap-2">
                  <strong>₹<?= number_format((float) $p['amount_paid'], 2) ?></strong>
                  <span class="badge-soft"><?= esc($p['payment_mode']) ?></span>
                  <span class="ms-auto">
                    <form method="post" action="<?= site_url('vendor-payments/' . $p['id'] . '/delete') ?>" class="d-inline" data-confirm="Delete?">
                      <?= csrf_field() ?><button class="btn btn-sm btn-light"><i class="bi bi-trash"></i></button>
                    </form>
                  </span>
                </div>
                <small class="text-muted"><?= esc($p['payment_date']) ?> · <?= esc($p['reference_no']) ?> · by <?= esc($p['created_by_name'] ?? '—') ?></small>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
