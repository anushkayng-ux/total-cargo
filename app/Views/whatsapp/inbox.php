<div class="d-flex align-items-center mb-3 gap-2 flex-wrap">
  <h5 class="m-0"><?= esc($pageTitle) ?></h5>
</div>

<div class="card">
  <div class="table-responsive">
    <table class="table mb-0" data-tpt-cols="wa-inbox">
      <thead>
        <tr><th data-col="from">From</th><th data-col="type">Type</th><th data-col="text">Text / Caption</th><th data-col="rfq">Matched RFQ</th><th data-col="vendor">Vendor</th><th data-col="processed">Processed</th><th data-col="received">Received</th></tr>
      </thead>
      <tbody>
        <?php if (empty($rows)): ?><tr><td colspan="7" class="text-center text-muted">No incoming messages yet.</td></tr><?php endif; ?>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td data-col="from" data-label="From"><code><?= esc($r['sender_no']) ?></code></td>
            <td data-col="type" data-label="Type"><?= esc($r['message_type']) ?></td>
            <td data-col="text" data-label="Text" style="max-width:420px;word-break:break-word;"><?= esc($r['text_body']) ?></td>
            <td data-col="rfq" data-label="RFQ"><?= $r['rfq_id'] ? '<a href="' . site_url('rfq/' . (int) $r['rfq_id']) . '">#' . (int) $r['rfq_id'] . '</a>' : '—' ?></td>
            <td data-col="vendor" data-label="Vendor"><?= $r['vendor_id'] ? '#' . (int) $r['vendor_id'] : '—' ?></td>
            <td data-col="processed" data-label="Processed"><?= (int)$r['is_processed'] === 1 ? '<span class="badge-soft badge-ok">Yes</span>' : '<span class="badge-soft badge-warn">No</span>' ?></td>
            <td data-col="received" data-label="Received"><?= esc($r['received_at']) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php if (!empty($pager)): ?><div class="mt-3"><?= $pager->links() ?></div><?php endif; ?>
