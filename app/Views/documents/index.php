<?php
$moduleLink = function (string $m, int $ref): ?string {
    return match ($m) {
        'trip'         => site_url('trips/' . $ref),
        'booking'      => site_url('bookings/' . $ref),
        'invoice'      => site_url('invoices/' . $ref),
        'vendor'       => site_url('vendors/' . $ref . '/edit'),
        'client'       => site_url('clients/' . $ref . '/edit'),
        'vendor_bill'  => site_url('vendor-bills/' . $ref),
        'rfq'          => site_url('rfq/' . $ref),
        'lead'         => site_url('leads/' . $ref),
        default        => null,
    };
};
$verCls = fn($s) => match ((string) $s) {
    'Verified' => 'badge-soft badge-ok',
    'Rejected' => 'badge-soft badge-danger',
    default    => 'badge-soft badge-warn',
};
$anyFilter = ($search !== '' || $module !== '' || $type !== '' || $verified !== '');

$extra = '<form method="get" action="' . site_url('documents') . '" class="d-flex align-items-center gap-2 flex-wrap m-0">'
    . '<input type="text" name="q" class="form-control form-control-sm" style="width:170px;" placeholder="file name / remarks" value="' . esc($search) . '">'
    . '<select name="module" class="form-select form-select-sm" style="width:auto;"><option value="">All Modules</option>';
foreach ($modules as $m) {
    $extra .= '<option value="' . esc($m) . '"' . ($module === $m ? ' selected' : '') . '>' . esc(ucfirst(str_replace('_', ' ', $m))) . '</option>';
}
$extra .= '</select><select name="type" class="form-select form-select-sm" style="width:auto;"><option value="">All Types</option>';
foreach ($types as $t) {
    $extra .= '<option value="' . esc($t) . '"' . ($type === $t ? ' selected' : '') . '>' . esc($t) . '</option>';
}
$extra .= '</select><select name="verified" class="form-select form-select-sm" style="width:auto;"><option value="">Any Status</option>'
    . '<option value="Pending"' . ($verified === 'Pending' ? ' selected' : '') . '>Pending</option>'
    . '<option value="Verified"' . ($verified === 'Verified' ? ' selected' : '') . '>Verified</option>'
    . '<option value="Rejected"' . ($verified === 'Rejected' ? ' selected' : '') . '>Rejected</option>'
    . '</select>'
    . '<button class="btn btn-sm btn-outline-dark">Filter</button>';
if ($anyFilter) {
    $extra .= '<a class="btn btn-sm btn-light" href="' . site_url('documents') . '" title="Reset"><i class="bi bi-x-lg"></i></a>';
}
$extra .= '</form>';

echo tpt_toolbar([
    'close_href' => site_url('dashboard'),
    'extra'      => $extra,
    'auth'       => $auth,
]);
?>
<div class="tabs" role="tablist">
  <div class="tab active">All Documents</div>
  <?php if ($anyFilter): ?><span class="badge-soft" style="margin-left:10px;">Filtered</span><?php endif; ?>
  <div class="spacer"></div>
  <div class="recordnav"><?= (int) ($pager->getTotal() ?: count($rows)) ?> total records</div>
</div>

<div class="gridwrap">
  <div class="table-responsive">
    <table class="table grid mobile-cards mb-0" data-tpt-cols="documents">
      <thead>
        <tr>
          <th data-col="id">#</th><th data-col="module">Module</th><th data-col="type">Type</th><th data-col="file">File</th>
          <th data-col="source">Source</th><th data-col="status">Status</th><th data-col="uploaded">Uploaded</th><th data-col="by">By</th>
          <th class="text-end" data-col="actions">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($rows)): ?>
          <tr><td colspan="9" class="text-center text-muted">
            No documents yet. POD, LR, RC, invoice copies and incoming WhatsApp media all land here.
          </td></tr>
        <?php endif; ?>
        <?php foreach ($rows as $r):
          $link = $moduleLink($r['module_name'], (int) $r['module_ref_id']);
        ?>
          <tr>
            <td data-col="id" data-label="#"><?= (int) $r['id'] ?></td>
            <td data-col="module" data-label="Module">
              <span class="badge-soft"><?= esc($r['module_name']) ?></span>
              <?php if ($link): ?>
                <a href="<?= $link ?>"><code>#<?= (int) $r['module_ref_id'] ?></code></a>
              <?php else: ?>
                <code>#<?= (int) $r['module_ref_id'] ?></code>
              <?php endif; ?>
            </td>
            <td data-col="type" data-label="Type"><span class="badge-soft"><?= esc($r['document_type']) ?></span></td>
            <td data-col="file" data-label="File"><?= esc($r['original_file_name']) ?><br>
              <small class="text-muted"><?= esc(round(((int) $r['file_size']) / 1024, 1)) ?> KB · <?= esc($r['mime_type']) ?></small>
            </td>
            <td data-col="source" data-label="Source"><span class="badge-soft"><?= esc($r['source_channel']) ?></span></td>
            <td data-col="status" data-label="Status">
              <span class="<?= $verCls($r['verification_status']) ?>"><?= esc($r['verification_status']) ?></span>
            </td>
            <td data-col="uploaded" data-label="Uploaded"><?= esc(tpt_dt($r['created_at'])) ?></td>
            <td data-col="by" data-label="By"><?= esc($r['uploaded_by_name'] ?? '—') ?></td>
            <td class="text-end" data-col="actions" data-label="Actions">
              <a class="btn btn-sm btn-light" href="<?= site_url('documents/' . $r['id'] . '/download') ?>" title="Download"><i class="bi bi-download"></i></a>
              <?php if ($r['verification_status'] !== 'Verified'): ?>
                <form class="d-inline" method="post" action="<?= site_url('documents/' . $r['id'] . '/verify') ?>">
                  <?= csrf_field() ?>
                  <input type="hidden" name="verification_status" value="Verified">
                  <button class="btn btn-sm btn-light" title="Mark verified"><i class="bi bi-check2"></i></button>
                </form>
              <?php endif; ?>
              <?php if ($r['verification_status'] !== 'Rejected'): ?>
                <form class="d-inline" method="post" action="<?= site_url('documents/' . $r['id'] . '/verify') ?>">
                  <?= csrf_field() ?>
                  <input type="hidden" name="verification_status" value="Rejected">
                  <button class="btn btn-sm btn-light" title="Mark rejected"><i class="bi bi-x"></i></button>
                </form>
              <?php endif; ?>
              <form class="d-inline" method="post" action="<?= site_url('documents/' . $r['id'] . '/delete') ?>" data-confirm="Delete this document?">
                <?= csrf_field() ?>
                <button class="btn btn-sm btn-light" title="Delete"><i class="bi bi-trash"></i></button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php if (!empty($pager)): ?><div class="mt-3"><?= $pager->links() ?></div><?php endif; ?>
</div>
