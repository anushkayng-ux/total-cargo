<h5 class="mb-3"><i class="bi bi-toggles"></i> Feature Flags</h5>
<p class="text-muted">Disable a module to hide it across the entire tenant — useful for free-tier installs.</p>

<div class="card">
  <div class="table-responsive">
    <table class="table table-sm align-middle mb-0">
      <thead><tr><th>Flag</th><th>State</th><th>Notes</th><th>Updated</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($flags as $f): ?>
          <tr>
            <td><code><?= esc($f['flag_key']) ?></code></td>
            <td>
              <?php if ((int) $f['enabled'] === 1): ?>
                <span class="badge bg-success">Enabled</span>
              <?php else: ?>
                <span class="badge bg-secondary">Disabled</span>
              <?php endif; ?>
            </td>
            <td><?= esc($f['notes'] ?: '—') ?></td>
            <td><?= esc($f['updated_at'] ?? '—') ?></td>
            <td>
              <form method="post" action="<?= site_url('sys/feature-flags/' . $f['id'] . '/toggle') ?>" style="display:inline;">
                <?= csrf_field() ?>
                <button class="btn btn-sm btn-light"><i class="bi bi-power"></i> Toggle</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
