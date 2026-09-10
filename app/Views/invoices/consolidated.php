<?php
$fmt = fn ($n) => 'Rs. ' . number_format((float) $n, 2);
?>
<div class="d-flex align-items-center mb-3 gap-2 flex-wrap">
  <h5 class="m-0"><i class="bi bi-collection"></i> Combined Invoice</h5>
  <small class="text-muted">Pick multiple LRs for a client and bill them in one invoice</small>
  <a class="ms-auto btn btn-sm btn-light" href="<?= site_url('invoices') ?>"><i class="bi bi-arrow-left"></i> Back to Invoices</a>
</div>

<div class="card mb-3">
  <div class="card-body">
    <form method="get" action="<?= site_url('invoices/consolidated') ?>" class="row g-2 align-items-end">
      <div class="col-md-6">
        <label class="form-label">Client</label>
        <select class="form-select" name="client_id">
          <option value="">— Select client —</option>
          <?php foreach ($clients as $c): ?>
            <option value="<?= (int) $c['id'] ?>" <?= (int) $clientId === (int) $c['id'] ? 'selected' : '' ?>>
              <?= esc($c['company_name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3">
        <button class="btn btn-primary w-100"><i class="bi bi-search"></i> Show unbilled LRs</button>
      </div>
    </form>
  </div>
</div>

<?php if ($clientId > 0): ?>
  <?php if (empty($trips)): ?>
    <div class="alert alert-info">
      <i class="bi bi-info-circle"></i>
      No unbilled LRs found for <strong><?= esc($client['company_name'] ?? '') ?></strong>.
      Every delivered / closed trip for this client has already been invoiced.
    </div>
  <?php else: ?>
    <form method="post" action="<?= site_url('invoices/consolidated') ?>">
      <?= csrf_field() ?>
      <input type="hidden" name="client_id" value="<?= (int) $clientId ?>">
      <div class="card mb-3">
        <div class="card-header d-flex align-items-center">
          <span><i class="bi bi-truck"></i> Unbilled LRs for <strong><?= esc($client['company_name'] ?? '') ?></strong></span>
          <span class="ms-auto text-muted" style="font-size:.85rem;">
            <?= count($trips) ?> LR<?= count($trips) === 1 ? '' : 's' ?> ready to bill
          </span>
        </div>
        <div class="table-responsive">
          <table class="table table-sm align-middle mb-0" id="consolidatedTable">
            <thead>
              <tr>
                <th style="width:36px;"><input type="checkbox" id="siAll" title="Select all"></th>
                <th>Trip / LR</th>
                <th>Route</th>
                <th>Vehicle</th>
                <th>Delivered</th>
                <th>Status</th>
                <th class="text-end">Sell Rate</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($trips as $t): ?>
                <tr>
                  <td><input type="checkbox" class="ci-row" name="trip_ids[]" value="<?= (int) $t['id'] ?>" data-rate="<?= (float) ($t['final_sell_rate'] ?? 0) ?>"></td>
                  <td>
                    <a href="<?= site_url('trips/' . $t['id']) ?>" target="_blank" style="text-decoration:none;">
                      <strong><?= esc($t['trip_no']) ?></strong>
                    </a>
                    <?php if (!empty($t['lr_no'])): ?>
                      <br><small class="text-muted">LR <?= esc($t['lr_no']) ?></small>
                    <?php else: ?>
                      <br><small class="text-danger">no LR set</small>
                    <?php endif; ?>
                  </td>
                  <td><?= esc(tpt_route(($t['pickup_city'] ?? '') . ' - ' . ($t['drop_city'] ?? ''))) ?></td>
                  <td><?= esc($t['vehicle_number'] ?? '—') ?></td>
                  <td><?= esc(!empty($t['delivery_datetime']) ? tpt_d($t['delivery_datetime']) : '—') ?></td>
                  <td><span class="badge-soft"><?= esc($t['current_status']) ?></span></td>
                  <td class="text-end"><?= $fmt($t['final_sell_rate'] ?? 0) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
            <tfoot>
              <tr>
                <th colspan="6" class="text-end">Selected sub-total:</th>
                <th class="text-end" id="ciTotal">Rs. 0.00</th>
              </tr>
            </tfoot>
          </table>
        </div>
        <div class="card-body">
          <button class="btn btn-primary" type="submit" id="ciSubmit" disabled>
            <i class="bi bi-file-earmark-plus"></i> Generate Combined Invoice
          </button>
          <small class="text-muted ms-2">Selected trips will be billed together and removed from this list once the invoice is saved.</small>
        </div>
      </div>
    </form>

    <script>
    (function () {
      var all      = document.getElementById('siAll');
      var rows     = document.querySelectorAll('.ci-row');
      var totalEl  = document.getElementById('ciTotal');
      var submitBt = document.getElementById('ciSubmit');
      function fmt(n) {
        return 'Rs. ' + n.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
      }
      function recompute() {
        var picked = 0, total = 0;
        rows.forEach(function (cb) {
          if (cb.checked) { picked++; total += parseFloat(cb.getAttribute('data-rate')) || 0; }
        });
        totalEl.textContent = fmt(total);
        submitBt.disabled   = picked === 0;
      }
      all.addEventListener('change', function () {
        rows.forEach(function (cb) { cb.checked = all.checked; });
        recompute();
      });
      rows.forEach(function (cb) { cb.addEventListener('change', recompute); });
      recompute();
    })();
    </script>
  <?php endif; ?>
<?php endif; ?>
