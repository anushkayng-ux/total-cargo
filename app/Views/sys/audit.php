<h5 class="mb-3"><i class="bi bi-shield-shaded"></i> Super-Admin Audit</h5>
<p class="text-muted" style="font-size:.85rem;">Append-only log of every super-admin action. Tenant admins can never read this table.</p>

<div class="card">
  <div class="table-responsive">
    <table class="table table-sm mb-0">
      <thead><tr><th>When</th><th>Who</th><th>Action</th><th>Target</th><th>Description</th><th>IP</th></tr></thead>
      <tbody>
      <?php if (empty($rows)): ?>
        <tr><td colspan="6" class="text-center text-muted py-3">Nothing logged yet.</td></tr>
      <?php endif; ?>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><?= esc(date('d-m-Y H:i:s', strtotime($r['created_at']))) ?></td>
          <td><?= esc($r['author'] ?: ('user#' . $r['user_id'])) ?></td>
          <td><span class="badge-sys"><?= esc($r['action']) ?></span></td>
          <td><?= esc(($r['target_type'] ?: '') . ($r['target_id'] ? ' #' . $r['target_id'] : '')) ?></td>
          <td style="max-width:400px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= esc($r['description'] ?: '—') ?></td>
          <td class="mono" style="font-size:.78rem;"><?= esc($r['ip_address'] ?: '—') ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php if ($pager): ?><div class="mt-3"><?= $pager->links() ?></div><?php endif; ?>
