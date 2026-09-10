<div class="d-flex align-items-center mb-3 gap-2 flex-wrap">
  <h5 class="m-0"><?= esc($pageTitle) ?></h5>
  <?php if (!empty($pager)): ?>
    <span class="badge bg-secondary ms-2"><?= $pager->getTotal() ?> total</span>
  <?php endif; ?>
</div>

<form method="get" class="card mb-3">
  <div class="card-body p-3">
    <div class="row g-2 align-items-end">
      <div class="col-md-4">
        <label class="form-label" style="font-size:.78rem;text-transform:uppercase;letter-spacing:.04em;color:#6b7280;font-weight:600;">Search</label>
        <input class="form-control form-control-sm" type="text" name="q" value="<?= esc($filters['search'] ?? '') ?>" placeholder="Vendor, RFQ no, route, remarks…">
      </div>
      <div class="col-md-2">
        <label class="form-label" style="font-size:.78rem;text-transform:uppercase;letter-spacing:.04em;color:#6b7280;font-weight:600;">RFQ no</label>
        <input class="form-control form-control-sm" type="text" name="rfq_no" value="<?= esc($filters['rfqNo'] ?? '') ?>" placeholder="RFQxxxxx">
      </div>
      <div class="col-md-3">
        <label class="form-label" style="font-size:.78rem;text-transform:uppercase;letter-spacing:.04em;color:#6b7280;font-weight:600;">Vendor</label>
        <select class="form-select form-select-sm" name="vendor_id">
          <option value="">All</option>
          <?php foreach (($vendors ?? []) as $v): ?>
            <option value="<?= (int) $v['id'] ?>" <?= (int) ($filters['vendorId'] ?? 0) === (int) $v['id'] ? 'selected' : '' ?>><?= esc($v['company_name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label" style="font-size:.78rem;text-transform:uppercase;letter-spacing:.04em;color:#6b7280;font-weight:600;">Status</label>
        <select class="form-select form-select-sm" name="selected">
          <option value="">All</option>
          <option value="shortlisted" <?= ($filters['selected'] ?? '') === 'shortlisted' ? 'selected' : '' ?>>Shortlisted</option>
          <option value="final"       <?= ($filters['selected'] ?? '') === 'final' ? 'selected' : '' ?>>Awarded</option>
        </select>
      </div>
      <div class="col-md-1">
        <label class="form-label" style="font-size:.78rem;text-transform:uppercase;letter-spacing:.04em;color:#6b7280;font-weight:600;">Min ₹</label>
        <input class="form-control form-control-sm" type="number" step="100" name="min_amount" value="<?= esc($filters['minAmt'] ?? '') ?>">
      </div>
      <div class="col-md-1">
        <label class="form-label" style="font-size:.78rem;text-transform:uppercase;letter-spacing:.04em;color:#6b7280;font-weight:600;">Max ₹</label>
        <input class="form-control form-control-sm" type="number" step="100" name="max_amount" value="<?= esc($filters['maxAmt'] ?? '') ?>">
      </div>
    </div>
    <div class="d-flex gap-2 mt-3">
      <button class="btn btn-sm btn-primary" type="submit"><i class="bi bi-funnel"></i> Apply filters</button>
      <a class="btn btn-sm btn-light" href="<?= site_url('quotations') ?>"><i class="bi bi-x-circle"></i> Clear</a>
      <div class="ms-auto d-flex align-items-center gap-2">
        <small class="text-muted">Per page</small>
        <select class="form-select form-select-sm" name="per_page" style="width:auto;" onchange="this.form.submit()">
          <?php foreach ([25,50,100] as $p): ?>
            <option value="<?= $p ?>" <?= ($filters['perPage'] ?? 25) === $p ? 'selected' : '' ?>><?= $p ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
  </div>
</form>

<div class="card">
  <div class="table-responsive">
    <table class="table mobile-cards mb-0" data-tpt-cols="quotations">
      <thead>
        <tr><th data-col="rfq">RFQ</th><th data-col="vendor">Vendor</th><th class="text-end" data-col="amount">Amount</th><th data-col="transit">Transit</th><th data-col="source">Source</th><th data-col="status">Status</th><th data-col="created">Created</th></tr>
      </thead>
      <tbody>
        <?php if (empty($rows)): ?>
          <tr><td colspan="7" class="text-center text-muted py-3">No quotations match the filters.</td></tr>
        <?php endif; ?>
        <?php foreach ($rows as $q): ?>
          <tr>
            <td data-col="rfq" data-label="RFQ"><a href="<?= site_url('rfq/' . $q['rfq_id']) ?>"><code><?= esc($q['rfq_no']) ?></code></a><br>
              <small class="text-muted"><?= esc(trim(($q['pickup_city'] ?? '') . ' - ' . ($q['drop_city'] ?? ''), ' -')) ?></small>
            </td>
            <td data-col="vendor" data-label="Vendor"><?= esc($q['vendor_name']) ?>
              <?= !empty($q['is_preferred'])   ? ' <span class="badge-soft badge-ok">Preferred</span>' : '' ?>
              <?= !empty($q['is_blacklisted']) ? ' <span class="badge-soft badge-danger">Blacklisted</span>' : '' ?>
            </td>
            <td class="text-end" data-col="amount" data-label="Amount">₹<?= number_format((float) $q['quote_amount'], 2) ?></td>
            <td data-col="transit" data-label="Transit"><?= esc($q['transit_days'] ?? '—') ?> d</td>
            <td data-col="source" data-label="Source"><span class="badge-soft"><?= esc($q['response_source']) ?></span></td>
            <td data-col="status" data-label="Status">
              <?php if (!empty($q['is_final_selected'])): ?><span class="badge-soft badge-ok">Awarded</span>
              <?php elseif (!empty($q['is_shortlisted'])): ?><span class="badge-soft badge-warn">Shortlisted</span>
              <?php else: ?><span class="text-muted">—</span><?php endif; ?>
            </td>
            <td data-col="created" data-label="Created"><?= esc(tpt_dt($q['created_at'])) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php if (!empty($pager)): ?>
  <div class="d-flex justify-content-center mt-3"><?= $pager->links() ?></div>
<?php endif; ?>
