<?php $fmtAmt = fn ($n) => '₹' . number_format((float) $n, 0); ?>
<div class="d-flex align-items-center mb-3 gap-2 flex-wrap">
  <h5 class="m-0"><i class="bi bi-shield-shaded"></i> Cargo Insurance</h5>
  <small class="text-muted">All active trips — click a row to enter/update cargo value + policy details.</small>
</div>

<?php if (empty($rows)): ?>
  <div class="card"><div class="card-body text-center text-muted">No active trips.</div></div>
<?php else: ?>
  <div class="card">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0" style="font-size:.9rem;" data-tpt-cols="insurance">
        <thead class="table-light">
          <tr>
            <th style="width:32px;"></th>
            <th data-col="trip">Trip / LR</th>
            <th data-col="vehicle">Vehicle</th>
            <th data-col="route">Route</th>
            <th data-col="status">Status</th>
            <th class="text-end" data-col="cargo-value">Cargo Value</th>
            <th data-col="policy">Policy</th>
            <th data-col="provider">Provider</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $t): ?>
            <?php $cv = (float) ($t['cargo_value_inr'] ?? $t['booking_cargo_value'] ?? 0); ?>
            <tr class="ins-row" data-target="#ins-<?= (int) $t['id'] ?>" style="cursor:pointer;">
              <td class="text-muted"><i class="bi bi-chevron-right ins-chevron"></i></td>
              <td data-col="trip"><strong><?= esc($t['trip_no']) ?></strong>
                <?php if (!empty($t['lr_no'])): ?><br><small class="text-muted">LR <?= esc($t['lr_no']) ?></small><?php endif; ?>
              </td>
              <td data-col="vehicle"><code><?= esc($t['vehicle_number'] ?? '—') ?></code></td>
              <td data-col="route"><?= esc($t['route_text'] ?? '—') ?></td>
              <td data-col="status"><span class="badge-soft"><?= esc($t['current_status']) ?></span></td>
              <td class="text-end" data-col="cargo-value"><?= $cv > 0 ? $fmtAmt($cv) : '—' ?></td>
              <td data-col="policy">
                <?php if (!empty($t['insurance_policy_no'])): ?>
                  <span class="badge-soft badge-ok"><?= esc($t['insurance_policy_no']) ?></span>
                <?php elseif ($cv > 0): ?>
                  <span class="badge-soft badge-warn">Not insured</span>
                <?php else: ?>
                  <span class="text-muted">—</span>
                <?php endif; ?>
              </td>
              <td data-col="provider"><?= esc($t['insurance_provider'] ?? '—') ?></td>
            </tr>
            <tr id="ins-<?= (int) $t['id'] ?>" class="ins-detail" style="display:none;background:#fafcff;">
              <td></td>
              <td colspan="7" style="padding:12px 16px;">
                <form method="post" action="<?= site_url('trips/' . $t['id'] . '/insurance') ?>" class="row g-2 align-items-end">
                  <?= csrf_field() ?>
                  <div class="col-md-3">
                    <label class="form-label">Cargo Value (₹)</label>
                    <input class="form-control form-control-sm" type="number" step="0.01" name="cargo_value_inr" value="<?= esc($cv > 0 ? $cv : '') ?>">
                  </div>
                  <div class="col-md-4">
                    <label class="form-label">Policy No.</label>
                    <input class="form-control form-control-sm" name="insurance_policy_no" value="<?= esc($t['insurance_policy_no'] ?? '') ?>">
                  </div>
                  <div class="col-md-4">
                    <label class="form-label">Provider</label>
                    <input class="form-control form-control-sm" name="insurance_provider" value="<?= esc($t['insurance_provider'] ?? '') ?>" placeholder="ICICI Lombard / Tata AIG">
                  </div>
                  <div class="col-md-1"><button class="btn btn-sm btn-primary w-100">Save</button></div>
                  <?php if ($cv > 0 && empty($t['insurance_policy_no'])): ?>
                    <div class="col-12"><small class="text-warning"><i class="bi bi-exclamation-triangle"></i> Cargo value declared but no policy attached.</small></div>
                  <?php endif; ?>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endif; ?>
<?php if (!empty($pager)): ?><div class="mt-3"><?= $pager->links() ?></div><?php endif; ?>

<script>
document.querySelectorAll('tr.ins-row').forEach(function (tr) {
  tr.addEventListener('click', function (e) {
    if (e.target.closest('a, button, input, select, textarea, label')) return;
    var tgt = document.querySelector(tr.getAttribute('data-target'));
    var chev = tr.querySelector('.ins-chevron');
    if (!tgt) return;
    var open = tgt.style.display !== 'none';
    tgt.style.display = open ? 'none' : 'table-row';
    if (chev) { chev.classList.toggle('bi-chevron-right', open); chev.classList.toggle('bi-chevron-down', !open); }
  });
});
</script>
