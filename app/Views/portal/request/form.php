<?php
$kycVerified = ($client['kyc_status'] ?? 'Pending') === 'Verified';
$rb = $rebook ?? [];
$pre = function (string $k, $default = '') use ($rb) { return old($k, $rb[$k] ?? $default); };
?>
<style>
  /* Sticky submit on mobile so users don't have to scroll back to send */
  @media (max-width: 768px) {
    .portal-request-form .submit-row {
      position: sticky;
      bottom: 0;
      background: #fff;
      padding: .75rem;
      margin: 0 -.75rem -.75rem -.75rem;
      border-top: 1px solid #eee;
      z-index: 10;
    }
  }
</style>
<h5 class="mb-3">Request a Truck</h5>

<?php if (!empty($rb)): ?>
  <div class="alert alert-info"><i class="bi bi-arrow-clockwise"></i> Pre-filled from your previous booking. Adjust as needed.</div>
<?php endif; ?>

<?php if (!$kycVerified): ?>
  <div class="alert alert-warning">
    <i class="bi bi-info-circle"></i>
    Your KYC is not yet verified. You can still submit a request — our team will reach out for any additional documents before confirming.
  </div>
<?php endif; ?>

<div class="row g-3">
  <div class="col-lg-7">
    <div class="card portal-request-form">
      <div class="card-body">
        <form method="post" action="<?= site_url('portal/request') ?>" novalidate>
          <?= csrf_field() ?>
          <div class="row g-3">
            <div class="col-sm-6">
              <label class="form-label">Pickup city *</label>
              <input class="form-control" name="pickup_city" value="<?= esc($pre('pickup_city')) ?>" required>
            </div>
            <div class="col-sm-6">
              <label class="form-label">Drop city *</label>
              <input class="form-control" name="drop_city" value="<?= esc($pre('drop_city')) ?>" required>
            </div>
            <div class="col-sm-6">
              <label class="form-label">Vehicle type *</label>
              <input class="form-control" name="vehicle_type_required" placeholder="e.g. 32ft MXL, Container 20ft" value="<?= esc($pre('vehicle_type_required')) ?>" required>
            </div>
            <div class="col-sm-6">
              <label class="form-label">Material</label>
              <input class="form-control" name="material_type" value="<?= esc($pre('material_type')) ?>">
            </div>
            <div class="col-sm-3">
              <label class="form-label">No. of trucks</label>
              <input class="form-control" type="number" min="1" max="50" name="vehicle_count" value="<?= esc($pre('vehicle_count', '1')) ?>">
            </div>
            <div class="col-sm-3">
              <label class="form-label">Weight (TON)</label>
              <input class="form-control" type="number" step="0.01" name="weight" value="<?= esc(old('weight')) ?>">
            </div>
            <div class="col-sm-3">
              <label class="form-label">Expected dispatch</label>
              <input class="form-control" type="date" name="expected_dispatch_date" value="<?= esc(old('expected_dispatch_date', $prefillDate ?? '')) ?>">
            </div>
            <div class="col-sm-3">
              <label class="form-label">Priority</label>
              <select class="form-select" name="priority">
                <?php foreach (['Low','Normal','High','Urgent'] as $p): ?>
                  <option value="<?= $p ?>" <?= old('priority', 'Normal') === $p ? 'selected' : '' ?>><?= $p ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-12">
              <label class="form-label">Remarks / instructions</label>
              <textarea class="form-control" name="remarks" rows="3"><?= esc($pre('remarks')) ?></textarea>
            </div>
          </div>
          <div class="mt-3 submit-row">
            <button class="btn btn-primary">Submit request</button>
            <a href="<?= site_url('portal') ?>" class="btn btn-light ms-1">Cancel</a>
          </div>
          <div class="text-muted mt-3" style="font-size:.8rem;">
            Submitting this form does not create a confirmed booking. Our team will revert with a quote based on availability.
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="col-lg-5">
    <div class="card">
      <div class="card-header">Recent requests</div>
      <div class="table-responsive">
        <table class="table table-sm mb-0">
          <thead><tr><th>#</th><th>Route</th><th>Vehicle</th><th>Status</th></tr></thead>
          <tbody>
          <?php if (empty($recent)): ?>
            <tr><td colspan="4" class="text-center text-muted py-3">No requests yet.</td></tr>
          <?php endif; ?>
          <?php foreach ($recent as $l): ?>
            <tr>
              <td><?= esc($l['lead_no']) ?><br><small class="text-muted"><?= esc(date('d-m', strtotime($l['created_at']))) ?></small></td>
              <td><?= esc(($l['pickup_city'] ?? '') . ' - ' . ($l['drop_city'] ?? '')) ?></td>
              <td><?= esc($l['vehicle_type_required'] ?: '—') ?><?= ($l['vehicle_count'] ?? 1) > 1 ? ' × ' . (int) $l['vehicle_count'] : '' ?></td>
              <td><?= esc($l['current_status']) ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
