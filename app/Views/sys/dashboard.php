<h5 class="mb-3"><i class="bi bi-speedometer2"></i> Diagnostics</h5>

<div class="row g-3 mb-3">
  <div class="col-md-6">
    <div class="card h-100">
      <div class="card-header">Runtime</div>
      <div class="card-body">
        <div class="stat-row">
          <div class="item"><div class="l">PHP</div><div class="v"><?= esc($diag['php_version']) ?></div></div>
          <div class="item"><div class="l">CodeIgniter</div><div class="v"><?= esc($diag['ci_version']) ?></div></div>
          <div class="item"><div class="l">Env</div><div class="v"><?= esc($diag['env']) ?></div></div>
          <div class="item"><div class="l">Server time</div><div class="v"><?= esc($diag['server_time']) ?></div></div>
          <div class="item"><div class="l">Memory limit</div><div class="v"><?= esc($diag['memory_limit']) ?></div></div>
          <div class="item"><div class="l">Free disk</div><div class="v"><?= esc($diag['free_disk_gb']) ?> GB</div></div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-md-6">
    <div class="card h-100">
      <div class="card-header">Database</div>
      <div class="card-body">
        <div class="stat-row">
          <div class="item"><div class="l">Driver</div><div class="v">MariaDB</div></div>
          <div class="item"><div class="l">Version</div><div class="v"><?= esc($diag['db_version']) ?></div></div>
          <div class="item"><div class="l">DB</div><div class="v"><?= esc($diag['db_name']) ?></div></div>
          <div class="item"><div class="l">Mail driver</div><div class="v"><?= esc($diag['mail_driver'] ?: '—') ?></div></div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row g-3 mb-3">
  <div class="col-md-6">
    <div class="card h-100">
      <div class="card-header">Email queue</div>
      <div class="card-body">
        <div class="stat-row">
          <?php foreach ($emailQueue as $r): ?>
            <div class="item"><div class="l"><?= esc($r['status']) ?></div><div class="v"><?= (int) $r['c'] ?></div></div>
          <?php endforeach; ?>
          <?php if (empty($emailQueue)): ?><div class="item">No email logs yet.</div><?php endif; ?>
        </div>
        <div class="mt-3 d-flex gap-2 flex-wrap">
          <form method="post" action="<?= site_url('sys/email-queue/flush') ?>" class="d-inline">
            <?= csrf_field() ?><button class="btn btn-sm btn-primary"><i class="bi bi-arrow-clockwise"></i> Flush queue</button>
          </form>
          <a href="<?= site_url('sys/email-queue') ?>" class="btn btn-sm btn-light">View queue →</a>
        </div>
      </div>
    </div>
  </div>
  <div class="col-md-6">
    <div class="card h-100">
      <div class="card-header">WhatsApp queue</div>
      <div class="card-body">
        <div class="stat-row">
          <?php foreach ($whatsappQueue as $r): ?>
            <div class="item"><div class="l"><?= esc($r['delivery_status'] ?: '(unset)') ?></div><div class="v"><?= (int) $r['c'] ?></div></div>
          <?php endforeach; ?>
          <?php if (empty($whatsappQueue)): ?><div class="item">No WA logs yet.</div><?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row g-3 mb-3">
  <div class="col-md-6">
    <div class="card h-100">
      <div class="card-header">Table sizes</div>
      <div class="table-responsive">
        <table class="table table-sm mb-0">
          <thead><tr><th>Table</th><th class="text-end">Rows</th></tr></thead>
          <tbody>
            <?php foreach ($tableSizes as $t => $n): ?>
              <tr><td><code><?= esc($t) ?></code></td><td class="text-end"><?= $n >= 0 ? number_format($n) : '<span class="text-danger">err</span>' ?></td></tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <div class="col-md-6">
    <div class="card h-100">
      <div class="card-header">Maintenance &amp; access</div>
      <div class="card-body">
        <form method="post" action="<?= site_url('sys/maintenance/toggle') ?>" class="mb-3">
          <?= csrf_field() ?>
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" id="mm" disabled <?= $maintMode ? 'checked' : '' ?>>
            <label class="form-check-label" for="mm">
              Maintenance mode is <strong><?= $maintMode ? 'ON — only super-admins can sign in' : 'off' ?></strong>
            </label>
          </div>
          <textarea name="maintenance_message" class="form-control mt-2" rows="2" placeholder="Message shown to users (optional)"></textarea>
          <button class="btn btn-sm btn-primary mt-2"><?= $maintMode ? 'Turn maintenance OFF' : 'Turn maintenance ON' ?></button>
        </form>

        <hr style="border-color:#232831;">

        <form method="post" action="<?= site_url('sys/ip-allowlist') ?>">
          <?= csrf_field() ?>
          <label class="form-label">Super-admin IP allowlist (CIDR or single IP, comma-separated; empty = open)</label>
          <input class="form-control mono" name="list" value="<?= esc($ipAllowlist) ?>" placeholder="203.0.113.5, 198.51.100.0/24">
          <button class="btn btn-sm btn-primary mt-2">Save allowlist</button>
        </form>

        <hr style="border-color:#232831;">

        <div class="d-flex gap-2 flex-wrap">
          <form method="post" action="<?= site_url('sys/cache/clear') ?>" class="d-inline" onsubmit="return confirm('Clear all CI4 cache?');">
            <?= csrf_field() ?><button class="btn btn-sm btn-light"><i class="bi bi-eraser"></i> Clear cache</button>
          </form>
          <form method="post" action="<?= site_url('sys/sessions/purge') ?>" class="d-inline" onsubmit="return confirm('PURGE every user session? Everyone will need to sign in again.');">
            <?= csrf_field() ?><button class="btn btn-sm btn-light"><i class="bi bi-people-slash"></i> Purge sessions</button>
          </form>
          <form method="post" action="<?= site_url('sys/backup') ?>" class="d-inline">
            <?= csrf_field() ?><button class="btn btn-sm btn-light"><i class="bi bi-download"></i> Backup database</button>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
