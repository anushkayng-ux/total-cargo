<div class="d-flex align-items-center mb-3 gap-2">
  <h5 class="m-0">Rate Contracts</h5>
  <a href="<?= site_url('rate-contracts/create') ?>" class="btn btn-sm btn-primary ms-auto"><i class="bi bi-plus-lg"></i> New contract</a>
</div>

<div class="card">
  <div class="table-responsive">
    <table class="table table-sm align-middle mb-0">
      <thead><tr><th>#</th><th>Client</th><th>Valid</th><th>Status</th><th>GST / TDS</th><th class="text-end">Actions</th></tr></thead>
      <tbody>
        <?php if (empty($rows)): ?>
          <tr><td colspan="6" class="text-center text-muted py-4">No contracts yet. <a href="<?= site_url('rate-contracts/create') ?>">Create one →</a></td></tr>
        <?php endif; ?>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td><code><?= esc($r['contract_no']) ?></code></td>
            <td><?= esc($r['client_company']) ?></td>
            <td><?= esc(date('d-m-Y', strtotime($r['valid_from']))) ?> → <?= esc(date('d-m-Y', strtotime($r['valid_to']))) ?></td>
            <td>
              <span class="badge-soft <?= $r['status'] === 'Active' ? 'badge-ok' : 'badge-warn' ?>"><?= esc($r['status']) ?></span>
            </td>
            <td>
              <?= esc(strtoupper($r['gst_treatment'])) ?>
              <?php if (!empty($r['tds_rate'])): ?> · TDS <?= esc($r['tds_rate']) ?>%<?php endif; ?>
            </td>
            <td class="text-end">
              <a class="btn btn-sm btn-light" href="<?= site_url('rate-contracts/' . $r['id'] . '/edit') ?>"><i class="bi bi-pencil"></i></a>
              <form class="d-inline" method="post" action="<?= site_url('rate-contracts/' . $r['id'] . '/delete') ?>" onsubmit="return confirm('Delete contract?');">
                <?= csrf_field() ?><button class="btn btn-sm btn-light"><i class="bi bi-trash"></i></button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php if (!empty($pager)): ?><div class="mt-3"><?= $pager->links() ?></div><?php endif; ?>
