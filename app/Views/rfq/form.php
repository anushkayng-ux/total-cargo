<?php $v = function ($k, $d = '') use ($prefill) { return old($k, $prefill[$k] ?? $d); }; ?>
<h5 class="mb-3"><?= esc($pageTitle) ?><?= $lead ? ' <small class="text-muted">from lead ' . esc($lead['lead_no']) . '</small>' : '' ?></h5>

<form method="post" action="<?= site_url('rfq/store') ?>">
  <?= csrf_field() ?>
  <?php if ($lead): ?><input type="hidden" name="lead_id" value="<?= (int) $lead['id'] ?>"><?php endif; ?>

  <?php if (!empty($historyHint) && ($historyHint['samples'] ?? 0) >= 2): ?>
    <div class="alert alert-info py-2 mb-3" style="font-size:.88rem;">
      <i class="bi bi-lightbulb"></i>
      <strong>From history (last 90 days):</strong>
      <?= (int) $historyHint['samples'] ?> quotes on this lane —
      median <strong>₹<?= number_format((float) $historyHint['median_rate'], 0) ?></strong>
      (range ₹<?= number_format((float) $historyHint['min_rate'], 0) ?> – ₹<?= number_format((float) $historyHint['max_rate'], 0) ?>)
      <?php if (!empty($historyHint['top_vehicle'])): ?>
        · most common vehicle <code><?= esc($historyHint['top_vehicle']) ?></code>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <div class="card mb-3">
    <div class="card-header">RFQ Details <span class="text-muted ms-2" style="font-size:.8rem;">Vendors see only these, never the client.</span></div>
    <div class="card-body">
      <div class="row g-3">
        <div class="col-md-3"><label class="form-label">Pickup City</label>
          <input class="form-control" name="pickup_city" data-tpt-city value="<?= esc($v('pickup_city')) ?>"></div>
        <div class="col-md-3"><label class="form-label">Drop City</label>
          <input class="form-control" name="drop_city" data-tpt-city value="<?= esc($v('drop_city')) ?>"></div>
        <div class="col-md-3"><label class="form-label">Vehicle Type</label>
          <input class="form-control" name="vehicle_type" value="<?= esc($v('vehicle_type')) ?>"></div>
        <div class="col-md-3"><label class="form-label">Material</label>
          <input class="form-control" name="material_category" value="<?= esc($v('material_category')) ?>"></div>

        <div class="col-md-3"><label class="form-label">Weight</label>
          <input type="number" step="0.01" class="form-control" name="weight" value="<?= esc($v('weight')) ?>"></div>
        <div class="col-md-3"><label class="form-label">Unit</label>
          <select class="form-select" name="weight_unit">
            <?php foreach (['TON','KG','MT'] as $u): ?>
              <option value="<?= $u ?>" <?= $v('weight_unit','TON') === $u ? 'selected' : '' ?>><?= $u ?></option>
            <?php endforeach; ?>
          </select></div>
        <div class="col-md-3"><label class="form-label">Loading Date</label>
          <input type="date" class="form-control" name="loading_date" value="<?= esc($v('loading_date')) ?>"></div>
      </div>
    </div>
  </div>

  <div class="card mb-3">
    <div class="card-header d-flex flex-wrap align-items-center gap-2">
      <span>Suggested Vendors</span>
      <small class="text-muted ms-2">
        <?php if (!empty($prefill['drop_city']) && empty($includeUncovered)): ?>
          Only vendors who cover destination <strong><?= esc($prefill['drop_city']) ?></strong> are listed.
        <?php elseif (!empty($includeUncovered)): ?>
          Showing all vendors (route coverage filter is off).
        <?php else: ?>
          Preferred + route/vehicle match on top.
        <?php endif; ?>
      </small>
      <?php if (!empty($prefill['drop_city'])): ?>
        <div class="ms-auto">
          <?php $qs = http_build_query(array_filter(['lead_id' => $lead['id'] ?? null, 'include_uncovered' => empty($includeUncovered) ? 1 : 0])); ?>
          <a class="btn btn-sm <?= !empty($includeUncovered) ? 'btn-primary' : 'btn-light' ?>" href="<?= site_url('rfq/create') . ($qs ? '?' . $qs : '') ?>">
            <i class="bi bi-funnel"></i> <?= !empty($includeUncovered) ? 'Hide untagged vendors' : 'Show all vendors anyway' ?>
          </a>
        </div>
      <?php endif; ?>
    </div>
    <div class="table-responsive">
      <table class="table mb-0">
        <thead>
          <tr>
            <th style="width:40px;"><input type="checkbox" id="selAll"></th>
            <th>Vendor</th><th>WhatsApp / Mobile</th><th>Preferred</th><th>Rating</th><th>Route</th><th>Vehicle</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($suggested)): ?>
            <tr><td colspan="7" class="text-center text-muted py-3">
              <?php if (!empty($prefill['drop_city']) && empty($includeUncovered) && $totalVendors > 0): ?>
                No vendors are tagged for destination <strong><?= esc($prefill['drop_city']) ?></strong>.
                <a href="<?= site_url('rfq/create?' . http_build_query(['lead_id' => $lead['id'] ?? null, 'include_uncovered' => 1])) ?>">Show all <?= $totalVendors ?> vendors anyway</a>,
                or <a href="<?= site_url('vendors') ?>">edit a vendor</a> to tag this city as a destination they serve.
              <?php else: ?>
                No vendors found. Add vendors first.
              <?php endif; ?>
            </td></tr>
          <?php endif; ?>
          <?php foreach ($suggested as $s): ?>
            <tr>
              <td><input type="checkbox" name="vendor_ids[]" value="<?= (int) $s['id'] ?>" class="vendor-chk"></td>
              <td><?= esc($s['company_name']) ?> <small class="text-muted"><?= esc($s['owner_name']) ?></small></td>
              <td><?= esc($s['whatsapp_no'] ?: $s['mobile']) ?></td>
              <td><?= (int)$s['is_preferred'] === 1 ? '<span class="badge-soft badge-ok">Preferred</span>' : '' ?></td>
              <td><?= esc(number_format((float) $s['rating'], 1)) ?> / 5</td>
              <td><?= (int) $s['route_match'] > 0 ? '<span class="badge-soft badge-ok">Match</span>' : '<span class="text-muted">—</span>' ?></td>
              <td><?= (int) $s['vehicle_match'] > 0 ? '<span class="badge-soft badge-ok">Match</span>' : '<span class="text-muted">—</span>' ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="d-flex gap-2">
    <button class="btn btn-primary" type="submit"><i class="bi bi-send"></i> Create RFQ</button>
    <a class="btn btn-light" href="<?= site_url('rfq') ?>">Cancel</a>
  </div>
</form>

<script>
document.getElementById('selAll')?.addEventListener('change', function (e) {
  document.querySelectorAll('.vendor-chk').forEach(c => c.checked = e.target.checked);
});
</script>
