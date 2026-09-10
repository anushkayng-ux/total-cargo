<div class="d-flex align-items-center mb-3 gap-2 flex-wrap">
  <h5 class="m-0"><?= esc($pageTitle) ?></h5>
  <?php if (!$service->isConfigured()): ?>
    <span class="badge-soft badge-warn ms-2">API credentials not set — messages are queued only</span>
  <?php else: ?>
    <span class="badge-soft badge-ok ms-2">API configured</span>
  <?php endif; ?>
</div>

<div class="card">
  <div class="table-responsive">
    <table class="table mb-0" data-tpt-cols="wa-logs">
      <thead>
        <tr><th data-col="id">ID</th><th data-col="module">Module</th><th data-col="to">To</th><th data-col="template">Template</th><th data-col="type">Type</th><th data-col="status">Status</th><th data-col="sent">Sent</th><th data-col="delivered">Delivered</th><th data-col="error">Error</th></tr>
      </thead>
      <tbody>
        <?php if (empty($rows)): ?><tr><td colspan="9" class="text-center text-muted">No outgoing messages yet.</td></tr><?php endif; ?>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td data-col="id" data-label="ID"><?= (int) $r['id'] ?></td>
            <td data-col="module" data-label="Module"><?= esc($r['module_name']) ?><?= $r['module_ref_id'] ? ' #' . (int) $r['module_ref_id'] : '' ?></td>
            <td data-col="to" data-label="To"><?= esc($r['recipient_no']) ?></td>
            <td data-col="template" data-label="Template"><code><?= esc($r['template_key']) ?></code></td>
            <td data-col="type" data-label="Type"><?= esc($r['message_type']) ?></td>
            <td data-col="status" data-label="Status">
              <?php
                $s = (string) $r['delivery_status'];
                $cls = match ($s) {
                    'Sent','Delivered','Read' => 'badge-soft badge-ok',
                    'Queued'                  => 'badge-soft badge-warn',
                    'Failed'                  => 'badge-soft badge-danger',
                    default                   => 'badge-soft',
                };
              ?>
              <span class="<?= $cls ?>"><?= esc($s) ?></span>
            </td>
            <td data-col="sent" data-label="Sent"><?= esc($r['sent_at'] ?? '—') ?></td>
            <td data-col="delivered" data-label="Delivered"><?= esc($r['delivered_at'] ?? '—') ?></td>
            <td data-col="error" data-label="Error" style="max-width:260px;"><?= esc($r['error_message']) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php if (!empty($pager)): ?><div class="mt-3"><?= $pager->links() ?></div><?php endif; ?>
