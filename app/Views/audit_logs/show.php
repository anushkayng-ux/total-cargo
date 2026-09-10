<?php
$old = !empty($row['old_value_json']) ? json_decode((string) $row['old_value_json'], true) : null;
$new = !empty($row['new_value_json']) ? json_decode((string) $row['new_value_json'], true) : null;
?>
<div class="d-flex align-items-center mb-3 gap-2">
  <h5 class="m-0"><?= esc($pageTitle) ?></h5>
  <a class="ms-auto btn btn-sm btn-light" href="<?= site_url('audit-logs') ?>"><i class="bi bi-arrow-left"></i> Back</a>
</div>

<div class="card mb-3"><div class="card-body">
  <div class="row g-3" style="font-size:.92rem;">
    <div class="col-md-3"><div class="text-muted">When</div><div><?= esc($row['created_at']) ?></div></div>
    <div class="col-md-3"><div class="text-muted">User</div><div><?= esc($row['user_name'] ?? '—') ?></div></div>
    <div class="col-md-3"><div class="text-muted">Module</div><div><?= esc($row['module_name']) ?><?= $row['module_ref_id'] ? ' #' . (int) $row['module_ref_id'] : '' ?></div></div>
    <div class="col-md-3"><div class="text-muted">Action</div><div><?= esc($row['action_type']) ?></div></div>
    <div class="col-md-6"><div class="text-muted">IP</div><div><?= esc($row['ip_address']) ?></div></div>
    <div class="col-md-6"><div class="text-muted">User Agent</div><div style="word-break:break-all;"><?= esc($row['user_agent']) ?></div></div>
    <div class="col-12"><div class="text-muted">Description</div><div><?= esc($row['action_description']) ?></div></div>
  </div>
</div></div>

<div class="row g-3">
  <div class="col-lg-6">
    <div class="card"><div class="card-header">Before</div>
      <div class="card-body">
        <?php if (empty($old)): ?><div class="text-muted">—</div>
        <?php else: ?>
          <pre style="background:var(--tpt-surface);padding:10px;border-radius:10px;font-size:.8rem;overflow:auto;max-height:500px;"><?= esc(json_encode($old, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></pre>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="card"><div class="card-header">After</div>
      <div class="card-body">
        <?php if (empty($new)): ?><div class="text-muted">—</div>
        <?php else: ?>
          <pre style="background:var(--tpt-surface);padding:10px;border-radius:10px;font-size:.8rem;overflow:auto;max-height:500px;"><?= esc(json_encode($new, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></pre>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
