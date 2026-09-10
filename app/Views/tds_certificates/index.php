<?php
$fmt = fn ($n) => '₹' . number_format((float) $n, 2);
$badge = function ($s) {
    return ['Pending'=>'badge-warn','Received'=>'badge-ok','Disputed'=>'badge-danger','Reconciled'=>'badge-ok'][$s] ?? 'badge-soft';
};
?>
<div class="d-flex align-items-center mb-3 gap-2">
  <h5 class="m-0">TDS Certificates · Form 16A</h5>
  <form method="post" action="<?= site_url('tds-certificates/reconcile') ?>" class="ms-auto">
    <?= csrf_field() ?>
    <button class="btn btn-sm btn-primary"><i class="bi bi-arrow-clockwise"></i> Reconcile from invoices</button>
  </form>
</div>

<div class="row g-3 mb-3">
  <div class="col-md-3"><div class="stat"><span class="label">Expected total</span><span class="value"><?= $fmt($totals['expected']) ?></span></div></div>
  <div class="col-md-3"><div class="stat"><span class="label">Received total</span><span class="value"><?= $fmt($totals['received']) ?></span></div></div>
  <div class="col-md-3"><div class="stat"><span class="label">Pending rows</span><span class="value"><?= (int) $totals['pending_count'] ?></span></div></div>
  <div class="col-md-3"><div class="stat"><span class="label">Outstanding</span><span class="value"><?= $fmt($totals['expected'] - $totals['received']) ?></span></div></div>
</div>

<div class="card">
  <div class="table-responsive">
    <table class="table table-sm align-middle mb-0">
      <thead><tr><th>Client</th><th>FY</th><th>Q</th><th class="text-end">Expected</th><th class="text-end">Received</th><th>Status</th><th>Cert no / file</th><th></th></tr></thead>
      <tbody>
        <?php if (empty($rows)): ?>
          <tr><td colspan="8" class="text-center text-muted py-3">No TDS rows yet — click "Reconcile from invoices" to populate.</td></tr>
        <?php endif; ?>
        <?php foreach ($rows as $r): ?>
          <tr>
            <form method="post" action="<?= site_url('tds-certificates/' . $r['id']) ?>" enctype="multipart/form-data">
              <?= csrf_field() ?>
              <td><?= esc($r['company_name']) ?> <small class="text-muted"><?= esc($r['client_code']) ?></small></td>
              <td><?= esc($r['financial_year']) ?></td>
              <td><?= esc($r['quarter']) ?></td>
              <td class="text-end"><?= $fmt($r['expected_amount']) ?></td>
              <td class="text-end" style="min-width:140px;"><input class="form-control form-control-sm text-end" type="number" step="0.01" name="received_amount" value="<?= esc($r['received_amount']) ?>"></td>
              <td>
                <select class="form-select form-select-sm" name="status">
                  <?php foreach (\App\Models\TdsCertificateModel::STATUSES as $s): ?>
                    <option value="<?= $s ?>" <?= $r['status'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                  <?php endforeach; ?>
                </select>
              </td>
              <td>
                <input class="form-control form-control-sm mb-1" name="certificate_no" placeholder="Form 16A no" value="<?= esc($r['certificate_no']) ?>" style="min-width:140px;">
                <input class="form-control form-control-sm" type="file" name="certificate_file" accept=".pdf,.png,.jpg,.jpeg">
                <?php if (!empty($r['certificate_path'])): ?>
                  <small class="text-success">Uploaded</small>
                <?php endif; ?>
              </td>
              <td><button class="btn btn-sm btn-primary">Save</button></td>
            </form>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php if (!empty($pager)): ?><div class="mt-3"><?= $pager->links() ?></div><?php endif; ?>
