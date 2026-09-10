<?php
$service = $service ?? new \App\Libraries\EwayBillService();
$ewbNo   = $row['ewb_no'] ?? '';
$status  = (string) ($row['ewb_status'] ?? 'None');
$valid   = $row['ewb_valid_until'] ?? '';
$expired = $valid && strtotime((string) $valid) < time();
$cls = match ($status) {
    'Active'    => $expired ? 'badge-soft badge-danger' : 'badge-soft badge-ok',
    'Cancelled' => 'badge-soft badge-danger',
    default     => 'badge-soft',
};
?>
<div class="card mb-3" id="ewb">
  <div class="card-header d-flex align-items-center gap-2 flex-wrap">
    <span>E-Way Bill</span>
    <span class="<?= $cls ?>"><?= esc($expired && $status === 'Active' ? 'Expired' : $status) ?></span>
    <?php if ($ewbNo): ?><code class="ms-2"><?= esc($ewbNo) ?></code><?php endif; ?>
    <?php if (!$service->isConfigured()): ?>
      <span class="badge-soft badge-warn ms-auto" style="font-size:.72rem;">API keys not set — use Manual Entry</span>
    <?php endif; ?>
  </div>
  <div class="card-body">
    <?php if ($ewbNo): ?>
      <div class="row g-3" style="font-size:.9rem;">
        <div class="col-md-3"><div class="text-muted">EWB No</div><div><code><?= esc($ewbNo) ?></code></div></div>
        <div class="col-md-3"><div class="text-muted">Generated</div><div><?= esc($row['ewb_date'] ?? '—') ?></div></div>
        <div class="col-md-3"><div class="text-muted">Valid Until</div>
          <div><?= esc($valid ?: '—') ?><?= $expired ? ' <span class="badge-soft badge-danger ms-1">Expired</span>' : '' ?></div>
        </div>
        <div class="col-md-3 d-flex align-items-end gap-2">
          <form method="post" action="<?= site_url('trips/' . $row['id'] . '/ewb/fetch') ?>" class="d-inline">
            <?= csrf_field() ?>
            <button class="btn btn-sm btn-light" type="submit" title="Refresh validity from NIC"><i class="bi bi-arrow-clockwise"></i> Refresh</button>
          </form>
          <?php if ($status === 'Active'): ?>
            <form method="post" action="<?= site_url('trips/' . $row['id'] . '/ewb/cancel') ?>" class="d-inline" data-confirm="Cancel this EWB on NIC portal?">
              <?= csrf_field() ?>
              <input type="hidden" name="reason" value="Duplicate">
              <button class="btn btn-sm btn-light" type="submit"><i class="bi bi-x-octagon"></i> Cancel</button>
            </form>
          <?php endif; ?>
          <form method="post" action="<?= site_url('trips/' . $row['id'] . '/ewb/clear') ?>" class="d-inline" data-confirm="Clear the locally-stored EWB (use if manually entered wrongly)?">
            <?= csrf_field() ?>
            <button class="btn btn-sm btn-light" type="submit"><i class="bi bi-trash"></i> Clear</button>
          </form>
        </div>
      </div>
    <?php else: ?>
      <div class="row g-2">
        <div class="col-md-6">
          <form method="post" action="<?= site_url('trips/' . $row['id'] . '/ewb/generate') ?>" class="d-flex gap-2 align-items-end">
            <?= csrf_field() ?>
            <div class="flex-grow-1">
              <label class="form-label">Distance (km) — for NIC calculation</label>
              <input type="number" class="form-control form-control-sm" name="distance_km" min="1" max="4000" placeholder="e.g. 1420">
            </div>
            <button class="btn btn-primary btn-sm" type="submit"><i class="bi bi-lightning"></i> Generate via API</button>
          </form>
          <small class="text-muted">Calls ClearTax GSP when configured. Otherwise queues the payload for reference.</small>
        </div>
        <div class="col-md-6">
          <form method="post" action="<?= site_url('trips/' . $row['id'] . '/ewb/manual') ?>">
            <?= csrf_field() ?>
            <label class="form-label">Or enter EWB manually</label>
            <div class="row g-2">
              <div class="col-md-6"><input class="form-control form-control-sm" name="ewb_no" placeholder="12-digit EWB no" required></div>
              <div class="col-md-4"><input type="datetime-local" class="form-control form-control-sm" name="ewb_valid_until" placeholder="Valid until"></div>
              <div class="col-md-2"><button class="btn btn-outline-dark btn-sm w-100" type="submit">Save</button></div>
            </div>
            <small class="text-muted">Paste in the number you generated on ewaybillgst.gov.in — it will appear on the LR automatically.</small>
          </form>
        </div>
      </div>
    <?php endif; ?>
  </div>
</div>
