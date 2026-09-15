<?php
$val = fn ($v, $empty = '—') => ($v === null || $v === '') ? $empty : esc((string) $v);
$cls = match ((string) $row['status']) {
    'Paid'           => 'badge-soft badge-ok',
    'Partially Paid' => 'badge-soft badge-warn',
    'Cancelled'      => 'badge-soft badge-danger',
    default          => 'badge-soft',
};
$otherQuickAdd = array_values(array_filter(
    tpt_quick_add_items($auth),
    fn ($qa) => rtrim($qa['url'], '/') !== rtrim(site_url('vendor-bills/create'), '/')
));
?>
<div class="retro-toolbar is-sticky">
  <?php if ($otherQuickAdd): ?>
    <div class="dropdown d-inline-block">
      <button type="button" class="retro-tbtn retro-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false"><i class="bi bi-plus-circle-fill"></i>New</button>
      <ul class="dropdown-menu shadow-sm" style="font-size:.85rem;">
        <li><a class="dropdown-item d-flex align-items-center gap-2" href="<?= site_url('vendor-bills/create') ?>"><i class="bi bi-plus-circle"></i> New Vendor Bill</a></li>
        <li><hr class="dropdown-divider"></li>
        <li><h6 class="dropdown-header">Other…</h6></li>
        <?php foreach ($otherQuickAdd as $qa): ?>
          <li><a class="dropdown-item d-flex align-items-center gap-2" href="<?= esc($qa['url']) ?>"><i class="bi bi-<?= esc($qa['icon']) ?>"></i> <?= esc($qa['label']) ?></a></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php else: ?>
    <a class="retro-tbtn retro-primary" href="<?= site_url('vendor-bills/create') ?>"><i class="bi bi-plus-circle-fill"></i>New</a>
  <?php endif; ?>
  <a class="retro-tbtn" href="<?= site_url('vendor-bills/' . $row['id'] . '/edit') ?>"><i class="bi bi-pencil-fill"></i>Edit</a>
  <?php if (!in_array($row['status'], ['Paid', 'Cancelled'], true)): ?>
    <form method="post" action="<?= site_url('vendor-bills/' . $row['id'] . '/cancel') ?>" class="d-inline" data-confirm="Cancel this bill?">
      <?= csrf_field() ?><button type="submit" class="retro-tbtn"><i class="bi bi-x-octagon"></i>Cancel</button>
    </form>
  <?php endif; ?>
  <a class="retro-tbtn" href="<?= site_url('vendor-bills') ?>"><i class="bi bi-x-lg"></i>Close</a>
</div>

<div class="tabs" role="tablist">
  <div class="tab active">Bill Details</div>
  <div class="spacer"></div>
  <div class="recordnav">
    <a href="<?= site_url('vendor-bills') ?>"><i class="bi bi-list"></i> List</a> &middot;
    Bill <?= esc($row['bill_no'] ?: '#' . $row['id']) ?> &middot;
    <span class="<?= $cls ?>"><?= esc($row['status']) ?></span>
  </div>
</div>

<div class="retro-detail">
  <div class="retro-detail-main formwrap">
    <div class="retro-row">
      <div class="retro-field" style="width:60%;"><label>Vendor :</label><div class="retro-box xwide"><?= $val($row['vendor_company']) ?></div></div>
    </div>
    <div class="retro-row">
      <div class="retro-field"><label>Trip :</label><div class="retro-box wide"><?= $row['trip_no'] ? '<a href="' . site_url('trips/' . $row['trip_id']) . '">' . esc($row['trip_no']) . '</a>' : '—' ?></div></div>
      <div class="retro-field"><label>Vehicle :</label><div class="retro-box wide"><?= $val($row['vehicle_number'] ?? null) ?></div></div>
    </div>
    <div class="retro-row">
      <div class="retro-field"><label>Bill No :</label><div class="retro-box wide"><?= $val($row['bill_no']) ?></div></div>
      <div class="retro-field"><label>Bill Date :</label><div class="retro-box wide"><?= $val($row['bill_date']) ?></div></div>
      <div class="retro-field"><label>Due Date :</label><div class="retro-box wide"><?= $val($row['due_date']) ?></div></div>
    </div>
    <div class="retro-row">
      <div class="retro-field"><label>Amount :</label><div class="retro-box">₹<?= number_format((float) $row['bill_amount'], 2) ?></div></div>
      <div class="retro-field"><label>Paid :</label><div class="retro-box">₹<?= number_format((float) $row['amount_paid'], 2) ?></div></div>
      <div class="retro-field"><label>Balance :</label><div class="retro-box"><strong>₹<?= number_format((float) $row['balance_due'], 2) ?></strong></div></div>
    </div>
    <?php if (!empty($row['notes'])): ?>
    <div class="retro-row" style="align-items:flex-start;">
      <div class="retro-field" style="width:100%;"><label style="white-space:nowrap;">Notes :</label>
        <div class="retro-particulars"><?= nl2br(esc($row['notes'])) ?></div>
      </div>
    </div>
    <?php endif; ?>

    <h6 class="mb-2 mt-3 text-muted" style="font-size:.82rem;text-transform:uppercase;letter-spacing:.5px;">Bill Copy</h6>
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

  <div class="retro-detail-side">
    <?php if (!in_array($row['status'], ['Paid','Cancelled'], true) && (float) $row['balance_due'] > 0.01): ?>
      <h4>Record Payment :</h4>
      <form method="post" action="<?= site_url('vendor-payments/store') ?>" class="mb-2">
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
    <?php endif; ?>

    <h4>Payments :</h4>
    <?php if (empty($payments)): ?>
      <div class="remarksbox">None yet.</div>
    <?php else: ?>
      <ul class="list-unstyled m-0" style="font-size:.85rem;">
        <?php foreach ($payments as $p): ?>
          <li class="mb-2 pb-2" style="border-bottom:1px solid var(--tpt-border);">
            <div class="d-flex align-items-center gap-2">
              <strong>₹<?= number_format((float) $p['amount_paid'], 2) ?></strong>
              <span class="badge-soft"><?= esc($p['payment_mode']) ?></span>
              <span class="ms-auto">
                <form method="post" action="<?= site_url('vendor-payments/' . $p['id'] . '/delete') ?>" class="d-inline" data-confirm="Delete?">
                  <?= csrf_field() ?><button class="btn btn-sm btn-light" style="padding:0 4px;"><i class="bi bi-trash"></i></button>
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
