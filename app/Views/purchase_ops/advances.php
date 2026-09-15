<?php $fmtAmt = fn ($n) => '₹' . number_format((float) $n, 0); ?>
<?= tpt_toolbar([
    'close_href' => site_url('dashboard'),
    'auth'       => $auth,
]) ?>
<div class="tabs" role="tablist">
  <div class="tab active">All Active Trips</div>
  <div class="spacer"></div>
  <div class="recordnav"><?= (int) ($pager->getTotal() ?: count($rows)) ?> total records</div>
</div>
<div class="formwrap" style="flex:0 0 auto;">
  <p class="text-muted mb-0" style="font-size:.9rem;">Click a row to log an advance or settle an outstanding one.</p>
</div>

<?php if (empty($rows)): ?>
  <div class="gridwrap"><div class="text-center text-muted py-4">No active trips.</div></div>
<?php else: ?>
  <div class="gridwrap">
    <div class="table-responsive">
      <table class="table grid table-hover align-middle mb-0" style="font-size:.9rem;" data-tpt-cols="advances">
        <thead class="table-light">
          <tr>
            <th style="width:32px;"></th>
            <th data-col="trip">Trip / LR</th>
            <th data-col="vehicle-driver">Vehicle · Driver</th>
            <th data-col="route">Route</th>
            <th data-col="status">Status</th>
            <th class="text-end" data-col="given">Given</th>
            <th class="text-end" data-col="settled">Settled</th>
            <th class="text-end" data-col="outstanding">Outstanding</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $t): ?>
            <?php $tot = $t['advance_totals'] ?? ['given'=>0,'settled'=>0,'outstanding'=>0]; ?>
            <tr class="po-row" data-target="#adv-<?= (int) $t['id'] ?>" style="cursor:pointer;">
              <td class="text-muted"><i class="bi bi-chevron-right po-chevron"></i></td>
              <td data-col="trip"><strong><?= esc($t['trip_no']) ?></strong>
                <?php if (!empty($t['lr_no'])): ?><br><small class="text-muted">LR <?= esc($t['lr_no']) ?></small><?php endif; ?>
              </td>
              <td data-col="vehicle-driver"><code><?= esc($t['vehicle_number'] ?? '—') ?></code>
                <?php if (!empty($t['driver_name'])): ?><br><small><?= esc($t['driver_name']) ?></small><?php endif; ?>
              </td>
              <td data-col="route"><?= esc($t['route_text'] ?? '—') ?></td>
              <td data-col="status"><span class="badge-soft"><?= esc($t['current_status']) ?></span></td>
              <td class="text-end" data-col="given"><?= $fmtAmt($tot['given']) ?></td>
              <td class="text-end" data-col="settled"><?= $fmtAmt($tot['settled']) ?></td>
              <td class="text-end" data-col="outstanding"><strong><?= $fmtAmt($tot['outstanding']) ?></strong></td>
            </tr>
            <tr id="adv-<?= (int) $t['id'] ?>" class="po-detail" style="display:none;background:#fafcff;">
              <td></td>
              <td colspan="7" style="padding:12px 16px;">
                <?php if (!empty($t['advances'])): ?>
                  <div class="table-responsive mb-2">
                    <table class="table table-sm mb-0">
                      <thead><tr><th>When</th><th>Mode</th><th class="text-end">Given</th><th>To / Ref</th><th class="text-end">Settled</th><th></th></tr></thead>
                      <tbody>
                        <?php foreach ($t['advances'] as $a): ?>
                          <tr>
                            <td><?= esc(!empty($a['given_at']) ? date('d-m H:i', strtotime($a['given_at'])) : '—') ?></td>
                            <td><?= esc($a['mode']) ?></td>
                            <td class="text-end"><?= $fmtAmt($a['amount']) ?></td>
                            <td><?= esc($a['given_to'] ?: '—') ?> <small class="text-muted"><?= esc($a['reference_no']) ?></small></td>
                            <td class="text-end">
                              <?= $fmtAmt($a['settled_amount']) ?>
                              <?php if ((float) $a['settled_amount'] < (float) $a['amount']): ?>
                                <form method="post" action="<?= site_url('trips/' . $t['id'] . '/advances/' . $a['id'] . '/settle') ?>" class="d-flex justify-content-end gap-1 mt-1">
                                  <?= csrf_field() ?>
                                  <input class="form-control form-control-sm" type="number" step="0.01" name="settled_amount" value="<?= esc($a['amount']) ?>" style="width:100px;">
                                  <button class="btn btn-sm btn-light">Settle</button>
                                </form>
                              <?php endif; ?>
                            </td>
                            <td>
                              <form method="post" action="<?= site_url('trips/' . $t['id'] . '/advances/' . $a['id'] . '/delete') ?>" onsubmit="return confirm('Remove advance?');">
                                <?= csrf_field() ?><button class="btn btn-sm btn-light"><i class="bi bi-trash"></i></button>
                              </form>
                            </td>
                          </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>
                <?php endif; ?>
                <form method="post" action="<?= site_url('trips/' . $t['id'] . '/advances') ?>" class="row g-2 align-items-end">
                  <?= csrf_field() ?>
                  <div class="col-md-2"><label class="form-label">Amount ₹ *</label><input class="form-control form-control-sm" type="number" step="0.01" name="amount" required></div>
                  <div class="col-md-2"><label class="form-label">Mode</label>
                    <select class="form-select form-select-sm tpt-no-search" name="mode">
                      <?php foreach (\App\Models\TripAdvanceModel::MODES as $m): ?><option><?= $m ?></option><?php endforeach; ?>
                    </select>
                  </div>
                  <div class="col-md-3"><label class="form-label">Given to</label><input class="form-control form-control-sm" name="given_to" value="<?= esc($t['driver_name'] ?? '') ?>"></div>
                  <div class="col-md-2"><label class="form-label">Reference</label><input class="form-control form-control-sm" name="reference_no" placeholder="UPI / cheque no"></div>
                  <div class="col-md-2"><label class="form-label">When</label><input type="datetime-local" class="form-control form-control-sm" name="given_at"></div>
                  <div class="col-md-1"><button class="btn btn-sm btn-primary w-100">Add</button></div>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php if (!empty($pager)): ?><div class="mt-3"><?= $pager->links() ?></div><?php endif; ?>
  </div>
<?php endif; ?>

<script>
document.querySelectorAll('tr.po-row').forEach(function (tr) {
  tr.addEventListener('click', function (e) {
    if (e.target.closest('a, button, input, select, textarea, label')) return;
    var tgt = document.querySelector(tr.getAttribute('data-target'));
    var chev = tr.querySelector('.po-chevron');
    if (!tgt) return;
    var open = tgt.style.display !== 'none';
    tgt.style.display = open ? 'none' : 'table-row';
    if (chev) { chev.classList.toggle('bi-chevron-right', open); chev.classList.toggle('bi-chevron-down', !open); }
  });
});
</script>
