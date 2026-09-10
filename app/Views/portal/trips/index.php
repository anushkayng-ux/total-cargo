<h5 class="mb-3">My Trips</h5>

<form method="get" class="row g-2 mb-3">
  <div class="col-12 col-md-6">
    <input class="form-control form-control-sm" name="q" placeholder="Search trip / LR / vehicle / route" value="<?= esc($search) ?>">
  </div>
  <div class="col-8 col-md-4">
    <select name="status" class="form-select form-select-sm">
      <option value="">All statuses</option>
      <?php foreach ($statuses as $s): ?>
        <option value="<?= esc($s) ?>" <?= $s === $status ? 'selected' : '' ?>><?= esc($s) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-4 col-md-2"><button class="btn btn-sm btn-light w-100">Filter</button></div>
</form>

<div class="card">
  <div class="table-responsive">
    <table class="table table-sm align-middle mb-0">
      <thead><tr><th>Trip</th><th>LR</th><th>Route</th><th>Vehicle</th><th>Dispatch</th><th>Status</th></tr></thead>
      <tbody>
      <?php if (empty($rows)): ?>
        <tr><td colspan="6" class="text-center text-muted py-4">No trips yet.</td></tr>
      <?php endif; ?>
      <?php foreach ($rows as $t): ?>
        <tr>
          <td><a href="<?= site_url('portal/trips/' . $t['id']) ?>"><?= esc($t['trip_no']) ?></a></td>
          <td><?= esc($t['lr_no'] ?: '—') ?></td>
          <td><?= esc(tpt_route($t['route_text'] ?? "")) ?></td>
          <td><?= esc($t['vehicle_number'] ?: '—') ?></td>
          <td><?= !empty($t['dispatch_datetime']) ? esc(date('d-m H:i', strtotime($t['dispatch_datetime']))) : '—' ?></td>
          <td><?= esc($t['current_status']) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
