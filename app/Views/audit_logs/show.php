<?php
$val = fn ($v, $empty = '—') => ($v === null || $v === '') ? $empty : esc((string) $v);
$old = !empty($row['old_value_json']) ? json_decode((string) $row['old_value_json'], true) : null;
$new = !empty($row['new_value_json']) ? json_decode((string) $row['new_value_json'], true) : null;
?>
<?= tpt_toolbar([
    'close_href' => site_url('audit-logs'),
    'auth'       => $auth,
]) ?>
<div class="tabs">
  <div class="tab active">Audit Entry</div>
  <div class="spacer"></div>
  <div class="recordnav"><a href="<?= site_url('audit-logs') ?>"><i class="bi bi-list"></i> List</a></div>
</div>

<div class="formwrap">
  <div class="retro-row">
    <div class="retro-field"><label>When :</label><div class="retro-box wide"><?= $val($row['created_at']) ?></div></div>
    <div class="retro-field"><label>User :</label><div class="retro-box wide"><?= $val($row['user_name'] ?? null) ?></div></div>
    <div class="retro-field"><label>Module :</label><div class="retro-box wide"><?= $val($row['module_name']) . ($row['module_ref_id'] ? ' #' . (int) $row['module_ref_id'] : '') ?></div></div>
    <div class="retro-field"><label>Action :</label><div class="retro-box"><?= $val($row['action_type']) ?></div></div>
  </div>
  <div class="retro-row">
    <div class="retro-field"><label>IP :</label><div class="retro-box wide"><?= $val($row['ip_address']) ?></div></div>
    <div class="retro-field" style="width:100%;"><label>User Agent :</label><div class="retro-box xwide" style="min-width:400px;word-break:break-all;"><?= $val($row['user_agent']) ?></div></div>
  </div>
  <div class="retro-row" style="align-items:flex-start;">
    <div class="retro-field" style="width:100%;"><label style="white-space:nowrap;">Description :</label>
      <div class="retro-particulars"><?= $val($row['action_description']) ?></div>
    </div>
  </div>

  <div class="row g-3" style="margin-top:14px;">
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
</div>
