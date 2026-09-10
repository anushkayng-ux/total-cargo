<div class="d-flex justify-content-between align-items-center mb-3">
  <h5 class="m-0"><i class="bi bi-database-gear"></i> Migrations</h5>
  <form method="post" action="<?= site_url('sys/migrations/run') ?>">
    <?= csrf_field() ?>
    <button class="btn btn-sm btn-primary"><i class="bi bi-arrow-clockwise"></i> Run pending</button>
  </form>
</div>

<div class="row g-3">
  <div class="col-md-6">
    <div class="card">
      <div class="card-header">Files on disk</div>
      <div class="table-responsive">
        <table class="table table-sm mb-0 mono">
          <tbody>
          <?php foreach ($files as $f): ?>
            <tr><td><?= esc($f) ?></td></tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <div class="col-md-6">
    <div class="card">
      <div class="card-header">Applied (most recent first)</div>
      <div class="table-responsive">
        <table class="table table-sm mb-0 mono">
          <thead><tr><th>Version</th><th>Class</th><th>Group</th><th>Time</th></tr></thead>
          <tbody>
          <?php if (empty($applied)): ?>
            <tr><td colspan="4" class="text-center text-muted py-3">No migrations table yet.</td></tr>
          <?php endif; ?>
          <?php foreach ($applied as $m): ?>
            <tr>
              <td><?= esc($m['version'] ?? '') ?></td>
              <td style="font-size:.75rem;"><?= esc($m['class'] ?? '') ?></td>
              <td><?= esc($m['group'] ?? 'default') ?></td>
              <td><?= !empty($m['time']) ? esc(date('d-m-Y H:i', (int) $m['time'])) : '—' ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
