<?= tpt_toolbar([
    'new_href'       => site_url('rate-contracts/create'),
    'new_item_label' => 'New Rate Contract',
    'close_href'     => site_url('dashboard'),
    'auth'           => $auth,
]) ?>
<div class="tabs">
  <div class="tab active">All Rate Contracts</div>
  <div class="spacer"></div>
  <div class="recordnav"><?= (int) ($pager->getTotal() ?: count($rows)) ?> total records</div>
</div>

<div class="gridwrap">
  <div class="table-responsive">
    <table class="table grid mb-0">
      <thead><tr><th>#</th><th>Client</th><th>Valid</th><th>Status</th><th>GST / TDS</th><th class="text-end">Actions</th></tr></thead>
      <tbody>
        <?php if (empty($rows)): ?>
          <tr><td colspan="6" class="text-center text-muted py-4">No contracts yet. <a href="<?= site_url('rate-contracts/create') ?>">Create one →</a></td></tr>
        <?php endif; ?>
        <?php foreach ($rows as $r): ?>
          <tr class="row-link" data-href="<?= site_url('rate-contracts/' . $r['id'] . '/edit') ?>">
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
  <?php if (!empty($pager)): ?><div class="mt-3"><?= $pager->links() ?></div><?php endif; ?>
</div>
