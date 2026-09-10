<h5 class="mb-3"><i class="bi bi-person-arms-up"></i> Impersonate Tenant User</h5>
<p class="text-muted" style="font-size:.85rem;">You will be logged in as the chosen user with their permissions. A purple banner is shown while impersonating; click "Return to super-admin" to come back.</p>

<div class="card">
  <div class="table-responsive">
    <table class="table table-sm align-middle mb-0">
      <thead><tr><th>#</th><th>Name</th><th>Email</th><th>Role</th><th></th></tr></thead>
      <tbody>
      <?php if (empty($rows)): ?>
        <tr><td colspan="5" class="text-center text-muted py-3">No active tenant users.</td></tr>
      <?php endif; ?>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><?= (int) $r['id'] ?></td>
          <td><?= esc($r['name']) ?></td>
          <td><?= esc($r['email']) ?></td>
          <td><?= esc($r['role_name'] ?? '—') ?></td>
          <td>
            <form method="post" action="<?= site_url('sys/impersonate/' . $r['id']) ?>" style="display:inline;" onsubmit="return confirm('Impersonate ' + <?= json_encode($r['email']) ?> + '? This is recorded in the audit log.');">
              <?= csrf_field() ?>
              <button class="btn btn-sm btn-primary"><i class="bi bi-box-arrow-in-right"></i> Impersonate</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
