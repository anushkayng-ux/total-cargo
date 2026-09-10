<h5 class="mb-3"><i class="bi bi-terminal"></i> SQL Console (read-only)</h5>
<p class="text-muted" style="font-size:.85rem;">SELECT / SHOW / DESCRIBE / EXPLAIN only. Limit clamped to 1000 rows.</p>

<div class="card mb-3">
  <div class="card-body">
    <form method="post" action="<?= site_url('sys/sql') ?>">
      <?= csrf_field() ?>
      <textarea name="sql" rows="6" class="form-control mono" placeholder="SELECT ..." spellcheck="false"><?= esc($sql) ?></textarea>
      <div class="d-flex gap-2 mt-2 flex-wrap">
        <button class="btn btn-sm btn-primary"><i class="bi bi-play-fill"></i> Run</button>
        <button type="button" class="btn btn-sm btn-light" onclick="document.querySelector('textarea').value='SELECT * FROM email_logs ORDER BY id DESC LIMIT 50';">Recent emails</button>
        <button type="button" class="btn btn-sm btn-light" onclick="document.querySelector('textarea').value='SHOW TABLES';">Tables</button>
        <button type="button" class="btn btn-sm btn-light" onclick="document.querySelector('textarea').value='SELECT id, name, email, status FROM users WHERE deleted_at IS NULL';">Users</button>
      </div>
    </form>
  </div>
</div>

<?php if ($error): ?>
  <div class="alert alert-danger"><strong>SQL error:</strong> <?= esc($error) ?></div>
<?php endif; ?>

<?php if ($rows !== null && empty($error)): ?>
  <div class="card">
    <div class="card-header"><?= (int) $rowCount ?> row<?= $rowCount === 1 ? '' : 's' ?></div>
    <div class="table-responsive">
      <table class="table table-sm mono mb-0">
        <thead><tr><?php foreach ($columns as $c): ?><th><?= esc($c) ?></th><?php endforeach; ?></tr></thead>
        <tbody>
        <?php if (empty($rows)): ?>
          <tr><td colspan="<?= max(1, count($columns)) ?>" class="text-center text-muted py-3">No rows.</td></tr>
        <?php endif; ?>
        <?php foreach ($rows as $r): ?>
          <tr><?php foreach ($columns as $c): ?><td style="white-space:pre-wrap;word-break:break-word;max-width:400px;"><?= esc((string) ($r[$c] ?? '')) ?></td><?php endforeach; ?></tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endif; ?>
